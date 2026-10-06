@extends($activeTemplate.'layouts.master')
@section('content')

@push('style')
<style>
    body {
        background-color: #f1f5f9 !important;
    }
    * { font-family: 'Inter', sans-serif; }
    .withdraw-card {
        background: #ffffff;
        border-radius: 20px;
        padding: 16px;
        margin-bottom: 16px;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.05);
        border: 1px solid rgba(22, 163, 74, 0.12);
    }
    .withdraw-input {
        width: 100%;
        height: 48px;
        border-radius: 12px;
        border: 1px solid #cbd5e1;
        background: #ffffff;
        padding: 0 15px;
        font-size: 14px;
        color: #0f172a;
    }
    .withdraw-input:focus {
        outline: none;
        border-color: #16a34a;
        box-shadow: 0 0 0 2px rgba(22, 163, 74, 0.2);
    }
    .withdraw-input::placeholder { color: #94a3b8; }
    .withdraw-select {
        width: 100%;
        height: 48px;
        border-radius: 12px;
        border: 1px solid #cbd5e1;
        background: #ffffff;
        padding: 0 15px;
        font-size: 14px;
        color: #0f172a;
        -webkit-appearance: none;
    }
    .withdraw-select:focus {
        outline: none;
        border-color: #16a34a;
    }
    .withdraw-select option { background: #ffffff; color: #0f172a; }
    .submit-btn {
        width: 100%;
        height: 52px;
        border-radius: 12px;
        border: none;
        background: linear-gradient(135deg, #16a34a 0%, #15803d 100%);
        color: white;
        font-size: 16px;
        font-weight: 800;
        cursor: pointer;
        box-shadow: 0 4px 14px rgba(22, 163, 74, 0.28);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .submit-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
    .pix-info-box-light {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 14px;
        margin-bottom: 18px;
    }
</style>
@endpush

<div style="margin: 0; padding: 0 0 100px;">
    {{-- Header Banner --}}
    <div style="
        background: linear-gradient(135deg, #16a34a 0%, #15803d 60%, #0b2818 100%);
        padding: 28px 16px 24px;
        text-align: center;
        border-radius: 0 0 24px 24px;
        box-shadow: 0 6px 18px rgba(22, 163, 74, 0.28);
        margin-bottom: 18px;
    ">
        <div style="color: #ffffff; font-size: 22px; font-weight: 900; letter-spacing: 1px; line-height: 1.1;">SACAR</div>
        <div style="color: rgba(255,255,255,0.85); font-size: 13px; font-weight: 600; letter-spacing: 2px; margin-top: 6px;">RETIRAR GANHOS</div>
    </div>

    {{-- Saldo --}}
    <div class="withdraw-card d-flex justify-content-between align-items-center">
        <div>
            <div style="color: #64748b; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">Saldo Disponível</div>
            <div style="color: #16a34a; font-size: 26px; font-weight: 800; margin-top: 4px;">{{ $general->cur_sym }} {{ showAmount(auth()->user()->interest_wallet) }}</div>
        </div>
        <a href="{{ route('user.withdraw.history') }}" style="
            background: linear-gradient(135deg, #16a34a 0%, #15803d 100%);
            color: #ffffff;
            font-size: 11px;
            font-weight: 700;
            padding: 9px 16px;
            border-radius: 10px;
            text-decoration: none;
        ">Histórico</a>
    </div>

    {{-- Horário de saques --}}
    <div class="withdraw-card" style="text-align: center;">
        <span style="color: #15803d; font-size: 12px; font-weight: 600;">&#128337; Saques liberados 24 horas por dia, todos os dias</span>
    </div>

    {{-- Aviso plano ativo + taxa --}}
    <div class="withdraw-card">
        <div style="margin-bottom: 12px;">
            <div style="color: #b45309; font-size: 12px; font-weight: 700; line-height: 1.3;">Para sacar, é preciso ter assinado um plano</div>
            @if(!$hasActivePlan)
            <div style="color: #64748b; font-size: 11px; line-height: 1.4; margin-top: 2px;">Adquira um plano no painel para liberar o saque</div>
            @else
            <div style="color: #16a34a; font-size: 11px; line-height: 1.4; margin-top: 2px;">Seu plano está ativo, você já pode solicitar saques</div>
            @endif
        </div>
        <div style="display: flex; align-items: center; gap: 10px;">
            <div style="width: 28px; height: 28px; border-radius: 8px; background: rgba(22, 163, 74, 0.1); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="5" x2="5" y2="19"></line><circle cx="6.5" cy="6.5" r="2.5"></circle><circle cx="17.5" cy="17.5" r="2.5"></circle></svg>
            </div>
            <div>
                <div style="color: #15803d; font-size: 12px; font-weight: 700; line-height: 1.3;">Taxa de saque: R$ {{ number_format((float) ($withdrawMethod->first()->fixed_charge ?? 0), 2, ',', '.') }}{{ (float) ($withdrawMethod->first()->percent_charge ?? 0) > 0 ? ' + ' . number_format((float) $withdrawMethod->first()->percent_charge, 2, ',', '.') . '%' : '' }} · Mínimo: R$ {{ number_format((float) ($withdrawMethod->first()->min_limit ?? 0), 2, ',', '.') }}</div>
                <div style="color: #64748b; font-size: 11px; line-height: 1.4;">Taxa descontada do valor solicitado no ato da confirmação</div>
            </div>
        </div>
    </div>

    {{-- Formulário --}}
    <div class="withdraw-card">
        {{-- Etapa 1: Dados PIX --}}
        <div style="color: #15803d; font-size: 15px; font-weight: 800; margin-bottom: 14px; letter-spacing: 0.5px;">Dados para Saque</div>

        <div id="pixSection">
        @if(auth()->user()->pix_key)
            <div class="pix-info-box-light">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                    <span style="color: #64748b; font-size: 12px; text-transform: uppercase;">Seus dados PIX</span>
                    @if(!$pixLocked)
                        <button type="button" onclick="editPix()" style="background: none; border: none; color: #15803d; font-size: 12px; cursor: pointer; font-weight: 700;">Editar</button>
                    @else
                        <span style="color: #94a3b8; font-size: 11px;">Travado</span>
                    @endif
                </div>
                <div style="color: #0f172a; font-size: 13px; line-height: 1.8;">
                    <div><span style="color: #64748b;">Nome:</span> {{ auth()->user()->pix_name }}</div>
                    <div><span style="color: #64748b;">Tipo:</span> {{ auth()->user()->pix_type }}</div>
                    <div><span style="color: #64748b;">Chave:</span> {{ auth()->user()->pix_key }}</div>
                </div>
                <div style="color: #94a3b8; font-size: 11px; margin-top: 8px;">
                    @if($pixLocked) Travados após saque aprovado — não podem mais ser alterados. @else Salvos automaticamente para suas próximas solicitações. @endif
                </div>
            </div>
        @else
            <form action="{{ route('user.withdraw.pix') }}" method="post" id="pixForm">
                @csrf
                <div style="margin-bottom: 14px;">
                    <label style="color: #64748b; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px; display: block;">Nome completo</label>
                    <input type="text" name="pix_name" class="withdraw-input" placeholder="Seu nome completo" required>
                </div>
                <div style="margin-bottom: 14px;">
                    <label style="color: #64748b; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px; display: block;">Tipo de chave PIX</label>
                    <select name="pix_type" class="withdraw-select" required>
                        <option value="">Selecione</option>
                        <option value="CPF">CPF</option>
                        <option value="CNPJ">CNPJ</option>
                        <option value="E-mail">E-mail</option>
                        <option value="Celular">Celular</option>
                        <option value="Chave Aleatória">Chave Aleatória</option>
                    </select>
                </div>
                <div style="margin-bottom: 18px;">
                    <label style="color: #64748b; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px; display: block;">Chave PIX</label>
                    <input type="text" name="pix_key" class="withdraw-input" placeholder="Informe sua chave PIX" required>
                </div>
                <button type="submit" class="submit-btn">Salvar Dados</button>
            </form>
        @endif
        </div>

        {{-- Etapa 2: Solicitar saque (só com dados salvos) --}}
        @if(auth()->user()->pix_key)
        <form action="{{ route('user.withdraw.money') }}" method="post" id="withdrawForm" style="margin-top: 18px;">
            @csrf
            <input type="hidden" name="method_code" value="{{ $withdrawMethod->first()->id ?? '' }}">
            <input type="hidden" name="pix_name" value="{{ auth()->user()->pix_name }}">
            <input type="hidden" name="pix_type" value="{{ auth()->user()->pix_type }}">
            <input type="hidden" name="pix_key" value="{{ auth()->user()->pix_key }}">

            {{-- Valor --}}
            <div style="margin-bottom: 18px;">
                <label style="color: #64748b; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; display: block;">
                    Valor do saque
                </label>
                <div style="position: relative;">
                    <input type="text" inputmode="numeric" name="amount" class="withdraw-input" id="amountField" placeholder="Digite o valor (sem centavos)" required oninput="calcReceive()" style="padding-right: 50px;">
                    <span style="position: absolute; right: 14px; top: 50%; transform: translateY(-50%); color: #16a34a; font-weight: 800; font-size: 14px;">R$</span>
                </div>
            </div>

            {{-- Resumo --}}
            <div id="receiveInfo" style="display:none; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 14px; padding: 14px; margin-bottom: 18px;">
                <div style="display: flex; justify-content: space-between; font-size: 12px; color: #64748b; margin-bottom: 6px;">
                    <span>Taxa de saque</span>
                    <span id="feeDisplay" style="color: #ef4444;">- R$ 0,00</span>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 15px; font-weight: 700;">
                    <span style="color: #0f172a;">Você receberá</span>
                    <span id="receiveDisplay" style="color: #16a34a;">R$ 0,00</span>
                </div>
                <div id="intWarning" style="display:none; color: #b45309; font-size: 11px; margin-top: 8px;">O saque não pode ter centavos. Use um valor inteiro (ex: 30).</div>
            </div>

            <button type="submit" id="submitBtn" class="submit-btn" {{ !$hasActivePlan ? 'disabled style="opacity:.45;cursor:not-allowed;"' : '' }}>Solicitar Saque</button>
            @if(!$hasActivePlan)
                <p style="color: #b45309; font-size: 12px; margin-top: 10px; text-align: center; line-height: 1.5;">Para solicitar um saque é preciso ter assinado um plano.</p>
            @endif
        </form>
        @else
            <p style="color: #64748b; font-size: 12px; margin-top: 16px; text-align: center; line-height: 1.5;">
                Salve seus dados PIX acima para liberar a solicitação de saque.
            </p>
        @endif
    </div>

    {{-- Bottom border --}}
    <div style="height: 4px; background: linear-gradient(90deg, #16a34a, #4ade80, #16a34a); border-radius: 4px;"></div>
</div>

<div id="wsnackbar"></div>
<style>
#wsnackbar {
    visibility: hidden;
    min-width: 260px;
    max-width: 88%;
    left: 50%;
    transform: translateX(-50%);
    background-color: #ffffff;
    border: 1px solid rgba(22, 163, 74, 0.5);
    color: #0f172a;
    text-align: center;
    border-radius: 12px;
    padding: 13px 18px;
    position: fixed;
    z-index: 99999;
    bottom: 95px;
    font-size: 14px;
    font-weight: 600;
    box-shadow: 0 8px 24px rgba(0,0,0,.15);
}
#wsnackbar.error { color: #b91c1c; border-color: #ef4444; }
#wsnackbar.show { visibility: visible; animation: wsfadein .4s, wsfadeout .4s 3s forwards; }
@keyframes wsfadein { from { bottom: 0; opacity: 0; } to { bottom: 95px; opacity: 1; } }
@keyframes wsfadeout { from { bottom: 95px; opacity: 1; } to { bottom: 0; opacity: 0; } }
</style>

<script>
    function showWsnackbar(msg, type) {
        var s = document.getElementById('wsnackbar');
        if (!s) return;
        s.textContent = msg;
        s.className = type === 'error' ? 'show error' : 'show';
        setTimeout(function(){ s.className = s.className.replace('show', '').replace('error', '').trim(); }, 3400);
    }
</script>

<script>
    var FEE_PERCENT = {{ (float) ($withdrawMethod->first()->percent_charge ?? 0) }};
    var FIXED_CHARGE = {{ (float) ($withdrawMethod->first()->fixed_charge ?? 0) }};
    var MIN_AMOUNT = {{ (float) ($withdrawMethod->first()->min_limit ?? 0) }};

    function parseAmount(str) {
        if (str === null || str === undefined) return 0;
        str = String(str).trim();
        var normalized = str.replace(/\.(?=\d{3}[.,]|$)/g, '').replace(',', '.');
        var val = parseFloat(normalized);
        return isNaN(val) ? 0 : val;
    }

    function amountInput() {
        return document.getElementById('amountField') || document.querySelector('input[name="amount"]');
    }

    function calcReceive() {
        var el = amountInput();
        if (!el) return;
        var val = parseAmount(el.value);
        var info = document.getElementById('receiveInfo');
        var warn = document.getElementById('intWarning');
        if (val > 0) {
            var fee = FIXED_CHARGE + (val * FEE_PERCENT / 100);
            var receive = val - fee;
            document.getElementById('feeDisplay').textContent = '- R$ ' + fee.toFixed(2).replace('.', ',');
            document.getElementById('receiveDisplay').textContent = 'R$ ' + receive.toFixed(2).replace('.', ',');
            info.style.display = 'block';
            warn.style.display = (val % 1 !== 0) ? 'block' : 'none';
        } else {
            info.style.display = 'none';
        }
    }

    function editPix() {
        var box = document.getElementById('pixSection');
        var pixName = @json(auth()->user()->pix_name);
        var pixType = @json(auth()->user()->pix_type);
        var pixKey = @json(auth()->user()->pix_key);

        box.innerHTML = `
            <form action="{{ route('user.withdraw.pix') }}" method="post" id="pixForm">
                @csrf
                <div style="margin-bottom: 14px;">
                    <label style="color: #64748b; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px; display: block;">Nome completo</label>
                    <input type="text" name="pix_name" class="withdraw-input" value="${pixName}" required>
                </div>
                <div style="margin-bottom: 14px;">
                    <label style="color: #64748b; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px; display: block;">Tipo de chave PIX</label>
                    <select name="pix_type" class="withdraw-select" required>
                        <option value="">Selecione</option>
                        <option value="CPF" ${pixType == 'CPF' ? 'selected' : ''}>CPF</option>
                        <option value="CNPJ" ${pixType == 'CNPJ' ? 'selected' : ''}>CNPJ</option>
                        <option value="E-mail" ${pixType == 'E-mail' ? 'selected' : ''}>E-mail</option>
                        <option value="Celular" ${pixType == 'Celular' ? 'selected' : ''}>Celular</option>
                        <option value="Chave Aleatória" ${pixType == 'Chave Aleatória' ? 'selected' : ''}>Chave Aleatória</option>
                    </select>
                </div>
                <div style="margin-bottom: 18px;">
                    <label style="color: #64748b; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px; display: block;">Chave PIX</label>
                    <input type="text" name="pix_key" class="withdraw-input" value="${pixKey}" required>
                </div>
                <div style="color: #64748b; font-size: 11px; margin-bottom: 12px;">Esses dados serão salvos para suas próximas solicitações.</div>
                <button type="submit" class="submit-btn">Salvar Dados</button>
                <button type="button" style="background: none; border: none; color: #ef4444; font-size: 12px; cursor: pointer; margin-left: 10px; font-weight: 600;" onclick="location.reload()">Cancelar</button>
            </form>
        `;
    }
</script>

@endsection

@push('script')
<script>
  (function($) {
    "use strict";
    $('#withdrawForm').on('submit', function(e) {
      e.preventDefault();
      var amount = parseAmount(($('#amountField').val() || $('input[name="amount"]').val()) || '');
      var balance = {{ (float) auth()->user()->interest_wallet }};
      var hasActivePlan = @json($hasActivePlan);
      if (!hasActivePlan) {
        showWsnackbar('Para solicitar um saque é preciso ter assinado um plano.', 'error');
        return false;
      }
      if (amount <= 0) {
        showWsnackbar('Informe um valor válido.', 'error');
        return false;
      }
      if (amount % 1 !== 0) {
        showWsnackbar('O valor do saque deve ser um número inteiro, sem centavos (ex: 100, 20, 56).', 'error');
        return false;
      }
      if (MIN_AMOUNT > 0 && amount < MIN_AMOUNT) {
        showWsnackbar('Valor mínimo para saque: R$ ' + MIN_AMOUNT, 'error');
        return false;
      }
      if (amount > balance) {
        showWsnackbar('Saldo insuficiente na carteira selecionada. Disponível: R$ ' + balance.toFixed(2).replace('.', ','), 'error');
        return false;
      }
      if ($('#submitBtn').prop('disabled')) {
        return false;
      }
      $('#submitBtn').prop('disabled', true).text('Processando...');

      var normalized = amount.toFixed(0);
      $('#amountField').val(normalized); $('input[name="amount"]').val(normalized);

      var formData = new FormData($('#withdrawForm')[0]);
      var csrfMeta = $('meta[name="csrf-token"]').attr('content');
      var csrfInput = formData.get('_token');
      $.ajax({
        url: '{{ route("user.withdraw.money") }}',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        headers: {
          'X-CSRF-TOKEN': csrfMeta || csrfInput || '',
          'Accept': 'application/json'
        },
        success: function(response) {
          window.location.href = '{{ route("user.withdraw.history") }}';
        },
        error: function(xhr) {
          $('#submitBtn').prop('disabled', false).text('Solicitar Saque');
          var msg = 'Erro ao processar saque.';
          if (xhr.responseJSON && xhr.responseJSON.errors) {
            var errs = xhr.responseJSON.errors;
            var first = Object.values(errs)[0];
            msg = Array.isArray(first) ? first[0] : first;
          } else if (xhr.responseJSON && xhr.responseJSON.message) {
            msg = xhr.responseJSON.message;
          } else if (xhr.responseText) {
            try {
              var d = JSON.parse(xhr.responseText);
              if (d.message) msg = d.message;
            } catch(ex) {}
          }
          showWsnackbar(msg, 'error');
        }
      });
    });
  })(jQuery);
</script>
@endpush