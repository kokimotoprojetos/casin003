@extends($activeTemplate.'layouts.master')
@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">
<style>
    * { -webkit-user-select: none; user-select: none; }
    input, .copyable, textarea { -webkit-user-select: text; user-select: text; }
    .pix-container {
        max-width: 400px;
        margin: 0 auto;
        padding: 20px;
    }
    .pix-card {
        background: white;
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    .pix-amount {
        font-size: 32px;
        font-weight: 800;
        color: #059669;
        text-align: center;
        margin: 20px 0;
    }
    .pix-qr {
        display: flex;
        justify-content: center;
        margin: 20px 0;
    }
    .pix-qr img {
        width: 220px;
        height: 220px;
        border-radius: 12px;
        border: 1px solid #e5e7eb;
    }
    .pix-code-container {
        position: relative;
        margin-top: 16px;
    }
    .pix-code-label {
        font-size: 12px;
        font-weight: 600;
        color: #6b7280;
        margin-bottom: 8px;
    }
    .pix-code-textarea {
        width: 100%;
        font-size: 11px;
        color: #374151;
        background: #f9fafb;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 12px;
        resize: none;
    }
    .copy-btn {
        position: absolute;
        top: 32px;
        right: 8px;
        background: #059669;
        color: white;
        font-size: 12px;
        font-weight: 600;
        padding: 6px 12px;
        border-radius: 8px;
        border: none;
        cursor: pointer;
    }
    .copy-btn:hover {
        background: #047857;
    }
    .pix-info {
        background: #fef3c7;
        border: 1px solid #fcd34d;
        border-radius: 12px;
        padding: 16px;
        margin-top: 16px;
        color: #92400e;
        font-size: 13px;
    }
    .pix-info strong {
        display: block;
        margin-bottom: 4px;
    }
    .paid-box {
        display: none;
        background: #d1fae5;
        border: 1px solid #34d399;
        border-radius: 12px;
        padding: 16px;
        text-align: center;
        margin-top: 16px;
    }
    .paid-box .paid-title {
        color: #059669;
        font-size: 20px;
        font-weight: 700;
    }
    .paid-box .paid-text {
        color: #047857;
        font-size: 13px;
        margin-top: 4px;
    }
    .back-link {
        display: block;
        text-align: center;
        margin-top: 16px;
        font-size: 13px;
        color: #6b7280;
        text-decoration: underline;
    }
</style>

<div class="pix-container">
    <div class="pix-card">
        <h1 style="font-size: 18px; font-weight: 700; text-align: center; color: #111827;">Depósito via PIX</h1>
        <p style="text-align: center; color: #6b7280; font-size: 13px; margin-top: 4px;">Escaneie o QR Code ou copie o código PIX</p>

        <div class="pix-amount">
            R$ {{ number_format((float)$deposit->amount, 2, ',', '.') }}
        </div>

        <div class="pix-qr">
            @if($deposit->qr_code_url)
                <img src="{{ $deposit->qr_code_url }}" alt="QR Code PIX">
            @endif
        </div>

        <div class="pix-code-container">
            <div class="pix-code-label">Código PIX (copia e cola)</div>
            <textarea id="pix-code" readonly rows="3" class="copyable pix-code-textarea">{{ $deposit->pix_code }}</textarea>
            <button onclick="copyPix()" class="copy-btn">Copiar</button>
        </div>

        <div class="pix-info">
            <strong>Pagamento confirmado automaticamente.</strong>
            Assim que o pagamento for identificado, o saldo cai na hora na sua conta.
        </div>

        <div id="paid-box" class="paid-box">
            <div class="paid-title">Pagamento recebido!</div>
            <div class="paid-text">R$ <span id="paid-amount"></span> creditados na sua conta.</div>
            <div class="paid-text" style="margin-top: 8px;">Redirecionando para o painel...</div>
        </div>

        <a href="{{ route('user.deposit.history') }}" class="back-link">Ver histórico de depósitos</a>
    </div>
</div>

<script>
    function copyPix() {
        var el = document.getElementById('pix-code');
        var link = String(el ? el.value : '').trim();

        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(link).then(function () {
                alert('Código PIX copiado!');
            }).catch(function () {
                copyPixFallback(el, link);
            });
        } else {
            copyPixFallback(el, link);
        }
    }

    function copyPixFallback(el, link) {
        if (el) {
            el.style.webkitUserSelect = 'text';
            el.style.userSelect = 'text';
            el.focus();
            el.select();
            el.setSelectionRange(0, el.value.length);
        }
        var ok = false;
        try { ok = document.execCommand('copy'); } catch (e) {}
        if (ok) {
            alert('Código PIX copiado!');
        } else {
            prompt('Copie o código PIX:', link);
        }
    }

    async function checkStatus() {
        try {
            const res = await fetch('{{ route("user.pix.deposit.status") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ deposit_id: {{ $deposit->id }} })
            });
            const json = await res.json();
            if (json.status === 'approved') {
                document.getElementById('paid-amount').textContent = Number(json.amount || 0).toFixed(2);
                document.getElementById('paid-box').style.display = 'block';
                setTimeout(function () {
                    window.location.href = '{{ route("user.home") }}';
                }, 2000);
                return;
            }
        } catch (e) {}
        setTimeout(checkStatus, 60000);
    }
    checkStatus();
</script>
@endsection
