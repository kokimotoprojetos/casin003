<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

include_once __DIR__ . "/../config.php";
if (!defined('DASH')) {
    define('DASH', 'admin');
}
include_once __DIR__ . "/../" . DASH . "/services-prod/prod.php";
include_once __DIR__ . "/../" . DASH . "/services/database.php";
include_once __DIR__ . "/../" . DASH . "/services/funcao.php";
include_once __DIR__ . "/../" . DASH . "/services/crud.php";

const LOG_FILE = __DIR__ . '/webhook_log.txt';

function writeLog($message) {
    $timestamp = date('Y-m-d H:i:s');
    
    // Se for array ou objeto, converte para string
    if (is_array($message) || is_object($message)) {
        $message = print_r($message, true);
    }
    
    $logEntry = "[$timestamp] $message" . PHP_EOL;
    file_put_contents(LOG_FILE, $logEntry, FILE_APPEND);
}

// Log inicial
writeLog("========== NOVA REQUISIÇÃO RECEBIDA ==========");
writeLog("REQUEST METHOD: " . $_SERVER['REQUEST_METHOD']);
writeLog("REQUEST URI: " . $_SERVER['REQUEST_URI']);
writeLog("REMOTE ADDR: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
writeLog("USER AGENT: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown'));

// Headers da requisição
$headers = getallheaders();
writeLog("HEADERS: " . json_encode($headers));

// Raw input
$rawInput = file_get_contents('php://input');
writeLog("RAW INPUT: " . $rawInput);

// Dados GET
writeLog("GET DATA: " . json_encode($_GET));

// Dados POST (se houver)
writeLog("POST DATA: " . json_encode($_POST));

// Decodifica JSON
$requestData = json_decode($rawInput, true);
if (!$requestData && !empty($rawInput)) {
    writeLog("FALHA AO DECODIFICAR JSON: " . json_last_error_msg());
    // Tenta decodificar como array associativo mesmo com erro
    $requestData = json_decode($rawInput, true, 512, JSON_INVALID_UTF8_IGNORE);
    writeLog("TENTATIVA DE RECUPERAÇÃO: " . json_encode($requestData));
}

// Se ainda não temos dados, tenta usar POST
if (!$requestData) {
    $requestData = $_POST;
    writeLog("USANDO \$_POST COMO FALLBACK: " . json_encode($requestData));
}

// Se ainda não temos dados, cria array vazio
if (!$requestData) {
    $requestData = [];
    writeLog("NENHUM DADO RECEBIDO, USANDO ARRAY VAZIO");
}

// Log dos dados processados
writeLog("DADOS PROCESSADOS: " . json_encode($requestData));

// Log de todas as variáveis de ambiente/request
writeLog("TODAS AS VARIÁVEIS DE REQUEST:");
writeLog("_GET: " . json_encode($_GET));
writeLog("_POST: " . json_encode($_POST));
writeLog("_REQUEST: " . json_encode($_REQUEST));
writeLog("_FILES: " . json_encode($_FILES));
writeLog("_SERVER[REQUEST_TIME]: " . ($_SERVER['REQUEST_TIME'] ?? ''));
writeLog("_SERVER[QUERY_STRING]: " . ($_SERVER['QUERY_STRING'] ?? ''));

// Verifica se é um ping de teste
if (isset($requestData['ping']) || isset($_GET['ping']) || isset($_POST['ping'])) {
    writeLog("PING RECEBIDO - RESPONDENDO PONG");
    echo json_encode(['status' => 1, 'msg' => 'pong', 'timestamp' => time()]);
    writeLog("========== FIM DA REQUISIÇÃO (PING) ==========");
    exit;
}

// Log das credenciais recebidas (se houver)
$agentCode = $requestData['agent_code'] ?? $_GET['agent_code'] ?? $_POST['agent_code'] ?? '';
$agentToken = $requestData['agent_token'] ?? $requestData['agent_secret'] ?? $_GET['agent_token'] ?? $_POST['agent_token'] ?? '';

writeLog("AGENT_CODE RECEBIDO: " . $agentCode);
writeLog("AGENT_TOKEN RECEBIDO: " . $agentToken);

// Method recebido
$method = $requestData['method'] ?? $_GET['method'] ?? $_POST['method'] ?? '';
writeLog("METHOD RECEBIDO: " . $method);

// Log completo dos dados para debug
writeLog("DADOS COMPLETOS DA REQUISIÇÃO:");
foreach ($requestData as $key => $value) {
    if (is_array($value)) {
        writeLog("  $key: " . json_encode($value));
    } else {
        writeLog("  $key: " . $value);
    }
}

// Autenticação real: agent_code + agent_secret (MaxAPIGames v2) validados contra tabela igamewin
function authenticateAgent($agentCode, $agentToken) {
    global $data_igamewin;

    $expectedCode = $data_igamewin['agent_code'] ?? '';
    $expectedSecret = $data_igamewin['agent_secret'] ?? '';

    if ($expectedCode === '' || $expectedSecret === '') {
        writeLog("AUTH FALHOU: credenciais MaxAPIGames não configuradas no banco (agent_code/agent_secret).");
        return false;
    }

    $codeOk = hash_equals($expectedCode, (string)$agentCode);
    $secretOk = hash_equals($expectedSecret, (string)$agentToken);

    if ($codeOk && $secretOk) {
        writeLog("AUTH OK: agent_code/agent_secret válidos.");
        return true;
    }

    writeLog("AUTH FALHOU: agent_code ou agent_secret divergente. Recebido agent_code=$agentCode");
    return false;
}

function sendErrorResponse($httpCode, $status, $message, $extraData = []) {
    http_response_code($httpCode);
    $response = array_merge(['status' => $status, 'msg' => $message], $extraData);
    writeLog("ENVIANDO ERRO: " . json_encode($response));
    echo json_encode($response);
    exit;
}

function sendSuccessResponse($data) {
    writeLog("ENVIANDO SUCESSO: " . json_encode($data));
    echo json_encode($data);
    exit;
}

function handleUserBalance($request) {
    global $mysqli;
    
    writeLog("INICIANDO handleUserBalance");
    writeLog("REQUEST RECEBIDA: " . json_encode($request));
    
    $userCode = $request['user_code'] ?? '';
    writeLog("USER_CODE: $userCode");
    
    if (empty($userCode)) {
        return ['status' => 0, 'msg' => 'USER_CODE_REQUIRED'];
    }
    
    $query = "SELECT saldo FROM usuarios WHERE mobile = ?";
    $stmt = $mysqli->prepare($query);
    
    if (!$stmt) {
        $error = $mysqli->error;
        writeLog("ERRO SQL prepare: $error");
        return ['status' => 0, 'msg' => 'ERROR_PREPARING_QUERY', 'sql_error' => $error];
    }
    
    $stmt->bind_param("s", $userCode);
    
    if (!$stmt->execute()) {
        $sqlError = $stmt->error;
        writeLog("ERRO NA EXECUÇÃO DO SELECT: $sqlError");
        return ['status' => 0, 'msg' => 'ERROR_QUERY', 'error' => $sqlError];
    }
    
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        writeLog("USUÁRIO NÃO ENCONTRADO: $userCode");
        return ['status' => 0, 'msg' => 'INVALID_USER', 'user_code' => $userCode];
    }
    
    $row = $result->fetch_assoc();
    $balance = $row['saldo'];
    
    writeLog("SALDO ENCONTRADO: $balance");
    $stmt->close();
    
    return ['status' => 1, 'user_balance' => floatval($balance)];
}

function handleTransaction($request) {
    global $mysqli;
    
    writeLog("INICIANDO handleTransaction");
    writeLog("REQUEST RECEBIDA: " . json_encode($request));
    
    $userCode = $request['user_code'] ?? '';
    $gameType = $request['game_type'] ?? '';
    $slotData = $request['slot'] ?? [];
    $providerCode = $slotData['provider_code'] ?? '';
    $gameCode = $slotData['game_code'] ?? '';
    $betMoney = $slotData['bet_money'] ?? 0;
    $winMoney = $slotData['win_money'] ?? 0;
    $txnId = $slotData['txn_id'] ?? '';
    $txnType = $slotData['txn_type'] ?? '';
    
    writeLog("DADOS EXTRAÍDOS:");
    writeLog("user_code=$userCode");
    writeLog("game_type=$gameType");
    writeLog("provider_code=$providerCode");
    writeLog("game_code=$gameCode");
    writeLog("bet_money=$betMoney");
    writeLog("win_money=$winMoney");
    writeLog("txn_id=$txnId");
    writeLog("txn_type=$txnType");
    
    if (empty($userCode) || empty($txnId)) {
        return ['status' => 0, 'msg' => 'REQUIRED_FIELDS_MISSING'];
    }
    
    $userData = getUserData($userCode);
    if (!$userData) {
        writeLog("USUÁRIO INVÁLIDO: $userCode");
        return ['status' => 0, 'msg' => 'INVALID_USER', 'user_code' => $userCode];
    }
    
    $userId = $userData['id'];
    $currentBalance = $userData['saldo'];
    $newBalance = $currentBalance - $betMoney + $winMoney;
    
    writeLog("ID_USER: $userId");
    writeLog("SALDO ATUAL: $currentBalance");
    writeLog("NOVO SALDO: $newBalance");
    
    if ($betMoney > $currentBalance) {
        writeLog("SALDO INSUFICIENTE: bet=$betMoney, saldo=$currentBalance");
        return ['status' => 0, 'msg' => 'INSUFFICIENT_BALANCE'];
    }
    
    $mysqli->autocommit(false);
    
    try {
        if (!insertGameHistory($userId, $gameCode, $betMoney, $winMoney, $txnId)) {
            throw new Exception("Erro ao inserir histórico");
        }
        
        if (!updateUserBalance($userId, $newBalance)) {
            throw new Exception("Erro ao atualizar saldo");
        }
        
        $mysqli->commit();
        writeLog("TRANSAÇÃO CONCLUÍDA COM SUCESSO");
        
        return ['status' => 1, 'user_balance' => floatval($newBalance)];
        
    } catch (Exception $e) {
        $mysqli->rollback();
        writeLog("ERRO NA TRANSAÇÃO: " . $e->getMessage());
        return ['status' => 0, 'msg' => 'TRANSACTION_FAILED'];
    } finally {
        $mysqli->autocommit(true);
    }
}

function getUserData($userCode) {
    global $mysqli;
    
    $query = "SELECT id, saldo FROM usuarios WHERE mobile = ?";
    $stmt = $mysqli->prepare($query);
    
    if (!$stmt) {
        writeLog("ERRO SQL prepare (getUserData): " . $mysqli->error);
        return false;
    }
    
    $stmt->bind_param("s", $userCode);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        return false;
    }
    
    $userData = $result->fetch_assoc();
    $stmt->close();
    
    return $userData;
}

function insertGameHistory($userId, $gameCode, $betMoney, $winMoney, $txnId) {
    global $mysqli;
    
    $query = "INSERT INTO historico_play (id_user, nome_game, bet_money, win_money, txn_id, created_at, status_play) 
              VALUES (?, ?, ?, ?, ?, NOW(), 1)";
    
    $stmt = $mysqli->prepare($query);
    if (!$stmt) {
        writeLog("ERRO SQL prepare (insertGameHistory): " . $mysqli->error);
        return false;
    }
    
    $stmt->bind_param("isdds", $userId, $gameCode, $betMoney, $winMoney, $txnId);
    $success = $stmt->execute();
    
    if (!$success) {
        writeLog("ERRO ao inserir histórico: " . $stmt->error);
    } else {
        writeLog("HISTÓRICO INSERIDO COM SUCESSO");
    }
    
    $stmt->close();
    return $success;
}

function updateUserBalance($userId, $newBalance) {
    global $mysqli;
    
    $query = "UPDATE usuarios SET saldo = ? WHERE id = ?";
    $stmt = $mysqli->prepare($query);
    
    if (!$stmt) {
        writeLog("ERRO SQL prepare (updateUserBalance): " . $mysqli->error);
        return false;
    }
    
    $stmt->bind_param("di", $newBalance, $userId);
    $success = $stmt->execute();
    
    if (!$success) {
        writeLog("ERRO ao atualizar saldo: " . $stmt->error);
    } else {
        writeLog("SALDO ATUALIZADO COM SUCESSO");
    }
    
    $stmt->close();
    return $success;
}

if (!authenticateAgent($agentCode, $agentToken)) {
    sendErrorResponse(401, 0, 'INVALID_AGENT', ['agent_code' => $agentCode]);
}

if (empty($method)) {
    writeLog("METHOD NÃO ESPECIFICADO");
    // Tenta encontrar o method em diferentes lugares
    if (isset($requestData['acao'])) {
        $method = $requestData['acao'];
        writeLog("USANDO 'acao' COMO METHOD: $method");
    } elseif (isset($_GET['acao'])) {
        $method = $_GET['acao'];
        writeLog("USANDO GET[acao] COMO METHOD: $method");
    } elseif (isset($_POST['acao'])) {
        $method = $_POST['acao'];
        writeLog("USANDO POST[acao] COMO METHOD: $method");
    } else {
        writeLog("NENHUM METHOD ENCONTRADO - RESPONDENDO COM LISTA DE MÉTODOS DISPONÍVEIS");
        sendErrorResponse(400, 0, 'Method not specified', [
            'available_methods' => ['user_balance', 'transaction'],
            'received_data' => $requestData
        ]);
    }
}

writeLog("PROCESSANDO MÉTODO: $method");

// Resposta baseada no método
switch ($method) {
    case 'user_balance':
        $response = handleUserBalance($requestData);
        writeLog("RESPOSTA USER_BALANCE: " . json_encode($response));
        sendSuccessResponse($response);
        break;
        
    case 'transaction':
        $response = handleTransaction($requestData);
        writeLog("RESPOSTA TRANSACTION: " . json_encode($response));
        sendSuccessResponse($response);
        break;
        
    default:
        writeLog("MÉTODO NÃO SUPORTADO: $method");
        sendErrorResponse(400, 0, 'Method not supported', [
            'received_method' => $method,
            'available_methods' => ['user_balance', 'transaction']
        ]);
}

// Log final
writeLog("========== FIM DA REQUISIÇÃO ==========");
?>