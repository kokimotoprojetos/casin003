<?php
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../admin/services/database.php';
require_once __DIR__ . '/../admin/services/env_loader.php';

function getAdminSession() {
    return isset($_SESSION['gozei_admin']) && $_SESSION['gozei_admin'] === true;
}

function jsonResponse($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

// Handle Vercel rewrite: /gozei/api.php?route=login -> route=login
$route = $_GET['route'] ?? '';
$uri = '/gozei/api/' . $route;

// Also handle direct access
if (!$route) {
    $fullUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $uri = rtrim($fullUri, '/');
}

// LOGIN
if ($uri === '/gozei/api/login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $user = $input['username'] ?? '';
    $pass = $input['password'] ?? '';
    
    $adminUser = getenv('GOZEI_ADMIN_USER') ?: 'admin';
    $adminPass = getenv('GOZEI_ADMIN_PASS') ?: 'admin123';
    
    if ($user === $adminUser && $pass === $adminPass) {
        $_SESSION['gozei_admin'] = true;
        jsonResponse(['success' => true]);
    }
    jsonResponse(['error' => 'Credenciais invalidas'], 401);
}

if ($uri === '/gozei/api/login' && $_SERVER['REQUEST_METHOD'] === 'DELETE') {
    unset($_SESSION['gozei_admin']);
    jsonResponse(['success' => true]);
}

if (!getAdminSession()) {
    jsonResponse(['error' => 'Unauthorized'], 401);
}

// DATA
if ($uri === '/gozei/api/data' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    global $mysqli;
    $search = $_GET['search'] ?? '';
    
    $stats = [];
    $r = $mysqli->query("SELECT 
        (SELECT COUNT(*) FROM usuarios) as totalUsers,
        (SELECT COUNT(*) FROM usuarios WHERE DATE(created_at) = CURDATE()) as todayUsers,
        (SELECT COUNT(*) FROM usuarios WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)) as users90d,
        (SELECT IFNULL(SUM(saldo), 0) FROM usuarios) as totalBalance,
        (SELECT IFNULL(SUM(valor), 0) FROM transacoes WHERE status = 'pago') as totalDeposits,
        (SELECT IFNULL(SUM(valor), 0) FROM solicitacao_saques WHERE status = '1') as totalWithdrawalsApproved,
        (SELECT IFNULL(SUM(valor), 0) FROM solicitacao_saques WHERE status = '0') as pendingWithdrawals,
        (SELECT IFNULL(SUM(valor), 0) FROM transacoes WHERE status = 'pago' AND DATE(data_registro) = CURDATE()) as todayDeposits,
        (SELECT IFNULL(SUM(valor), 0) FROM solicitacao_saques WHERE status = '1' AND DATE(data_registro) = CURDATE()) as todayWithdrawals
    ");
    if ($r) $stats = $r->fetch_assoc();
    
    $users = [];
    $sql = "SELECT id, mobile as phone, saldo as balance, nome as full_name, status, created_at FROM usuarios";
    if ($search) {
        $s = $mysqli->real_escape_string($search);
        $sql .= " WHERE id LIKE '%$s%' OR mobile LIKE '%$s%' OR nome LIKE '%$s%'";
    }
    $sql .= " ORDER BY id DESC LIMIT 500";
    $r = $mysqli->query($sql);
    if ($r) while ($row = $r->fetch_assoc()) $users[] = $row;
    
    jsonResponse(['success' => true, 'users' => $users, 'stats' => $stats]);
}

// BALANCE
if ($uri === '/gozei/api/balance' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    global $mysqli;
    $input = json_decode(file_get_contents('php://input'), true);
    $userId = intval($input['userId'] ?? 0);
    $amount = floatval($input['amount'] ?? 0);
    $action = ($input['action'] ?? 'add') === 'remove' ? 'remove' : 'add';
    
    if ($userId <= 0) jsonResponse(['error' => 'ID invalido'], 400);
    if ($amount <= 0) jsonResponse(['error' => 'Valor invalido'], 400);
    
    $mysqli->begin_transaction();
    try {
        $r = $mysqli->query("SELECT id, saldo FROM usuarios WHERE id = $userId FOR UPDATE");
        if (!$r || $r->num_rows === 0) { $mysqli->rollback(); jsonResponse(['error' => 'Usuario nao encontrado'], 404); }
        $user = $r->fetch_assoc();
        
        if ($action === 'remove' && $user['saldo'] < $amount) {
            $mysqli->rollback();
            jsonResponse(['error' => 'Saldo insuficiente'], 400);
        }
        
        $mod = $action === 'add' ? $amount : -$amount;
        $mysqli->query("UPDATE usuarios SET saldo = saldo + $mod WHERE id = $userId");
        $tipo = $action === 'add' ? 'adicao' : 'remocao';
        $abs = abs($mod);
        $mysqli->query("INSERT INTO adicao_saldo (id_usuario, valor, tipo, data_registro) VALUES ($userId, $abs, '$tipo', NOW())");
        
        $mysqli->commit();
        jsonResponse(['success' => true]);
    } catch (Exception $e) {
        $mysqli->rollback();
        jsonResponse(['error' => 'Erro interno'], 500);
    }
}

// WITHDRAWALS
if ($uri === '/gozei/api/withdrawals' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    global $mysqli;
    $status = $_GET['status'] ?? 'all';
    $sql = "SELECT s.id, s.id_user, s.valor, s.status, s.tipo_saque, s.chave_pix, s.tipo_chave_pix, s.data_registro,
                   u.mobile as user_phone, u.nome as user_name
            FROM solicitacao_saques s LEFT JOIN usuarios u ON s.id_user = u.id";
    if ($status === 'pending') $sql .= " WHERE s.status = '0'";
    elseif ($status === 'approved') $sql .= " WHERE s.status = '1'";
    elseif ($status === 'refused') $sql .= " WHERE s.status = '2'";
    $sql .= " ORDER BY s.id DESC LIMIT 200";
    
    $withdrawals = [];
    $r = $mysqli->query($sql);
    if ($r) while ($row = $r->fetch_assoc()) $withdrawals[] = $row;
    jsonResponse(['success' => true, 'withdrawals' => $withdrawals]);
}

if ($uri === '/gozei/api/withdrawals' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    global $mysqli;
    $input = json_decode(file_get_contents('php://input'), true);
    $wid = intval($input['withdrawalId'] ?? 0);
    $action = $input['action'] ?? '';
    
    if ($wid <= 0 || !in_array($action, ['approve', 'reject'])) jsonResponse(['error' => 'Parametros invalidos'], 400);
    
    $mysqli->begin_transaction();
    try {
        $r = $mysqli->query("SELECT * FROM solicitacao_saques WHERE id = $wid FOR UPDATE");
        if (!$r || $r->num_rows === 0) { $mysqli->rollback(); jsonResponse(['error' => 'Nao encontrado'], 404); }
        $w = $r->fetch_assoc();
        if ($w['status'] !== '0') { $mysqli->rollback(); jsonResponse(['error' => 'Estado invalido'], 400); }
        
        if ($action === 'approve') {
            $mysqli->query("UPDATE solicitacao_saques SET status = '1' WHERE id = $wid");
        } else {
            $mysqli->query("UPDATE usuarios SET saldo = saldo + {$w['valor']} WHERE id = {$w['id_user']}");
            $mysqli->query("UPDATE solicitacao_saques SET status = '2' WHERE id = $wid");
        }
        $mysqli->commit();
        jsonResponse(['success' => true]);
    } catch (Exception $e) {
        $mysqli->rollback();
        jsonResponse(['error' => 'Erro interno'], 500);
    }
}

// DEPOSITS
if ($uri === '/gozei/api/deposits' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    global $mysqli;
    $status = $_GET['status'] ?? 'all';
    $sql = "SELECT id, usuario as user_phone, valor as amount, status, data_registro as created_at, qr_code, txid FROM transacoes";
    if ($status === 'pending') $sql .= " WHERE status = 'processamento'";
    elseif ($status === 'paid') $sql .= " WHERE status = 'pago'";
    elseif ($status === 'expired') $sql .= " WHERE status = 'expirado'";
    $sql .= " ORDER BY id DESC LIMIT 200";
    
    $deposits = [];
    $r = $mysqli->query($sql);
    if ($r) while ($row = $r->fetch_assoc()) $deposits[] = $row;
    jsonResponse(['success' => true, 'deposits' => $deposits]);
}

// REJECT DEPOSIT
if ($uri === '/gozei/api/reject-deposit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    global $mysqli;
    $input = json_decode(file_get_contents('php://input'), true);
    $tid = intval($input['transactionId'] ?? 0);
    if ($tid <= 0) jsonResponse(['error' => 'ID invalido'], 400);
    
    $mysqli->begin_transaction();
    try {
        $r = $mysqli->query("SELECT id, status FROM transacoes WHERE id = $tid FOR UPDATE");
        if (!$r || $r->num_rows === 0) { $mysqli->rollback(); jsonResponse(['error' => 'Nao encontrado'], 404); }
        $tx = $r->fetch_assoc();
        if ($tx['status'] !== 'processamento') { $mysqli->rollback(); jsonResponse(['error' => 'Estado invalido'], 400); }
        $mysqli->query("UPDATE transacoes SET status = 'expirado' WHERE id = $tid");
        $mysqli->commit();
        jsonResponse(['success' => true]);
    } catch (Exception $e) {
        $mysqli->rollback();
        jsonResponse(['error' => 'Erro interno'], 500);
    }
}

// USER DETAIL
if (preg_match('#^/gozei/api/user/(\d+)$#', $uri, $m) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    global $mysqli;
    $uid = intval($m[1]);
    if ($uid <= 0) jsonResponse(['error' => 'ID invalido'], 400);
    
    $r = $mysqli->query("SELECT id, mobile as phone, saldo as balance, nome as full_name, status, created_at FROM usuarios WHERE id = $uid");
    if (!$r || $r->num_rows === 0) jsonResponse(['error' => 'Nao encontrado'], 404);
    $user = $r->fetch_assoc();
    
    $txs = [];
    $r = $mysqli->query("SELECT id, valor as amount, status, tipo as type, data_registro as created_at FROM transacoes WHERE usuario = $uid ORDER BY id DESC LIMIT 100");
    if ($r) while ($row = $r->fetch_assoc()) $txs[] = $row;
    
    $ws = [];
    $r = $mysqli->query("SELECT id, valor as amount, status, data_registro as created_at FROM solicitacao_saques WHERE id_user = $uid ORDER BY id DESC LIMIT 50");
    if ($r) while ($row = $r->fetch_assoc()) $ws[] = $row;
    
    jsonResponse(['user' => $user, 'transactions' => $txs, 'withdrawals' => $ws]);
}

jsonResponse(['error' => 'Not found'], 404);
?>
