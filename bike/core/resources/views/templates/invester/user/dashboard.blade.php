@extends($activeTemplate.'layouts.master')
@section('content')

<div class="modal fade" id="myPopup" tabindex="-1" role="dialog"
aria-labelledby="popupTitle" data-bs-backdrop="static" aria-hidden="true" style="z-index: 999999;">
    <div class="modal-dialog modal-dialog-centered justify-content-center" role="document" style="width: 75%; max-width: 320px; margin: 0 auto; z-index: 999999;">
      <div class="linear-bg" style="width: 100%;">
      <div class="modal-content" style="background: transparent; border:none; width: 100%;">
     
        <div class="modal-body p-0 position-relative">
          <button type="button" class="position-absolute d-flex justify-content-center align-items-center" data-bs-dismiss="modal" aria-label="Close" style="background-color: #ff0000; color: white; border: 2px solid white; border-radius: 50%; width: 28px; height: 28px; top: -10px; right: -10px; z-index: 20; cursor: pointer; font-weight: bold; font-size: 18px; box-shadow: 0px 2px 5px rgba(0,0,0,0.5); padding: 0; line-height: 1;">&times;</button>

          <a href="https://t.me/WindsBikes0001" target="_blank" onclick="var m = bootstrap.Modal.getInstance(document.getElementById('myPopup')); if(m) m.hide();" style="display: block;">
              <img src="{{asset ('core/img/grupotelegram.webp?v=' . time())}}" class="w-100 border-0" style="border:none; border-radius: 10px;" >
          </a>
        </div>

      </div>
    </div>
    </div>
</div>

<!-- Popup Grupo do WhatsApp (OCULTO POR ENQUANTO) -->
<!--
<div class="modal fade" id="whatsappPopup" tabindex="-1" role="dialog"
aria-labelledby="whatsappPopupTitle" data-bs-backdrop="static" aria-hidden="true" style="z-index: 999999;">
    <div class="modal-dialog modal-dialog-centered justify-content-center" role="document" style="width: 85%; max-width: 320px; margin: 0 auto; z-index: 999999;">
        <div class="modal-content" style="background: #333333; border: 1px solid rgba(255,255,255,0.1); border-radius: 22px; width: 100%; box-shadow: 0 10px 30px rgba(0,0,0,0.6); padding: 26px 20px 20px;">
            <div class="modal-body p-0 text-center">
                {{-- Ícone WhatsApp --}}
                <div style="display: flex; justify-content: center; margin-bottom: 16px;">
                    <img src="{{ asset('core/img/whatsapp-icon.webp') }}" alt="WhatsApp" style="width: 60px; height: 60px; object-fit: contain;">
                </div>

                {{-- Aviso de suspensão --}}
                <div style="background: rgba(255, 92, 10, 0.15); border: 1px solid rgba(255, 92, 10, 0.5); border-radius: 12px; padding: 12px 14px; margin-bottom: 12px; text-align: center;">
                    <p style="color: #22c55e; font-size: 13px; font-weight: 700; margin: 0; font-family: 'Inter', sans-serif; line-height: 1.4;">
                        ⚠️ O grupo antigo do WhatsApp sofreu uma suspensão.<br>
                        <span style="color: #ffffff; font-weight: 600;">Entre no novo grupo abaixo!</span>
                    </p>
                </div>

                {{-- Título --}}
                <h4 style="color: #ffffff; font-size: 18px; font-weight: 800; margin-bottom: 10px; font-family: 'Inter', sans-serif;">
                    Novo grupo oficial WhatsApp
                </h4>

                {{-- Descrição --}}
                <p style="color: #d1d1d1; font-size: 13px; line-height: 1.45; margin-bottom: 22px; font-family: 'Inter', sans-serif;">
                    O grupo anterior foi suspenso pelo WhatsApp. Entre agora no novo grupo oficial para acompanhar avisos e novidades exclusivas da plataforma.
                </p>

                {{-- Botão Entrar no Grupo --}}
                <a href="https://chat.whatsapp.com/Kc5nYJhxYFR3OsjrUgxiKo" target="_blank" 
                   onclick="var m = bootstrap.Modal.getInstance(document.getElementById('whatsappPopup')); if(m) m.hide();"
                   style="display: block; width: 100%; background: #16a34a; color: #ffffff; font-size: 15px; font-weight: 800; padding: 14px 10px; border-radius: 12px; text-decoration: none; text-align: center; box-shadow: 0 4px 15px rgba(255, 92, 10, 0.35); transition: opacity 0.2s;">
                    Entrar no grupo
                </a>

                {{-- Botão Agora não --}}
                <div style="margin-top: 14px;">
                    <button type="button" data-bs-dismiss="modal" 
                            style="background: transparent; border: none; color: #aaaaaa; font-size: 14px; font-weight: 600; cursor: pointer; padding: 6px 12px; text-decoration: none; outline: none;">
                        Agora não
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
-->

<script>
    document.addEventListener("DOMContentLoaded", function () {
      var navbar = document.querySelector('.navbar-bottom');
      if (navbar) navbar.style.display = 'none';

      var myPopupEl = document.getElementById('myPopup');

      var myPopup = new bootstrap.Modal(myPopupEl);

      // Abre o popup do Telegram primeiro
      myPopup.show();

      // Quando fechar o popup do Telegram, restaura a barra de navegação
      myPopupEl.addEventListener('hidden.bs.modal', function () {
          if (navbar) navbar.style.display = '';
      });
    });
</script>

<div id="snackbar"></div>
<div id="snackbar_error"></div>

<!-- start slider section -->
<div id="top_section" class="banner_main" style="margin-top: 0px;">
    <div style="position: relative; width: 100%;">
        <img src="{{ asset('core/img/principaltopo.webp?v=' . time()) }}" alt="Wind Bikes" class="w-100" style="border-radius: 0px 0px 30px 30px;">
        <div data-bs-toggle="modal" data-bs-target="#exampleModal" style="position: absolute; top: 2.7%; right: 2.3%; width: 18.5%; height: 12%; z-index: 10; cursor: pointer; border-radius: 20px;" title="Check-In"></div>
    </div>
</div>
<div class="mx-2 mt-2" style="position: relative;">
    <img src="{{asset ('core/img/botoes.webp?v=2')}}" class="w-100" style="display: block; border-radius: 15px;">
    <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; display: flex;">
        <a href="{{route ('user.deposit.index')}}" style="flex: 1; height: 100%;" title="Recarregar"></a>
        <a href="{{route ('user.withdraw')}}" style="flex: 1; height: 100%;" title="Sacar"></a>
        <a href="{{ route('user.profile.setting') }}" style="flex: 1; height: 100%;" title="Conta"></a>
        <a href="{{ route('user.referrals') }}" style="flex: 1; height: 100%;" title="Equipe"></a>
    </div>
</div>

<!-- end slider section -->
<div class="banner-container m-2 mt-3" id="aboutus">
    <a class="p-0 m-0" href="{{ route('user.referrals') }}">
        <img src="{{ asset('core/img/convite.webp?v=' . time()) }}" alt="Sua imagem" class="image w-100" style="border-radius: 30px;">
    </a>
</div>
<div class="px-2 mt-3 mb-1" style="position: relative;">
    <div style="position: relative; width: 100%;">
        <img src="{{ asset('core/img/saldoerendimento.webp?v=' . time()) }}" alt="Saldo e Rendimento" class="w-100" style="display: block;">
        <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; display: flex; pointer-events: none;">
            <!-- SALDO -->
            <div style="flex: 1; position: relative; height: 100%;">
                <div style="
                    position: absolute;
                    left: 25%;
                    top: 36%;
                    bottom: 26%;
                    display: flex;
                    align-items: center;
                    color: #02340e;
                    font-size: clamp(14px, 4.2vw, 22px);
                    font-weight: 800;
                    line-height: 1;
                    font-family: 'Inter', sans-serif;
                ">
                    {{ $general->cur_sym }}{{ showAmount(auth()->user()->interest_wallet) }}
                </div>
            </div>
            <!-- RENDIMENTO -->
            <div style="flex: 1; position: relative; height: 100%;">
                <div style="
                    position: absolute;
                    left: 25.5%;
                    top: 36%;
                    bottom: 26%;
                    display: flex;
                    align-items: center;
                    color: #02340e;
                    font-size: clamp(14px, 4.2vw, 22px);
                    font-weight: 800;
                    line-height: 1;
                    font-family: 'Inter', sans-serif;
                ">
                    {{ $general->cur_sym }}{{ showAmount($interests) }}
                </div>
            </div>
        </div>
    </div>
</div>

<!-- TIMER FERIADO -->
@if($isHoliday)
<div class="px-3 mt-3 text-center">
    <div style="background: linear-gradient(135deg, #1a1a2e, #16213e); border-radius: 18px; padding: 16px;">
        <div style="color: rgba(255,255,255,0.6); font-size: 11px; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 6px;">Próximo dia útil em</div>
        <div id="counter" style="color: #16a34a; font-size: 22px; font-weight: 800;"></div>
    </div>
</div>
@endif

















<!-- Daily reward div start -->

<div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog  modal-dialog-centered">
        <div class="modal-content mx-4" style="border-radius: 25px;">
            <div class="modal-header d-flex justify-content-center"
                style="background: #16a34a; border-radius: 25px 25px 0px 0px;">
                <span class=" text-center text-capitalize text-white" id="staticBackdropLabel"
                    style="font-size: 12px; font-weight: 800;">Check-in Diário
                </span>
            </div>
            <div class="modal-body">
                <div class="d-flex justify-content-center">
                    <svg fill="#16a34a" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" style="width: 34px; height: 34px; margin-right: 5px;"><path d="M0 405.3V448c0 35.3 86 64 192 64s192-28.7 192-64v-42.7C342.7 434.4 267.2 448 192 448S41.3 434.4 0 405.3zM320 128c106 0 192-28.7 192-64S426 0 320 0 128 28.7 128 64s86 64 192 64zM0 300.4V352c0 35.3 86 64 192 64s192-28.7 192-64v-51.6c-41.3 34-116.9 51.6-192 51.6S41.3 334.4 0 300.4zm416 11c57.3-11.1 96-31.7 96-55.4v-42.7c-23.2 16.4-57.3 27.6-96 34.5v63.6zM192 160C86 160 0 195.8 0 240s86 80 192 80 192-35.8 192-80-86-80-192-80zm219.3 56.3c60-10.8 100.7-32 100.7-56.3v-42.7c-35.5 25.1-96.5 38.6-160.7 41.8 29.5 14.3 51.2 33.5 60 57.2z"/></svg>
                    <h1 style="color: #020202; font-size: 25px; ">{{ $general->cur_text }}<strong>{{ showAmount(\App\Http\Controllers\User\UserController::DAILY_REWARD) }} </strong></h1>
                </div>
                <div class="text-center mt-2">
                    
                    @php
                        $alreadyCollected = auth()->user()->last_daily_reward === now()->toDateString();
                    @endphp
                    <button class="px-5 p-2" type="button"
                        style="background-color: #16a34a; color: white; border-radius: 25px; font-size: 12px; {{ $alreadyCollected ? '' : 'display:none' }}"
                        id="collect_btn" {{ $alreadyCollected ? 'disabled' : '' }}>
                       {{ $alreadyCollected ? 'Já Coletado' : 'Coletar' }}
                    </button>
                    <button class="px-4 btn text-center" type="button" onclick="collectDailyReward()"
                        style="background-color: #4ade80; color: white; border-radius: 25px; font-size: 12px; {{ $alreadyCollected ? 'display:none' : '' }}"
                        id="collect_btn2">
                        Coletar R$ 0,05
                    </button>
                    
                    
                </div>

            </div>

        </div>
    </div>
</div>



<script>
    function collectDailyReward() {
        var btn = document.getElementById('collect_btn2');
        if (btn) { btn.disabled = true; btn.textContent = 'Coletando...'; }
        fetch('{{ route("user.daily.reward") }}', {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(function(r) {
            if (!r.ok) throw new Error('HTTP ' + r.status);
            return r.json();
        })
        .then(function(data) {
            if (data.error) {
                if (btn) { btn.disabled = false; btn.textContent = 'Coletar R$ 0,05'; }
                var snackbar = document.getElementById("snackbar");
                snackbar.className = "show";
                snackbar.textContent = data.error;
                setTimeout(function() { snackbar.className = snackbar.className.replace("show", ""); }, 3000);
            } else {
                document.getElementById('collect_btn').style.display = '';
                document.getElementById('collect_btn').textContent = 'Já Coletado';
                document.getElementById('collect_btn').disabled = true;
                document.getElementById('collect_btn2').style.display = 'none';
                var snackbar = document.getElementById("snackbar");
                snackbar.className = "show";
                snackbar.textContent = 'Recompensa coletada!';
                setTimeout(function() { snackbar.className = snackbar.className.replace("show", ""); }, 3000);
            }
        })
        .catch(function(err) {
            if (btn) { btn.disabled = false; btn.textContent = 'Coletar R$ 0,05'; }
            var snackbar = document.getElementById("snackbar_error");
            if (snackbar) {
                snackbar.className = "show";
                snackbar.textContent = 'Erro ao coletar. Tente novamente.';
                setTimeout(function() { snackbar.className = snackbar.className.replace("show", ""); }, 3000);
            }
        });
    }
</script>

    <br><br><br>

@endsection

@push('script')
<script>
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
                    document.getElementById(elementId).innerHTML = "CONCLUÍDO";
                }
                tms--;
            }, 1000);
        }

        createCountDown('counter', {{\Carbon\Carbon::parse($nextWorkingDay)->diffInSeconds()}});
    @endif

    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    })

</script>
@endpush
