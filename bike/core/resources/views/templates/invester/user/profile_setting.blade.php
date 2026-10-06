@extends($activeTemplate.'layouts.master')
@section('content')

<style>
    body { font-family: 'Inter', sans-serif; }

    #header-card {
        background: #3F3F3F;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
        border-radius: 0px 0px 0px 47px;
    }

    .plans-label {
        font-size: 18px;
        font-weight: 700;
        color: white;
    }

    .text-overlay-mine {
        position: absolute;
        left: 22%;
        top: 44px;
        transform: translate(-50%, -50%);
        padding: 10px;
    }

    .more-div-icon { width: 24px; height: 24px; }

    .heading_label {
        border-radius: 0px 0px 40px 40px;
        background: url('{{ asset('core/img/partedoperfil.webp?v=' . time()) }}') no-repeat center center / 100% 100%;
        height: 220px;
    }

    .text-overlay {
        position: absolute;
        top: 66%;
        left: 34%;
        transform: translate(-50%, -50%);
    }

    .mine-page-heading-white {
        color: #000000;
        font-size: 11px;
        font-weight: 100;
        line-height: 11px;
        margin-top: 5px;
    }

    .mine-page-sub-heading-white {
        color: #000000;
        font-size: 10px;
        font-weight: 700;
        margin-top: 5px;
    }

    .plan-card {
        width: 175px;
        height: 100px;
        position: relative;
        background-size: cover;
    }

    .navigation-bar {
        box-shadow: 0px 0px 4px 0px #0000001A;
        border-radius: 10px;
        margin-top: 5px;
    }
</style>

    <div class="mx-auto" id="snackbar_error"></div>
    <div id="snackbar"></div>

    <div class="heading_label m-0 p-0 px-3 pt-2" style="position: relative;">
        <div class="d-flex justify-content-between ">
            <div class="d-flex">
                <div>
                    <svg fill="#ffffff" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 496 512" style="width: 67px; height: 67px; opacity: 0.9; margin-right: 5px;"><path d="M248 8C111 8 0 119 0 256s111 248 248 248 248-111 248-248S385 8 248 8zm0 96c48.6 0 88 39.4 88 88s-39.4 88-88 88-88-39.4-88-88 39.4-88 88-88zm0 344c-58.7 0-111.3-26.6-146.5-68.2 18.8-35.4 55.6-59.8 98.5-59.8 2.4 0 4.8.4 7.1 1.1 13 4.2 26.6 6.9 40.9 6.9 14.3 0 28-2.7 40.9-6.9 2.3-.7 4.7-1.1 7.1-1.1 42.9 0 79.7 24.4 98.5 59.8C359.3 421.4 306.7 448 248 448z"/></svg>
                </div>
                <div class="align-self-center ms-1">
                    <h1 style="font-family: 'Inter', sans-serif; font-size: 15px; font-weight: 400; color: #FFFFFF; text-align: left;">ID:Código</h1>
                    <h1 style="font-family: 'Inter', sans-serif; font-size: 14px; font-weight: 700; color: #FFFFFF; text-align: left;">{{ auth()->user()->username }}</h1>
                </div>
            </div>
            
            <div data-bs-toggle="modal" data-bs-target="#exampleModal" style="position: absolute; right: 15px; top: 15px; width: 100px; height: 90px; cursor: pointer; z-index: 10;">
            </div>
        </div>

        <div class="row m-0 p-0 mt-4">
            <div class="col-3">
                <a href="{{route ('user.deposit.index')}}">
                    <div class="wallet_box text-center">
                        <img src="{{ asset('core/img/mine-recharge.webp?v=' . time()) }}" style="width: 54px;">
                        <h4 style="color: #FFFFFF; font-size: 10px; font-weight: 700;">Recarregar</h4>
                    </div>
                </a>
            </div>
            <div class="col-3">
                <a href="{{route ('user.withdraw')}}">
                    <div class="wallet_box text-center">
                        <img src="{{ asset('core/img/mine-withdraw.webp?v=' . time()) }}" style="width: 54px;">
                        <h4 style="color: #FFFFFF; font-size: 10px; font-weight: 700;">Sacar</h4>
                    </div>
                </a>
            </div>
            <div class="col-3">
                <a href="{{ route('user.invest.log') }}">
                    <div class="wallet_box text-center">
                        <img src="{{ asset('core/img/mine-account.webp?v=' . time()) }}" style="width: 54px;">
                        <h4 style="color: #FFFFFF; font-size: 10px; font-weight: 700;">Ganhos</h4>
                    </div>
                </a>
            </div>
            <div class="col-3">
                <a href="{{ route('user.referrals') }}">
                    <div class="wallet_box text-center">
                        <img src="{{ asset('core/img/mine-task.webp?v=' . time()) }}" style="width: 54px;">
                        <h4 style="color: #FFFFFF; font-size: 10px; font-weight: 700;">Equipe</h4>
                    </div>
                </a>
            </div>
        </div>
    </div>

    {{-- Codigo bonus --}}
    <div class="mx-1 mt-2 mb-3 withdraw-card" style="background: #ffffff; border-radius: 16px; padding: 16px; border: 1px solid rgba(22,163,74,0.12);">
        <div class="d-flex align-items-center mb-2">
            <img src="{{ asset('core/img/mine-task.webp?v=' . time()) }}" style="width: 22px;" class="me-2">
            <h5 style="margin: 0; font-weight: 800; color: #0f172a; font-size: 15px;">Código Bônus</h5>
        </div>
        <div class="d-flex gap-2">
            <input type="text" id="bonusCodeInput" class="form-control" placeholder="Digite o código bônus"
                style="border-radius: 12px; border: 2px solid #e2e8f0; font-weight: 700; text-transform: uppercase;"
                autocomplete="off">
            <button type="button" id="bonusRedeemBtn"
                style="background: linear-gradient(135deg, #16a34a, #22c55e); color: #fff; border: none; border-radius: 12px; padding: 8px 18px; font-weight: 800; white-space: nowrap;">Resgatar</button>
        </div>
        <div id="bonusMsg" style="display:none; margin-top: 10px; font-size: 12px; font-weight: 700;"></div>
    </div>

    {{-- Celebracao codigo bonus --}}
    <div id="bonus-celebration" style="display:none; position:fixed; inset:0; z-index:1000001; background:rgba(2,44,34,0.82); backdrop-filter:blur(8px); -webkit-backdrop-filter:blur(8px); justify-content:center; align-items:center;">
        <div id="confetti-container" style="position:absolute; inset:0; overflow:hidden; pointer-events:none;"></div>
        <div style="position:relative; z-index:1000003; background:#fff; border-radius:24px; padding:32px 40px; text-align:center; box-shadow:0 20px 60px rgba(0,0,0,0.4); max-width:340px; margin:0 16px;">
            <div style="font-size:64px; line-height:1; margin-bottom:8px;">🎉</div>
            <div id="bonus-celebration-msg" style="font-size:20px; font-weight:800; color:#16a34a;">Código resgatado!</div>
            <div style="font-size:13px; color:#64748b; margin-top:8px;">O valor já foi adicionado ao seu saldo.</div>
        </div>
    </div>

    <script>
    (function () {
        var btn = document.getElementById('bonusRedeemBtn');
        if (!btn) return;
        btn.addEventListener('click', function () {
            var input = document.getElementById('bonusCodeInput');
            var box = document.getElementById('bonusMsg');
            var code = (input.value || '').trim().toUpperCase();
            box.style.display = 'none';
            if (!code) {
                box.textContent = 'Digite um código.';
                box.style.color = '#b91c1c';
                box.style.display = 'block';
                return;
            }
            btn.disabled = true;
            btn.textContent = 'Resgatando...';
            fetch('{{ route('user.bonus.redeem') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ code: code })
            })
            .then(function (r) { return r.json().then(function (j) { return { s: r.status, j: j }; }); })
            .then(function (o) {
                box.textContent = o.j.message || o.j.error || 'Erro.';
                box.style.color = o.s === 200 ? '#15803d' : '#b91c1c';
                box.style.display = 'block';
                if (o.s === 200) {
                    input.value = '';
                    showBonusCelebration(o.j.message || 'Código resgatado!');
                }
            })
            .catch(function () {
                box.textContent = 'Erro de conexão.';
                box.style.color = '#b91c1c';
                box.style.display = 'block';
            })
            .finally(function () {
                btn.disabled = false;
                btn.textContent = 'Resgatar';
            });
        });
    })();

    function showBonusCelebration(msg) {
        var overlay = document.getElementById('bonus-celebration');
        overlay.style.display = 'flex';
        overlay.querySelector('#bonus-celebration-msg').textContent = msg;
        launchConfetti();
        setTimeout(function () { overlay.style.display = 'none'; }, 4000);
    }

    function launchConfetti() {
        var colors = ['#16a34a', '#22c55e', '#fbbf24', '#f59e0b', '#3b82f6', '#ef4444', '#a855f7'];
        var container = document.getElementById('confetti-container');
        container.innerHTML = '';
        for (var i = 0; i < 80; i++) {
            var c = document.createElement('div');
            var size = 6 + Math.random() * 8;
            c.style.cssText = 'position:absolute;width:' + size + 'px;height:' + (size * 0.5) + 'px;'
                + 'background:' + colors[Math.floor(Math.random() * colors.length)] + ';'
                + 'left:' + (Math.random() * 100) + '%;top:-20px;'
                + 'opacity:' + (0.7 + Math.random() * 0.3) + ';'
                + 'border-radius:' + (Math.random() > 0.5 ? '50%' : '2px') + ';'
                + 'z-index:1000002;';
            var fall = document.createElement('style');
            var dur = 2200 + Math.random() * 1800;
            var delay = Math.random() * 600;
            var drift = (Math.random() - 0.5) * 200;
            var rot = 360 + Math.random() * 720;
            c.style.animation = 'confetti-fall-' + i + ' ' + dur + 'ms linear ' + delay + 'ms forwards';
            var kf = '@keyframes confetti-fall-' + i + ' {'
                + '0% { transform: translateY(0) translateX(0) rotate(0deg); opacity: 1; }'
                + '100% { transform: translateY(' + (window.innerHeight + 60) + 'px) translateX(' + drift + 'px) rotate(' + rot + 'deg); opacity: 0.4; }'
                + '}';
            fall.textContent = kf;
            document.head.appendChild(fall);
            container.appendChild(c);
        }
        setTimeout(function () { container.innerHTML = ''; }, 4800);
    }
    </script>

@php
    use App\Models\Transaction;
    $authUserId = Auth::id();
    $referralCommission = Transaction::where('user_id', $authUserId)
                                    ->where('remark', 'referral_commission')
                                    ->sum('amount');
    $userCount = App\Models\User::where('ref_by', Auth::id())->count();
@endphp

    <div class="mx-1 mt-2 mb-3" style="position: relative; background: url('{{ asset('core/img/ladoperfil.webp?v=6') }}') no-repeat center center / 100% 100%; border-radius: 16px; min-height: 110px; padding: 58px 2px 5px 2px; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
        <div class="d-flex justify-content-between" style="width: 100%;">
            <div style="flex: 1; text-align: center; padding: 0 1px;">
                <span style="font-size: 7.5px; font-weight: 700; color: #333; display: block; line-height: 1.1; white-space: normal; word-wrap: break-word;">Saldo da Conta</span>
                <span style="font-size: 9px; font-weight: 800; color: #16a34a; display: block; margin-top: 1px;">{{ $general->cur_text }} {{ showAmount(auth()->user()->interest_wallet) }}</span>
            </div>
            <div style="flex: 1; text-align: center; padding: 0 1px;">
                <span style="font-size: 7.5px; font-weight: 700; color: #333; display: block; line-height: 1.1; white-space: normal; word-wrap: break-word;">Total Depositos</span>
                <span style="font-size: 9px; font-weight: 800; color: #16a34a; display: block; margin-top: 1px;">{{ $general->cur_text }} {{ showAmount(\App\Models\Deposit::where('user_id', auth()->id())->where('status', 1)->sum('amount')) }}</span>
            </div>
            <div style="flex: 1; text-align: center; padding: 0 1px;">
                <span style="font-size: 7.5px; font-weight: 700; color: #333; display: block; line-height: 1.1; white-space: normal; word-wrap: break-word;">Renda da Equipe</span>
                <span style="font-size: 9px; font-weight: 800; color: #9333ea; display: block; margin-top: 1px;">{{ $general->cur_text }} {{ showAmount($referralCommission) }}</span>
            </div>
            <div style="flex: 1; text-align: center; padding: 0 1px;">
                <span style="font-size: 7.5px; font-weight: 700; color: #333; display: block; line-height: 1.1; white-space: normal; word-wrap: break-word;">Total Pessoas</span>
                <span style="font-size: 9px; font-weight: 800; color: #2563eb; display: block; margin-top: 1px;">{{ $userCount }}</span>
            </div>
        </div>
    </div>

    <!-- Banner Meio / Area de Perfil -->
    <div class="mx-2 mb-3 mt-1">
        <img src="{{ asset('core/img/areadeperfil.webp?v=' . time()) }}" alt="Explore Novas Rotas - Wind Bikes" style="width: 100%; border-radius: 16px; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
    </div>

    <div class="mt-3 mx-3">
        <a href="{{route ('user.invest.log')}}">
            <div class="d-flex justify-content-between navigation-bar p-2">
                <div>
                    <img src="{{ asset('core/img/income-record.webp?v=' . time()) }}" style="width: 18px">
                    <span style="font-size: 12px; font-weight: 400; color: #000000; margin-left: 7px;">Registro de Rendimentos</span>
                </div>
                <div></div>
            </div>
        </a>

        <a href="{{route ('user.deposit.history')}}">
            <div class="d-flex justify-content-between navigation-bar p-2">
                <div>
                    <img src="{{ asset('core/img/recharge-record.webp?v=' . time()) }}" style="width: 16px">
                    <span style="font-size: 12px; font-weight: 400; color: #000000;margin-left: 7px;">Registro de Depósitos</span>
                </div>
                <div></div>
            </div>
        </a>

        <a href="{{route ('user.withdraw.history')}}">
            <div class="d-flex justify-content-between navigation-bar p-2">
                <div>
                    <img src="{{ asset('core/img/withdraw-record.webp?v=' . time()) }}" style="width: 22px"> 
                    <span style="font-size: 12px; font-weight: 400; color: #000000;margin-left: 7px;">Registro de Saques</span>
                </div>
                <div></div>
            </div>
        </a>
    </div>

    <div class="text-center m-2" style="margin-bottom: 90px !important;">
        <a href="{{route ('user.logout')}}">
            <button class="p-3" style="background: #dc3545; border-radius: 10px; width: 162px;">
                <h6 class="align-self-center" style="font-size: 12.12px; font-weight: 600; color: #FFFFFF;">Deslogar</h6>
            </button>
        </a>
    </div>

    <!-- Daily reward modal -->
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
                        <h1 style="color: #020202; font-size: 25px; ">{{ $general->cur_text }}<strong>{{ showAmount(1.00) }}</strong></h1>
                    </div>
                    <div class="text-center mt-2">
                        <button class="px-5 p-2" type="button"
                            style="background-color: #16a34a; color: white; border-radius: 25px; font-size: 12px;"
                            id="collect_btn">
                           Já Coletado
                        </button>
                        <button class="px-4 btn text-center" type="button" onclick="collectedReward()"
                            style="background-color: #16a34a; color: white; border-radius: 25px; font-size: 12px;display:none"
                            id="collect_btn2">
                           Coletado
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function collectedReward() {
            var modal = bootstrap.Modal.getInstance(document.getElementById('exampleModal'));
            if (modal) modal.hide();
            var snackbar = document.getElementById("snackbar");
            snackbar.className = "show";
            snackbar.textContent = 'Já Coletado';
            setTimeout(function () {
                snackbar.className = snackbar.className.replace("show", "");
            }, 3000);
        }
    </script>

    <script>
        var mineDiv = document.getElementById("mine_div");
        if (mineDiv) mineDiv.classList.add("active");
    </script>

@endsection
