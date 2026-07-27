<?php
chdir(__DIR__ . '/..');

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = rtrim($uri, '/') ?: '/';

$cleanRoutes = [
    '/admin/login'                => '/admin/auth-login.php',
    '/admin/sair'                 => '/admin/sair.php',
    '/admin/dashboard'            => '/admin/index.php',
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
    '/gold_api'                   => '/callback/game_callback.php',
    '/infinitysoft_api'           => '/callback/game_callback.php',
    '/igamewin'                   => '/callback/igamewin.php',
    '/ppclone'                    => '/callback/ppclone.php',
    '/drakon_api'                 => '/callback/drakon.php',
    '/playfiver/webhook'          => '/callback/playfiver.php',
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

if (isset($cleanRoutes[$uri])) {
    require __DIR__ . $cleanRoutes[$uri];
    exit;
}

foreach ($slugRoutes as $pattern => $dest) {
    if (preg_match($pattern, $uri, $m)) {
        $_GET['slug'] = $m[1];
        require __DIR__ . $dest;
        exit;
    }
}

if (preg_match('#^/admin/([a-zA-Z0-9_-]+)$#', $uri, $m)) {
    $file = '/admin/' . $m[1] . '.php';
    if (file_exists(__DIR__ . $file)) {
        require __DIR__ . $file;
        exit;
    }
}

if (preg_match('#^/(callback|callbackpayment)/([a-zA-Z0-9_-]+)$#', $uri, $m)) {
    $file = '/' . $m[1] . '/' . $m[2] . '.php';
    if (file_exists(__DIR__ . $file)) {
        require __DIR__ . $file;
        exit;
    }
}

foreach ($directPrefixes as $prefix => $dest) {
    if (strpos($uri . '/', $prefix . '/') === 0) {
        require __DIR__ . $dest;
        exit;
    }
}

if (preg_match('#^/api/(frontend|v1)(/.*)?$#', $uri)) {
    require __DIR__ . '/v1/api.php';
    exit;
}

if (preg_match('#\.(png|jpg|jpeg|gif|svg|webp|ico)$#', $uri)) {
    require __DIR__ . '/../missing_asset.php';
    exit;
}

require __DIR__ . '/../index.php';
