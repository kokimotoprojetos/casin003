@extends($activeTemplate.'layouts.master')
@section('content')

@php
    $plans = App\Models\Plan::where('status', 1)->orderBy('sort_order')->orderBy('id')->get()
        ->reject(function ($p) {
            $u = auth()->user();
            if (!$u || empty($p->max_compras)) return false;
            $compras = \App\Models\Invest::where('user_id', $u->id)->where('plan_id', $p->id)->count();
            return $compras >= (int) $p->max_compras;
        })->values();
    $produtoImgs = produto_images();
@endphp

@push('style')
<style>
    body {
        background-color: #f1f5f9 !important;
    }
    .bike-plan-card {
        background: #ffffff;
        border-radius: 20px;
        padding: 16px;
        margin-bottom: 16px;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.05);
        border: 1px solid rgba(22, 163, 74, 0.12);
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .bike-plan-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(22, 163, 74, 0.12);
    }
    .bike-plan-img-wrap {
        width: 100px;
        height: 100px;
        background: #ffffff;
        border-radius: 16px;
        padding: 4px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        border: 1px solid #f1f5f9;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.03);
    }
    .bike-plan-img-wrap img {
        width: 100%;
        height: 100%;
        object-fit: contain;
    }
    .metric-box {
        background: #f8fafc;
        border-radius: 10px;
        padding: 6px 10px;
        border: 1px solid #e2e8f0;
    }
    .btn-rent-now {
        background: linear-gradient(135deg, #16a34a 0%, #15803d 100%);
        color: #ffffff;
        border: none;
        border-radius: 12px;
        font-size: 14px;
        font-weight: 800;
        padding: 12px;
        width: 100%;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        box-shadow: 0 4px 14px rgba(22, 163, 74, 0.28);
        transition: opacity 0.2s, transform 0.2s;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .btn-rent-now:active {
        opacity: 0.85;
    }
    .btn-rent-disabled {
        background: #cbd5e1;
        color: #64748b;
        border: none;
        border-radius: 12px;
        font-size: 13px;
        font-weight: 700;
        padding: 12px;
        width: 100%;
        cursor: not-allowed;
    }

    /* Super Plano VIP Effects */
    @keyframes superPlanGlow {
        0%, 100% {
            box-shadow: 0 8px 26px rgba(245, 158, 11, 0.24), 0 2px 10px rgba(0, 0, 0, 0.04);
            border-color: #f59e0b;
        }
        50% {
            box-shadow: 0 14px 38px rgba(245, 158, 11, 0.42), 0 4px 14px rgba(234, 179, 8, 0.28);
            border-color: #fbbf24;
        }
    }
    @keyframes superBadgePulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.03); }
    }
    .super-plan-card {
        background: linear-gradient(180deg, #ffffff 0%, #fffdf0 100%) !important;
        border: 2px solid #f59e0b !important;
        animation: superPlanGlow 3s ease-in-out infinite;
        position: relative;
        overflow: hidden;
    }
    .super-plan-card:hover {
        transform: translateY(-3px);
    }
    .super-plan-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 12px;
        padding-bottom: 10px;
        border-bottom: 1.5px dashed rgba(245, 158, 11, 0.35);
    }
    .super-plan-badge {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        color: #ffffff !important;
        font-size: 11px;
        font-weight: 900;
        padding: 5px 12px;
        border-radius: 20px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        letter-spacing: 0.6px;
        text-transform: uppercase;
        box-shadow: 0 3px 10px rgba(245, 158, 11, 0.4);
        animation: superBadgePulse 2.5s ease-in-out infinite;
    }
    .super-plan-card .bike-plan-img-wrap {
        background: #ffffff;
        border: 2px solid #fbbf24;
        box-shadow: 0 4px 16px rgba(245, 158, 11, 0.25);
    }
    .super-plan-card .metric-box {
        background: #ffffff;
        border: 1px solid #fef08a;
    }
    .super-plan-btn {
        background: linear-gradient(135deg, #16a34a 0%, #ca8a04 100%) !important;
        box-shadow: 0 6px 20px rgba(202, 138, 4, 0.38) !important;
        border: 1px solid rgba(254, 240, 138, 0.5) !important;
        font-size: 14.5px !important;
        letter-spacing: 0.6px !important;
    }
</style>
@endpush

<div style="margin: 0; padding: 0 0 100px;">
    {{-- Header Banner --}}
    <div style="width: 100%; margin-bottom: 18px;">
        <img src="{{ asset('core/img/topoplanos.webp?v=' . time()) }}" alt="Frota Elétrica Wind Bikes - Disponível para Aluguel" class="w-100" style="display: block; border-radius: 0 0 24px 24px; box-shadow: 0 6px 18px rgba(22, 163, 74, 0.22);">
    </div>

    {{-- Cards List --}}
    <div class="px-3">
        @foreach ($plans as $plan)
        @php
            $isSuperPlan = ($plan->id == 7 || $plan->fixed_amount >= 5000);
            $imgIndex = $loop->index % max(1, count($produtoImgs));
            $imgFile = $produtoImgs[$imgIndex];
            if (!empty($plan->image)) {
                $imgSrc = (str_starts_with($plan->image, 'data:') || str_starts_with($plan->image, 'http'))
                    ? $plan->image
                    : asset('core/img/' . $plan->image . '?v=' . time());
            } else {
                $imgSrc = asset('core/img/' . $imgFile . '?v=' . time());
            }
            $totalGain = $plan->lifetime ? '∞' : 'R$ ' . number_format($plan->interest * $plan->repeat_time, 2, ',', '.');
            $investText = $plan->fixed_amount > 0
                ? 'R$ ' . number_format($plan->fixed_amount, 2, ',', '.')
                : 'R$ ' . number_format($plan->minimum, 2, ',', '.');
            $gainText = ($plan->interest_type == 1 ? '' : 'R$ ') . number_format($plan->interest, 2, ',', '.') . ($plan->interest_type == 1 ? '%' : '');
            $duracao = $plan->lifetime ? 'Vitalício' : $plan->repeat_time . ' dias';
            $blocked = $plan->status == 0;
        @endphp

        <div class="bike-plan-card {{ $isSuperPlan ? 'super-plan-card' : '' }}" style="{{ $blocked ? 'opacity: 0.5; filter: grayscale(80%); pointer-events: none;' : '' }}">
            @if($isSuperPlan)
                <div class="super-plan-header">
                    <span class="super-plan-badge">
                        SUPER PLANO VIP
                    </span>
                    <span style="color: #d97706; font-size: 11px; font-weight: 800; display: inline-flex; align-items: center;">
                        MÁXIMA RENTABILIDADE
                    </span>
                </div>
            @endif

            {{-- Top row: Title and Badge --}}
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div>
                    <span style="color: #0f172a; font-size: 17px; font-weight: 900; font-family: 'Inter', sans-serif;">{{ $plan->name }}</span>
                </div>
                @if(!$blocked)
                    <span style="background: rgba(22, 163, 74, 0.1); color: #15803d; font-size: 11px; font-weight: 700; padding: 4px 10px; border-radius: 20px; display: inline-flex; align-items: center; gap: 4px;">
                        <span style="width: 6px; height: 6px; border-radius: 50%; background: #16a34a; display: inline-block;"></span> Disponível
                    </span>
                @else
                    <span style="background: #f1f5f9; color: #64748b; font-size: 11px; font-weight: 700; padding: 4px 10px; border-radius: 20px;">
                        Indisponível
                    </span>
                @endif
            </div>

            {{-- Middle: Image + Metrics --}}
            <div class="d-flex gap-3 mb-3 align-items-center">
                <div class="bike-plan-img-wrap">
                    <img src="{{ $imgSrc }}" alt="{{ $plan->name }}">
                </div>
                <div style="flex: 1; min-width: 0;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                        <div class="metric-box">
                            <div style="color: #64748b; font-size: 10px; font-weight: 600; text-transform: uppercase;">Preço</div>
                            <div style="color: #0f172a; font-size: 14px; font-weight: 800;">{{ $investText }}</div>
                        </div>
                        <div class="metric-box">
                            <div style="color: #64748b; font-size: 10px; font-weight: 600; text-transform: uppercase;">Diário</div>
                            <div style="color: #16a34a; font-size: 14px; font-weight: 800;">+{{ $gainText }}</div>
                        </div>
                        <div class="metric-box">
                            <div style="color: #64748b; font-size: 10px; font-weight: 600; text-transform: uppercase;">Duração</div>
                            <div style="color: #0f172a; font-size: 13px; font-weight: 700;">{{ $duracao }}</div>
                        </div>
                        <div class="metric-box">
                            <div style="color: #64748b; font-size: 10px; font-weight: 600; text-transform: uppercase;">Renda Total</div>
                            <div style="color: {{ $isSuperPlan ? '#b45309' : '#15803d' }}; font-size: 13px; font-weight: 800; {{ $isSuperPlan ? 'background: #fef3c7; padding: 1px 6px; border-radius: 4px; display: inline-block;' : '' }}">{{ $totalGain }}</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Action button --}}
            @if($blocked)
                <button class="btn-rent-disabled" disabled>Em Breve</button>
            @else
                <button class="btn-rent-now {{ $isSuperPlan ? 'super-plan-btn' : '' }} investModal"
                    data-bs-toggle="modal"
                    data-plan="{{ json_encode($plan) }}"
                    data-bs-target="#investModal">
                    {{ $isSuperPlan ? 'Alugar Super Plano' : 'Alugar Agora' }}
                </button>
            @endif
        </div>
        @endforeach
    </div>
</div>

<div class="modal fade" id="investModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 20px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.15); overflow: hidden;">
            <div class="modal-header" style="background: linear-gradient(135deg, #16a34a, #15803d); color: white; border-bottom: none; padding: 18px 20px;">
                <h5 class="modal-title" style="font-weight: 700; font-size: 17px; margin: 0;">
                    Confirmar Aluguel
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center p-4">
                <form action="{{ route('user.invest.submit') }}" method="post">
                    @csrf
                    <input type="hidden" name="plan_id">
                    
                    <h5 class="mb-2" style="font-size: 15px; color: #64748b; font-weight: 600;">Você deseja alugar a bike</h5>
                    <div class="planName mb-3" style="color: #16a34a; font-weight: 800; font-size: 24px;"></div>
                    
                    <div style="background: #f8fafc; border-radius: 14px; padding: 14px; margin-bottom: 20px; border: 1px solid #e2e8f0; text-align: left;">
                        <div class="d-flex justify-content-between mb-2">
                            <span style="color: #64748b; font-size: 13px;">Rendimento:</span>
                            <span class="interestDetails" style="color: #16a34a; font-weight: 700; font-size: 13px;"></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span style="color: #64748b; font-size: 13px;">Duração:</span>
                            <span class="interestValidity" style="color: #0f172a; font-weight: 700; font-size: 13px;"></span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span style="color: #64748b; font-size: 13px;">Valor do Aluguel:</span>
                            <span class="investAmountRange" style="color: #0f172a; font-weight: 800; font-size: 14px;"></span>
                        </div>
                    </div>
                    
                    <!-- Hidden inputs to satisfy the form -->
                    <div style="display:none">
                        <select name="wallet_type" required>
                            <option value="interest_wallet" selected>interest_wallet</option>
                        </select>
                        <input type="number" step="any" name="amount" required>
                    </div>

                    <div class="d-flex justify-content-center gap-2 mt-3">
                        <button type="button" class="btn btn-light px-4" style="border-radius: 12px; font-weight: 600; color: #64748b; border: 1px solid #cbd5e1;" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn px-4 text-white" style="background: linear-gradient(135deg, #16a34a, #15803d); font-weight: 700; border-radius: 12px; box-shadow: 0 4px 12px rgba(22, 163, 74, 0.35);">Confirmar Aluguel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('script')
<script>
    (function($){
        "use strict"
        $('.investModal').click(function(){
            var plan = $(this).data('plan');
            if (!plan || plan.status == 0) return;
            var symbol = '{{ $general->cur_sym }}';
            var currency = '{{ $general->cur_text }}';
            $('.gateway-info').addClass('d-none');
            var modal = $('#investModal');
            modal.find('[name=plan_id]').val(plan.id);
            modal.find('.planName').text(plan.name);
            let fixedAmount = parseFloat(plan.fixed_amount).toFixed(2);
            let minimumAmount = parseFloat(plan.minimum).toFixed(2);
            let maximumAmount = parseFloat(plan.maximum).toFixed(2);
            let interestAmount = parseFloat(plan.interest);

            if (plan.fixed_amount > 0) {
                modal.find('.investAmountRange').text(`${symbol}${fixedAmount}`);
                modal.find('[name=amount]').val(parseFloat(plan.fixed_amount).toFixed(2));
                modal.find('[name=amount]').attr('readonly',true);
            }else{
                modal.find('.investAmountRange').text(`${symbol}${minimumAmount} - ${symbol}${maximumAmount}`);
                modal.find('[name=amount]').val('');
                modal.find('[name=amount]').removeAttr('readonly');
            }

            if (plan.interest_type == '1') {
                modal.find('.interestDetails').html(`<strong>+${interestAmount}%</strong>`);
            } else {
                modal.find('.interestDetails').html(`<strong>+${symbol}${interestAmount} / dia</strong>`);
            }

            if (plan.lifetime == '0') {
                modal.find('.interestValidity').html(`<strong>${plan.repeat_time} dias</strong>`);
            } else {
                modal.find('.interestValidity').html(`<strong>Vitalício</strong>`);
            }

        });

        $('[name=amount]').on('input',function(){
            $('[name=wallet_type]').trigger('change');
        })

        $('[name=wallet_type]').change(function () {
            var amount = $('[name=amount]').val();
            if($(this).val() != 'deposit_wallet' && $(this).val() != 'interest_wallet' && amount){
                var resource = $('select[name=wallet_type] option:selected').data('gateway');
                var fixed_charge = parseFloat(resource.fixed_charge);
                var percent_charge = parseFloat(resource.percent_charge);
                var charge = parseFloat(fixed_charge + (amount * percent_charge / 100)).toFixed(2);
                $('.charge').text(charge);
                $('.gateway-rate').text(parseFloat(resource.rate));
                $('.gateway-info').removeClass('d-none');
                if (resource.currency == '{{ $general->cur_text }}') {
                    $('.rate-info').addClass('d-none');
                }else{
                    $('.rate-info').removeClass('d-none');
                }
                $('.method_currency').text(resource.currency);
                $('.total').text(parseFloat(charge) + parseFloat(amount));
            }else{
                $('.gateway-info').addClass('d-none');
            }
        });
    })(jQuery);
</script>
@endpush
@endsection
