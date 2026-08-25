<?php
if (file_exists('../../vercel_session.php')) {
    require_once '../../vercel_session.php';
}
session_start();

include __DIR__ . '/../services/database.php';
include __DIR__ . '/../services/crud.php';
include_once __DIR__ . '/../services/funcao.php';

if (!isset($_SESSION['token_adm_encrypted']) || !isset($_SESSION["crsf_token_adm"]) || !isset($_SESSION["anti_crsf_token_adm"])) {
    echo json_encode(['success' => false, 'message' => 'Sessão expirada. Faça login novamente.']);
    exit;
}

if (isset($_SESSION['token_adm_encrypted'])) {
    $view_id_user_decrypted = CRIPT_AES('decrypt', $_SESSION['token_adm_encrypted']);
    $safe_id = mysqli_real_escape_string($mysqli, $view_id_user_decrypted);
    $adminQuery = "SELECT * FROM admin_users WHERE id = '$safe_id' AND status = 1 LIMIT 1";
    $adminResult = mysqli_query($mysqli, $adminQuery);
    if (!$adminResult || mysqli_num_rows($adminResult) === 0) {
        echo json_encode(['success' => false, 'message' => 'Usuário admin bloqueado ou inexistente.']);
        exit;
    }
    $_SESSION['data_adm'] = mysqli_fetch_assoc($adminResult);
}

if (!isset($_SESSION['data_adm']) || empty($_SESSION['data_adm']['id'])) {
    echo json_encode(['success' => false, 'message' => 'Acesso não autorizado.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método inválido de requisição.']);
    exit;
}

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

if (!$data || !isset($data['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Dados inválidos fornecidos.']);
    exit;
}

$user_id = intval($data['user_id']);
$adicionar = floatval($data['adicionar'] ?? 0);
$remover = floatval($data['remover'] ?? 0);

$query = "SELECT saldo FROM usuarios WHERE id = ?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$resposta = $stmt->get_result();
$usuario = $resposta->fetch_assoc();

if (!$usuario) {
    echo json_encode(['success' => false, 'message' => 'Usuário não encontrado.']);
    exit;
}

$saldoAtual = floatval($usuario['saldo']);

if ($adicionar > 0) {
    $novoSaldo = $saldoAtual + $adicionar;
    $tipo = 'adicao';
    $valorLog = $adicionar;
} elseif ($remover > 0) {
    if ($saldoAtual < $remover) {
        echo json_encode(['success' => false, 'message' => 'Saldo insuficiente.']);
        exit;
    }
    $novoSaldo = $saldoAtual - $remover;
    $tipo = 'remocao';
    $valorLog = $remover;
} else {
    echo json_encode(['success' => false, 'message' => 'Valor inválido para adição ou remoção de saldo.']);
    exit;
}

$mysqli->begin_transaction();

try {
    $query = "UPDATE usuarios SET saldo = ? WHERE id = ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("di", $novoSaldo, $user_id);
    if (!$stmt->execute()) {
        throw new Exception('Erro ao atualizar o saldo.');
    }

    $data_time = date('Y-m-d H:i:s');
    $logQuery = "INSERT INTO adicao_saldo (id_user, valor, tipo, data_registro) VALUES (?, ?, ?, ?)";
    $stmtLog = $mysqli->prepare($logQuery);
    $stmtLog->bind_param("idss", $user_id, $valorLog, $tipo, $data_time);
    if (!$stmtLog->execute()) {
        throw new Exception('Erro ao registrar log de saldo.');
    }

    $mysqli->commit();
    echo json_encode(['success' => true, 'message' => 'Saldo atualizado com sucesso.']);
} catch (Exception $e) {
    $mysqli->rollback();
    // Adiciona o erro nativo do mysql se houver
    $mysql_err = isset($stmtLog) ? $stmtLog->error : (isset($stmt) ? $stmt->error : $mysqli->error);
    $extra = $mysql_err ? " (Detalhes: " . $mysql_err . ")" : "";
    echo json_encode(['success' => false, 'message' => $e->getMessage() . $extra]);
}

$mysqli->close();
