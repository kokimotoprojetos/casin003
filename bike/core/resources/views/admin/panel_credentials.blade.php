<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Painel Administrativo - Credenciais - {{ config('app.name') }}</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css"/>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
</head>
<body class="bg-gray-50 text-gray-800 font-sans antialiased">

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

            <form id="login-form" class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold mb-1">E-mail ou Usuário</label>
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
                <h1 class="text-xl font-bold text-gray-900">Painel Administrativo (Credenciais)</h1>
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

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-8">
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex items-center gap-4">
                    <div class="p-4 bg-blue-50 text-blue-600 rounded-lg"><i data-lucide="users" class="w-6 h-6"></i></div>
                    <div>
                        <p class="text-sm text-gray-500 font-medium">Total de Usuários</p>
                        <p id="stat-total-users" class="text-2xl font-bold">0</p>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex items-center gap-4">
                    <div class="p-4 bg-green-50 text-green-600 rounded-lg"><i data-lucide="banknote" class="w-6 h-6"></i></div>
                    <div>
                        <p class="text-sm text-gray-500 font-medium">Depósitos Pagos</p>
                        <p id="stat-total-deposits" class="text-2xl font-bold">R$ 0.00</p>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex items-center gap-4">
                    <div class="p-4 bg-purple-50 text-purple-600 rounded-lg"><i data-lucide="credit-card" class="w-6 h-6"></i></div>
                    <div>
                        <p class="text-sm text-gray-500 font-medium">Saques Pagos</p>
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
                                    <th class="p-4 font-semibold">Senha</th>
                                    <th class="p-4 font-semibold">Nome</th>
                                    <th class="p-4 font-semibold">Saldo</th>
                                    <th class="p-4 font-semibold">IP</th>
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
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="font-bold text-gray-900">Contas com mesmo IP</h3>
                            <button onclick="loadAlerts()" class="text-xs font-bold px-3 py-1.5 bg-gray-100 rounded-lg hover:bg-gray-200 transition">Atualizar</button>
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
(function () {
    const state = {
        isLoggedIn: false,
        data: null,
        loading: false,
        activeTab: 'users',
        searchQuery: '',
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
        options.headers = Object.assign({ 'X-CSRF-TOKEN': csrf() }, options.headers || {});
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

    async function handleLogin(e) {
        e.preventDefault();
        $('login-error').classList.add('hidden');

        var email = $('login-email').value.trim();
        var password = $('login-password').value;
        var btn = document.querySelector('#login-form button[type="submit"]');

        if (!email || !password) {
            $('login-error').textContent = 'Informe e-mail e senha.';
            $('login-error').classList.remove('hidden');
            return;
        }

        if (btn) {
            btn.disabled = true;
            btn.textContent = 'Entrando...';
        }

        const res = await api('/admin-api/login', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email: email, password: password })
        });

        if (btn) {
            btn.disabled = false;
            btn.textContent = 'Entrar no Painel';
        }

        if (res.res.status === 429) {
            $('login-error').textContent = 'Muitas tentativas de login. Aguarde 1 minuto e tente novamente.';
            $('login-error').classList.remove('hidden');
        } else if (res.res.ok && res.json && res.json.success && res.json.token) {
            state.token = res.json.token;
            localStorage.setItem('admin_panel_token', res.json.token);
            location.reload();
        } else {
            $('login-error').textContent = res.json && res.json.error ? res.json.error : 'Credenciais inválidas';
            $('login-error').classList.remove('hidden');
        }
    }

    async function handleLogout() {
        try { await api('/admin-api/login', { method: 'DELETE' }); } catch(e) {}
        state.token = null;
        state.isLoggedIn = false;
        state.data = null;
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
            const res = await api('/admin-api/data');
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
        const actionLabel = action === 'approve' ? 'APROVAR AUTOMATICAMENTE (envia via gateway)' : 'REJEITAR';
        openConfirm(
            'Confirmar Ação',
            'Deseja realmente ' + actionLabel + ' este saque?',
            async () => {
                const res = await api('/admin-api/withdrawals', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ transactionId: id, action })
                });
                if (res.res.ok) { fetchData(); }
                else { alert((res.json && res.json.error) || 'Erro ao realizar ação.'); }
            }
        );
    }

    function handleWithdrawalManual(id) {
        openConfirm(
            'Aprovar Manualmente',
            'Deseja aprovar este saque SEM enviar via gateway? O saque será marcado como aprovado, mas o PIX não será enviado automaticamente.',
            async () => {
                const res = await api('/admin-api/withdrawals', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ transactionId: id, action: 'approve_manual' })
                });
                if (res.res.ok) { fetchData(); }
                else { alert((res.json && res.json.error) || 'Erro ao aprovar saque.'); }
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
                const res = await api('/admin-api/reject-deposit', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ transactionId: id })
                });
                if (res.res.ok) { fetchData(); }
                else { alert((res.json && res.json.error) || 'Erro ao rejeitar depósito.'); }
            }
        );
    }

    function handlePackageStatus(id) {
        const pkg = state.data.packages.find((p) => p.id === id);
        if (!pkg) return;
        const block = pkg.status === 'active';
        openConfirm(
            block ? 'Bloquear Plano' : 'Liberar Plano',
            'Deseja realmente ' + (block ? 'BLOQUEAR' : 'LIBERAR') + ' a compra do plano "' + (pkg.name || pkg.label || '#' + id) + '"?',
            async () => {
                const res = await api('/admin-api/package-status', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ packageId: id })
                });
                if (res.res.ok) { fetchData(); }
                else { alert((res.json && res.json.error) || 'Erro ao alterar status do plano.'); }
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
                const res = await api('/admin-api/toggle-leader', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ userId: id })
                });
                if (res.res.ok) { fetchData(); }
                else { alert((res.json && res.json.error) || 'Erro ao alterar líder.'); }
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
        const q = state.searchQuery.toLowerCase().trim();
        if (!q || !state.data) return state.data ? state.data.users : [];
        return state.data.users.filter((u) =>
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
                <td class="p-4 font-medium text-red-600">${esc(u.password) || '-'}</td>
                <td class="p-4 text-gray-700">${esc(u.name) || '-'}</td>
                <td class="p-4 font-bold text-green-600">${money(u.balance)}</td>
                <td class="p-4 text-gray-500 font-mono text-xs">${esc(u.ip) || '-'}</td>
                <td class="p-4 text-gray-500">${fmtDate(u.created_at)}</td>
                <td class="p-4">
                    <div class="flex items-center gap-2">
                        <button onclick="window.__panel.toggleLeader(${u.id})"
                            class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-bold transition ${u.is_leader ? 'bg-amber-100 text-amber-700 hover:bg-amber-200' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'}">
                            ${u.is_leader ? 'Remover Líder' : 'Marcar como Líder'}
                        </button>
                        <a href="/admin-api/impersonate?userId=${u.id}"
                            class="inline-flex items-center gap-1 bg-indigo-600 text-white px-3 py-1.5 rounded-lg text-xs font-bold hover:bg-indigo-700 transition">
                            <i data-lucide="eye" class="w-3 h-3"></i> Entrar na Conta
                        </a>
                    </div>
                </td>
            </tr>`).join('')
            : `<tr><td colspan="8" class="p-6 text-center text-gray-500">${state.searchQuery ? 'Nenhum usuário encontrado para essa busca.' : 'Nenhum usuário cadastrado.'}</td></tr>`;
    }

    function renderWithdrawals() {
        if (!state.data) return;
        const ws = state.data.withdrawals;

        const pending = ws.filter((w) => w.status === 'pending');
        const rejected = ws.filter((w) => w.status === 'rejected');
        const approved = ws.filter((w) => w.status === 'approved');

        $('pending-count').textContent = pending.length;
        $('pending-count').classList.toggle('hidden', pending.length === 0);

        $('withdrawals-tbody').innerHTML = pending.length ? pending.map((w) => `
            <tr class="hover:bg-gray-50 transition">
                <td class="p-4 text-gray-500">#${w.id}</td>
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
    }

    function renderDeposits() {
        if (!state.data) return;
        const ds = state.data.deposits;
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
            : '<tr><td colspan="5" class="p-6 text-center text-gray-500">' + (state.searchQuery ? 'Nenhum usuário encontrado para essa busca.' : 'Nenhum usuário cadastrado.') + '</td></tr>';
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
    }

    let statePlans = { plans: [], loading: false };

    async function fetchPlans() {
        statePlans.loading = true;
        try {
            const res = await api('/admin-api/plans');
            if (res.res.ok && res.json && res.json.success) {
                statePlans.plans = res.json.plans;
            }
        } catch(e) {}
        statePlans.loading = false;
        renderPlanos();
    }

    function renderPlanos() {
        const ps = statePlans.plans || [];
        $('planos-tbody').innerHTML = ps.length ? ps.map((p) => {
            const imgHtml = p.image
                ? '<img src="' + esc(p.image) + '" class="w-10 h-10 rounded-lg object-cover">'
                : '<div class="w-10 h-10 rounded-lg bg-gray-200 flex items-center justify-center text-gray-400 text-xs">sem</div>';
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
                        <button onclick="editPlan(${p.id})" class="p-2 bg-blue-100 text-blue-600 hover:bg-blue-200 rounded-lg transition" title="Editar"><i data-lucide="pencil" class="w-4 h-4"></i></button>
                        <button onclick="deletePlan(${p.id})" class="p-2 bg-red-100 text-red-600 hover:bg-red-200 rounded-lg transition" title="Excluir"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                    </div>
                </td>
            </tr>`;
        }).join('')
        : '<tr><td colspan="6" class="p-6 text-center text-gray-500">Nenhum plano cadastrado.</td></tr>';
        if (typeof lucide !== 'undefined') lucide.createIcons();
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
            $('plan-amount').value = plan.fixed_amount || '';
            $('plan-time').value = plan.repeat_time || '';
            $('plan-daily-return').value = plan.interest || '';
            $('plan-total-return').value = 'R$ ' + ((parseFloat(plan.interest) * parseInt(plan.repeat_time)) || 0).toFixed(2);
            $('plan-time-name').value = plan.time_name || '';
            $('plan-status').value = plan.status ?? 1;
            $('plan-featured').value = plan.featured ?? 0;
            $('plan-capital-back').value = plan.capital_back ?? 0;
            $('plan-lifetime').value = plan.lifetime ?? 0;
            hidden.value = plan.image || '';
            fileInput.value = '';
            if (plan.image) { preview.src = plan.image; preview.classList.remove('hidden'); }
            else { preview.classList.add('hidden'); }
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

    var imgFileEl = document.getElementById('plan-image-file');
    if (imgFileEl) {
        imgFileEl.addEventListener('change', function() {
            const file = this.files[0];
            const preview = document.getElementById('plan-image-preview');
            const hidden = document.getElementById('plan-image');
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    hidden.value = e.target.result;
                    preview.src = e.target.result;
                    preview.classList.remove('hidden');
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
        };
        if (!data.name || !data.time) {
            alert('Preencha nome e prazo.');
            return;
        }
        const url = id ? '/admin-api/plans-update/' + id : '/admin-api/plans';
        const res = await api(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        if (res.res.ok && res.json && res.json.success) {
            alert('Plano salvo com sucesso!');
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
    }

    function deletePlan(id) {
        const plan = statePlans.plans.find((p) => p.id === id);
        const name = plan ? plan.name : '#' + id;
        openConfirm(
            'Excluir Plano',
            'Deseja realmente excluir o plano "' + name + '"?',
            async () => {
                const res = await api('/admin-api/plans-delete/' + id, { method: 'POST' });
                if (res.res.ok && res.json.success) {
                    fetchPlans();
                } else {
                    alert((res.json && res.json.error) || 'Erro ao excluir plano.');
                }
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

        renderUsersAndBalance();
        renderWithdrawals();
        renderDeposits();
        renderPackages();
        fetchPlans();

        if (typeof lucide !== 'undefined') lucide.createIcons();
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
            const groups = res.json.groups || [];
            if (!groups.length) {
                body.innerHTML = '<p class="text-sm text-gray-500">Nenhuma conta com IP compartilhado no momento.</p>';
                return;
            }
            body.innerHTML = groups.map((g) => `
                <div class="border border-gray-200 rounded-xl mb-4 overflow-hidden">
                    <div class="bg-gray-50 px-4 py-3 flex items-center justify-between border-b border-gray-200">
                        <div>
                            <span class="font-bold text-gray-900 font-mono">${esc(g.ip)}</span>
                            <span class="ml-2 text-xs text-gray-500">${g.total} conta(s)</span>
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
        } catch(e) {
            body.innerHTML = '<p class="text-sm text-red-500">Erro de conexão ao carregar alertas.</p>';
        }
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
        openBalance: (id) => {
            const user = state.data.users.find((u) => u.id === id);
            if (!user) return;
            state.balanceModal = user;
            $('balance-modal-info').innerHTML =
                'Usuário: <strong>#' + user.id + '</strong> &mdash; ' + (user.is_leader ? '👑 ' : '') + (user.phone || 'sem telefone') +
                '<br>Saldo atual: <strong>' + money(user.balance) + '</strong>';
            $('balance-amount').value = '';
            $('balance-modal').classList.remove('hidden');
            $('balance-amount').focus();
        },
    };

    document.addEventListener('DOMContentLoaded', function () {
        $('login-form').addEventListener('submit', handleLogin);
        $('btn-logout').addEventListener('click', handleLogout);
        $('search-users').addEventListener('input', (e) => { state.searchQuery = e.target.value; renderUsersAndBalance(); });
        $('search-balance').addEventListener('input', (e) => { state.searchQuery = e.target.value; renderUsersAndBalance(); });
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
                } else if (res.res.status === 403 && (res.res.headers.get('x-vercel-mitigated') || '').includes('challenge')) {
                    location.reload();
                    return;
                } else {
                    state.token = null;
                    localStorage.removeItem('admin_panel_token');
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

        window.editPlan = editPlan;
        window.savePlan = savePlan;
        window.deletePlan = deletePlan;
        window.openPlanModal = openPlanModal;
        window.closePlanModal = closePlanModal;
        window.switchTab = switchTab;
        window.fetchPlans = fetchPlans;
    });
})();
</script>
</body>
</html>
