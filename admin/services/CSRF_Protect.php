<?php
// CSRF Protection class
class CSRF_Protect {
    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            try { @session_start(); } catch (\Throwable $e) {}
        }
    }
    
    public function getTokenField() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return '<input type="hidden" name="csrf_token" value="' . $_SESSION['csrf_token'] . '">';
    }
    
    public function verifyToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}
?>
