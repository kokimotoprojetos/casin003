<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

// O painel Laravel e servido por /muitomoney*. As views compiladas vao pra
// /tmp (o FS da Vercel e legravel). Sem invalidar o cache aqui, qualquer
// alteracao em uma .blade.php continuaria aparecendo a versao antiga na
// proxima requisicao da mesma instancia — entao guardamos um hash de todas
// as views + rotas e limpamos o diretorio quando ele muda.
$cacheDir = '/tmp/storage/framework/views';
$versionFile = '/tmp/storage/framework/views_version';
$viewsDir = __DIR__ . '/core/resources/views';

$parts = '';
if (is_dir($viewsDir)) {
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($viewsDir, FilesystemIterator::SKIP_DOTS)
    );
    foreach ($it as $f) {
        if ($f->isFile() && in_array($f->getExtension(), ['php', 'css', 'js'])) {
            $parts .= md5_file($f->getPathname());
        }
    }
}
$parts .= @md5_file(__DIR__ . '/core/routes/web.php');
$parts .= (string) (
    $_SERVER['HTTP_X_VERCEL_DEPLOYMENT_URL']
    ?? $_SERVER['VERCEL_DEPLOYMENT_ID']
    ?? getenv('VERCEL_DEPLOYMENT_ID')
    ?? ''
);
$cacheKey = md5($parts);

if (!is_dir($cacheDir)) {
    @mkdir($cacheDir, 0755, true);
}
$cachedKey = @file_get_contents($versionFile);
if ($cachedKey !== $cacheKey) {
    foreach ((array) @glob($cacheDir . '/*.php') as $stale) {
        @unlink($stale);
    }
    @file_put_contents($versionFile, $cacheKey);
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
