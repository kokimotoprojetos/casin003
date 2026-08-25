<?php
function PHP_SEGURO($variable) {
    if (is_array($variable)) {
        return array_map('PHP_SEGURO', $variable);
    }
    return htmlspecialchars(trim(strip_tags($variable)), ENT_QUOTES, 'UTF-8');
}

function Reais2($valor) {
    return number_format((float)$valor, 2, ',', '.');
}

function ver_data($data) {
    if (empty($data)) return '-';
    return date('d/m/Y H:i', strtotime($data));
}

function data_user_id($id_user) {
    global $mysqli;
    if (!$mysqli || $mysqli->connect_errno) return null;
    $stmt = $mysqli->prepare("SELECT mobile FROM usuarios WHERE id = ?");
    if (!$stmt) return null;
    $stmt->bind_param("i", $id_user);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

function qtd_usuarios() {
    global $mysqli;
    if (!$mysqli || $mysqli->connect_errno) return 0;
    $r = $mysqli->query("SELECT COUNT(*) as total FROM usuarios");
    if (!$r) return 0;
    $row = $r->fetch_assoc();
    return $row['total'] ?? 0;
}

function qtd_usuarios_diarios() {
    global $mysqli;
    if (!$mysqli || $mysqli->connect_errno) return 0;
    $r = $mysqli->query("SELECT COUNT(*) as total FROM usuarios WHERE DATE(created_at) = CURDATE()");
    if (!$r) return 0;
    $row = $r->fetch_assoc();
    return $row['total'] ?? 0;
}

function qtd_usuarios_90d() {
    global $mysqli;
    if (!$mysqli || $mysqli->connect_errno) return 0;
    $r = $mysqli->query("SELECT COUNT(*) as total FROM usuarios WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)");
    if (!$r) return 0;
    $row = $r->fetch_assoc();
    return $row['total'] ?? 0;
}

function total_saldos_usuarios() {
    global $mysqli;
    if (!$mysqli || $mysqli->connect_errno) return 0;
    $r = $mysqli->query("SELECT IFNULL(SUM(saldo), 0) as total FROM usuarios");
    if (!$r) return 0;
    $row = $r->fetch_assoc();
    return $row['total'] ?? 0;
}

function saldo_cassino() {
    global $mysqli;
    if (!$mysqli || $mysqli->connect_errno) return 0;
    $r = $mysqli->query("SELECT IFNULL(SUM(valor), 0) as total FROM transacoes WHERE status = 'pago'");
    if (!$r) return 0;
    $row = $r->fetch_assoc();
    $depositos = $row['total'] ?? 0;
    $r2 = $mysqli->query("SELECT IFNULL(SUM(valor), 0) as total FROM solicitacao_saques WHERE status = '1'");
    if (!$r2) return $depositos;
    $row2 = $r2->fetch_assoc();
    $saques = $row2['total'] ?? 0;
    return $depositos - $saques;
}

function depositos_totalsemlink() {
    global $mysqli;
    if (!$mysqli || $mysqli->connect_errno) return 0;
    $r = $mysqli->query("SELECT IFNULL(SUM(valor), 0) as total FROM transacoes WHERE status = 'pago' AND tipo = 'deposito_pix'");
    if (!$r) return 0;
    $row = $r->fetch_assoc();
    return $row['total'] ?? 0;
}

function depositos_blogueiros() {
    global $mysqli;
    if (!$mysqli || $mysqli->connect_errno) return 0;
    $r = $mysqli->query("SELECT IFNULL(SUM(valor), 0) as total FROM transacoes WHERE status = 'pago' AND tipo = 'deposito_pix'");
    if (!$r) return 0;
    $row = $r->fetch_assoc();
    return $row['total'] ?? 0;
}

function saques_total() {
    global $mysqli;
    if (!$mysqli || $mysqli->connect_errno) return 0;
    $r = $mysqli->query("SELECT IFNULL(SUM(valor), 0) as total FROM solicitacao_saques WHERE status = '1' AND tipo_saque = '0'");
    if (!$r) return 0;
    $row = $r->fetch_assoc();
    return $row['total'] ?? 0;
}

function saques_totalsemlink() {
    global $mysqli;
    if (!$mysqli || $mysqli->connect_errno) return 0;
    $r = $mysqli->query("SELECT IFNULL(SUM(valor), 0) as total FROM solicitacao_saques WHERE status = '1' AND tipo_saque = '0'");
    if (!$r) return 0;
    $row = $r->fetch_assoc();
    return $row['total'] ?? 0;
}

function depositos_por_dia() {
    global $mysqli;
    if (!$mysqli || $mysqli->connect_errno) return [];
    $r = $mysqli->query("SELECT DATE(data_registro) as dia, IFNULL(SUM(valor), 0) as total FROM transacoes WHERE status = 'pago' AND data_registro >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) GROUP BY DATE(data_registro) ORDER BY dia ASC");
    $result = [];
    if ($r) while ($row = $r->fetch_assoc()) $result[] = $row;
    return $result;
}

function saques_por_dia() {
    global $mysqli;
    if (!$mysqli || $mysqli->connect_errno) return [];
    $r = $mysqli->query("SELECT DATE(data_registro) as dia, IFNULL(SUM(valor), 0) as total FROM solicitacao_saques WHERE status = '1' AND data_registro >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) GROUP BY DATE(data_registro) ORDER BY dia ASC");
    $result = [];
    if ($r) while ($row = $r->fetch_assoc()) $result[] = $row;
    return $result;
}

function get_online_count() {
    global $mysqli;
    if (!$mysqli || $mysqli->connect_errno) return 0;
    try {
        $r = $mysqli->query("SELECT COUNT(*) as total FROM usuarios WHERE last_activity >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)");
        if (!$r) return 0;
        $row = $r->fetch_assoc();
        return $row['total'] ?? 0;
    } catch (\Throwable $e) {
        return 0;
    }
}

function visitas_count($tipo) {
    global $mysqli;
    if (!$mysqli || $mysqli->connect_errno) return 0;
    $r = $mysqli->query("SELECT COUNT(*) as total FROM visita_site");
    if (!$r) return 0;
    $row = $r->fetch_assoc();
    return $row['total'] ?? 0;
}

function visitas_count2($tipo) {
    global $mysqli;
    if (!$mysqli || $mysqli->connect_errno) return ['cidade' => '', 'estado' => '', 'mac_os' => ''];
    $r = $mysqli->query("SELECT cidade, estado, mac_os FROM visita_site ORDER BY id DESC LIMIT 1");
    if ($r && $row = $r->fetch_assoc()) return $row;
    return ['cidade' => '', 'estado' => '', 'mac_os' => ''];
}
?>
