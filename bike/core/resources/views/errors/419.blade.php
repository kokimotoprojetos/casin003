<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ $general->siteName($pageTitle ?? '419 | Sessão expirada') }}</title>
  <link rel="stylesheet" href="{{ asset('assets/global/css/bootstrap.min.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/errors/css/main.css') }}">
  <script>
    setTimeout(function() {
      window.location.href = '{{ route("user.login") }}';
    }, 3000);
  </script>
</head>
  <body>
  <div class="error">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-lg-7 text-center">
          <img src="{{ asset('assets/errors/images/error-419.webp') }}" alt="@lang('imagem')">
          <h2 class="card-title"><b>@lang('419')</b> @lang('Desculpe, sua sessão expirou.')</h2>
          <p>@lang('Redirecionando para o login em 3 segundos...')</p>
          <a href="{{ route('user.login') }}" class="cmn-btn mt-4">@lang('Ir para o login agora')</a>
        </div>
      </div>
    </div>
  </div>
  </body>
</html>
