@extends($activeTemplate . 'layouts.master')
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
        <div style="color: #ffffff; font-size: 22px; font-weight: 900; letter-spacing: 1px; line-height: 1.1;">ALTERAR SENHA</div>
        <div style="color: rgba(255,255,255,0.85); font-size: 13px; font-weight: 600; letter-spacing: 2px; margin-top: 6px;">SEGURANÇA DA CONTA</div>
    </div>

    {{-- Formulário --}}
    <div class="withdraw-card">
        <form action="{{ route('user.change.password') }}" method="post">
            @csrf
            <div style="margin-bottom: 18px;">
                <label style="color: #64748b; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; display: block;">
                    Senha atual
                </label>
                <input type="password" name="current_password" class="withdraw-input" placeholder="Digite sua senha atual" required autocomplete="current-password">
            </div>

            <div style="margin-bottom: 18px;">
                <label style="color: #64748b; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; display: block;">
                    Nova senha
                </label>
                <input type="password" name="password" class="withdraw-input" placeholder="Digite a nova senha" required autocomplete="new-password">
                @if ($general->secure_password)
                    <div style="margin-top: 10px; padding: 12px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px;">
                        <p style="font-size: 11px; color: #64748b; margin: 3px 0;"><span style="color: #16a34a;">&#9679;</span> 1 letra minúscula</p>
                        <p style="font-size: 11px; color: #64748b; margin: 3px 0;"><span style="color: #16a34a;">&#9679;</span> 1 letra maiúscula</p>
                        <p style="font-size: 11px; color: #64748b; margin: 3px 0;"><span style="color: #16a34a;">&#9679;</span> 1 número</p>
                        <p style="font-size: 11px; color: #64748b; margin: 3px 0;"><span style="color: #16a34a;">&#9679;</span> 1 caractere especial</p>
                        <p style="font-size: 11px; color: #64748b; margin: 3px 0;"><span style="color: #16a34a;">&#9679;</span> Mínimo 6 caracteres</p>
                    </div>
                @endif
            </div>

            <div style="margin-bottom: 18px;">
                <label style="color: #64748b; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; display: block;">
                    Confirmar nova senha
                </label>
                <input type="password" name="password_confirmation" class="withdraw-input" placeholder="Repita a nova senha" required autocomplete="new-password">
            </div>

            <button type="submit" class="submit-btn">Alterar Senha</button>
        </form>
    </div>

    {{-- Bottom border --}}
    <div style="height: 4px; background: linear-gradient(90deg, #16a34a, #4ade80, #16a34a); border-radius: 4px;"></div>
</div>

@if ($general->secure_password)
    @push('script-lib')
        <script src="{{ asset('assets/global/js/secure_password.js') }}"></script>
    @endpush
@endif

@endsection