<?php
session_start();
include_once "../config.php";
include_once('../'.DASH.'/services/database.php');
include_once('../'.DASH.'/services/funcao.php');
include_once('../'.DASH.'/services/crud.php');
include_once('../'.DASH.'/services/webhook.php');
global $mysqli;

// ⭐ Configurar handlers de erro ⭐
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ⭐ Handler de erros fatais ⭐
function fatal_error_handler() {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        bspay_log("ERRO FATAL: " . json_encode($error), 'FATAL');
    }
}
register_shutdown_function('fatal_error_handler');

// ⭐ Handler de exceções ⭐
function exception_handler($exception) {
    bspay_log("EXCEÇÃO: " . $exception->getMessage() . " em " . $exception->getFile() . ":" . $exception->getLine(), 'EXCEPTION');
}
set_exception_handler('exception_handler');

// ⭐ Handler de erros ⭐
function error_handler($errno, $errstr, $errfile, $errline) {
    bspay_log("ERRO: [$errno] $errstr em $errfile:$errline", 'PHP_ERROR');
    return false;
}
set_error_handler('error_handler');

// ⭐ Função de log unificada ⭐
function bspay_log($mensagem, $tipo = 'INFO') {
    $logFile = dirname(__DIR__) . '/bspay.txt';
    $date = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'CLI';
    $pid = getmypid();
    
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
    $caller = isset($backtrace[0]) ? basename($backtrace[0]['file']) . ':' . $backtrace[0]['line'] : 'unknown';
    
    $logMessage = "[$date] [PID:$pid] [$ip] [$tipo] [$caller] $mensagem" . PHP_EOL;
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

bspay_log("=== WEBHOOK BSPAY INICIADO ===", 'INICIO');

$raw_input = file_get_contents("php://input");
bspay_log("Payload RAW recebido: " . ($raw_input ? substr($raw_input, 0, 500) : 'VAZIO'), 'PAYLOAD');

$data = json_decode($raw_input, true);

if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
    $erro_json = json_last_error_msg();
    bspay_log("ERRO JSON: " . $erro_json, 'ERRO');
    http_response_code(400);
    exit;
}

// Extrair dados do payload BSPAY
$idTransaction = PHP_SEGURO($data['requestBody']['transactionId'] ?? '');
$typeTransaction = strtolower(PHP_SEGURO($data['requestBody']['paymentType'] ?? ''));
$statusTransaction = strtolower(PHP_SEGURO($data['requestBody']['status'] ?? ''));
$amount = PHP_SEGURO($data['requestBody']['amount']['total'] ?? 0);

bspay_log("Dados processados: ID=$idTransaction, Type=$typeTransaction, Status=$statusTransaction, Valor=$amount", 'PROCESSADO');

$dev_hook = 'https://webhook.site/42161bbc-8877-4171-b9df-998bb61ffdae';

// Função original mantida para compatibilidade
function log_bspay($msg) {
    bspay_log("[BSPAY] $msg", 'WEBHOOK');
}

function url_send(){
    global $data, $dev_hook;
    bspay_log("url_send() chamada", 'DEBUG');
    $url = $dev_hook;
    $ch = curl_init($url);
    $corpo = json_encode($data);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $corpo);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $resultado = curl_exec($ch);
    
    if ($resultado === false) {
        bspay_log("Curl error: " . curl_error($ch), 'ERRO');
    }
    
    curl_close($ch);
    bspay_log("url_send() resultado: " . substr($resultado, 0, 200), 'DEBUG');
    return $resultado;
}
//url_send();

// ==================== FUNÇÕES DE BÔNUS ====================

function verificarBonus($userId, $valorPago, $transacao_id = null) {
    global $mysqli;
    
    bspay_log("verificarBonus() chamado: User=$userId, Valor=$valorPago, Transacao=$transacao_id", 'BONUS');
    
    $sqlCount = "SELECT COUNT(*) as total FROM transacoes WHERE usuario = ? AND tipo = 'deposito' AND status = 'pago'";
    $stmtCount = $mysqli->prepare($sqlCount);
    if (!$stmtCount) {
        bspay_log("ERRO prepare sqlCount: " . $mysqli->error, 'ERRO');
        return 0;
    }
    
    $stmtCount->bind_param("i", $userId);
    $stmtCount->execute();
    $resCount = $stmtCount->get_result();
    $rowCount = $resCount->fetch_assoc();
    $totalPaid = $rowCount['total'];
    $stmtCount->close();
    
    bspay_log("Total de depósitos pagos do usuário: $totalPaid", 'BONUS');
    
    if ($totalPaid > 1) {
        bspay_log("Usuário já tem mais de 1 depósito, sem bônus", 'BONUS');
        return 0;
    }

    $tagValue = 0;
    $payTypeId = 0;

    if ($transacao_id) {
        bspay_log("Buscando pay_type_sub_list_id para transação: $transacao_id", 'BONUS');
        $sqlTrans = "SELECT pay_type_sub_list_id, join_bonus FROM transacoes WHERE transacao_id = ?";
        $stmtTrans = $mysqli->prepare($sqlTrans);
        if (!$stmtTrans) {
            bspay_log("ERRO prepare sqlTrans: " . $mysqli->error, 'ERRO');
            return 0;
        }
        
        $stmtTrans->bind_param("s", $transacao_id);
        $stmtTrans->execute();
        $resTrans = $stmtTrans->get_result();
        if ($rowTrans = $resTrans->fetch_assoc()) {
            $payTypeId = $rowTrans['pay_type_sub_list_id'];
            bspay_log("payTypeId encontrado: $payTypeId", 'BONUS');
            
            if (isset($rowTrans['join_bonus']) && $rowTrans['join_bonus'] != 1) {
                bspay_log("join_bonus não é 1, sem bônus", 'BONUS');
                $stmtTrans->close();
                return 0;
            }
        }
        $stmtTrans->close();
    }

    if ($payTypeId) {
        bspay_log("Buscando bônus específico para payTypeId: $payTypeId", 'BONUS');
        $sqlPay = "SELECT tag_value, bonus_active FROM pay_type_sub_list WHERE id = ?";
        $stmtPay = $mysqli->prepare($sqlPay);
        if (!$stmtPay) {
            bspay_log("ERRO prepare sqlPay específico: " . $mysqli->error, 'ERRO');
            return 0;
        }
        
        $stmtPay->bind_param("i", $payTypeId);
        $stmtPay->execute();
        $resPay = $stmtPay->get_result();
        if ($rowPay = $resPay->fetch_assoc()) {
            if ($rowPay['bonus_active'] == 1) {
                $percentage = floatval($rowPay['tag_value']);
                $tagValue = ($valorPago * $percentage) / 100;
                bspay_log("Bônus específico: $percentage% = R$ $tagValue", 'BONUS');
            } else {
                bspay_log("Bônus inativo para este payType", 'BONUS');
            }
        }
        $stmtPay->close();
    } else {
        bspay_log("Buscando bônus por faixa de valor: $valorPago", 'BONUS');
        $sqlPay = "SELECT tag_value FROM pay_type_sub_list WHERE status = 1 AND bonus_active = 1 AND ? >= min_amount AND ? <= max_amount ORDER BY id DESC LIMIT 1";
        $stmtPay = $mysqli->prepare($sqlPay);
        if (!$stmtPay) {
            bspay_log("ERRO prepare sqlPay faixa: " . $mysqli->error, 'ERRO');
            return 0;
        }
        
        $stmtPay->bind_param("dd", $valorPago, $valorPago);
        $stmtPay->execute();
        $resPay = $stmtPay->get_result();
        if ($rowPay = $resPay->fetch_assoc()) {
            $percentage = floatval($rowPay['tag_value']);
            $tagValue = ($valorPago * $percentage) / 100;
            bspay_log("Bônus por faixa: $percentage% = R$ $tagValue", 'BONUS');
        } else {
            bspay_log("Nenhum bônus encontrado para esta faixa", 'BONUS');
        }
        $stmtPay->close();
    }
    
    bspay_log("verificarBonus() retornando: $tagValue", 'BONUS');
    return $tagValue;
}

function registrarBonusUsado($userId, $valorPago, $bonusRecebido) {
    global $mysqli;
    
    bspay_log("registrarBonusUsado() chamado: User=$userId, ValorPago=$valorPago, Bonus=$bonusRecebido", 'BONUS');
    
    if ($bonusRecebido <= 0) {
        bspay_log("Bônus <= 0, não registrando", 'BONUS');
        return false;
    }
    
    $sql = "INSERT INTO cupom_usados (id_user, valor, bonus, data_registro) VALUES (?, ?, ?, NOW())";
    $stmt = $mysqli->prepare($sql);
    
    if (!$stmt) {
        bspay_log("ERRO prepare registrarBonusUsado: " . $mysqli->error, 'ERRO');
        return false;
    }
    
    $stmt->bind_param("idi", $userId, $valorPago, $bonusRecebido);
    $resultado = $stmt->execute();
    
    if (!$resultado) {
        bspay_log("ERRO execute registrarBonusUsado: " . $stmt->error, 'ERRO');
    } else {
        bspay_log("Bônus registrado com sucesso", 'BONUS');
    }
    
    $stmt->close();
    return $resultado;
}

// ==================== FUNÇÕES PRINCIPAIS ====================

function busca_valor_ipn($transacao_id){
    global $mysqli;
    
    bspay_log("busca_valor_ipn() chamado: Transacao=$transacao_id", 'IPN');
    
    $qry = "SELECT usuario, valor FROM transacoes WHERE transacao_id = ?";
    $stmt = $mysqli->prepare($qry);
    
    if (!$stmt) {
        bspay_log("ERRO prepare busca_valor_ipn: " . $mysqli->error, 'ERRO');
        return false;
    }
    
    $stmt->bind_param("s", $transacao_id);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if ($res->num_rows > 0) {
        $data = $res->fetch_assoc();
        $stmt->close();
        
        $userId = $data['usuario'];
        $valorPago = $data['valor'];
        
        bspay_log("Transação encontrada: User=$userId, Valor=$valorPago", 'IPN');
        
        // ========== VERIFICAR E APLICAR BÔNUS ==========
        $bonusRecebido = verificarBonus($userId, $valorPago, $transacao_id);
        $valorTotal = $valorPago + $bonusRecebido;
        
        bspay_log("Bônus calculado: $bonusRecebido | Total a creditar: $valorTotal", 'IPN');
        
        bspay_log("Chamando adicionarSaldoUsuarioWebhook(User=$userId, Valor=$valorTotal)", 'IPN');
        
        // Verificar se a função existe
        if (!function_exists('adicionarSaldoUsuarioWebhook')) {
            bspay_log("ERRO FATAL: Função adicionarSaldoUsuarioWebhook NÃO EXISTE!", 'ERRO');
        }
        
        // Verificar conexão com banco
        if (!$mysqli || $mysqli->connect_error) {
            bspay_log("ERRO conexão MySQL: " . ($mysqli->connect_error ?? 'Conexão nula'), 'ERRO');
        }
        
        // Tenta executar e captura qualquer erro
        try {
            $retorna_insert_saldo = adicionarSaldoUsuarioWebhook($userId, $valorTotal);
            bspay_log("Resultado adicionarSaldoUsuarioWebhook: " . ($retorna_insert_saldo ? 'SUCESSO' : 'FALHA (retornou false)'), 'IPN');
        } catch (Exception $e) {
            bspay_log("EXCEÇÃO em adicionarSaldoUsuarioWebhook: " . $e->getMessage(), 'ERRO');
            $retorna_insert_saldo = false;
        } catch (Error $e) {
            bspay_log("ERRO FATAL em adicionarSaldoUsuarioWebhook: " . $e->getMessage(), 'ERRO');
            $retorna_insert_saldo = false;
        }
        
        // Se creditou com sucesso
        if ($retorna_insert_saldo) {
            bspay_log("Saldo creditado com sucesso, prosseguindo...", 'IPN');
            
            // REGISTRAR AUDIT FLOW (ROLLOVER)
            bspay_log("Chamando criarAuditFlowDeposito(User=$userId, Valor=$valorPago)", 'IPN');
            if (function_exists('criarAuditFlowDeposito')) {
                criarAuditFlowDeposito($userId, $valorPago);
            } else {
                bspay_log("Função criarAuditFlowDeposito não encontrada", 'AVISO');
            }

            // Registrar o uso do bônus (se houver)
            if ($bonusRecebido > 0) {
                registrarBonusUsado($userId, $valorPago, $bonusRecebido);
            }
            
            bspay_log("Chamando processarTodasComissoes para User $userId, Valor $valorPago", 'IPN');
            
            // Processar comissões de afiliação
            try {
                if (function_exists('processarTodasComissoes')) {
                    $comissoes_result = processarTodasComissoes($userId, $valorPago);
                    bspay_log("Resultado processarTodasComissoes: " . ($comissoes_result ? 'SUCESSO' : 'FALHA/NADA'), 'IPN');
                } else {
                    bspay_log("Função processarTodasComissoes não encontrada", 'AVISO');
                }
            } catch (Throwable $t) {
                bspay_log("ERRO ao executar processarTodasComissoes: " . $t->getMessage(), 'ERRO');
            }
            
            // 🔔 WEBHOOK: Notificar PIX pago
            $qry_user = "SELECT real_name FROM usuarios WHERE id = ?";
            $stmt_user = $mysqli->prepare($qry_user);
            
            if ($stmt_user) {
                $stmt_user->bind_param("i", $userId);
                $stmt_user->execute();
                $res_user = $stmt_user->get_result();
                $user_data = $res_user->fetch_assoc();
                $stmt_user->close();
                
                bspay_log("Chamando WebhookPixPagos para usuário: " . ($user_data['real_name'] ?? 'Desconhecido'), 'IPN');
                
                if (function_exists('WebhookPixPagos')) {
                    WebhookPixPagos($user_data['real_name'] ?? 'Usuário', $_SERVER['HTTP_HOST'], $valorPago);
                }
            } else {
                bspay_log("ERRO prepare qry_user: " . $mysqli->error, 'ERRO');
            }
        } else {
            bspay_log("FALHA ao creditar saldo para usuário $userId", 'ERRO');
        }
        
        return $retorna_insert_saldo;
    } else {
        bspay_log("Nenhuma transação encontrada com ID: $transacao_id", 'ERRO');
    }
    
    $stmt->close();
    return false;
}

function att_paymentpix($transacao_id){
    global $mysqli;
    
    bspay_log("att_paymentpix() chamado: Transacao=$transacao_id", 'UPDATE');
    
    // Verifica se já está pago
    $stmt_check = $mysqli->prepare("SELECT status FROM transacoes WHERE transacao_id = ?");
    if ($stmt_check) {
        $stmt_check->bind_param("s", $transacao_id);
        $stmt_check->execute();
        $res_check = $stmt_check->get_result();
        if ($res_check && $row = $res_check->fetch_assoc()) {
            if ($row['status'] == 'pago') { 
                bspay_log("Transação já está como paga", 'UPDATE');
                $stmt_check->close(); 
                return 2; 
            }
        }
        $stmt_check->close();
    } else {
        bspay_log("ERRO prepare stmt_check: " . $mysqli->error, 'ERRO');
    }
    
    // Atualiza para pago
    $sql = $mysqli->prepare("UPDATE transacoes SET status='pago' WHERE transacao_id=?");
    if (!$sql) {
        bspay_log("ERRO prepare update: " . $mysqli->error, 'ERRO');
        return 0;
    }
    
    $sql->bind_param("s", $transacao_id);
    
    if ($sql->execute()) {
        bspay_log("Update realizado com sucesso", 'UPDATE');
        $buscar = busca_valor_ipn($transacao_id);
        if ($buscar) {
            bspay_log("busca_valor_ipn retornou sucesso", 'UPDATE');
            $rf = 1;
        } else {
            bspay_log("busca_valor_ipn retornou falha", 'UPDATE');
            $rf = 0;
        }
    } else {
        bspay_log("ERRO execute update: " . $sql->error, 'ERRO');
        $rf = 0;
    }
    
    return $rf;
}

// ==================== EXECUÇÃO PRINCIPAL ====================
if (isset($idTransaction) && $typeTransaction === "pix" && $statusTransaction === "paid") {
    bspay_log("Status PAID detectado para PIX, processando pagamento", 'PRINCIPAL');
    $att_transacao = att_paymentpix($idTransaction);
    bspay_log("Resultado att_paymentpix: $att_transacao", 'PRINCIPAL');
    
    // Se já estava pago (return 2), ainda assim notificar webhook
    if ($att_transacao == 2) {
        bspay_log("Transação já estava paga, mas notificando webhook novamente", 'PRINCIPAL');
        $qry = "SELECT t.usuario, t.valor, u.real_name FROM transacoes t JOIN usuarios u ON u.id = t.usuario WHERE t.transacao_id = ? LIMIT 1";
        $stmt = $mysqli->prepare($qry);
        if ($stmt) {
            $stmt->bind_param("s", $idTransaction);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res && $row = $res->fetch_assoc()) {
                bspay_log("Disparando WebhookPixPagos para transação já paga", 'PRINCIPAL');
                WebhookPixPagos($row['real_name'] ?? 'Usuário', $_SERVER['HTTP_HOST'], $row['valor'] ?? 0);
            }
            $stmt->close();
        }
    }
} else {
    bspay_log("Status não é PAID ou não é PIX: Type=$typeTransaction, Status=$statusTransaction", 'PRINCIPAL');
}

bspay_log("=== WEBHOOK BSPAY FINALIZADO ===", 'FIM');

// ==================== FUNÇÃO DE ADIÇÃO DE SALDO ====================

function adicionarSaldoUsuarioWebhook($id_user, $valor) {
    global $mysqli;
    
    bspay_log("adicionarSaldoUsuarioWebhook() chamado: User=$id_user, Valor=$valor", 'CRUD');
    
    try {
        if (!$mysqli) {
            bspay_log("ERRO: Conexão MySQL inválida.", 'ERRO');
            return false;
        }
        
        // 1. Obter saldo atual
        bspay_log("Preparando SELECT saldo...", 'CRUD');
        $stmt = $mysqli->prepare("SELECT saldo FROM usuarios WHERE id = ?");
        if (!$stmt) {
            bspay_log("ERRO Prepare Select Saldo: " . $mysqli->error, 'ERRO');
            return false;
        }
        
        $stmt->bind_param("i", $id_user);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows === 0) {
            bspay_log("ERRO: Usuário $id_user não encontrado.", 'ERRO');
            $stmt->close();
            return false;
        }
        $row = $res->fetch_assoc();
        $saldo_atual = $row['saldo'];
        $stmt->close();
        
        $novo_saldo = $saldo_atual + $valor;
        bspay_log("Saldo atual: $saldo_atual, Novo saldo: $novo_saldo", 'CRUD');
        
        // 2. Atualizar saldo
        bspay_log("Executando UPDATE saldo...", 'CRUD');
        $stmt = $mysqli->prepare("UPDATE usuarios SET saldo = ? WHERE id = ?");
        if (!$stmt) {
            bspay_log("ERRO Prepare Update Saldo: " . $mysqli->error, 'ERRO');
            return false;
        }
        
        $stmt->bind_param("di", $novo_saldo, $id_user);
        
        if (!$stmt->execute()) {
            bspay_log("ERRO Execute Update Saldo: " . $stmt->error, 'ERRO');
            $stmt->close();
            return false;
        }
        $stmt->close();
        bspay_log("UPDATE saldo concluído com sucesso", 'CRUD');
        
        // 3. Registrar log em adicao_saldo (valores em centavos)
        $tipo = "deposito_pix";
        $data_registro = date('Y-m-d H:i:s');
        $valor_centavos = intval($valor * 100); // Converte para centavos
        
        bspay_log("Registrando log em adicao_saldo: Valor em centavos = $valor_centavos", 'CRUD');
        $stmt = $mysqli->prepare("INSERT INTO adicao_saldo (id_user, valor, tipo, data_registro) VALUES (?, ?, ?, ?)");
        
        if ($stmt) {
            $stmt->bind_param("iiss", $id_user, $valor_centavos, $tipo, $data_registro);
            if (!$stmt->execute()) {
                bspay_log("ERRO Execute Insert AdicaoSaldo: " . $stmt->error, 'ERRO');
            } else {
                bspay_log("Log de adição de saldo registrado com sucesso (centavos: $valor_centavos)", 'CRUD');
            }
            $stmt->close();
        } else {
            bspay_log("AVISO: Falha ao preparar insert em adicao_saldo: " . $mysqli->error, 'AVISO');
        }
        
        return true;
    } catch (Throwable $e) {
        bspay_log("ERRO CRÍTICO (Exception) em adicionarSaldoUsuarioWebhook: " . $e->getMessage(), 'ERRO');
        return false;
    }
}

// ==================== FUNÇÕES DE LOG PARA AFILIAÇÃO ====================

function logAfiliacao($message) {
    bspay_log("[AFILIACAO] $message", 'AFILIACAO');
}

// ==================== FUNÇÕES DE AFILIAÇÃO ====================

function getAfiliadosConfig() {
    global $mysqli;
    
    bspay_log("getAfiliadosConfig() chamado", 'AFILIACAO');
    
    if (!$mysqli) {
        bspay_log("ERRO CRÍTICO: Conexão MySQL perdida em getAfiliadosConfig.", 'ERRO');
        return null;
    }

    $qry = "SELECT * FROM afiliados_config WHERE id = 1";
    $res = mysqli_query($mysqli, $qry);
    
    if (!$res) {
        bspay_log("ERRO SQL em getAfiliadosConfig: " . mysqli_error($mysqli), 'ERRO');
        return null;
    }

    $config = mysqli_fetch_assoc($res);
    
    if (!$config) {
        bspay_log("ERRO: Configuração de afiliados não encontrada.", 'ERRO');
    } else {
        bspay_log("Configuração encontrada: " . json_encode($config), 'AFILIACAO');
    }
    
    return $config;
}

function getHierarquiaAfiliados($user_id) {
    global $mysqli;
    
    bspay_log("getHierarquiaAfiliados() chamado: User=$user_id", 'AFILIACAO');
    
    $hierarquia = [
        'nivel1' => null,
        'nivel2' => null,
        'nivel3' => null
    ];

    if (!$mysqli) {
        bspay_log("ERRO CRÍTICO: Conexão MySQL perdida em getHierarquiaAfiliados.", 'ERRO');
        return $hierarquia;
    }

    // Busca invitation_code do usuário
    $qry = "SELECT invitation_code FROM usuarios WHERE id = ?";
    $stmt = $mysqli->prepare($qry);
    if (!$stmt) {
        bspay_log("ERRO prepare invitation_code: " . $mysqli->error, 'ERRO');
        return $hierarquia;
    }
    
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    
    if (!$user || !$user['invitation_code']) {
        bspay_log("Usuário $user_id não tem invitation_code", 'AFILIACAO');
        return $hierarquia;
    }
    
    bspay_log("Usuário convidado por código: " . $user['invitation_code'], 'AFILIACAO');

    // Busca nível 1 (quem indicou diretamente)
    $qry = "SELECT id, mobile, invite_code, invitation_code FROM usuarios WHERE invite_code = ?";
    $stmt = $mysqli->prepare($qry);
    if (!$stmt) {
        bspay_log("ERRO prepare nivel1: " . $mysqli->error, 'ERRO');
        return $hierarquia;
    }
    
    $stmt->bind_param("s", $user['invitation_code']);
    $stmt->execute();
    $result = $stmt->get_result();
    $nivel1 = $result->fetch_assoc();
    $stmt->close();
    
    if ($nivel1) {
        $hierarquia['nivel1'] = $nivel1;
        bspay_log("Nível 1 encontrado: ID " . $nivel1['id'], 'AFILIACAO');

        // Busca nível 2 (quem indicou o nível 1)
        if (!empty($nivel1['invitation_code'])) {
            $qry = "SELECT id, mobile, invite_code, invitation_code FROM usuarios WHERE invite_code = ?";
            $stmt = $mysqli->prepare($qry);
            
            if ($stmt) {
                $stmt->bind_param("s", $nivel1['invitation_code']);
                $stmt->execute();
                $result = $stmt->get_result();
                $nivel2 = $result->fetch_assoc();
                $stmt->close();
                
                if ($nivel2) {
                    $hierarquia['nivel2'] = $nivel2;
                    bspay_log("Nível 2 encontrado: ID " . $nivel2['id'], 'AFILIACAO');
                    
                    // Busca nível 3
                    if (!empty($nivel2['invitation_code'])) {
                        $qry = "SELECT id, mobile, invite_code, invitation_code FROM usuarios WHERE invite_code = ?";
                        $stmt = $mysqli->prepare($qry);
                        
                        if ($stmt) {
                            $stmt->bind_param("s", $nivel2['invitation_code']);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            $nivel3 = $result->fetch_assoc();
                            $stmt->close();
                            
                            if ($nivel3) {
                                $hierarquia['nivel3'] = $nivel3;
                                bspay_log("Nível 3 encontrado: ID " . $nivel3['id'], 'AFILIACAO');
                            }
                        }
                    }
                }
            }
        }
    } else {
        bspay_log("Nenhum usuário encontrado com o código " . $user['invitation_code'], 'AFILIACAO');
    }
    
    bspay_log("Hierarquia final: " . json_encode($hierarquia), 'AFILIACAO');
    return $hierarquia;
}

function aplicarChanceCpa($chanceCpa) {
    if ($chanceCpa >= 100) {
        bspay_log("Chance CPA >=100, aprovado automaticamente", 'AFILIACAO');
        return true;
    }
    
    $random = mt_rand(1, 100);
    $aprovado = $random <= $chanceCpa;
    bspay_log("Sorteio: random=$random, chance=$chanceCpa, resultado=" . ($aprovado ? 'APROVADO' : 'REPROVADO'), 'AFILIACAO');
    return $aprovado;
}

function creditarComissaoAfiliado($user_id, $valor, $nivel, $depositante_id, $valor_deposito, $porcentagem) {
    global $mysqli;
    
    bspay_log("creditarComissaoAfiliado() chamado: Afiliado=$user_id, Valor=$valor, Nivel=$nivel, %=$porcentagem", 'AFILIACAO');
    
    // Atualiza saldo_afiliados
    $qry = "UPDATE usuarios SET saldo_afiliados = saldo_afiliados + ? WHERE id = ?";
    $stmt = $mysqli->prepare($qry);
    
    if (!$stmt) {
        bspay_log("ERRO prepare update saldo_afiliados: " . $mysqli->error, 'ERRO');
        return false;
    }
    
    $stmt->bind_param("di", $valor, $user_id);
    
    if (!$stmt->execute()) {
        bspay_log("ERRO execute update saldo_afiliados: " . $stmt->error, 'ERRO');
        $stmt->close();
        return false;
    }
    
    bspay_log("Update saldo_afiliados realizado com sucesso", 'AFILIACAO');
    $stmt->close();

    // Registra em adicao_saldo (valores em centavos)
    $data_registro = date('Y-m-d H:i:s');
    $tipo = "comissao_cpa_nivel_{$nivel}";
    $valor_centavos = intval($valor * 100); // Converte para centavos
    
    $qry = "INSERT INTO adicao_saldo (id_user, valor, tipo, data_registro) VALUES (?, ?, ?, ?)";
    $stmt = $mysqli->prepare($qry);
    
    if (!$stmt) {
        bspay_log("ERRO prepare insert adicao_saldo: " . $mysqli->error, 'ERRO');
        return false;
    }
    
    $stmt->bind_param("iiss", $user_id, $valor_centavos, $tipo, $data_registro);
    
    if (!$stmt->execute()) {
        bspay_log("ERRO execute insert adicao_saldo: " . $stmt->error, 'ERRO');
        $stmt->close();
        return false;
    }
    
    bspay_log("Insert adicao_saldo realizado com sucesso", 'AFILIACAO');
    $stmt->close();
    
    bspay_log("SUCESSO: Comissão creditada para afiliado $user_id", 'AFILIACAO');
    return true;
}

function processarCpa($user_id, $valor_deposito) {
    global $mysqli;

    bspay_log("processarCpa() iniciado: User=$user_id, Deposito=$valor_deposito", 'AFILIACAO');

    // Busca configuração
    $config = getAfiliadosConfig();
    if (!$config) {
        bspay_log("ABORTANDO: Configuração inválida", 'ERRO');
        return false;
    }
    
    // Verifica valor mínimo
    if ($valor_deposito < $config['minDepForCpa']) {
        bspay_log("ABORTANDO: Valor $valor_deposito < mínimo {$config['minDepForCpa']}", 'AFILIACAO');
        return false;
    }
    
    // Verifica chance CPA
    if (!aplicarChanceCpa($config['chanceCpa'])) {
        bspay_log("ABORTANDO: Reprovado na chance CPA", 'AFILIACAO');
        return false;
    }

    // Busca hierarquia
    $hierarquia = getHierarquiaAfiliados($user_id);
    $comissoes_processadas = 0;

    // Função para buscar porcentagem do afiliado
    $getPorcentagem = function($afiliado_id, $nivel, $global_valor) use ($mysqli) {
        bspay_log("Buscando porcentagem para afiliado=$afiliado_id, nivel=$nivel, global=$global_valor", 'AFILIACAO');
        
        $coluna = "cpaLvl{$nivel}";
        $qry = "SELECT {$coluna} FROM usuarios WHERE id = ?";
        $stmt = $mysqli->prepare($qry);
        
        if (!$stmt) {
            bspay_log("ERRO prepare getPorcentagem: " . $mysqli->error, 'ERRO');
            return floatval($global_valor);
        }
        
        $stmt->bind_param("i", $afiliado_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $stmt->close();
        
        $valor_final = floatval($global_valor);
        if ($row && isset($row[$coluna]) && floatval($row[$coluna]) > 0) {
            $valor_final = floatval($row[$coluna]);
            bspay_log("Usando porcentagem personalizada: $valor_final%", 'AFILIACAO');
        } else {
            bspay_log("Usando porcentagem global: $valor_final%", 'AFILIACAO');
        }
        
        return $valor_final;
    };

    // Processa nível 1
    if ($hierarquia['nivel1']) {
        bspay_log("Processando Nível 1: " . json_encode($hierarquia['nivel1']), 'AFILIACAO');
        
        $porcentagem_nivel1 = $getPorcentagem($hierarquia['nivel1']['id'], 1, $config['cpaLvl1']);
        
        if ($porcentagem_nivel1 > 0) {
            $comissao_nivel1 = ($valor_deposito * $porcentagem_nivel1) / 100;
            bspay_log("Nível 1: comissão calculada = $comissao_nivel1", 'AFILIACAO');
            
            if (creditarComissaoAfiliado(
                $hierarquia['nivel1']['id'], 
                $comissao_nivel1, 
                1, 
                $user_id,
                $valor_deposito,
                $porcentagem_nivel1
            )) {
                $comissoes_processadas++;
                bspay_log("Nível 1: comissão creditada com sucesso", 'AFILIACAO');
            } else {
                bspay_log("Nível 1: falha ao creditar comissão", 'ERRO');
            }
        } else {
            bspay_log("Nível 1: porcentagem zerada, pulando", 'AFILIACAO');
        }

        // Processa nível 2 (se existir)
        if ($hierarquia['nivel2']) {
            bspay_log("Processando Nível 2: " . json_encode($hierarquia['nivel2']), 'AFILIACAO');
            
            $porcentagem_nivel2 = $getPorcentagem($hierarquia['nivel2']['id'], 2, $config['cpaLvl2']);
            
            if ($porcentagem_nivel2 > 0) {
                $comissao_nivel2 = ($valor_deposito * $porcentagem_nivel2) / 100;
                bspay_log("Nível 2: comissão calculada = $comissao_nivel2", 'AFILIACAO');
                
                if (creditarComissaoAfiliado(
                    $hierarquia['nivel2']['id'], 
                    $comissao_nivel2, 
                    2, 
                    $user_id,
                    $valor_deposito,
                    $porcentagem_nivel2
                )) {
                    $comissoes_processadas++;
                    bspay_log("Nível 2: comissão creditada com sucesso", 'AFILIACAO');
                } else {
                    bspay_log("Nível 2: falha ao creditar comissão", 'ERRO');
                }
            } else {
                bspay_log("Nível 2: porcentagem zerada, pulando", 'AFILIACAO');
            }

            // Processa nível 3 (se existir)
            if ($hierarquia['nivel3']) {
                bspay_log("Processando Nível 3: " . json_encode($hierarquia['nivel3']), 'AFILIACAO');
                
                $porcentagem_nivel3 = $getPorcentagem($hierarquia['nivel3']['id'], 3, $config['cpaLvl3']);
                
                if ($porcentagem_nivel3 > 0) {
                    $comissao_nivel3 = ($valor_deposito * $porcentagem_nivel3) / 100;
                    bspay_log("Nível 3: comissão calculada = $comissao_nivel3", 'AFILIACAO');
                    
                    if (creditarComissaoAfiliado(
                        $hierarquia['nivel3']['id'], 
                        $comissao_nivel3, 
                        3, 
                        $user_id,
                        $valor_deposito,
                        $porcentagem_nivel3
                    )) {
                        $comissoes_processadas++;
                        bspay_log("Nível 3: comissão creditada com sucesso", 'AFILIACAO');
                    } else {
                        bspay_log("Nível 3: falha ao creditar comissão", 'ERRO');
                    }
                } else {
                    bspay_log("Nível 3: porcentagem zerada, pulando", 'AFILIACAO');
                }
            }
        }
    } else {
        bspay_log("Nenhum afiliado de nível 1 encontrado", 'AFILIACAO');
    }

    $resultado = $comissoes_processadas > 0;
    bspay_log("processarCpa() finalizado. Comissões processadas: $comissoes_processadas. Resultado: " . ($resultado ? 'TRUE' : 'FALSE'), 'AFILIACAO');
    return $resultado;
}

function processarTodasComissoes($user_id, $valor_deposito) {
    global $mysqli;
    
    bspay_log("processarTodasComissoes() chamado: User=$user_id, Valor=$valor_deposito", 'AFILIACAO');
    
    // ⭐ VERIFICAÇÃO REMOVIDA - PAGA EM TODOS OS DEPÓSITOS ⭐
    // Não há mais verificação de primeiro depósito
    
    bspay_log("Processando CPA para depósito...", 'AFILIACAO');
    $cpa_processado = processarCpa($user_id, $valor_deposito);
    
    bspay_log("processarTodasComissoes() finalizado. Retorno: " . ($cpa_processado ? 'TRUE' : 'FALSE'), 'AFILIACAO');
    return $cpa_processado ? true : false;
}

?>