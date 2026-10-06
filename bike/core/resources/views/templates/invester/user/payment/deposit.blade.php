@extends($activeTemplate.'layouts.master')
@section('content')
<style>
  * { font-family: 'Inter', sans-serif; }
  .recharge-label { font-size: 16px; font-weight: 500; color: #000; font-family: 'Inter', sans-serif; }
  .form-group { margin-bottom: 5px; margin-top: 20px; }
  .form-group label { font-size: 12px; display: block; font-weight: 700; color: #000000B2; }
  .form-group input, select { width: 100%; height: 40px; padding-left: 8px; border-radius: 10px; box-shadow: 0px 0px 6px 0px #0000001A; font-size: 10px; font-weight: 400; margin-top: 8px; border: none; }
  ::placeholder { color: #00000040; font-size: 10px; font-weight: 400; }
</style>

<div class="mx-auto" style="position: relative;">
  <div id="snackbar_error"></div>
</div>
<div class="mx-auto col-sm-10">
  <h4 id="snackbar"></h4>
</div>

<h2 class="recharge-label m-3">Recarregar</h2>

<div class="mx-3 mt-2 mb-4 d-flex justify-content-between align-items-center p-3" style="background: linear-gradient(135deg, #ffffff, #f9f9f9); border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border: 1px solid rgba(0,0,0,0.05);">
    <div>
      <h5 style="color: #333; font-weight: 700; font-size: 18px; margin: 0;">{{ $general->cur_text }} {{ showAmount(auth()->user()->deposit_wallet + auth()->user()->interest_wallet) }}</h5>
      <span style="color: #666; font-size: 13px; font-weight: 500;">Saldo da Conta</span>
    </div>
    <a href="{{route ('user.deposit.history')}}" style="display: flex; align-items: center; justify-content: center; background: #f0fdf4; border-radius: 8px; padding: 8px;">
      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
    </a>
</div>

<div id="depositForm">
  <div class="card mx-3">
    <div class="card-body">
      <div class="row">
        <div class="col-12">
          <div class="form-group">
            <label class="form-label">Forma de pagamento</label>
            <select class="form-select form-control form--control" name="gateway" disabled>
              <option>PIX</option>
            </select>
          </div>
        </div>
      </div>
    </div>
  </div>

  <center>
    <span style="font-size: 13px;">Depósito mínimo R$ 15,00 e máximo R$ 5.000,00</span>
  </center>

  <div class="form-group mx-3 mt-2">
    <div class="d-flex align-items-end mb-1">
      <span>
        <img src="{{asset ('core/img/recharge-label.webp')}}" style="width: 16px;">
      </span>
      <label for="holder-name" class="ms-2">Selecione o valor</label>
    </div>
    <input type="text" inputmode="decimal" class="input-field no-arrow recharge-input" name="amount" required id="amountField"
      style="box-shadow: 0px 0px 5px 0px #47474740; color: #000; border: none;"
      placeholder="Digite o valor">
  </div>

  <div class="d-flex justify-content-between px-3 mt-2 gap-2" id="quick-values">
    <div class="quick-val-btn" data-val="30.00" style="flex:1; border: 2px solid #16a34a; color: #16a34a; background: #fff; border-radius: 8px; font-weight: 600; font-size: 14px; text-align: center; padding: 10px 0; cursor: pointer;">R$ 30</div>
    <div class="quick-val-btn" data-val="100.00" style="flex:1; border: 2px solid #16a34a; color: #16a34a; background: #fff; border-radius: 8px; font-weight: 600; font-size: 14px; text-align: center; padding: 10px 0; cursor: pointer;">R$ 100</div>
    <div class="quick-val-btn" data-val="200.00" style="flex:1; border: 2px solid #16a34a; color: #16a34a; background: #fff; border-radius: 8px; font-weight: 600; font-size: 14px; text-align: center; padding: 10px 0; cursor: pointer;">R$ 200</div>
    <div class="quick-val-btn" data-val="1000.00" style="flex:1; border: 2px solid #16a34a; color: #16a34a; background: #fff; border-radius: 8px; font-weight: 600; font-size: 14px; text-align: center; padding: 10px 0; cursor: pointer;">R$ 1000</div>
    <div class="quick-val-btn" data-val="5000.00" style="flex:1; border: 2px solid #16a34a; color: #16a34a; background: #fff; border-radius: 8px; font-weight: 600; font-size: 14px; text-align: center; padding: 10px 0; cursor: pointer;">R$ 5000</div>
  </div>
  <script>
  (function(){
    var container = document.getElementById('quick-values');
    if (!container) return;
    container.addEventListener('click', function(e) {
      var btn = e.target.closest ? e.target.closest('.quick-val-btn') : null;
      if (!btn) return;
      e.preventDefault();
      var val = btn.getAttribute('data-val');
      var field = document.getElementById('amountField') || document.querySelector('input[name="amount"]');
      if (field) {
        field.value = val;
        field.style.color = '#000';
        field.dispatchEvent(new Event('input', { bubbles: true }));
        field.dispatchEvent(new Event('change', { bubbles: true }));
      }
      var all = container.querySelectorAll('.quick-val-btn');
      for (var j = 0; j < all.length; j++) {
        var active = all[j] === btn;
        all[j].style.background = active ? '#16a34a' : '#fff';
        all[j].style.color = active ? '#fff' : '#16a34a';
      }
    });
  })();
  </script>

  <div class="d-flex justify-content-center mt-3">
    <button class="text-center mt-2 px-4 w-75" type="button" id="recarregarBtn" style="background: linear-gradient(135deg, #16a34a, #22c55e); color: white; font-weight: 700; border: none; border-radius: 24px; height: 48px; font-size: 17px; box-shadow: 0 4px 15px rgba(22,163,74,0.3);">Recarregar</button>
  </div>

  <div class="mx-3 mt-4" style="margin-bottom: 80px !important;">
    <h6 style="font-weight: 700; color: #333; font-size: 14px; margin-bottom: 12px; display: flex; align-items: center;">
      <svg fill="#16a34a" viewBox="0 0 512 512" style="width: 16px; height: 16px; margin-right: 8px;"><path d="M256 8C119.043 8 8 119.083 8 256c0 136.997 111.043 248 248 248s248-111.003 248-248C504 119.083 392.957 8 256 8zm0 110c23.196 0 42 18.804 42 42s-18.804 42-42 42-42-18.804-42-42 18.804-42 42-42zm56 254c0 6.627-5.373 12-12 12h-88c-6.627 0-12-5.373-12-12v-24c0-6.627 5.373-12 12-12h12v-64h-12c-6.627 0-12-5.373-12-12v-24c0-6.627 5.373-12 12-12h64c6.627 0 12 5.373 12 12v100h12c6.627 0 12 5.373 12 12v24z"/></svg>
      Como recarregar?
    </h6>
    <div class="card border-0" style="background: #f0fdf4; border-radius: 12px; box-shadow: 0 2px 10px rgba(22,163,74,0.05);">
      <div class="card-body p-3" style="font-size: 13px; color: #555; line-height: 1.6;">
        <ul class="mb-0 ps-3" style="list-style-type: decimal;">
          <li class="mb-2"><strong>Selecione ou digite</strong> o valor que deseja depositar.</li>
          <li class="mb-2">Clique em <strong style="color: #16a34a;">Recarregar</strong> para gerar a sua cobrança PIX.</li>
          <li class="mb-2"><strong>Copie o código</strong> (PIX Copia e Cola) ou escaneie o <strong>QR Code</strong> direto no app do seu banco.</li>
          <li>Pronto! O saldo será adicionado à sua conta automaticamente.</li>
        </ul>
      </div>
    </div>
  </div>
</div>

<script>
document.getElementById('recarregarBtn').onclick = function() {
  var amountField = document.querySelector('input[name="amount"]');
  var rawValue = amountField ? amountField.value : '';
  var amountValue = rawValue.trim().replace(',', '.');
  var parsedAmount = parseFloat(amountValue);
  var snackbar = document.getElementById("snackbar_error");

  if (isNaN(parsedAmount) || parsedAmount < 15) {
    if (snackbar) {
      snackbar.className = "show";
      snackbar.textContent = 'Depósito Mínimo R$ 15,00';
      setTimeout(function () { snackbar.className = snackbar.className.replace("show", ""); }, 3000);
    }
    return;
  }

  if (parsedAmount > 5000) {
    if (snackbar) {
      snackbar.className = "show";
      snackbar.textContent = 'Depósito Máximo R$ 5.000,00';
      setTimeout(function () { snackbar.className = snackbar.className.replace("show", ""); }, 3000);
    }
    return;
  }

  document.getElementById('depositForm').style.display = 'none';
  var pixBox = document.getElementById('pixBox');
  pixBox.style.display = 'block';
  var pixLoading = document.getElementById('pixLoading');
  var pixQrImg = document.getElementById('pixQrImg');
  pixLoading.style.display = 'block';
  pixQrImg.style.display = 'none';
  document.getElementById('pixPaidBox').style.display = 'none';

  fetch('{{ route("pix.deposit.ajax") }}', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
    },
    body: JSON.stringify({ amount: parsedAmount })
  })
  .then(function (res) {
    return res.text().then(function (t) {
      try { return JSON.parse(t); } catch (e) { return { success: false, error: 'Resposta inválida do servidor. Tente novamente.' }; }
    });
  })
  .then(function (json) {
    pixLoading.style.display = 'none';
    if (!json.success) {
      pixBox.style.display = 'none';
      document.getElementById('depositForm').style.display = 'block';
      alert(json.error || 'Erro ao gerar PIX');
      return;
    }
    document.getElementById('pixAmountDisplay').textContent = json.amount;
    pixQrImg.src = json.qr_url;
    pixQrImg.style.display = 'block';
    document.getElementById('pixCodeText').value = json.pix_code;
    checkPixStatus(json.deposit_id);
  })
  .catch(function () {
    pixLoading.style.display = 'none';
    pixBox.style.display = 'none';
    document.getElementById('depositForm').style.display = 'block';
    alert('Erro de conexão. Tente novamente.');
  });
};

var pixPollTimer = null;
var pixPollCount = 0;
var pixPollMax = 60;

function checkPixStatus(depositId) {
  pixPollCount++;
  if (pixPollCount > pixPollMax) {
    var snackbar = document.getElementById("snackbar_error");
    if (snackbar) {
      snackbar.className = "show";
      snackbar.textContent = 'Tempo esgotado. Verifique seu extrato.';
      setTimeout(function () { snackbar.className = snackbar.className.replace("show", ""); }, 5000);
    }
    return;
  }
  fetch('{{ route("pix.deposit.status") }}', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
    },
    body: JSON.stringify({ deposit_id: depositId })
  })
  .then(function (res) { return res.json(); })
  .then(function (json) {
    if (json.status === 'approved') {
      document.getElementById('pixPaidAmount').textContent = Number(json.amount || 0).toFixed(2);
      document.getElementById('pixPaidBox').style.display = 'block';
      setTimeout(function () { window.location.reload(); }, 2000);
      return;
    }
    pixPollTimer = setTimeout(function () { checkPixStatus(depositId); }, 5000);
  })
  .catch(function () {
    pixPollTimer = setTimeout(function () { checkPixStatus(depositId); }, 5000);
  });
}

function goBackToForm() {
  if (pixPollTimer) clearTimeout(pixPollTimer);
  pixPollCount = 0;
  document.getElementById('pixBox').style.display = 'none';
  document.getElementById('depositForm').style.display = 'block';
}

function copyPixCode() {
  var el = document.getElementById('pixCodeText');
  var text = el.value.trim();
  if (navigator.clipboard && window.isSecureContext) {
    navigator.clipboard.writeText(text).then(function () { alert('Código PIX copiado!'); });
  } else {
    el.focus(); el.select();
    document.execCommand('copy');
    alert('Código PIX copiado!');
  }
}
</script>

<div id="pixBox" style="display:none; padding: 0 12px;">
  <div style="max-width: 400px; margin: 0 auto;">
    <button onclick="goBackToForm()" style="background: none; border: 1px solid #e5e7eb; border-radius: 8px; padding: 6px 14px; font-size: 13px; color: #374151; cursor: pointer; margin-bottom: 12px;">&larr; Voltar</button>
    <h3 style="font-size: 18px; font-weight: 700; text-align: center; color: #111827;">Depósito via PIX</h3>
    <p style="text-align: center; color: #6b7280; font-size: 13px; margin-top: 4px;">Escaneie o QR Code ou copie o código</p>
    <div style="font-size: 32px; font-weight: 800; color: #059669; text-align: center; margin: 20px 0;">
      R$ <span id="pixAmountDisplay"></span>
    </div>
    <div style="display: flex; justify-content: center; margin: 16px 0;">
      <img id="pixQrImg" src="" alt="Codigo QR PIX" style="width: 220px; height: 220px; border-radius: 12px; border: 1px solid #e5e7eb;">
    </div>
    <div style="position: relative; margin-top: 16px;">
      <div style="font-size: 12px; font-weight: 600; color: #6b7280; margin-bottom: 8px;">Código PIX (copia e cola)</div>
      <textarea id="pixCodeText" readonly rows="3" style="width: 100%; font-size: 11px; color: #374151; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 10px; padding: 12px; resize: none; user-select: text;"></textarea>
      <button onclick="copyPixCode()" style="position: absolute; top: 30px; right: 8px; background: #059669; color: white; font-size: 12px; font-weight: 600; padding: 6px 12px; border-radius: 8px; border: none; cursor: pointer;">Copiar</button>
    </div>
    <div style="background: #fef3c7; border: 1px solid #fcd34d; border-radius: 10px; padding: 14px; margin-top: 16px; color: #92400e; font-size: 13px;">
      <strong>Pagamento confirmado automaticamente.</strong><br>
      Assim que pagar, o saldo cai na hora.
    </div>
    <div id="pixPaidBox" style="display: none; background: #d1fae5; border: 1px solid #34d399; border-radius: 10px; padding: 14px; text-align: center; margin-top: 16px;">
      <div style="color: #059669; font-size: 20px; font-weight: 700;">Pagamento recebido!</div>
      <div style="color: #047857; font-size: 13px; margin-top: 4px;">R$ <span id="pixPaidAmount"></span> creditados.</div>
    </div>
    <div id="pixLoading" style="text-align: center; padding: 30px; color: #6b7280; font-size: 14px;">
      Gerando QR Code...
    </div>
  </div>
</div>

@endsection
