<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: text/plain');
echo "PHP OK\n";
try {
    $host = getenv('DB_HOST') ?: 'mysql-32225e83-mandadinheiroproloky-4897.i.aivencloud.com';
    $user = getenv('DB_USER') ?: 'avnadmin';
    $pass = getenv('DB_PASS') ?: 'AVNS_Hx-pbf22fhzULYbOUkR';
    $db   = getenv('DB_NAME') ?: 'defaultdb';
    $port = getenv('DB_PORT') ?: 18533;
    $mysqli = new mysqli();
    $mysqli->ssl_set(null, null, null, null, null);
    $mysqli->real_connect($host, $user, $pass, $db, $port, null, MYSQLI_CLIENT_SSL);
    if ($mysqli->connect_errno) {
        echo "DB ERROR: " . $mysqli->connect_error . "\n";
    } else {
        echo "DB CONNECTED\n";
        $res = $mysqli->query("SELECT COUNT(*) as t FROM information_schema.tables WHERE table_schema = '$db'");
        if ($res) {
            $row = $res->fetch_assoc();
            echo "Tables: " . $row['t'] . "\n";
        }
        $mysqli->close();
    }
} catch (Exception $e) {
    echo "DB EXCEPTION: " . $e->getMessage() . "\n";
}
