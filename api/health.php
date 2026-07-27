<?php
header('Content-Type: application/json');
$result = ['status' => 'ok', 'time' => date('c'), 'php' => PHP_VERSION];

// Test DB
try {
    require_once __DIR__ . '/../admin/services/database.php';
    $q = $mysqli->query("SELECT 1 AS test");
    $r = $q->fetch_assoc();
    $result['db'] = $r['test'] == 1 ? 'ok' : 'fail';
    $result['db_info'] = $mysqli->server_info;
} catch (Throwable $e) {
    $result['db'] = 'error';
    $result['db_error'] = $e->getMessage();
}

echo json_encode($result, JSON_PRETTY_PRINT);
