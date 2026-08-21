<?php
// Admin login check - minimal version
function checa_login_adm() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    // For the main site, this is a no-op since users access via the frontend
}
?>
