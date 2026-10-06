@extends($activeTemplate.'layouts.master')
@section('content')
<div class="cmn-section">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card card-deposit text-center">
                    <div class="card-header card-header-bg">
                        <h3>@lang('Pré-visualização de pagamento')</h3>
                    </div>
                    <div class="card-body card-body-deposit text-center">
                        <h4 class="my-2"> @lang('POR FAVOR ENVIE EXATAMENTE') <span class="text-success"> {{ $data->amount }}</span> {{__($data->currency)}}</h4>
                        <h5 class="mb-2">@lang('PARA') <span class="text-success"> {{ $data->sendto }}</span></h5>
                        <img src="{{$data->img}}" alt="@lang('Imagem')">
                        <h4 class="text-white bold my-4">@lang('DIGITALIZAR PARA ENVIAR')</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
