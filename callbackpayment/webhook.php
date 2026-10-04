<?php

session_start();
// Caminhos relativos falhavam apos o chdir() do api/index.php (raiz do projeto),
// deixando PHP_SEGURO()/DASH indefinidos e derrubando todo callback desta rota.
include_once __DIR__ . "/../config.php";
if (!defined('DASH')) {
    define('DASH', 'admin');
}
include_once __DIR__ . '/../' . DASH . '/services/database.php';
include_once __DIR__ . '/../' . DASH . '/services/funcao.php';
include_once __DIR__ . '/../' . DASH . '/services/crud.php';
include_once __DIR__ . '/../' . DASH . '/services/afiliacao.php';
global $mysqli;

function busca_valor_ipn($transacao_id){
    global $mysqli;
    $qry = "SELECT usuario, valor FROM transacoes WHERE transacao_id='" . $transacao_id . "'";
    $res = mysqli_query($mysqli, $qry);
    if (mysqli_num_rows($res) > 0) {
        $data = mysqli_fetch_assoc($res);
        $retorna_insert_saldo = adicionarSaldoUsuario($data['usuario'], $data['valor']);
        
        // Processar comissões de afiliação após creditar o saldo
        if ($retorna_insert_saldo) {
            processarTodasComissoes($data['usuario'], $data['valor']);
        }
        
        return $retorna_insert_saldo;
    }
    return false;
}

function att_paymentpix($transacao_id){
    global $mysqli;
    // Guarda atomica: so credita se o pedido ainda nao estava pago. Sem ela,
    // cada repost do webhook creditava de novo. E 'status=1' nao pertencia ao
    // enum('pago','processamento','expirado'), fazendo o UPDATE falhar e o
    // deposito nunca ser creditado.
    $sql = $mysqli->prepare("UPDATE transacoes SET status='pago' WHERE transacao_id=? AND status<>'pago'");
    $sql->bind_param("s", $transacao_id);
    $rf = 0;
    if ($sql->execute() && $sql->affected_rows > 0) {
        $buscar = busca_valor_ipn($transacao_id);
        $rf = $buscar ? 1 : 0;
    }
    $sql->close();
    return $rf;
}

function webhook() {

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo 'erro404';
        //show_404();
    }
    $json_data = file_get_contents('php://input');

    $data = json_decode($json_data, true);

    if (!isset($data['idTransaction']) || !isset($data['typeTransaction']) || !isset($data['statusTransaction'])) {
        echo json_encode(['status' => 'error', 'message' => 'Dados incompletos']);
        return;
    }

    // Mesma protecao do poseidonpay: exige o token por pedido que vai na
    // notification_url (?tk=). Sem ela este endpoint creditava saldo a cada
    // POST, sem nenhuma verificacao de autenticidade.
    $tkReq = trim((string)($_GET['tk'] ?? ($_SERVER['HTTP_X_CALLBACK_TOKEN'] ?? '')));
    $tkOk = false;
    if ($tkReq !== '' && strlen($tkReq) <= 64) {
        $stmtTk = $mysqli->prepare("SELECT callback_token FROM transacoes WHERE transacao_id = ? LIMIT 1");
        if ($stmtTk) {
            $stmtTk->bind_param("s", $data['idTransaction']);
            $stmtTk->execute();
            $resTk = $stmtTk->get_result();
            $rowTk = ($resTk && $resTk->num_rows > 0) ? $resTk->fetch_assoc() : null;
            $stmtTk->close();
            $tkStored = trim((string)($rowTk['callback_token'] ?? ''));
            if ($tkStored !== '' && hash_equals($tkStored, $tkReq)) {
                $tkOk = true;
            }
        }
    }
    if (!$tkOk) {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Token de callback inválido']);
        return;
    }

    // Processa os dados conforme necessário
        $idTransaction = PHP_SEGURO($data['idTransaction']);     		 // id da transação
        $typeTransaction = PHP_SEGURO($data['typeTransaction']); 		// tipo de transação
        $statusTransaction = PHP_SEGURO($data['statusTransaction']);


    if ($statusTransaction === 'PAID_OUT') {

        $att_transacao = att_paymentpix($idTransaction);

        //$retorna_insert_saldo_suit_pay = enviarSaldo($retornaUSER['email'], $data['valor']);

    }

    echo json_encode(['status' => 'success']);
}

// Executar webhook se chamado diretamente
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    webhook();
}

?>