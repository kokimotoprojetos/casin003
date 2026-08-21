<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RIO PG | Painel Administrativo</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
        .fade-in { animation: fadeIn 0.3s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .modal-overlay { animation: overlayIn 0.2s ease; }
        @keyframes overlayIn { from { opacity: 0; } to { opacity: 1; } }
        .modal-content { animation: modalIn 0.2s ease; }
        @keyframes modalIn { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: #f1f5f9; }
        ::-webkit-scrollbar-thumb { background: #22c55e; border-radius: 20px; }
    </style>
</head>
<body class="bg-gray-50 text-gray-800">

<!-- LOGIN SCREEN -->
<div id="loginScreen" class="min-h-screen bg-gray-900 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl p-8 w-full max-w-md">
        <div class="flex flex-col items-center mb-8">
            <div class="w-16 h-16 bg-green-100 text-green-600 rounded-full flex items-center justify-center mb-4">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
            </div>
            <h1 class="text-2xl font-bold text-gray-900">RIO PG</h1>
            <p class="text-gray-500 text-sm mt-1">Painel Administrativo</p>
        </div>
        <form id="loginForm" class="space-y-4">
            <div>
                <label class="block text-sm font-semibold mb-1">Usuario</label>
                <input type="text" id="loginUser" class="w-full border-2 border-gray-200 rounded-lg px-4 py-3 focus:outline-none focus:border-green-500 transition" required>
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">Senha</label>
                <input type="password" id="loginPass" class="w-full border-2 border-gray-200 rounded-lg px-4 py-3 focus:outline-none focus:border-green-500 transition" required>
            </div>
            <p id="loginError" class="text-red-500 text-sm font-medium hidden"></p>
            <button type="submit" class="w-full bg-green-600 text-white font-bold py-3 rounded-lg hover:bg-green-700 transition">Entrar no Painel</button>
        </form>
    </div>
</div>

<!-- ADMIN PANEL -->
<div id="adminPanel" class="hidden min-h-screen flex">
    <!-- SIDEBAR -->
    <aside id="sidebar" class="fixed inset-y-0 left-0 z-50 w-64 bg-white border-r border-gray-200 transform transition-transform duration-200 -translate-x-full lg:translate-x-0">
        <div class="flex items-center gap-3 px-6 py-4 border-b border-gray-200">
            <div class="w-8 h-8 bg-green-600 text-white rounded-lg flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
            </div>
            <h1 class="text-lg font-bold text-gray-900">RIO PG</h1>
            <button onclick="toggleSidebar()" class="lg:hidden ml-auto"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>
        </div>
        <nav class="p-3 overflow-y-auto h-[calc(100%-65px)]" id="sidebarNav"></nav>
    </aside>

    <!-- MAIN -->
    <div class="flex-1 lg:ml-64">
        <!-- TOPBAR -->
        <nav class="bg-white border-b border-gray-200 px-4 lg:px-6 py-3 flex justify-between items-center sticky top-0 z-40">
            <div class="flex items-center gap-3">
                <button onclick="toggleSidebar()" class="lg:hidden p-2 hover:bg-gray-100 rounded-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                </button>
                <h2 id="pageTitle" class="text-lg font-bold text-gray-900">Dashboard</h2>
            </div>
            <button onclick="doLogout()" class="flex items-center gap-2 text-red-600 font-semibold hover:bg-red-50 px-4 py-2 rounded-lg transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                Sair
            </button>
        </nav>
        <div id="content" class="p-4 lg:p-6"></div>
    </div>
</div>

<!-- MODALS -->
<div id="confirmModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-[100] p-4 modal-overlay">
    <div class="bg-white rounded-2xl shadow-2xl p-6 w-full max-w-sm modal-content">
        <h2 id="confirmTitle" class="text-lg font-bold text-gray-900 mb-2"></h2>
        <p id="confirmMessage" class="text-sm text-gray-600 mb-6"></p>
        <div class="flex gap-3 justify-end">
            <button onclick="closeConfirm()" class="px-4 py-2 text-sm font-bold text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg transition">Cancelar</button>
            <button id="confirmBtn" class="px-4 py-2 text-sm font-bold text-white bg-green-600 hover:bg-green-700 rounded-lg transition">Confirmar</button>
        </div>
    </div>
</div>

<div id="balanceModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4 modal-overlay">
    <div class="bg-white rounded-2xl shadow-2xl p-6 w-full max-w-md modal-content">
        <h2 class="text-lg font-bold text-gray-900 mb-2">Ajustar Saldo</h2>
        <p id="balanceUserInfo" class="text-sm text-gray-500 mb-4"></p>
        <input type="number" id="balanceAmount" placeholder="Valor (R$)" class="w-full border-2 border-gray-200 rounded-xl px-4 py-3 text-lg font-medium focus:outline-none focus:border-green-500 mb-4" min="0" step="0.01">
        <div class="flex gap-2">
            <button onclick="closeBalanceModal()" class="flex-1 border-2 border-gray-200 text-gray-600 py-3 rounded-xl font-bold hover:bg-gray-50 transition">Cancelar</button>
            <button onclick="adjustBalance('remove')" class="flex-1 bg-red-600 text-white py-3 rounded-xl font-bold hover:bg-red-700 transition">Remover</button>
            <button onclick="adjustBalance('add')" class="flex-1 bg-green-600 text-white py-3 rounded-xl font-bold hover:bg-green-700 transition">Adicionar</button>
        </div>
    </div>
</div>

<div id="userDetailModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4 modal-overlay">
    <div class="bg-white rounded-2xl shadow-2xl p-6 w-full max-w-4xl max-h-[90vh] overflow-y-auto modal-content">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-bold text-gray-900">Detalhe do Usuario</h2>
            <button onclick="closeUserDetail()" class="p-2 hover:bg-gray-100 rounded-lg"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>
        </div>
        <div id="userDetailContent"></div>
    </div>
</div>

<script>
const API = '/gozei/api';
let currentTab = 'dashboard';
let appData = { users: [], stats: {} };
let balanceUserId = null;

// AUTH
document.getElementById('loginForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const user = document.getElementById('loginUser').value;
    const pass = document.getElementById('loginPass').value;
    try {
        const r = await fetch(`${API}/login`, {
            method: 'POST', headers: {'Content-Type':'application/json'},
            body: JSON.stringify({username: user, password: pass})
        });
        if (r.ok) { showAdmin(); } 
        else { document.getElementById('loginError').textContent = 'Credenciais invalidas'; document.getElementById('loginError').classList.remove('hidden'); }
    } catch { document.getElementById('loginError').textContent = 'Erro de conexao'; document.getElementById('loginError').classList.remove('hidden'); }
});

async function doLogout() {
    await fetch(`${API}/login`, { method: 'DELETE' });
    document.getElementById('adminPanel').classList.add('hidden');
    document.getElementById('loginScreen').classList.remove('hidden');
}

// NAVIGATION
const sidebarItems = [
    { id: 'dashboard', label: 'Dashboard', icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6', group: 'Relatorios' },
    { id: 'users', label: 'Usuarios', icon: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z', group: 'Usuarios' },
    { id: 'balance', label: 'Ajuste de Saldo', icon: 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z', group: 'Usuarios' },
    { id: 'dep-pending', label: 'Dep. Pendentes', icon: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z', group: 'Financeiro' },
    { id: 'dep-paid', label: 'Dep. Pagos', icon: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z', group: 'Financeiro' },
    { id: 'dep-expired', label: 'Dep. Expirados', icon: 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z', group: 'Financeiro' },
    { id: 'w-pending', label: 'Saques Pendentes', icon: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z', group: 'Financeiro' },
    { id: 'w-approved', label: 'Saques Aprovados', icon: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z', group: 'Financeiro' },
    { id: 'w-refused', label: 'Saques Recusados', icon: 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z', group: 'Financeiro' },
];

function buildSidebar() {
    const groups = [...new Set(sidebarItems.map(i => i.group))];
    let html = '';
    groups.forEach(g => {
        html += `<p class="text-xs font-semibold text-gray-400 uppercase tracking-wider px-3 pt-4 pb-1">${g}</p>`;
        sidebarItems.filter(i => i.group === g).forEach(item => {
            const active = currentTab === item.id;
            html += `<button onclick="switchTab('${item.id}')" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition ${active ? 'bg-green-50 text-green-700' : 'text-gray-600 hover:bg-gray-50'}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="${item.icon}"></path></svg>
                <span class="flex-1 text-left">${item.label}</span>
            </button>`;
        });
    });
    document.getElementById('sidebarNav').innerHTML = html;
}

function switchTab(tab) {
    currentTab = tab;
    buildSidebar();
    toggleSidebar(true);
    renderContent();
}

function toggleSidebar(close) {
    const sb = document.getElementById('sidebar');
    if (close) { sb.classList.remove('show'); sb.classList.add('-translate-x-full'); }
    else { sb.classList.toggle('-translate-x-full'); sb.classList.toggle('show'); }
}

// DATA
async function fetchData() {
    const r = await fetch(`${API}/data`);
    if (r.status === 401) { doLogout(); return; }
    const d = await r.json();
    appData = d;
    renderContent();
}

function fmt(v) { return 'R$ ' + (parseFloat(v) || 0).toFixed(2).replace('.', ','); }
function fmtN(v) { return (parseInt(v) || 0).toLocaleString('pt-BR'); }

// RENDER
function renderContent() {
    const el = document.getElementById('content');
    const titles = {
        'dashboard': 'Dashboard', 'users': 'Usuarios', 'balance': 'Ajuste de Saldo',
        'dep-pending': 'Depositos Pendentes', 'dep-paid': 'Depositos Pagos', 'dep-expired': 'Depositos Expirados',
        'w-pending': 'Saques Pendentes', 'w-approved': 'Saques Aprovados', 'w-refused': 'Saques Recusados',
    };
    document.getElementById('pageTitle').textContent = titles[currentTab] || 'Dashboard';

    if (currentTab === 'dashboard') renderDashboard(el);
    else if (currentTab === 'users') renderUsers(el);
    else if (currentTab === 'balance') renderBalance(el);
    else if (currentTab.startsWith('dep-')) renderDeposits(el);
    else if (currentTab.startsWith('w-')) renderWithdrawals(el);
}

function renderDashboard(el) {
    const s = appData.stats || {};
    el.innerHTML = `
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6 fade-in">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 flex items-center gap-4">
            <div class="p-3 bg-blue-50 text-blue-600 rounded-lg"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path></svg></div>
            <div><p class="text-sm text-gray-500 font-medium">Total Usuarios</p><p class="text-xl font-bold">${fmtN(s.totalUsers)}</p></div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 flex items-center gap-4">
            <div class="p-3 bg-green-50 text-green-600 rounded-lg"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg></div>
            <div><p class="text-sm text-gray-500 font-medium">Hoje</p><p class="text-xl font-bold">${fmtN(s.todayUsers)}</p></div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 flex items-center gap-4">
            <div class="p-3 bg-purple-50 text-purple-600 rounded-lg"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg></div>
            <div><p class="text-sm text-gray-500 font-medium">Saldo Usuarios</p><p class="text-xl font-bold">${fmt(s.totalBalance)}</p></div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 flex items-center gap-4">
            <div class="p-3 bg-emerald-50 text-emerald-600 rounded-lg"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg></div>
            <div><p class="text-sm text-gray-500 font-medium">Depositos Totais</p><p class="text-xl font-bold">${fmt(s.totalDeposits)}</p></div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 flex items-center gap-4">
            <div class="p-3 bg-red-50 text-red-600 rounded-lg"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"></path></svg></div>
            <div><p class="text-sm text-gray-500 font-medium">Saques Aprovados</p><p class="text-xl font-bold">${fmt(s.totalWithdrawalsApproved)}</p></div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 flex items-center gap-4">
            <div class="p-3 bg-orange-50 text-orange-600 rounded-lg"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg></div>
            <div><p class="text-sm text-gray-500 font-medium">Saques Pendentes</p><p class="text-xl font-bold text-orange-600">${fmt(s.pendingWithdrawals)}</p></div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 flex items-center gap-4">
            <div class="p-3 bg-cyan-50 text-cyan-600 rounded-lg"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg></div>
            <div><p class="text-sm text-gray-500 font-medium">Depositos Hoje</p><p class="text-xl font-bold">${fmt(s.todayDeposits)}</p></div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 flex items-center gap-4">
            <div class="p-3 bg-pink-50 text-pink-600 rounded-lg"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg></div>
            <div><p class="text-sm text-gray-500 font-medium">Saques Hoje</p><p class="text-xl font-bold">${fmt(s.todayWithdrawals)}</p></div>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <div><h3 class="font-bold text-gray-900">Ultimos Usuarios</h3><p class="text-sm text-gray-500">${(appData.users||[]).length} registros</p></div>
            <button onclick="fetchData()" class="p-2 hover:bg-gray-100 rounded-lg"><svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg></button>
        </div>
        <div class="overflow-x-auto max-h-96">
            <table class="w-full text-sm"><thead class="bg-gray-50 text-gray-600 sticky top-0"><tr>
                <th class="px-4 py-2 text-left font-semibold">ID</th>
                <th class="px-4 py-2 text-left font-semibold">Telefone</th>
                <th class="px-4 py-2 text-left font-semibold">Nome</th>
                <th class="px-4 py-2 text-left font-semibold">Saldo</th>
            </tr></thead><tbody class="divide-y divide-gray-100">
            ${(appData.users||[]).slice(0,20).map(u => `<tr class="hover:bg-gray-50 cursor-pointer" onclick="viewUser(${u.id})">
                <td class="px-4 py-2 text-gray-500">#${u.id}</td>
                <td class="px-4 py-2 font-medium">${u.phone||'-'}</td>
                <td class="px-4 py-2 text-gray-700">${u.full_name||'-'}</td>
                <td class="px-4 py-2 font-bold text-green-600">${fmt(u.balance)}</td>
            </tr>`).join('')}
            ${(appData.users||[]).length === 0 ? '<tr><td colspan="4" class="px-4 py-4 text-center text-gray-500">Nenhum registro</td></tr>' : ''}
            </tbody></table>
        </div>
    </div>`;
}

function renderUsers(el) {
    el.innerHTML = `
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 fade-in">
        <div class="p-4 border-b border-gray-100">
            <div class="relative max-w-xs">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                <input type="text" id="searchInput" placeholder="Buscar por ID, telefone ou nome..." class="w-full border border-gray-200 rounded-lg pl-9 pr-4 py-2 text-sm focus:outline-none focus:border-green-500" onkeydown="if(event.key==='Enter')searchUsers()">
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm"><thead class="bg-gray-50 text-gray-600 border-b border-gray-200"><tr>
                <th class="p-4 font-semibold">ID</th><th class="p-4 font-semibold">Telefone</th><th class="p-4 font-semibold">Nome</th>
                <th class="p-4 font-semibold">Saldo</th><th class="p-4 font-semibold">Status</th><th class="p-4 font-semibold">Cadastro</th>
                <th class="p-4 font-semibold">Acoes</th>
            </tr></thead><tbody class="divide-y divide-gray-100" id="usersTable"></tbody></table>
        </div>
    </div>`;
    renderUsersTable(appData.users||[]);
}

function renderUsersTable(users) {
    document.getElementById('usersTable').innerHTML = users.map(u => `<tr class="hover:bg-gray-50 transition">
        <td class="p-4 text-gray-500">#${u.id}</td>
        <td class="p-4 font-medium text-gray-900">${u.phone||'-'}</td>
        <td class="p-4 text-gray-700">${u.full_name||'-'}</td>
        <td class="p-4 font-bold text-green-600">${fmt(u.balance)}</td>
        <td class="p-4">${u.status=='1' ? '<span class="bg-green-100 text-green-700 px-2 py-1 rounded-md text-xs font-bold">Ativo</span>' : '<span class="bg-red-100 text-red-700 px-2 py-1 rounded-md text-xs font-bold">Banido</span>'}</td>
        <td class="p-4 text-gray-500">${u.created_at ? new Date(u.created_at).toLocaleDateString() : '-'}</td>
        <td class="p-4"><div class="flex gap-2">
            <button onclick="viewUser(${u.id})" class="inline-flex items-center gap-1 bg-blue-100 text-blue-700 px-3 py-1.5 rounded-lg text-xs font-bold hover:bg-blue-200 transition">Ver</button>
            <button onclick="openBalanceModal(${u.id},'${(u.phone||'').replace(/'/g,"\\'")}','${(u.full_name||'').replace(/'/g,"\\'")}',${u.balance})" class="inline-flex items-center gap-1 bg-green-100 text-green-700 px-3 py-1.5 rounded-lg text-xs font-bold hover:bg-green-200 transition">Saldo</button>
        </div></td>
    </tr>`).join('') || '<tr><td colspan="7" class="p-6 text-center text-gray-500">Nenhum usuario encontrado.</td></tr>';
}

async function searchUsers() {
    const q = document.getElementById('searchInput').value;
    const r = await fetch(`${API}/data?search=${encodeURIComponent(q)}`);
    if (r.ok) { const d = await r.json(); appData.users = d.users; renderUsersTable(d.users); }
}

function renderBalance(el) {
    el.innerHTML = `
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 fade-in">
        <div class="p-4 border-b border-gray-100">
            <div class="relative max-w-xs">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                <input type="text" id="searchInput" placeholder="Buscar por ID, telefone ou nome..." class="w-full border border-gray-200 rounded-lg pl-9 pr-4 py-2 text-sm focus:outline-none focus:border-green-500" onkeydown="if(event.key==='Enter')searchUsers()">
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm"><thead class="bg-gray-50 text-gray-600 border-b border-gray-200"><tr>
                <th class="p-4 font-semibold">ID</th><th class="p-4 font-semibold">Telefone</th><th class="p-4 font-semibold">Nome</th>
                <th class="p-4 font-semibold">Saldo</th><th class="p-4 font-semibold text-right">Acao</th>
            </tr></thead><tbody class="divide-y divide-gray-100" id="usersTable"></tbody></table>
        </div>
    </div>`;
    renderBalanceTable(appData.users||[]);
}

function renderBalanceTable(users) {
    document.getElementById('usersTable').innerHTML = users.map(u => `<tr class="hover:bg-gray-50 transition">
        <td class="p-4 text-gray-500">#${u.id}</td>
        <td class="p-4 font-medium text-gray-900">${u.phone||'-'}</td>
        <td class="p-4 text-gray-700">${u.full_name||'-'}</td>
        <td class="p-4 font-bold text-green-600">${fmt(u.balance)}</td>
        <td class="p-4 text-right"><button onclick="openBalanceModal(${u.id},'${(u.phone||'').replace(/'/g,"\\'")}','${(u.full_name||'').replace(/'/g,"\\'")}',${u.balance})" class="inline-flex items-center gap-1 bg-blue-600 text-white px-3 py-1.5 rounded-lg text-xs font-bold hover:bg-blue-700 transition">Ajustar Saldo</button></td>
    </tr>`).join('');
}

function renderDeposits(el) {
    const statusMap = { 'dep-pending': 'pending', 'dep-paid': 'paid', 'dep-expired': 'expired' };
    const status = statusMap[currentTab];
    fetch(`${API}/deposits?status=${status}`).then(r=>r.json()).then(d => {
        el.innerHTML = `<div class="bg-white rounded-xl shadow-sm border border-gray-100 fade-in"><div class="overflow-x-auto">
            <table class="w-full text-left text-sm"><thead class="bg-gray-50 text-gray-600 border-b border-gray-200"><tr>
                <th class="p-4 font-semibold">ID</th><th class="p-4 font-semibold">Telefone</th><th class="p-4 font-semibold">Valor</th>
                <th class="p-4 font-semibold">Status</th><th class="p-4 font-semibold">Data</th>
                ${currentTab==='dep-pending' ? '<th class="p-4 font-semibold text-right">Acoes</th>' : ''}
            </tr></thead><tbody class="divide-y divide-gray-100">
            ${(d.deposits||[]).map(dp => `<tr class="hover:bg-gray-50 transition">
                <td class="p-4 text-gray-500">#${dp.id}</td>
                <td class="p-4 font-medium">${dp.user_phone||'-'}</td>
                <td class="p-4 font-bold">${fmt(dp.amount)}</td>
                <td class="p-4">${statusBadge(dp.status)}</td>
                <td class="p-4 text-gray-500">${dp.created_at||'-'}</td>
                ${currentTab==='dep-pending' ? `<td class="p-4 text-right"><button onclick="rejectDeposit(${dp.id})" class="p-2 bg-red-100 text-red-600 hover:bg-red-200 rounded-lg transition" title="Rejeitar"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button></td>` : ''}
            </tr>`).join('')}
            ${(d.deposits||[]).length===0 ? '<tr><td colspan="6" class="p-6 text-center text-gray-500">Nenhum registro.</td></tr>' : ''}
            </tbody></table></div></div>`;
    });
}

function renderWithdrawals(el) {
    const statusMap = { 'w-pending': 'pending', 'w-approved': 'approved', 'w-refused': 'refused' };
    const status = statusMap[currentTab];
    fetch(`${API}/withdrawals?status=${status}`).then(r=>r.json()).then(d => {
        el.innerHTML = `<div class="bg-white rounded-xl shadow-sm border border-gray-100 fade-in"><div class="overflow-x-auto">
            <table class="w-full text-left text-sm"><thead class="bg-gray-50 text-gray-600 border-b border-gray-200"><tr>
                <th class="p-4 font-semibold">ID</th><th class="p-4 font-semibold">Usuario</th><th class="p-4 font-semibold">Tipo PIX</th>
                <th class="p-4 font-semibold">Chave PIX</th><th class="p-4 font-semibold">Valor</th>
                <th class="p-4 font-semibold">Status</th><th class="p-4 font-semibold">Data</th>
                ${currentTab==='w-pending' ? '<th class="p-4 font-semibold text-right">Acoes</th>' : ''}
            </tr></thead><tbody class="divide-y divide-gray-100">
            ${(d.withdrawals||[]).map(w => `<tr class="hover:bg-gray-50 transition">
                <td class="p-4 text-gray-500">#${w.id}</td>
                <td class="p-4 font-medium">${w.user_phone||'-'}</td>
                <td class="p-4 text-gray-600 text-xs font-semibold uppercase">${w.tipo_chave_pix||'-'}</td>
                <td class="p-4 text-gray-600 font-mono text-xs">${w.chave_pix||'-'}</td>
                <td class="p-4 font-bold">${fmt(w.valor)}</td>
                <td class="p-4">${wStatusBadge(w.status)}</td>
                <td class="p-4 text-gray-500">${w.data_registro||'-'}</td>
                ${currentTab==='w-pending' ? `<td class="p-4 text-right"><div class="flex justify-end gap-2">
                    <button onclick="actionWithdrawal(${w.id},'approve')" class="p-2 bg-green-100 text-green-600 hover:bg-green-200 rounded-lg transition" title="Aprovar"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg></button>
                    <button onclick="actionWithdrawal(${w.id},'reject')" class="p-2 bg-red-100 text-red-600 hover:bg-red-200 rounded-lg transition" title="Rejeitar"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>
                </div></td>` : ''}
            </tr>`).join('')}
            ${(d.withdrawals||[]).length===0 ? '<tr><td colspan="8" class="p-6 text-center text-gray-500">Nenhum registro.</td></tr>' : ''}
            </tbody></table></div></div>`;
    });
}

function statusBadge(s) {
    if (s==='processamento') return '<span class="bg-orange-100 text-orange-700 px-2 py-1 rounded-md text-xs font-bold">Pendente</span>';
    if (s==='pago') return '<span class="bg-green-100 text-green-700 px-2 py-1 rounded-md text-xs font-bold">Pago</span>';
    return '<span class="bg-red-100 text-red-700 px-2 py-1 rounded-md text-xs font-bold">Expirado</span>';
}

function wStatusBadge(s) {
    if (s==='0') return '<span class="bg-orange-100 text-orange-700 px-2 py-1 rounded-md text-xs font-bold">Pendente</span>';
    if (s==='1') return '<span class="bg-green-100 text-green-700 px-2 py-1 rounded-md text-xs font-bold">Aprovado</span>';
    return '<span class="bg-red-100 text-red-700 px-2 py-1 rounded-md text-xs font-bold">Recusado</span>';
}

// ACTIONS
function openBalanceModal(id, phone, name, balance) {
    balanceUserId = id;
    document.getElementById('balanceUserInfo').innerHTML = `Usuario: <strong>#${id}</strong> — ${phone||'sem telefone'}<br>Saldo atual: <strong>${fmt(balance)}</strong>`;
    document.getElementById('balanceAmount').value = '';
    document.getElementById('balanceModal').classList.remove('hidden');
}

function closeBalanceModal() { document.getElementById('balanceModal').classList.add('hidden'); balanceUserId = null; }

async function adjustBalance(action) {
    const amount = parseFloat(document.getElementById('balanceAmount').value);
    if (!amount || amount <= 0) { alert('Valor invalido'); return; }
    const actionText = action === 'add' ? 'Adicionar' : 'Remover';
    showConfirm(`${actionText} Saldo`, `${actionText} ${fmt(amount)} ${action==='add'?'ao':'do'} usuario #${balanceUserId}?`, async () => {
        const r = await fetch(`${API}/balance`, {
            method: 'POST', headers: {'Content-Type':'application/json'},
            body: JSON.stringify({ userId: balanceUserId, amount, action })
        });
        if (r.ok) { closeBalanceModal(); fetchData(); }
        else { const d = await r.json(); alert(d.error || 'Erro'); }
    });
}

async function actionWithdrawal(id, action) {
    const actionText = action === 'approve' ? 'APROVAR' : 'REJEITAR';
    showConfirm('Confirmar Acao', `Deseja realmente ${actionText} este saque?`, async () => {
        const r = await fetch(`${API}/withdrawals`, {
            method: 'POST', headers: {'Content-Type':'application/json'},
            body: JSON.stringify({ withdrawalId: id, action })
        });
        if (r.ok) fetchData(); else { const d = await r.json(); alert(d.error || 'Erro'); }
    });
}

async function rejectDeposit(id) {
    showConfirm('Rejeitar Deposito', `Marcar deposito #${id} como expirado?`, async () => {
        const r = await fetch(`${API}/reject-deposit`, {
            method: 'POST', headers: {'Content-Type':'application/json'},
            body: JSON.stringify({ transactionId: id })
        });
        if (r.ok) fetchData(); else { const d = await r.json(); alert(d.error || 'Erro'); }
    });
}

async function viewUser(id) {
    const r = await fetch(`${API}/user/${id}`);
    if (!r.ok) { alert('Erro ao carregar'); return; }
    const d = await r.json();
    const u = d.user;
    document.getElementById('userDetailContent').innerHTML = `
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div><p class="text-sm text-gray-500">Telefone</p><p class="font-semibold">${u.phone||'-'}</p></div>
        <div><p class="text-sm text-gray-500">Nome</p><p class="font-semibold">${u.full_name||'-'}</p></div>
        <div><p class="text-sm text-gray-500">Saldo</p><p class="font-bold text-green-600">${fmt(u.balance)}</p></div>
        <div><p class="text-sm text-gray-500">Status</p><p class="font-semibold">${u.status==1?'Ativo':'Banido'}</p></div>
        <div><p class="text-sm text-gray-500">Cadastro</p><p class="font-semibold">${u.created_at ? new Date(u.created_at).toLocaleDateString() : '-'}</p></div>
    </div>
    <h4 class="font-bold mb-3">Transacoes</h4>
    <div class="overflow-x-auto mb-6"><table class="w-full text-sm"><thead class="bg-gray-50 text-gray-600"><tr>
        <th class="px-4 py-2 text-left font-semibold">ID</th><th class="px-4 py-2 text-left font-semibold">Tipo</th>
        <th class="px-4 py-2 text-left font-semibold">Valor</th><th class="px-4 py-2 text-left font-semibold">Status</th>
        <th class="px-4 py-2 text-left font-semibold">Data</th>
    </tr></thead><tbody class="divide-y divide-gray-100">
    ${(d.transactions||[]).slice(0,20).map(t => `<tr class="hover:bg-gray-50">
        <td class="px-4 py-2 text-gray-500">#${t.id}</td><td class="px-4 py-2">${t.type}</td>
        <td class="px-4 py-2 font-bold">${fmt(t.amount)}</td><td class="px-4 py-2">${statusBadge(t.status)}</td>
        <td class="px-4 py-2 text-gray-500">${t.created_at ? new Date(t.created_at).toLocaleDateString() : '-'}</td>
    </tr>`).join('')}
    ${(d.transactions||[]).length===0 ? '<tr><td colspan="5" class="px-4 py-4 text-center text-gray-500">Nenhuma transacao.</td></tr>' : ''}
    </tbody></table></div>`;
    document.getElementById('userDetailModal').classList.remove('hidden');
}

function closeUserDetail() { document.getElementById('userDetailModal').classList.add('hidden'); }

// CONFIRM MODAL
let confirmCallback = null;
function showConfirm(title, message, onConfirm) {
    document.getElementById('confirmTitle').textContent = title;
    document.getElementById('confirmMessage').textContent = message;
    confirmCallback = onConfirm;
    document.getElementById('confirmModal').classList.remove('hidden');
}
function closeConfirm() { document.getElementById('confirmModal').classList.add('hidden'); confirmCallback = null; }
document.getElementById('confirmBtn').addEventListener('click', () => { if (confirmCallback) confirmCallback(); closeConfirm(); });

// INIT
async function showAdmin() {
    document.getElementById('loginScreen').classList.add('hidden');
    document.getElementById('adminPanel').classList.remove('hidden');
    buildSidebar();
    await fetchData();
}

// CHECK SESSION ON LOAD
(async () => {
    try {
        const r = await fetch(`${API}/data`);
        if (r.ok) showAdmin();
    } catch {}
})();
</script>

</body>
</html>
