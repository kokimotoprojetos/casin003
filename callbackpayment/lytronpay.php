<?php
/**
 * CALLBACK LYTRONPAY
 * Webhook para processar notificações de depósitos e saques da LytronPay
 * Validação com HMAC-SHA256 usando o secret_hash (salvo em client_secret)
 */
 
session_start();
include_once "../config.php";
include_once('../' . DASH . '/services/database.php');
include_once('../' . DASH . '/services/funcao.php');
include_once('../' . DASH . '/services/crud.php');
include_once('../' . DASH . '/services/webhook.php');

global $mysqli;

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    exit("Invalid JSON");
}

function log_lytronpay($msg, $context = []) {
    $logFile = dirname(__DIR__) . '/errorlog.log';
    $line = '[LYTRONPAY ' . date('Y-m-d H:i:s') . '] ' . $msg;
    if (!empty($context)) {
        $line .= ' | ' . json_encode($context, JSON_UNESCAPED_UNICODE);
    }
    $line .= PHP_EOL;
    file_put_contents($logFile, $line, FILE_APPEND);
}

log_lytronpay("Webhook recebido", $data);

// Verificar assinatura HMAC-SHA256 se client_secret configurado
$res_lyt = $mysqli->query("SELECT client_secret FROM lytronpay WHERE id = 1");
$lyt_row = $res_lyt ? $res_lyt->fetch_assoc() : ['client_secret' => ''];
$secret_hash = trim($lyt_row['client_secret'] ?? '');

if (!empty($secret_hash)) {
    $received_sig = $_SERVER['HTTP_X_SIGNATURE'] ?? ($_SERVER['HTTP_X_GATEWAY_SIGNATURE'] ?? '');
    if (!empty($received_sig)) {
        $received_sig = str_replace('sha256=', '', $received_sig);
        $expected_sig = hash_hmac('sha256', $raw, $secret_hash);
        if (!hash_equals($expected_sig, $received_sig)) {
            log_lytronpay("Assinatura de webhook inválida! Esperado: $expected_sig, Recebido: $received_sig");
            http_response_code(401);
            exit("Invalid webhook signature");
        }
    }
}

function verificarBonusLytron($userId, $valorPago, $transacao_id = null) {
    global $mysqli;
    
    $sqlCount = "SELECT COUNT(*) as total FROM transacoes WHERE usuario = ? AND tipo = 'deposito' AND status = 'pago'";
    $stmtCount = $mysqli->prepare($sqlCount);
    $stmtCount->bind_param("i", $userId);
    $stmtCount->execute();
    $resCount = $stmtCount->get_result();
    $rowCount = $resCount->fetch_assoc();
    $totalPaid = $rowCount['total'];
    $stmtCount->close();
    
    if ($totalPaid > 1) {
        return 0;
    }

    $tagValue = 0;
    $payTypeId = 0;

    if ($transacao_id) {
        $sqlTrans = "SELECT pay_type_sub_list_id, join_bonus FROM transacoes WHERE transacao_id = ?";
        $stmtTrans = $mysqli->prepare($sqlTrans);
        $stmtTrans->bind_param("s", $transacao_id);
        $stmtTrans->execute();
        $resTrans = $stmtTrans->get_result();
        if ($rowTrans = $resTrans->fetch_assoc()) {
            $payTypeId = $rowTrans['pay_type_sub_list_id'];
            if (isset($rowTrans['join_bonus']) && $rowTrans['join_bonus'] != 1) {
                return 0;
            }
        }
        $stmtTrans->close();
    }

    if ($payTypeId) {
        $sqlPay = "SELECT tag_value, bonus_active FROM pay_type_sub_list WHERE id = ?";
        $stmtPay = $mysqli->prepare($sqlPay);
        $stmtPay->bind_param("i", $payTypeId);
        $stmtPay->execute();
        $resPay = $stmtPay->get_result();
        if ($rowPay = $resPay->fetch_assoc()) {
            if ($rowPay['bonus_active'] == 1) {
                $percentage = floatval($rowPay['tag_value']);
                $tagValue = ($valorPago * $percentage) / 100;
            }
        }
        $stmtPay->close();
    } else {
        $sqlPay = "SELECT tag_value FROM pay_type_sub_list WHERE status = 1 AND bonus_active = 1 AND ? >= min_amount AND ? <= max_amount ORDER BY id DESC LIMIT 1";
        $stmtPay = $mysqli->prepare($sqlPay);
        $stmtPay->bind_param("dd", $valorPago, $valorPago);
        $stmtPay->execute();
        $resPay = $stmtPay->get_result();
        if ($rowPay = $resPay->fetch_assoc()) {
            $percentage = floatval($rowPay['tag_value']);
            $tagValue = ($valorPago * $percentage) / 100;
        }
        $stmtPay->close();
    }
    
    return $tagValue;
}

function registrarBonusUsadoLytron($userId, $valorPago, $bonusRecebido) {
    global $mysqli;
    if ($bonusRecebido <= 0) return false;
    $sql = "INSERT INTO cupom_usados (id_user, valor, bonus, data_registro) VALUES (?, ?, ?, NOW())";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("idi", $userId, $valorPago, $bonusRecebido);
    $resultado = $stmt->execute();
    $stmt->close();
    return $resultado;
}

function buscarValorIpnCashinLytron($transacao_id) {
    global $mysqli;
    $qry = "SELECT usuario, valor FROM transacoes WHERE transacao_id = ? AND tipo = 'deposito'";
    $stmt = $mysqli->prepare($qry);
    if (!$stmt) return false;
    $stmt->bind_param("s", $transacao_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows > 0) {
        $data = $res->fetch_assoc();
        $stmt->close();
        
        $usuario_id = $data['usuario'];
        $valorPago = $data['valor'];
        $bonusRecebido = verificarBonusLytron($usuario_id, $valorPago, $transacao_id);
        $valorTotal = $valorPago + $bonusRecebido;
        
        $retorna_insert_saldo = adicionarSaldoUsuario($usuario_id, $valorTotal);
        if ($retorna_insert_saldo) {
            criarAuditFlowDeposito($usuario_id, $valorPago);
            if ($bonusRecebido > 0) {
                registrarBonusUsadoLytron($usuario_id, $valorPago, $bonusRecebido);
            }
            if (function_exists('processarTodasComissoes')) {
                processarTodasComissoes($usuario_id, $valorPago);
            }
            $qry_user = "SELECT real_name FROM usuarios WHERE id = ?";
            $stmt_user = $mysqli->prepare($qry_user);
            $stmt_user->bind_param("i", $usuario_id);
            $stmt_user->execute();
            $res_user = $stmt_user->get_result();
            $user_data = $res_user->fetch_assoc();
            $stmt_user->close();

            if (function_exists('WebhookPixPagos')) {
                WebhookPixPagos($user_data['real_name'] ?? 'Usuário', $_SERVER['HTTP_HOST'], $valorPago);
            }
            return true;
        }
    } else {
        $stmt->close();
    }
    return false;
}

function attPaymentPixLytron($transacao_id) {
    global $mysqli;
    $qry_check = "SELECT status FROM transacoes WHERE transacao_id = ? AND tipo = 'deposito'";
    $stmt_check = $mysqli->prepare($qry_check);
    if ($stmt_check) {
        $stmt_check->bind_param("s", $transacao_id);
        $stmt_check->execute();
        $res_check = $stmt_check->get_result();
        if ($res_check->num_rows > 0) {
            $trans = $res_check->fetch_assoc();
            if ($trans['status'] == 'pago') {
                $stmt_check->close();
                return 2;
            }
        } else {
            $stmt_check->close();
            return 0;
        }
        $stmt_check->close();
    }
    
    $sql = $mysqli->prepare("UPDATE transacoes SET status = 'pago' WHERE transacao_id = ? AND tipo = 'deposito'");
    if (!$sql) return 0;
    $sql->bind_param("s", $transacao_id);
    if ($sql->execute()) {
        $linhas_afetadas = $sql->affected_rows;
        $sql->close();
        if ($linhas_afetadas > 0) {
            if (buscarValorIpnCashinLytron($transacao_id)) return 1;
            else return 0;
        }
    } else {
        $sql->close();
    }
    return 0;
}

function attSaqueStatusLytron($transaction_id, $status) {
    global $mysqli;
    $qry_find = "SELECT transacao_id, status, id_user, valor FROM solicitacao_saques WHERE transacao_id = ? ORDER BY data_registro DESC LIMIT 1";
    $stmt_find = $mysqli->prepare($qry_find);
    if (!$stmt_find) return 0;
    $stmt_find->bind_param("s", $transaction_id);
    $stmt_find->execute();
    $res_find = $stmt_find->get_result();
    if ($res_find->num_rows === 0) {
        $stmt_find->close();
        return 3;
    }
    $saque = $res_find->fetch_assoc();
    $transacao_id_real = $saque['transacao_id'];
    $status_atual = $saque['status'];
    $usuario_id = $saque['id_user'];
    $valor_saque = $saque['valor'];
    $stmt_find->close();
    
    if ($status_atual == 1 && in_array(strtolower($status), ['completed', 'complete', 'success', 'approved', 'paid'])) {
        return 2;
    }
    
    $status_db = 0;
    switch (strtolower($status)) {
        case 'completed':
        case 'complete':
        case 'success':
        case 'approved':
        case 'paid':
            $status_db = 1;
            break;
        case 'failed':
        case 'rejected':
        case 'cancelled':
        case 'error':
            $status_db = 2;
            break;
        default:
            $status_db = 0;
            break;
    }
    
    $sql_update = "UPDATE solicitacao_saques SET status = ?, data_att = NOW() WHERE transacao_id = ?";
    $stmt_update = $mysqli->prepare($sql_update);
    if (!$stmt_update) return 0;
    $stmt_update->bind_param("is", $status_db, $transacao_id_real);
    if ($stmt_update->execute()) {
        $linhas = $stmt_update->affected_rows;
        $stmt_update->close();
        if ($linhas > 0 && $status_db == 1 && function_exists('WebhookSaquesPagos')) {
            $qry_user = "SELECT nome FROM usuarios WHERE id = ?";
            $stmt_user = $mysqli->prepare($qry_user);
            $stmt_user->bind_param("i", $usuario_id);
            $stmt_user->execute();
            $res_user = $stmt_user->get_result();
            $user_data = $res_user->fetch_assoc();
            $stmt_user->close();
            WebhookSaquesPagos($user_data['nome'] ?? 'Usuário', $_SERVER['HTTP_HOST'], $valor_saque);
        }
        return 1;
    } else {
        $stmt_update->close();
        return 0;
    }
}

$event = strtolower(PHP_SEGURO($data['event'] ?? ($_SERVER['HTTP_X_GATEWAY_EVENT'] ?? '')));
$transaction_id = PHP_SEGURO($data['txid'] ?? ($data['reference'] ?? ($data['id'] ?? ($data['payoutId'] ?? ''))));
$status = strtolower(PHP_SEGURO($data['status'] ?? ''));

$valid_success = ['confirmed', 'paid', 'success', 'completed', 'approved', 'complete'];

if (!empty($transaction_id)) {
    if (strpos($event, 'charge') !== false || strpos($event, 'deposit') !== false || in_array($status, $valid_success)) {
        $res = attPaymentPixLytron($transaction_id);
        if ($res == 1 || $res == 2) {
            log_lytronpay("Sucesso processamento charge/depósito: $transaction_id ($res)");
            http_response_code(200);
            echo json_encode(['success' => true, 'message' => 'Processed charge']);
            exit;
        } else {
            $resSaque = attSaqueStatusLytron($transaction_id, $status);
            if ($resSaque == 1 || $resSaque == 2) {
                log_lytronpay("Sucesso processamento payout/saque: $transaction_id ($resSaque)");
                http_response_code(200);
                echo json_encode(['success' => true, 'message' => 'Processed payout']);
                exit;
            }
        }
    } elseif (strpos($event, 'payout') !== false || in_array($status, ['failed', 'rejected', 'cancelled', 'error'])) {
        attSaqueStatusLytron($transaction_id, $status);
        http_response_code(200);
        echo json_encode(['success' => true, 'message' => 'Updated payout status']);
        exit;
    }
}

http_response_code(200);
echo json_encode(['success' => true, 'message' => 'Webhook received and logged']);
?>
