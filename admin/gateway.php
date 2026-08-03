<?php include 'partials/html.php' ?>

<?php
#======================================#
ini_set('display_errors', 0);
error_reporting(E_ALL);
#======================================#
session_start();
include_once "services/database.php";
include_once 'logs/registrar_logs.php';
include_once "services/funcao.php";
include_once "services/crud.php";
include_once "services/crud-adm.php";
include_once 'services/checa_login_adm.php';
include_once "services/CSRF_Protect.php";
include_once "validar_2fa.php";
$csrf = new CSRF_Protect();
#======================================#
checa_login_adm();
#======================================#

if ($_SESSION['data_adm']['status'] != '1') {
    echo "<script>setTimeout(function() { window.location.href = 'bloqueado.php'; }, 0);</script>";
    exit();
}

// Função para validar 2FA do administrador logado
// Aceita texto puro (padrão do sistema) ou hash bcrypt (>= 60 chars) caso o valor já tenha sido hasheado.
function validar_2fa_admin($codigo_2fa)
{
    global $mysqli;
    if (empty($_SESSION['data_adm']['id'])) return false;
    $admin_id = intval($_SESSION['data_adm']['id']);

    $qry = $mysqli->prepare("SELECT `2fa` FROM admin_users WHERE id = ?");
    if (!$qry) return false;
    $qry->bind_param("i", $admin_id);
    $qry->execute();
    $result = $qry->get_result();
    $admin = $result ? $result->fetch_assoc() : null;
    if (!$admin || empty($admin['2fa'])) return false;

    $stored = $admin['2fa'];
    $codigo_2fa = trim($codigo_2fa);
    if (strlen($stored) >= 60) {
        return password_verify($codigo_2fa, $stored);
    }
    return hash_equals($stored, $codigo_2fa);
}

// Garante que apenas gateways legados sem uso fiquem inativos por padrão.
$mysqli->query("UPDATE expfypay SET ativo = 0 WHERE id = 1");
$mysqli->query("UPDATE bspay SET ativo = 0 WHERE id = 1");
$mysqli->query("UPDATE aurenpay SET ativo = 0 WHERE id = 1");
$mysqli->query("UPDATE versell SET ativo = 0 WHERE id = 1");
$mysqli->query("UPDATE inpagamentos SET ativo = 0 WHERE id = 1");

function get_gateways_config()
{
    global $mysqli;

    $GreePayQuery = "SELECT * FROM greepay WHERE id = 1";
    $GreePayResult = mysqli_query($mysqli, $GreePayQuery);
    $GreePayConfig = mysqli_fetch_assoc($GreePayResult);

    $IronQuery = "SELECT * FROM ironpay WHERE id = 1";
    $IronResult = mysqli_query($mysqli, $IronQuery);
    $IronConfig = mysqli_fetch_assoc($IronResult);

    $InvQuery = "SELECT * FROM invictuspay WHERE id = 1";
    $InvResult = mysqli_query($mysqli, $InvQuery);
    $InvConfig = mysqli_fetch_assoc($InvResult);

    $LytQuery = "SELECT * FROM lytronpay WHERE id = 1";
    $LytResult = mysqli_query($mysqli, $LytQuery);
    $LytConfig = mysqli_fetch_assoc($LytResult);

    $PosQuery = "SELECT * FROM poseidonpay WHERE id = 1";
    try {
        $PosResult = mysqli_query($mysqli, $PosQuery);
        $PosConfig = mysqli_fetch_assoc($PosResult);
    } catch (Throwable $e) {
        $PosConfig = null;
    }

    return [
        'greepay' => $GreePayConfig,
        'ironpay' => $IronConfig,
        'invictuspay' => $InvConfig,
        'lytronpay' => $LytConfig,
        'poseidonpay' => $PosConfig
    ];
}

function update_gateway_status($selectedGateway)
{
    global $mysqli;

    // Garante exclusividade: zera outros (defensivo) e ativa o selecionado.
    $mysqli->query("UPDATE greepay SET ativo = 0 WHERE id = 1");
    $mysqli->query("UPDATE expfypay SET ativo = 0 WHERE id = 1");
    $mysqli->query("UPDATE bspay SET ativo = 0 WHERE id = 1");
    $mysqli->query("UPDATE aurenpay SET ativo = 0 WHERE id = 1");
    $mysqli->query("UPDATE versell SET ativo = 0 WHERE id = 1");
    $mysqli->query("UPDATE inpagamentos SET ativo = 0 WHERE id = 1");
    $mysqli->query("UPDATE ironpay SET ativo = 0 WHERE id = 1");
    $mysqli->query("UPDATE invictuspay SET ativo = 0 WHERE id = 1");
    $mysqli->query("UPDATE lytronpay SET ativo = 0 WHERE id = 1");
    try { $mysqli->query("UPDATE poseidonpay SET ativo = 0 WHERE id = 1"); } catch (Throwable $e) {}

    if ($selectedGateway === 'GGPIX') {
        $mysqli->query("UPDATE greepay SET ativo = 1 WHERE id = 1");
    } elseif ($selectedGateway === 'IRONPAY') {
        $mysqli->query("UPDATE ironpay SET ativo = 1 WHERE id = 1");
    } elseif ($selectedGateway === 'INVICTUSPAY') {
        $mysqli->query("UPDATE invictuspay SET ativo = 1 WHERE id = 1");
    } elseif ($selectedGateway === 'LYTRONPAY') {
        $mysqli->query("UPDATE lytronpay SET ativo = 1 WHERE id = 1");
    } elseif ($selectedGateway === 'POSEIDONPAY') {
        try { $mysqli->query("UPDATE poseidonpay SET ativo = 1 WHERE id = 1"); } catch (Throwable $e) {}
    }
}

function update_config($data)
{
    global $mysqli;

    if ($data['gateway'] === 'GGPIX') {
        $qry = $mysqli->prepare("UPDATE greepay SET client_id = ? WHERE id = 1");
        if (!$qry) return false;
        $qry->bind_param("s", $data['client_id']);
        return $qry->execute();
    } elseif ($data['gateway'] === 'IRONPAY') {
        $qry = $mysqli->prepare("UPDATE ironpay SET client_id = ?, url = ? WHERE id = 1");
        if (!$qry) return false;
        $url = !empty($data['url']) ? $data['url'] : 'https://api.ironpayapp.com.br/api/public/v1';
        $qry->bind_param("ss", $data['client_id'], $url);
        return $qry->execute();
    } elseif ($data['gateway'] === 'INVICTUSPAY') {
        $qry = $mysqli->prepare("UPDATE invictuspay SET client_id = ?, url = ? WHERE id = 1");
        if (!$qry) return false;
        $url = !empty($data['url']) ? $data['url'] : 'https://api.invictuspay.app.br/api/public/v1';
        $qry->bind_param("ss", $data['client_id'], $url);
        return $qry->execute();
    } elseif ($data['gateway'] === 'LYTRONPAY') {
        $qry = $mysqli->prepare("UPDATE lytronpay SET client_id = ?, client_secret = ?, url = ? WHERE id = 1");
        if (!$qry) return false;
        $url = !empty($data['url']) ? $data['url'] : 'https://api.lytronpay.com/api/v1';
        $qry->bind_param("sss", $data['client_id'], $data['client_secret'], $url);
        return $qry->execute();
    } elseif ($data['gateway'] === 'POSEIDONPAY') {
        try {
            $qry = $mysqli->prepare("UPDATE poseidonpay SET client_id = ?, client_secret = ? WHERE id = 1");
            if (!$qry) {
                return false;
            }
            $qry->bind_param("ss", $data['client_id'], $data['client_secret']);
            return $qry->execute();
        } catch (Throwable $e) {
            return false;
        }
    }

    return false;
}

function toggle_gateway_status($gateway, $status)
{
    global $mysqli;
    $status = (int)$status;

    if ($status === 1) {
        $mysqli->query("UPDATE greepay SET ativo = 0 WHERE id = 1");
        $mysqli->query("UPDATE ironpay SET ativo = 0 WHERE id = 1");
        $mysqli->query("UPDATE invictuspay SET ativo = 0 WHERE id = 1");
        $mysqli->query("UPDATE lytronpay SET ativo = 0 WHERE id = 1");
        try { $mysqli->query("UPDATE poseidonpay SET ativo = 0 WHERE id = 1"); } catch (Throwable $e) {}
    }

    if ($gateway === 'GGPIX') {
        $stmt = $mysqli->prepare("UPDATE greepay SET ativo = ? WHERE id = 1");
    } elseif ($gateway === 'IRONPAY') {
        $stmt = $mysqli->prepare("UPDATE ironpay SET ativo = ? WHERE id = 1");
    } elseif ($gateway === 'INVICTUSPAY') {
        $stmt = $mysqli->prepare("UPDATE invictuspay SET ativo = ? WHERE id = 1");
    } elseif ($gateway === 'LYTRONPAY') {
        $stmt = $mysqli->prepare("UPDATE lytronpay SET ativo = ? WHERE id = 1");
    } elseif ($gateway === 'POSEIDONPAY') {
        try {
            $stmt = $mysqli->prepare("UPDATE poseidonpay SET ativo = ? WHERE id = 1");
        } catch (Throwable $e) {
            $stmt = false;
        }
    } else {
        return false;
    }

    if ($stmt) {
        $stmt->bind_param("i", $status);
        return $stmt->execute();
    }
    return false;
}

function get_active_gateway($mysqli)
{
    $res = $mysqli->query("SELECT ativo FROM greepay WHERE id = 1");
    if ($res && ($row = $res->fetch_assoc()) && $row['ativo'] == 1) return 'GGPIX';

    $res = $mysqli->query("SELECT ativo FROM ironpay WHERE id = 1");
    if ($res && ($row = $res->fetch_assoc()) && $row['ativo'] == 1) return 'IRONPAY';

    $res = $mysqli->query("SELECT ativo FROM invictuspay WHERE id = 1");
    if ($res && ($row = $res->fetch_assoc()) && $row['ativo'] == 1) return 'INVICTUSPAY';

    $res = $mysqli->query("SELECT ativo FROM lytronpay WHERE id = 1");
    if ($res && ($row = $res->fetch_assoc()) && $row['ativo'] == 1) return 'LYTRONPAY';

    try {
        $res = $mysqli->query("SELECT ativo FROM poseidonpay WHERE id = 1");
        if ($res && ($row = $res->fetch_assoc()) && $row['ativo'] == 1) return 'POSEIDONPAY';
    } catch (Throwable $e) {}

    return 'Nenhum';
}

$toastType = null;
$toastMessage = '';

// Validação de 2FA desativada a pedido do usuário: credenciais sempre desbloqueadas
$credenciais_desbloqueadas = true;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['gateway'])) {
    $data = [
        'gateway' => $_POST['gateway'],
        'client_id' => $_POST['client_id'],
        'client_secret' => $_POST['client_secret'],
        'url' => isset($_POST['url']) ? $_POST['url'] : ''
    ];

    $update_success = update_config($data);

    if ($update_success) {
        $toastType = 'success';
        $toastMessage = admin_t('toast_config_updated');
    } else {
        $toastType = 'error';
        $toastMessage = admin_t('toast_config_error');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_gateway'])) {
    $gateway = $_POST['gateway_name'];
    $new_status = $_POST['new_status'];
    if (toggle_gateway_status($gateway, $new_status)) {
        $toastType = 'success';
        $toastMessage = 'Status do gateway ' . htmlspecialchars($gateway) . ' atualizado!';
    } else {
        $toastType = 'error';
        $toastMessage = admin_t('gateway_toast_status_error');
    }
}

$config = get_gateways_config();
$activeGateway = get_active_gateway($mysqli);
?>

<head>
    <?php $title = admin_t('page_gateway_title');
    include 'partials/title-meta.php' ?>
    <?php include 'partials/head-css.php' ?>
    <style>
        .gateways-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;
        }
        
        .gateway-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            overflow: hidden;
            transition: all 0.3s ease;
            border: 1px solid #eef2f7;
        }
        
        .gateway-card:hover {
            box-shadow: 0 5px 20px rgba(0,0,0,0.12);
            transform: translateY(-2px);
        }
        
        .gateway-header {
            padding: 1.25rem 1.5rem;
            background: #f8fafd;
            border-bottom: 1px solid #eef2f7;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .gateway-title {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .gateway-title i {
            font-size: 1.75rem;
        }
        
        .gateway-name {
            margin: 0;
            font-weight: 600;
            font-size: 1.1rem;
        }
        
        .gateway-description {
            margin: 0;
            font-size: 0.8rem;
            color: #6c757d;
        }
        
        .gateway-status {
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
        }
        
        .gateway-status.active {
            background: #d1fae5;
            color: #065f46;
        }
        
        .gateway-status.inactive {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .gateway-form {
            padding: 1.5rem;
        }
        
        .save-btn {
            width: 100%;
            padding: 0.6rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s;
            margin-top: 1rem;
        }
        
        .save-btn:hover:not(:disabled) {
            opacity: 0.9;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        
        .save-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .credencial-bloqueada {
            background-color: #f3f4f6 !important;
            cursor: not-allowed;
        }
        
        .payment-layout {
            padding: 2rem;
        }
        
        .payment-header {
            margin-bottom: 2rem;
        }
        
        .payment-header h4 {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-weight: 600;
        }
        
        .active-gateway-section {
            background: linear-gradient(135deg, #f6f9fc 0%, #edf2f7 100%);
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 2rem;
            flex-wrap: wrap;
        }
        
        .active-gateway-status {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 0.5rem 1.5rem;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .active-gateway-value {
            font-size: 1.2rem;
        }
    </style>
</head>

<body>

    <?php include 'partials/topbar.php' ?>
    <?php include 'partials/startbar.php' ?>

    <div class="page-wrapper">
        <div class="page-content">
            <div class="container-xxl">
                <div class="row justify-content-center">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h4 class="card-title mb-0"><?= admin_t('gateway_card_title') ?></h4>
                                <?php if (!$credenciais_desbloqueadas): ?>
                                    <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#modal2FAVisualizar">
                                        <i class="ti ti-lock me-2"></i><?= admin_t('gateway_unlock_button') ?>
                                    </button>
                                <?php else: ?>
                                    <span class="badge bg-success" style="font-size: 14px;">
                                        <i class="ti ti-lock-open me-1"></i><?= admin_t('gateway_creds_unlocked_badge') ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <div class="card-body payment-layout">
                                <div class="payment-header">
                                    <h4><i class="ti ti-credit-card"></i><?= admin_t('gateway_card_title') ?></h4>
                                    <p class="mb-0"><?= admin_t('gateway_card_subtitle') ?></p>
                                </div>

                                <div class="active-gateway-section">
                                    <div class="active-gateway-status"><?= admin_t('status_active') ?></div>
                                    <div>
                                        <label class="form-label mb-1"><?= admin_t('gateway_active_label') ?></label>
                                        <div class="active-gateway-value">
                                            <strong><?php echo htmlspecialchars($activeGateway); ?></strong>
                                        </div>
                                    </div>
                                </div>

                                <?php if (!$credenciais_desbloqueadas): ?>
                                    <div class="alert alert-warning" role="alert">
                                        <i class="ti ti-lock me-2"></i>
                                        <strong><?= admin_t('gateway_creds_locked_title') ?></strong> <?= admin_t('gateway_creds_locked_text') ?>
                                    </div>
                                <?php endif; ?>

                                <!-- Grid de Gateways -->
                                <div class="gateways-grid">
                                    
                                    <!-- GGPIX -->
                                    <div class="gateway-card">
                                        <div class="gateway-header">
                                            <div class="gateway-title">
                                                <i class="ti ti-bolt text-warning"></i>
                                                <div>
                                                    <h5 class="gateway-name">GGPIX</h5>
                                                    <p class="gateway-description">Gateway PIX GGPIX</p>
                                                </div>
                                            </div>
                                            <div class="gateway-status <?= ($activeGateway === 'GGPIX') ? 'active' : 'inactive' ?>"><?= ($activeGateway === 'GGPIX') ? admin_t('status_active') : admin_t('status_inactive') ?></div>
                                        </div>

                                        <!-- Controle de Ativação -->
                                        <div class="px-3 pt-3">
                                            <form method="POST" class="d-flex align-items-center">
                                                <?php $csrf->echoInputField(); ?>
                                                <input type="hidden" name="toggle_gateway" value="1">
                                                <input type="hidden" name="gateway_name" value="GGPIX">
                                                <input type="hidden" name="new_status" value="<?= ($config['greepay']['ativo'] == 1) ? '0' : '1' ?>">

                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" role="switch" id="switchGGPIX"
                                                        <?= ($config['greepay']['ativo'] == 1) ? 'checked' : '' ?>
                                                        onchange="this.form.submit()">
                                                    <label class="form-check-label" for="switchGGPIX">
                                                        <?= ($config['greepay']['ativo'] == 1) ? admin_t('status_active') : admin_t('status_inactive') ?>
                                                    </label>
                                                </div>
                                            </form>
                                        </div>

                                        <div class="gateway-form">
                                            <form method="POST" action="" id="formGGPIX">
                                                <?php $csrf->echoInputField(); ?>
                                                <input type="hidden" name="gateway" value="GGPIX">
                                                <div class="mb-3">
                                                    <label class="form-label"><i class="ti ti-key"></i>API Key</label>
                                                    <div class="input-group">
                                                        <input type="password" id="ggpix_api_key" name="client_id" class="form-control <?= !$credenciais_desbloqueadas ? 'credencial-bloqueada' : '' ?>" value="<?= htmlspecialchars($config['greepay']['client_id'] ?? '') ?>" required <?= !$credenciais_desbloqueadas ? 'disabled' : '' ?>>
                                                        <?php if ($credenciais_desbloqueadas): ?>
                                                            <span class="input-group-text" style="cursor: pointer;" onclick="togglePassword('ggpix_api_key', this)"><i class="ti ti-eye"></i></span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>

                                                <button type="button" class="save-btn" onclick="abrirModal2FASalvar('GGPIX')" <?= !$credenciais_desbloqueadas ? 'disabled' : '' ?>>
                                                    <i class="ti ti-device-floppy me-1"></i><?= admin_t('gateway_save_button_prefix') ?> GGPIX
                                                </button>
                                            </form>
                                        </div>
                                    </div>

                                    <!-- IRONPAY -->
                                    <div class="gateway-card">
                                        <div class="gateway-header">
                                            <div class="gateway-title">
                                                <i class="ti ti-shield-check text-info"></i>
                                                <div>
                                                    <h5 class="gateway-name">IRONPAY</h5>
                                                    <p class="gateway-description">Gateway PIX IronPay</p>
                                                </div>
                                            </div>
                                            <div class="gateway-status <?= ($activeGateway === 'IRONPAY') ? 'active' : 'inactive' ?>"><?= ($activeGateway === 'IRONPAY') ? admin_t('status_active') : admin_t('status_inactive') ?></div>
                                        </div>

                                        <!-- Controle de Ativação -->
                                        <div class="px-3 pt-3">
                                            <form method="POST" class="d-flex align-items-center">
                                                <?php $csrf->echoInputField(); ?>
                                                <input type="hidden" name="toggle_gateway" value="1">
                                                <input type="hidden" name="gateway_name" value="IRONPAY">
                                                <input type="hidden" name="new_status" value="<?= (($config['ironpay']['ativo'] ?? 0) == 1) ? '0' : '1' ?>">

                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" role="switch" id="switchIRONPAY"
                                                        <?= (($config['ironpay']['ativo'] ?? 0) == 1) ? 'checked' : '' ?>
                                                        onchange="this.form.submit()">
                                                    <label class="form-check-label" for="switchIRONPAY">
                                                        <?= (($config['ironpay']['ativo'] ?? 0) == 1) ? admin_t('status_active') : admin_t('status_inactive') ?>
                                                    </label>
                                                </div>
                                            </form>
                                        </div>

                                        <div class="gateway-form">
                                            <form method="POST" action="" id="formIRONPAY">
                                                <?php $csrf->echoInputField(); ?>
                                                <input type="hidden" name="gateway" value="IRONPAY">
                                                <div class="mb-3">
                                                    <label class="form-label"><i class="ti ti-key"></i>API Token / Client ID</label>
                                                    <div class="input-group">
                                                        <input type="password" id="ironpay_api_token" name="client_id" class="form-control <?= !$credenciais_desbloqueadas ? 'credencial-bloqueada' : '' ?>" value="<?= htmlspecialchars($config['ironpay']['client_id'] ?? '') ?>" required <?= !$credenciais_desbloqueadas ? 'disabled' : '' ?>>
                                                        <?php if ($credenciais_desbloqueadas): ?>
                                                            <span class="input-group-text" style="cursor: pointer;" onclick="togglePassword('ironpay_api_token', this)"><i class="ti ti-eye"></i></span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label"><i class="ti ti-link"></i>URL Base</label>
                                                    <input type="text" name="url" class="form-control" value="<?= htmlspecialchars($config['ironpay']['url'] ?? 'https://api.ironpayapp.com.br/api/public/v1') ?>" required <?= !$credenciais_desbloqueadas ? 'disabled' : '' ?>>
                                                </div>

                                                <button type="button" class="save-btn" onclick="abrirModal2FASalvar('IRONPAY')" <?= !$credenciais_desbloqueadas ? 'disabled' : '' ?>>
                                                    <i class="ti ti-device-floppy me-1"></i><?= admin_t('gateway_save_button_prefix') ?> IRONPAY
                                                </button>
                                            </form>
                                        </div>
                                    </div>

                                    <!-- INVICTUSPAY -->
                                    <div class="gateway-card">
                                        <div class="gateway-header">
                                            <div class="gateway-title">
                                                <i class="ti ti-trophy text-success"></i>
                                                <div>
                                                    <h5 class="gateway-name">INVICTUSPAY</h5>
                                                    <p class="gateway-description">Gateway PIX InvictusPay</p>
                                                </div>
                                            </div>
                                            <div class="gateway-status <?= ($activeGateway === 'INVICTUSPAY') ? 'active' : 'inactive' ?>"><?= ($activeGateway === 'INVICTUSPAY') ? admin_t('status_active') : admin_t('status_inactive') ?></div>
                                        </div>

                                        <!-- Controle de Ativação -->
                                        <div class="px-3 pt-3">
                                            <form method="POST" class="d-flex align-items-center">
                                                <?php $csrf->echoInputField(); ?>
                                                <input type="hidden" name="toggle_gateway" value="1">
                                                <input type="hidden" name="gateway_name" value="INVICTUSPAY">
                                                <input type="hidden" name="new_status" value="<?= (($config['invictuspay']['ativo'] ?? 0) == 1) ? '0' : '1' ?>">

                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" role="switch" id="switchINVICTUSPAY"
                                                        <?= (($config['invictuspay']['ativo'] ?? 0) == 1) ? 'checked' : '' ?>
                                                        onchange="this.form.submit()">
                                                    <label class="form-check-label" for="switchINVICTUSPAY">
                                                        <?= (($config['invictuspay']['ativo'] ?? 0) == 1) ? admin_t('status_active') : admin_t('status_inactive') ?>
                                                    </label>
                                                </div>
                                            </form>
                                        </div>

                                        <div class="gateway-form">
                                            <form method="POST" action="" id="formINVICTUSPAY">
                                                <?php $csrf->echoInputField(); ?>
                                                <input type="hidden" name="gateway" value="INVICTUSPAY">
                                                <div class="mb-3">
                                                    <label class="form-label"><i class="ti ti-key"></i>API Token / Client ID</label>
                                                    <div class="input-group">
                                                        <input type="password" id="invictuspay_api_token" name="client_id" class="form-control <?= !$credenciais_desbloqueadas ? 'credencial-bloqueada' : '' ?>" value="<?= htmlspecialchars($config['invictuspay']['client_id'] ?? '') ?>" required <?= !$credenciais_desbloqueadas ? 'disabled' : '' ?>>
                                                        <?php if ($credenciais_desbloqueadas): ?>
                                                            <span class="input-group-text" style="cursor: pointer;" onclick="togglePassword('invictuspay_api_token', this)"><i class="ti ti-eye"></i></span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label"><i class="ti ti-link"></i>URL Base</label>
                                                    <input type="text" name="url" class="form-control" value="<?= htmlspecialchars($config['invictuspay']['url'] ?? 'https://api.invictuspay.app.br/api/public/v1') ?>" required <?= !$credenciais_desbloqueadas ? 'disabled' : '' ?>>
                                                </div>

                                                <button type="button" class="save-btn" onclick="abrirModal2FASalvar('INVICTUSPAY')" <?= !$credenciais_desbloqueadas ? 'disabled' : '' ?>>
                                                    <i class="ti ti-device-floppy me-1"></i><?= admin_t('gateway_save_button_prefix') ?> INVICTUSPAY
                                                </button>
                                            </form>
                                        </div>
                                    </div>

                                    <!-- LYTRONPAY -->
                                    <div class="gateway-card">
                                        <div class="gateway-header">
                                            <div class="gateway-title">
                                                <i class="ti ti-wallet text-primary"></i>
                                                <div>
                                                    <h5 class="gateway-name">LYTRONPAY</h5>
                                                    <p class="gateway-description">Gateway PIX LytronPay</p>
                                                </div>
                                            </div>
                                            <div class="gateway-status <?= ($activeGateway === 'LYTRONPAY') ? 'active' : 'inactive' ?>"><?= ($activeGateway === 'LYTRONPAY') ? admin_t('status_active') : admin_t('status_inactive') ?></div>
                                        </div>

                                        <!-- Controle de Ativação -->
                                        <div class="px-3 pt-3">
                                            <form method="POST" class="d-flex align-items-center">
                                                <?php $csrf->echoInputField(); ?>
                                                <input type="hidden" name="toggle_gateway" value="1">
                                                <input type="hidden" name="gateway_name" value="LYTRONPAY">
                                                <input type="hidden" name="new_status" value="<?= (($config['lytronpay']['ativo'] ?? 0) == 1) ? '0' : '1' ?>">

                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" role="switch" id="switchLYTRONPAY"
                                                        <?= (($config['lytronpay']['ativo'] ?? 0) == 1) ? 'checked' : '' ?>
                                                        onchange="this.form.submit()">
                                                    <label class="form-check-label" for="switchLYTRONPAY">
                                                        <?= (($config['lytronpay']['ativo'] ?? 0) == 1) ? admin_t('status_active') : admin_t('status_inactive') ?>
                                                    </label>
                                                </div>
                                            </form>
                                        </div>

                                        <div class="gateway-form">
                                            <form method="POST" action="" id="formLYTRONPAY">
                                                <?php $csrf->echoInputField(); ?>
                                                <input type="hidden" name="gateway" value="LYTRONPAY">
                                                <div class="mb-3">
                                                    <label class="form-label"><i class="ti ti-key"></i>Api-Access-Key</label>
                                                    <div class="input-group">
                                                        <input type="password" id="lytronpay_api_key" name="client_id" class="form-control <?= !$credenciais_desbloqueadas ? 'credencial-bloqueada' : '' ?>" value="<?= htmlspecialchars($config['lytronpay']['client_id'] ?? '') ?>" required <?= !$credenciais_desbloqueadas ? 'disabled' : '' ?>>
                                                        <?php if ($credenciais_desbloqueadas): ?>
                                                            <span class="input-group-text" style="cursor: pointer;" onclick="togglePassword('lytronpay_api_key', this)"><i class="ti ti-eye"></i></span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label"><i class="ti ti-lock"></i>Secret Hash</label>
                                                    <div class="input-group">
                                                        <input type="password" id="lytronpay_secret_hash" name="client_secret" class="form-control <?= !$credenciais_desbloqueadas ? 'credencial-bloqueada' : '' ?>" value="<?= htmlspecialchars($config['lytronpay']['client_secret'] ?? '') ?>" <?= !$credenciais_desbloqueadas ? 'disabled' : '' ?>>
                                                        <?php if ($credenciais_desbloqueadas): ?>
                                                            <span class="input-group-text" style="cursor: pointer;" onclick="togglePassword('lytronpay_secret_hash', this)"><i class="ti ti-eye"></i></span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label"><i class="ti ti-link"></i>URL Base</label>
                                                    <input type="text" name="url" class="form-control" value="<?= htmlspecialchars($config['lytronpay']['url'] ?? 'https://api.lytronpay.com/api/v1') ?>" required <?= !$credenciais_desbloqueadas ? 'disabled' : '' ?>>
                                                </div>

                                                <button type="button" class="save-btn" onclick="abrirModal2FASalvar('LYTRONPAY')" <?= !$credenciais_desbloqueadas ? 'disabled' : '' ?>>
                                                    <i class="ti ti-device-floppy me-1"></i><?= admin_t('gateway_save_button_prefix') ?> LYTRONPAY
                                                </button>
                                            </form>
                                        </div>
                                    </div>

                                    <!-- POSEIDONPAY -->
                                    <div class="gateway-card">
                                        <div class="gateway-header">
                                            <div class="gateway-title">
                                                <i class="ti ti-brand-hipchat text-danger"></i>
                                                <div>
                                                    <h5 class="gateway-name">POSEIDONPAY</h5>
                                                    <p class="gateway-description">Gateway PIX PoseidonPay</p>
                                                </div>
                                            </div>
                                            <div class="gateway-status <?= ($activeGateway === 'POSEIDONPAY') ? 'active' : 'inactive' ?>"><?= ($activeGateway === 'POSEIDONPAY') ? admin_t('status_active') : admin_t('status_inactive') ?></div>
                                        </div>

                                        <!-- Controle de Ativação -->
                                        <div class="px-3 pt-3">
                                            <form method="POST" class="d-flex align-items-center">
                                                <?php $csrf->echoInputField(); ?>
                                                <input type="hidden" name="toggle_gateway" value="1">
                                                <input type="hidden" name="gateway_name" value="POSEIDONPAY">
                                                <input type="hidden" name="new_status" value="<?= (($config['poseidonpay']['ativo'] ?? 0) == 1) ? '0' : '1' ?>">

                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" role="switch" id="switchPOSEIDONPAY"
                                                        <?= (($config['poseidonpay']['ativo'] ?? 0) == 1) ? 'checked' : '' ?>
                                                        onchange="this.form.submit()">
                                                    <label class="form-check-label" for="switchPOSEIDONPAY">
                                                        <?= (($config['poseidonpay']['ativo'] ?? 0) == 1) ? admin_t('status_active') : admin_t('status_inactive') ?>
                                                    </label>
                                                </div>
                                            </form>
                                        </div>

                                        <div class="gateway-form">
                                            <form method="POST" action="" id="formPOSEIDONPAY">
                                                <?php $csrf->echoInputField(); ?>
                                                <input type="hidden" name="gateway" value="POSEIDONPAY">
                                                <div class="mb-3">
                                                    <label class="form-label"><i class="ti ti-key"></i>Public Key (x-public-key)</label>
                                                    <div class="input-group">
                                                        <input type="password" id="poseidonpay_public_key" name="client_id" class="form-control <?= !$credenciais_desbloqueadas ? 'credencial-bloqueada' : '' ?>" value="<?= htmlspecialchars($config['poseidonpay']['client_id'] ?? '') ?>" required <?= !$credenciais_desbloqueadas ? 'disabled' : '' ?>>
                                                        <?php if ($credenciais_desbloqueadas): ?>
                                                            <span class="input-group-text" style="cursor: pointer;" onclick="togglePassword('poseidonpay_public_key', this)"><i class="ti ti-eye"></i></span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label"><i class="ti ti-lock"></i>Secret Key (x-secret-key)</label>
                                                    <div class="input-group">
                                                        <input type="password" id="poseidonpay_secret_key" name="client_secret" class="form-control <?= !$credenciais_desbloqueadas ? 'credencial-bloqueada' : '' ?>" value="<?= htmlspecialchars($config['poseidonpay']['client_secret'] ?? '') ?>" <?= !$credenciais_desbloqueadas ? 'disabled' : '' ?>>
                                                        <?php if ($credenciais_desbloqueadas): ?>
                                                            <span class="input-group-text" style="cursor: pointer;" onclick="togglePassword('poseidonpay_secret_key', this)"><i class="ti ti-eye"></i></span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>

                                                <button type="button" class="save-btn" onclick="abrirModal2FASalvar('POSEIDONPAY')" <?= !$credenciais_desbloqueadas ? 'disabled' : '' ?>>
                                                    <i class="ti ti-device-floppy me-1"></i><?= admin_t('gateway_save_button_prefix') ?> POSEIDONPAY
                                                </button>
                                            </form>
                                        </div>
                                    </div>

                                </div> <!-- Fim do grid de gateways -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <?php include 'partials/endbar.php' ?>
        <?php include 'partials/footer.php' ?>
        
    </div>

    <!-- Modal 2FA para Visualizar Credenciais -->
    <div class="modal fade" id="modal2FAVisualizar" tabindex="-1" aria-labelledby="modal2FAVisualizarLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title" id="modal2FAVisualizarLabel">
                        <i class="ti ti-lock-open me-2"></i><?= admin_t('gateway_modal_unlock_title') ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <?php $csrf->echoInputField(); ?>
                    <div class="modal-body">
                        <div class="alert alert-info" role="alert">
                            <i class="ti ti-info-circle me-2"></i>
                            <?= admin_t('gateway_modal_unlock_info') ?>
                        </div>
                        <div class="mb-3">
                            <label for="codigo_2fa_visualizar" class="form-label"><?= admin_t('twofa_code_label') ?></label>
                            <input type="text" name="codigo_2fa_visualizar" class="form-control" placeholder="<?= admin_t('twofa_placeholder') ?>" required autofocus>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= admin_t('button_cancel') ?></button>
                        <button type="submit" name="validar_2fa_visualizar" class="btn btn-warning">
                            <i class="ti ti-lock-open me-1"></i><?= admin_t('gateway_modal_unlock_button') ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal 2FA para Salvar Alterações -->
    <div class="modal fade" id="modal2FASalvar" tabindex="-1" aria-labelledby="modal2FASalvarLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modal2FASalvarLabel">
                        <i class="ti ti-shield-lock me-2"></i><?= admin_t('gateway_modal_confirm_title') ?>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning" role="alert">
                        <i class="ti ti-alert-triangle me-2"></i>
                        <strong><?= admin_t('alert_attention') ?></strong> <?= admin_t('gateway_modal_confirm_warning') ?> <strong id="gatewayNome"></strong>.
                    </div>
                    <div class="mb-3">
                        <label for="codigo_2fa_salvar" class="form-label"><?= admin_t('twofa_code_label') ?></label>
                        <input type="text" id="codigo_2fa_salvar" class="form-control" placeholder="<?= admin_t('twofa_placeholder') ?>" required autofocus>
                        <small class="text-muted"><?= admin_t('gateway_modal_confirm_helper') ?></small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= admin_t('button_cancel') ?></button>
                    <button type="button" class="btn btn-primary" onclick="confirmarSalvar()">
                        <i class="ti ti-check me-1"></i><?= admin_t('gateway_modal_confirm_button') ?>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div id="toastPlacement" class="toast-container position-fixed bottom-0 end-0 p-3"></div>

    <?php include 'partials/vendorjs.php' ?>
    <script src="assets/js/app.js"></script>

    <script>
        function abrirModal2FASalvar(gateway) {
            let form;
            if (gateway === 'GGPIX') {
                form = document.getElementById('formGGPIX');
            } else if (gateway === 'IRONPAY') {
                form = document.getElementById('formIRONPAY');
            } else if (gateway === 'INVICTUSPAY') {
                form = document.getElementById('formINVICTUSPAY');
            } else if (gateway === 'LYTRONPAY') {
                form = document.getElementById('formLYTRONPAY');
            } else if (gateway === 'POSEIDONPAY') {
                form = document.getElementById('formPOSEIDONPAY');
            }

            if (form) {
                form.submit();
            } else {
                showToast('error', '<?= admin_t('gateway_toast_form_not_found') ?>');
            }
        }

        function showToast(type, message) {
            var toastPlacement = document.getElementById('toastPlacement');
            var toast = document.createElement('div');
            toast.className = `toast align-items-center bg-light border-0 fade show`;
            toast.setAttribute('role', 'alert');
            toast.setAttribute('aria-live', 'assertive');
            toast.setAttribute('aria-atomic', 'true');
            toast.innerHTML = `
                <div class="toast-header">
                    <h5 class="me-auto my-0"><?= admin_t('gateway_toast_title') ?></h5>
                    <small><?= admin_t('gateway_toast_now') ?></small>
                    <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="toast-body">${message}</div>
            `;
            toastPlacement.appendChild(toast);

            var bootstrapToast = new bootstrap.Toast(toast);
            bootstrapToast.show();

            setTimeout(function () {
                bootstrapToast.hide();
                setTimeout(() => toast.remove(), 500);
            }, 3000);
        }

        function togglePassword(inputId, icon) {
            const input = document.getElementById(inputId);
            const iconElement = icon.querySelector('i');

            if (input.type === "password") {
                input.type = "text";
                iconElement.classList.remove('ti-eye');
                iconElement.classList.add('ti-eye-off');
            } else {
                input.type = "password";
                iconElement.classList.remove('ti-eye-off');
                iconElement.classList.add('ti-eye');
            }
        }
    </script>

    <?php if ($toastType && $toastMessage): ?>
        <script>
            showToast('<?= $toastType ?>', '<?= $toastMessage ?>');
        </script>
    <?php endif; ?>

</body>

</html>