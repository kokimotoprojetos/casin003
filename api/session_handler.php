<?php
if (php_sapi_name() === 'cli') return;
if (defined('VERCEL_SESSION_LOADED')) return;
define('VERCEL_SESSION_LOADED', true);

require_once __DIR__ . '/../config.php';

// Use native file-based sessions for reliability
ini_set('session.save_handler', 'files');
ini_set('session.save_path', sys_get_temp_dir());
session_start();
