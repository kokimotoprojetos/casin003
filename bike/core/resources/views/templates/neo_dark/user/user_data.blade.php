@extends($activeTemplate.'layouts.frontend')
@section('content')
<section class="pt-150 pb-150">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-7 col-xl-6">
                <div class="card card-bg">
                    <div class="card-header">
                        <h5 class="title">{{ __($pageTitle) }}</h5>
                    </div>

                    <div class="card-body">
                        <form method="POST" action="{{ route('user.data.submit') }}">
                            @csrf
                            <div class="row">
                                <div class="form-group col-sm-6">
                                    <label class="form-label">@lang('Primeiro nome')</label>
                                    <input type="text" class="form-control form--control" name="firstname" value="{{ old('firstname') }}" required>
                                </div>

                                <div class="form-group col-sm-6">
                                    <label class="form-label">@lang('Sobrenome')</label>
                                    <input type="text" class="form-control form--control" name="lastname" value="{{ old('lastname') }}" required>
                                </div>
                                <div class="form-group col-sm-6">
                                    <label class="form-label">@lang('Endereço')</label>
                                    <input type="text" class="form-control form--control" name="address" value="{{ old('address') }}">
                                </div>
                                <div class="form-group col-sm-6">
                                    <label class="form-label">@lang('Estado')</label>
                                    <input type="text" class="form-control form--control" name="state" value="{{ old('state') }}">
                                </div>
                                <div class="form-group col-sm-6">
                                    <label class="form-label">@lang('CEP')</label>
                                    <input type="text" class="form-control form--control" name="zip" value="{{ old('zip') }}">
                                </div>

                                <div class="form-group col-sm-6">
                                    <label class="form-label">@lang('Cidade')</label>
                                    <input type="text" class="form-control form--control" name="city" value="{{ old('city') }}">
                                </div>
                            </div>
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary w-100">
                                    @lang('Enviar')
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
