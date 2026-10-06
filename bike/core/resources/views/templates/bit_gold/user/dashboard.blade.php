@extends($activeTemplate.'layouts.master')
@section('content')
<div class="pb-60 pt-60">
    <div class="container">

        <div class="row notice"></div>
        
        <div class="row justify-content-center">
            <div class="col-md-12">
                @if ($user->deposit_wallet <= 0 && $user->interest_wallet <= 0)
                    <div class="alert border border--danger" role="alert">
                        <div class="alert__icon d-flex align-items-center text--danger"><i
                                class="fas fa-exclamation-triangle"></i></div>
                        <p class="alert__message">
                            <span class="fw-bold">@lang('Saldo Vazio')</span><br>
                            <small><i>@lang('Seu saldo está vazio. Por favor faça') <a href="{{ route('user.deposit.index') }}"
                                        class="link-color">@lang('depósito')</a> @lang('para o seu próximo investimento.')</i></small>
                        </p>
                    </div>
                @endif

                @if ($user->deposits->where('status',1)->count() == 1 && !$user->invests->count())
                    <div class="alert border border--success" role="alert">
                        <div class="alert__icon d-flex align-items-center text--success"><i class="fas fa-check"></i>
                        </div>
                        <p class="alert__message">
                            <span class="fw-bold">@lang('Primeiro Depósito')</span><br>
                            <small><i><span class="fw-bold">@lang('Parabéns!')</span> @lang('Você fez seu primeiro depósito com sucesso. Vá para') <a
                                        href="{{ route('plan') }}" class="link-color">@lang('plano de investimento')</a>
                                    @lang('página e invista agora')</i></small>
                        </p>
                    </div>
                @endif

                @if ($pendingWithdrawals)
                    <div class="alert border border--primary" role="alert">
                        <div class="alert__icon d-flex align-items-center text--primary"><i class="fas fa-spinner"></i>
                        </div>
                        <p class="alert__message">
                            <span class="fw-bold">@lang('Retirada Pendente')</span><br>
                            <small><i>@lang('Total') {{ showAmount($pendingWithdrawals) }} {{ $general->cur_text }}
                                    @lang('solicitação de retirada está pendente. Aguarde a aprovação do administrador. O valor será enviado para a conta que você forneceu. Ver') <a href="{{ route('user.withdraw.history') }}"
                                        class="link-color">@lang('histórico de retirada')</a></i></small>
                        </p>
                    </div>
                @endif

                @if ($pendingDeposits)
                    <div class="alert border border--primary" role="alert">
                        <div class="alert__icon d-flex align-items-center text--primary"><i class="fas fa-spinner"></i>
                        </div>
                        <p class="alert__message">
                            <span class="fw-bold">@lang('Depósito pendente')</span><br>
                            <small><i>@lang('Total') {{ showAmount($pendingDeposits) }} {{ $general->cur_text }}
                                    @lang('solicitação de depósito está pendente. Aguarde a aprovação do administrador. Ver') <a href="{{ route('user.deposit.history') }}"
                                        class="link-color">@lang('histórico de depósitos')</a></i></small>
                        </p>
                    </div>
                @endif

                @if (!$user->ts)
                    <div class="alert border border--warning" role="alert">
                        <div class="alert__icon d-flex align-items-center text--warning"><i
                                class="fas fa-user-lock"></i></div>
                        <p class="alert__message">
                            <span class="fw-bold">@lang('Autenticação 2FA')</span><br>
                            <small><i>@lang('Para manter sua conta segura, ative') <a href="{{ route('user.twofactor') }}"
                                        class="link-color">@lang('2FA')</a> @lang('segurança').</i>
                                @lang('Isso tornará sua conta e saldo seguros.')</small>
                        </p>
                    </div>
                @endif

                @if ($isHoliday)
                    <div class="alert border border--info" role="alert">
                        <div class="alert__icon d-flex align-items-center text--info"><i class="fas fa-toggle-off"></i>
                        </div>
                        <p class="alert__message">
                            <span class="fw-bold">@lang('Feriado')</span><br>
                            <small><i>@lang('Hoje é feriado neste sistema. Você não obterá nenhum interesse hoje neste sistema. Além disso, você não pode fazer solicitação de saque hoje.') <br> @lang('O próximo dia útil virá depois') <span id="counter"
                                        class="fw-bold text--primary fs--15px"></span></i></small>
                        </p>
                    </div>
                @endif

                @if ($user->kv == 0)
                    <div class="alert border border--info" role="alert">
                        <div class="alert__icon d-flex align-items-center text--info"><i class="fas fa-file-signature"></i>
                        </div>
                        <p class="alert__message">
                            <span class="fw-bold">@lang('Verificação KYC necessária')</span><br>
                            <small><i>@lang('Envie as informações KYC necessárias para verificar você mesmo. Caso contrário, você não poderá fazer nenhuma solicitação de saque ao sistema.') <a href="{{ route('user.kyc.form') }}"
                                        class="link-color">@lang('Clique aqui')</a> @lang('para enviar informações KYC').</i></small>
                        </p>
                    </div>
                @elseif($user->kv == 2)
                    <div class="alert border border--warning" role="alert">
                        <div class="alert__icon d-flex align-items-center text--warning"><i
                                class="fas fa-user-check"></i></div>
                        <p class="alert__message">
                            <span class="fw-bold">@lang('Verificação KYC pendente')</span><br>
                            <small><i>@lang('Suas informações KYC enviadas estão pendentes para aprovação do administrador. Por favor, espere até isso.') <a href="{{ route('user.kyc.data') }}"
                                        class="link-color">@lang('Clique aqui')</a> @lang('para ver as informações enviadas')</i></small>
                        </p>
                    </div>
                @endif
            </div>
        </div>
        <div class="row justify-content-center">
            <div class="col-lg-12 mt-lg-0 mt-5">
                <div class="row mb-none-30">
                    <div class="col-xl-4 col-sm-6 mb-30">
                        <div class="d-widget d-flex justify-content-between gap-5">
                            <div class="left-content">
                                <span class="caption">@lang('Depositar Saldo da Carteira')</span>
                                <h4 class="currency-amount">{{ $general->cur_sym }}{{ getAmount($user->deposit_wallet) }}</h4>
                            </div>
                            <div class="icon ms-auto">
                                <i class="las la-dollar-sign"></i>
                            </div>
                        </div><!-- d-widget-two end -->
                    </div>
                    <div class="col-xl-4 col-sm-6 mb-30">
                        <div class="d-widget d-flex justify-content-between gap-5">
                            <div class="left-content">
                                <span class="caption">@lang('Saldo da carteira de juros')</span>
                                <h4 class="currency-amount">
                                    {{ $general->cur_sym }}{{ getAmount($user->interest_wallet) }}</h4>
                            </div>
                            <div class="icon ms-auto">
                                <i class="las la-wallet"></i>
                            </div>
                        </div><!-- d-widget-two end -->
                    </div>
                    <div class="col-xl-4 col-sm-6 mb-30">
                        <div class="d-widget d-flex justify-content-between gap-5">
                            <div class="left-content">
                                <span class="caption">@lang('Investimento Total')</span>
                                <h4 class="currency-amount">
                                    {{ $general->cur_sym }}{{ getAmount($totalInvest) }}
                                </h4>
                            </div>
                            <div class="icon ms-auto">
                                <i class="las la-cubes "></i>
                            </div>
                        </div><!-- d-widget-two end -->
                    </div>
                    <div class="col-xl-4 col-sm-6 mb-30">
                        <div class="d-widget d-flex justify-content-between gap-5">
                            <div class="left-content">
                                <span class="caption">@lang('Depósito total')</span>
                                <h4 class="currency-amount">
                                    {{ $general->cur_sym }}{{ getAmount($user->deposits->where('status',1)->sum('amount')) }}
                                </h4>
                            </div>
                            <div class="icon ms-auto">
                                <i class="las la-credit-card"></i>
                            </div>
                        </div><!-- d-widget-two end -->
                    </div>
                    <div class="col-xl-4 col-sm-6 mb-30">
                        <div class="d-widget d-flex justify-content-between gap-5">
                            <div class="left-content">
                                <span class="caption">@lang('Retirada total')</span>
                                <h4 class="currency-amount">
                                    {{ $general->cur_sym }}{{ getAmount($user->withdrawals->where('status',1)->sum('amount')) }}
                                </h4>
                            </div>
                            <div class="icon ms-auto">
                                <i class="las la-cloud-download-alt"></i>
                            </div>
                        </div><!-- d-widget-two end -->
                    </div>
                    <div class="col-xl-4 col-sm-6 mb-30">
                        <div class="d-widget d-flex justify-content-between gap-5">
                            <div class="left-content">
                                <span class="caption">@lang('Ganhos por indicação')</span>
                                <h4 class="currency-amount">
                                    {{ $general->cur_sym }}{{ showAmount($referral_earnings) }}
                                </h4>
                            </div>
                            <div class="icon ms-auto">
                                <i class="las la-user-friends"></i>
                            </div>
                        </div><!-- d-widget-two end -->
                    </div>
                </div><!-- row end -->
               
            </div>
        </div>
    </div>
</div>
@endsection
@push('style')
    <style>
        #copyBoard {
            cursor: pointer;
        }

    </style>
@endpush

@push('script')
 <script>
        'use strict';
        (function ($) {
            @if($isHoliday)
                function createCountDown(elementId, sec) {
                    var tms = sec;
                    var x = setInterval(function () {
                        var distance = tms * 1000;
                        var days = Math.floor(distance / (1000 * 60 * 60 * 24));
                        var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                        var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                        var seconds = Math.floor((distance % (1000 * 60)) / 1000);
                        var days = `<span>${days}d</span>`;
                        var hours = `<span>${hours}h</span>`;
                        var minutes = `<span>${minutes}m</span>`;
                        var seconds = `<span>${seconds}s</span>`;
                        document.getElementById(elementId).innerHTML = days +' '+ hours + " " + minutes + " " + seconds;
                        if (distance < 0) {
                            clearInterval(x);
                            document.getElementById(elementId).innerHTML = "COMPLETE";
                        }
                        tms--;
                    }, 1000);
                }

                createCountDown('counter', {{\Carbon\Carbon::parse($nextWorkingDay)->diffInSeconds()}});
            @endif
        })(jQuery);
 </script>
@endpush