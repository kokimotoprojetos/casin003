@extends($activeTemplate.'layouts.master')
@section('content')

    <div class="dashboard-inner">
        <div class="mb-4">
            <h3>@lang('Envio KYC')</h3>
            <p>@lang('O sistema exige que você envie informações KYC (conheça seu cliente). Seus dados enviados serão verificados pelo administrador do sistema. Se todas as suas informações estiverem corretas, o administrador aprovará os dados KYC e você poderá fazer solicitações de saque') @if($general->b_transfer) @lang('e transferir dinheiro para outros usuários') @endif.</p>
        </div>
        <div class="row">
            <div class="col-12">
                <div class="card custom--card">
                    <div class="card-body">
                        <form action="{{route('user.kyc.submit')}}" method="post" enctype="multipart/form-data">
                            @csrf

                            <x-viser-form identifier="act" identifierValue="kyc" />

                            <div class="form-group">
                                <button type="submit" class="btn btn--base w-100">@lang('Enviar')</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>


@endsection
@push('style')
    <style>
        .form-group{
            margin-bottom: 12px;
        }
    </style>
@endpush
