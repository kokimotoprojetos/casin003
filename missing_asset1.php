<?php
$request = $_SERVER['REQUEST_URI'];
$path = parse_url($request, PHP_URL_PATH);
$relativePath = ltrim($path, '/');

if (strpos($relativePath, '..') !== false) {
    http_response_code(400);
    exit;
}

function fetch_remote_content($url) {
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
        $data = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($data !== false && $status >= 200 && $status < 400) {
            return $data;
        }
        return false;
    }
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 15,
            'header' => "User-Agent: Mozilla/5.0\r\n"
        ],
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
        ]
    ]);
    return @file_get_contents($url, false, $context);
}

function get_mime_type($ext) {
    $mimes = [
        'png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg',
        'gif' => 'image/gif', 'svg' => 'image/svg+xml', 'webp' => 'image/webp',
        'ico' => 'image/x-icon', 'js' => 'application/javascript',
        'css' => 'text/css', 'json' => 'application/json',
        'woff' => 'font/woff', 'woff2' => 'font/woff2',
        'ttf' => 'font/ttf', 'otf' => 'font/otf', 'eot' => 'application/vnd.ms-fontobject',
    ];
    return $mimes[$ext] ?? 'application/octet-stream';
}

$localPath = __DIR__ . '/' . $relativePath;
$ext = strtolower(pathinfo($localPath, PATHINFO_EXTENSION));

if (file_exists($localPath)) {
    header("Content-Type: " . get_mime_type($ext));
    readfile($localPath);
    exit;
}

$remoteBases = [
    "https://a89s.com/",
    "https://panda99.vip/",
    "https://upload-sys-pics.f-1-g-h.com/",
    "https://upload-sys-pics.bcbd123.com/",
    "https://upload-us.f-1-g-h.com/",
    "https://upload-us.bcbd123.com/"
];

foreach ($remoteBases as $base) {
    $url = $base . $relativePath;
    $content = fetch_remote_content($url);
    if ($content !== false) {
        header("Content-Type: " . get_mime_type($ext));
        echo $content;
        exit;
    }
}

http_response_code(404);
