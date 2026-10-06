@extends($activeTemplate.'layouts.master')
@section('content')

    <div class="dashboard-inner">
        <div class="mb-4">
            <div class="row">
                <div class="col-md-8">
                    <h3>@lang('Dados KYC')</h3>
                    <p>@lang('As informações KYC enviadas são mostradas abaixo. Não foi possível alterar os dados enviados. Se o administrador rejeitar suas informações, você poderá reenviá-las.')</p>
                </div>
            </div>
        </div>
        <div class="card custom--card">
            <div class="card-body">
                @if($user->kyc_data)
                <ul class="list-group">
                  @foreach($user->kyc_data as $val)
                  @continue(!$val->value)
                  <li class="list-group-item d-flex justify-content-between align-items-center">
                    {{__($val->name)}}
                    <span>
                        @if($val->type == 'checkbox')
                            {{ implode(',',$val->value) }}
                        @elseif($val->type == 'file')
                            <a href="{{ route('user.attachment.download',encrypt(getFilePath('verify').'/'.$val->value)) }}" class="me-3"><i class="fa fa-file"></i>  @lang('Anexo') </a>
                        @else
                        <p>{{__($val->value)}}</p>
                        @endif
                    </span>
                  </li>
                  @endforeach
                </ul>
                @else
                <h5 class="text-center">@lang('Dados KYC não encontrados')</h5>
                @endif
            </div>
        </div>
    </div>

@endsection
