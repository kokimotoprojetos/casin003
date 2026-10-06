@extends($activeTemplate.'layouts.master')
@section('content')

    <div class="dashboard-inner">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="mb-4">
                    <h3 class="mb-2">@lang('Saldo de transferência')</h3>
                    <p>@lang('Você pode transferir o saldo para outro usuário de ambas as carteiras. O valor transferido será adicionado à carteira de depósitos do usuário alvo.')</p>
                </div>
                <div class="card custom--card">
                    <form action="" method="post" enctype="multipart/form-data">
                        @csrf
                        <div class="card-body">
                            <div class="form-group">
                                <label>@lang('Carteira')</label>
                                <select class="form-control form--control form-select" name="wallet">
                                    <option value="">@lang('Selecione uma carteira')</option>
                                    <option value="interest_wallet">@lang('Carteira de juros') - {{ showAmount($user->interest_wallet) }} {{ $general->cur_text }}</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>@lang('Nome de usuário')</label>
                                <input type="text" name="username" class="form-control form--control findUser" required>
                                <code class="error-message"></code>
                            </div>
                            <div class="form-group">
                                <label>@lang('Quantia') <small class="text--success">(@lang('Cobrar'): {{ getAmount($general->f_charge) }} {{ $general->cur_text }} + {{ getAmount($general->p_charge) }}%)</small></label>
                                <div class="input-group">
                                    <input type="number" step="any" autocomplete="off" name="amount" class="form-control form--control" required>
                                    <span class="input-group-text">{{ $general->cur_text }}</span>
                                </div>
                                <small><code class="calculation"></code></small>
                            </div>

                            @if(auth()->user()->ts)
                            <div class="form-group">
                                <label>@lang('Código do autenticador do Google')</label>
                                <input type="text" name="authenticator_code" class="form-control form--control" required>
                            </div>
                            @endif


                            <div class="form-group mt-3">
                                <button type="submit" class="btn btn--base w-100">@lang('Enviar')</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection
@push('script')
<script>
    $('input[name=amount]').on('input',function(){
        var amo = parseFloat($(this).val());
        var calculation = amo + ( parseFloat({{ $general->f_charge }}) + ( amo * parseFloat({{ $general->p_charge }}) ) / 100 );
        if (calculation) {
            $('.calculation').text(calculation+' {{ $general->cur_text }} será descontado da carteira selecionada');
        }else{
            $('.calculation').text('');
        }
    });

    $('.findUser').on('focusout',function(e){
        var url = '{{ route('user.findUser') }}';
        var value = $(this).val();
        var token = '{{ csrf_token() }}';

        var data = {username:value,_token:token}
        $.post(url,data,function(response) {
            if (response.message) {
                $('.error-message').text(response.message);
            }else{
                $('.error-message').text('');
            }
        });
    });
</script>
@endpush
