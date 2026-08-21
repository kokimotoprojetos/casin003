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

require_once __DIR__ . '/env_loader.php';

if (!defined('DATABASE_LOADED')) {
    $bd_local = getenv('DB_HOST');
    $bd_usuario = getenv('DB_USER');
    $bd_senha = getenv('DB_PASS');
    $bd_banco = getenv('DB_NAME');
    $bd_porta = getenv('DB_PORT');

    if (empty($bd_local) || empty($bd_usuario) || empty($bd_senha) || empty($bd_banco) || empty($bd_porta)) {
        $bd_local = 'j240';
        $bd_usuario = 'opcao';
        $bd_senha = 'RItIrtMIGlAw';
        $bd_banco = 'valsa-traje';
        $bd_porta = '3306';
    }

    $bd = array(
        'local' => $bd_local,
        'usuario' => $bd_usuario,
        'senha' => $bd_senha,
        'banco' => $bd_banco,
        'porta' => (int)$bd_porta
    );

    try {
        $mysqli = new mysqli();
        $mysqli->options(MYSQLI_OPT_CONNECT_TIMEOUT, 10);
        $mysqli->options(MYSQLI_OPT_READ_TIMEOUT, 10);
        $mysqli->ssl_set(null, null, null, null, null);
        @$mysqli->real_connect($bd['local'], $bd['usuario'], $bd['senha'], $bd['banco'], $bd['porta'], null, MYSQLI_CLIENT_SSL);
    } catch (Exception $e) {
        error_log("Database connection error: " . $e->getMessage());
        $mysqli = null;
    }
    
    if ($mysqli && !$mysqli->connect_errno) {
        $mysqli->set_charset("utf8mb4");
        if (!$mysqli->set_charset("utf8mb4")) {
            $mysqli->set_charset("utf8");
        }
    }
    
    define('DATABASE_LOADED', true);
}
?>
