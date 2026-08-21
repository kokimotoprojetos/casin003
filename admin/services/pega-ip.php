<?php
// IP Grabber
$ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
if (strpos($ip, ',') !== false) {
    $ip = trim(explode(',', $ip)[0]);
}
?>
