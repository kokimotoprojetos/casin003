<?php
date_default_timezone_set("America/Sao_Paulo");

if (!defined('SITE_URL')) {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    $protocol = $isHttps ? 'https://' : 'http://';
    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
    define('SITE_URL', $protocol . $host);
}

if (!defined('DATABASE_LOADED')) {
    $bd_local = getenv('DB_HOST') ?: 'mysql-32225e83-mandadinheiroproloky-4897.i.aivencloud.com';
    $bd_usuario = getenv('DB_USER') ?: 'avnadmin';
    $bd_senha = getenv('DB_PASS') ?: 'AVNS_Hx-pbf22fhzULYbOUkR';
    $bd_banco = getenv('DB_NAME') ?: 'defaultdb';
    $bd_porta = getenv('DB_PORT') ?: 18533;

    $bd = array(
        'local' => $bd_local,
        'usuario' => $bd_usuario,
        'senha' => $bd_senha,
        'banco' => $bd_banco,
        'porta' => (int)$bd_porta
    );

    try {
        $mysqli = new mysqli();
        $mysqli->options(MYSQLI_OPT_CONNECT_TIMEOUT, 5);
        $mysqli->options(MYSQLI_OPT_READ_TIMEOUT, 5);
        $mysqli->ssl_set(null, null, null, null, null);
        @$mysqli->real_connect($bd['local'], $bd['usuario'], $bd['senha'], $bd['banco'], $bd['porta'], null, MYSQLI_CLIENT_SSL);
    } catch (Exception $e) {
        error_log("Database connection error: " . $e->getMessage());
        echo json_encode([
            'status' => 'error', 
            'message' => 'Erro: Falha na conexão com o banco de dados.'
        ]);
        exit;
    }
    
    $mysqli->set_charset("utf8mb4");

    if ($mysqli->connect_errno) {
        echo json_encode([
            'status' => 'error', 
            'message' => 'Erro: Arquivo de configuração do banco não encontrado.'
        ]);
        exit;
    }

    if (!$mysqli->set_charset("utf8mb4")) {
        $mysqli->set_charset("utf8");
    }
    
    // Check for table collation only if connection is successful
    try {
        $res = $mysqli->query("SELECT T.table_collation FROM information_schema.TABLES T WHERE T.table_schema = DATABASE() AND T.table_name = 'config' LIMIT 1");
        if ($res) {
            $row = $res->fetch_assoc();
            if ($row && strpos($row['table_collation'], 'utf8mb4') === false) {
                $mysqli->query("ALTER TABLE `config` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            }
        }
    } catch (Exception $e) {
        // Ignore collation check errors
    }
    
    define('DATABASE_LOADED', true);
}
?>