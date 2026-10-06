@extends($activeTemplate.'layouts.app')
@section('panel')

<!-- Account Section -->
<section class="account-section position-relative">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xl-6 col-lg-7 col-md-8">
                <div class="text-center">
                    <a href="{{ route('home') }}" class="d-block mb-3 mb-sm-4 auth-page-logo"><img src="{{ getImage(getFilePath('logoIcon').'/logo_2.webp') }}" alt="logo"></a>
                </div>
                <form action="{{ route('user.password.email') }}" method="POST" class="account-form">
                    @csrf
                    <div class="mb-4">
                        <h4 class="mb-2">{{ __($pageTitle) }}</h4>
                        <p>@lang('Para recuperar sua conta, forneça seu e-mail ou nome de usuário para encontrar sua conta.')</p>
                    </div>
                    <div class="row gy-2 gap-2">
                        <div class="col-12">
                            <div class="form-group">
                                <label>@lang('E-mail ou nome de usuário')</label>
                                <input type="text" class="form-control form--control h-45" name="value" value="{{ old('value') }}" required autofocus="off">
                            </div>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn--base w-100">@lang('Enviar')</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>
<!-- Account Section -->

@endsection
