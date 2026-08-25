<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: text/plain');

echo "=== PHP " . PHP_VERSION . " ===\n";
echo "Time: " . date('c') . "\n\n";

echo "=== Testing session ===\n";
@ini_set('session.save_handler', 'none');
@session_start();
echo "Session status: " . session_status() . "\n\n";

echo "=== Testing config.php ===\n";
try {
    require_once __DIR__ . '/../config.php';
    echo "OK\n\n";
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n\n";
}

echo "=== Testing database.php ===\n";
try {
    require_once __DIR__ . '/../admin/services/database.php';
    echo "OK - connected: " . ($mysqli ? 'yes' : 'no') . "\n";
    if ($mysqli && !$mysqli->connect_errno) {
        echo "Server info: " . $mysqli->server_info . "\n";
    }
    if ($mysqli && $mysqli->connect_errno) {
        echo "Connect error: " . $mysqli->connect_error . "\n";
    }
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
echo "\n";

echo "=== Testing funcao.php ===\n";
try {
    require_once __DIR__ . '/../admin/services/funcao.php';
    echo "OK\n\n";
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n\n";
}

echo "=== Testing crud.php ===\n";
try {
    require_once __DIR__ . '/../admin/services/crud.php';
    echo "OK\n\n";
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n\n";
}

echo "=== Testing CSRF_Protect.php ===\n";
try {
    require_once __DIR__ . '/../admin/services/CSRF_Protect.php';
    $csrf = new CSRF_Protect();
    echo "OK\n\n";
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n\n";
}

echo "=== Testing pega-ip.php ===\n";
try {
    require_once __DIR__ . '/../admin/services/pega-ip.php';
    echo "OK - IP: " . ($ip ?? 'not set') . "\n\n";
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n\n";
}

echo "=== Testing ip-crawler.php ===\n";
try {
    require_once __DIR__ . '/../admin/services/ip-crawler.php';
    echo "OK\n\n";
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n\n";
}

echo "=== DB Query test ===\n";
try {
    if ($mysqli && !$mysqli->connect_errno) {
        $res = $mysqli->query("SELECT id, nome FROM config LIMIT 1");
        if ($row = $res->fetch_assoc()) {
            echo "Config: id=" . $row['id'] . ", nome=" . $row['nome'] . "\n";
        }
        $res2 = $mysqli->query("SHOW TABLES");
        echo "Total tables: " . $res2->num_rows . "\n";
    }
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
