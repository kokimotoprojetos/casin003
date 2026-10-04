<?php
  #======================================#
  ini_set('display_errors', 1);
  error_reporting(E_ALL);
  #======================================#
  session_start();
  include_once('../services/database.php');
  include_once('../services/funcao.php');
  include_once('../services/crud-adm.php');
  include_once('../services/crud.php');
  include_once('../logs/registrar_logs.php');
  include_once('../services/checa_login_adm.php');
  include_once("../services/CSRF_Protect.php");
  $csrf = new CSRF_Protect();
  #======================================#
  #expulsa user
  checa_login_adm();
  #======================================#

if (isset($_POST['att-pay']) && isset($_POST['_csrf']) && isset($_POST['id_pay'])) {
    #----------------------------------------------#
    $id_pay =  PHP_SEGURO($_POST['id_pay']);
    $CSRF =   PHP_SEGURO($_POST['_csrf']);
    $data = date('Y-m-d H:i:s');
    #----------------------------------------------#

    // Verifica se o CSRF está vazio
    if (empty($CSRF)) {
        echo json_encode(['status' => 'error', 'message' => 'Houve um erro ao obter dados. Atualize sua página.']);
        exit;
    }

    // Valor e dono do saque vem SEMPRE do banco. O POST so era display e podia
    // inflar o reembolso (ou estourar outro usuario) a partir do cliente.
    $stmtSel = $mysqli->prepare("SELECT id_user, valor FROM solicitacao_saques WHERE id = ?");
    if (!$stmtSel) {
        echo json_encode(['status' => 'error', 'message' => 'Não foi possível recusar o saque.']);
        exit;
    }
    $stmtSel->bind_param("i", $id_pay);
    $stmtSel->execute();
    $resSel = $stmtSel->get_result();
    $saqueRow = $resSel ? $resSel->fetch_assoc() : null;
    $stmtSel->close();
    if (!$saqueRow) {
        echo json_encode(['status' => 'error', 'message' => 'Saque não encontrado.']);
        exit;
    }

    // Guarda de estado: so recusa um saque que ainda esta pendente (status 0).
    // Sem isso, recusar duas vezes creditava o reembols duas vezes.
    $sql = $mysqli->prepare("UPDATE solicitacao_saques SET data_att=?, status=2 WHERE id=? AND status=0");
    $sql->bind_param("si", $data, $id_pay);

    if ($sql->execute() && $sql->affected_rows > 0) {
        $stmtUser = $mysqli->prepare("SELECT mobile FROM usuarios WHERE id = ?");
        $mobileUser = '';
        if ($stmtUser) {
            $stmtUser->bind_param("i", $saqueRow['id_user']);
            $stmtUser->execute();
            $resUser = $stmtUser->get_result();
            if ($rowUser = $resUser->fetch_assoc()) {
                $mobileUser = $rowUser['mobile'];
            }
            $stmtUser->close();
        }
        if ($mobileUser !== '') {
            enviarSaldo($mobileUser, $saqueRow['valor']);
        } else {
            registrarLog($mysqli, $_SESSION['data_adm']['email'], 'SAQUE RECUSADO SEM REEMBOLSO (usuario nao encontrado) id=' . $id_pay);
        }
        registrarLog($mysqli, $_SESSION['data_adm']['email'], 'Recusou o saque ' . $id_pay);

        echo json_encode(['status' => 'success', 'message' => 'Saque recusado com sucesso!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Saque já processado ou não encontrado.']);
    }

    $mysqli->close();
    exit;
}
?>
