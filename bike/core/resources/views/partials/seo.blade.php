@php
    $socialImageUrlWebp = asset('assets/images/seo/wind_bikes_preview.webp');
    $socialImageUrlJpg  = asset('assets/images/seo/wind_bikes_preview.jpg');
    $socialTitle = 'Wind Bikes - Desbloqueie Aventuras';
    $socialDescription = 'Com a sua e-bike WIND, você ganha acesso exclusivo a trilhas premium e roteiros curados.';

    if (isset($seoContents) && count($seoContents)) {
        $seoContents = json_decode(json_encode($seoContents, true));
    } elseif ($seo) {
        $seoContents = $seo;
        if (!empty($seo->social_title)) {
            $socialTitle = $seo->social_title;
        }
        if (!empty($seo->social_description)) {
            $socialDescription = $seo->social_description;
        }
    } else {
        $seoContents = null;
    }

    $seoWebpWithVersion = $socialImageUrlWebp . '?v=' . time();
    $seoJpgWithVersion  = $socialImageUrlJpg . '?v=' . time();
@endphp

@if(!isset($pageTitle))
<title>{{ $socialTitle }}</title>
@endif
<meta name="title" content="{{ $socialTitle }}">
<meta name="description" content="{{ $socialDescription }}">

{{-- Apple Stuff --}}
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black">
<meta name="apple-mobile-web-app-title" content="Wind Bikes">

{{-- Google / Search Engine Tags --}}
<meta itemprop="name" content="{{ $socialTitle }}">
<meta itemprop="description" content="{{ $socialDescription }}">

{{-- Facebook / WhatsApp / Telegram Meta Tags --}}
<meta property="og:type" content="website">
<meta property="og:site_name" content="Wind Bikes">
<meta property="og:title" content="{{ $socialTitle }}">
<meta property="og:description" content="{{ $socialDescription }}">
<meta property="og:url" content="{{ url()->current() }}">

{{-- Primary WebP Image (Ultra-leve ~69KB) --}}
<meta property="og:image" content="{{ $seoWebpWithVersion }}">
<meta property="og:image:secure_url" content="{{ $seoWebpWithVersion }}">
<meta property="og:image:type" content="image/webp">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:image:alt" content="Wind Bikes">

{{-- Secondary JPG Image (Compatibilidade universal) --}}
<meta property="og:image" content="{{ $seoJpgWithVersion }}">
<meta property="og:image:secure_url" content="{{ $seoJpgWithVersion }}">
<meta property="og:image:type" content="image/jpeg">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:image:alt" content="Wind Bikes">

{{-- Twitter Meta Tags --}}
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $socialTitle }}">
<meta name="twitter:description" content="{{ $socialDescription }}">
<meta name="twitter:image" content="{{ $seoWebpWithVersion }}">
