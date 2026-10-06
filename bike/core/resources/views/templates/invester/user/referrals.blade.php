@extends($activeTemplate.'layouts.master')
@section('content')

<style>
    body { background-color: #f1f5f9 !important; }
    * { font-family: 'Inter', sans-serif; }
    .main-heading { font-size: 16px; font-weight: 500; color: #000; font-family: 'Inter', sans-serif; }
    .cash-back, .team-level, .rewards-card { box-shadow: 0px 0px 10px 0px #0000001A; border-radius: 15px; }
    .style-line { border: 0.7px solid #000; opacity: 0.5; }
    .team-sub-heading { font-size: 12px; font-weight: 500; color: #000; }
    .team-card-sub-heading { color: #000; font-size: 11px; font-weight: 500; }
    .plan-card {
        width: 48%;
        max-width: 175px;
        aspect-ratio: 1127 / 510;
        border-radius: 15px;
        position: relative;
        overflow: hidden;
        background-size: 100% 100% !important;
        background-repeat: no-repeat !important;
        background-position: center !important;
    }
    .plan-card::before { display: none; }
    .plan-card::after { display: none; }
    .card-val-people {
        position: absolute;
        top: 20%;
        left: 42%;
        width: 44%;
        text-align: center;
        font-size: 18px;
        font-weight: 800;
        color: #000;
        line-height: 1;
    }
    .card-val-commission {
        position: absolute;
        top: 25%;
        left: 59%;
        font-size: 14px;
        font-weight: 800;
        color: #16a34a;
        line-height: 1;
        white-space: nowrap;
    }
</style>

@php
  $authUser = Auth::user();
  $userCount = App\Models\User::where('ref_by', $authUser->id)->count();
  $referralCommission = \App\Models\Transaction::where('user_id', Auth::id())
      ->where('remark', 'referral_commission')->sum('amount');

  if (!function_exists('maskPhone')) {
      function maskPhone($phone) {
          $phone = preg_replace('/\D/', '', $phone ?? '');
          if (strlen($phone) <= 4) return $phone;
          return substr($phone, 0, 3) . str_repeat('*', max(0, strlen($phone) - 5)) . substr($phone, -2);
      }
  }
@endphp

<div class="mx-auto text-center">
    <h4 id="snackbar_error"></h4>
</div>
<div class="mx-auto col-sm-10">
    <h4 id="snackbar"></h4>
</div>

<div class="mb-4">
    <div class="text-center mt-2 mb-2 p-1">
        <img src="{{ asset('core/img/equipetopo.webp?v=' . time()) }}" alt="Equipe" style="max-width: 105px; width: 28%; height: auto;">
    </div>
    <div class="d-flex justify-content-evenly p-0 m-0">
        <div class="plan-card" style="background-image: url('{{ asset('core/img/areadeequipe.webp') }}?v={{ time() }}');">
            <div class="card-val-people">{{ $userCount }}</div>
        </div>
        <div class="plan-card" style="background-image: url('{{ asset('core/img/ladodaquipe.webp') }}?v={{ time() }}');">
            <div class="card-val-commission">{{ showAmount($referralCommission) }}</div>
        </div>
    </div>

    <div style="display:none;">
        <input type="text" value="{{ route('home') }}?refcode={{ auth()->user()->refer_code }}" id="myInput">
    </div>
    <div style="display:none;">
        <input type="text" value="{{ auth()->user()->refer_code }}" id="myInpu">
    </div>

<script>
function myFunctio() {
    var copyText = document.getElementById("myInpu");
    copyText.select();
    copyText.setSelectionRange(0, 99999);
    navigator.clipboard.writeText(copyText.value).then(function() {
        showCopyToast();
    });
}
function myFunction() {
    var copyText = document.getElementById("myInput");
    copyText.select();
    copyText.setSelectionRange(0, 99999);
    navigator.clipboard.writeText(copyText.value).then(function() {
        showCopyToast();
    });
}
function showCopyToast() {
    var toast = document.getElementById('liveToast');
    if (toast) {
        var bsToast = new bootstrap.Toast(toast);
        bsToast.show();
    } else {
        var snackbar = document.getElementById("snackbar");
        if (snackbar) {
            snackbar.className = "show";
            snackbar.textContent = 'Copiado!';
            setTimeout(function() { snackbar.className = snackbar.className.replace("show", ""); }, 3000);
        }
    }
}
</script>

    <div class="m-2 mt-4" style="position: relative; border-radius: 15px; overflow: hidden;">
        <img class="w-100" src="{{ asset('core/img/CODIGO1.webp?v=' . time()) }}" alt="Código de Convite" style="display: block; border-radius: 15px;" />

        <div style="position: absolute; top: 41%; left: 52%; width: 36%; height: 17%; display: flex; align-items: center; justify-content: center; overflow: hidden;">
            <span id="referal_code" style="font-size: clamp(9px, 3vw, 14px); font-weight: 700; color: #222; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 100%;">
                {{ auth()->user()->refer_code }}
            </span>
        </div>
        <div id="myToolti" onclick="myFunctio()" style="position: absolute; top: 41%; right: 1.5%; width: 10%; height: 17%; cursor: pointer; z-index: 10;"></div>

        <div style="position: absolute; top: 67%; left: 52%; width: 36%; height: 17%; display: flex; align-items: center; justify-content: center; overflow: hidden;">
            <span id="referal_link" style="font-size: clamp(7px, 2.2vw, 11px); font-weight: 500; color: #222; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 100%;">
                {{ route('home') }}?refcode={{ auth()->user()->refer_code }}
            </span>
        </div>
        <div id="myTooltip" onclick="myFunction()" style="position: absolute; top: 67%; right: 1.5%; width: 10%; height: 17%; cursor: pointer; z-index: 10;"></div>
    </div>

    <div class="team-level mx-2 p-3 mt-4">
        <div class="d-flex justify-content-between">
            <span style="font-size: 15px; font-weight: 700; color: #000;"><img src="{{ asset('core/img/level-one.webp?v=' . time()) }}" style="width: 20px;"> Nível 1/2/3</span>
            <span style="font-size: 13px; font-weight: 700; color: #000;">{{ \App\Models\Referral::where('commission_type', 'invest_commission')->where('status', 1)->orderBy('level')->pluck('percent')->map(function ($p) { return rtrim(rtrim(number_format((float) $p, 2, ',', ''), '0'), ',') . '%'; })->implode('/') }}</span>
        </div>
        <div class="text-center mt-2">
            <div>
                <span class="team-card-sub-heading" style="font-size: 20px; font-weight: 800; color: #16a34a;">{{ $general->cur_sym }} {{ showAmount($referralCommission) }}</span>
            </div>
            <div>
                <span class="team-sub-heading">Comissão</span>
            </div>
        </div>
        <div class="style-line my-2"></div>
        <div class="text-center">
            <div>
                <span class="team-card-sub-heading" style="font-size: 20px; font-weight: 800; color: #000;">{{ $userCount }}</span>
            </div>
            <div>
                <span class="team-sub-heading">Total de Pessoas</span>
            </div>
        </div>
        <div class="style-line my-2"></div>
        <div class="text-center">
            <div>
                <span class="team-card-sub-heading" style="font-size: 20px; font-weight: 800; color: #16a34a;">{{ $general->cur_sym }} {{ showAmount($teamInvestments) }}</span>
            </div>
            <div>
                <span class="team-sub-heading">Investimentos da Equipe</span>
            </div>
        </div>
    </div>

    <div class="team-level mx-2 p-3 mt-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <span style="font-size: 15px; font-weight: 700; color: #000;">Meus Indicados</span>
            <span style="font-size: 12px; font-weight: 700; color: #16a34a;">{{ count($referrals) }} pessoa(s)</span>
        </div>

        @forelse($referrals as $ref)
            <div class="d-flex justify-content-between align-items-center p-2 mb-2" style="background: #fff; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.06);">
                <div>
                    <div style="font-size: 14px; font-weight: 800; color: #000;">
                        {{ maskPhone($ref->mobile) }}
                    </div>
                    <div style="font-size: 11px; color: #888;">
                        Cadastrado em {{ \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', substr((string) $ref->created_at, 0, 19), 'America/Sao_Paulo')->format('d/m/Y') }}
                    </div>
                </div>
                <div class="text-end">
                    <div style="font-size: 13px; font-weight: 800; color: #16a34a;">
                        {{ (int) ($ref->active_plans_count ?? 0) }} plano(s) ativo(s)
                    </div>
                    <div style="font-size: 11px; color: #888;">
                        {{ $general->cur_sym }} {{ showAmount($ref->active_plans_total ?? 0) }} investidos
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center p-3" style="color: #888; font-size: 13px;">
                Nenhum indicado ainda. Compartilhe seu código de convite!
            </div>
        @endforelse
    </div>

    <div class="toast-container position-fixed top-0 end-0 p-3">
        <div id="liveToast" class="toast bg-success" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-body text-white">
                Código copiado com sucesso!
                <button type="button" class="btn-close" style="float: right;" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    </div>
</div>

@endsection
