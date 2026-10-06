@extends($activeTemplate . 'layouts.master')
@section('content')
<style>
    body {
        background-color: #f1f5f9 !important;
    }
    * { font-family: 'Inter', sans-serif; }
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
    .invest-metric-grid {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 10px;
    }
</style>

@php
    $totalInvested = \App\Models\Invest::where('user_id', auth()->id())->sum('amount');
    $totalEarned = \App\Models\Transaction::where('user_id', auth()->id())->where('remark', 'interest')->sum('amount');
@endphp
    <div class="col-12 m-0 p-0" style="padding: 0 0 100px !important;">
        <div class="px-3">

    <div class="mt-3 mb-4">
        <span style="color: #0f172a; font-size: 20px; font-weight: 900; letter-spacing: 0.5px;">Meus Investimentos</span>
    </div>

    {{-- Resumo --}}
    <div class="mb-4">
    <div style="background: linear-gradient(135deg, #16a34a 0%, #15803d 60%, #0b2818 100%); border-radius: 20px; padding: 22px 20px; color: white; box-shadow: 0 4px 18px rgba(22,163,74,0.28); position: relative; overflow: hidden;">
        <div style="position: absolute; top: -20px; right: -20px; width: 100px; height: 100px; background: rgba(255,255,255,0.1); border-radius: 50%;"></div>
        <div style="position: absolute; bottom: -30px; left: -10px; width: 80px; height: 80px; background: rgba(255,255,255,0.1); border-radius: 50%;"></div>

        <h5 style="font-size: 14px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 20px; position: relative; z-index: 1;">Planos Ativos</h5>

        <div class="d-flex justify-content-between align-items-center" style="position: relative; z-index: 1;">
            <div style="text-align: left;">
                <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; opacity: 0.9; margin-bottom: 4px;">Depositado</div>
                <div style="font-size: 18px; font-weight: 800;">{{ $general->cur_text }} {{ showAmount($totalInvested) }}</div>
            </div>

            <div style="height: 40px; width: 1px; background: rgba(255,255,255,0.3);"></div>

            <div style="text-align: right;">
                <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; opacity: 0.9; margin-bottom: 4px;">Meus Ganhos</div>
                <div style="font-size: 18px; font-weight: 800;">{{ $general->cur_text }} {{ showAmount($totalEarned) }}</div>
            </div>
        </div>
    </div>
</div>

                @forelse($invests as $invest)
                    @php
                        $plan = $invest->plan;
                        $planImgMap = [
                            12 => 'produto1.webp',
                            13 => 'produto2.webp',
                            14 => 'produto3.webp',
                            15 => 'produto4.webp',
                            16 => 'produto5.webp',
                            17 => 'produto6.webp',
                        ];
                        $imgFallback = $planImgMap[$invest->plan_id] ?? 'produto' . (($invest->plan_id % 6) + 1) . '.webp';
                        if (!empty($plan->image)) {
                            $imgSrc = (str_starts_with($plan->image, 'data:') || str_starts_with($plan->image, 'http'))
                                ? $plan->image
                                : asset('core/img/' . $plan->image);
                        } else {
                            $imgSrc = asset('core/img/' . $imgFallback);
                        }

                        $totalDays = $plan->lifetime ? 0 : ($plan->repeat_time ?? 30);
                        $completedDays = $invest->return_rec_time ?? 0;
                        $percent = $plan->lifetime ? 100 : min(100, round(($completedDays / max(1, $totalDays)) * 100));

                        $createdDate = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', substr((string) $invest->created_at, 0, 19), 'America/Sao_Paulo')->format('d/m/Y H:i');
                        $nextDate = $invest->status == 1 && $invest->next_time
                            ? \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', substr((string) $invest->next_time, 0, 19), 'America/Sao_Paulo')->format('d/m/Y H:i')
                            : 'Finalizado';
                    @endphp

                    <div class="bike-plan-card">
                        {{-- Top: Imagem + Detalhes do Produto --}}
                        <div style="display: flex; gap: 14px; align-items: center;">
                            <img src="{{ $imgSrc }}"
                                 alt="{{ $plan->name }}"
                                 style="width: 72px; height: 72px; object-fit: cover; border-radius: 14px; border: 2px solid rgba(22, 163, 74, 0.3); flex-shrink: 0; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">

                            <div style="flex: 1; min-width: 0;">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 8px;">
                                    <div style="color: #0f172a; font-size: 16px; font-weight: 800; line-height: 1.2;">{{ __($plan->name) }}</div>
                                    @if ($invest->status == 1)
                                        <span style="background: rgba(22, 163, 74, 0.1); color: #15803d; font-size: 10px; font-weight: 700; padding: 3px 8px; border-radius: 20px; border: 1px solid rgba(22, 163, 74, 0.3); text-transform: uppercase; flex-shrink: 0;">Ativo</span>
                                    @else
                                        <span style="background: rgba(220, 38, 38, 0.08); color: #dc2626; font-size: 10px; font-weight: 700; padding: 3px 8px; border-radius: 20px; border: 1px solid rgba(220, 38, 38, 0.3); text-transform: uppercase; flex-shrink: 0;">Finalizado</span>
                                    @endif
                                </div>
                                <div style="color: #16a34a; font-size: 13px; font-weight: 700; margin-top: 4px;">
                                    +{{ $plan->interest_type != 1 ? 'R$ ' : '' }}{{ showAmount($plan->interest) }}{{ $plan->interest_type == 1 ? '%' : '' }} ao dia
                                </div>
                                <div style="color: #64748b; font-size: 11px; margin-top: 2px;">
                                    Investido: <span style="color: #0f172a; font-weight: 700;">R$ {{ showAmount($invest->amount) }}</span>
                                </div>
                            </div>
                        </div>

                        {{-- Barra de Progresso de Rendimento --}}
                        <div style="margin-top: 14px; padding-top: 12px; border-top: 1px solid #e2e8f0;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px; font-size: 11px;">
                                <span style="color: #64748b; font-weight: 700;">Progresso do Ciclo</span>
                                <span style="color: #0f172a; font-weight: 700;">
                                    @if($plan->lifetime)
                                        Vitalício (Ativo)
                                    @else
                                        {{ $completedDays }}/{{ $totalDays }} dias ({{ $percent }}%)
                                    @endif
                                </span>
                            </div>
                            <div style="background: #e2e8f0; height: 8px; border-radius: 6px; overflow: hidden;">
                                <div style="background: linear-gradient(90deg, #16a34a, #4ade80); width: {{ $percent > 0 ? $percent : 2 }}%; height: 100%; border-radius: 6px;"></div>
                            </div>
                        </div>

                        {{-- Datas e Retorno --}}
                        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 8px; margin-top: 14px; background: #f8fafc; padding: 10px; border-radius: 12px; border: 1px solid #e2e8f0;">
                            <div>
                                <div style="color: #64748b; font-size: 10px; font-weight: 700; text-transform: uppercase;">Início</div>
                                <div style="color: #0f172a; font-size: 11px; font-weight: 700; margin-top: 2px;">{{ $createdDate }}</div>
                            </div>
                            <div>
                                <div style="color: #64748b; font-size: 10px; font-weight: 700; text-transform: uppercase;">Próx. Retorno</div>
                                <div style="color: #16a34a; font-size: 11px; font-weight: 700; margin-top: 2px;">{{ $nextDate }}</div>
                            </div>
                            <div style="text-align: right;">
                                <div style="color: #64748b; font-size: 10px; font-weight: 700; text-transform: uppercase;">Retorno Total</div>
                                <div style="color: #16a34a; font-size: 12px; font-weight: 800; margin-top: 2px;">+R$ {{ showAmount($invest->paid) }}</div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="bike-plan-card text-center p-4">
                        <img src="{{asset ('core/img/no-record.webp')}}" alt="" width="230px">
                    </div>
                @endforelse
            </div>
            <div class="mt-3">
                {{ $invests->links() }}
            </div>
        </div>
    </div>
@endsection
@push('style')
    <style>
        .custom-progress {
            max-width: 40px !important;
            max-height: 40px;
            transform: rotate(-90deg);
        }

        .custom-progress .bg-circle {
            stroke: #00000011;
            fill: none;
            stroke-width: 4px;
            position: relative;
            z-index: -1;
        }

        .custom-progress .progress-circle {
            fill: none;
            stroke: #16a34a;
            stroke-width: 4px;
            z-index: 11;
            position: absolute;
        }

        .expired-time-circle {
            position: relative;
            border: none !important;
            height: 38px;
            width: 38px;
            margin-right: 7px;
        }

        .expired-time-circle::before {
            position: absolute;
            content: '';
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            border: 4px solid #dbdce1;
        }

        .expired-time-circle.danger-border .animation-circle {
            border-color: #16a34a !important;
        }

        .animation-circle {
            position: absolute;
            top: 0;
            left: 0;
            border: 4px solid #16a34a;
            height: 100%;
            width: 100%;
            border-radius: 150px;
            transform: rotateY(180deg);
            animation-name: clipCircle;
            animation-iteration-count: 1;
            animation-timing-function: cubic-bezier(0, 0, 1, 1);
            z-index: 1;
        }

        .capital-back {
            font-size: 10px;
        }

        .closed-invest {
            max-width: 40px !important;
            max-height: 40px;
            text-align: center;
        }
    </style>
@endpush

@push('script')
    <script>
        let animationCircle = $('.animation-circle');
        animationCircle.css('animation-duration', function() {
            let duration = ($(this).data('duration'));
            return duration;
        });
    </script>
@endpush