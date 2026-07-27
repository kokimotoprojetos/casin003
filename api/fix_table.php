<?php
header('Content-Type: text/plain');
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../admin/services/database.php';

echo "Before fix:\n";
$q = $mysqli->query("SHOW CREATE TABLE visita_site");
$r = $q->fetch_assoc();
echo $r['Create Table'] . "\n\n";

// Fix: ensure id has AUTO_INCREMENT
$mysqli->query("ALTER TABLE visita_site MODIFY COLUMN id INT NOT NULL AUTO_INCREMENT");

echo "After fix:\n";
$q = $mysqli->query("SHOW CREATE TABLE visita_site");
$r = $q->fetch_assoc();
echo $r['Create Table'] . "\n";

echo "\nDone.\n";
