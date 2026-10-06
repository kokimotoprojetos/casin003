@extends($activeTemplate.'layouts.master')
@section('content')
<div class="pt-150 pb-150">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-4 mb-30">

                <div class="card card-bg">
                    <div class="card-body">
                        <h4 class="mb-2">{{ $user->fullname }}</h4>
                        <ul class="list-group">

                            <li class="list-group-item d-flex justify-content-between align-items-center">
                               <span><i class="las la-user base--color"></i> @lang('Nome do usuário')</span> <span class="fw-bold">{{ $user->username }}</span>
                            </li>

                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span><i class="las la-envelope base--color"></i> @lang('E-mail')</span> <span class="fw-bold">{{ $user->email }}</span>
                            </li>

                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span><i class="las la-phone base--color"></i> @lang('Móvel')</span> <span class="fw-bold">{{ $user->mobile }}</span>
                            </li>

                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span><i class="las la-globe base--color"></i> @lang('país')</span> <span class="fw-bold">{{ $user->address->country }}</span>
                            </li>

                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="card card-bg">
                    <div class="card-body">
                        <form class="register" action="" method="post">
                            @csrf
                            <div class="row">
                                <div class="form-group col-sm-6">
                                    <label class="form-label">@lang('Primeiro nome')</label>
                                    <input type="text"  name="firstname" value="{{$user->firstname}}" required>
                                </div>
                                <div class="form-group col-sm-6">
                                    <label class="form-label">@lang('Sobrenome')</label>
                                    <input type="text"  name="lastname" value="{{$user->lastname}}" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group col-sm-6">
                                    <label class="form-label">@lang('Endereço')</label>
                                    <input type="text"  name="address" value="{{@$user->address->address}}">
                                </div>
                                <div class="form-group col-sm-6">
                                    <label class="form-label">@lang('Estado')</label>
                                    <input type="text"  name="state" value="{{@$user->address->state}}">
                                </div>
                            </div>


                            <div class="row">
                                <div class="form-group col-sm-6">
                                    <label class="form-label">@lang('CEP')</label>
                                    <input type="text"  name="zip" value="{{@$user->address->zip}}">
                                </div>

                                <div class="form-group col-sm-6">
                                    <label class="form-label">@lang('Cidade')</label>
                                    <input type="text"  name="city" value="{{@$user->address->city}}">
                                </div>

                            </div>

                            <div class="form-group">
                                <button type="submit" class="btn btn-sm btn-primary w-100">@lang('Enviar')</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
