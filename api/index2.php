<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
@session_module_name('none');
@session_start();
header('Content-Type: text/html; charset=utf-8');
require_once "config.php";
require_once DASH . "/services/database.php";
require_once DASH . "/services/funcao.php";
require_once DASH . "/services/crud.php";
require_once DASH . "/services/CSRF_Protect.php";
require_once DASH . "/services/pega-ip.php";
require_once DASH . "/services/ip-crawler.php";

$csrf = new CSRF_Protect();

$proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
    || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
    ? 'https' : 'http';
$url_atual = $proto . "://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
$url_base = $proto . "://{$_SERVER['HTTP_HOST']}";

$activeLayout = 'Layout2';
$activeTheme = 'ChalcedonyGreen';
$config = [
    'nome' => '', 'descricao' => '', 'logo' => '', 'favicon' => '', 'img_seo' => '',
];

try {
    if ($mysqli && !$mysqli->connect_errno) {
        $result_conf = $mysqli->query("SELECT * FROM config LIMIT 1");
        if ($result_conf && $row_conf = $result_conf->fetch_assoc()) {
            $config = array_merge($config, $row_conf);
        }
        $res = $mysqli->query("SELECT nome_cor, valor_cor FROM temas ORDER BY id DESC LIMIT 1");
        if ($res && $row = $res->fetch_assoc()) {
            if (!empty($row['nome_cor'])) $activeLayout = $row['nome_cor'];
            if (!empty($row['valor_cor'])) $activeTheme = $row['valor_cor'];
        }
    }
    $image_fields = ['logo', 'favicon', 'img_seo'];
    foreach ($image_fields as $field) {
        if (!empty($config[$field]) && strpos($config[$field], 'http') !== 0) {
            if (strpos($config[$field], '/') !== 0) {
                $config[$field] = '/uploads/' . $config[$field];
            }
            $config[$field] = $base_url . $config[$field];
        }
    }
} catch (Exception $e) {}

$language = $config['language'] ?? 'pt-BR';
$phoneCode = $config['phoneCode'] ?? '+55';
$currency = $config['currency'] ?? 'BRL';
$timezoneConfig = $config['timezone'] ?? 'Etc/GMT+3';
$regionNameConfig = $config['regionName'] ?? 'Brasil';
$regionIdConfig = (int)($config['regionId'] ?? 1);
$regionCode = 'BR';

$online_count = 0;
if ($mysqli && !$mysqli->connect_errno) {
    try {
        $r = $mysqli->query("SELECT COUNT(*) as total FROM usuarios");
        if ($r) { $row = $r->fetch_assoc(); $online_count = $row['total'] ?? 0; }
    } catch (Throwable $e) {}
}

ob_start();
include __DIR__ . '/index_body.php';
$html = ob_get_clean();
echo $html;
