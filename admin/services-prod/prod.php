<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);
define('PROD_LOG_FILE', sys_get_temp_dir() . '/errorlog.log');

function prodLog($msg) {
    $date = date('Y-m-d H:i:s');
    @file_put_contents(PROD_LOG_FILE, "[$date] [POSEIDONPAY] $msg" . PHP_EOL, FILE_APPEND);
    error_log("[POSEIDONPAY] $msg");
}

function generateQRCodeSvg($code) {
    $dir = dirname(__DIR__, 2) . '/api/v1/phpqrcode.php';
    if (!file_exists($dir)) {
        return '';
    }
    require_once $dir;
    ob_start();
    QRcode::svg($code, false, QR_ECLEVEL_L, 3, 4);
    $svg = ob_get_clean();
    if (!empty($svg)) {
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
    return '';
}

function generateQRCodePoseidonPay($code) {
    return generateQRCodeSvg($code);
}

function next_sistemas_qrcode($valor, $nome, $id, $comissao = null, $afiliado_id = null, $payTypeSubListId = null, $joinBonus = true)
{
    global $mysqli, $data_ironpay;
    $GLOBALS['LAST_GATEWAY_ERROR'] = "";

    $ironpay_ativo = isset($data_ironpay['ativo']) && (int)$data_ironpay['ativo'] === 1;

    if ($ironpay_ativo) {
        $res = criarQrIronPay($valor, $nome, $id, $comissao, $afiliado_id, $payTypeSubListId, $joinBonus);
        if (!empty($res) && isset($res['transacao_id'])) {
            prodLog("Sucesso no gateway: IRONPAY");
            return $res;
        }
        prodLog("IRONPAY falhou, tentando POSEIDONPAY");
    }

    $res = criarQrPoseidonPay($valor, $nome, $id, $comissao, $afiliado_id, $payTypeSubListId, $joinBonus);
    if (!empty($res) && isset($res['transacao_id'])) {
        prodLog("Sucesso no gateway: POSEIDONPAY");
        return $res;
    }

    prodLog("Nenhum gateway ativo obteve sucesso.");
    return null;
}

function insert_payment($insert)
{
    global $mysqli;
    
    prodLog("insert_payment: Iniciando inserção.");
    
    try {
        $res = $mysqli->query("SELECT MAX(id) as maxid FROM transacoes");
        $row = $res ? $res->fetch_assoc() : null;
        $next_id = ($row && isset($row['maxid'])) ? intval($row['maxid']) + 1 : 1;
        
        $columns = "id,transacao_id,usuario,valor,tipo,data_registro,qrcode,code,status";
        $placeholders = "?,?,?,?,?,?,?,?,?";
        $types = "issssssss";
        $values = [
            $next_id,
            $insert['transacao_id'], 
            $insert['usuario'], 
            $insert['valor'], 
            $insert['tipo'], 
            $insert['data_registro'], 
            $insert['qrcode'], 
            $insert['code'], 
            $insert['status']
        ];
        
        if (isset($insert['comissao']) && isset($insert['afiliado_id'])) {
            $columns .= ",comissao,afiliado_id";
            $placeholders .= ",?,?";
            $types .= "ss";
            $values[] = $insert['comissao'];
            $values[] = $insert['afiliado_id'];
        }
    
        if (isset($insert['pay_type_sub_list_id']) && !empty($insert['pay_type_sub_list_id'])) {
            $columns .= ",pay_type_sub_list_id";
            $placeholders .= ",?";
            $types .= "i";
            $values[] = $insert['pay_type_sub_list_id'];
        }
    
        if (isset($insert['join_bonus'])) {
            $columns .= ",join_bonus";
            $placeholders .= ",?";
            $types .= "i";
            $values[] = $insert['join_bonus'];
        }
        
        $sql = "INSERT INTO transacoes ($columns) VALUES ($placeholders)";
        $stmt = $mysqli->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param($types, ...$values);
            if ($stmt->execute()) {
                $stmt->close();
                prodLog("insert_payment: Sucesso.");
                return 1;
            } else {
                prodLog("insert_payment: ERRO Execute: " . $stmt->error);
                $stmt->close();
                return 0;
            }
        } else {
            prodLog("insert_payment: ERRO Prepare: " . $mysqli->error);
            return 0;
        }
    } catch (Exception $e) {
        prodLog("insert_payment EXCEPTION: " . $e->getMessage());
        return 0;
    }
}

function criarQrPoseidonPay($valor, $nome, $id, $comissao = null, $afiliado_id = null, $payTypeSubListId = null, $joinBonus = true)
{
    global $data_poseidonpay, $url_base, $mysqli;
    
    prodLog("criarQrPoseidonPay: Entrada - Valor: $valor, Nome: $nome, ID: $id");
    
    $public_key = $data_poseidonpay['client_id'] ?? '';
    $secret_key = $data_poseidonpay['client_secret'] ?? '';
    
    if (empty($public_key) || empty($secret_key)) {
        prodLog("[POSEIDONPAY] Credenciais não configuradas.");
        return [];
    }
    
    $user_data = null;
    $stmt = $mysqli->prepare("SELECT real_name, cpf, email, mobile FROM usuarios WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        $user_data = $res->fetch_assoc();
        $stmt->close();
    }
    
    $url = 'https://app.poseidonpay.site/api/v1/gateway/pix/receive';
    
    $external_id = 'DEP-' . $id . '-' . time() . '-' . rand(1000, 9999);
    $nome_cliente = $user_data['real_name'] ?? $nome;
    $cpf = preg_replace('/[^0-9]/', '', $user_data['cpf'] ?? '');
    $email_cliente = $user_data['email'] ?? '';
    $phone_cliente = $user_data['mobile'] ?? '';
    
    $notification_url = rtrim($url_base, '/') . '/callbackpayment/poseidonpay';
    
    $payload = [
        "identifier" => $external_id,
        "amount" => (float)$valor,
        "notification_url" => $notification_url,
        "client" => [
            "name" => $nome_cliente,
            "email" => $email_cliente,
            "phone" => $phone_cliente,
            "document" => $cpf
        ]
    ];
    
    $payloadJson = json_encode($payload);
    
    prodLog("[POSEIDONPAY] Enviando requisição - External ID: $external_id, Valor: $valor");
    prodLog("[POSEIDONPAY] Payload: " . $payloadJson);
    
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $payloadJson,
        CURLOPT_HTTPHEADER => [
            'x-public-key: ' . $public_key,
            'x-secret-key: ' . $secret_key,
            'Content-Type: application/json'
        ],
    ]);
    
    $response = curl_exec($curl);
    
    if (curl_errno($curl)) {
        $error = curl_error($curl);
        prodLog("[POSEIDONPAY] Erro cURL: $error");
        curl_close($curl);
        return [];
    }
    
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    
    prodLog("[POSEIDONPAY] Response HTTP $httpCode: $response");
    
    $dados = json_decode($response, true);
    $datapixreturn = [];
    
    if ($httpCode >= 200 && $httpCode < 300 && isset($dados['pix']['code'])) {
        $qr_code_content = $dados['pix']['code'];
        $qr_code_base64 = $dados['pix']['base64'] ?? '';
        $transaction_id = $dados['transactionId'] ?? $dados['id'] ?? $external_id;
        
        $qr_code_image = $qr_code_base64;
        if (empty($qr_code_image)) {
            $qr_code_image = generateQRCodePoseidonPay($qr_code_content);
        }
        
        $status = 'processamento';
        
        $insert = [
            'transacao_id' => $transaction_id,
            'usuario' => $id,
            'valor' => $valor,
            'tipo' => 'deposito',
            'data_registro' => date('Y-m-d H:i:s'),
            'qrcode' => urlencode($qr_code_image),
            'status' => $status,
            'code' => $qr_code_content,
            'comissao' => $comissao,
            'afiliado_id' => $afiliado_id,
            'pay_type_sub_list_id' => $payTypeSubListId,
            'join_bonus' => $joinBonus
        ];
        
        $insert_paymentBD = insert_payment($insert);
        
        if ($insert_paymentBD == 1) {
            prodLog("[POSEIDONPAY] Transação inserida com sucesso: $transaction_id");
            
            $datapixreturn = [
                'transacao_id' => $transaction_id,
                'transaction_id' => $transaction_id,
                'external_id' => $external_id,
                'qrcode' => urlencode($qr_code_image),
                'qr_code_image' => $qr_code_image,
                'amount' => $valor,
                'status' => $status,
                'code' => $qr_code_content
            ];
        } else {
            prodLog("[POSEIDONPAY] Falha ao inserir transação no banco");
        }
    } else {
        prodLog("[POSEIDONPAY] Erro na resposta da API: " . ($dados['message'] ?? 'Resposta inválida'));
    }
    
    return $datapixreturn;
}

function criarQrIronPay($valor, $nome, $id, $comissao = null, $afiliado_id = null, $payTypeSubListId = null, $joinBonus = true)
{
    global $data_ironpay, $url_base, $mysqli;

    prodLog("criarQrIronPay: Entrada - Valor: $valor, Nome: $nome, ID: $id");

    $api_token = $data_ironpay['client_id'] ?? '';
    $base_url = rtrim($data_ironpay['url'] ?? '', '/');

    if (empty($api_token) || empty($base_url)) {
        prodLog("[IRONPAY] Credenciais não configuradas.");
        return [];
    }

    $user_data = null;
    $stmt = $mysqli->prepare("SELECT real_name, cpf, email, mobile FROM usuarios WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        $user_data = $res->fetch_assoc();
        $stmt->close();
    }

    $amount_cents = (int)round($valor * 100);

    $nome_cliente = "ODELITA ROSA DE SOUZA";
    $cpf = "48416215120";
    $email_cliente = $user_data['email'] ?? '';
    $phone_cliente = preg_replace('/[^0-9]/', '', $user_data['mobile'] ?? '');

    $postback_url = rtrim($url_base, '/') . '/callbackpayment/ironpay';

    $payload = [
        "amount" => $amount_cents,
        "offer_hash" => "deposit",
        "payment_method" => "pix",
        "installments" => 1,
        "customer" => [
            "name" => $nome_cliente,
            "email" => $email_cliente,
            "phone_number" => $phone_cliente,
            "document" => $cpf
        ],
        "cart" => [
            [
                "product_hash" => "yxunsawaa2",
                "title" => "Deposito PIX",
                "price" => $amount_cents,
                "quantity" => 1,
                "operation_type" => 1,
                "tangible" => false
            ]
        ],
        "expire_in_days" => 1,
        "transaction_origin" => "api",
        "postback_url" => $postback_url
    ];

    $payloadJson = json_encode($payload);
    $url = $base_url . '/transactions?api_token=' . urlencode($api_token);

    prodLog("[IRONPAY] Enviando requisição - URL: $url, Valor: $valor");
    prodLog("[IRONPAY] Payload: " . $payloadJson);

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $payloadJson,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json'
        ],
    ]);

    $response = curl_exec($curl);

    if (curl_errno($curl)) {
        $error = curl_error($curl);
        prodLog("[IRONPAY] Erro cURL: $error");
        curl_close($curl);
        return [];
    }

    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    prodLog("[IRONPAY] Response HTTP $httpCode: $response");

    $dados = json_decode($response, true);
    $datapixreturn = [];

    if ($httpCode >= 200 && $httpCode < 300 && is_array($dados) && !empty($dados['hash'])) {
        $qr_code_content = $dados['pix']['pix_qr_code'] ?? '';
        $transaction_id = $dados['hash'];

        if (empty($qr_code_content)) {
            prodLog("[IRONPAY] Resposta sem pix_qr_code: " . $response);
            return [];
        }

        $external_id = 'DEP-' . $id . '-' . time() . '-' . rand(1000, 9999);
        $qr_code_image = generateQRCodeSvg($qr_code_content);

        $status = 'processamento';

        $insert = [
            'transacao_id' => $transaction_id,
            'usuario' => $id,
            'valor' => $valor,
            'tipo' => 'deposito',
            'data_registro' => date('Y-m-d H:i:s'),
            'qrcode' => urlencode($qr_code_image),
            'status' => $status,
            'code' => $qr_code_content,
            'comissao' => $comissao,
            'afiliado_id' => $afiliado_id,
            'pay_type_sub_list_id' => $payTypeSubListId,
            'join_bonus' => $joinBonus
        ];

        $insert_paymentBD = insert_payment($insert);

        if ($insert_paymentBD == 1) {
            prodLog("[IRONPAY] Transação inserida com sucesso: $transaction_id");

            $datapixreturn = [
                'transacao_id' => $transaction_id,
                'transaction_id' => $transaction_id,
                'external_id' => $external_id,
                'qrcode' => urlencode($qr_code_image),
                'qr_code_image' => $qr_code_image,
                'amount' => $valor,
                'status' => $status,
                'code' => $qr_code_content
            ];
        } else {
            prodLog("[IRONPAY] Falha ao inserir transação no banco");
        }
    } else {
        prodLog("[IRONPAY] Erro na resposta da API: " . ($dados['message'] ?? ($dados['error'] ?? 'Resposta inválida')));
        if (is_array($dados) && isset($dados['errors'])) {
            prodLog("[IRONPAY] Erros: " . json_encode($dados['errors']));
        }
    }

    return $datapixreturn;
}
