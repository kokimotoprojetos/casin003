@extends($activeTemplate . 'layouts.app')
@section('panel')
    @php
        $authContent = getContent('authentication.content', true);
    @endphp

    <style>
        *, *::before, *::after {
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
        }

        html, body {
            width: 100%;
            max-width: 100vw;
            min-height: 100vh;
            min-height: 100dvh;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
            touch-action: pan-y;
            font-family: 'Inter', sans-serif;
            background-color: #1a1a1a;
        }

        .auth-bg {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            width: 100vw;
            height: 100vh;
            height: 100dvh;
            background-image: url('{{ asset('core/css/static/images/new/fundodeloguind.webp') }}?v={{ time() }}');
            background-size: cover;
            background-repeat: no-repeat;
            background-position: center center;
            z-index: 1;
            pointer-events: none;
            -webkit-transform: translate3d(0, 0, 0);
            transform: translate3d(0, 0, 0);
        }

        body {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 30px 20px 40px;
            position: relative;
        }

        .auth-wrapper {
            position: relative;
            z-index: 2;
            width: 100%;
            max-width: 360px;
            margin: auto;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .auth-logo {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 100%;
            margin-bottom: 12px;
        }

        .auth-logo img {
            width: 200px;
            max-width: 65vw;
            height: auto;
            display: block;
            filter: drop-shadow(0 4px 10px rgba(0, 0, 0, 0.3));
        }

        .auth-title {
            color: #ffffff;
            font-size: 24px;
            font-weight: 700;
            text-align: center;
            width: 100%;
            margin: 0 0 24px 0;
            text-shadow: 0 2px 8px rgba(0, 0, 0, 0.5);
            letter-spacing: -0.3px;
        }

        .auth-form {
            width: 100%;
            display: flex;
            flex-direction: column;
            gap: 16px;
            margin: 0;
            padding: 0;
        }

        .auth-input-group {
            display: flex;
            align-items: center;
            position: relative;
            background: rgba(255, 255, 255, 0.22);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-radius: 30px;
            width: 100%;
            box-sizing: border-box;
            border: 1px solid rgba(255, 255, 255, 0.45);
            height: 52px;
            padding: 0 18px;
            gap: 12px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .auth-input-group:focus-within {
            border-color: #22c55e;
            box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.25);
        }

        .auth-input-group svg {
            flex-shrink: 0;
        }

        .auth-input-group input {
            flex: 1;
            min-width: 0;
            color: #ffffff !important;
            background: transparent !important;
            border: none !important;
            outline: none !important;
            font-size: 15px;
            height: 100%;
            padding: 0;
            margin: 0;
            font-family: 'Inter', sans-serif;
        }

        .auth-input-group input::placeholder {
            color: rgba(255, 255, 255, 0.75) !important;
            opacity: 1;
        }

        .no-arrow {
            -moz-appearance: textfield;
        }

        .no-arrow::-webkit-inner-spin-button,
        .no-arrow::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        .auth-btn {
            border-radius: 30px;
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
            width: 100%;
            box-sizing: border-box;
            color: #ffffff;
            font-size: 17px;
            font-weight: 800;
            border: none;
            margin-top: 10px;
            height: 54px;
            box-shadow: 0 6px 20px rgba(22, 163, 74, 0.45);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-family: 'Inter', sans-serif;
            text-shadow: 0px 1px 2px rgba(0, 0, 0, 0.2);
            transition: transform 0.15s, opacity 0.15s;
        }

        .auth-btn:active {
            transform: scale(0.98);
            opacity: 0.9;
        }

        .auth-footer {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: 16px;
            width: 100%;
            text-align: center;
        }

        .auth-footer a {
            text-decoration: none;
            color: #ffffff;
            font-size: 13px;
            font-weight: 500;
        }

        .auth-footer a span {
            color: #22c55e;
            font-weight: 700;
            margin-left: 4px;
        }
    </style>

    <div class="auth-bg"></div>

    <div class="auth-wrapper">
        <div class="auth-logo">
            <img src="{{ asset(getImage(getFilePath('logoIcon').'/logo.webp')) }}?v={{ time() }}" alt="Wind Bikes Logo">
        </div>
        <div class="auth-title">
            Cadastrar
        </div>

        <form method="POST" action="{{ route('user.register') }}" id="signupForm" class="auth-form">
            @csrf

            <div class="auth-input-group">
                <svg fill="#22c55e" viewBox="0 0 512 512" style="width: 20px; height: 20px;"><path d="M164.9 24.6c-7.7-18.6-28-28.5-47.4-23.2l-88 24C12.1 30.2 0 46 0 64C0 311.4 200.6 512 448 512c18 0 33.8-12.1 38.6-29.5l24-88c5.3-19.4-4.6-39.7-23.2-47.4l-96-40c-16.3-6.8-35.2-2.1-46.3 11.6L304.7 368C234.3 334.7 177.3 277.7 144 207.3L193.3 167c13.7-11.2 18.4-30 11.6-46.3l-40-96z"/></svg>
                <input type="number" name="username" class="no-arrow" id="phone" placeholder="Telefone" required="" maxlength="11" oninput="if(this.value.length>11)this.value=this.value.slice(0,11)">
            </div>

            <div class="auth-input-group">
                <svg fill="#22c55e" viewBox="0 0 448 512" style="width: 17px; height: 17px;"><path d="M144 144v48H304V144c0-44.2-35.8-80-80-80s-80 35.8-80 80zM80 192V144C80 64.5 144.5 0 224 0s144 64.5 144 144v48h16c26.5 0 48 21.5 48 48V464c0 26.5-21.5 48-48 48H64c-26.5 0-48-21.5-48-48V240c0-26.5 21.5-48 48-48H80z"/></svg>
                <input type="password" name="password" id="password" minlength="8" placeholder="Senha" required="">
                <div onclick="let p=document.getElementById('password'); p.type=p.type==='password'?'text':'password';" style="cursor: pointer; flex-shrink: 0; display: flex; align-items: center;">
                    <svg fill="#22c55e" viewBox="0 0 576 512" style="width: 20px; height: 20px;"><path d="M288 32c-80.8 0-145.5 36.8-192.6 80.6C48.6 156 17.3 208 2.5 243.7c-3.3 7.9-3.3 16.7 0 24.6C17.3 304 48.6 356 95.4 399.4C142.5 443.2 207.2 480 288 480s145.5-36.8 192.6-80.6c46.8-43.5 78.1-95.4 92.9-131.1c3.3-7.9 3.3-16.7 0-24.6c-14.8-35.7-46.1-87.7-92.9-131.1C433.5 68.8 368.8 32 288 32zM128 256a160 160 0 1 1 320 0 160 160 0 1 1 -320 0zm160-80a80 80 0 1 0 0 160 80 80 0 1 0 0-160z"/></svg>
                </div>
            </div>

            <div class="auth-input-group">
                <svg fill="#22c55e" viewBox="0 0 512 512" style="width: 19px; height: 19px;"><path d="M190.5 68.8L225.3 128H224 152c-22.1 0-40-17.9-40-40s17.9-40 40-40h2.2c14.9 0 28.8 7.9 36.3 20.8zM64 88c0 14.4 3.5 28 9.6 40H32c-17.7 0-32 14.3-32 32v64c0 17.7 14.3 32 32 32H480c17.7 0 32-14.3 32-32V160c0-17.7-14.3-32-32-32H438.4c6.1-12 9.6-25.6 9.6-40c0-48.6-39.4-88-88-88h-2.2c-31.9 0-61.5 16.9-77.7 44.4L256 85.5l-24.1-41C215.7 16.9 186.1 0 154.2 0H152C103.4 0 64 39.4 64 88zm336 0c0 22.1-17.9 40-40 40H288h-1.3l34.8-59.2C329.1 55.9 342.9 48 357.8 48H360c22.1 0 40 17.9 40 40zM32 288V464c0 26.5 21.5 48 48 48H224V288H32zm256 0V512H432c26.5 0 48-21.5 48-48V288H288z"/></svg>
                <input class="no-arrow" type="number" name="reference" placeholder="Código de Convite" value="{{ session()->get('reference') }}">
            </div>

            <button type="submit" class="auth-btn">
                Cadastrar
            </button>

            <div class="auth-footer">
                <a href="{{ route('user.login') }}">
                    Já tem uma conta? <span>Entrar</span>
                </a>
            </div>
        </form>
    </div>
@endsection

@if ($general->secure_password)
    @push('script-lib')
        <script src="{{ asset('assets/global/js/secure_password.js') }}"></script>
    @endpush
@endif

@push('script')
    <script>
        "use strict";
        (function($) {
            @if ($mobileCode)
                $(`option[data-code={{ $mobileCode }}]`).attr('selected', '');
            @endif
            $('select[name=country]').change(function() {
                $('input[name=mobile_code]').val($('select[name=country] :selected').data('mobile_code'));
                $('input[name=country_code]').val($('select[name=country] :selected').data('code'));
                $('.mobile-code').text('+' + $('select[name=country] :selected').data('mobile_code'));
            });
            $('input[name=mobile_code]').val($('select[name=country] :selected').data('mobile_code'));
            $('input[name=country_code]').val($('select[name=country] :selected').data('code'));
            $('.mobile-code').text('+' + $('select[name=country] :selected').data('mobile_code'));
            $('.checkUser').on('focusout', function(e) {
                var url = '{{ route('user.checkUser') }}';
                var value = $(this).val();
                var token = '{{ csrf_token() }}';
                if ($(this).attr('name') == 'mobile') {
                    var mobile = `${$('.mobile-code').text().substr(1)}${value}`;
                    var data = {
                        mobile: mobile,
                        _token: token
                    }
                }
                if ($(this).attr('name') == 'email') {
                    var data = {
                        email: value,
                        _token: token
                    }
                }
                if ($(this).attr('name') == 'username') {
                    var data = {
                        username: value,
                        _token: token
                    }
                }
                $.post(url, data, function(response) {
                    if (response.data != false && response.type == 'email') {
                        $('#existModalCenter').modal('show');
                    } else if (response.data != false) {
                        $(`.${response.type}Exist`).text(`Este ${response.type} já está em uso`);
                    } else {
                        $(`.${response.type}Exist`).text('');
                    }
                });
            });
        })(jQuery);
    </script>
@endpush
