<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: text/plain');

echo "Step 1: PHP OK " . PHP_VERSION . "\n";

echo "Step 2: session_module_name... ";
@session_module_name('none');
echo "OK\n";

echo "Step 3: session_start... ";
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}
echo "status=" . session_status() . "\n";

echo "Step 4: config.php... ";
chdir(__DIR__ . '/..');
require_once __DIR__ . '/../config.php';
echo "OK DASH=" . DASH . "\n";

echo "Step 5: database.php... ";
require_once DASH . '/services/database.php';
echo "OK connected=" . ($mysqli ? 'yes' : 'no') . "\n";
if ($mysqli && $mysqli->connect_errno) echo "ERR: " . $mysqli->connect_error . "\n";

echo "Step 6: funcao.php... ";
require_once DASH . '/services/funcao.php';
echo "OK\n";

echo "Step 7: crud.php... ";
require_once DASH . '/services/crud.php';
echo "OK\n";

echo "Step 8: CSRF_Protect... ";
require_once DASH . '/services/CSRF_Protect.php';
$csrf = new CSRF_Protect();
echo "OK\n";

echo "Step 9: pega-ip... ";
require_once DASH . '/services/pega-ip.php';
echo "OK ip=" . ($ip ?? 'null') . "\n";

echo "Step 10: ip-crawler... ";
require_once DASH . '/services/ip-crawler.php';
echo "OK browser=" . ($browser ?? 'null') . "\n";

echo "Step 11: DB query config... ";
$res = $mysqli->query("SELECT id, nome FROM config LIMIT 1");
$row = $res->fetch_assoc();
echo "OK nome=" . ($row['nome'] ?? 'null') . "\n";

echo "Step 12: DB query visita_site... ";
$res = $mysqli->query("SELECT 1 FROM visita_site LIMIT 1");
echo "OK\n";

echo "\nALL STEPS PASSED";
