<?php
/**
 * PAYMENT MANUAL - Multi Gateway (GreePay + AurenPay + BSPay + ExpfyPay)
 * Processamento de saques PIX com múltiplos gateways e hierarquia de afiliados
 * Versão com IPv4 forçado e retry automático
 * 
 * ATUALIZAÇÃO: Adicionado suporte para GreePay como gateway prioritário
 * ATUALIZAÇÃO: Logs completos no arquivo daanrox.txt
 */

ini_set('display_errors', 0);
error_reporting(E_ALL);

session_start();

// Includes necessários
include_once('../services/database.php');
include_once('../services/funcao.php');
include_once('../services/crud.php');

// ==================== FUNÇÃO DE LOG PARA DAANROX.TXT ====================

function logToDaanrox($message, $data = [], $type = 'INFO')
{
    $log_file = __DIR__ . '/daanrox.txt';
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    $request_id = uniqid('req_', true);
    
    // Formatar a mensagem
    $log_entry = [
        'timestamp' => $timestamp,
        'request_id' => $request_id,
        'type' => $type,
        'ip' => $ip,
        'user_agent' => $user_agent,
        'message' => $message,
        'data' => $data
    ];
    
    // Converter para JSON com formatação legível
    $log_line = json_encode($log_entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . "\n" . str_repeat('-', 100) . "\n";
    
    // Escrever no arquivo
    file_put_contents($log_file, $log_line, FILE_APPEND | LOCK_EX);
    
    // Também enviar para o error_log padrão
    error_log("[DAANROX] " . $message . " - " . json_encode($data, JSON_UNESCAPED_UNICODE));
}

// Função para log de requisições/respostas HTTP completas
function logHttpTransaction($gateway, $url, $request_data, $response_data, $http_code, $success)
{
    logToDaanrox("HTTP Transaction - $gateway", [
        'url' => $url,
        'request' => $request_data,
        'response' => [
            'http_code' => $http_code,
            'body' => $response_data,
            'success' => $success
        ]
    ], 'HTTP');
}

// Função para log de erros detalhados
function logError($component, $error, $context = [])
{
    logToDaanrox("ERROR in $component", [
        'error' => $error,
        'context' => $context,
        'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5)
    ], 'ERROR');
}

// ==================== FUNÇÕES AUXILIARES ====================

function validar_2fa_admin($codigo_2fa){
    global $mysqli;
    if(empty($_SESSION['data_adm']['id'])) return false;
    $admin_id = intval($_SESSION['data_adm']['id']);
    $qry = $mysqli->prepare("SELECT `2fa` FROM admin_users WHERE id = ?");
    $qry->bind_param("i", $admin_id);
    $qry->execute();
    $res = $qry->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    if(!$row || empty($row['2fa'])) return false;
    $stored = $row['2fa'];
    $codigo_2fa = trim($codigo_2fa);
    if(strlen($stored) >= 60){
        return password_verify($codigo_2fa, $stored);
    }
    return hash_equals($stored, $codigo_2fa);
}

function formatCnpjCpf($value)
{
    $CPF_LENGTH = 11;
    $cnpj_cpf = preg_replace("/\D/", '', $value);

    if (strlen($cnpj_cpf) === $CPF_LENGTH) {
        return preg_replace("/(\d{3})(\d{3})(\d{3})(\d{2})/", "\$1.\$2.\$3-\$4", $cnpj_cpf);
    }

    return preg_replace("/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/", "\$1.\$2.\$3/\$4-\$5", $cnpj_cpf);
}

function validaCPF($cpf)
{
    $cpf = preg_replace('/[^0-9]/is', '', $cpf);

    if (strlen($cpf) != 11) {
        return false;
    }

    if (preg_match('/(\d)\1{10}/', $cpf)) {
        return false;
    }

    for ($t = 9; $t < 11; $t++) {
        for ($d = 0, $c = 0; $c < $t; $c++) {
            $d += $cpf[$c] * (($t + 1) - $c);
        }

        $d = ((10 * $d) % 11) % 10;

        if ($cpf[$c] != $d) {
            return false;
        }
    }

    return true;
}

function identificarTipoChavePix($chavepix)
{
    if (preg_match('/^\d{10,11}$/', $chavepix)) {
        if (validaCPF($chavepix)) {
            return 'document';
        }
        return 'phoneNumber';
    } elseif (preg_match('/^\d{11}$/', $chavepix)) {
        return 'document';
    } elseif (preg_match('/^\d{14}$/', $chavepix)) {
        return 'document';
    } elseif (filter_var($chavepix, FILTER_VALIDATE_EMAIL)) {
        return 'email';
    } elseif (preg_match('/^[0-9a-f]{32}$/i', $chavepix)) {
        return 'randomKey';
    } else {
        return 'invalid';
    }
}

function logAction($message, $data = [])
{
    $log_entry = date('Y-m-d H:i:s') . " - " . $message . " - " . json_encode($data, JSON_UNESCAPED_UNICODE);
    error_log("[PAYMENT_MULTI_GATEWAY] " . $log_entry);
    // Também registrar no daanrox.txt
    logToDaanrox($message, $data, 'ACTION');
}

function sendResponse($success, $message, $data = null, $http_code = 200)
{
    http_response_code($http_code);
    
    $response = [
        "success" => $success,
        "message" => $message,
        "timestamp" => date('c')
    ];
    
    if ($data !== null) {
        $response["data"] = $data;
    }
    
    // Log da resposta enviada
    logToDaanrox("Resposta enviada", [
        'http_code' => $http_code,
        'response' => $response
    ], 'RESPONSE');
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit();
}

// ==================== CLASSE PARA PROCESSAMENTO DE GATEWAYS ====================

class MultiGatewayProcessor
{
    private $mysqli;
    private $debug = [];
    private $transaction_log = [];
    
    public function __construct($mysqli)
    {
        $this->mysqli = $mysqli;
        $this->transaction_log['start_time'] = microtime(true);
        $this->transaction_log['steps'] = [];
        
        logToDaanrox("MultiGatewayProcessor inicializado", [
            'mysql_info' => [
                'host_info' => $mysqli->host_info,
                'server_info' => $mysqli->server_info
            ]
        ], 'INIT');
    }
    
    public function getDebug()
    {
        return $this->debug;
    }
    
    public function getTransactionLog()
    {
        $this->transaction_log['total_time'] = microtime(true) - $this->transaction_log['start_time'];
        return $this->transaction_log;
    }
    
    private function addStep($step, $details)
    {
        $this->transaction_log['steps'][] = [
            'step' => $step,
            'time' => microtime(true) - $this->transaction_log['start_time'],
            'details' => $details
        ];
    }
    
    /**
     * Buscar credenciais GreePay
     */
    public function getGreePayCredentials()
    {
        $this->addStep('get_greepay_creds', ['action' => 'start']);
        
        $sql = "SELECT * FROM greepay WHERE ativo = 1 LIMIT 1";
        $result = $this->mysqli->query($sql);
        
        if ($result && $result->num_rows > 0) {
            $config = $result->fetch_assoc();
            if (!empty($config['client_id']) && !empty($config['client_secret'])) {
                $this->debug[] = "GreePay: Credenciais encontradas e ativas";
                $this->addStep('get_greepay_creds', ['success' => true, 'config' => $config['client_id']]);
                
                logToDaanrox("GreePay: Credenciais obtidas", [
                    'client_id' => substr($config['client_id'], 0, 10) . '...',
                    'url' => $config['url']
                ], 'CONFIG');
                
                return $config;
            }
        }
        
        $this->debug[] = "GreePay: Nenhuma credencial ativa encontrada";
        $this->addStep('get_greepay_creds', ['success' => false, 'reason' => 'no_credentials']);
        logToDaanrox("GreePay: Sem credenciais ativas", [], 'CONFIG');
        
        return null;
    }
    
    /**
     * Buscar credenciais AurenPay
     */
    public function getAurenPayCredentials()
    {
        $this->addStep('get_aurenpay_creds', ['action' => 'start']);
        
        $sql = "SELECT * FROM aurenpay WHERE ativo = 1 LIMIT 1";
        $result = $this->mysqli->query($sql);
        
        if ($result && $result->num_rows > 0) {
            $config = $result->fetch_assoc();
            if (!empty($config['client_id']) && !empty($config['client_secret'])) {
                $this->debug[] = "AurenPay: Credenciais encontradas e ativas";
                $this->addStep('get_aurenpay_creds', ['success' => true, 'config' => $config['client_id']]);
                
                logToDaanrox("AurenPay: Credenciais obtidas", [
                    'client_id' => substr($config['client_id'], 0, 10) . '...',
                    'url' => $config['url']
                ], 'CONFIG');
                
                return $config;
            }
        }
        
        $this->debug[] = "AurenPay: Nenhuma credencial ativa encontrada";
        $this->addStep('get_aurenpay_creds', ['success' => false, 'reason' => 'no_credentials']);
        logToDaanrox("AurenPay: Sem credenciais ativas", [], 'CONFIG');
        
        return null;
    }
    
    /**
     * Buscar credenciais BSPay com hierarquia de afiliados
     */
    public function getBSPayCredentials($usuario_param = null)
    {
        $this->addStep('get_bspay_creds', ['action' => 'start', 'usuario_param' => $usuario_param]);
        
        if (!$usuario_param) {
            // Sem usuário, pega primeira credencial ativa
            $sql = "SELECT * FROM bspay WHERE ativo = 1 LIMIT 1";
            $result = $this->mysqli->query($sql);
            if ($result && $result->num_rows > 0) {
                $config = $result->fetch_assoc();
                $this->debug[] = "BSPay: Usando primeira credencial ativa (sem usuário)";
                $this->addStep('get_bspay_creds', ['success' => true, 'method' => 'first_active']);
                
                logToDaanrox("BSPay: Credenciais obtidas (primeira ativa)", [
                    'client_id' => substr($config['client_id'], 0, 10) . '...',
                    'invite_code' => $config['invite_code']
                ], 'CONFIG');
                
                return $config;
            }
            return null;
        }
        
        // Buscar ID do usuário
        $usuario_id = null;
        $qry_userid = "SELECT id FROM usuarios WHERE mobile = ? OR id = ?";
        if ($stmt = $this->mysqli->prepare($qry_userid)) {
            $stmt->bind_param("si", $usuario_param, $usuario_param);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $usuario_id = $row['id'];
                $this->debug[] = "BSPay: ID do usuário encontrado: $usuario_id";
            }
            $stmt->close();
        }
        
        if (!$usuario_id) {
            $this->debug[] = "BSPay: Usuário não encontrado";
            $this->addStep('get_bspay_creds', ['success' => false, 'reason' => 'usuario_not_found']);
            return null;
        }
        
        // Carregar todas credenciais BSPay
        $sql = "SELECT * FROM bspay";
        $result = $this->mysqli->query($sql);
        $bspay_creds = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                if (!empty($row['invite_code'])) {
                    $bspay_creds[$row['invite_code']] = $row;
                }
            }
            $this->debug[] = "BSPay: Credenciais carregadas: " . count($bspay_creds);
        }
        
        // Subir hierarquia de afiliados
        $current_user = $usuario_id;
        $checked_codes = [];
        $max_depth = 10;
        $hierarchy_chain = [];
        
        for ($i = 0; $i < $max_depth && $current_user; $i++) {
            $qry = "SELECT invitation_code, invite_code FROM usuarios WHERE id = ?";
            if ($stmt = $this->mysqli->prepare($qry)) {
                $stmt->bind_param("i", $current_user);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($row = $result->fetch_assoc()) {
                    $invitation_code = $row['invitation_code'];
                    $hierarchy_chain[] = [
                        'user_id' => $current_user,
                        'invitation_code' => $invitation_code,
                        'invite_code' => $row['invite_code']
                    ];
                    
                    $this->debug[] = "BSPay: Nível $i - Usuário $current_user, invitation_code: $invitation_code";
                    
                    if (!empty($invitation_code) && isset($bspay_creds[$invitation_code]) && $bspay_creds[$invitation_code]['ativo'] == 1) {
                        $this->debug[] = "BSPay: Credencial encontrada na hierarquia";
                        $this->addStep('get_bspay_creds', [
                            'success' => true,
                            'method' => 'hierarchy',
                            'depth' => $i,
                            'hierarchy_chain' => $hierarchy_chain,
                            'found_code' => $invitation_code
                        ]);
                        
                        logToDaanrox("BSPay: Credencial encontrada por hierarquia", [
                            'depth' => $i,
                            'user_id' => $usuario_id,
                            'found_code' => $invitation_code,
                            'client_id' => substr($bspay_creds[$invitation_code]['client_id'], 0, 10) . '...'
                        ], 'CONFIG');
                        
                        $stmt->close();
                        return $bspay_creds[$invitation_code];
                    }
                    
                    if (in_array($invitation_code, $checked_codes)) {
                        $this->debug[] = "BSPay: Loop detectado na hierarquia";
                        $this->addStep('get_bspay_creds', ['warning' => 'loop_detected', 'hierarchy_chain' => $hierarchy_chain]);
                        break;
                    }
                    
                    $checked_codes[] = $invitation_code;
                    
                    // Subir para o pai
                    $current_user = null;
                    if (!empty($row['invite_code'])) {
                        $qry_parent = "SELECT id FROM usuarios WHERE invite_code = ? LIMIT 1";
                        if ($stmt_parent = $this->mysqli->prepare($qry_parent)) {
                            $stmt_parent->bind_param("s", $row['invitation_code']);
                            $stmt_parent->execute();
                            $result_parent = $stmt_parent->get_result();
                            if ($row_parent = $result_parent->fetch_assoc()) {
                                $current_user = $row_parent['id'];
                            }
                            $stmt_parent->close();
                        }
                    }
                }
                $stmt->close();
            }
        }
        
        // Fallback: primeira credencial ativa
        $sql = "SELECT * FROM bspay WHERE ativo = 1 LIMIT 1";
        $result = $this->mysqli->query($sql);
        if ($result && $result->num_rows > 0) {
            $config = $result->fetch_assoc();
            $this->debug[] = "BSPay: Usando primeira credencial ativa (fallback)";
            $this->addStep('get_bspay_creds', ['success' => true, 'method' => 'fallback']);
            
            logToDaanrox("BSPay: Credenciais obtidas (fallback)", [
                'client_id' => substr($config['client_id'], 0, 10) . '...',
                'reason' => 'hierarchy_not_found'
            ], 'CONFIG');
            
            return $config;
        }
        
        $this->debug[] = "BSPay: Nenhuma credencial disponível";
        $this->addStep('get_bspay_creds', ['success' => false, 'reason' => 'no_credentials']);
        
        return null;
    }
    
    /**
     * Buscar credenciais ExpfyPay
     */
    public function getExpfyPayCredentials()
    {
        $this->addStep('get_expfypay_creds', ['action' => 'start']);
        
        $sql = "SELECT * FROM expfypay WHERE ativo = 1 LIMIT 1";
        $result = $this->mysqli->query($sql);
        
        if ($result && $result->num_rows > 0) {
            $config = $result->fetch_assoc();
            if (!empty($config['client_id']) && !empty($config['client_secret'])) {
                $this->debug[] = "ExpfyPay: Credenciais encontradas e ativas";
                $this->addStep('get_expfypay_creds', ['success' => true, 'config' => $config['client_id']]);
                
                logToDaanrox("ExpfyPay: Credenciais obtidas", [
                    'client_id' => substr($config['client_id'], 0, 10) . '...',
                    'url' => $config['url']
                ], 'CONFIG');
                
                return $config;
            }
        }
        
        $this->debug[] = "ExpfyPay: Nenhuma credencial ativa encontrada";
        $this->addStep('get_expfypay_creds', ['success' => false, 'reason' => 'no_credentials']);
        logToDaanrox("ExpfyPay: Sem credenciais ativas", [], 'CONFIG');
        
        return null;
    }
    
    /**
     * Buscar credenciais PoseidonPay
     */
    public function getPoseidonPayCredentials()
    {
        $this->addStep('get_poseidonpay_creds', ['action' => 'start']);
        
        $sql = "SELECT * FROM poseidonpay WHERE ativo = 1 LIMIT 1";
        try {
            $result = $this->mysqli->query($sql);
        } catch (Throwable $e) {
            $this->debug[] = "PoseidonPay: Tabela não disponível";
            $this->addStep('get_poseidonpay_creds', ['success' => false, 'reason' => 'table_missing']);
            logToDaanrox("PoseidonPay: Tabela não disponível", [], 'CONFIG');
            return null;
        }
        
        if ($result && $result->num_rows > 0) {
            $config = $result->fetch_assoc();
            if (!empty($config['client_id']) && !empty($config['client_secret'])) {
                $this->debug[] = "PoseidonPay: Credenciais encontradas e ativas";
                $this->addStep('get_poseidonpay_creds', ['success' => true, 'config' => $config['client_id']]);
                
                logToDaanrox("PoseidonPay: Credenciais obtidas", [
                    'client_id' => substr($config['client_id'], 0, 10) . '...'
                ], 'CONFIG');
                
                return $config;
            }
        }
        
        $this->debug[] = "PoseidonPay: Nenhuma credencial ativa encontrada";
        $this->addStep('get_poseidonpay_creds', ['success' => false, 'reason' => 'no_credentials']);
        logToDaanrox("PoseidonPay: Sem credenciais ativas", [], 'CONFIG');
        
        return null;
    }
    
    /**
     * Processar saque via GreePay
     */
    public function processGreePay($config, $transacao_id, $valor, $chavepix, $cpf, $nome_real)
    {
        $step_id = 'process_greepay_' . $transacao_id;
        $this->addStep($step_id, ['action' => 'start', 'valor' => $valor]);

        if (empty($config['client_id'])) {
            $error = 'GGPIX: API Key não configurada';
            $this->addStep($step_id, ['success' => false, 'error' => $error]);
            logError('GGPIX', $error, ['config' => array_keys($config)]);
            return ['success' => false, 'message' => $error];
        }

        $api_url = 'https://ggpixapi.com/api/v1';
        $this->debug[] = "GGPIX: Iniciando processamento de saque (transação $transacao_id)";

        // Mapear tipo de chave PIX
        $tipo_chave = identificarTipoChavePix($chavepix);
        $pix_type_map = [
            'document' => 'CPF',
            'phoneNumber' => 'PHONE',
            'email' => 'EMAIL',
            'randomKey' => 'EVP'
        ];
        $pix_type = $pix_type_map[$tipo_chave] ?? 'CPF';

        // Formatar chave PIX conforme o tipo
        $pix_key_formatada = $chavepix;

        if ($tipo_chave === 'document') {
            $apenas_numeros = preg_replace('/[^0-9]/', '', $chavepix);
            $pix_type = (strlen($apenas_numeros) == 11) ? 'CPF' : 'CNPJ';
            $pix_key_formatada = $apenas_numeros;
        } elseif ($tipo_chave === 'phoneNumber') {
            $apenas_numeros = preg_replace('/[^0-9]/', '', $chavepix);
            if (strlen($apenas_numeros) === 11) {
                $pix_key_formatada = '+55' . $apenas_numeros;
            } elseif (strlen($apenas_numeros) === 13 && substr($apenas_numeros, 0, 2) === '55') {
                $pix_key_formatada = '+' . $apenas_numeros;
            } else {
                $pix_key_formatada = (strpos($chavepix, '+') === 0) ? $chavepix : '+55' . $apenas_numeros;
            }
        }

        $this->debug[] = "GGPIX: Tipo de chave: $pix_type, Chave formatada: $pix_key_formatada";

        $documento_limpo = preg_replace('/[^0-9]/', '', $cpf);

        $payload = [
            'amountCents'       => intval(round((float) $valor * 100)),
            'pixKey'            => $pix_key_formatada,
            'pixKeyType'        => $pix_type,
            'externalId'        => (string) $transacao_id,
            'description'       => "Saque PIX #" . $transacao_id,
            'webhookUrl'        => $GLOBALS['url_base'] . 'callbackpayment/greepay.php',
            'recipientDocument' => $documento_limpo
        ];

        $headers = [
            'Content-Type: application/json',
            'X-API-Key: ' . $config['client_id']
        ];

        $url = $api_url . '/pix/out';

        logToDaanrox("GGPIX: Enviando requisição", [
            'transaction_id' => $transacao_id,
            'url' => $url,
            'payload' => $payload
        ], 'REQUEST');

        // Enviar requisição
        $response = $this->makeCurlRequest(
            $url,
            $payload,
            $headers,
            'POST',
            30,
            true
        );

        // Log da resposta HTTP
        logHttpTransaction(
            'GGPIX',
            $url,
            $payload,
            $response,
            $response['http_code'] ?? 0,
            $response['success'] ?? false
        );

        if (!$response['success']) {
            $error_msg = 'GGPIX: Falha de comunicação - ' . ($response['message'] ?? 'sem mensagem');
            $this->addStep($step_id, ['success' => false, 'error' => $error_msg]);
            logError('GGPIX', $error_msg, $response);

            return [
                'success' => false,
                'message' => $error_msg,
                'response' => $response
            ];
        }

        $data = $response['data'];
        $http_code = $response['http_code'];
        $raw_response = $response['raw_response'] ?? json_encode($data);

        $success_http = ($http_code >= 200 && $http_code < 300);

        $eh_sucesso = false;
        $e2e = null;
        $transaction_id = null;

        if ($success_http && !empty($data['id'])) {
            $eh_sucesso = true;
            $e2e = $data['id'];
            $transaction_id = $data['id'];

            $this->debug[] = "GGPIX: Saque criado com sucesso - ID: $e2e";
        } elseif ($http_code === 409) {
            // externalId duplicado — saque já existe na GGPIX. Tratar como sucesso (idempotente).
            $eh_sucesso = true;
            $e2e = $data['id'] ?? $transacao_id;
            $transaction_id = $data['id'] ?? $transacao_id;
            $this->debug[] = "GGPIX: Saque já existia (409 conflict) - tratando como sucesso";
        }

        logToDaanrox("GGPIX: Resposta recebida", [
            'http_code' => $http_code,
            'success' => $eh_sucesso,
            'e2e' => $e2e,
            'data' => $data
        ], 'RESPONSE');

        if ($eh_sucesso) {
            $this->addStep($step_id, [
                'success' => true,
                'e2e' => $e2e,
                'transaction_id' => $transaction_id
            ]);

            return [
                'success' => true,
                'gateway' => 'greepay',
                'gateway_transaction_id' => $transaction_id,
                'e2e' => $e2e,
                'message' => 'Saque processado via GGPIX com sucesso',
                'response_data' => $data,
                'full_response' => $raw_response
            ];
        }

        $error_msg = $data['message'] ?? $data['error'] ?? 'Erro desconhecido (HTTP ' . $http_code . ')';
        $this->addStep($step_id, ['success' => false, 'error' => $error_msg]);

        return [
            'success' => false,
            'message' => 'GGPIX: ' . $error_msg,
            'response_data' => $data,
            'raw_response' => $raw_response
        ];
    }
    
    /**
     * Processar saque via AurenPay
     */
    public function processAurenPay($config, $transacao_id, $valor, $chavepix, $cpf, $nome_real, $tipo_chave_db = null)
    {
        $step_id = 'process_aurenpay_' . $transacao_id;
        $this->addStep($step_id, ['action' => 'start', 'valor' => $valor]);
        
        if (empty($config['client_id']) || empty($config['client_secret']) || empty($config['url'])) {
            $error = 'AurenPay: Credenciais não configuradas';
            $this->addStep($step_id, ['success' => false, 'error' => $error]);
            logError('AurenPay', $error, ['config' => array_keys($config)]);
            return ['success' => false, 'message' => $error];
        }
    
        $api_url = rtrim($config['url'], '/');
        $pix_key_type = 'evp';
        $this->debug[] = "AurenPay: Iniciando processamento de saque (transação $transacao_id)";
    
        // Normalização do tipo de chave
        if ($tipo_chave_db) {
            $tipo_upper = strtoupper(trim($tipo_chave_db));
            if (in_array($tipo_upper, ['CPF', 'DOCUMENT'])) $pix_key_type = 'cpf';
            elseif (in_array($tipo_upper, ['CNPJ'])) $pix_key_type = 'cnpj';
            elseif (in_array($tipo_upper, ['EMAIL', 'E-MAIL'])) $pix_key_type = 'email';
            elseif (in_array($tipo_upper, ['TELEFONE', 'PHONE', 'PHONENUMBER', 'CELULAR'])) $pix_key_type = 'phone';
            elseif (in_array($tipo_upper, ['EVP', 'ALEATORIA', 'RANDOMKEY', 'RANDOM'])) $pix_key_type = 'evp';
            else {
                $tipo_detectado = identificarTipoChavePix($chavepix);
                if ($tipo_detectado === 'document') {
                    $apenas_numeros = preg_replace('/[^0-9]/', '', $chavepix);
                    $pix_key_type = (strlen($apenas_numeros) == 11) ? 'cpf' : 'cnpj';
                } elseif ($tipo_detectado === 'phoneNumber') $pix_key_type = 'phone';
                elseif ($tipo_detectado === 'email') $pix_key_type = 'email';
                else $pix_key_type = 'evp';
                $this->debug[] = "AurenPay: Tipo '$tipo_chave_db' não reconhecido, detectado como '$pix_key_type'";
            }
            $this->debug[] = "AurenPay: Tipo do banco '$tipo_chave_db' convertido para '$pix_key_type'";
        } else {
            $tipo_detectado = identificarTipoChavePix($chavepix);
            if ($tipo_detectado === 'document') {
                $apenas_numeros = preg_replace('/[^0-9]/', '', $chavepix);
                $pix_key_type = (strlen($apenas_numeros) == 11) ? 'cpf' : 'cnpj';
            } elseif ($tipo_detectado === 'phoneNumber') $pix_key_type = 'phone';
            elseif ($tipo_detectado === 'email') $pix_key_type = 'email';
            else $pix_key_type = 'evp';
            $this->debug[] = "AurenPay: Tipo detectado automaticamente como '$pix_key_type'";
        }
    
        $documento_limpo = preg_replace('/[^0-9]/', '', $cpf);
        $value_cents = (int)($valor * 100);
        $external_id = (string)$transacao_id;
    
        $payload = [
            'external_id' => $external_id,
            'value_cents' => $value_cents,
            'receiver_name' => $nome_real ?: 'Cliente',
            'receiver_document' => $documento_limpo,
            'pix_key' => $chavepix,
            'pix_key_type' => $pix_key_type,
            'description' => 'Saque processado - ID: ' . $transacao_id,
            'postbackUrl' => $GLOBALS['url_base'] . 'callbackpayment/aurenpay'
        ];
    
        $headers = [
            'ci: ' . $config['client_id'],
            'cs: ' . $config['client_secret'],
            'Content-Type: application/json'
        ];
    
        $url = $api_url . '/v1/pix/payment';
        
        logToDaanrox("AurenPay: Enviando requisição", [
            'transaction_id' => $transacao_id,
            'url' => $url,
            'payload' => $payload
        ], 'REQUEST');
    
        $response = $this->makeCurlRequest(
            $url,
            $payload,
            $headers,
            'POST',
            30,
            true
        );
    
        logHttpTransaction('AurenPay', $url, $payload, $response, $response['http_code'] ?? 0, $response['success'] ?? false);
    
        if (!$response['success']) {
            $error_msg = 'AurenPay: Falha de comunicação - ' . ($response['message'] ?? 'sem mensagem');
            $this->addStep($step_id, ['success' => false, 'error' => $error_msg]);
            logError('AurenPay', $error_msg, $response);
            
            return [
                'success' => false,
                'message' => $error_msg,
                'response' => $response
            ];
        }
    
        $data = $response['data'];
        $http_code = $response['http_code'];
        $raw_response = $response['raw_response'] ?? json_encode($data);
    
        $success_http = ($http_code >= 200 && $http_code < 300);
        
        $payment_data = $data['cashout'] ?? $data['data'] ?? $data ?? [];
        $reference_code = $payment_data['reference_code'] ?? $payment_data['id'] ?? $external_id;
    
        $eh_sucesso = false;
        if ($success_http) {
            if (
                (isset($payment_data['status']) && in_array(strtolower($payment_data['status']), ['success', 'approved', 'completed', 'processing'])) ||
                (isset($payment_data['message']) && stripos($payment_data['message'], 'success') !== false) ||
                isset($payment_data['reference_code'])
            ) {
                $eh_sucesso = true;
            }
        }
    
        logToDaanrox("AurenPay: Resposta recebida", [
            'http_code' => $http_code,
            'success' => $eh_sucesso,
            'reference_code' => $reference_code,
            'data' => $payment_data
        ], 'RESPONSE');
    
        if ($eh_sucesso) {
            $this->addStep($step_id, [
                'success' => true,
                'reference_code' => $reference_code
            ]);
            
            return [
                'success' => true,
                'gateway' => 'aurenpay',
                'gateway_transaction_id' => $reference_code,
                'message' => 'Saque processado via AurenPay com sucesso',
                'response_data' => $payment_data,
                'full_response' => $raw_response
            ];
        }
    
        $error_msg = $data['message'] ?? 'Erro desconhecido (HTTP ' . $http_code . ')';
        if (isset($data['code'])) $error_msg .= ' #' . $data['code'];
        
        $this->addStep($step_id, ['success' => false, 'error' => $error_msg]);
    
        return [
            'success' => false,
            'message' => 'AurenPay: ' . $error_msg,
            'response_data' => $data,
            'raw_response' => $raw_response
        ];
    }
    
    /**
     * Processar via BSPay (pixupbr.com)
     */
    /**
 * Processar via BSPay (pixupbr.com)
 * CORREÇÃO: Identificar e enviar keyType corretamente
 */
public function processBSPay($config, $transacao_id, $valor, $chavepix, $cpf, $nome_real)
{
    $step_id = 'process_bspay_' . $transacao_id;
    $this->addStep($step_id, ['action' => 'start', 'valor' => $valor]);
    
    if (empty($config['client_id']) || empty($config['client_secret'])) {
        $error = 'BSPay: Credenciais não configuradas';
        $this->addStep($step_id, ['success' => false, 'error' => $error]);
        logError('BSPay', $error, ['config' => array_keys($config)]);
        return ['success' => false, 'message' => $error];
    }
    
    $api_url = $config['url'] ?: 'https://api.pixupbr.com';
    $bearer = base64_encode($config['client_id'] . ':' . $config['client_secret']);
    
    logToDaanrox("BSPay: Iniciando autenticação", [
        'transaction_id' => $transacao_id,
        'api_url' => $api_url
    ], 'AUTH');
    
    // 1. Obter token
    $token_response = $this->makeCurlRequest(
        $api_url . '/v2/oauth/token',
        null,
        [
            'accept: application/json',
            'Authorization: Basic ' . $bearer
        ],
        'POST',
        45,
        true
    );
    
    logHttpTransaction('BSPay-Token', $api_url . '/v2/oauth/token', ['auth' => 'basic'], $token_response, $token_response['http_code'] ?? 0, $token_response['success'] ?? false);
    
    if (!$token_response['success']) {
        $error_msg = 'BSPay: Erro na autenticação - ' . $token_response['message'];
        $this->addStep($step_id, ['success' => false, 'error' => $error_msg]);
        logError('BSPay', $error_msg, $token_response);
        
        return [
            'success' => false,
            'message' => $error_msg
        ];
    }
    
    $token_data = $token_response['data'];
    if (!isset($token_data['access_token'])) {
        $error_msg = 'BSPay: Token não retornado';
        $this->addStep($step_id, ['success' => false, 'error' => $error_msg]);
        logError('BSPay', $error_msg, $token_data);
        
        return [
            'success' => false,
            'message' => $error_msg
        ];
    }
    
    $token = $token_data['access_token'];
    logToDaanrox("BSPay: Token obtido com sucesso", [
        'token_type' => $token_data['token_type'] ?? 'Bearer',
        'expires_in' => $token_data['expires_in'] ?? null
    ], 'AUTH');
    
    // 2. IDENTIFICAR O TIPO DE CHAVE PIX CORRETAMENTE
    $tipo_chave = identificarTipoChavePix($chavepix);
    
    // Mapeamento para os tipos aceitos pela BSPay/pixupbr.com
    $key_type_map = [
        'document' => 'CPF',      // Para CPF (11 dígitos)
        'phoneNumber' => 'PHONE',  // Para telefone
        'email' => 'EMAIL',        // Para email
        'randomKey' => 'EVP'       // Para chave aleatória
    ];
    
    // Definir keyType baseado no tipo identificado
    $keyType = $key_type_map[$tipo_chave] ?? 'CPF';
    
    // Ajustes específicos para document (CPF/CNPJ)
    if ($tipo_chave === 'document') {
        $apenas_numeros = preg_replace('/[^0-9]/', '', $chavepix);
        if (strlen($apenas_numeros) == 14) {
            $keyType = 'CNPJ';
        } else {
            $keyType = 'CPF';
        }
        // Para document, a chave deve conter apenas números
        $chavepix = $apenas_numeros;
    }
    
    // Ajuste para telefone - remover caracteres não numéricos mas manter DDI
    if ($tipo_chave === 'phoneNumber') {
        $apenas_numeros = preg_replace('/[^0-9]/', '', $chavepix);
        // Se não tiver DDI (55), adicionar
        if (strlen($apenas_numeros) == 11) {
            $chavepix = '55' . $apenas_numeros;
        } elseif (strlen($apenas_numeros) == 13 && substr($apenas_numeros, 0, 2) == '55') {
            $chavepix = $apenas_numeros;
        } else {
            $chavepix = $apenas_numeros;
        }
    }
    
    // Validação do CPF para o campo taxId
    $taxId = preg_replace('/[^0-9]/', '', $cpf);
    if (strlen($taxId) != 11) {
        logToDaanrox("BSPay: CPF inválido", ['taxId' => $taxId, 'original' => $cpf], 'WARNING');
        // Se não for CPF válido, usar um fallback ou o documento do usuário
        $taxId = $taxId ?: '00000000191'; // CPF genérico como fallback (nunca usar em produção!)
    }
    
    // Log do mapeamento para debug
    logToDaanrox("BSPay: Mapeamento de tipos", [
        'tipo_identificado' => $tipo_chave,
        'keyType_selecionado' => $keyType,
        'chave_formatada' => substr($chavepix, 0, 4) . '...' . substr($chavepix, -4)
    ], 'PIX_TYPE');
    
    $external_id = $transacao_id . '_bspay_' . time();
    
    $payment_data = [
        'creditParty' => [
            'name' => $nome_real ?: 'Cliente',
            'keyType' => $keyType,  
            'key' => $chavepix,
            'taxId' => ($keyType == 'CPF' || $keyType == 'CNPJ') ? $chavepix : '10900159685'
        ],
        'amount' => number_format((float) $valor, 2, '.', ''),
        'external_id' => $external_id,
        'description' => 'Saque processado - ID: ' . $transacao_id
    ];
    
    logToDaanrox("BSPay: Enviando pagamento", [
        'transaction_id' => $transacao_id,
        'external_id' => $external_id,
        'amount' => $payment_data['amount'],
        'creditParty' => [
            'name' => $payment_data['creditParty']['name'],
            'keyType' => $payment_data['creditParty']['keyType'],
            'key_masked' => substr($payment_data['creditParty']['key'], 0, 4) . '...' . substr($payment_data['creditParty']['key'], -4),
            'taxId_masked' => substr($payment_data['creditParty']['taxId'], 0, 3) . '...' . substr($payment_data['creditParty']['taxId'], -2)
        ]
    ], 'REQUEST');
    
    $payment_response = $this->makeCurlRequest(
        $api_url . '/v2/pix/payment',
        $payment_data,
        [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
            'Accept: application/json'
        ],
        'POST',
        60,
        true
    );
    
    logHttpTransaction('BSPay-Payment', $api_url . '/v2/pix/payment', $payment_data, $payment_response, $payment_response['http_code'] ?? 0, $payment_response['success'] ?? false);
    
    if (!$payment_response['success']) {
        $error_msg = 'BSPay: Erro no pagamento - ' . $payment_response['message'];
        $this->addStep($step_id, ['success' => false, 'error' => $error_msg]);
        logError('BSPay', $error_msg, $payment_response);
        
        return [
            'success' => false,
            'message' => $error_msg
        ];
    }
    
    $data = $payment_response['data'];
    $http_code = $payment_response['http_code'];
    $raw_response = $payment_response['raw_response'] ?? json_encode($data);
    
    $response_gateway = $data['response'] ?? $data['status'] ?? '';
    $message = $data['message'] ?? $data['msg'] ?? '';
    $transaction_id_gateway = $data['transaction_id'] ?? $data['id'] ?? $external_id;
    
    $sucesso = false;
    if ($http_code >= 200 && $http_code < 300) {
        if (
            $response_gateway === 'OK' ||
            $response_gateway === 'SUCCESS' ||
            stripos($message, 'sucesso') !== false ||
            stripos($response_gateway, 'success') !== false ||
            isset($data['transaction_id'])
        ) {
            $sucesso = true;
        }
    }
    
    logToDaanrox("BSPay: Resposta do pagamento", [
        'http_code' => $http_code,
        'success' => $sucesso,
        'transaction_id' => $transaction_id_gateway,
        'response_gateway' => $response_gateway,
        'message' => $message,
        'data' => $data
    ], 'RESPONSE');
    
    if ($sucesso) {
        $this->addStep($step_id, [
            'success' => true,
            'transaction_id' => $transaction_id_gateway
        ]);
        
        return [
            'success' => true,
            'gateway' => 'bspay',
            'gateway_transaction_id' => $transaction_id_gateway,
            'message' => 'Saque processado via BSPay: ' . ($message ?: 'Processado com sucesso'),
            'response_data' => $data,
            'full_response' => $raw_response
        ];
    }
    
    $error_msg = 'BSPay: ' . ($message ?: 'Resposta inesperada');
    $this->addStep($step_id, ['success' => false, 'error' => $error_msg]);
    
    return [
        'success' => false,
        'message' => $error_msg,
        'response_data' => $data,
        'full_response' => $raw_response
    ];
}
    
    /**
     * Processar via ExpfyPay
     */
    public function processExpfyPay($config, $transacao_id, $valor, $chavepix)
    {
        $step_id = 'process_expfypay_' . $transacao_id;
        $this->addStep($step_id, ['action' => 'start', 'valor' => $valor]);
        
        if (empty($config['client_id']) || empty($config['client_secret']) || empty($config['url'])) {
            $error = 'ExpfyPay: Não configurado corretamente';
            $this->addStep($step_id, ['success' => false, 'error' => $error]);
            logError('ExpfyPay', $error, ['config' => array_keys($config)]);
            return ['success' => false, 'message' => $error];
        }
        
        $tipo_chave = identificarTipoChavePix($chavepix);
        $pix_key_type_map = [
            'document' => 'CPF',
            'phoneNumber' => 'PHONE',
            'email' => 'EMAIL',
            'randomKey' => 'EVP'
        ];
        
        $external_id = $transacao_id . '_expfy_' . time();
        
        $payload = [
            "amount" => (float) $valor,
            "pix_key" => $chavepix,
            "pix_key_type" => $pix_key_type_map[$tipo_chave] ?? 'CPF',
            "description" => "Saque processado - ID: " . $transacao_id,
            "external_id" => $external_id
        ];
        
        $url = rtrim($config['url'], '/') . '/api/v1/withdrawls';
        $headers = [
            'X-Public-Key: ' . $config['client_id'],
            'X-Secret-Key: ' . $config['client_secret'],
            'Content-Type: application/json'
        ];
        
        logToDaanrox("ExpfyPay: Enviando requisição", [
            'transaction_id' => $transacao_id,
            'external_id' => $external_id,
            'url' => $url,
            'payload' => $payload
        ], 'REQUEST');
        
        $response = $this->makeCurlRequest($url, $payload, $headers, 'POST', 30, true);
        
        logHttpTransaction('ExpfyPay', $url, $payload, $response, $response['http_code'] ?? 0, $response['success'] ?? false);
        
        if (!$response['success']) {
            $this->addStep($step_id, ['success' => false, 'error' => $response['message']]);
            logError('ExpfyPay', $response['message'], $response);
            return $response;
        }
        
        $data = $response['data'];
        $http_code = $response['http_code'];
        $raw_response = $response['raw_response'] ?? json_encode($data);
        
        if ($http_code >= 200 && $http_code < 300) {
            $withdrawal_id = $data['withdrawal_id'] ?? $data['data']['withdrawal_id'] ?? $data['id'] ?? $external_id;
            
            $this->addStep($step_id, [
                'success' => true,
                'withdrawal_id' => $withdrawal_id
            ]);
            
            logToDaanrox("ExpfyPay: Saque aprovado", [
                'withdrawal_id' => $withdrawal_id,
                'data' => $data
            ], 'RESPONSE');
            
            return [
                'success' => true,
                'gateway' => 'expfypay',
                'gateway_transaction_id' => $withdrawal_id,
                'message' => 'Saque processado via ExpfyPay com sucesso',
                'response_data' => $data,
                'full_response' => $raw_response
            ];
        }
        
        $error_msg = $data['message'] ?? $data['error'] ?? 'Erro desconhecido';
        $this->addStep($step_id, ['success' => false, 'error' => $error_msg]);
        
        return [
            'success' => false,
            'message' => 'ExpfyPay: ' . $error_msg,
            'response_data' => $data,
            'full_response' => $raw_response
        ];
    }
    
    /**
     * Processar saque via PoseidonPay
     * Auth: x-public-key + x-secret-key
     */
    public function processPoseidonPay($config, $transacao_id, $valor, $chavepix, $cpf, $nome_real)
    {
        $step_id = 'process_poseidonpay_' . $transacao_id;
        $this->addStep($step_id, ['action' => 'start', 'valor' => $valor]);
        
        if (empty($config['client_id']) || empty($config['client_secret'])) {
            $error = 'PoseidonPay: Credenciais não configuradas';
            $this->addStep($step_id, ['success' => false, 'error' => $error]);
            logError('PoseidonPay', $error, ['config' => array_keys($config)]);
            return ['success' => false, 'message' => $error];
        }
        
        $tipo_chave = identificarTipoChavePix($chavepix);
        $pix_key_type_map = [
            'document' => 'CPF',
            'phoneNumber' => 'PHONE',
            'email' => 'EMAIL',
            'randomKey' => 'EVP'
        ];
        
        $external_id = 'SAQ-' . $transacao_id . '-' . time();
        
        $payload = [
            "identifier" => $external_id,
            "amount" => (float)$valor,
            "client" => [
                "name" => $nome_real ?: 'Cliente',
                "document" => preg_replace('/[^0-9]/', '', $cpf),
                "keypix" => $chavepix,
                "keypix_type" => $pix_key_type_map[$tipo_chave] ?? 'CPF'
            ],
            "description" => "Saque processado - ID: " . $transacao_id
        ];
        
        $url = 'https://app.poseidonpay.site/api/v1/gateway/pix/send';
        $headers = [
            'x-public-key: ' . $config['client_id'],
            'x-secret-key: ' . $config['client_secret'],
            'Content-Type: application/json'
        ];
        
        logToDaanrox("PoseidonPay: Enviando requisição", [
            'transaction_id' => $transacao_id,
            'external_id' => $external_id,
            'url' => $url,
            'payload' => $payload
        ], 'REQUEST');
        
        $response = $this->makeCurlRequest($url, $payload, $headers, 'POST', 30, true);
        
        logHttpTransaction('PoseidonPay', $url, $payload, $response, $response['http_code'] ?? 0, $response['success'] ?? false);
        
        if (!$response['success']) {
            $error_msg = 'PoseidonPay: Falha de comunicação - ' . ($response['message'] ?? 'sem mensagem');
            $this->addStep($step_id, ['success' => false, 'error' => $error_msg]);
            logError('PoseidonPay', $error_msg, $response);
            
            return [
                'success' => false,
                'message' => $error_msg,
                'response' => $response
            ];
        }
        
        $data = $response['data'];
        $http_code = $response['http_code'];
        $raw_response = $response['raw_response'] ?? json_encode($data);
        
        if ($http_code >= 200 && $http_code < 300) {
            $withdrawal_id = $data['withdrawal_id'] ?? $data['transactionId'] ?? $data['id'] ?? $external_id;
            
            $this->addStep($step_id, [
                'success' => true,
                'withdrawal_id' => $withdrawal_id
            ]);
            
            logToDaanrox("PoseidonPay: Saque aprovado", [
                'withdrawal_id' => $withdrawal_id,
                'data' => $data
            ], 'RESPONSE');
            
            return [
                'success' => true,
                'gateway' => 'poseidonpay',
                'gateway_transaction_id' => $withdrawal_id,
                'message' => 'Saque processado via PoseidonPay com sucesso',
                'response_data' => $data,
                'full_response' => $raw_response
            ];
        }
        
        $error_msg = $data['message'] ?? $data['error'] ?? 'Erro desconhecido';
        $this->addStep($step_id, ['success' => false, 'error' => $error_msg]);
        logToDaanrox("PoseidonPay: Erro na resposta", [
            'http_code' => $http_code,
            'data' => $data
        ], 'RESPONSE');
        
        return [
            'success' => false,
            'message' => 'PoseidonPay: ' . $error_msg,
            'response_data' => $data,
            'full_response' => $raw_response
        ];
    }
    
    /**
     * Fazer requisição cURL com retry e IPv4 forçado
     */
    private function makeCurlRequest($url, $data = null, $headers = [], $method = 'POST', $timeout = 30, $force_ipv4 = true, $max_retries = 2)
    {
        $last_error = null;
        $request_id = uniqid('curl_', true);
        
        logToDaanrox("cURL Request iniciado", [
            'request_id' => $request_id,
            'url' => $url,
            'method' => $method,
            'timeout' => $timeout,
            'force_ipv4' => $force_ipv4,
            'max_retries' => $max_retries
        ], 'CURL');
        
        for ($attempt = 1; $attempt <= $max_retries; $attempt++) {
            $curl = curl_init();
            
            $curl_options = [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => $timeout,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_VERBOSE => true,
                CURLOPT_STDERR => fopen('php://temp', 'w+')
            ];
            
            if ($force_ipv4) {
                $curl_options[CURLOPT_IPRESOLVE] = CURL_IPRESOLVE_V4;
            }
            
            if ($method === 'POST') {
                $curl_options[CURLOPT_POST] = true;
                if ($data) {
                    $json_data = json_encode($data);
                    $curl_options[CURLOPT_POSTFIELDS] = $json_data;
                    
                    // Log do payload (com dados sensíveis mascarados)
                    $masked_data = $this->maskSensitiveData($data);
                    logToDaanrox("cURL Attempt $attempt - Payload", [
                        'request_id' => $request_id,
                        'payload' => $masked_data
                    ], 'CURL');
                }
            }
            
            curl_setopt_array($curl, $curl_options);
            
            $start_time = microtime(true);
            $response = curl_exec($curl);
            $exec_time = microtime(true) - $start_time;
            
            $curl_error = curl_error($curl);
            $curl_errno = curl_errno($curl);
            $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $primary_ip = curl_getinfo($curl, CURLINFO_PRIMARY_IP);
            $total_time = curl_getinfo($curl, CURLINFO_TOTAL_TIME);
            $namelookup_time = curl_getinfo($curl, CURLINFO_NAMELOOKUP_TIME);
            $connect_time = curl_getinfo($curl, CURLINFO_CONNECT_TIME);
            $pretransfer_time = curl_getinfo($curl, CURLINFO_PRETRANSFER_TIME);
            $starttransfer_time = curl_getinfo($curl, CURLINFO_STARTTRANSFER_TIME);
            $size_download = curl_getinfo($curl, CURLINFO_SIZE_DOWNLOAD);
            $speed_download = curl_getinfo($curl, CURLINFO_SPEED_DOWNLOAD);
            
            // Obter verbose output
            $verbose_output = '';
            if (isset($curl_options[CURLOPT_STDERR])) {
                rewind($curl_options[CURLOPT_STDERR]);
                $verbose_output = stream_get_contents($curl_options[CURLOPT_STDERR]);
                fclose($curl_options[CURLOPT_STDERR]);
            }
            
            curl_close($curl);
            
            // Log detalhado da tentativa
            logToDaanrox("cURL Attempt $attempt - Resultado", [
                'request_id' => $request_id,
                'attempt' => $attempt,
                'success' => empty($curl_error),
                'http_code' => $http_code,
                'ip_used' => $primary_ip,
                'timing' => [
                    'total' => round($total_time, 3),
                    'namelookup' => round($namelookup_time, 3),
                    'connect' => round($connect_time, 3),
                    'pretransfer' => round($pretransfer_time, 3),
                    'starttransfer' => round($starttransfer_time, 3),
                    'execution' => round($exec_time, 3)
                ],
                'transfer' => [
                    'size_download' => $size_download,
                    'speed_download' => round($speed_download, 2)
                ],
                'error' => $curl_error ?: null,
                'errno' => $curl_errno ?: null
            ], 'CURL');
            
            if ($curl_error) {
                $last_error = [
                    'success' => false,
                    'message' => 'Erro de comunicação (tentativa ' . $attempt . '): ' . $curl_error,
                    'http_code' => $http_code,
                    'errno' => $curl_errno,
                    'verbose' => $verbose_output
                ];
                
                logError('cURL', $curl_error, [
                    'request_id' => $request_id,
                    'attempt' => $attempt,
                    'errno' => $curl_errno,
                    'url' => $url
                ]);
                
                if ($attempt < $max_retries) {
                    $sleep_time = 2 * $attempt;
                    logToDaanrox("cURL - Aguardando para retentativa", [
                        'request_id' => $request_id,
                        'next_attempt' => $attempt + 1,
                        'sleep' => $sleep_time
                    ], 'CURL');
                    sleep($sleep_time);
                    continue;
                }
            } else {
                $decoded = json_decode($response, true);
                
                // Log da resposta (com dados sensíveis mascarados)
                $masked_response = $this->maskSensitiveData($decoded ?: ['raw' => substr($response, 0, 1000) . '...']);
                
                logToDaanrox("cURL Success - Resposta", [
                    'request_id' => $request_id,
                    'http_code' => $http_code,
                    'response' => $masked_response,
                    'verbose' => substr($verbose_output, 0, 1000) // Limitar verbose output
                ], 'CURL');
                
                return [
                    'success' => true,
                    'data' => $decoded ?: [],
                    'http_code' => $http_code,
                    'raw_response' => $response,
                    'ip_used' => $primary_ip,
                    'timing' => [
                        'total' => $total_time,
                        'connect' => $connect_time
                    ]
                ];
            }
        }
        
        $final_error = $last_error ?: [
            'success' => false,
            'message' => 'Falha após ' . $max_retries . ' tentativas'
        ];
        
        logToDaanrox("cURL - Falha final", [
            'request_id' => $request_id,
            'error' => $final_error
        ], 'CURL');
        
        return $final_error;
    }
    
    /**
     * Mascarar dados sensíveis para logging
     */
    private function maskSensitiveData($data)
    {
        if (is_array($data)) {
            $masked = [];
            foreach ($data as $key => $value) {
                // Lista de campos sensíveis
                $sensitive_fields = ['cpf', 'document', 'taxId', 'pix_key', 'key', 'token', 'access_token', 'secret', 'password', 'client_secret'];
                
                if (in_array(strtolower($key), $sensitive_fields)) {
                    if (is_string($value) && strlen($value) > 4) {
                        $masked[$key] = substr($value, 0, 4) . '...' . substr($value, -4);
                    } else {
                        $masked[$key] = '***MASKED***';
                    }
                } elseif (is_array($value)) {
                    $masked[$key] = $this->maskSensitiveData($value);
                } else {
                    $masked[$key] = $value;
                }
            }
            return $masked;
        }
        return $data;
    }
}

// ==================== PROCESSAMENTO PRINCIPAL ====================

// Registrar início da requisição
logToDaanrox("=== NOVA REQUISIÇÃO ===", [
    'method' => $_SERVER['REQUEST_METHOD'],
    'uri' => $_SERVER['REQUEST_URI'],
    'query' => $_GET,
    'post' => $_POST
], 'REQUEST_START');

// Verificar 2FA
$codigo2fa = $_GET['codigo_2fa'] ?? $_POST['codigo_2fa'] ?? '';
if($codigo2fa === ''){
    logToDaanrox("2FA obrigatório não fornecido", [], 'SECURITY');
    header('Content-Type: application/json'); http_response_code(403);
    echo json_encode(['success'=>false,'message'=>'Código 2FA obrigatório']); exit;
}
if(!validar_2fa_admin($codigo2fa)){
    logToDaanrox("2FA inválido", ['codigo' => $codigo2fa], 'SECURITY');
    header('Content-Type: application/json'); http_response_code(403);
    echo json_encode(['success'=>false,'message'=>'Código 2FA inválido']); exit;
}

logToDaanrox("2FA validado com sucesso", ['admin' => $_SESSION['data_adm']['id'] ?? 'unknown'], 'SECURITY');

// Verificar se ID foi fornecido
if (!isset($_GET['id'])) {
    logToDaanrox("ID da transação não informado", [], 'ERROR');
    sendResponse(false, "ID da transação não informado", null, 400);
}

$id = PHP_SEGURO($_GET['id']);
$usuario_param = isset($_GET['usuario']) ? PHP_SEGURO($_GET['usuario']) : null;

logToDaanrox("Processando saque", [
    'transaction_id' => $id,
    'usuario_param' => $usuario_param
], 'PROCESS');

// Buscar dados do saque
$sql = "SELECT valor, pix, tipo FROM solicitacao_saques WHERE transacao_id = ?";
if (!$stmt = $mysqli->prepare($sql)) {
    logError('Database', 'Erro ao preparar consulta', ['error' => $mysqli->error]);
    sendResponse(false, "Erro ao preparar consulta", null, 500);
}

$stmt->bind_param("s", $id);
$stmt->execute();
$stmt->bind_result($valor, $chavepix1, $tipo_chave_db);
$stmt->fetch();
$stmt->close();

if (!$valor || !$chavepix1) {
    logToDaanrox("Saque não encontrado", ['transaction_id' => $id], 'ERROR');
    sendResponse(false, "Saque não encontrado", null, 404);
}

logToDaanrox("Dados do saque obtidos", [
    'valor' => $valor,
    'tipo_chave_db' => $tipo_chave_db
], 'DATABASE');

// Obter informações da chave PIX
$chavepix2 = localizarchavepix($chavepix1);
$chavepix = $chavepix2['chave'];
$cpf = $chavepix2['cpf'];
$nome_real = $chavepix2['realname'];

logToDaanrox("Dados do beneficiário", [
    'chave_pix' => substr($chavepix, 0, 4) . '...' . substr($chavepix, -4),
    'cpf' => substr($cpf, 0, 3) . '...' . substr($cpf, -2),
    'nome' => $nome_real
], 'BENEFICIARY');

// Validar chave PIX
$tipoChavePix = identificarTipoChavePix($chavepix);
if ($tipoChavePix === 'invalid') {
    logToDaanrox("Chave PIX inválida", ['chave' => $chavepix], 'ERROR');
    sendResponse(false, "Chave PIX inválida", null, 400);
}

logToDaanrox("Chave PIX validada", ['tipo' => $tipoChavePix], 'VALIDATION');

// Inicializar processador
$processor = new MultiGatewayProcessor($mysqli);

// Buscar credenciais dos gateways
$greepay_cred = $processor->getGreePayCredentials();
$auren_cred = $processor->getAurenPayCredentials();
$bspay_cred = $processor->getBSPayCredentials($usuario_param);
$expfy_cred = $processor->getExpfyPayCredentials();
$poseidon_cred = $processor->getPoseidonPayCredentials();

$debug_info = $processor->getDebug();
$transaction_log = $processor->getTransactionLog();

// Verificar se há pelo menos um gateway configurado
if (!$greepay_cred && !$auren_cred && !$bspay_cred && !$expfy_cred && !$poseidon_cred) {
    logToDaanrox("Nenhum gateway configurado", $debug_info, 'ERROR');
    sendResponse(false, "Nenhum gateway de pagamento configurado", [
        'debug' => $debug_info,
        'transaction_log' => $transaction_log
    ], 500);
}

logToDaanrox('Iniciando processamento', [
    'transaction_id' => $id,
    'valor' => $valor,
    'usuario' => $usuario_param,
    'greepay_available' => !empty($greepay_cred),
    'aurenpay_available' => !empty($auren_cred),
    'bspay_available' => !empty($bspay_cred),
    'expfy_available' => !empty($expfy_cred),
    'poseidon_available' => !empty($poseidon_cred)
], 'GATEWAY_CHECK');

// Tentar processar com os gateways disponíveis
$result = null;
$gateway_usado = null;
$errors = [];
$gateway_responses = [];

// Preferir gateway especificado
$gateway_preferido = $_GET['gateway'] ?? null;

// Array de gateways com suas funções de processamento
$gateway_processors = [
    'greepay' => [
        'cred' => $greepay_cred,
        'func' => function() use ($processor, $greepay_cred, $id, $valor, $chavepix, $cpf, $nome_real) {
            return $processor->processGreePay($greepay_cred, $id, $valor, $chavepix, $cpf, $nome_real);
        }
    ],
    'aurenpay' => [
        'cred' => $auren_cred,
        'func' => function() use ($processor, $auren_cred, $id, $valor, $chavepix, $cpf, $nome_real, $tipo_chave_db) {
            return $processor->processAurenPay($auren_cred, $id, $valor, $chavepix, $cpf, $nome_real, $tipo_chave_db);
        }
    ],
    'bspay' => [
        'cred' => $bspay_cred,
        'func' => function() use ($processor, $bspay_cred, $id, $valor, $chavepix, $cpf, $nome_real) {
            return $processor->processBSPay($bspay_cred, $id, $valor, $chavepix, $cpf, $nome_real);
        }
    ],
    'expfypay' => [
        'cred' => $expfy_cred,
        'func' => function() use ($processor, $expfy_cred, $id, $valor, $chavepix) {
            return $processor->processExpfyPay($expfy_cred, $id, $valor, $chavepix);
        }
    ],
    'poseidonpay' => [
        'cred' => $poseidon_cred,
        'func' => function() use ($processor, $poseidon_cred, $id, $valor, $chavepix, $cpf, $nome_real) {
            return $processor->processPoseidonPay($poseidon_cred, $id, $valor, $chavepix, $cpf, $nome_real);
        }
    ]
];

// Ordem de tentativa (prioridade)
$gateway_order = ['greepay', 'aurenpay', 'bspay', 'expfypay', 'poseidonpay'];

// Se um gateway preferido foi especificado, movê-lo para o início
if ($gateway_preferido && in_array($gateway_preferido, $gateway_order)) {
    $gateway_order = array_merge(
        [$gateway_preferido],
        array_diff($gateway_order, [$gateway_preferido])
    );
}

// Tentar cada gateway na ordem
foreach ($gateway_order as $gateway_name) {
    $gateway = $gateway_processors[$gateway_name];
    
    if (!$gateway['cred']) {
        logToDaanrox("Gateway $gateway_name ignorado (sem credenciais)", [], 'GATEWAY_SKIP');
        continue;
    }
    
    if ($result && $result['success']) {
        logToDaanrox("Parando tentativas - gateway anterior já sucedeu", [], 'GATEWAY_STOP');
        break;
    }
    
    logToDaanrox("Tentando gateway: $gateway_name", [], 'GATEWAY_ATTEMPT');
    
    try {
        $result = $gateway['func']();
        $gateway_responses[$gateway_name] = $result;
        
        if ($result['success']) {
            $gateway_usado = $gateway_name;
            logToDaanrox("Gateway $gateway_name sucedeu", $result, 'GATEWAY_SUCCESS');
            break;
        } else {
            $errors[$gateway_name] = $result['message'];
            logToDaanrox("Gateway $gateway_name falhou", $result, 'GATEWAY_FAIL');
        }
    } catch (Exception $e) {
        $errors[$gateway_name] = 'Exceção: ' . $e->getMessage();
        logError("Gateway $gateway_name", $e->getMessage(), ['trace' => $e->getTraceAsString()]);
    }
}

// Verificar se conseguiu processar
if (!$result || !$result['success']) {
    logToDaanrox('Falha em todos os gateways', [
        'errors' => $errors,
        'responses' => $gateway_responses
    ], 'FATAL');
    
    sendResponse(false, "Falha ao processar pagamento em todos os gateways", [
        'errors' => $errors,
        'debug' => $debug_info,
        'transaction_log' => $transaction_log,
        'gateway_responses' => $gateway_responses
    ], 500);
}

// Atualizar banco de dados
$sql_update = "UPDATE solicitacao_saques SET status = 1, data_att = CONVERT_TZ(NOW(), '+00:00', '-03:00') WHERE transacao_id = ?";

if (!$stmt_update = $mysqli->prepare($sql_update)) {
    logError('Database', 'Erro ao preparar atualização', ['error' => $mysqli->error]);
    sendResponse(false, "Erro ao preparar atualização do banco", null, 500);
}

$stmt_update->bind_param("s", $id);
$stmt_update->execute();

if ($stmt_update->affected_rows > 0) {
    $stmt_update->close();
    
    logToDaanrox('Pagamento processado com sucesso', [
        'transaction_id' => $id,
        'gateway' => $gateway_usado,
        'gateway_transaction_id' => $result['gateway_transaction_id'],
        'full_response' => $result
    ], 'SUCCESS');
    
    sendResponse(true, "Saque aprovado com sucesso via " . strtoupper($gateway_usado), [
        'transaction_id' => $id,
        'gateway_transaction_id' => $result['gateway_transaction_id'],
        'gateway_used' => $gateway_usado,
        'valor' => 'R$ ' . number_format($valor, 2, ',', '.'),
        'debug' => $debug_info,
        'transaction_log' => $transaction_log,
        'gateway_response' => $result['response_data'] ?? null
    ]);
} else {
    $stmt_update->close();
    logError('Database', 'Erro ao atualizar status', ['transaction_id' => $id, 'affected_rows' => 0]);
    sendResponse(false, "Erro ao atualizar status no banco de dados", null, 500);
}