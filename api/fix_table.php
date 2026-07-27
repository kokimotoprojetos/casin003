<?php
header('Content-Type: text/plain');
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../admin/services/database.php';

echo "Before fix:\n";
$q = $mysqli->query("SHOW CREATE TABLE visita_site");
$r = $q->fetch_assoc();
echo $r['Create Table'] . "\n\n";

// Disable sql_require_primary_key for this session (Aiven default is ON)
$mysqli->query("SET SESSION sql_require_primary_key = 0");

// Fix: add primary key + auto_increment to id
$mysqli->query("ALTER TABLE visita_site MODIFY COLUMN id INT NOT NULL AUTO_INCREMENT PRIMARY KEY");

echo "After fix:\n";
$q = $mysqli->query("SHOW CREATE TABLE visita_site");
$r = $q->fetch_assoc();
echo $r['Create Table'] . "\n";

echo "\nDone.\n";
