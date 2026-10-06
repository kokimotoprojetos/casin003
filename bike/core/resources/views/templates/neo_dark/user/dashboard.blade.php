@extends($activeTemplate . 'layouts.master')
@section('content')
    <section class="dashboard-section pt-120 pb-120">
        <div class="container">
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

                    @if ($user->deposits->where('status', 1)->count() == 1 && !$user->invests->count())
                        <div class="alert border border--success" role="alert">
                            <div class="alert__icon d-flex align-items-center text--success"><i class="fas fa-check"></i>
                            </div>
                            <p class="alert__message">
                                <span class="fw-bold">@lang('Primeiro Depósito')</span><br>
                                <small><i><span class="fw-bold">@lang('Parabéns!')</span> @lang('Você fez seu primeiro depósito com sucesso. Vá para') <a
                                            href="{{ route('plan') }}" class="link-color">@lang('plano de investimento')</a>
                                        @lang('página e revista agora')</i></small>
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
                                        @lang('O pedido de retirada está pendente. Aguarde a aprovação do administrador. O valor será enviado para a conta que você compareceu. Ver') <a href="{{ route('user.withdraw.history') }}"
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
                                        @lang('o pedido de depósito está pendente. Aguarde a aprovação do administrador. Ver') <a href="{{ route('user.deposit.history') }}"
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
                                <small><i>@lang('Para manter sua conta segura, ativa') <a href="{{ route('user.twofactor') }}"
                                            class="link-color">@lang('2FA')</a> @lang('segurança').</i>
                                    @lang('Isso garantirá sua conta e saldo seguro.')</small>
                            </p>
                        </div>
                    @endif

                    @if ($isHoliday)
                        <div class="alert border border--info" role="alert">
                            <div class="alert__icon d-flex align-items-center text--info"><i class="fas fa-toggle-off"></i>
                            </div>
                            <p class="alert__message">
                                <span class="fw-bold">@lang('Feriado')</span><br>
                                <small><i>@lang('Hoje é feriado neste sistema. Você não obterá nenhum interesse hoje neste sistema. Além disso, você não pode fazer pedido de saque hoje.') <br> @lang('O próximo dia útil virá depois') <span id="counter" class="fw-bold text--base fs--15px"></span></i></small>
                            </p>
                        </div>
                    @endif

                    @if ($user->kv == 0)
                        <div class="alert border border--info" role="alert">
                            <div class="alert__icon d-flex align-items-center text--info"><i
                                    class="fas fa-file-signature"></i>
                            </div>
                            <p class="alert__message">
                                <span class="fw-bold">@lang('Verificação necessária de KYC')</span><br>
                                <small><i>@lang('Envie as informações de KYC para verificar você mesmo. Caso contrário, você não poderá fazer nenhuma solicitação de saque ao sistema.') <a href="{{ route('user.kyc.form') }}"
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
            <div class="row">

                <div class="col-lg-12">
                    <div class="dashboard-main">
                        <div class="row mt-30 mb-5">

                            <div class="col-lg-3 col-md-6 mb-30">
                                <div class="stat-item">
                                    <i class="las la-piggy-bank base--color"></i>
                                    <h6 class="caption text-shadow">@lang('Carteira de Depósito')</h6>
                                    <span
                                        class="total__amount">{{ $general->cur_sym }}{{ showAmount($user->deposit_wallet) }}</span>

                                    <div class="d-flex justify-content-center mt-3">
                                        <a href="{{ route('user.transactions') }}?wallet=deposit_wallet"
                                            class="btn btn-primary btn-small d-block text-center style--two">@lang('Ver relatório')</a>
                                    </div>

                                </div>
                            </div>

                            <div class="col-lg-3 col-md-6 mb-30">
                                <div class="stat-item">
                                    <i class="las la-piggy-bank base--color"></i>
                                    <h6 class="caption text-shadow">@lang('Carteira de juros')</h6>
                                    <span
                                        class="total__amount">{{ $general->cur_sym }}{{ showAmount($user->interest_wallet) }}</span>
                                    <div class="d-flex justify-content-center mt-3">
                                        <a href="{{ route('user.transactions') }}?wallet=interest_wallet"
                                            class="btn btn-primary btn-small d-block text-center style--two">@lang('Ver relatório')</a>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-3 col-md-6 mb-30">
                                <div class="stat-item">
                                    <i class="las la-credit-card base--color"></i>
                                    <h6 class="caption text-shadow">@lang('Investimento total')</h6>
                                    <span
                                        class="total__amount">{{ $general->cur_sym }}{{ showAmount($totalInvest) }}</span>
                                    <div class="d-flex justify-content-center mt-3">
                                        <a href="{{ route('user.transactions') }}?remark=invest"
                                            class="btn btn-primary btn-small d-block text-center style--two">@lang('Ver relatório')</a>
                                    </div>
                                </div><!-- stat-item end -->
                            </div>

                            <div class="col-lg-3 col-md-6 mb-30">
                                <div class="stat-item">
                                    <i class="las la-ticket-alt base--color"></i>
                                    <h6 class="caption text-shadow">@lang('Bilhete Total')</h6>
                                    <span class="total__amount">{{ $totalTicket }} </span>
                                    <div class="d-flex justify-content-center mt-3">
                                        <a href="{{ route('ticket.index') }}"
                                            class="btn btn-primary btn-small d-block text-center style--two">@lang('Ver relatório')</a>
                                    </div>
                                </div><!-- stat-item end -->
                            </div>

                            <div class="col-lg-6">
                                <div class="stat-wrapper deposit">
                                    <div class="stat__header">
                                        <div class="left">
                                            <div class="icon"><i class="las la-chart-area"></i></div>
                                            <h3 class="caption">@lang('Depósito')</h3>
                                        </div>
                                        <div class="right"><i class="flaticon-next"></i></div>
                                    </div>
                                    <div class="item-wrapper">
                                        <div class="stat-item-two box-shadow-two">
                                            <h5 class="caption text-shadow">@lang('Depósito total')</h5>
                                            <span
                                                class="total__amount base--color">{{ $general->cur_sym }}{{ showAmount($totalDeposit) }}</span>
                                        </div><!-- stat-item-two end -->
                                        <div class="stat-item-two box-shadow-two">
                                            <h5 class="caption text-shadow">@lang('Último Depósito')</h5>
                                            <span
                                                class="total__amount base--color">{{ $general->cur_sym }}{{ showAmount(@$lastDeposit->amount ?? 0) }}</span>
                                        </div><!-- stat-item-two end -->

                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-6 mt-lg-0 mt-5">
                                <div class="stat-wrapper withdraw">
                                    <div class="stat__header">
                                        <div class="left">
                                            <div class="icon"><i class="las la-credit-card"></i></div>
                                            <h3 class="caption">@lang('Retirar')</h3>
                                        </div>
                                        <div class="right"><i class="flaticon-next"></i></div>
                                    </div>
                                    <div class="item-wrapper">
                                        <div class="stat-item-two box-shadow-two">
                                            <h5 class="caption text-shadow">@lang('Retirada total')</h5>
                                            <span
                                                class="total__amount base--color">{{ $general->cur_sym }}{{ showAmount($totalWithdraw) }}</span>
                                        </div><!-- stat-item-two end -->
                                        <div class="stat-item-two box-shadow-two">
                                            <h5 class="caption text-shadow">@lang('Último saque')</h5>
                                            <span
                                                class="total__amount base--color">{{ $general->cur_sym }}{{ showAmount(@$lastWithdraw->amount ?? 0) }}</span>
                                        </div><!-- stat-item-two end -->

                                    </div><!-- item-wrapper end -->
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div><!-- row end -->
        </div>
    </section>

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
            (function($) {
                @if ($isHoliday)
                    function createCountDown(elementId, sec) {
                        var tms = sec;
                        var x = setInterval(function() {
                            var distance = tms * 1000;
                            var days = Math.floor(distance / (1000 * 60 * 60 * 24));
                            var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                            var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                            var seconds = Math.floor((distance % (1000 * 60)) / 1000);
                            var days = `<span class="text--base">${days}d</span>`;
                            var hours = `<span class="text--base">${hours}h</span>`;
                            var minutes = `<span class="text--base">${minutes}m</span>`;
                            var seconds = `<span class="text--base">${seconds}s</span>`;
                            document.getElementById(elementId).innerHTML = days + ' ' + hours + " " + minutes +
                                " " + seconds;
                            if (distance < 0) {
                                clearInterval(x);
                                document.getElementById(elementId).innerHTML = "COMPLETE";
                            }
                            tms--;
                        }, 1000);
                    }

                    createCountDown('counter', {{ \Carbon\Carbon::parse($nextWorkingDay)->diffInSeconds() }});
                @endif
            })(jQuery);
        </script>
    @endpush
