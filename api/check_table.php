<?php
header('Content-Type: text/plain');
require_once __DIR__ . '/../admin/services/database.php';

$tables = ['visita_site', 'admin_users', 'config', 'sessions'];
foreach ($tables as $t) {
    $q = $mysqli->query("SHOW CREATE TABLE `$t`");
    if ($q && $r = $q->fetch_assoc()) {
        echo "=== $t ===\n";
        echo $r['Create Table'] . "\n\n";
    } else {
        echo "=== $t === NOT FOUND: " . $mysqli->error . "\n\n";
    }
}
