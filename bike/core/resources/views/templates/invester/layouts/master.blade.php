@extends($activeTemplate.'layouts.app')
@section('panel')

    <style>
    /* ===== Adaptacao para PC: coluna de app centralizada ===== */
    @media (min-width: 768px) {
        body {
            background: radial-gradient(circle at 50% 0%, #15803d 0%, #064e3b 45%, #022c22 100%) !important;
        }
        #app-shell.app-frame {
            max-width: 430px;
            margin: 0 auto;
            min-height: 100vh;
            background: #ffffff;
            box-shadow: 0 0 70px rgba(0, 0, 0, 0.55);
            position: relative;
            overflow-x: hidden;
        }
        #app-shell.app-frame .navbar-bottom {
            left: 50% !important;
            right: auto !important;
            transform: translateX(-50%);
            width: 100%;
            max-width: 430px;
        }
    }
    </style>
    <div id="app-shell" class="app-frame">
 

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <!-- <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4"
        crossorigin="anonymous"></script>

    <!-- Font Awesome  css-->
    <link href="{{asset ('core/css/static/fontawesomefree/css/fontawesome.css')}}" rel="stylesheet" type="text/css">
    <link href="{{asset ('core/css/static/fontawesomefree/css/brands.css')}}" rel="stylesheet" type="text/css">
    <link href="{{asset ('core/css/static/fontawesomefree/css/solid.css')}}" rel="stylesheet" type="text/css">

    <!-- Custom style  -->
    <!-- <link rel="stylesheet" href="/static/css/responsives.css"> -->
    <link rel="stylesheet" href="{{asset ('core/styles.css')}}">
    <link rel="stylesheet" href="{{asset ('core/snackbar.css')}}">


    <!-- style.css -->
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

        * {
            font-family: 'Inter', sans-serif;
        }

        html, body {
            margin: 0;
            padding: 0;
            width: 100%;
            -webkit-tap-highlight-color: transparent;
        }

        input, textarea, select, button, a, label, [onclick], [role="button"] {
            -webkit-user-select: auto;
            user-select: auto;
            -webkit-touch-callout: default;
            touch-action: manipulation;
        }

        .main-content-wrapper {
            padding-bottom: 80px;
            min-height: 100vh;
        }

        /* ── Bottom Navbar ─────────────────────────────────────── */
        .navbar-bottom {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            width: 100%;
            z-index: 99999;
            padding-bottom: env(safe-area-inset-bottom, 0);
            background: #ffffff;
            box-shadow: 0 -2px 12px rgba(0,0,0,0.08);
            border-radius: 14px 14px 0 0;
        }

        nav {
            position: static;
            width: 100%;
        }

        .nav-box {
            display: flex;
            padding: 4px 8px 0;
            background: transparent;
            border-radius: 0;
            box-shadow: none;
        }

        .nav-container {
            display: flex;
            width: 100%;
            list-style: none;
            justify-content: space-around;
            align-items: center;
            padding: 6px 0 4px;
            margin: 0;
            min-height: auto;
        }

        .nav__item {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            padding: 4px 0 2px;
            flex: 1;
        }

        .nav__item.active {
            border-bottom: none;
        }

        .nav__item.active .nav__item-icon {
            background: rgba(22,163,74,0.1);
            border-radius: 12px;
            padding: 3px 12px;
        }

        .nav__item-link {
            display: flex;
            flex-direction: column;
            align-items: center;
            color: #999;
            text-decoration: none;
            gap: 2px;
        }

        .nav__item-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 26px;
            min-width: 26px;
            transition: all 200ms ease;
        }

        .nav__item-icon img, .nav__item-icon svg {
            height: 20px;
            width: 20px;
            filter: brightness(0.6);
            transition: filter 200ms ease;
        }

        #produtos_div .nav__item-icon svg,
        #produtos_div .nav__item-icon img {
            height: 27px;
            width: 27px;
        }

        #team_div .nav__item-icon svg,
        #team_div .nav__item-icon img {
            height: 22px;
            width: 22px;
        }

        .nav__item.active .nav__item-icon img,
        .nav__item.active .nav__item-icon svg {
            filter: brightness(0) saturate(100%) invert(48%) sepia(68%) saturate(1878%) hue-rotate(94deg) brightness(97%) contrast(85%);
        }

        .nav__item-text {
            font-size: 9px;
            font-weight: 600;
            color: #999;
            transition: color 200ms ease;
            white-space: nowrap;
        }

        .nav__item.active .nav__item-text {
            color: #16a34a;
            font-weight: 700;
        }

        .nav__item:active .nav__item-icon {
            transform: scale(0.9);
        }

        /* ── Snackbar ──────────────────────────────────────────── */
        #snackbar {
            visibility: hidden;
            min-width: 200px;
            background-color: rgba(31, 28, 28, 0.85);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            color: white;
            text-align: center;
            border-radius: 12px;
            padding: 14px 20px;
            position: fixed;
            z-index: 999999;
            left: 50%;
            bottom: 100px;
            transform: translateX(-50%);
            font-size: 14px;
            font-weight: 500;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }

        #snackbar.show {
            visibility: visible;
            animation: snackbarIn 0.3s ease, snackbarOut 0.3s ease 2.7s;
        }

        #snackbar_error {
            visibility: hidden;
            min-width: 200px;
            background-color: rgba(220, 38, 38, 0.9);
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.2);
            color: white;
            text-align: center;
            border-radius: 12px;
            padding: 14px 20px;
            position: fixed;
            z-index: 999999;
            left: 50%;
            bottom: 100px;
            transform: translateX(-50%);
            font-size: 14px;
            font-weight: 500;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }

        #snackbar_error.show {
            visibility: visible;
            animation: snackbarIn 0.3s ease, snackbarOut 0.3s ease 2.7s;
        }

        @keyframes snackbarIn {
            from { opacity: 0; transform: translateX(-50%) translateY(20px); }
            to { opacity: 1; transform: translateX(-50%) translateY(0); }
        }

        @keyframes snackbarOut {
            from { opacity: 1; transform: translateX(-50%) translateY(0); }
            to { opacity: 0; transform: translateX(-50%) translateY(20px); }
        }

        @media (min-width: 576px) {
            #snackbar, #snackbar_error {
                width: auto;
                max-width: 80%;
            }
        }

        @media (max-width: 575.99px) {
            #snackbar, #snackbar_error {
                width: 80%;
                max-width: 320px;
                font-size: 13px;
            }
        }

        /* ── Preloader ─────────────────────────────────────────── */
        #preloader {
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            z-index: 999999;
            display: none;
            justify-content: center;
            align-items: center;
            color: black;
        }

        #blurred-background {
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            z-index: 999998;
            display: none;
            pointer-events: none;
        }
    </style>

    <title>
         Início
    </title>
    <script>
        document.onreadystatechange = function () {
            var state = document.readyState;
            if (state == "complete") {
                document.getElementById("preloader").style.display = "none";
                document.getElementById("blurred-background").style.display = "none";
            }
        };
    </script>

    <title>
         Início
    </title>

    <!-- end header -->
<style>
    /* ── Component styles ───────────────────────────────────── */
    .aboutus_text { font-size: 14px; }
    .home-banner-text { font-size: 14px; font-weight: 700; color: #2502F9; }
    .home-banner-text-sub { font-size: 10px; font-weight: 400; margin-top: 2px; }
    .bluid h2 { font-family: 'Inter', sans-serif; font-weight: 600; }
    .invest_card_font { font-size: 10px; }
    .invest-card { min-height: 210px; height: auto; padding-bottom: 10px; box-shadow: 0 0 6px rgba(0,0,0,0.25); border-radius: 17px; }
    .invest-plan-text { font-size: 8px; font-weight: 400; color: #000; }
    .extra-label { color: #9400FF; font-size: 18px; font-weight: 700; padding-left: 10px; margin: 8px; }
    .plan-name { font-size: 15px; font-weight: 700; color: #000; }
    .popup-heading { font-size: 11px; font-weight: 400; color: #fff; }
    .popup-price { font-size: 11px; font-weight: 300; color: #fff; }
    .main-heading { font-size: 20px; font-weight: 700; color: #fff; }
    .header-card { background-image: url("../../static/images/background.png"); border-radius: 20px; box-shadow: rgba(0,0,0,0.24) 0 3px 8px; width: 100%; }
    .card { background-color: transparent; border-radius: 5px; width: 100%; border: none; }
    .home-button { color: white; border: none; border-radius: 30px; padding: 10px 20px; cursor: pointer; font-size: 16px; }
    .line { border-top: 1px solid #ddd; margin: 20px 0; }
    .totals { display: flex; justify-content: space-between; align-items: center; }
    .vertical-line { border-left: 1px solid #ddd; height: 40px; margin: 0 10px; width: 1px; }
    .referal-link { background-color: white; line-height: 1.5; overflow: hidden; white-space: nowrap; text-overflow: ellipsis; border-radius: 40px; box-shadow: rgba(0,0,0,0.24) 0 3px 8px; }
    .copy-button { background-color: white; color: white; border: none; border-radius: 5px; padding: 5px; cursor: pointer; }
    .account-card, .service-card, .about-card, .logout-card { box-shadow: rgba(0,0,0,0.24) 0 3px 8px; display: flex; align-items: center; justify-content: space-between; width: 100%; max-height: 60px; padding: 15px; background-color: white; text-decoration: none; color: #000; }
    .card_hover:hover { border-radius: 15px; color: blue; }
    .card-text { margin: 0 10px; flex-grow: 1; font-size: 18px; }
    .balance-amount { color: #FF6F61; font-size: 16px; }
    .home-page-heading { color: white; font-size: 16px; }
    .home-page-heading-white { color: white; font-size: 11px; font-weight: 700; line-height: 11px; margin-top: 10px; }
    .home-page-sub-heading-white { color: #000; font-size: 14px; font-weight: 700; margin-top: 5px; }
    .home-page-sub-heading { color: #FC4C4E; font-size: 14px; }
    .plan-card { width: 175px; height: 100px; background-image: url('https://perview.freelancerawais.online/cyclepro/core/img/linear-bg.webp'); background-size: cover; position: relative; }
    .hex3 { background-image: linear-gradient(to right, #474444, #000); -webkit-clip-path: polygon(25% 5%, 75% 5%, 100% 50%, 75% 95%, 25% 95%, 0 50%); clip-path: polygon(25% 5%, 75% 5%, 100% 50%, 75% 95%, 25% 95%, 0 50%); }
    .banner-container { position: relative; margin: 0 auto; }
    .reward-card { background: rgba(0,0,0,0.16); border-radius: 16px; }
    .daily-reward { width: 72px; height: 56px; text-align: center; border-radius: 10px; background-color: #e4e1e1; box-shadow: 0 0 6px rgba(0,0,0,0.25); }
    .daily-reward-active { background-color: #3a3a3a; width: 72px; height: 56px; text-align: center; border-radius: 10px; box-shadow: 0 0 6px rgba(0,0,0,0.25); }
    .daily-reward-next { width: 156px; height: 56px; text-align: center; border-radius: 10px; background-color: white; box-shadow: 0 0 6px rgba(0,0,0,0.25); }
    .reward-icon { width: 18px; height: 18px; float: right; margin-top: -10px; }
    .about-us-img { border: none; }
    .heading_label { border-radius: 0 0 40px 40px; background-color: #9400FF; height: 176px; }
    .text-overlay { position: absolute; top: 45%; left: 35%; transform: translate(-50%, -50%); text-align: center; }

    @media screen and (max-width: 600px) { .img_responsive { width: 160px; } }
    @media screen and (max-width: 768px) { .aboutus_text { font-size: 11px; } .home-banner { margin-top: 5%; } }
    @media screen and (min-width: 768px) { .container { max-width: 750px; } }
    @media screen and (min-width: 992px) { .container { max-width: 970px; } }
    @media screen and (min-width: 1200px) { .container { max-width: 1170px; } }
</style>






            <div class="main-content-wrapper">
                @yield('content')
            </div>



<div class="navbar-bottom">
        <nav>
            <div class="nav-box p-0 m-0">
                <ul class="nav-container">
                    <li class="nav__item" id="home_div">
                        <a class="nav__item-link" href="{{route ('user.home')}}">
                            <div class="nav__item-icon">
                                <ion-icon><img src="{{asset ('core/img/home-nav.webp')}}" style="height: 20px;" />
                                </ion-icon>
                            </div>
                            <span class="nav__item-text">Início</span>
                        </a>
                    </li>
                    
                    <li class="nav__item" id="produtos_div">
                        <a class="nav__item-link" href="{{route ('user.produtos')}}">
                            <div class="nav__item-icon">
                                <ion-icon><svg fill="#ffffff" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512" style="height: 27px; width: 27px;"><path d="M312 32c-13.3 0-24 10.7-24 24s10.7 24 24 24l25.7 0 34.6 64-149.4 0-27.4-38C191 99.7 183.7 96 176 96l-56 0c-13.3 0-24 10.7-24 24s10.7 24 24 24l43.7 0 22.1 30.7-26.6 53.1c-10-2.5-20.5-3.8-31.2-3.8C57.3 224 0 281.3 0 352s57.3 128 128 128c65.3 0 119.1-48.9 127-112l49 0c8.5 0 16.3-4.5 20.7-11.8l84.8-143.5 21.7 40.1C402.4 276.3 384 312 384 352c0 70.7 57.3 128 128 128s128-57.3 128-128s-57.3-128-128-128c-13.5 0-26.5 2.1-38.7 6L375.4 48.8C369.8 38.4 359 32 347.2 32L312 32zM458.6 303.7l32.3 59.7c6.3 11.7 20.9 16 32.5 9.7s16-20.9 9.7-32.5l-32.3-59.7c3.6-.6 7.4-.9 11.2-.9c39.8 0 72 32.2 72 72s-32.2 72-72 72s-72-32.2-72-72c0-18.6 7-35.5 18.6-48.3zM133.2 368l65 0c-7.3 32.1-36 56-70.2 56c-39.8 0-72-32.2-72-72s32.2-72 72-72c1.7 0 3.4 .1 5.1 .2l-24.2 48.5c-9 18.1 4.1 39.4 24.3 39.4zm33.7-48l50.7-101.3 72.9 101.2-.1 .1-123.5 0zm90.6-128l108.5 0L317 274.8 257.4 192z"/></svg></ion-icon>
                            </div>
                            <span class="nav__item-text">Produtos</span>
                        </a>
                    </li>
                    <li class="nav__item " id="activeplan_div">
                        <a class="nav__item-link" href="{{route ('user.invest.log')}}">
                            <div class="nav__item-icon">
                                <ion-icon><svg fill="#ffffff" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" style="height: 20px; width: 20px;"><path d="M64 64c0-17.7-14.3-32-32-32S0 46.3 0 64L0 400c0 44.2 35.8 80 80 80l400 0c17.7 0 32-14.3 32-32s-14.3-32-32-32L80 416c-8.8 0-16-7.2-16-16L64 64zm406.6 86.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L320 210.7l-57.4-57.4c-12.5-12.5-32.8-12.5-45.3 0l-112 112c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L240 221.3l57.4 57.4c12.5 12.5 32.8 12.5 45.3 0l128-128z"/></svg></ion-icon>
                            </div>
                            <span class="nav__item-text">Investimentos</span>
                        </a>
                    </li>
                    <!-- <li class="nav__item active">
                        <a class="nav__item-link" href="/activePlan/">
                            <div class="nav__item-icon">
                                <ion-icon><img src="/static/images/new/nav-icon3.png" style="height: 20px;" />
                                </ion-icon>                      
                              </div>
                        </a>
                    </li> -->
                    <li class="nav__item" id="team_div">
                        <a class="nav__item-link" href="{{ route('user.referrals') }}">
                            <div class="nav__item-icon">
                                <ion-icon><svg fill="#ffffff" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512" style="height: 22px; width: 22px;"><path d="M144 0a80 80 0 1 1 0 160A80 80 0 1 1 144 0zM512 0a80 80 0 1 1 0 160A80 80 0 1 1 512 0zM0 298.7C0 239.8 47.8 192 106.7 192l42.7 0c15.9 0 31 3.5 44.6 9.7c-1.3 7.2-1.9 14.7-1.9 22.3c0 38.2 16.8 72.5 43.3 96c-.2 0-.4 0-.7 0L21.3 320C9.6 320 0 310.4 0 298.7zM405.3 320c-.2 0-.4 0-.7 0c26.6-23.5 43.3-57.8 43.3-96c0-7.6-.7-15-1.9-22.3c13.6-6.3 28.7-9.7 44.6-9.7l42.7 0C592.2 192 640 239.8 640 298.7c0 11.8-9.6 21.3-21.3 21.3l-213.3 0zM224 224a96 96 0 1 1 192 0 96 96 0 1 1 -192 0zM128 485.3C128 411.7 187.7 352 261.3 352l117.3 0C452.3 352 512 411.7 512 485.3c0 14.7-11.9 26.7-26.7 26.7l-330.7 0c-14.7 0-26.7-11.9-26.7-26.7z"/></svg></ion-icon>
                            </div>
                            <span class="nav__item-text">Equipe</span>
                        </a>
                    </li>
                    <li class="nav__item" id="mine_div">
                        <a class="nav__item-link" href="{{ route('user.profile.setting') }}">
                            <div class="nav__item-icon">
                                <ion-icon><img src="{{asset ('core/img/mine-nav.webp')}}" style="height: 20px;" />
                                </ion-icon>                        </div>
                            <span class="nav__item-text">Perfil</span>
                        </a>
                    </li>
                </ul>
            </div>
        </nav>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const currentUrl = window.location.href;
            document.querySelectorAll('.nav__item').forEach(item => {
                item.classList.remove('active');
            });

            if (currentUrl.includes("{{ route('user.home') }}")) {
                document.getElementById('home_div').classList.add('active');
            } else if (currentUrl.includes("{{ route('user.produtos') }}")) {
                document.getElementById('produtos_div').classList.add('active');
            } else if (currentUrl.includes("{{ route('user.invest.log') }}")) {
                document.getElementById('activeplan_div').classList.add('active');
            } else if (currentUrl.includes("{{ route('user.referrals') }}")) {
                document.getElementById('team_div').classList.add('active');
            } else if (currentUrl.includes("{{ route('user.profile.setting') }}")) {
                document.getElementById('mine_div').classList.add('active');
            }
        });
    </script>

    </div>
@endsection
