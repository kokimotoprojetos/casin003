@extends($activeTemplate.'layouts.master')
@section('content')

@push('style')
<style>
    body {
        background-color: #f1f5f9 !important;
    }
    * { font-family: 'Inter', sans-serif; }
    .wlog-card {
        background: #ffffff;
        border-radius: 16px;
        padding: 14px;
        margin-bottom: 12px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.04);
        border: 1px solid rgba(22, 163, 74, 0.12);
    }
    .wlog-container {
        margin: 0;
        padding: 0 0 100px;
    }
</style>
@endpush

<div class="wlog-container">
    {{-- Header Banner --}}
    <div style="
        background: linear-gradient(135deg, #16a34a 0%, #15803d 60%, #0b2818 100%);
        padding: 28px 16px 24px;
        text-align: center;
        border-radius: 0 0 24px 24px;
        box-shadow: 0 6px 18px rgba(22, 163, 74, 0.28);
        margin-bottom: 18px;
    ">
        <div style="color: #ffffff; font-size: 22px; font-weight: 900; letter-spacing: 1px; line-height: 1.1;">HISTÓRICO DE SAQUES</div>
        <div style="color: rgba(255,255,255,0.85); font-size: 13px; font-weight: 600; letter-spacing: 2px; margin-top: 6px;">RESGATE DE GANHOS</div>
    </div>

    {{-- Withdraw rows --}}
    @forelse($withdraws as $withdraw)
    @php
        [$statusColor, $statusBg, $statusBorder] = match($withdraw->status) {
            1 => ['#16a34a', 'rgba(22, 163, 74, 0.1)', 'rgba(22, 163, 74, 0.25)'],
            2 => ['#b45309', 'rgba(180, 83, 9, 0.08)', 'rgba(180, 83, 9, 0.25)'],
            3 => ['#dc2626', 'rgba(220, 38, 38, 0.08)', 'rgba(220, 38, 38, 0.25)'],
            default => ['#64748b', 'rgba(100, 116, 139, 0.08)', 'rgba(100, 116, 139, 0.2)'],
        };
        $statusText = match($withdraw->status) {
            1 => 'Aprovado',
            2 => 'Pendente',
            3 => 'Rejeitado',
            default => 'Iniciado',
        };
    @endphp
    <div class="wlog-card px-3">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
            <div style="color: #0f172a; font-size: 16px; font-weight: 800;">{{ showAmount($withdraw->amount) }} {{ $general->cur_text }}</div>
            <div style="background: {{ $statusBg }}; color: {{ $statusColor }}; font-size: 11px; font-weight: 700; padding: 4px 10px; border-radius: 20px; border: 1px solid {{ $statusBorder }};">
                {{ $statusText }}
            </div>
        </div>
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div style="color: #64748b; font-size: 11px;">
                <span style="margin-right: 4px;">&#128337;</span>
                {{ showDateTime($withdraw->created_at, 'd/m/Y H:i') }}
            </div>
            <div style="color: #94a3b8; font-size: 10px;">{{ $withdraw->trx }}</div>
        </div>
    </div>
    @empty
    <div style="
        background: #ffffff;
        border-radius: 16px;
        border: 1px solid rgba(22, 163, 74, 0.12);
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.04);
        padding: 40px 14px;
        text-align: center;
    ">
        <div style="color: #94a3b8; font-size: 14px;">
            <span style="font-size: 24px; display: block; margin-bottom: 8px;">&#128533;</span>
            Nenhum saque encontrado
        </div>
    </div>
    @endforelse

    {{-- Pagination --}}
    @if($withdraws->hasPages())
    <div style="padding: 16px 14px;">
        {{ $withdraws->links() }}
    </div>
    @endif

    {{-- Bottom border --}}
    <div style="height: 4px; background: linear-gradient(90deg, #16a34a, #4ade80, #16a34a); border-radius: 4px;"></div>
</div>

@endsection