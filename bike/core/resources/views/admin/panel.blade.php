<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Painel Administrativo - {{ config('app.name') }}</title>
    <link rel="stylesheet" href="{{ asset("core/css/cdn/toastr.min.css") }}"/>
    <script src="{{ asset("core/js/cdn/tailwind.js") }}"></script>
    <script src="{{ asset("core/js/cdn/lucide.min.js") }}"></script>
    <script src="{{ asset("core/js/cdn/jquery.min.js") }}"></script>
    <script src="{{ asset("core/js/cdn/toastr.min.js") }}"></script>
</head>
<body class="bg-gray-50 text-gray-800 font-sans antialiased">
    <script>
    window.addEventListener('error', function (e) {
        var d = document.createElement('pre');
        d.id = 'js-fatal-error';
        d.style.cssText = 'position:fixed;top:0;left:0;z-index:999999999;background:#000;color:#0f0;padding:10px;font-size:14px;max-width:100%;white-space:pre-wrap;';
        d.textContent = 'JSERROR: ' + (e.message || '?') + ' @ ' + (e.filename || '?') + ':' + (e.lineno || '?') + ':' + (e.colno || '?');
        document.body.insertBefore(d, document.body.firstChild);
    });
    window.addEventListener('unhandledrejection', function (e) {
        var d = document.createElement('pre');
        d.id = 'js-fatal-reject';
        d.style.cssText = 'position:fixed;top:0;left:0;z-index:999999999;background:#900;color:#fff;padding:10px;font-size:14px;max-width:100%;white-space:pre-wrap;';
        d.textContent = 'REJECT: ' + ((e.reason && (e.reason.message || e.reason)) || '?');
        document.body.insertBefore(d, document.body.firstChild);
    });
    </script>

    <!-- ============ LOGIN ============ -->
    <div id="login-screen" class="min-h-screen bg-gray-900 flex items-center justify-center p-4 font-sans text-gray-800 hidden">
        <div id="login-card" class="bg-white rounded-2xl shadow-2xl p-8 w-full max-w-md">
            <div class="flex flex-col items-center mb-8">
                <div class="w-16 h-16 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center mb-4">
                    <i data-lucide="shield-check" class="w-8 h-8"></i>
                </div>
                <h1 class="text-2xl font-bold text-gray-900">Acesso Restrito</h1>
                <p class="text-gray-500 text-sm mt-1">Painel Administrativo do Sistema</p>
            </div>

            @if(session('error'))
            <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg">
                <p class="text-red-600 text-sm font-medium">{{ session('error') }}</p>
            </div>
            @endif

            <form id="login-form" class="space-y-4" onsubmit="return handleLogin(event)">
                <div>
                    <label class="block text-sm font-semibold mb-1">E-mail</label>
                    <input id="login-email" type="text"
                        class="w-full border-2 border-gray-200 rounded-lg px-4 py-3 focus:outline-none focus:border-blue-500"
                        placeholder="Email ou usuário"
                        required>
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-1">Senha</label>
                    <input id="login-password" type="password"
                        class="w-full border-2 border-gray-200 rounded-lg px-4 py-3 focus:outline-none focus:border-blue-500"
                        required>
                </div>
                <p id="login-error" class="text-red-500 text-sm font-medium hidden"></p>
                <button type="submit"
                    class="w-full bg-blue-600 text-white font-bold py-3 rounded-lg hover:bg-blue-700 transition">
                    Entrar no Painel
                </button>
            </form>
        </div>
        <div id="login-loading" class="hidden text-center">
            <div class="text-white text-lg font-semibold">Verificando sessão...</div>
        </div>
    </div>

    <!-- ============ PAINEL ============ -->
    <div id="panel-screen" class="hidden">

        <!-- Navbar -->
        <nav class="bg-white border-b border-gray-200 px-6 py-4 flex justify-between items-center shadow-sm">
            <div class="flex items-center gap-3">
                <i data-lucide="shield-check" class="w-8 h-8 text-blue-600"></i>
                <h1 class="text-xl font-bold text-gray-900">Painel Administrativo</h1>
            </div>
            <div class="flex items-center gap-3">
                <button id="btn-logout" class="flex items-center gap-2 text-red-600 font-semibold hover:bg-red-50 px-4 py-2 rounded-lg transition">
                    <i data-lucide="log-out" class="w-5 h-5"></i> Sair
                </button>
            </div>
        </nav>

        <div id="panel-loading" class="min-h-[60vh] flex items-center justify-center">
            <p class="text-gray-500 font-medium">Carregando painel...</p>
        </div>

        <div id="panel-content" class="max-w-7xl mx-auto p-6 hidden">

            <!-- Stats Filter -->
            <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
                <div class="flex items-center gap-2">
                    <label class="text-sm font-semibold text-gray-700">Filtrar estatísticas:</label>
                    <input id="stats-date" type="date" value=""
                        class="border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:border-blue-500">
                    <button id="stats-today" class="text-xs font-bold px-3 py-1.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">Hoje</button>
                    <button id="stats-yesterday" class="text-xs font-bold px-3 py-1.5 bg-gray-100 rounded-lg hover:bg-gray-200 transition">Ontem</button>
                    <button id="stats-clear" class="text-xs font-bold px-3 py-1.5 bg-gray-100 rounded-lg hover:bg-gray-200 transition">Tudo</button>
                </div>
                <span id="stats-date-label" class="text-xs text-gray-500"></span>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-8">
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex items-center gap-4">
                    <div class="p-4 bg-blue-50 text-blue-600 rounded-lg"><i data-lucide="users" class="w-6 h-6"></i></div>
                    <div>
                        <p id="stat-label-users" class="text-sm text-gray-500 font-medium">Total de Usuários</p>
                        <p id="stat-total-users" class="text-2xl font-bold">0</p>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex items-center gap-4">
                    <div class="p-4 bg-green-50 text-green-600 rounded-lg"><i data-lucide="banknote" class="w-6 h-6"></i></div>
                    <div>
                        <p id="stat-label-deposits" class="text-sm text-gray-500 font-medium">Depósitos Pagos</p>
                        <p id="stat-total-deposits" class="text-2xl font-bold">R$ 0.00</p>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex items-center gap-4">
                    <div class="p-4 bg-purple-50 text-purple-600 rounded-lg"><i data-lucide="credit-card" class="w-6 h-6"></i></div>
                    <div>
                        <p id="stat-label-withdrawals" class="text-sm text-gray-500 font-medium">Saques Pagos</p>
                        <p id="stat-total-withdrawals" class="text-2xl font-bold">R$ 0.00</p>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex items-center gap-4">
                    <div class="p-4 bg-orange-50 text-orange-600 rounded-lg"><i data-lucide="clock" class="w-6 h-6"></i></div>
                    <div>
                        <p class="text-sm text-gray-500 font-medium">Saques Pendentes</p>
                        <p id="stat-pending-withdrawals" class="text-2xl font-bold text-orange-600">R$ 0.00</p>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex items-center gap-4">
                    <div class="p-4 bg-amber-50 text-amber-600 rounded-lg"><i data-lucide="wallet" class="w-6 h-6"></i></div>
                    <div>
                        <p class="text-sm text-gray-500 font-medium">Saldo Total</p>
                        <p id="stat-total-balance" class="text-2xl font-bold text-amber-600">R$ 0.00</p>
                    </div>
                </div>
            </div>

            <!-- Tabs -->
            <div class="flex gap-4 mb-6 border-b border-gray-200 overflow-x-auto">
                <button data-tab="users" class="tab-btn pb-3 px-2 font-semibold transition whitespace-nowrap border-b-2 border-blue-600 text-blue-600">Usuários Cadastrados</button>
                <button data-tab="withdrawals" class="tab-btn pb-3 px-2 font-semibold transition whitespace-nowrap flex items-center gap-2 text-gray-500 hover:text-gray-700">
                    Pendentes <span id="pending-count" class="bg-red-500 text-white text-xs px-2 py-0.5 rounded-full hidden">0</span>
                </button>
                <button data-tab="rejected" class="tab-btn pb-3 px-2 font-semibold transition whitespace-nowrap text-gray-500 hover:text-gray-700">Rejeitados</button>
                <button data-tab="approved" class="tab-btn pb-3 px-2 font-semibold transition whitespace-nowrap text-gray-500 hover:text-gray-700">Aprovados</button>
                <button data-tab="deposits" class="tab-btn pb-3 px-2 font-semibold transition whitespace-nowrap text-gray-500 hover:text-gray-700">Histórico de Depósitos</button>
                <button data-tab="packages" class="tab-btn pb-3 px-2 font-semibold transition whitespace-nowrap text-gray-500 hover:text-gray-700">Bloquear Planos</button>
                <button data-tab="planos" class="tab-btn pb-3 px-2 font-semibold transition whitespace-nowrap text-gray-500 hover:text-gray-700">Planos</button>
                <button data-tab="balance" class="tab-btn pb-3 px-2 font-semibold transition whitespace-nowrap text-gray-500 hover:text-gray-700">Ajuste de Saldo</button>
                <button data-tab="alerts" class="tab-btn pb-3 px-2 font-semibold transition whitespace-nowrap flex items-center gap-1 text-gray-500 hover:text-gray-700"><i data-lucide="shield-alert" class="w-4 h-4"></i> Alerta</button>
            </div>

            <!-- Content Area -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">

                <!-- USERS -->
                <div id="tab-users" class="tab-content">
                    <div class="p-4 border-b border-gray-100">
                        <div class="relative max-w-xs">
                            <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"></i>
                            <input id="search-users" type="text"
                                placeholder="Buscar por ID, telefone ou nome..."
                                class="w-full border border-gray-200 rounded-lg pl-9 pr-4 py-2 text-sm focus:outline-none focus:border-blue-500">
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-gray-50 text-gray-600 border-b border-gray-200">
                                <tr>
                                    <th class="p-4 font-semibold">ID</th>
                                    <th class="p-4 font-semibold">Telefone</th>
                                    <th class="p-4 font-semibold">Nome</th>
                                    <th class="p-4 font-semibold">Saldo</th>
                                    <th class="p-4 font-semibold">Data Cadastro</th>
                                    <th class="p-4 font-semibold">Ações</th>
                                </tr>
                            </thead>
                            <tbody id="users-tbody" class="divide-y divide-gray-100"></tbody>
                        </table>
                    </div>
                </div>

                <!-- WITHDRAWALS PENDING -->
                <div id="tab-withdrawals" class="tab-content hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-gray-50 text-gray-600 border-b border-gray-200">
                                <tr>
                                    <th class="p-4 font-semibold">ID</th>
                                    <th class="p-4 font-semibold">Usuário</th>
                                    <th class="p-4 font-semibold">Nome</th>
                                    <th class="p-4 font-semibold">Valor</th>
                                    <th class="p-4 font-semibold">Tipo PIX</th>
                                    <th class="p-4 font-semibold">Chave PIX</th>
                                    <th class="p-4 font-semibold">Status</th>
                                    <th class="p-4 font-semibold">Data</th>
                                    <th class="p-4 font-semibold text-right">Ações</th>
                                </tr>
                            </thead>
                            <tbody id="withdrawals-tbody" class="divide-y divide-gray-100"></tbody>
                        </table>
                    </div>
                </div>

                <!-- WITHDRAWALS REJECTED -->
                <div id="tab-rejected" class="tab-content hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-gray-50 text-gray-600 border-b border-gray-200">
                                <tr>
                                    <th class="p-4 font-semibold">ID</th>
                                    <th class="p-4 font-semibold">Usuário</th>
                                    <th class="p-4 font-semibold">Nome</th>
                                    <th class="p-4 font-semibold">Valor</th>
                                    <th class="p-4 font-semibold">Tipo PIX</th>
                                    <th class="p-4 font-semibold">Chave PIX</th>
                                    <th class="p-4 font-semibold">Data da Rejeição</th>
                                </tr>
                            </thead>
                            <tbody id="rejected-tbody" class="divide-y divide-gray-100"></tbody>
                        </table>
                    </div>
                </div>

                <!-- WITHDRAWALS APPROVED -->
                <div id="tab-approved" class="tab-content hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-gray-50 text-gray-600 border-b border-gray-200">
                                <tr>
                                    <th class="p-4 font-semibold">ID</th>
                                    <th class="p-4 font-semibold">Usuário</th>
                                    <th class="p-4 font-semibold">Nome</th>
                                    <th class="p-4 font-semibold">Valor</th>
                                    <th class="p-4 font-semibold">Tipo PIX</th>
                                    <th class="p-4 font-semibold">Chave PIX</th>
                                    <th class="p-4 font-semibold">Data de Aprovação</th>
                                </tr>
                            </thead>
                            <tbody id="approved-tbody" class="divide-y divide-gray-100"></tbody>
                        </table>
                    </div>
                </div>

                <!-- DEPOSITS -->
                <div id="tab-deposits" class="tab-content hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-gray-50 text-gray-600 border-b border-gray-200">
                                <tr>
                                    <th class="p-4 font-semibold">ID</th>
                                    <th class="p-4 font-semibold">Usuário (Telefone)</th>
                                    <th class="p-4 font-semibold">Valor</th>
                                    <th class="p-4 font-semibold">Status</th>
                                    <th class="p-4 font-semibold">Data</th>
                                    <th class="p-4 font-semibold text-right">Ações</th>
                                </tr>
                            </thead>
                            <tbody id="deposits-tbody" class="divide-y divide-gray-100"></tbody>
                        </table>
                    </div>
                </div>

                <!-- PACKAGES -->
                <div id="tab-packages" class="tab-content hidden">
                    <div class="p-4 border-b border-gray-100">
                        <p class="text-sm text-gray-500">Bloqueie ou libere a compra de cada plano. Planos bloqueados mostram "EM BREVE" na loja.</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-gray-50 text-gray-600 border-b border-gray-200">
                                <tr>
                                    <th class="p-4 font-semibold">ID</th>
                                    <th class="p-4 font-semibold">Nome</th>
                                    <th class="p-4 font-semibold">Preço</th>
                                    <th class="p-4 font-semibold">Validade</th>
                                    <th class="p-4 font-semibold">Status</th>
                                    <th class="p-4 font-semibold text-right">Ação</th>
                                </tr>
                            </thead>
                            <tbody id="packages-tbody" class="divide-y divide-gray-100"></tbody>
                        </table>
                    </div>
                </div>

                <!-- PLANOS -->
                <div id="tab-alerts" class="tab-content hidden">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                        <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
                            <h3 class="font-bold text-gray-900">Contas com mesmo IP</h3>
                            <div class="flex items-center gap-2">
                                <input id="search-alerts" type="text"
                                    placeholder="Buscar telefone, id ou nome..."
                                    class="w-60 border border-gray-200 rounded-lg px-3 py-1.5 text-xs focus:outline-none focus:border-blue-500">
                                <button onclick="loadAlerts()" class="text-xs font-bold px-3 py-1.5 bg-gray-100 rounded-lg hover:bg-gray-200 transition">Atualizar</button>
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 mb-4">Contas registradas a partir do mesmo endereço IP. O selo <b>Se auto convidou</b> indica que a conta se cadastrou usando o código de convite de outra conta do mesmo IP.</p>
                        <div id="alerts-body" class="space-y-4"><p class="text-sm text-gray-400">Selecione a aba para carregar os alertas.</p></div>
                    </div>
                </div>

                <div id="tab-planos" class="tab-content hidden">
                    <div class="p-4 border-b border-gray-100 flex justify-between items-center">
                        <p class="text-sm text-gray-500">Gerencie os planos de investimento.</p>
                        <button onclick="openPlanModal()"
                            class="inline-flex items-center gap-1 bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-blue-700 transition">
                            <i data-lucide="plus" class="w-4 h-4"></i> Novo Plano
                        </button>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-gray-50 text-gray-600 border-b border-gray-200">
                                <tr>
                                    <th class="p-4 font-semibold">Imagem</th>
                                    <th class="p-4 font-semibold">Nome</th>
                                    <th class="p-4 font-semibold">Preço</th>
                                    <th class="p-4 font-semibold">Prazo</th>
                                    <th class="p-4 font-semibold">Status</th>
                                    <th class="p-4 font-semibold text-right">Ações</th>
                                </tr>
                            </thead>
                            <tbody id="planos-tbody" class="divide-y divide-gray-100"></tbody>
                        </table>

                <div class="mt-6 p-4 bg-gray-50 rounded-xl border border-gray-100">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="font-extrabold text-gray-900">Imagens dos Produtos</h3>
                        <span class="text-xs text-gray-400">ordem usada na página de produtos do site</span>
                    </div>
                    <div id="imagens-produtos" class="space-y-2"></div>
                </div>

                <div class="mt-6 p-4 bg-gray-50 rounded-xl border border-gray-100">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="font-extrabold text-gray-900">Códigos Bônus</h3>
                        <span class="text-xs text-gray-400">o usuário resgata no perfil (Código Bônus)</span>
                    </div>
                    <div class="flex gap-2 mb-2">
                        <input id="bonus-new-code" placeholder="CÓDIGO (vazio = gerar aleatório)" class="border-2 border-gray-200 rounded-lg px-3 py-2 text-sm uppercase flex-1">
                        <button id="bonus-generate-btn" class="px-3 py-2 bg-indigo-600 text-white rounded-lg text-xs font-bold hover:bg-indigo-700 transition" title="Gerar código aleatório">🎲 Gerar</button>
                    </div>
                    <div class="flex gap-2 mb-2">
                        <input id="bonus-new-amount" placeholder="Valor R$" type="number" step="0.01" min="0.01" class="border-2 border-gray-200 rounded-lg px-3 py-2 text-sm flex-1">
                        <input id="bonus-new-max" placeholder="Máx. resgates" type="number" min="1" value="100" class="border-2 border-gray-200 rounded-lg px-3 py-2 text-sm w-32">
                    </div>
                    <div class="flex gap-2 mb-3 items-center">
                        <span class="text-xs text-gray-500 font-semibold">Válido por:</span>
                        <input id="bonus-duration-value" type="number" min="0" value="24" class="border-2 border-gray-200 rounded-lg px-3 py-2 text-sm w-24">
                        <select id="bonus-duration-unit" class="border-2 border-gray-200 rounded-lg px-2 py-2 text-sm">
                            <option value="hours">horas</option>
                            <option value="minutes">minutos</option>
                        </select>
                        <span class="text-xs text-gray-400">(0 = sem validade)</span>
                        <span class="flex-1"></span>
                        <button id="bonus-create-btn" class="px-4 py-2 bg-green-600 text-white rounded-lg text-sm font-bold hover:bg-green-700 transition">Criar código</button>
                    </div>
                    <div id="bonus-codes-list" class="space-y-2"></div>
                </div>
                    </div>
                </div>

                <!-- BALANCE -->
                <div id="tab-balance" class="tab-content hidden">
                    <div class="p-4 border-b border-gray-100">
                        <div class="relative max-w-xs">
                            <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"></i>
                            <input id="search-balance" type="text"
                                placeholder="Buscar por ID, telefone ou nome..."
                                class="w-full border border-gray-200 rounded-lg pl-9 pr-4 py-2 text-sm focus:outline-none focus:border-blue-500">
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-gray-50 text-gray-600 border-b border-gray-200">
                                <tr>
                                    <th class="p-4 font-semibold">ID</th>
                                    <th class="p-4 font-semibold">Telefone</th>
                                    <th class="p-4 font-semibold">Nome</th>
                                    <th class="p-4 font-semibold">Saldo</th>
                                    <th class="p-4 font-semibold text-right">Ação</th>
                                </tr>
                            </thead>
                            <tbody id="balance-tbody" class="divide-y divide-gray-100"></tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Balance Modal -->
    <div id="balance-modal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4 hidden">
        <div class="bg-white rounded-2xl shadow-2xl p-6 w-full max-w-md">
            <h2 class="text-lg font-bold text-gray-900 mb-2">Ajustar Saldo</h2>
            <p id="balance-modal-info" class="text-sm text-gray-500 mb-4"></p>
            <input id="balance-amount" type="number" placeholder="Valor (R$)"
                class="w-full border-2 border-gray-200 rounded-xl px-4 py-3 text-lg font-medium focus:outline-none focus:border-blue-500 mb-4"
                min="0" step="0.01" autofocus>

            <div class="flex gap-2">
                <button id="balance-cancel"
                    class="flex-1 border-2 border-gray-200 text-gray-600 py-3 rounded-xl font-bold hover:bg-gray-50 transition">Cancelar</button>
                <button id="balance-remove"
                    class="flex-1 bg-red-600 text-white py-3 rounded-xl font-bold hover:bg-red-700 transition">Remover</button>
                <button id="balance-add"
                    class="flex-1 bg-blue-600 text-white py-3 rounded-xl font-bold hover:bg-blue-700 transition">Adicionar</button>
            </div>
        </div>
    </div>

    <!-- Plan Modal -->
    <div id="plan-modal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-[100] p-4 hidden">
        <div class="bg-white rounded-2xl shadow-2xl p-6 w-full max-w-lg max-h-[90vh] overflow-y-auto">
            <h2 id="plan-modal-title" class="text-lg font-bold text-gray-900 mb-4">Novo Plano</h2>
            <input type="hidden" id="plan-id">
            <div class="space-y-3 mb-4">
                <div>
                    <label class="block text-xs font-bold text-gray-500 mb-1">Nome</label>
                    <input id="plan-name" type="text" placeholder="Ex: VIP 1"
                        class="w-full border-2 border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 mb-1">Imagem</label>
                    <input id="plan-image-file" type="file" accept="image/*"
                        class="w-full border-2 border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 file:mr-3 file:py-1 file:px-3 file:rounded-lg file:border-0 file:bg-blue-50 file:text-blue-700 file:font-bold file:text-xs hover:file:bg-blue-100">
                    <img id="plan-image-preview" class="mt-2 rounded-lg hidden" style="max-height:80px">
                    <input type="hidden" id="plan-image">
                    <div class="mt-2">
                        <div class="text-xs text-gray-500 mb-1">ou escolha uma imagem padrão:</div>
                        <div class="flex gap-1 flex-wrap">
                            <img class="plan-img-choice cursor-pointer" data-img="produto1.webp" src="/core/img/produto1.webp" style="width:38px;height:38px;object-fit:cover;border:2px solid #e5e7eb;border-radius:8px">
                            <img class="plan-img-choice cursor-pointer" data-img="produto2.webp" src="/core/img/produto2.webp" style="width:38px;height:38px;object-fit:cover;border:2px solid #e5e7eb;border-radius:8px">
                            <img class="plan-img-choice cursor-pointer" data-img="produto3.webp" src="/core/img/produto3.webp" style="width:38px;height:38px;object-fit:cover;border:2px solid #e5e7eb;border-radius:8px">
                            <img class="plan-img-choice cursor-pointer" data-img="produto4.webp" src="/core/img/produto4.webp" style="width:38px;height:38px;object-fit:cover;border:2px solid #e5e7eb;border-radius:8px">
                            <img class="plan-img-choice cursor-pointer" data-img="produto5.webp" src="/core/img/produto5.webp" style="width:38px;height:38px;object-fit:cover;border:2px solid #e5e7eb;border-radius:8px">
                            <img class="plan-img-choice cursor-pointer" data-img="produto6.webp" src="/core/img/produto6.webp" style="width:38px;height:38px;object-fit:cover;border:2px solid #e5e7eb;border-radius:8px">
                        </div>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 mb-1">Preço Fixo (R$)</label>
                        <input id="plan-amount" type="number" placeholder="0.00" min="0" step="0.01"
                            class="w-full border-2 border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 mb-1">Prazo (dias)</label>
                        <input id="plan-time" type="number" placeholder="Ex: 30" min="1"
                            class="w-full border-2 border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 mb-1">Renda Diária (R$)</label>
                    <input id="plan-daily-return" type="number" placeholder="Ex: 10.00" min="0" step="0.01"
                        class="w-full border-2 border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 mb-1">Renda Total (R$)</label>
                    <input id="plan-total-return" type="text" readonly placeholder="--"
                        class="w-full border-2 border-gray-100 rounded-lg px-3 py-2 text-sm bg-gray-50 text-gray-500 font-bold">
                </div>
                <div class="plan-extra">
                    <label class="block text-xs font-bold text-gray-500 mb-1">Nome do Prazo</label>
                    <input id="plan-time-name" type="text" placeholder="Ex: Day, Month"
                        class="w-full border-2 border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500">
                </div>
                <div class="plan-extra">
                    <label class="block text-xs font-bold text-gray-500 mb-1">Status</label>
                    <select id="plan-status"
                        class="w-full border-2 border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500">
                        <option value="1">Ativo</option>
                        <option value="0">Inativo</option>
                    </select>
                </div>
                <div class="grid grid-cols-3 gap-3 plan-extra">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 mb-1">Destaque</label>
                        <select id="plan-featured"
                            class="w-full border-2 border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500">
                            <option value="0">Não</option>
                            <option value="1">Sim</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 mb-1">Capital de Volta</label>
                        <select id="plan-capital-back"
                            class="w-full border-2 border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500">
                            <option value="0">Não</option>
                            <option value="1">Sim</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 mb-1">Vitalício</label>
                        <select id="plan-lifetime"
                            class="w-full border-2 border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500">
                            <option value="0">Não</option>
                            <option value="1">Sim</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 mb-1">Máx. compras por usuário</label>
                        <input id="plan-max" type="number" min="0" value="0"
                            class="w-full border-2 border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500">
                        <div class="text-[10px] text-gray-400 mt-0.5">0 = ilimitado</div>
                    </div>
                </div>
            </div>
            <div id="plan-preview" class="bg-gray-50 rounded-lg p-3 mb-4 text-sm hidden">
                <div class="flex justify-between text-gray-600">
                    <span>Renda/dia:</span>
                    <span id="preview-daily">R$ 0.00</span>
                </div>
                <div class="flex justify-between text-gray-600">
                    <span>Ciclo:</span>
                    <span id="preview-cycle">0 dia(s)</span>
                </div>
                <div class="flex justify-between font-bold text-green-600 text-base mt-1">
                    <span>Renda Total:</span>
                    <span id="preview-total">R$ 0.00</span>
                </div>
            </div>
            <div class="flex gap-2">
                <button onclick="closePlanModal()"
                    class="flex-1 border-2 border-gray-200 text-gray-600 py-3 rounded-xl font-bold hover:bg-gray-50 transition">Cancelar</button>
                <button onclick="savePlan()"
                    class="flex-1 bg-blue-600 text-white py-3 rounded-xl font-bold hover:bg-blue-700 transition">Salvar</button>
            </div>
        </div>
    </div>

    <!-- Confirm Modal -->
    <div id="confirm-modal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-[100] p-4 hidden">
        <div class="bg-white rounded-2xl shadow-2xl p-6 w-full max-w-sm">
            <h2 id="confirm-title" class="text-lg font-bold text-gray-900 mb-2"></h2>
            <p id="confirm-message" class="text-sm text-gray-600 mb-6"></p>
            <div class="flex gap-3 justify-end">
                <button id="confirm-cancel"
                    class="px-4 py-2 text-sm font-bold text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg transition">Cancelar</button>
                <button id="confirm-ok"
                    class="px-4 py-2 text-sm font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition">Confirmar</button>
            </div>
        </div>
    </div>

<script>
    window.__adminLoggedIn = {{ $adminLoggedIn ? 'true' : 'false' }};
</script>

<script>
function handleLogin(e) {
    e.preventDefault();
    var email = document.getElementById('login-email').value.trim();
    var password = document.getElementById('login-password').value;
    var errEl = document.getElementById('login-error');
    var btn = document.querySelector('#login-form button[type="submit"]');
    errEl.classList.add('hidden');

    if (!email || !password) {
        errEl.textContent = 'Informe e-mail e senha.';
        errEl.classList.remove('hidden');
        return false;
    }

    btn.disabled = true;
    btn.textContent = 'Entrando...';
    fetch('/admin-api/login', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ email: email, password: password })
    }).then(function(r) {
        if (r.status === 429) {
            errEl.textContent = 'Muitas tentativas de login. Aguarde 1 minuto e tente novamente.';
            errEl.classList.remove('hidden');
            btn.disabled = false;
            btn.textContent = 'Entrar no Painel';
            return;
        }
        if (r.status === 419) {
            location.reload();
            return;
        }
        return r.json().then(function(data) {
            if (data.success && data.token) {
                localStorage.setItem('admin_panel_token', data.token);
                if ('{{ $panelMode ?? '' }}' === 'passwords') {
                    window.location.href = '/gozadinha/secured/panel';
                } else {
                    location.reload();
                }
            } else {
                errEl.textContent = data.error || 'Credenciais inválidas';
                errEl.classList.remove('hidden');
                btn.disabled = false;
                btn.textContent = 'Entrar no Painel';
            }
        });
    }).catch(function() {
        errEl.textContent = 'Erro de conexão';
        errEl.classList.remove('hidden');
        btn.disabled = false;
        btn.textContent = 'Entrar no Painel';
    });
    return false;
}
(function () {
    const state = {
        isLoggedIn: window.__adminLoggedIn === true,
        data: null,
        loading: false,
        activeTab: 'users',
        searchQueryUsers: '',
        searchQueryBalance: '',
        searchQueryAlerts: '',
        statsDate: '',
        balanceModal: null,
        balanceLoading: false,
        confirmModal: null,
        token: localStorage.getItem('admin_panel_token') || null,
    };

    const $ = (id) => document.getElementById(id);
    const csrf = () => document.querySelector('meta[name="csrf-token"]').content;
    const money = (v) => 'R$ ' + Number(v || 0).toFixed(2);
    const fmtDate = (d) => d ? new Date(d).toLocaleString('pt-BR') : '-';

    async function api(url, options = {}) {
        options.headers = Object.assign({
            'X-CSRF-TOKEN': csrf(),
        }, options.headers || {});
        if (state.token) {
            options.headers['Authorization'] = 'Bearer ' + state.token;
        }
        const res = await fetch(url, options);
        if (res.status === 403 && (res.headers.get('x-vercel-mitigated') || '').includes('challenge')) {
            location.reload();
            return { res, json: null };
        }
        if (res.status === 419) { location.reload(); }
        let json = null;
        try { json = await res.json(); } catch (e) {}
        return { res, json };
    }

    async function handleLogout() {
        try { await fetch('/admin-api/login', { method: 'DELETE', headers: { 'Authorization': 'Bearer ' + state.token } }); } catch(e) {}
        state.token = null;
        localStorage.removeItem('admin_panel_token');
        location.reload();
    }

    // Atualização em tempo real das estatísticas (a cada 30 segundos)
    setInterval(function () {
        if (state.isLoggedIn && !state.loading) { fetchData(); }
    }, 30000);

    async function fetchData() {
        state.loading = true;
        render();
        try {
            const q = state.statsDate ? '?date=' + encodeURIComponent(state.statsDate) : '';
            const res = await api('/admin-api/data' + q);
            if (res.res.ok) {
                state.data = res.json;
                state.isLoggedIn = true;
            } else if (res.res.status === 401) {
                state.isLoggedIn = false;
                state.data = null;
            }
        } catch(e) {}
        state.loading = false;
        render();
    }

    function handleWithdrawalAction(id, action) {
        const actionLabel = action === 'approve_gateway'
            ? 'DISPARAR O PAGAMENTO VIA GATEWAY (envia o PIX agora)'
            : (action === 'approve' ? 'APROVAR AUTOMATICAMENTE (envia via gateway)' : 'REJEITAR');
        openConfirm(
            'Confirmar Ação',
            'Deseja realmente ' + actionLabel + ' este saque?',
            async () => {
                try {
                    const res = await api('/admin-api/withdrawals', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ transactionId: id, action })
                    });
                    if (res.res.ok) {
                        if (res.json && res.json.via === 'gateway_pending') {
                            alert('Saque enviado ao gateway! A confirmação do PIX chega em instantes (webhook/cron) e vai para Aprovados.');
                        }
                        fetchData();
                    }
                    else { alert((res.json && res.json.error) || 'Erro ao realizar ação.'); }
                } catch(e) { alert('Erro de conexão ao processar saque.'); }
            }
        );
    }

    function handleWithdrawalManual(id) {
        openConfirm(
            'Aprovar Manualmente',
            'Deseja aprovar este saque SEM enviar via gateway? O saque será marcado como aprovado, mas o PIX não será enviado automaticamente.',
            async () => {
                try {
                    const res = await api('/admin-api/withdrawals', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ transactionId: id, action: 'approve_manual' })
                    });
                    if (res.res.ok) { fetchData(); }
                    else { alert((res.json && res.json.error) || 'Erro ao aprovar saque.'); }
                } catch(e) { alert('Erro de conexão ao aprovar saque.'); }
            }
        );
    }

    function handleAdjustBalance(action) {
        if (!state.balanceModal) return;
        const amount = parseFloat($('balance-amount').value);
        if (!amount || amount <= 0) { toastr.error('Informe um valor válido.'); return; }

        const actionText = action === 'add' ? 'Adicionar' : 'Remover';
        const preposition = action === 'add' ? 'ao' : 'do';
        const user = state.balanceModal;

        openConfirm(
            actionText + ' Saldo',
            actionText + ' R$ ' + amount.toFixed(2) + ' ' + preposition + ' usuário #' + user.id + ' (' + (user.phone || 'sem telefone') + ')?',
            async () => {
                try {
                    state.balanceLoading = true;
                    const res = await api('/admin-api/balance', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ userId: user.id, amount, action })
                    });
                    state.balanceLoading = false;
                    if (res.res.ok) {
                        state.balanceModal = null;
                        $('balance-modal').classList.add('hidden');
                        fetchData();
                        toastr.success('Saldo ajustado com sucesso!');
                    } else {
                        toastr.error((res.json && res.json.error) || 'Erro ao ' + actionText.toLowerCase() + ' saldo.');
                    }
                } catch(e) {
                    state.balanceLoading = false;
                    toastr.error('Erro de conexão ao ajustar saldo.');
                }
            }
        );
    }

    function handleRejectDeposit(id) {
        openConfirm(
            'Rejeitar Depósito',
            'Marcar depósito #' + id + ' como estornado/rejeitado? Esta ação não pode ser desfeita.',
            async () => {
                try {
                    const res = await api('/admin-api/reject-deposit', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ transactionId: id })
                    });
                    if (res.res.ok) { fetchData(); }
                    else { alert((res.json && res.json.error) || 'Erro ao rejeitar depósito.'); }
                } catch(e) { alert('Erro de conexão ao rejeitar depósito.'); }
            }
        );
    }

    function handleToggleLeader(id) {
        const user = (state.data.users || []).find((u) => u.id === id);
        if (!user) return;
        const makeLeader = !user.is_leader;
        openConfirm(
            makeLeader ? 'Marcar como Líder' : 'Remover Líder',
            'Deseja realmente ' + (makeLeader ? 'MARCAR' : 'REMOVER') + ' o usuário "' + esc(user.name || user.phone || '#' + id) + '" como líder?',
            async () => {
                try {
                    const res = await api('/admin-api/toggle-leader', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ userId: id })
                    });
                    if (res.res.ok) { fetchData(); }
                    else { alert((res.json && res.json.error) || 'Erro ao alterar líder.'); }
                } catch(e) { alert('Erro de conexão ao alterar líder.'); }
            }
        );
    }

    function handleToggleBlockAutoWithdraw(id) {
        const user = (state.data.users || []).find((u) => u.id === id);
        if (!user) return;
        const block = !user.block_auto_withdraw;
        openConfirm(
            block ? 'Bloquear Saque Automático' : 'Liberar Saque Automático',
            'Deseja realmente ' + (block ? 'BLOQUEAR' : 'LIBERAR') + ' o saque automático da conta "' + esc(user.name || user.phone || '#' + id) + '"?' + (block ? ' Os saques daqui passam a ficar manuais (aprovação no painel).' : ''),
            async () => {
                try {
                    const res = await api('/admin-api/block-auto-withdraw', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ userId: id, value: block ? 1 : 0 })
                    });
                    if (res.res.ok) { fetchData(); }
                    else { alert((res.json && res.json.error) || 'Erro ao alterar bloqueio de saque automático.'); }
                } catch(e) { alert('Erro de conexão.'); }
            }
        );
    }

    function handlePackageStatus(id) {
        const pkg = (state.data.packages || []).find((p) => p.id === id);
        if (!pkg) return;
        const block = pkg.status === 'active';
        openConfirm(
            block ? 'Bloquear Plano' : 'Liberar Plano',
            'Deseja realmente ' + (block ? 'BLOQUEAR' : 'LIBERAR') + ' a compra do plano "' + esc(pkg.name || pkg.label || '#' + id) + '"?',
            async () => {
                try {
                    const res = await api('/admin-api/package-status', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ packageId: id })
                    });
                    if (res.res.ok) { fetchData(); }
                    else { alert((res.json && res.json.error) || 'Erro ao alterar status do plano.'); }
                } catch(e) { alert('Erro de conexão ao alterar plano.'); }
            }
        );
    }

    function openConfirm(title, message, onConfirm) {
        state.confirmModal = { title, message, onConfirm };
        $('confirm-title').textContent = title;
        $('confirm-message').textContent = message;
        $('confirm-modal').classList.remove('hidden');
    }

    function closeConfirm() {
        state.confirmModal = null;
        $('confirm-modal').classList.add('hidden');
    }

    function filteredUsers() {
        const q = (state.activeTab === 'balance' ? state.searchQueryBalance : state.searchQueryUsers).toLowerCase().trim();
        if (!q || !state.data) return state.data ? (state.data.users || []) : [];
        return (state.data.users || []).filter((u) =>
            u.id.toString().includes(q) ||
            (u.phone && u.phone.includes(q)) ||
            (u.name && u.name.toLowerCase().includes(q))
        );
    }

    function esc(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, (c) => (
            { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]
        ));
    }

    function leaderPhone(phone, isLeader) {
        return (isLeader ? '<span title="Líder">👑</span> ' : '') + (esc(phone) || '-');
    }

    function withdrawStatusBadge(status) {
        if (status === 'pending') return '<span class="bg-orange-100 text-orange-700 px-2 py-1 rounded-md text-xs font-bold">Pendente</span>';
        if (status === 'approved') return '<span class="bg-green-100 text-green-700 px-2 py-1 rounded-md text-xs font-bold">Aprovado</span>';
        return '<span class="bg-red-100 text-red-700 px-2 py-1 rounded-md text-xs font-bold">Rejeitado</span>';
    }

    function depositStatusBadge(status) {
        if (status === 'pending') return '<span class="bg-orange-100 text-orange-700 px-2 py-1 rounded-md text-xs font-bold">Aguardando Pgto</span>';
        if (status === 'approved') return '<span class="bg-green-100 text-green-700 px-2 py-1 rounded-md text-xs font-bold">Pago</span>';
        return '<span class="bg-red-100 text-red-700 px-2 py-1 rounded-md text-xs font-bold">Estornado</span>';
    }

    function renderUsers(users) {
        $('users-tbody').innerHTML = users.length ? users.map((u) => `
            <tr class="hover:bg-gray-50 transition">
                <td class="p-4 text-gray-500">#${u.id}</td>
                <td class="p-4 font-medium text-gray-900">${leaderPhone(u.phone, u.is_leader)}</td>
                <td class="p-4 text-gray-700">${esc(u.name) || '-'}</td>
                <td class="p-4 font-bold text-green-600">${money(u.balance)}</td>
                <td class="p-4 text-gray-500">${fmtDate(u.created_at)}</td>
                <td class="p-4">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="${u.status == 0 ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700'} px-2 py-1 rounded-lg text-xs font-extrabold">${u.status == 0 ? 'BANIDO' : 'ATIVO'}</span>
                        <button onclick="window.__panel.toggleBan(${u.id}, ${u.status == 0 ? 'false' : 'true'})"
                            class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-bold transition ${u.status == 0 ? 'bg-green-600 text-white hover:bg-green-700' : 'bg-red-600 text-white hover:bg-red-700'}">
                            <i data-lucide="user-x" class="w-3 h-3"></i> ${u.status == 0 ? 'Desbanir' : 'Banir'}
                        </button>
                        <button onclick="window.__panel.changePassword(${u.id})"
                            class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-bold bg-blue-600 text-white hover:bg-blue-700 transition">
                            <i data-lucide="key" class="w-3 h-3"></i> Alterar Senha
                        </button>
                        <button onclick="window.__panel.resetSaque(${u.id})"
                            class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-bold bg-orange-500 text-white hover:bg-orange-600 transition">
                            <i data-lucide="recycle" class="w-3 h-3"></i> Resetar PIX
                        </button>
                        <button onclick="window.__panel.openBalance(${u.id})"
                            class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-bold bg-emerald-600 text-white hover:bg-emerald-700 transition">
                            <i data-lucide="wallet" class="w-3 h-3"></i> Ajustar Saldo
                        </button>
                        <button onclick="window.__panel.toggleLeader(${u.id})"
                            class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-bold transition ${u.is_leader ? 'bg-amber-100 text-amber-700 hover:bg-amber-200' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'}">
                            ${u.is_leader ? 'Remover Líder' : 'Marcar como Líder'}
                        </button>
                        <button onclick="window.__panel.toggleBlockAutoWithdraw(${u.id})"
                            class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-bold transition ${u.block_auto_withdraw ? 'bg-red-100 text-red-700 hover:bg-red-200' : 'bg-sky-100 text-sky-700 hover:bg-sky-200'}">
                            <i data-lucide="${u.block_auto_withdraw ? 'lock' : 'unlock'}" class="w-3 h-3"></i> ${u.block_auto_withdraw ? 'Liberar Saque Automático' : 'Bloquear Saque Automático'}
                        </button>
                        <a href="/admin-api/impersonate?userId=${u.id}"
                            class="inline-flex items-center gap-1 bg-indigo-600 text-white px-3 py-1.5 rounded-lg text-xs font-bold hover:bg-indigo-700 transition">
                            <i data-lucide="eye" class="w-3 h-3"></i> Entrar na Conta
                        </a>
                    </div>
                </td>
            </tr>`).join('')
            : `<tr><td colspan="6" class="p-6 text-center text-gray-500">${state.searchQueryUsers ? 'Nenhum usuário encontrado para essa busca.' : 'Nenhum usuário cadastrado.'}</td></tr>`;
        refreshIcons();
    }

    function renderWithdrawals() {
        if (!state.data) return;
        const ws = state.data.withdrawals || [];

        const pending = ws.filter((w) => w.status === 'pending');
        const rejected = ws.filter((w) => w.status === 'rejected');
        const approved = ws.filter((w) => w.status === 'approved');

        $('pending-count').textContent = pending.length;
        $('pending-count').classList.toggle('hidden', pending.length === 0);

        $('withdrawals-tbody').innerHTML = pending.length ? pending.map((w) => `
            <tr class="hover:bg-gray-50 transition">
                <td class="p-4 text-gray-500">#${w.id}${w.self_invite ? '<div class="mt-1 bg-red-100 text-red-700 px-2 py-0.5 rounded text-[10px] font-extrabold" title="Conta autoconvidada (mesmo IP do indicador)">⚠ AUTOCONVIDADO</div>' : ''}</td>
                <td class="p-4 font-medium">${leaderPhone(w.user_phone, w.user_is_leader)}</td>
                <td class="p-4 font-medium text-gray-900">${esc(w.user_name) || '-'}</td>
                <td class="p-4">
                    <div class="font-bold text-red-600">${money(w.final_amount)}</div>
                    <div class="text-xs text-gray-500 font-normal">Bruto: ${money(w.amount)}</div>
                </td>
                <td class="p-4 text-gray-600 text-xs font-semibold uppercase">${esc(w.pix_type) || '-'}</td>
                <td class="p-4 text-gray-600 font-mono text-xs">${esc(w.pix_key) || '-'}</td>
                <td class="p-4">${withdrawStatusBadge(w.status)}</td>
                <td class="p-4 text-gray-500">${fmtDate(w.created_at)}</td>
                <td class="p-4 text-right">
                    <div class="flex justify-end gap-2">
                        <button onclick="window.__panel.withdrawal('${w.id}','approve_gateway')" class="p-2 bg-blue-600 text-white hover:bg-blue-700 rounded-lg transition" title="Confirmar e DISPARAR via gateway (envia o PIX agora)"><i data-lucide="send" class="w-4 h-4"></i></button>
                        <button onclick="window.__panel.withdrawalManual('${w.id}')" class="p-2 bg-green-100 text-green-600 hover:bg-green-200 rounded-lg transition" title="Aprovar Manual (sem enviar via gateway)"><i data-lucide="check" class="w-4 h-4"></i></button>
                        <button onclick="window.__panel.withdrawal('${w.id}','reject')" class="p-2 bg-red-100 text-red-600 hover:bg-red-200 rounded-lg transition" title="Rejeitar"><i data-lucide="x" class="w-4 h-4"></i></button>
                    </div>
                </td>
            </tr>`).join('')
            : '<tr><td colspan="9" class="p-6 text-center text-gray-500">Nenhum saque pendente.</td></tr>';

        $('rejected-tbody').innerHTML = rejected.length ? rejected.map((w) => `
            <tr class="hover:bg-red-50/50 transition">
                <td class="p-4 text-gray-500">#${w.id}</td>
                <td class="p-4 font-medium">${leaderPhone(w.user_phone, w.user_is_leader)}</td>
                <td class="p-4 font-medium text-gray-900">${esc(w.user_name) || '-'}</td>
                <td class="p-4">
                    <div class="font-bold text-red-600">${money(w.final_amount)}</div>
                    <div class="text-xs text-gray-500 font-normal">Bruto: ${money(w.amount)}</div>
                </td>
                <td class="p-4 text-gray-600 text-xs font-semibold uppercase">${esc(w.pix_type) || '-'}</td>
                <td class="p-4 text-gray-600 font-mono text-xs">${esc(w.pix_key) || '-'}</td>
                <td class="p-4 text-gray-500">${fmtDate(w.created_at)}</td>
            </tr>`).join('')
            : '<tr><td colspan="7" class="p-6 text-center text-gray-500">Nenhum saque rejeitado.</td></tr>';

        $('approved-tbody').innerHTML = approved.length ? approved.map((w) => `
            <tr class="hover:bg-gray-50 transition">
                <td class="p-4 text-gray-500">#${w.id}</td>
                <td class="p-4 font-medium">${leaderPhone(w.user_phone, w.user_is_leader)}</td>
                <td class="p-4 font-medium text-gray-900">${esc(w.user_name) || '-'}</td>
                <td class="p-4 font-bold text-green-600">${money(w.amount)}</td>
                <td class="p-4 text-gray-600 text-xs font-semibold uppercase">${esc(w.pix_type) || '-'}</td>
                <td class="p-4 text-gray-600 font-mono text-xs">${esc(w.pix_key) || '-'}</td>
                <td class="p-4 text-gray-500">${fmtDate(w.updated_at || w.created_at)}</td>
            </tr>`).join('')
            : '<tr><td colspan="7" class="p-6 text-center text-gray-500">Nenhum saque aprovado.</td></tr>';
        refreshIcons();
    }

    function renderDeposits() {
        if (!state.data) return;
        const ds = state.data.deposits || [];
        $('deposits-tbody').innerHTML = ds.length ? ds.map((d) => `
            <tr class="hover:bg-gray-50 transition">
                <td class="p-4 text-gray-500">#${d.id}</td>
                <td class="p-4 font-medium">${leaderPhone(d.user_phone, d.user_is_leader)}</td>
                <td class="p-4 font-bold text-blue-600">${money(d.amount)}</td>
                <td class="p-4">${depositStatusBadge(d.status)}</td>
                <td class="p-4 text-gray-500">${fmtDate(d.created_at)}</td>
                <td class="p-4 text-right">
                    ${d.status === 'pending'
                        ? `<button onclick="window.__panel.rejectDeposit('${d.id}')" class="p-2 bg-red-100 text-red-600 hover:bg-red-200 rounded-lg transition" title="Marcar como estornado"><i data-lucide="x" class="w-4 h-4"></i></button>`
                        : ''}
                </td>
            </tr>`).join('')
            : '<tr><td colspan="6" class="p-6 text-center text-gray-500">Nenhum depósito gerado.</td></tr>';
        refreshIcons();
    }

    function renderBalance(users) {
        $('balance-tbody').innerHTML = users.length ? users.map((u) => `
            <tr class="hover:bg-gray-50 transition">
                <td class="p-4 text-gray-500">#${u.id}</td>
                <td class="p-4 font-medium text-gray-900">${leaderPhone(u.phone, u.is_leader)}</td>
                <td class="p-4 text-gray-700">${esc(u.name) || '-'}</td>
                <td class="p-4 font-bold text-green-600">${money(u.balance)}</td>
                <td class="p-4 text-right">
                    <button onclick="window.__panel.openBalance(${u.id})"
                        class="inline-flex items-center gap-1 bg-blue-600 text-white px-3 py-1.5 rounded-lg text-xs font-bold hover:bg-blue-700 transition">
                        <i data-lucide="plus" class="w-3 h-3"></i> Ajustar Saldo
                    </button>
                </td>
            </tr>`).join('')
            : '<tr><td colspan="5" class="p-6 text-center text-gray-500">' + (state.searchQueryBalance ? 'Nenhum usuário encontrado para essa busca.' : 'Nenhum usuário cadastrado.') + '</td></tr>';
        refreshIcons();
    }

    function renderPackages() {
        if (!state.data) return;
        const ps = state.data.packages || [];
        $('packages-tbody').innerHTML = ps.length ? ps.map((p) => `
            <tr class="hover:bg-gray-50 transition">
                <td class="p-4 text-gray-500">#${p.id}</td>
                <td class="p-4 font-bold text-gray-900">${esc(p.label || p.name) || '-'}</td>
                <td class="p-4 text-gray-700">${money(p.price)}</td>
                <td class="p-4 text-gray-500">${p.validity} dias</td>
                <td class="p-4">
                    ${p.status === 'active'
                        ? '<span class="bg-green-100 text-green-700 px-2 py-1 rounded-md text-xs font-bold">Liberado</span>'
                        : '<span class="bg-red-100 text-red-700 px-2 py-1 rounded-md text-xs font-bold">Bloqueado</span>'}
                </td>
                <td class="p-4 text-right">
                    <button onclick="window.__panel.packageStatus(${p.id})"
                        class="inline-flex items-center gap-1 ${p.status === 'active' ? 'bg-red-600 hover:bg-red-700' : 'bg-green-600 hover:bg-green-700'} text-white px-3 py-1.5 rounded-lg text-xs font-bold transition">
                        <i data-lucide="${p.status === 'active' ? 'lock' : 'unlock'}" class="w-3 h-3"></i>
                        ${p.status === 'active' ? 'Bloquear' : 'Liberar'}
                    </button>
                </td>
            </tr>`).join('')
            : '<tr><td colspan="6" class="p-6 text-center text-gray-500">Nenhum plano cadastrado.</td></tr>';
        refreshIcons();
    }

    let statePlans = { plans: [], loading: false };

    async function fetchPlans() {
        statePlans.loading = true;
        try {
            const res = await api('/admin-api/plans');
            if (res.res.ok && res.json && res.json.success) {
                statePlans.plans = res.json.plans;
            }
            fetchProductImages();
            fetchBonusCodes();
        } catch(e) {}
        statePlans.loading = false;
        renderPlanos();
    }

    function resolvePlanImg(v) {
        if (!v) return null;
        if (v.indexOf('data:') === 0 || v.indexOf('http') === 0) return v;
        return '/core/img/' + v;
    }

    function syncImgChoices() {
        const hidden = document.getElementById('plan-image');
        const val = hidden ? hidden.value : '';
        document.querySelectorAll('.plan-img-choice').forEach(function (x) {
            const on = x.getAttribute('data-img') === val;
            x.style.borderColor = on ? '#2563eb' : '#e5e7eb';
            x.style.boxShadow = on ? '0 0 0 2px rgba(37,99,235,0.45)' : 'none';
        });
    }

    async function fetchBonusCodes() {
        try {
            const res = await api('/admin-api/bonus-codes');
            if (res.res.ok && res.json && res.json.success) {
                renderBonusCodes(res.json.codes);
            }
        } catch(e) {}
    }

    function renderBonusCodes(codes) {
        const box = $('bonus-codes-list');
        if (!box) return;
        box.innerHTML = (codes && codes.length) ? codes.map((c) => `
            <div class="flex items-center gap-3 p-2 bg-white rounded-lg border border-gray-100">
                <span class="font-mono font-bold text-gray-900 text-sm">${esc(c.code)}</span>
                <span class="text-green-600 font-bold text-sm">${money(c.amount)}</span>
                <span class="text-xs text-gray-500">usos: ${c.uses}/${c.max_uses}</span>
                <span class="flex-1"></span>
                ${c.expires_at ? `<span class="text-xs font-bold ${new Date(c.expires_at) < new Date() ? 'text-red-500' : 'text-gray-600'}">expira: ${new Date(c.expires_at).toLocaleString('pt-BR')}</span>` : '<span class="text-xs text-gray-400">sem validade</span>'}
                <button onclick="window.__panel.deleteBonusCode(${c.id})" class="p-2 bg-red-100 text-red-600 hover:bg-red-200 rounded-lg transition" title="Excluir"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
            </div>`).join('')
            : '<div class="text-sm text-gray-400">Nenhum código criado.</div>';
        refreshIcons();
    }

    async function createBonusCode() {
        const code = $('bonus-new-code').value.trim().toUpperCase();
        const amount = parseFloat($('bonus-new-amount').value) || 0;
        const maxUses = parseInt($('bonus-new-max').value) || 1;
        const durationValue = parseInt($('bonus-duration-value').value) || 0;
        const durationUnit = $('bonus-duration-unit').value;
        if (amount <= 0) { alert('Informe o valor do bônus.'); return; }
        try {
            const res = await api('/admin-api/bonus-codes-create', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ code: code, amount: amount, max_uses: maxUses, duration_value: durationValue, duration_unit: durationUnit })
            });
            if (res.res.ok) {
                $('bonus-new-code').value = ''; $('bonus-new-amount').value = '';
                fetchBonusCodes();
            } else { alert((res.json && res.json.error) || 'Erro ao criar código.'); }
        } catch(e) { alert('Erro de conexão.'); }
    }

    function generateBonusCode() {
        const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        let code = 'WB-';
        for (let i = 0; i < 6; i++) code += chars.charAt(Math.floor(Math.random() * chars.length));
        $('bonus-new-code').value = code;
    }

    async function deleteBonusCode(id) {
        try {
            const res = await api('/admin-api/bonus-codes-delete', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id })
            });
            if (res.res.ok) { fetchBonusCodes(); }
            else { alert('Erro ao excluir.'); }
        } catch(e) { alert('Erro de conexão.'); }
    }

    async function fetchProductImages() {
        try {
            const res = await api('/admin-api/product-images');
            if (res.res.ok && res.json && res.json.success) {
                statePlans.images = res.json.images;
            }
        } catch(e) {}
        renderImagens();
    }

    function renderImagens() {
        const box = $('imagens-produtos');
        if (!box) return;
        const list = statePlans.images || [];
        box.innerHTML = list.length ? list.map((img) => `
            <div class="flex items-center gap-3 p-2 bg-white rounded-lg border border-gray-100">
                <img src="/core/img/${esc(img.image)}" class="w-12 h-12 rounded-lg object-cover" onerror="this.style.display='none'">
                <span class="text-sm text-gray-700 flex-1 font-semibold">${esc(img.image)}</span>
                <button onclick="window.__panel.moveImage(${img.id},'up')" class="p-2 bg-gray-100 text-gray-600 hover:bg-gray-200 rounded-lg transition" title="Subir"><i data-lucide="arrow-up" class="w-4 h-4"></i></button>
                <button onclick="window.__panel.moveImage(${img.id},'down')" class="p-2 bg-gray-100 text-gray-600 hover:bg-gray-200 rounded-lg transition" title="Descer"><i data-lucide="arrow-down" class="w-4 h-4"></i></button>
            </div>`).join('')
            : '<div class="text-sm text-gray-400">Nenhuma imagem cadastrada.</div>';
        refreshIcons();
    }

    async function cyclePlanImage(id, dir) {
        try {
            const res = await api('/admin-api/plan-image-cycle', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id, dir: dir })
            });
            if (res.res.ok) { fetchPlans(); }
            else { alert((res.json && res.json.error) || 'Erro ao trocar imagem.'); }
        } catch(e) { alert('Erro de conexão.'); }
    }

    async function moveImage(id, dir) {
        try {
            const res = await api('/admin-api/product-images-move', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id, dir: dir })
            });
            if (res.res.ok) { fetchProductImages(); }
            else { alert((res.json && res.json.error) || 'Erro ao mover imagem.'); }
        } catch(e) { alert('Erro de conexão.'); }
    }

    function renderPlanos() {
        const ps = statePlans.plans || [];
        const staticImgs = ['produto1.webp','produto2.webp','produto3.webp','produto4.webp','produto5.webp','produto6.webp'];
        $('planos-tbody').innerHTML = ps.length ? ps.map((p, i) => {
            const staticImg = '/core/img/' + staticImgs[p.id % 6];
            const imgSrc = (p.image && resolvePlanImg(p.image)) || staticImg;
            const canCycleImage = !p.image || (typeof p.image === 'string' && p.image.indexOf('data:') !== 0);
            const imgHtml = '<div class="flex flex-col items-center gap-0.5">'
                + (canCycleImage ? '<button onclick="window.__panel.cyclePlanImage(' + p.id + ',\'up\')" class="text-gray-400 hover:text-gray-700 leading-none" title="Imagem anterior"><i data-lucide="chevron-up" class="w-3 h-3"></i></button>' : '')
                + '<img src="' + esc(imgSrc) + '" class="w-10 h-10 rounded-lg object-cover" onerror="this.style.display=\'none\'">'
                + (canCycleImage ? '<button onclick="window.__panel.cyclePlanImage(' + p.id + ',\'down\')" class="text-gray-400 hover:text-gray-700 leading-none" title="Próxima imagem"><i data-lucide="chevron-down" class="w-3 h-3"></i></button>' : '')
                + '</div>';
            const statusBadge = p.status == 1
                ? '<span class="bg-green-100 text-green-700 px-2 py-1 rounded-md text-xs font-bold">Ativo</span>'
                : '<span class="bg-red-100 text-red-700 px-2 py-1 rounded-md text-xs font-bold">Inativo</span>';
            return `
            <tr class="hover:bg-gray-50 transition">
                <td class="p-3">${imgHtml}</td>
                <td class="p-3 font-bold text-gray-900">${esc(p.name)}</td>
                <td class="p-3 text-gray-700">${money(p.fixed_amount)}</td>
                <td class="p-3 text-gray-500">${p.repeat_time || p.time} dia(s)</td>
                <td class="p-3">${statusBadge}</td>
                <td class="p-3 text-right">
                    <div class="flex justify-end gap-1">
                        <button onclick="window.__panel.movePlan(${p.id}, 'up')" title="Subir"
                        class="p-2 bg-gray-100 text-gray-600 hover:bg-gray-200 rounded-lg transition"><i data-lucide="arrow-up" class="w-4 h-4"></i></button>
                    <button onclick="window.__panel.movePlan(${p.id}, 'down')" title="Descer"
                        class="p-2 bg-gray-100 text-gray-600 hover:bg-gray-200 rounded-lg transition"><i data-lucide="arrow-down" class="w-4 h-4"></i></button>
                    <button onclick="editPlan(${p.id})" class="p-2 bg-blue-100 text-blue-600 hover:bg-blue-200 rounded-lg transition" title="Editar"><i data-lucide="pencil" class="w-4 h-4"></i></button>
                        <button onclick="deletePlan(${p.id})" class="p-2 bg-red-100 text-red-600 hover:bg-red-200 rounded-lg transition" title="Excluir"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                    </div>
                </td>
            </tr>`;
        }).join('')
        : '<tr><td colspan="6" class="p-6 text-center text-gray-500">Nenhum plano cadastrado.</td></tr>';
        refreshIcons();
    }

    function openPlanModal(plan) {
        const modal = $('plan-modal');
        const preview = $('plan-image-preview');
        const fileInput = $('plan-image-file');
        const hidden = $('plan-image');
        const extraFields = modal.querySelectorAll('.plan-extra');
        if (plan) {
            $('plan-modal-title').textContent = 'Editar Plano';
            $('plan-id').value = plan.id;
            $('plan-name').value = plan.name || '';
            $('plan-amount').value = parseFloat(plan.fixed_amount) || '';
            $('plan-time').value = plan.repeat_time || '';
            $('plan-daily-return').value = parseFloat(plan.interest) || '';
            $('plan-total-return').value = 'R$ ' + ((parseFloat(plan.interest) * parseInt(plan.repeat_time)) || 0).toFixed(2);
            $('plan-time-name').value = plan.time_name || '';
            $('plan-status').value = plan.status ?? 1;
            $('plan-featured').value = plan.featured ?? 0;
            $('plan-capital-back').value = plan.capital_back ?? 0;
            $('plan-lifetime').value = plan.lifetime ?? 0;
            $('plan-max').value = plan.max_compras ?? 0;
            hidden.value = plan.image || '';
            fileInput.value = '';
            const resolvedImg = resolvePlanImg(plan.image);
            if (resolvedImg) { preview.src = resolvedImg; preview.classList.remove('hidden'); }
            else {
                const staticImgs = ['produto1.webp','produto2.webp','produto3.webp','produto4.webp','produto5.webp','produto6.webp'];
                preview.src = '/core/img/' + staticImgs[(plan.id || 0) % 6];
                preview.classList.remove('hidden');
            }
            syncImgChoices();
            extraFields.forEach(el => el.style.display = '');
        } else {
            $('plan-modal-title').textContent = 'Novo Plano';
            $('plan-id').value = '';
            $('plan-name').value = '';
            $('plan-amount').value = '';
            $('plan-time').value = '';
            $('plan-daily-return').value = '';
            $('plan-total-return').value = '';
            $('plan-time-name').value = '';
            $('plan-status').value = '1';
            $('plan-featured').value = '1';
            $('plan-capital-back').value = '0';
            $('plan-lifetime').value = '0';
            $('plan-max').value = '0';
            hidden.value = '';
            fileInput.value = '';
            preview.classList.add('hidden');
            extraFields.forEach(el => el.style.display = 'none');
        }
        modal.classList.remove('hidden');
    }

    function closePlanModal() {
        $('plan-modal').classList.add('hidden');
    }

    function refreshIcons() {
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    function updatePlanPreview() {
        const dailyReturn = parseFloat($('plan-daily-return').value) || 0;
        const time = parseInt($('plan-time').value) || 0;
        const preview = $('plan-preview');
        if (dailyReturn > 0 && time > 0) {
            const totalReturn = dailyReturn * time;
            $('plan-total-return').value = 'R$ ' + totalReturn.toFixed(2);
            preview.classList.remove('hidden');
            $('preview-daily').textContent = 'R$ ' + dailyReturn.toFixed(2);
            $('preview-cycle').textContent = time + ' dia(s)';
            $('preview-total').textContent = 'R$ ' + totalReturn.toFixed(2);
        } else {
            $('plan-total-return').value = '';
            preview.classList.add('hidden');
        }
    }

    ['plan-daily-return', 'plan-time'].forEach(function(id) {
        var el = document.getElementById(id);
        if (el) el.addEventListener('input', updatePlanPreview);
        if (el) el.addEventListener('change', updatePlanPreview);
    });

    document.querySelectorAll('.plan-img-choice').forEach(function (el) {
        el.addEventListener('click', function () {
            const hidden = document.getElementById('plan-image');
            const preview = document.getElementById('plan-image-preview');
            hidden.value = el.getAttribute('data-img');
            preview.src = resolvePlanImg(el.getAttribute('data-img'));
            preview.classList.remove('hidden');
            syncImgChoices();
        });
    });

    const bonusCreateBtn = document.getElementById('bonus-create-btn');
    if (bonusCreateBtn) {
        bonusCreateBtn.addEventListener('click', createBonusCode);
    }
    const bonusGenerateBtn = document.getElementById('bonus-generate-btn');
    if (bonusGenerateBtn) {
        bonusGenerateBtn.addEventListener('click', generateBonusCode);
    }

    var imgFileEl = document.getElementById('plan-image-file');
    if (imgFileEl) {
        imgFileEl.addEventListener('change', function() {
            const file = this.files[0];
            const preview = document.getElementById('plan-image-preview');
            const hidden = document.getElementById('plan-image');
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = new Image();
                    img.onload = function() {
                        try {
                            const maxDim = 1000;
                            let w = img.width, h = img.height;
                            if (w > maxDim || h > maxDim) {
                                const ratio = Math.min(maxDim / w, maxDim / h);
                                w = Math.round(w * ratio); h = Math.round(h * ratio);
                            }
                            const canvas = document.createElement('canvas');
                            canvas.width = w; canvas.height = h;
                            canvas.getContext('2d').drawImage(img, 0, 0, w, h);
                            let out = canvas.toDataURL('image/jpeg', 0.8);
                            if (out.length > 3500000) out = canvas.toDataURL('image/jpeg', 0.5);
                            hidden.value = out;
                            preview.src = out;
                            syncImgChoices();
                        } catch (err) {
                            hidden.value = e.target.result;
                            preview.src = e.target.result;
                        }
                        preview.classList.remove('hidden');
                    };
                    img.onerror = function() {
                        hidden.value = e.target.result;
                        preview.src = e.target.result;
                        preview.classList.remove('hidden');
                    };
                    img.src = e.target.result;
                };
                reader.readAsDataURL(file);
            } else {
                hidden.value = '';
                preview.classList.add('hidden');
            }
        });
    }

    function editPlan(id) {
        const plan = statePlans.plans.find((p) => p.id === id);
        if (plan) openPlanModal(plan);
    }

    async function savePlan() {
        const id = $('plan-id').value;
        const timeVal = $('plan-time').value.trim();
        const data = {
            name: $('plan-name').value.trim(),
            image: $('plan-image').value.trim() || null,
            fixed_amount: parseFloat($('plan-amount').value) || 0,
            interest: parseFloat($('plan-daily-return').value) || 0,
            interest_type: 0,
            time: timeVal,
            time_name: $('plan-time-name').value.trim() || null,
            repeat_time: parseInt(timeVal) || 1,
            status: $('plan-status').value === '0' ? 0 : 1,
            featured: $('plan-featured').value === '0' ? 0 : 1,
            capital_back: parseInt($('plan-capital-back').value) || 0,
            lifetime: parseInt($('plan-lifetime').value) || 0,
            max_compras: parseInt($('plan-max').value) || 0,
        };
        if (!data.name || !data.time) {
            alert('Preencha nome e prazo.');
            return;
        }
        try {
            const url = id ? '/admin-api/plans-update/' + id : '/admin-api/plans';
            const res = await api(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            if (res.res.ok && res.json && res.json.success) {
                alert(id ? 'Plano atualizado com sucesso!' : 'Plano criado com sucesso!');
                closePlanModal();
                fetchPlans();
            } else {
                let msg = 'Erro ao salvar plano.';
                if (res.json) {
                    if (res.json.error) msg = res.json.error;
                    else if (res.json.message) msg = res.json.message;
                    if (res.json.errors) {
                        msg += '\n' + Object.entries(res.json.errors).map(([k,v]) => k + ': ' + (Array.isArray(v) ? v.join(', ') : v)).join('\n');
                    }
                }
                alert(msg);
            }
        } catch(e) {
            alert('Erro de conexão ao salvar plano.');
        }
    }

    async function movePlan(id, dir) {
        try {
            const res = await api('/admin-api/plans-move', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id, dir: dir })
            });
            if (res.res.ok) { fetchPlans(); }
            else { alert((res.json && res.json.error) || 'Erro ao mover plano.'); }
        } catch(e) { alert('Erro de conexão.'); }
    }

    function deletePlan(id) {
        const plan = statePlans.plans.find((p) => p.id === id);
        const name = plan ? plan.name : '#' + id;
        openConfirm(
            'Excluir Plano',
            'Deseja realmente excluir o plano "' + esc(name) + '"?',
            async () => {
                try {
                    const res = await api('/admin-api/plans-delete/' + id, { method: 'POST' });
                    if (res.res.ok && res.json && res.json.success) {
                        fetchPlans();
                    } else {
                        alert((res.json && res.json.error) || 'Erro ao excluir plano.');
                    }
                } catch(e) { alert('Erro de conexão ao excluir plano.'); }
            }
        );
    }

    function switchTab(tab) {
        state.activeTab = tab;
        document.querySelectorAll('.tab-btn').forEach((b) => {
            const active = b.dataset.tab === tab;
            b.className = 'tab-btn pb-3 px-2 font-semibold transition whitespace-nowrap ' + (b.dataset.tab === 'withdrawals' ? 'flex items-center gap-2 ' : '') +
                (active ? (tab === 'rejected' ? 'border-b-2 border-red-600 text-red-600' : 'border-b-2 border-blue-600 text-blue-600') : 'text-gray-500 hover:text-gray-700');
        });
        document.querySelectorAll('.tab-content').forEach((c) => c.classList.add('hidden'));
        $('tab-' + tab).classList.remove('hidden');
        if (tab === 'users' || tab === 'balance') {
            renderUsersAndBalance();
        }
        if (tab === 'planos') {
            fetchPlans();
        }
        if (tab === 'alerts') {
            loadAlerts();
        }
        refreshIcons();
    }

    function dateStr(offsetDays) {
        const d = new Date();
        d.setDate(d.getDate() + offsetDays);
        const mm = String(d.getMonth() + 1).padStart(2, '0');
        const dd = String(d.getDate()).padStart(2, '0');
        return d.getFullYear() + '-' + mm + '-' + dd;
    }

    function applyStatsDate(val) {
        state.statsDate = val || '';
        const label = $('stats-date-label');
        if (state.statsDate) {
            label.textContent = 'Exibindo dados de ' + state.statsDate.split('-').reverse().join('/') + ' — usuários cadastrados, depósitos e saques pagos no dia.';
        } else {
            label.textContent = '';
        }
        fetchData();
    }

    async function loadAlerts() {
        const body = $('alerts-body');
        body.innerHTML = '<p class="text-sm text-gray-400">Carregando...</p>';
        try {
            const res = await api('/admin-api/alerts');
            if (!res.res.ok) {
                body.innerHTML = '<p class="text-sm text-red-500">' + ((res.json && res.json.error) || 'Erro ao carregar alertas.') + '</p>';
                return;
            }
            state.alertsGroups = res.json.groups || [];
            renderAlerts();
        } catch(e) {
            body.innerHTML = '<p class="text-sm text-red-500">Erro de conexão ao carregar alertas.</p>';
        }
    }

    function renderAlerts() {
        const body = $('alerts-body');
        const groups = state.alertsGroups || [];
        const q = (state.searchQueryAlerts || '').toLowerCase().trim();

        if (!groups.length) {
            body.innerHTML = '<p class="text-sm text-gray-500">Nenhuma conta com IP compartilhado no momento.</p>';
            return;
        }

        const filtered = q ? groups.map((g) => ({
            ...g,
            users: (g.users || []).filter((u) =>
                String(u.phone || '').toLowerCase().includes(q) ||
                String(u.id).includes(q) ||
                String(u.name || '').toLowerCase().includes(q))
        })).filter((g) => g.users.length) : groups;

        body.innerHTML = filtered.map((g) => `
            <div class="border border-gray-200 rounded-xl mb-4 overflow-hidden">
                <div class="bg-gray-50 px-4 py-3 flex items-center justify-between border-b border-gray-200">
                    <div>
                        <span class="font-bold text-gray-900 font-mono">${esc(g.ip)}</span>
                        <span class="ml-2 text-xs text-gray-500">${g.users.length} conta(s)</span>
                        ${g.has_self_invite ? '<span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-purple-100 text-purple-700">Se auto convidou</span>' : ''}
                    </div>
                </div>
                <div class="divide-y divide-gray-100">
                    ${g.users.map((u) => `
                        <div class="px-4 py-3 flex items-center justify-between flex-wrap gap-2">
                            <div class="flex items-center gap-3">
                                <div>
                                    <div class="font-bold text-gray-900 text-sm">#${u.id} ${esc(u.phone)}</div>
                                    <div class="text-xs text-gray-500">${esc(u.name)} · ${new Date(u.created_at).toLocaleDateString('pt-BR')} · ${money(u.balance)}</div>
                                    ${u.self_invite ? `<div class="text-xs font-bold text-purple-700">Se auto convidou (indicado por ${esc(u.invited_by || '—')})</div>` : ''}
                                    ${u.status == 0 ? '<div class="text-xs font-bold text-red-600">BANIDO — ' + esc(u.ban_reason || '') + '</div>' : ''}
                                </div>
                            </div>
<div class="flex items-center gap-2">
                                    <button onclick="window.__panel.openBalance(${u.id})"
                                        class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-bold bg-emerald-100 text-emerald-700 hover:bg-emerald-200 transition"><i data-lucide="wallet" class="w-3 h-3"></i> Saldo</button>
                                    <button onclick="window.__panel.toggleBan(${u.id}, ${u.status == 0 ? 'false' : 'true'})"
                                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-bold transition ${u.status == 0 ? 'bg-green-100 text-green-700 hover:bg-green-200' : 'bg-red-100 text-red-700 hover:bg-red-200'}">
                                    ${u.status == 0 ? 'Desbanir' : 'Banir'}
                                </button>
                                <a href="/admin-api/impersonate?userId=${u.id}"
                                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-bold bg-indigo-100 text-indigo-700 hover:bg-indigo-200 transition">Ver conta</a>
                            </div>
                        </div>`).join('')}
                </div>
            </div>`).join('');
        refreshIcons();
    }

    async function toggleBan(id, ban) {
        openConfirm(
            ban ? 'Banir Usuário' : 'Desbanir Usuário',
            ban ? 'O usuário verá: "Conta banida. Sistema identificou uma tentativa de fraude em suas contas." Confirmar?' : 'O usuário voltará a acessar a conta normalmente. Confirmar?',
            async () => {
                try {
                    const res = await api('/admin-api/ban', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ userId: id, ban })
                    });
                    if (res.res.ok) { loadAlerts(); toastr.success(ban ? 'Usuário banido.' : 'Usuário desbanido.'); }
                    else { toastr.error((res.json && res.json.error) || 'Erro ao atualizar usuário.'); }
                } catch(e) { toastr.error('Erro de conexão.'); }
            }
        );
    }

    async function changePassword(id) {
        const pw = prompt('Nova senha para o usuário #' + id + ':');
        if (pw === null) return;
        if (pw.trim().length < 4) { toastr.error('Senha muito curta (mínimo 4 caracteres).'); return; }
        try {
            const res = await api('/admin-api/change-password', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ userId: id, password: pw })
            });
            if (res.res.ok) { toastr.success('Senha alterada com sucesso.'); }
            else { toastr.error((res.json && res.json.error) || 'Erro ao alterar senha.'); }
        } catch(e) { toastr.error('Erro de conexão.'); }
    }

    async function resetSaque(id) {
        openConfirm('Resetar Dados de Saque', 'Limpar o PIX e arquivar saques aprovados do usuário #' + id + '? Ele poderá cadastrar novamente.', async () => {
            try {
                const res = await api('/admin-api/reset-saque', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ userId: id })
                });
                if (res.res.ok) { toastr.success('Dados de saque resetados.'); fetchData(); }
                else { toastr.error((res.json && res.json.error) || 'Erro ao resetar.'); }
            } catch(e) { toastr.error('Erro de conexão.'); }
        });
    }

    function renderUsersAndBalance() {
        if (!state.data) return;
        const users = filteredUsers();
        renderUsers(users);
        renderBalance(users);
    }

    function render() {
        const logged = state.isLoggedIn;
        const pending = !logged && !!state.token;
        $('login-screen').classList.toggle('hidden', logged);
        $('login-card').classList.toggle('hidden', pending);
        $('login-loading').classList.toggle('hidden', !pending);
        $('panel-screen').classList.toggle('hidden', !logged);
        $('panel-loading').classList.toggle('hidden', !(logged && state.loading && !state.data));
        $('panel-content').classList.toggle('hidden', !(logged && state.data));

        if (!logged || !state.data) return;

        $('stat-total-users').textContent = state.data.stats.totalUsers || 0;
        $('stat-total-deposits').textContent = money(state.data.stats.totalDeposits);
        $('stat-total-withdrawals').textContent = money(state.data.stats.totalWithdrawals);
        $('stat-pending-withdrawals').textContent = money(state.data.stats.pendingWithdrawalsAmount);
        $('stat-total-balance').textContent = money(state.data.stats.totalBalance);

        const activeDate = state.data.statsDate || '';
        const dateSuffix = activeDate ? ' (' + activeDate + ')' : '';
        $('stat-label-users').textContent = 'Total de Usuários' + dateSuffix;
        $('stat-label-deposits').textContent = 'Depósitos Pagos' + dateSuffix;
        $('stat-label-withdrawals').textContent = 'Saques Pagos' + dateSuffix;

        renderUsersAndBalance();
        renderWithdrawals();
        renderDeposits();
        renderPackages();
        fetchPlans();

        refreshIcons();
    }

    window.__panel = {
        withdrawal: (id, action) => handleWithdrawalAction(parseInt(id), action),
        withdrawalManual: (id) => handleWithdrawalManual(parseInt(id)),
        rejectDeposit: (id) => handleRejectDeposit(parseInt(id)),
        packageStatus: (id) => handlePackageStatus(parseInt(id)),
        toggleLeader: (id) => handleToggleLeader(parseInt(id)),
        toggleBlockAutoWithdraw: (id) => handleToggleBlockAutoWithdraw(parseInt(id)),
        movePlan: (id, dir) => movePlan(parseInt(id), dir),
        moveImage: (id, dir) => moveImage(parseInt(id), dir),
        deleteBonusCode: (id) => deleteBonusCode(parseInt(id)),
        cyclePlanImage: (id, dir) => cyclePlanImage(parseInt(id), dir),
        toggleBan: (id, ban) => toggleBan(Number(id), ban),
        changePassword: (id) => changePassword(Number(id)),
        resetSaque: (id) => resetSaque(Number(id)),
        loadAlerts: () => loadAlerts(),
        openBalance: (id) => {
            const user = (state.data.users || []).find((u) => u.id === id);
            if (!user) return;
            state.balanceModal = user;
            $('balance-modal-info').innerHTML =
                'Usuário: <strong>#' + esc(user.id) + '</strong> &mdash; ' + (user.is_leader ? '👑 ' : '') + esc(user.phone || 'sem telefone') +
                '<br>Saldo atual: <strong>' + money(user.balance) + '</strong>';
            $('balance-amount').value = '';
            $('balance-modal').classList.remove('hidden');
            $('balance-amount').focus();
        },
    };

    document.addEventListener('DOMContentLoaded', function () {
        // The login form submits natively via HTML POST to Laravel - no JS needed
        $('btn-logout').addEventListener('click', handleLogout);
        $('search-users').addEventListener('input', (e) => { state.searchQueryUsers = e.target.value; renderUsersAndBalance(); });
        $('search-alerts').addEventListener('input', (e) => { state.searchQueryAlerts = e.target.value; renderAlerts(); });
        $('stats-date').addEventListener('change', (e) => { applyStatsDate(e.target.value); });
        $('stats-today').addEventListener('click', () => { const v = dateStr(0); $('stats-date').value = v; applyStatsDate(v); });
        $('stats-yesterday').addEventListener('click', () => { const v = dateStr(-1); $('stats-date').value = v; applyStatsDate(v); });
        $('stats-clear').addEventListener('click', () => { $('stats-date').value = ''; applyStatsDate(''); });
        $('search-balance').addEventListener('input', (e) => { state.searchQueryBalance = e.target.value; renderUsersAndBalance(); });
        $('balance-cancel').addEventListener('click', () => { state.balanceModal = null; $('balance-modal').classList.add('hidden'); });
        $('balance-add').addEventListener('click', () => handleAdjustBalance('add'));
        $('balance-remove').addEventListener('click', () => handleAdjustBalance('remove'));
        $('confirm-cancel').addEventListener('click', closeConfirm);
        $('confirm-ok').addEventListener('click', async () => {
            if (state.confirmModal) {
                const cb = state.confirmModal.onConfirm;
                closeConfirm();
                try { await cb(); } catch(e) { console.error('Confirm action error:', e); }
            } else {
                closeConfirm();
            }
        });
        document.querySelectorAll('.tab-btn').forEach((b) => b.addEventListener('click', () => switchTab(b.dataset.tab)));

        // state.isLoggedIn is set from window.__adminLoggedIn (injected by PHP/Blade)
        window.editPlan = editPlan;
    window.savePlan = savePlan;
    window.deletePlan = deletePlan;
    window.openPlanModal = openPlanModal;
    window.closePlanModal = closePlanModal;
    window.switchTab = switchTab;
    window.fetchPlans = fetchPlans;

    render();
        if (state.isLoggedIn) {
            fetchData();
        } else if (state.token) {
            state.loading = true;
            render();
            api('/admin-api/data').then(function(res) {
                if (res.res.ok) {
                    state.data = res.json;
                    state.isLoggedIn = true;
                } else if (res.res.status === 401) {
                    state.token = null;
                    localStorage.removeItem('admin_panel_token');
                } else {
                    state.token = null;
                    localStorage.removeItem('admin_panel_token');
                    $('login-error').textContent = 'Erro ao carregar dados (código ' + res.res.status + '). Tente novamente.';
                    $('login-error').classList.remove('hidden');
                }
                state.loading = false;
                render();
            }).catch(function() {
                state.loading = false;
                state.token = null;
                localStorage.removeItem('admin_panel_token');
                render();
                $('login-error').textContent = 'Erro de conexão. Verifique sua internet.';
                $('login-error').classList.remove('hidden');
            });
        }
    });
})();
</script>
</body>
</html>
