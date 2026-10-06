@extends($activeTemplate.'layouts.master')
@section('content')

@push('style')
<style>
  * { font-family: 'Inter', sans-serif; }
  body { background-color: #f1f5f9 !important; }
  .wlog-card {
      background: #ffffff;
      border-radius: 16px;
      padding: 14px;
      margin-bottom: 12px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.04);
      border: 1px solid rgba(22, 163, 74, 0.12);
  }
  .wlog-container { margin: 0; padding: 0 0 100px; }
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
        <div style="color: #ffffff; font-size: 22px; font-weight: 900; letter-spacing: 1px; line-height: 1.1;">REGISTRO DE DEPÓSITOS</div>
        <div style="color: rgba(255,255,255,0.85); font-size: 13px; font-weight: 600; letter-spacing: 2px; margin-top: 6px;">PAGAMENTOS REALIZADOS</div>
    </div>

    {{-- Rows: depósitos, comissões e saldo adicionado pelo admin --}}
    @forelse($rows as $row)
    @php
        if ($row->remark === 'deposit') {
            $title = 'Depósito PIX';
            [$statusColor, $statusBg, $statusBorder, $statusText] = match((int) ($row->deposit_status ?? 1)) {
                0 => ['#b45309', 'rgba(180, 83, 9, 0.08)', 'rgba(180, 83, 9, 0.25)', 'Aguardando Pgto'],
                3 => ['#dc2626', 'rgba(220, 38, 38, 0.08)', 'rgba(220, 38, 38, 0.25)', 'Rejeitado'],
                default => ['#16a34a', 'rgba(22, 163, 74, 0.1)', 'rgba(22, 163, 74, 0.25)', 'Pago'],
            };
        } elseif ($row->remark === 'referral_commission') {
            $title = 'Comissão recebida';
            [$statusColor, $statusBg, $statusBorder, $statusText] = ['#2563eb', 'rgba(37, 99, 235, 0.08)', 'rgba(37, 99, 235, 0.25)', 'Comissão'];
        } else {
            if (($row->trx_type ?? '+') === '-') {
                $title = 'Saldo removido pelo admin';
                [$statusColor, $statusBg, $statusBorder, $statusText] = ['#dc2626', 'rgba(220, 38, 38, 0.08)', 'rgba(220, 38, 38, 0.25)', 'Removido'];
            } else {
                $title = 'Saldo adicionado pelo admin';
                [$statusColor, $statusBg, $statusBorder, $statusText] = ['#16a34a', 'rgba(22, 163, 74, 0.1)', 'rgba(22, 163, 74, 0.25)', 'Adicionado'];
            }
        }
    @endphp
    <div class="wlog-card px-3">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
            <div>
                <div style="color: #0f172a; font-size: 16px; font-weight: 800;">{{ showAmount($row->amount) }} {{ $general->cur_text }}</div>
                <div style="color: #64748b; font-size: 12px; font-weight: 600; margin-top: 3px;">{{ $title }}</div>
            </div>
            <div style="background: {{ $statusBg }}; color: {{ $statusColor }}; font-size: 11px; font-weight: 700; padding: 4px 10px; border-radius: 20px; border: 1px solid {{ $statusBorder }};">
                {{ $statusText }}
            </div>
        </div>
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div style="color: #64748b; font-size: 11px;">
                <span style="margin-right: 4px;">&#128337;</span>
                {{ showDateTime($row->created_at, 'd/m/Y H:i') }}
            </div>
            <div style="color: #94a3b8; font-size: 10px;">{{ $row->trx }}</div>
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
            Nenhum registro encontrado
        </div>
    </div>
    @endforelse

    {{-- Pagination --}}
    @if($rows->hasPages())
    <div style="padding: 16px 14px;">
        {{ $rows->links() }}
    </div>
    @endif

    {{-- Bottom border --}}
    <div style="height: 4px; background: linear-gradient(90deg, #16a34a, #4ade80, #16a34a); border-radius: 4px;"></div>
</div>

@endsection