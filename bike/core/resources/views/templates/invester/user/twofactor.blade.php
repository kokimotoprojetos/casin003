@extends($activeTemplate.'layouts.master')
@section('content')

    <div class="dashboard-inner">
        <div class="row justify-content-center">
            <div class="@if(!auth()->user()->ts) col-md-12 @else col-md-8 @endif">
                <div class="mb-4">
                    <h3>@lang('Autenticação de dois fatores')</h3>
                    @if(!auth()->user()->ts)
                    <p>@lang('Sua conta ficará mais segura se você usar esse recurso. Um código de verificação de 6 dígitos do seu aplicativo Android Google Authenticator deve ser inserido sempre que alguém tentar fazer login na conta. Para que o sistema possa verificar isso, este é você. Além disso, o procedimento de pagamento exigirá esta verificação.')</p>
                    @else
                    <p>@lang('Se você acha que não precisa da verificação 2FA, você tem uma maneira de desativá-la. Mas antes de desabilitar isso, o sistema avisa que sua conta pode estar correndo risco de segurança. A recomendação do sistema é habilitar a segurança 2FA.')</p>
                    @endif
                </div>
                <div class="row gy-4">

                    @if(!auth()->user()->ts)
                    <div class="col-md-6">
                        <div class="card custom--card">
                            <div class="card-header">
                                <h5 class="mb-0">@lang('Adicione sua conta')</h5>
                            </div>

                            <div class="card-body">
                                <h6 class="mb-3">
                                    @lang('Use o código QR ou a chave de configuração em seu aplicativo Google Authenticator para adicionar sua conta.')
                                </h6>

                                <div class="form-group mb-3 mx-auto text-center">
                                    <img class="mx-auto" src="{{$qrCodeUrl}}">
                                </div>

                                <div class="form-group mb-3">
                                    <label class="form-label">@lang('Chave de configuração')</label>
                                    <div class="copy-link">
                                        <input type="text" class="copyURL" value="{{$secret}}" readonly>
                                        <span class="copyBoard" id="copyBoard"><i class="las la-copy"></i> <strong class="copyText">@lang('Cópia')</strong></span>
                                    </div>
                                </div>

                                <label><i class="fa fa-info-circle"></i> @lang('Ajuda')</label>
                                <p>@lang('O Google Authenticator é um aplicativo multifatorial para dispositivos móveis. Ele gera códigos cronometrados usados ​​durante o processo de verificação em duas etapas. Para usar o Google Authenticator, instale o aplicativo Google Authenticator em seu dispositivo móvel.') <a class="text--base" href="https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2&hl=en" target="_blank">@lang('Download')</a></p>
                            </div>
                        </div>
                    </div>

                    @endif

                    <div class="@if(!auth()->user()->ts) col-md-6 @else col-md-12 @endif">

                        @if(auth()->user()->ts)
                            <div class="card custom--card">
                                <div class="card-header">
                                    <h5 class="mb-0">@lang('Desativar segurança 2FA')</h5>
                                </div>
                                <form action="{{route('user.twofactor.disable')}}" method="POST">
                                    <div class="card-body">
                                        @csrf
                                        <input type="hidden" name="key" value="{{$secret}}">
                                        <div class="form-group mb-3">
                                            <label class="form-label">@lang('Autenticador Google OTP')</label>
                                            <input type="text" class="form-control form--control" name="code" required>
                                        </div>
                                        <button type="submit" class="btn btn--base w-100">@lang('Enviar')</button>
                                    </div>
                                </form>
                            </div>
                        @else
                            <div class="card custom--card">
                                <div class="card-header">
                                    <h5 class="mb-0">@lang('Habilitar segurança 2FA')</h5>
                                </div>
                                <form action="{{ route('user.twofactor.enable') }}" method="POST">
                                    <div class="card-body">
                                        @csrf
                                        <input type="hidden" name="key" value="{{$secret}}">
                                        <div class="form-group mb-3">
                                            <label class="form-label">@lang('Autenticador Google OTP')</label>
                                            <input type="text" class="form-control form--control" name="code" required>
                                        </div>
                                        <button type="submit" class="btn btn--base w-100">@lang('Enviar')</button>
                                    </div>
                                </form>
                            </div>
                        @endif
                    </div>

                </div>
            </div>
        </div>
    </div>

@endsection


@push('script')
    <script>
        (function($){
            "use strict";
            $('#copyBoard').click(function(){
                var copyText = document.getElementsByClassName("copyURL");
                copyText = copyText[0];
                copyText.select();
                copyText.setSelectionRange(0, 99999);
                /*For mobile devices*/
                document.execCommand("copy");
                $('.copyText').text('Copiado!');
                setTimeout(() => {
                    $('.copyText').text('Copiar');
                }, 2000);
            });
        })(jQuery);
    </script>
@endpush
