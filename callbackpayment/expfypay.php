<?php
session_start();
include_once "../config.php";
include_once('../'.DASH.'/services/database.php');
include_once('../'.DASH.'/services/funcao.php');
include_once('../'.DASH.'/services/crud.php');
include_once('../'.DASH.'/services/webhook.php');
global $mysqli;


// ⭐ NOVO: Configurar para mostrar todos os erros ⭐
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ⭐ NOVO: Função para capturar erros fatais ⭐
function fatal_error_handler() {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        daanrox_log("ERRO FATAL: " . json_encode($error), 'FATAL');
    }
}
register_shutdown_function('fatal_error_handler');

// ⭐ NOVO: Função para capturar exceções ⭐
function exception_handler($exception) {
    daanrox_log("EXCEÇÃO: " . $exception->getMessage() . " em " . $exception->getFile() . ":" . $exception->getLine(), 'EXCEPTION');
}
set_exception_handler('exception_handler');

// ⭐ NOVO: Função para capturar erros ⭐
function error_handler($errno, $errstr, $errfile, $errline) {
    daanrox_log("ERRO: [$errno] $errstr em $errfile:$errline", 'PHP_ERROR');
    return false; // Permite que o handler padrão do PHP também execute
}
set_error_handler('error_handler');

// ⭐ NOVO: Função de log unificada para daanrox.txt ⭐
function daanrox_log($mensagem, $tipo = 'INFO') {
    $logFile = dirname(__DIR__) . '/daanrox.txt';
    $date = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'CLI';
    $pid = getmypid();
    
    // Pega onde foi chamada a função (arquivo:linha)
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
    $caller = isset($backtrace[0]) ? basename($backtrace[0]['file']) . ':' . $backtrace[0]['line'] : 'unknown';
    
    $logMessage = "[$date] [PID:$pid] [$ip] [$tipo] [$caller] $mensagem" . PHP_EOL;
    
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

daanrox_log("=== WEBHOOK EXPFYPAY INICIADO ===", 'INICIO');

$raw = file_get_contents('php://input');
daanrox_log("Payload recebido: " . ($raw ? substr($raw, 0, 500) : 'VAZIO'), 'PAYLOAD');

$data = json_decode($raw, true);

if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
    $erro_json = json_last_error_msg();
    daanrox_log("Erro ao decodificar JSON: $erro_json", 'ERRO');
    http_response_code(400);
    exit;
}

$idTransaction = PHP_SEGURO($data['transaction_id'] ?? '');
$statusTransaction = strtolower(PHP_SEGURO($data['status'] ?? ''));
$amount = PHP_SEGURO($data['amount'] ?? 0);

daanrox_log("Dados processados: ID=$idTransaction, Status=$statusTransaction, Valor=$amount", 'PROCESSADO');

// Função original mantida para compatibilidade
function log_expfypay($msg) {
    $logfile = dirname(__DIR__) . '/errorlog.log';
    $date = date('d-M-Y H:i:s T');
    file_put_contents($logfile, "[$date] [EXPFYPAY WEBHOOK] $msg" . PHP_EOL, FILE_APPEND);
    daanrox_log("[EXPFYPAY] $msg", 'WEBHOOK'); // Também loga no daanrox
}

$dev_hook = 'https://webhook.site/42161bbc-8877-4171-b9df-998bb61ffdae';

function url_send(){
    global $data, $dev_hook;
    daanrox_log("url_send() chamada", 'DEBUG');
    $url = $dev_hook;
    $ch = curl_init($url);
    $corpo = json_encode($data);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $corpo);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $resultado = curl_exec($ch);
    
    if ($resultado === false) {
        daanrox_log("Curl error: " . curl_error($ch), 'ERRO');
    }
    
    curl_close($ch);
    daanrox_log("url_send() resultado: " . substr($resultado, 0, 200), 'DEBUG');
    return $resultado;
}
//url_send();

// ==================== FUNÇÕES DE BÔNUS ====================

/**
 * Verifica se existe bônus ativo para o valor e se o usuário ainda não usou
 * Retorna o valor do bônus ou 0
 */
function verificarBonus($userId, $valorPago, $transacao_id = null) {
    global $mysqli;
    
    daanrox_log("verificarBonus() chamado: User=$userId, Valor=$valorPago, Transacao=$transacao_id", 'BONUS');
    
    $sqlCount = "SELECT COUNT(*) as total FROM transacoes WHERE usuario = ? AND tipo = 'deposito' AND status = 'pago'";
    $stmtCount = $mysqli->prepare($sqlCount);
    if (!$stmtCount) {
        daanrox_log("ERRO prepare sqlCount: " . $mysqli->error, 'ERRO');
        return 0;
    }
    
    $stmtCount->bind_param("i", $userId);
    $stmtCount->execute();
    $resCount = $stmtCount->get_result();
    $rowCount = $resCount->fetch_assoc();
    $totalPaid = $rowCount['total'];
    $stmtCount->close();
    
    daanrox_log("Total de depósitos pagos do usuário: $totalPaid", 'BONUS');
    
    if ($totalPaid > 1) {
        daanrox_log("Usuário já tem mais de 1 depósito, sem bônus", 'BONUS');
        return 0;
    }

    $tagValue = 0;
    $payTypeId = 0;

    if ($transacao_id) {
        daanrox_log("Buscando pay_type_sub_list_id para transação: $transacao_id", 'BONUS');
        $sqlTrans = "SELECT pay_type_sub_list_id, join_bonus FROM transacoes WHERE transacao_id = ?";
        $stmtTrans = $mysqli->prepare($sqlTrans);
        if (!$stmtTrans) {
            daanrox_log("ERRO prepare sqlTrans: " . $mysqli->error, 'ERRO');
            return 0;
        }
        
        $stmtTrans->bind_param("s", $transacao_id);
        $stmtTrans->execute();
        $resTrans = $stmtTrans->get_result();
        if ($rowTrans = $resTrans->fetch_assoc()) {
            $payTypeId = $rowTrans['pay_type_sub_list_id'];
            daanrox_log("payTypeId encontrado: $payTypeId", 'BONUS');
            
            if (isset($rowTrans['join_bonus']) && $rowTrans['join_bonus'] != 1) {
                daanrox_log("join_bonus não é 1, sem bônus", 'BONUS');
                $stmtTrans->close();
                return 0;
            }
        }
        $stmtTrans->close();
    }

    if ($payTypeId) {
        daanrox_log("Buscando bônus específico para payTypeId: $payTypeId", 'BONUS');
        $sqlPay = "SELECT tag_value, bonus_active FROM pay_type_sub_list WHERE id = ?";
        $stmtPay = $mysqli->prepare($sqlPay);
        if (!$stmtPay) {
            daanrox_log("ERRO prepare sqlPay específico: " . $mysqli->error, 'ERRO');
            return 0;
        }
        
        $stmtPay->bind_param("i", $payTypeId);
        $stmtPay->execute();
        $resPay = $stmtPay->get_result();
        if ($rowPay = $resPay->fetch_assoc()) {
            if ($rowPay['bonus_active'] == 1) {
                $percentage = floatval($rowPay['tag_value']);
                $tagValue = ($valorPago * $percentage) / 100;
                daanrox_log("Bônus específico: $percentage% = R$ $tagValue", 'BONUS');
            } else {
                daanrox_log("Bônus inativo para este payType", 'BONUS');
            }
        }
        $stmtPay->close();
    } else {
        daanrox_log("Buscando bônus por faixa de valor: $valorPago", 'BONUS');
        $sqlPay = "SELECT tag_value FROM pay_type_sub_list WHERE status = 1 AND bonus_active = 1 AND ? >= min_amount AND ? <= max_amount ORDER BY id DESC LIMIT 1";
        $stmtPay = $mysqli->prepare($sqlPay);
        if (!$stmtPay) {
            daanrox_log("ERRO prepare sqlPay faixa: " . $mysqli->error, 'ERRO');
            return 0;
        }
        
        $stmtPay->bind_param("dd", $valorPago, $valorPago);
        $stmtPay->execute();
        $resPay = $stmtPay->get_result();
        if ($rowPay = $resPay->fetch_assoc()) {
            $percentage = floatval($rowPay['tag_value']);
            $tagValue = ($valorPago * $percentage) / 100;
            daanrox_log("Bônus por faixa: $percentage% = R$ $tagValue", 'BONUS');
        } else {
            daanrox_log("Nenhum bônus encontrado para esta faixa", 'BONUS');
        }
        $stmtPay->close();
    }
    
    daanrox_log("verificarBonus() retornando: $tagValue", 'BONUS');
    return $tagValue;
}

/**
 * Registra o uso do bônus na tabela cupom_usados
 */
function registrarBonusUsado($userId, $valorPago, $bonusRecebido) {
    global $mysqli;
    
    daanrox_log("registrarBonusUsado() chamado: User=$userId, ValorPago=$valorPago, Bonus=$bonusRecebido", 'BONUS');
    
    if ($bonusRecebido <= 0) {
        daanrox_log("Bônus <= 0, não registrando", 'BONUS');
        return false;
    }
    
    $sql = "INSERT INTO cupom_usados (id_user, valor, bonus, data_registro) VALUES (?, ?, ?, NOW())";
    $stmt = $mysqli->prepare($sql);
    
    if (!$stmt) {
        daanrox_log("ERRO prepare registrarBonusUsado: " . $mysqli->error, 'ERRO');
        return false;
    }
    
    $stmt->bind_param("idi", $userId, $valorPago, $bonusRecebido);
    $resultado = $stmt->execute();
    
    if (!$resultado) {
        daanrox_log("ERRO execute registrarBonusUsado: " . $stmt->error, 'ERRO');
    } else {
        daanrox_log("Bônus registrado com sucesso", 'BONUS');
    }
    
    $stmt->close();
    return $resultado;
}

// ==================== FUNÇÕES PRINCIPAIS ====================

function busca_valor_ipn($transacao_id){
    global $mysqli;
    
    daanrox_log("busca_valor_ipn() chamado: Transacao=$transacao_id", 'IPN');
    
    $qry = "SELECT usuario, valor FROM transacoes WHERE transacao_id = ?";
    $stmt = $mysqli->prepare($qry);
    
    if (!$stmt) {
        daanrox_log("ERRO prepare busca_valor_ipn: " . $mysqli->error, 'ERRO');
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
        
        daanrox_log("Transação encontrada: User=$userId, Valor=$valorPago", 'IPN');
        
        // ========== VERIFICAR E APLICAR BÔNUS ==========
        $bonusRecebido = verificarBonus($userId, $valorPago, $transacao_id);
        $valorTotal = $valorPago + $bonusRecebido;
        
        daanrox_log("Bônus calculado: $bonusRecebido | Total a creditar: $valorTotal", 'IPN');
        
        daanrox_log("Chamando adicionarSaldoUsuario(User=$userId, Valor=$valorTotal)", 'IPN');
        
        // ⭐ NOVO: Verificar se a função existe ⭐
        if (!function_exists('adicionarSaldoUsuario')) {
            daanrox_log("ERRO FATAL: Função adicionarSaldoUsuario NÃO EXISTE!", 'ERRO');
        }
        
        // ⭐ NOVO: Verificar conexão com banco antes ⭐
        if (!$mysqli || $mysqli->connect_error) {
            daanrox_log("ERRO conexão MySQL: " . ($mysqli->connect_error ?? 'Conexão nula'), 'ERRO');
        }
        
        // Tenta executar e captura qualquer erro
        try {
            $retorna_insert_saldo = adicionarSaldoUsuario($userId, $valorTotal);
            daanrox_log("Resultado adicionarSaldoUsuario: " . ($retorna_insert_saldo ? 'SUCESSO' : 'FALHA (retornou false)'), 'IPN');
        } catch (Exception $e) {
            daanrox_log("EXCEÇÃO em adicionarSaldoUsuario: " . $e->getMessage(), 'ERRO');
            $retorna_insert_saldo = false;
        } catch (Error $e) {
            daanrox_log("ERRO FATAL em adicionarSaldoUsuario: " . $e->getMessage(), 'ERRO');
            $retorna_insert_saldo = false;
        }
        
        // Se creditou com sucesso
        if ($retorna_insert_saldo) {
            daanrox_log("Saldo creditado com sucesso, prosseguindo...", 'IPN');
            
            // REGISTRAR AUDIT FLOW (ROLLOVER)
            daanrox_log("Chamando criarAuditFlowDeposito(User=$userId, Valor=$valorPago)", 'IPN');
            criarAuditFlowDeposito($userId, $valorPago);

            // Registrar o uso do bônus (se houver)
            if ($bonusRecebido > 0) {
                registrarBonusUsado($userId, $valorPago, $bonusRecebido);
            }
            
            daanrox_log("Chamando processarTodasComissoes para User $userId, Valor $valorPago", 'IPN');
            
            // Processar comissões de afiliação
            $comissoes_result = processarTodasComissoes($userId, $valorPago);
            daanrox_log("Resultado processarTodasComissoes: " . ($comissoes_result ? 'SUCESSO' : 'FALHA/NADA'), 'IPN');
            
            // 🔔 WEBHOOK: Notificar PIX pago
            $qry_user = "SELECT real_name FROM usuarios WHERE id = ?";
            $stmt_user = $mysqli->prepare($qry_user);
            
            if ($stmt_user) {
                $stmt_user->bind_param("i", $userId);
                $stmt_user->execute();
                $res_user = $stmt_user->get_result();
                $user_data = $res_user->fetch_assoc();
                $stmt_user->close();
                
                daanrox_log("Chamando WebhookPixPagos para usuário: " . ($user_data['nome'] ?? 'Desconhecido'), 'IPN');
                WebhookPixPagos($user_data['real_name'] ?? 'Usuário', $_SERVER['HTTP_HOST'], $valorPago);
            } else {
                daanrox_log("ERRO prepare qry_user: " . $mysqli->error, 'ERRO');
            }
        } else {
            daanrox_log("FALHA ao creditar saldo para usuário $userId", 'ERRO');
        }
        
        return $retorna_insert_saldo;
    } else {
        daanrox_log("Nenhuma transação encontrada com ID: $transacao_id", 'ERRO');
    }
    
    $stmt->close();
    return false;
}

function att_paymentpix($transacao_id){
    global $mysqli;
    
    daanrox_log("att_paymentpix() chamado: Transacao=$transacao_id", 'UPDATE');
    
    // Verifica se já está pago
    $stmt_check = $mysqli->prepare("SELECT status FROM transacoes WHERE transacao_id = ?");
    if ($stmt_check) {
        $stmt_check->bind_param("s", $transacao_id);
        $stmt_check->execute();
        $res_check = $stmt_check->get_result();
        if ($res_check && $row = $res_check->fetch_assoc()) {
            if ($row['status'] == 'pago') { 
                daanrox_log("Transação já está como paga", 'UPDATE');
                $stmt_check->close(); 
                return 2; 
            }
        }
        $stmt_check->close();
    } else {
        daanrox_log("ERRO prepare stmt_check: " . $mysqli->error, 'ERRO');
    }
    
    // Atualiza para pago
    $sql = $mysqli->prepare("UPDATE transacoes SET status='pago' WHERE transacao_id=?");
    if (!$sql) {
        daanrox_log("ERRO prepare update: " . $mysqli->error, 'ERRO');
        return 0;
    }
    
    $sql->bind_param("s", $transacao_id);
    
    if ($sql->execute()) {
        daanrox_log("Update realizado com sucesso", 'UPDATE');
        $buscar = busca_valor_ipn($transacao_id);
        if ($buscar) {
            daanrox_log("busca_valor_ipn retornou sucesso", 'UPDATE');
            $rf = 1;
        } else {
            daanrox_log("busca_valor_ipn retornou falha", 'UPDATE');
            $rf = 0;
        }
    } else {
        daanrox_log("ERRO execute update: " . $sql->error, 'ERRO');
        $rf = 0;
    }
    
    return $rf;
}

// ==================== EXECUÇÃO PRINCIPAL ====================
if (isset($idTransaction) && $statusTransaction == "completed") {
    daanrox_log("Status COMPLETED detectado, processando pagamento", 'PRINCIPAL');
    $att_transacao = att_paymentpix($idTransaction);
    daanrox_log("Resultado att_paymentpix: $att_transacao", 'PRINCIPAL');
} else {
    daanrox_log("Status não é COMPLETED: $statusTransaction", 'PRINCIPAL');
}

daanrox_log("=== WEBHOOK EXPFYPAY FINALIZADO ===", 'FIM');

// ==================== LÓGICA DE AFILIAÇÃO (INTEGRADA) ====================

// Função de log específica para afiliação (agora usando daanrox_log)
function logAfiliacao($message) {
    daanrox_log("[AFILIACAO] $message", 'AFILIACAO');
}

/**
 * Busca a configuração de afiliados
 */
function getAfiliadosConfig() {
    global $mysqli;
    
    daanrox_log("getAfiliadosConfig() chamado", 'AFILIACAO');
    
    $qry = "SELECT * FROM afiliados_config WHERE id = 1";
    $res = mysqli_query($mysqli, $qry);
    
    if (!$res) {
        daanrox_log("ERRO query afiliados_config: " . mysqli_error($mysqli), 'ERRO');
        return null;
    }
    
    $config = mysqli_fetch_assoc($res);
    
    if (!$config) {
        daanrox_log("ERRO: Configuração de afiliados não encontrada.", 'ERRO');
    } else {
        daanrox_log("Configuração encontrada: " . json_encode($config), 'AFILIACAO');
    }
    
    return $config;
}

/**
 * Busca a hierarquia de afiliados de um usuário (até 3 níveis)
 */
function getHierarquiaAfiliados($user_id) {
    global $mysqli;
    
    daanrox_log("getHierarquiaAfiliados() chamado: User=$user_id", 'AFILIACAO');
    
    $hierarquia = [
        'nivel1' => null,
        'nivel2' => null,
        'nivel3' => null
    ];

    // Busca invitation_code do usuário
    $qry = "SELECT invitation_code FROM usuarios WHERE id = ?";
    $stmt = $mysqli->prepare($qry);
    
    if (!$stmt) {
        daanrox_log("ERRO prepare invitation_code: " . $mysqli->error, 'ERRO');
        return $hierarquia;
    }
    
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    
    if (!$user || !$user['invitation_code']) {
        daanrox_log("Usuário $user_id não tem invitation_code", 'AFILIACAO');
        return $hierarquia;
    }
    
    daanrox_log("Usuário convidado por código: " . $user['invitation_code'], 'AFILIACAO');

    // Busca nível 1 (quem indicou diretamente)
    $qry = "SELECT id, mobile, invite_code, invitation_code FROM usuarios WHERE invite_code = ?";
    $stmt = $mysqli->prepare($qry);
    
    if (!$stmt) {
        daanrox_log("ERRO prepare nivel1: " . $mysqli->error, 'ERRO');
        return $hierarquia;
    }
    
    $stmt->bind_param("s", $user['invitation_code']);
    $stmt->execute();
    $result = $stmt->get_result();
    $nivel1 = $result->fetch_assoc();
    $stmt->close();
    
    if ($nivel1) {
        $hierarquia['nivel1'] = $nivel1;
        daanrox_log("Nível 1 encontrado: ID " . $nivel1['id'], 'AFILIACAO');

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
                    daanrox_log("Nível 2 encontrado: ID " . $nivel2['id'], 'AFILIACAO');
                    
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
                                daanrox_log("Nível 3 encontrado: ID " . $nivel3['id'], 'AFILIACAO');
                            }
                        }
                    }
                }
            }
        }
    } else {
        daanrox_log("Nenhum usuário encontrado com o código " . $user['invitation_code'], 'AFILIACAO');
    }
    
    daanrox_log("Hierarquia final: " . json_encode($hierarquia), 'AFILIACAO');
    return $hierarquia;
}

/**
 * Verifica se deve aplicar a comissão baseado na chance CPA
 */
function aplicarChanceCpa($chanceCpa) {
    if ($chanceCpa >= 100) {
        daanrox_log("Chance CPA >=100, aprovado automaticamente", 'AFILIACAO');
        return true;
    }
    
    $random = mt_rand(1, 100);
    $aprovado = $random <= $chanceCpa;
    daanrox_log("Sorteio: random=$random, chance=$chanceCpa, resultado=" . ($aprovado ? 'APROVADO' : 'REPROVADO'), 'AFILIACAO');
    return $aprovado;
}

/**
 * Credita comissão no saldo de afiliados
 */
function creditarComissaoAfiliado($user_id, $valor, $nivel, $depositante_id, $valor_deposito, $porcentagem) {
    global $mysqli;
    
    daanrox_log("creditarComissaoAfiliado() chamado: Afiliado=$user_id, Valor=$valor, Nivel=$nivel, %=$porcentagem", 'AFILIACAO');
    
    // Atualiza saldo_afiliados
    $qry = "UPDATE usuarios SET saldo_afiliados = saldo_afiliados + ? WHERE id = ?";
    $stmt = $mysqli->prepare($qry);
    
    if (!$stmt) {
        daanrox_log("ERRO prepare update saldo_afiliados: " . $mysqli->error, 'ERRO');
        return false;
    }
    
    $stmt->bind_param("di", $valor, $user_id);
    
    if (!$stmt->execute()) {
        daanrox_log("ERRO execute update saldo_afiliados: " . $stmt->error, 'ERRO');
        $stmt->close();
        return false;
    }
    
    daanrox_log("Update saldo_afiliados realizado com sucesso", 'AFILIACAO');
    $stmt->close();

    // Registra em adicao_saldo
    $data_registro = date('Y-m-d H:i:s');
    $tipo = "comissao_cpa_nivel_{$nivel}";
    $valor_centavos = intval($valor * 100);
    
    $qry = "INSERT INTO adicao_saldo (id_user, valor, tipo, data_registro) VALUES (?, ?, ?, ?)";
    $stmt = $mysqli->prepare($qry);
    
    if (!$stmt) {
        daanrox_log("ERRO prepare insert adicao_saldo: " . $mysqli->error, 'ERRO');
        return false;
    }
    
    $stmt->bind_param("iiss", $user_id, $valor_centavos, $tipo, $data_registro);
    
    if (!$stmt->execute()) {
        daanrox_log("ERRO execute insert adicao_saldo: " . $stmt->error, 'ERRO');
        $stmt->close();
        return false;
    }
    
    daanrox_log("Insert adicao_saldo realizado com sucesso", 'AFILIACAO');
    $stmt->close();
    
    daanrox_log("SUCESSO: Comissão creditada para afiliado $user_id", 'AFILIACAO');
    return true;
}

/**
 * Processa as comissões CPA para um depósito
 */
function processarCpa($user_id, $valor_deposito) {
    global $mysqli;

    daanrox_log("processarCpa() iniciado: User=$user_id, Deposito=$valor_deposito", 'AFILIACAO');

    // Busca configuração
    $config = getAfiliadosConfig();
    if (!$config) {
        daanrox_log("ABORTANDO: Configuração inválida", 'ERRO');
        return false;
    }
    
    // Verifica valor mínimo
    if ($valor_deposito < $config['minDepForCpa']) {
        daanrox_log("ABORTANDO: Valor $valor_deposito < mínimo {$config['minDepForCpa']}", 'AFILIACAO');
        return false;
    }
    
    // Verifica chance CPA
    if (!aplicarChanceCpa($config['chanceCpa'])) {
        daanrox_log("ABORTANDO: Reprovado na chance CPA", 'AFILIACAO');
        return false;
    }

    // Busca hierarquia
    $hierarquia = getHierarquiaAfiliados($user_id);
    $comissoes_processadas = 0;

    // Função para buscar porcentagem do afiliado
    $getPorcentagem = function($afiliado_id, $nivel, $global_valor) use ($mysqli) {
        daanrox_log("Buscando porcentagem para afiliado=$afiliado_id, nivel=$nivel, global=$global_valor", 'AFILIACAO');
        
        $coluna = "cpaLvl{$nivel}";
        $qry = "SELECT {$coluna} FROM usuarios WHERE id = ?";
        $stmt = $mysqli->prepare($qry);
        
        if (!$stmt) {
            daanrox_log("ERRO prepare getPorcentagem: " . $mysqli->error, 'ERRO');
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
            daanrox_log("Usando porcentagem personalizada: $valor_final%", 'AFILIACAO');
        } else {
            daanrox_log("Usando porcentagem global: $valor_final%", 'AFILIACAO');
        }
        
        return $valor_final;
    };

    // Processa nível 1
    if ($hierarquia['nivel1']) {
        daanrox_log("Processando Nível 1: " . json_encode($hierarquia['nivel1']), 'AFILIACAO');
        
        $porcentagem_nivel1 = $getPorcentagem($hierarquia['nivel1']['id'], 1, $config['cpaLvl1']);
        
        if ($porcentagem_nivel1 > 0) {
            $comissao_nivel1 = ($valor_deposito * $porcentagem_nivel1) / 100;
            daanrox_log("Nível 1: comissão calculada = $comissao_nivel1", 'AFILIACAO');
            
            if (creditarComissaoAfiliado(
                $hierarquia['nivel1']['id'], 
                $comissao_nivel1, 
                1, 
                $user_id,
                $valor_deposito,
                $porcentagem_nivel1
            )) {
                $comissoes_processadas++;
                daanrox_log("Nível 1: comissão creditada com sucesso", 'AFILIACAO');
            } else {
                daanrox_log("Nível 1: falha ao creditar comissão", 'ERRO');
            }
        } else {
            daanrox_log("Nível 1: porcentagem zerada, pulando", 'AFILIACAO');
        }

        // Processa nível 2 (se existir)
        if ($hierarquia['nivel2']) {
            daanrox_log("Processando Nível 2: " . json_encode($hierarquia['nivel2']), 'AFILIACAO');
            
            $porcentagem_nivel2 = $getPorcentagem($hierarquia['nivel2']['id'], 2, $config['cpaLvl2']);
            
            if ($porcentagem_nivel2 > 0) {
                $comissao_nivel2 = ($valor_deposito * $porcentagem_nivel2) / 100;
                daanrox_log("Nível 2: comissão calculada = $comissao_nivel2", 'AFILIACAO');
                
                if (creditarComissaoAfiliado(
                    $hierarquia['nivel2']['id'], 
                    $comissao_nivel2, 
                    2, 
                    $user_id,
                    $valor_deposito,
                    $porcentagem_nivel2
                )) {
                    $comissoes_processadas++;
                    daanrox_log("Nível 2: comissão creditada com sucesso", 'AFILIACAO');
                } else {
                    daanrox_log("Nível 2: falha ao creditar comissão", 'ERRO');
                }
            } else {
                daanrox_log("Nível 2: porcentagem zerada, pulando", 'AFILIACAO');
            }

            // Processa nível 3 (se existir)
            if ($hierarquia['nivel3']) {
                daanrox_log("Processando Nível 3: " . json_encode($hierarquia['nivel3']), 'AFILIACAO');
                
                $porcentagem_nivel3 = $getPorcentagem($hierarquia['nivel3']['id'], 3, $config['cpaLvl3']);
                
                if ($porcentagem_nivel3 > 0) {
                    $comissao_nivel3 = ($valor_deposito * $porcentagem_nivel3) / 100;
                    daanrox_log("Nível 3: comissão calculada = $comissao_nivel3", 'AFILIACAO');
                    
                    if (creditarComissaoAfiliado(
                        $hierarquia['nivel3']['id'], 
                        $comissao_nivel3, 
                        3, 
                        $user_id,
                        $valor_deposito,
                        $porcentagem_nivel3
                    )) {
                        $comissoes_processadas++;
                        daanrox_log("Nível 3: comissão creditada com sucesso", 'AFILIACAO');
                    } else {
                        daanrox_log("Nível 3: falha ao creditar comissão", 'ERRO');
                    }
                } else {
                    daanrox_log("Nível 3: porcentagem zerada, pulando", 'AFILIACAO');
                }
            }
        }
    } else {
        daanrox_log("Nenhum afiliado de nível 1 encontrado", 'AFILIACAO');
    }

    $resultado = $comissoes_processadas > 0;
    daanrox_log("processarCpa() finalizado. Comissões processadas: $comissoes_processadas. Resultado: " . ($resultado ? 'TRUE' : 'FALSE'), 'AFILIACAO');
    return $resultado;
}

function processarTodasComissoes($user_id, $valor_deposito) {
    global $mysqli;
    
    daanrox_log("processarTodasComissoes() chamado: User=$user_id, Valor=$valor_deposito", 'AFILIACAO');
    
    // Verificar se é o primeiro depósito PAGO
    $sqlCount = "SELECT COUNT(*) as total FROM transacoes WHERE usuario = ? AND tipo = 'deposito' AND status = 'pago'";
    $stmtCount = $mysqli->prepare($sqlCount);
    
    if (!$stmtCount) {
        daanrox_log("ERRO prepare count depósitos: " . $mysqli->error, 'ERRO');
        return false;
    }
    
    $stmtCount->bind_param("i", $user_id);
    $stmtCount->execute();
    $resCount = $stmtCount->get_result();
    $rowCount = $resCount->fetch_assoc();
    $totalPaid = $rowCount['total'];
    $stmtCount->close();
    
    daanrox_log("Total de depósitos pagos do usuário: $totalPaid", 'AFILIACAO');
    
   

    $cpa_processado = processarCpa($user_id, $valor_deposito);
    
    daanrox_log("processarTodasComissoes() finalizado. Retorno: " . ($cpa_processado ? 'TRUE' : 'FALSE'), 'AFILIACAO');
    return $cpa_processado ? true : false;
}

?>