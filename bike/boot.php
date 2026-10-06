<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

// O painel Laravel e servido por /muitomoney*. Os arquivos .php do rio-nova
// nao gravam nada, entao nao e preciso invalidar nada aqui; apenas garantimos
// que o diretorio de views compiladas exista (a config ja aponta pra /tmp).
$cacheDir = '/tmp/storage/framework/views';
if (!is_dir($cacheDir)) {
    @mkdir($cacheDir, 0755, true);
}

if (file_exists($maintenance = __DIR__ . '/core/storage/framework/maintenance.php')) {
    require $maintenance;
}

require __DIR__ . '/core/vendor/autoload.php';

$app = require_once __DIR__ . '/core/bootstrap/app.php';

$kernel = $app->make(Kernel::class);

// Sem isto o Laravel montaria URLs a partir de /api/index.php (o script que
// esta realmente rodando) e o painel sairia de /muitomoney pra /api/...
if (!isset($_SERVER['SCRIPT_NAME']) || strpos($_SERVER['SCRIPT_NAME'], 'api/') !== false) {
    $_SERVER['SCRIPT_NAME'] = '/index.php';
}

$response = $kernel->handle($request = Request::capture());

$response->headers->set('Cache-Control', 'no-store, max-age=0');
$response->headers->set('X-Frame-Options', 'DENY');
$response->headers->set('X-Content-Type-Options', 'nosniff');
$response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

$response->send();

$kernel->terminate($request, $response);
exit;
