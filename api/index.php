<?php
require_once __DIR__ . '/session_handler.php';
error_log("api/index.php invoked: " . ($_SERVER['REQUEST_URI'] ?? 'unknown'));
if (strpos($_SERVER['REQUEST_URI'] ?? '', 'debug-headers') !== false || isset($_GET['debug-headers'])) {
    header('Content-Type: application/json');
    $info = [
        'SERVER' => $_SERVER,
        'getallheaders_exists' => function_exists('getallheaders'),
        'getallheaders' => function_exists('getallheaders') ? getallheaders() : null,
        'HTTP_AUTHORIZATION' => $_SERVER['HTTP_AUTHORIZATION'] ?? null,
        'REDIRECT_HTTP_AUTHORIZATION' => $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? null,
    ];
    echo json_encode($info, JSON_PRETTY_PRINT);
    exit;
}
if (strpos($_SERVER['REQUEST_URI'] ?? '', 'get-ip') !== false || isset($_GET['get-ip'])) {
    header('Content-Type: application/json');
    $ip = @file_get_contents('https://api.ipify.org');
    echo json_encode(['ip' => $ip, 'message' => 'Copie este IP e adicione no Controle de IPs da PlayFiver']);
    exit;
}
if (strpos($_SERVER['REQUEST_URI'] ?? '', 'test-launch') !== false || isset($_GET['test-launch'])) {
    header('Content-Type: application/json');
    require_once __DIR__ . '/../config.php';
    require_once __DIR__ . '/../admin/services/database.php';
    require_once __DIR__ . '/../admin/services/crud.php';
    $gameId = $_GET['id'] ?? null;
    $provider = $_GET['provider'] ?? 'PGSOFT';
    $gameCode = $_GET['game'] ?? 'fortune-tiger';
    $email = $_GET['email'] ?? 'testekoki@email.com';
    $saldo = floatval($_GET['saldo'] ?? 100);
    if ($gameId) {
        $stmt = $mysqli->prepare("SELECT game_code, provider, api, game_name FROM games WHERE id = ?");
        $stmt->bind_param('s', $gameId);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $provider = $row['provider'];
            $gameCode = $row['game_code'];
            $api = strtolower(trim($row['api']));
            $gameName = $row['game_name'];
            $res2 = null;
            if ($api === 'playfiver') {
                $res2 = pegarLinkJogoApiPlayFiver($provider, $gameCode, $email, $saldo);
            } elseif ($api === 'pgclone') {
                $res2 = pegarLinkJogoPGClone($provider, $gameCode, $email, $saldo);
            } elseif ($api === 'ppclone') {
                $res2 = pegarLinkJogoPPClone($provider, $gameCode, $email, $saldo);
            } else {
                $res2 = pegarLinkJogoigamewin($provider, $gameCode, $email, $saldo);
            }
            echo json_encode([
                'game_id' => $gameId,
                'game_name' => $gameName,
                'game_code' => $gameCode,
                'provider' => $provider,
                'api' => $row['api'],
                'launch_result' => $res2,
            ], JSON_PRETTY_PRINT);
            $stmt->close();
            exit;
        }
        $stmt->close();
        echo json_encode(['error' => 'Game not found', 'game_id' => $gameId]);
        exit;
    }
    $res = pegarLinkJogoApiPlayFiver($provider, $gameCode, $email, $saldo);
    echo json_encode(['result' => $res]);
    exit;
}
if (strpos($_SERVER['REQUEST_URI'] ?? '', 'pfiver-diag') !== false || isset($_GET['pfiver-diag'])) {
    header('Content-Type: application/json');
    require_once __DIR__ . '/../config.php';
    require_once __DIR__ . '/../admin/services/database.php';
    require_once __DIR__ . '/../admin/services/crud.php';
    $cfg = data_playfiver();
    $proxy = trim($cfg['proxy'] ?? '');
    $proxyMasked = '';
    if ($proxy !== '') {
        $proxyMasked = preg_replace('/(:\/\/)([^:]+):([^@]+)@/', '$1***:***@', $proxy);
    }
    $ipSeen = null;
    $ipRaw = null;
    $proxyError = null;
    if ($proxy !== '') {
        $chDiag = curl_init('https://ipinfo.io/ip');
        curl_setopt($chDiag, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($chDiag, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($chDiag, CURLOPT_TIMEOUT, 15);
        curl_setopt($chDiag, CURLOPT_HTTPGET, true);
        curl_setopt($chDiag, CURLOPT_USERAGENT, 'Mozilla/5.0');
        if (strpos($proxy, '://') !== false) {
            curl_setopt($chDiag, CURLOPT_PROXY, $proxy);
        } elseif (strpos($proxy, '@') !== false) {
            list($auth, $hostport) = explode('@', $proxy, 2);
            curl_setopt($chDiag, CURLOPT_PROXY, $hostport);
            curl_setopt($chDiag, CURLOPT_PROXYUSERPWD, $auth);
        } else {
            curl_setopt($chDiag, CURLOPT_PROXY, $proxy);
        }
        $ipRaw = curl_exec($chDiag);
        if ($ipRaw === false) {
            $proxyError = curl_error($chDiag);
        } else {
            $ipSeen = trim($ipRaw);
        }
        curl_close($chDiag);
    }
    $vercelIp = @file_get_contents('https://ipinfo.io/ip');
    echo json_encode([
        'ativo' => isset($cfg['ativo']) ? intval($cfg['ativo']) : null,
        'proxy_configured' => $proxy !== '',
        'proxy_masked' => $proxyMasked,
        'agent_code_set' => !empty(trim($cfg['agent_code'] ?? '')),
        'agent_token_set' => !empty(trim($cfg['agent_token'] ?? '')),
        'agent_secret_set' => !empty(trim($cfg['agent_secret'] ?? '')),
        'url_set' => !empty(trim($cfg['url'] ?? '')),
        'ip_seen_by_playfiver_via_proxy' => $ipSeen,
        'ip_echo_raw' => $ipRaw,
        'proxy_error' => $proxyError,
        'vercel_real_ip' => $vercelIp,
    ], JSON_PRETTY_PRINT);
    exit;
}
chdir(__DIR__ . '/..');
set_error_handler(function ($severity, $message, $file, $line) {
    error_log("PHP Error [$severity] $message in $file:$line");
});
register_shutdown_function(function () {
    $e = error_get_last();
    if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        error_log("PHP Fatal: {$e['message']} in {$e['file']}:{$e['line']}");
        $ob = @ob_get_contents();
        if ($ob) @ob_clean();
        while (@ob_end_clean());
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(500);
        }
        echo json_encode(['error' => 'Internal Server Error', 'detail' => $e['message']]);
        @ob_flush();
        flush();
    }
});

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = rtrim($uri, '/') ?: '/';

$cleanRoutes = [
    '/health'                     => '/api/health.php',
    '/api/ping'                   => '/api/ping.php',
    '/api/ping.php'               => '/api/ping.php',
    '/api/debug'                  => '/api/debug.php',
    '/api/debug.php'              => '/api/debug.php',

    '/admin'                      => '/admin/index.php',
    '/admin/login'                => '/admin/auth-login.php',
    '/admin/sair'                 => '/admin/sair.php',
    '/admin/dashboard'            => '/admin/index.php',
    '/admin/logout'               => '/admin/sair.php',
    '/admin/contas-demos'         => '/admin/contasdemos.php',
    '/admin/gerenciamento-nomes'  => '/admin/ge-nomes.php',
    '/admin/pixel'                => '/admin/pixeis.php',
    '/admin/identidade-visual'    => '/admin/imagens-plataforma.php',
    '/admin/banners'              => '/admin/editar-banners.php',
    '/admin/festival'             => '/admin/editar-festival.php',
    '/admin/promocoes'            => '/admin/editar-promocoes.php',
    '/admin/cupons'               => '/admin/ge-cupons.php',
    '/admin/popups'               => '/admin/editar-popups.php',
    '/admin/baixarpop'            => '/admin/popup-baixar.php',
    '/admin/iconesfloat'          => '/admin/editar-floats.php',
    '/admin/gamesbeeplay'         => '/admin/beeplay.php',
    '/admin/chavesplayfiver'      => '/admin/pfiverapi.php',
    '/admin/historicosplay'       => '/admin/historico_jogadas.php',
    '/admin/logsbonus'            => '/admin/logs_cupons.php',
    '/admin/niveislogs'           => '/admin/logs_niveis.php',
    '/admin/niveis'               => '/admin/ge-vips.php',
    '/admin/webhooks'             => '/admin/webhook.php',
    '/admin/depositos-pendentes'  => '/admin/depositos_pendentes.php',
    '/admin/all-depositos'        => '/admin/depositos_pagos.php',
    '/admin/saques_pen'           => '/admin/saques_pendentes.php',
    '/admin/all01-saques'         => '/admin/saques_aprovados.php',
    '/admin/saldo-api-js'         => '/admin/saldo-api-js.php',
    '/admin/slots-games'          => '/admin/jogos.php',
    '/admin/config_afiliados'     => '/admin/gerenciamento-afiliados.php',
    '/admin/mensagens'            => '/admin/editar-mensagens.php',
    '/callback/user_balance'      => '/callback/user_balance.php',
    '/callback/game_callback'     => '/callback/game_callback.php',
    '/callback/igamewin'          => '/callback/igamewin.php',
    '/callback/ppclone'           => '/callback/ppclone.php',
    '/callbackpayment/suitpay'    => '/callbackpayment/suitpay.php',
    '/callbackpayment/expfypay'   => '/callbackpayment/expfypay.php',
    '/callbackpayment/bspay'      => '/callbackpayment/bspay.php',
    '/callbackpayment/aurenpay'   => '/callbackpayment/aurenpay.php',
    '/callbackpayment/greepay'    => '/callbackpayment/greepay.php',
    '/callbackpayment/versell'    => '/callbackpayment/versell.php',
    '/callbackpayment/webhook'    => '/callbackpayment/webhook.php',
    '/callbackpayment/inpagamentos' => '/callbackpayment/inpagamentos.php',
    '/callbackpayment/poseidonpay' => '/callbackpayment/poseidonpay.php',
    '/gold_api'                   => '/callback/game_callback.php',
    '/infinitysoft_api'           => '/callback/game_callback.php',
    '/igamewin'                   => '/callback/igamewin.php',
    '/ppclone'                    => '/callback/ppclone.php',
    '/drakon_api'                 => '/callback/drakon.php',
    '/playfiver/webhook'          => '/callback/playfiver.php',
    '/api/webhook'                => '/callback/playfiver.php',
];

$slugRoutes = [
    '#^/admin/detalhes_usuario=(.+)$#' => '/admin/detalhes_usuario.php',
    '#^/admin/games=(.+)$#'            => '/admin/games.php',
    '#^/admin/edit_banner=(.+)$#'      => '/admin/edit_banner.php',
    '#^/admin/edit_popups=(.+)$#'      => '/admin/edit_popups.php',
];

$directPrefixes = [
    '/gold_api'         => '/callback/game_callback.php',
    '/infinitysoft_api' => '/callback/game_callback.php',
    '/igamewin'         => '/callback/igamewin.php',
    '/ppclone'          => '/callback/ppclone.php',
    '/drakon_api'       => '/callback/drakon.php',
    '/playfiver/webhook' => '/callback/playfiver.php',
];

$root = __DIR__ . '/..';

if (isset($cleanRoutes[$uri])) {
    require $root . $cleanRoutes[$uri];
    exit;
}

foreach ($slugRoutes as $pattern => $dest) {
    if (preg_match($pattern, $uri, $m)) {
        $_GET['slug'] = $m[1];
        require $root . $dest;
        exit;
    }
}

if (strpos($uri, '/admin/') === 0) {
    $relative_path = substr($uri, 7); // Strip '/admin/'
    if (strpos($relative_path, '..') === false) {
        $target_file = $root . '/admin/' . $relative_path;
        if (!preg_match('#\.[a-zA-Z0-9]+$#', $relative_path)) {
            $target_file .= '.php';
        }
        if (file_exists($target_file)) {
            require $target_file;
            exit;
        }
    }
}

if (preg_match('#^/(callback|callbackpayment)/([a-zA-Z0-9_-]+)$#', $uri, $m)) {
    $file = '/' . $m[1] . '/' . $m[2] . '.php';
    if (file_exists($root . $file)) {
        require $root . $file;
        exit;
    }
}

foreach ($directPrefixes as $prefix => $dest) {
    if (strpos($uri . '/', $prefix . '/') === 0) {
        require $root . $dest;
        exit;
    }
}

if (preg_match('#^/api/(frontend|v1)(/.*)?$#', $uri)) {
    chdir(__DIR__ . '/v1');
    require $root . '/api/v1/api.php';
    exit;
}

if (preg_match('#\.(png|jpg|jpeg|gif|svg|webp|ico)$#', $uri)) {
    require $root . '/missing_asset.php';
    exit;
}

if (isset($_GET['gozei_route'])) {
    $_SERVER['REQUEST_URI'] = '/gozei/api/' . $_GET['gozei_route'];
    require $root . '/gozei/api.php';
    exit;
}

if (strpos($uri, '/gozei/api/') === 0) {
    require $root . '/gozei/api.php';
    exit;
}

require $root . '/index.php';
