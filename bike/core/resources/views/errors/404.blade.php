<!-- meta tags and other links -->
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ $general->siteName($pageTitle ?? '404 | page not found') }}</title>
  <!-- bootstrap 4  -->
  <link rel="stylesheet" href="{{ asset('assets/global/css/bootstrap.min.css') }}">
  <!-- dashdoard main css -->
  <link rel="stylesheet" href="{{ asset('assets/errors/css/main.css') }}">
</head>
  <body>


  <!-- error-404 start -->
  <div class="error">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-lg-7 text-center">
          <img src="{{ asset('assets/errors/images/error-404.webp') }}" alt="@lang('imagem')">
          <h2><b>@lang('404')</b> @lang('Página não encontrada')</h2>
          <p>@lang('page you are looking for doesn\'t exit or an other error ocurred') <br> @lang('ou temporariamente indisponível.')</p>
          <a href="{{ route('home') }}" class="cmn-btn mt-4">@lang('Vá para casa')</a>
        </div>
      </div>
    </div>
  </div>
  <!-- error-404 end -->


  </body>
</html>
