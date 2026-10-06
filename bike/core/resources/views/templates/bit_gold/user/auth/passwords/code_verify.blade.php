@extends($activeTemplate.'layouts.frontend')
@section('content')
<section class="pt-120 pb-120">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-7 col-xl-5">
                <div class="d-flex justify-content-center">
                    <div class="verification-code-wrapper">
                        <div class="verification-area">
                            <form action="{{ route('user.password.verify.code') }}" method="POST" class="submit-form">
                                @csrf
                                <p class="verification-text">@lang('Um código de verificação de 6 dígitos enviado para seu endereço de e-mail') :  {{ showEmailAddress($email) }}</p>
                                <input type="hidden" name="email" value="{{ $email }}">

                                @include($activeTemplate.'partials.verification_code')

                                <div class="form-group">
                                    <button type="submit" class="btn--base w-100">@lang('Enviar')</button>
                                </div>

                                <div class="form-group">
                                    @lang('Por favor, verifique incluindo sua pasta de lixo eletrônico/spam. se não for encontrado, você pode')
                                    <a href="{{ route('user.password.request') }}">@lang('Tente enviar novamente')</a>
                                </div>

                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

