<?php
session_start();
include_once __DIR__ . '/../config.php';
include_once(__DIR__ . '/../' . DASH . '/services/database.php');
include_once(__DIR__ . '/../' . DASH . '/services/funcao.php');
include_once(__DIR__ . '/../' . DASH . '/services/crud.php');
include_once(__DIR__ . '/../' . DASH . '/services/webhook.php');

global $mysqli;

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    exit;
}

function log_poseidonpay($msg, $context = []) {
    $line = '[POSEIDONPAY ' . date('Y-m-d H:i:s') . '] ' . $msg;
    if (!empty($context)) {
        $line .= ' | ' . json_encode($context, JSON_UNESCAPED_UNICODE);
    }
    $target = sys_get_temp_dir() . '/poseidonpay.log';
    @file_put_contents($target, $line . PHP_EOL, FILE_APPEND);
    error_log($line);
}

function verificarBonus($userId, $valorPago, $transacao_id = null) {
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

function registrarBonusUsado($userId, $valorPago, $bonusRecebido) {
    global $mysqli;

    if ($bonusRecebido <= 0) {
        return false;
    }

    $sql = "INSERT INTO cupom_usados (id_user, valor, bonus, data_registro) VALUES (?, ?, ?, NOW())";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("idi", $userId, $valorPago, $bonusRecebido);
    $resultado = $stmt->execute();
    $stmt->close();

    return $resultado;
}

function buscarValorIpnCashin($transacao_id) {
    global $mysqli;

    $qry = "SELECT usuario, valor FROM transacoes WHERE transacao_id = ? AND tipo = 'deposito'";
    $stmt = $mysqli->prepare($qry);

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param("s", $transacao_id);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows > 0) {
        $data = $res->fetch_assoc();
        $stmt->close();

        $usuario_id = $data['usuario'];
        $valorPago = $data['valor'];

        $qry_saldo = "SELECT saldo FROM usuarios WHERE id = ?";
        $stmt_saldo = $mysqli->prepare($qry_saldo);
        $stmt_saldo->bind_param("i", $usuario_id);
        $stmt_saldo->execute();
        $res_saldo = $stmt_saldo->get_result();
        $usuario_data = $res_saldo->fetch_assoc();
        $saldo_anterior = $usuario_data['saldo'] ?? 0;
        $stmt_saldo->close();

        $bonusRecebido = verificarBonus($usuario_id, $valorPago, $transacao_id);
        $valorTotal = $valorPago + $bonusRecebido;

        $retorna_insert_saldo = adicionarSaldoUsuario($usuario_id, $valorTotal);

        if ($retorna_insert_saldo) {
            criarAuditFlowDeposito($usuario_id, $valorPago);

            $qry_saldo2 = "SELECT saldo FROM usuarios WHERE id = ?";
            $stmt_saldo2 = $mysqli->prepare($qry_saldo2);
            $stmt_saldo2->bind_param("i", $usuario_id);
            $stmt_saldo2->execute();
            $res_saldo2 = $stmt_saldo2->get_result();
            $usuario_data2 = $res_saldo2->fetch_assoc();
            $saldo_novo = $usuario_data2['saldo'] ?? 0;
            $stmt_saldo2->close();

            if ($bonusRecebido > 0) {
                registrarBonusUsado($usuario_id, $valorPago, $bonusRecebido);
            }

            log_poseidonpay("Chamando processarTodasComissoes para User $usuario_id, Valor $valorPago");

            $comissoes_processadas = processarTodasComissoes($usuario_id, $valorPago);

            $qry_user = "SELECT real_name FROM usuarios WHERE id = ?";
            $stmt_user = $mysqli->prepare($qry_user);
            $stmt_user->bind_param("i", $usuario_id);
            $stmt_user->execute();
            $res_user = $stmt_user->get_result();
            $user_data = $res_user->fetch_assoc();
            $stmt_user->close();

            WebhookPixPagos($user_data['real_name'] ?? 'Usuário', $_SERVER['HTTP_HOST'], $valorPago);

            return true;
        } else {
            return false;
        }
    }

    $stmt->close();
    return false;
}

function attPaymentPix($transacao_id) {
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

    if (!$sql) {
        return 0;
    }

    $sql->bind_param("s", $transacao_id);

    if ($sql->execute()) {
        $linhas_afetadas = $sql->affected_rows;
        $sql->close();

        if ($linhas_afetadas > 0) {
            $buscar = buscarValorIpnCashin($transacao_id);

            if ($buscar) {
                return 1;
            } else {
                return 0;
            }
        } else {
            return 0;
        }
    } else {
        $sql->close();
        return 0;
    }
}

function attSaqueStatus($transaction_id, $status) {
    global $mysqli;

    $qry_find = "SELECT transacao_id, status, id_user, valor FROM solicitacao_saques WHERE transacao_id = ? ORDER BY data_registro DESC LIMIT 1";
    $stmt_find = $mysqli->prepare($qry_find);

    if (!$stmt_find) {
        return 0;
    }

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

    if ($status === 'completed' && $status_atual == 1) {
        return 2;
    }

    $status_db = 0;
    switch (strtolower($status)) {
        case 'completed':
        case 'complete':
        case 'success':
        case 'approved':
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

    if (!$stmt_update) {
        return 0;
    }

    $stmt_update->bind_param("is", $status_db, $transacao_id_real);

    if ($stmt_update->execute()) {
        $linhas_afetadas = $stmt_update->affected_rows;
        $stmt_update->close();

        if ($linhas_afetadas > 0) {
            if ($status_db == 1) {
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
            return 0;
        }
    } else {
        $stmt_update->close();
        return 0;
    }
}

$id_transaction = PHP_SEGURO($data['idTransaction'] ?? $data['transactionId'] ?? $data['transaction_id'] ?? $data['id'] ?? '');
$status = PHP_SEGURO(strtolower($data['status'] ?? ''));
$external_id = PHP_SEGURO($data['external_reference'] ?? $data['externalId'] ?? $data['external_id'] ?? $data['identifier'] ?? '');

log_poseidonpay("Webhook recebido", [
    'idTransaction' => $id_transaction,
    'status' => $status,
    'external_id' => $external_id,
    'raw' => $raw
]);

$valid_statuses = ['paid_out', 'paid', 'confirmed', 'success', 'completed', 'approved', 'complete', '1'];

$id_busca = '';
foreach ([$id_transaction, $external_id] as $cand) {
    if (!empty($cand)) { $id_busca = $cand; break; }
}

if (!empty($id_busca) && in_array($status, $valid_statuses)) {
    $att = attPaymentPix($id_busca);

    if ($att == 1) {
        log_poseidonpay("Pagamento processado", ['id' => $id_busca]);
        http_response_code(200);
        echo json_encode(['success' => true, 'message' => 'Pagamento processado']);
    } elseif ($att == 2) {
        log_poseidonpay("Pagamento já processado anteriormente", ['id' => $id_busca]);
        http_response_code(200);
        echo json_encode(['success' => true, 'message' => 'Já processado']);
    } else {
        log_poseidonpay("Transação não encontrada", ['id' => $id_busca]);
        http_response_code(200);
        echo json_encode(['success' => false, 'message' => 'Transação não encontrada']);
    }
} else {
    log_poseidonpay("Webhook ignorado - status: $status, id: $id_busca");
    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Recebido']);
}
