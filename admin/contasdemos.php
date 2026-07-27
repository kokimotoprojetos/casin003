<?php include 'partials/html.php' ?>

<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
session_start();
include_once "services/database.php";
include_once "services/funcao.php";
include_once 'logs/registrar_logs.php';
include_once "services/crud.php";
include_once "services/crud-adm.php";
include_once "validar_2fa.php";
include_once "services/CSRF_Protect.php";
include_once 'services/checa_login_adm.php';
$csrf = new CSRF_Protect();

checa_login_adm();

// ==================== MaxAPIGames v2: criar conta demo ====================
// Usa o endpoint `user_create` com is_demo=true, que cria o jogador já como demo
// numa única chamada. Se o jogador já existe (DUPLICATED_USER), garante is_demo
// via `set_demo`. Não precisa de game_launch prévio (fluxo legado do iGameWin).
function criarUsuarioDemoMaxAPI($username)
{
    global $mysqli;

    $config_stmt = $mysqli->prepare("SELECT * FROM igamewin WHERE ativo = 1 LIMIT 1");
    $config_stmt->execute();
    $config = $config_stmt->get_result()->fetch_assoc();
    $config_stmt->close();

    if (!$config) {
        throw new Exception("MaxAPIGames não está configurada ou ativa");
    }

    $callMax = function(array $payload) use ($config) {
        $json_data = json_encode($payload);
        error_log("[MAXAPI DEMO] Request: " . $json_data);
        $ch = curl_init($config['url']);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($json_data)
        ]);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        error_log("[MAXAPI DEMO] HTTP $http_code | Resp: " . ($response ?: 'EMPTY') . ($curl_error ? " | cURL: $curl_error" : ""));
        if ($http_code != 200) {
            throw new Exception("HTTP $http_code" . ($curl_error ? " - $curl_error" : ""));
        }
        $decoded = json_decode($response, true);
        if (!is_array($decoded)) {
            throw new Exception("Resposta inválida da MaxAPI");
        }
        return $decoded;
    };

    // 1) Tenta criar já como demo
    $res = $callMax([
        "method"      => "user_create",
        "agent_code"  => $config['agent_code'],
        "agent_token" => $config['agent_token'],
        "user_code"   => $username,
        "is_demo"     => true,
    ]);

    // 2) Se já existe, marca como demo via set_demo (idempotente)
    $msg = $res['msg'] ?? '';
    if (isset($res['status']) && intval($res['status']) === 0 && $msg === 'DUPLICATED_USER') {
        $res = $callMax([
            "method"      => "set_demo",
            "agent_code"  => $config['agent_code'],
            "agent_token" => $config['agent_token'],
            "user_code"   => $username,
        ]);
    }

    if (isset($res['status']) && intval($res['status']) === 1) {
        return ['success' => true, 'data' => $res];
    }
    throw new Exception("MaxAPI: " . ($res['msg'] ?? 'Erro desconhecido') . (isset($res['detail']) ? " — " . $res['detail'] : ''));
}

function criarContasDemo($quantidade, $saldo)
{
    global $mysqli;

    $contas_criadas = [];
    $erros = [];
    $debug_logs = [];

    for ($i = 0; $i < $quantidade; $i++) {
        $debug_log = [];
        $numero_inicial_str = (string)($_POST['numero_inicial'] ?? '11900000000');
        $username = function_exists('bcadd') ? bcadd($numero_inicial_str, (string)$i) : (string)((float)$numero_inicial_str + $i);
        $random_id = substr($username, -9);
        $password = "123456";
        $token = md5(uniqid($username, true));
        $invite_code = (string)$random_id;
        $url = "https://" . $_SERVER['HTTP_HOST'];

        $debug_log[] = "1. Processando conta: $username (id=$random_id)";

        $conta = [
            'id' => $random_id,
            'username' => $username,
            'password' => $password,
            'saldo' => $saldo,
            'token' => $token,
            'maxapi_status' => 'aguardando',
            'debug' => []
        ];

        try {
            // Verifica se já existe localmente (mobile ou id)
            $check = $mysqli->prepare("SELECT id, password, saldo FROM usuarios WHERE id = ? OR mobile = ? LIMIT 1");
            $check->bind_param("is", $random_id, $username);
            $check->execute();
            $existing = $check->get_result()->fetch_assoc();
            $check->close();

            if ($existing) {
                $debug_log[] = "2. ℹ️ Conta já existe localmente (id={$existing['id']}) — atualizando saldo/senha";
                $upd = $mysqli->prepare("UPDATE usuarios SET password=?, senhaparasacar=?, saldo=?, statusaff=1, lobby=1 WHERE id=?");
                $upd->bind_param("ssdi", $password, $password, $saldo, $existing['id']);
                $upd->execute();
                $upd->close();
                $conta['id'] = $existing['id'];
            } else {
                $stmt = $mysqli->prepare("INSERT INTO usuarios
                    (id, mobile, celular, password, saldo, senhaparasacar, url, token, data_registro, invite_code, statusaff, lobby, vip)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, 1, 1, 0)");
                $stmt->bind_param("isssdssss",
                    $random_id, $username, $username, $password, $saldo, $password, $url, $token, $invite_code);
                $stmt->execute();
                $stmt->close();
                $debug_log[] = "2. ✓ Conta criada no banco local";
            }

            // ========== MAXAPI: marcar como DEMO (user_create+is_demo OR set_demo fallback) ==========
            try {
                criarUsuarioDemoMaxAPI($username);
                $conta['maxapi_status'] = 'ativo';
                $debug_log[] = "3. ✓ MaxAPI: conta marcada como demo";
            } catch (Exception $e) {
                $conta['maxapi_status'] = 'erro';
                $debug_log[] = "3. ✗ Erro MaxAPI: " . $e->getMessage();
            }

            $conta['debug'] = $debug_log;
            $contas_criadas[] = $conta;
        } catch (\Throwable $e) {
            $erros[] = "Erro ao processar $username: " . $e->getMessage();
            $debug_log[] = "✗ FATAL: " . $e->getMessage();
        }

        $debug_logs[$username] = $debug_log;
    }

    return [
        'sucesso' => count($contas_criadas),
        'erros' => count($erros),
        'contas' => $contas_criadas,
        'mensagens_erro' => $erros,
        'debug_logs' => $debug_logs
    ];
}

$toastType = null;
$toastMessage = '';
$resultado = null;

// VERIFICA SE HÁ MENSAGEM NA SESSÃO (após redirect)
if (isset($_SESSION['toast_type'])) {
    $toastType = $_SESSION['toast_type'];
    unset($_SESSION['toast_type']);
}

if (isset($_SESSION['toast_message'])) {
    $toastMessage = $_SESSION['toast_message'];
    unset($_SESSION['toast_message']);
}

if (isset($_SESSION['resultado'])) {
    $resultado = $_SESSION['resultado'];
    unset($_SESSION['resultado']);
}

// PROCESSA O POST E REDIRECIONA
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['criar_contas'])) {
    try {
        $quantidade = intval($_POST['quantidade']);
        $saldo = floatval($_POST['saldo']);

        if ($quantidade > 0 && $quantidade <= 100 && $saldo >= 0) {
            $resultado_criacao = criarContasDemo($quantidade, $saldo);

            $_SESSION['resultado'] = $resultado_criacao;

            if ($resultado_criacao['sucesso'] > 0) {
                $_SESSION['toast_type'] = 'success';
                $_SESSION['toast_message'] = "{$resultado_criacao['sucesso']} contas demo criadas e marcadas como demo na MaxAPI!";
            } else {
                $_SESSION['toast_type'] = 'error';
                $_SESSION['toast_message'] = "Erro ao criar contas demo";
            }
        } else {
            $_SESSION['toast_type'] = 'error';
            $_SESSION['toast_message'] = "Dados inválidos. Quantidade deve ser entre 1 e 100.";
        }
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    } catch (\Throwable $e) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=utf-8');
        echo "FATAL: " . $e->getMessage() . "\n";
        echo "Arquivo: " . $e->getFile() . ":" . $e->getLine() . "\n\n";
        echo $e->getTraceAsString();
        exit;
    }
}

$contas_demo_qry = "SELECT * FROM usuarios WHERE mobile LIKE '11%' ORDER BY id DESC LIMIT 50";
$contas_demo_result = mysqli_query($mysqli, $contas_demo_qry);
$contas_demo = [];
while ($row = mysqli_fetch_assoc($contas_demo_result)) {
    $contas_demo[] = $row;
}
?>

<head>
    <?php $title = "Criar Contas Demo em Massa"; ?>
    <?php include 'partials/title-meta.php' ?>
    <?php include 'partials/head-css.php' ?>
    <style>
        .conta-card {
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            background: #191a1a;
        }
        .conta-card:hover {
            background: #191a1a;
        }
        .copy-btn {
            cursor: pointer;
            font-size: 12px;
        }
    </style>
</head>

<body>

    <?php include 'partials/topbar.php' ?>
    <?php include 'partials/startbar.php' ?>

    <div class="page-wrapper">
        <div class="page-content">
            <div class="container-xxl">
                
                <!-- Formulário de Criação -->
                <div class="row justify-content-center mb-4">
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Criar Contas Demo em Massa</h4>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <input type="hidden" name="criar_contas" value="1">
                                    
                                    <div class="row mb-3">
                                        <div class="col-md-4">
                                            <label class="form-label">Quantidade de Contas</label>
                                            <input type="number" name="quantidade" class="form-control" 
                                                min="1" max="100" value="10" required>
                                            <small class="text-muted">Máximo: 100 contas por vez</small>
                                        </div>
                                        
                                        <div class="col-md-4">
                                            <label class="form-label">Número Inicial (Celular)</label>
                                            <input type="number" name="numero_inicial" class="form-control" 
                                                value="11900000000" required>
                                            <small class="text-muted">Ex: 11900000000 (as próximas serão 11900000001, etc)</small>
                                        </div>
                                        
                                        <div class="col-md-4">
                                            <label class="form-label">Saldo Inicial (R$)</label>
                                            <input type="number" name="saldo" class="form-control" 
                                                step="0.01" min="0" value="1000.00" required>
                                            <small class="text-muted">Saldo para cada conta</small>
                                        </div>
                                    </div>
                                    
                                    
                                    <div class="alert alert-warning">
                                        <strong>⏱️ Tempo de Processamento:</strong>
                                        <ul class="mb-0">
                                            <li>Cada conta leva ~1 segundo (uma chamada MaxAPI <code>user_create</code>)</li>
                                            <li>Para 10 contas: ~10 segundos</li>
                                            <li>Para 100 contas: ~1 a 2 minutos</li>
                                        </ul>
                                    </div>

                                    <div class="alert alert-info">
                                        <strong>ℹ️ SISTEMA DE CRIAR DEMOS (MaxAPI):</strong>
                                        <ul class="mb-0">
                                            <li>Todas as contas terão <strong>statusaff = 1</strong> (Afiliado ativo)</li>
                                            <li>Username: <code>[número sequencial]</code></li>
                                            <li>Senha: <code>123456</code></li>
                                            <li>Lobby habilitado automaticamente</li>
                                            <li><strong>✅ MaxAPI:</strong> <code>user_create</code> com <code>is_demo=true</code> — RTP demo configurado no painel do agente</li>
                                        </ul>
                                    </div>
                                    
                                    <div class="text-center">
                                        <button type="submit" class="btn btn-primary btn-lg">
                                            <i class="fas fa-users"></i> Criar Contas Demo
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Resultado da Criação -->
                <?php if ($resultado && $resultado['sucesso'] > 0): ?>
                <div class="row justify-content-center mb-4">
                    <div class="col-lg-10">
                        <div class="card border-success">
                            <div class="card-header bg-success text-white">
                                <h4 class="card-title text-white mb-0">
                                    ✅ Contas Criadas: <?= $resultado['sucesso'] ?>
                                </h4>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <?php foreach ($resultado['contas'] as $conta): ?>
                                    <div class="col-md-6">
                                        <div class="conta-card">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <div>
                                                    <h6 class="mb-1">
                                                        <i class="fas fa-user"></i> <?= $conta['username'] ?>
                                                    </h6>
                                                    <p class="mb-1">
                                                        <strong>Senha:</strong> 
                                                        <code><?= $conta['password'] ?></code>
                                                        <i class="fas fa-copy copy-btn ms-2" 
                                                            onclick="copiarTexto('<?= $conta['password'] ?>')" 
                                                            title="Copiar senha"></i>
                                                    </p>
                                                    <p class="mb-1">
                                                        <strong>Saldo:</strong> R$ <?= number_format($conta['saldo'], 2, ',', '.') ?>
                                                    </p>
                                                    <p class="mb-0">
                                                        <strong>ID:</strong> <?= $conta['id'] ?>
                                                    </p>
                                                </div>
                                                <span class="badge bg-success">DEMO</span>
                                            </div>
                                            
                                            <!-- Status MaxAPI -->
                                            <div class="mb-2">
                                                <small>
                                                    <strong>MaxAPI:</strong>
                                                    <?php if ($conta['maxapi_status'] === 'ativo'): ?>
                                                        <span class="text-success">✅ Demo criada</span>
                                                    <?php else: ?>
                                                        <span class="text-danger">❌ Erro</span>
                                                    <?php endif; ?>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <div class="text-center mt-3">
                                    <button class="btn btn-secondary" onclick="baixarContas()">
                                        <i class="fas fa-download"></i> Baixar Lista de Contas (.txt)
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Tabela de Contas Recentes -->
                <div class="row justify-content-center">
                    <div class="col-lg-10">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Últimas 50 Contas Criadas</h4>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-dark table-striped mb-0">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Username</th>
                                                <th>Senha</th>
                                                <th>Saldo</th>
                                                <th>Data</th>
                                                <th>Status</th>
                                                <th>Ações</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($contas_demo)): ?>
                                                <tr>
                                                    <td colspan="7" class="text-center">Nenhuma conta demo encontrada</td>
                                                </tr>
                                            <?php endif; ?>
                                            <?php foreach ($contas_demo as $conta): ?>
                                            <tr>
                                                <td><?= $conta['id'] ?></td>
                                                <td><?= $conta['mobile'] ?></td>
                                                <td><code><?= $conta['password'] ?></code></td>
                                                <td>R$ <?= number_format($conta['saldo'], 2, ',', '.') ?></td>
                                                <td><?= date('d/m/Y H:i', strtotime($conta['data_registro'])) ?></td>
                                                <td>
                                                    <span class="badge bg-success">Ativa</span>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-danger" onclick="deletarConta(<?= $conta['id'] ?>)">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <?php include 'partials/footer.php' ?>

    <script>
        function copiarTexto(texto) {
            navigator.clipboard.writeText(texto).then(() => {
                alert('Copiado com sucesso!');
            });
        }

        function baixarContas() {
            let texto = "LISTA DE CONTAS DEMO CRIADAS\n";
            texto += "====================================\n\n";
            
            <?php if ($resultado): ?>
                <?php foreach ($resultado['contas'] as $conta): ?>
                    texto += "Usuário: <?= $conta['username'] ?>\n";
                    texto += "Senha: <?= $conta['password'] ?>\n";
                    texto += "Saldo: R$ <?= number_format($conta['saldo'], 2, ',', '.') ?>\n";
                    texto += "------------------------------------\n";
                <?php endforeach; ?>
            <?php endif; ?>

            const blob = new Blob([texto], { type: 'text/plain' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'contas_demo_' + new Date().getTime() + '.txt';
            a.click();
        }

        function deletarConta(id) {
            if (confirm('Deseja realmente deletar esta conta demo?')) {
                fetch('ajax/deletar_conta_demo.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'id=' + id
                }).then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Erro ao deletar conta');
                    }
                });
            }
        }
    </script>

    <?php include 'partials/scripts.php' ?>
</body>
</html>
