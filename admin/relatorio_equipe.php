<?php
session_start();
include_once "services/database.php";
include_once "services/funcao.php";
include_once "services/checa_login_adm.php";
checa_login_adm();
include_once "validar_2fa.php";

global $mysqli;

$agente_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$agente_data = null;

if ($agente_id > 0) {
    $stmt = $mysqli->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $agente_id);
    $stmt->execute();
    $agente_data = $stmt->get_result()->fetch_assoc();
}

function getEquipe($mysqli, $invite_code, $nivel = 1) {
    if ($nivel > 3) return [];
    
    $equipe = [];
    $stmt = $mysqli->prepare("SELECT id, mobile, invite_code, data_registro, saldo FROM usuarios WHERE invitation_code = ?");
    $stmt->bind_param("s", $invite_code);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        // Buscar total depositado por este usuário
        $stmt_dep = $mysqli->prepare("SELECT SUM(valor) as total FROM transacoes WHERE usuario = ? AND status = 'pago'");
        $stmt_dep->bind_param("i", $row['id']);
        $stmt_dep->execute();
        $row['total_dep'] = $stmt_dep->get_result()->fetch_assoc()['total'] ?? 0;
        
        $row['nivel'] = $nivel;
        $row['sub'] = getEquipe($mysqli, $row['invite_code'], $nivel + 1);
        $equipe[] = $row;
    }
    return $equipe;
}

$equipe_completa = [];
if ($agente_data) {
    $equipe_completa = getEquipe($mysqli, $agente_data['invite_code']);
}

include 'partials/html.php';
?>
<head>
    <?php $title = "Relatório de Equipe - Nível 3"; ?>
    <?php include 'partials/title-meta.php' ?>
    <?php include 'partials/head-css.php' ?>
    <style>
        .tree-level-1 { background: rgba(var(--bs-primary-rgb), 0.05); border-left: 4px solid var(--bs-primary); }
        .tree-level-2 { background: rgba(var(--bs-success-rgb), 0.05); border-left: 4px solid var(--bs-success); margin-left: 20px; }
        .tree-level-3 { background: rgba(var(--bs-info-rgb), 0.05); border-left: 4px solid var(--bs-info); margin-left: 40px; }
        .badge-nivel { font-size: 0.7rem; padding: 2px 6px; }
    </style>
</head>
<body>
    <?php include 'partials/topbar.php' ?>
    <?php include 'partials/startbar.php' ?>

    <div class="page-wrapper">
        <div class="page-content">
            <div class="container-xxl">
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Relatório de Equipe (Até Nível 3)</h4>
                                <p class="text-muted">Selecione um agente para ver toda a sua árvore de convidados.</p>
                            </div>
                            <div class="card-body">
                                <form method="GET" class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label">ID do Agente / Usuário</label>
                                        <div class="input-group">
                                            <input type="number" name="id" class="form-control" placeholder="Digite o ID do Agente" value="<?= $agente_id ?: '' ?>" required>
                                            <button class="btn btn-primary" type="submit">Gerar Relatório</button>
                                        </div>
                                    </div>
                                </form>

                                <?php if ($agente_data): ?>
                                    <div class="alert alert-info mb-4">
                                        <strong>Agente Selecionado:</strong> <?= htmlspecialchars($agente_data['mobile']) ?> (ID: <?= $agente_data['id'] ?>) | 
                                        <strong>Invite Code:</strong> <?= $agente_data['invite_code'] ?>
                                    </div>

                                    <div class="table-responsive">
                                        <table class="table table-bordered align-middle">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Nível</th>
                                                    <th>Usuário (Mobile)</th>
                                                    <th>Data Registro</th>
                                                    <th>Saldo Atual</th>
                                                    <th>Total Depositado</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                function renderEquipe($equipe) {
                                                    foreach ($equipe as $user) {
                                                        $class = "tree-level-" . $user['nivel'];
                                                        $badge_class = $user['nivel'] == 1 ? "bg-primary" : ($user['nivel'] == 2 ? "bg-success" : "bg-info");
                                                        echo "<tr class='{$class}'>";
                                                        echo "<td><span class='badge {$badge_class} badge-nivel'>Nível {$user['nivel']}</span></td>";
                                                        echo "<td><strong>" . htmlspecialchars($user['mobile']) . "</strong> (ID: {$user['id']})</td>";
                                                        echo "<td>" . date('d/m/Y H:i', strtotime($user['data_registro'])) . "</td>";
                                                        echo "<td>R$ " . number_format($user['saldo'], 2, ',', '.') . "</td>";
                                                        echo "<td>R$ " . number_format($user['total_dep'], 2, ',', '.') . "</td>";
                                                        echo "</tr>";
                                                        if (!empty($user['sub'])) {
                                                            renderEquipe($user['sub']);
                                                        }
                                                    }
                                                }

                                                if (empty($equipe_completa)) {
                                                    echo "<tr><td colspan='5' class='text-center'>Nenhum convidado encontrado nesta equipe.</td></tr>";
                                                } else {
                                                    renderEquipe($equipe_completa);
                                                }
                                                ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
