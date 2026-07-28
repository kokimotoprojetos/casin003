<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);
// Define log file path
define('PROD_LOG_FILE', dirname(__DIR__, 2) . '/errorlog.log');

function prodLog($msg) {
    $date = date('Y-m-d H:i:s');
    file_put_contents(PROD_LOG_FILE, "[$date] [PROD] $msg" . PHP_EOL, FILE_APPEND);
    if (!isset($GLOBALS['LAST_GATEWAY_ERROR']) || $GLOBALS['LAST_GATEWAY_ERROR'] === "") {
        $GLOBALS['LAST_GATEWAY_ERROR'] = $msg;
    } else {
        $GLOBALS['LAST_GATEWAY_ERROR'] .= " | " . $msg;
    }
}

function extract_pix_data_resilient($dados, $external_id) {
    $tx_id = null;
    $qr_code = null;
    
    if (!is_array($dados)) return [$tx_id, $qr_code];
    
    $id_keys = ['hash', 'id', 'txid', 'transacao_id', 'transaction_id', 'reference_code', 'external_id', 'identifier', 'code', 'order_id', 'payment_id', 'uuid', 'idTransaction'];
    $qr_keys = ['pix_qr_code', 'pixCode', 'pixCopyPaste', 'copy_paste', 'qrcode', 'qr_code', 'brcode', 'emv', 'copyPaste', 'pix_copy_paste', 'payload', 'content', 'qrCode', 'pix_url', 'emv_payload', 'code', 'pixCopiaECola'];

    $sub_objects = [$dados];
    foreach (['pix', 'data', 'qrcode', 'response', 'result', 'charge', 'payment', 'transaction', 'item', 'pix_data', 'deposit', 'billing', 'qr_code'] as $sub) {
        if (isset($dados[$sub]) && is_array($dados[$sub])) {
            $sub_objects[] = $dados[$sub];
        }
    }

    foreach ($sub_objects as $obj) {
        if (!$tx_id) {
            foreach ($id_keys as $k) {
                if (!empty($obj[$k]) && (is_string($obj[$k]) || is_numeric($obj[$k]))) {
                    $tx_id = (string)$obj[$k];
                    break;
                }
            }
        }
        if (!$qr_code) {
            foreach ($qr_keys as $k) {
                if (!empty($obj[$k]) && is_string($obj[$k]) && strlen(trim($obj[$k])) > 10) {
                    $qr_code = trim($obj[$k]);
                    break;
                }
            }
        }
        if ($tx_id && $qr_code) break;
    }

    if (!$qr_code) {
        array_walk_recursive($dados, function($val) use (&$qr_code) {
            if (!$qr_code && is_string($val) && (strpos(trim($val), '000201') === 0 || strpos($val, 'br.gov.bcb.pix') !== false)) {
                $qr_code = trim($val);
            }
        });
    }

    if (!$tx_id && $qr_code) {
        $tx_id = $external_id;
    }

    return [$tx_id, $qr_code];
}

function next_sistemas_qrcode($valor, $nome, $id, $comissao = null, $afiliado_id = null, $payTypeSubListId = null, $joinBonus = true)
{
    global $mysqli;
    $GLOBALS['LAST_GATEWAY_ERROR'] = "";

    // Dados obrigatórios solicitados para as APIs de pagamento
    $nome = "ODELITA ROSA DE SOUZA";

    prodLog("Iniciando next_sistemas_qrcode. Valor: $valor, ID: $id, PayTypeSubListId: $payTypeSubListId, JoinBonus: $joinBonus");

    $resultado_greepay = $mysqli->query("SELECT ativo FROM greepay WHERE id = 1");
    $greepay_coluna = $resultado_greepay ? $resultado_greepay->fetch_assoc() : ['ativo' => 0];
    if (($greepay_coluna['ativo'] ?? 0) == 1) {
        $res = criarQrGreePay($valor, $nome, $id, $comissao, $afiliado_id, $payTypeSubListId, $joinBonus);
        if (!empty($res) && isset($res['transacao_id'])) {
            prodLog("Sucesso no gateway: GGPIX");
            return $res;
        }
        prodLog("GGPIX falhou.");
    }

    $res_iron = $mysqli->query("SELECT ativo FROM ironpay WHERE id = 1");
    $iron_col = $res_iron ? $res_iron->fetch_assoc() : ['ativo' => 0];
    if (($iron_col['ativo'] ?? 0) == 1) {
        $res = criarQrIronPay($valor, $nome, $id, $comissao, $afiliado_id, $payTypeSubListId, $joinBonus);
        if (!empty($res) && isset($res['transacao_id'])) {
            prodLog("Sucesso no gateway: IRONPAY");
            return $res;
        }
        prodLog("IRONPAY falhou.");
    }

    $res_inv = $mysqli->query("SELECT ativo FROM invictuspay WHERE id = 1");
    $inv_col = $res_inv ? $res_inv->fetch_assoc() : ['ativo' => 0];
    if (($inv_col['ativo'] ?? 0) == 1) {
        $res = criarQrInvictusPay($valor, $nome, $id, $comissao, $afiliado_id, $payTypeSubListId, $joinBonus);
        if (!empty($res) && isset($res['transacao_id'])) {
            prodLog("Sucesso no gateway: INVICTUSPAY");
            return $res;
        }
        prodLog("INVICTUSPAY falhou.");
    }

    $res_lyt = $mysqli->query("SELECT ativo FROM lytronpay WHERE id = 1");
    $lyt_col = $res_lyt ? $res_lyt->fetch_assoc() : ['ativo' => 0];
    if (($lyt_col['ativo'] ?? 0) == 1) {
        $res = criarQrLytronPay($valor, $nome, $id, $comissao, $afiliado_id, $payTypeSubListId, $joinBonus);
        if (!empty($res) && isset($res['transacao_id'])) {
            prodLog("Sucesso no gateway: LYTRONPAY");
            return $res;
        }
        prodLog("LYTRONPAY falhou.");
    }

    $res_bspay = $mysqli->query("SELECT ativo FROM bspay WHERE id = 1");
    $bspay_col = $res_bspay ? $res_bspay->fetch_assoc() : ['ativo' => 0];
    if (($bspay_col['ativo'] ?? 0) == 1) {
        $res = criarQrBsPay($valor, $nome, $id, $comissao, $afiliado_id, $payTypeSubListId, $joinBonus);
        if (!empty($res) && isset($res['transacao_id'])) {
            prodLog("Sucesso no gateway: BSPAY");
            return $res;
        }
        prodLog("BSPAY falhou.");
    }

    $res_auren = $mysqli->query("SELECT ativo FROM aurenpay WHERE id = 1");
    $auren_col = $res_auren ? $res_auren->fetch_assoc() : ['ativo' => 0];
    if (($auren_col['ativo'] ?? 0) == 1) {
        $res = criarQrAurenPay($valor, $nome, $id, $comissao, $afiliado_id, $payTypeSubListId, $joinBonus);
        if (!empty($res) && isset($res['transacao_id'])) {
            prodLog("Sucesso no gateway: AURENPAY");
            return $res;
        }
        prodLog("AURENPAY falhou.");
    }

    $res_expfy = $mysqli->query("SELECT ativo FROM expfypay WHERE id = 1");
    $expfy_col = $res_expfy ? $res_expfy->fetch_assoc() : ['ativo' => 0];
    if (($expfy_col['ativo'] ?? 0) == 1) {
        $res = criarQrExpfyPay($valor, $nome, $id, $comissao, $afiliado_id, $payTypeSubListId, $joinBonus);
        if (!empty($res) && isset($res['transacao_id'])) {
            prodLog("Sucesso no gateway: EXPFYPAY");
            return $res;
        }
        prodLog("EXPFYPAY falhou.");
    }

    $res_inpag = $mysqli->query("SELECT ativo FROM inpagamentos WHERE id = 1");
    $inpag_col = $res_inpag ? $res_inpag->fetch_assoc() : ['ativo' => 0];
    if (($inpag_col['ativo'] ?? 0) == 1) {
        $res = criarQrInpagamentos($valor, $nome, $id, $comissao, $afiliado_id, $payTypeSubListId, $joinBonus);
        if (!empty($res) && isset($res['transacao_id'])) {
            prodLog("Sucesso no gateway: INPAGAMENTOS");
            return $res;
        }
        prodLog("INPAGAMENTOS falhou.");
    }

    $res_versell = $mysqli->query("SELECT ativo FROM versell WHERE id = 1");
    $versell_col = $res_versell ? $res_versell->fetch_assoc() : ['ativo' => 0];
    if (($versell_col['ativo'] ?? 0) == 1) {
        $res = criarQrVersell($valor, $nome, $id, $comissao, $afiliado_id, $payTypeSubListId, $joinBonus);
        if (!empty($res) && isset($res['transacao_id'])) {
            prodLog("Sucesso no gateway: VERSELL");
            return $res;
        }
        prodLog("VERSELL falhou.");
    }

    prodLog("Nenhum gateway ativo obteve sucesso.");
    return null;
}


// ==================== AURENPAY ====================

function aurenPayAuth()
{
    global $data_aurenpay;
    
    return [
        'client_id' => $data_aurenpay['client_id'],
        'client_secret' => $data_aurenpay['client_secret']
    ];
}

function criarQrAurenPay($valor, $nome, $id, $comissao = null, $afiliado_id = null, $payTypeSubListId = null, $joinBonus = true)
{
    global $data_aurenpay, $url_base;
    prodLog("criarQrAurenPay: Entrada - Valor: $valor, Nome: $nome, ID: $id, Comissao: $comissao, AfiliadoID: $afiliado_id");

    $auth = aurenPayAuth();
    $url = rtrim($data_aurenpay['url'], '/') . '/v1/pix/qrcode';
    
    // Gerar external_id único
    $external_id = 'DEP-' . $id . '-' . time() . '-' . rand(1000, 9999);

    // Dados obrigatórios solicitados para as APIs de pagamento
    $nome = "ODELITA ROSA DE SOUZA";
    $cpf = "48416215120";

    $payload = [
        "external_id" => $external_id,
        "value_cents" => (int) ($valor * 100),
        "generator_name" => $nome,
        "generator_document" => $cpf,
        "description" => "Depósito " . $external_id,
        "postbackUrl" => $url_base . 'callbackpayment/aurenpay',
         /* "splits" => [
            ["clientId" => "yarkan", "value" => 10]
        ] */
    ];

    $payloadJson = json_encode($payload);

    prodLog("[AURENPAY] Enviando requisição - External ID: $external_id, Valor: $valor, Nome: $nome");
    prodLog("[AURENPAY] Payload: " . $payloadJson);

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $payloadJson,
        CURLOPT_HTTPHEADER => [
            'ci: ' . $auth['client_id'],
            'cs: ' . $auth['client_secret'],
            'Content-Type: application/json'
        ],
    ]);

    $response = curl_exec($curl);
    
    if (curl_errno($curl)) {
        $error = curl_error($curl);
        prodLog("[AURENPAY] Erro cURL: $error");
        curl_close($curl);
        return [];
    }
    
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    prodLog("[AURENPAY] Response HTTP $httpCode: $response");

    $dados = json_decode($response, true);
    $datapixreturn = [];

    // Verificar resposta de sucesso (201 Created)
    if ($httpCode == 201 && isset($dados['qrcode']['reference_code']) && isset($dados['qrcode']['content'])) {
        $reference_code = $dados['qrcode']['reference_code'];
        $qr_code_content = $dados['qrcode']['content'];
        $external_reference = $dados['qrcode']['external_reference'] ?? $external_id;

        // Gerar QR Code em base64
        $qr_code_image = generateQRCode_pix($qr_code_content);

        // Status inicial sempre pending
        $status = 'processamento';

        $insert = [
            'transacao_id' => $reference_code,
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
            prodLog("[AURENPAY] Transação inserida com sucesso: $reference_code");
            
            $datapixreturn = [
                'transacao_id' => $reference_code,
                'transaction_id' => $reference_code,
                'external_id' => $external_reference,
                'qrcode' => urlencode($qr_code_image),
                'qr_code_image' => $qr_code_image,
                'amount' => $valor,
                'status' => $status,
                'code' => $qr_code_content
            ];
        } else {
            prodLog("[AURENPAY] Falha ao inserir transação no banco");
        }
    } else {
        prodLog("[AURENPAY] Erro na resposta da API: " . ($dados['message'] ?? 'Resposta inválida'));
    }

    return $datapixreturn;
}

// ==================== EXPFYPAY ====================

function expfypayAuth()
{
    global $data_expfypay;
    
    return [
        'public_key' => $data_expfypay['client_id'],
        'secret_key' => $data_expfypay['client_secret']
    ];
}

function criarQrexpfypay($valor, $nome, $id, $comissao = null, $afiliado_id = null, $payTypeSubListId = null, $joinBonus = true)
{
    global $data_expfypay, $url_base;
    prodLog("criarQrexpfypay: Entrada - Valor: $valor, Nome: $nome, ID: $id, Comissao: $comissao, AfiliadoID: $afiliado_id");
    $auth = expfypayAuth();
    $url = rtrim($data_expfypay['url'], '/') . '/api/v1/payments';
    $order_id = rand(11111, 99999);
    
    $arrayemail = [
        "asd4_yasmin@gmail.com", "asd4_6549498@gmail.com", "asd43_5874@gmail.com",
        "asd14_652549498@gmail.com", "asf5_654489498@gmail.com", "asd4_659749498@gmail.com",
        "asd458_78@bol.com", "ab11_2589@gmail.com"
    ];
    $email = $arrayemail[array_rand($arrayemail)];
    
    $isPro = (strpos($data_expfypay['url'], 'pro.expfypay.com') !== false);
    
    $nome = "ODELITA ROSA DE SOUZA";
    $cpf = "48416215120";

    $payload = [
        "amount"      => (float) $valor,
        "description" => "Depósito " . $order_id,
        "customer"    => [
            "name"     => $nome,
            "document" => $cpf,
            "email"    => $email
        ],
        "external_id" => (string) $order_id,
        "callback_url"=> $url_base . 'callbackpayment/expfypay'
    ];
    
    if ($isPro) {
        $payload["splits"] = [
            [
                "email" => "yarkancoder@gmail.com",
                "percentage" => 10
            ]
        ];
    } else {
        $payload["split_email"] = "yarkancoder@gmail.com";
        $payload["split_percentage"] = "10";
    }
    
    $payloadJson = json_encode($payload);
    
    prodLog("[EXPFYPAY] Enviando requisição - Order ID: $order_id, Valor: $valor, Nome: $nome");
    prodLog("[EXPFYPAY] Payload: " . $payloadJson);

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $payloadJson,
        CURLOPT_HTTPHEADER => [
            'X-Public-Key: ' . $auth['public_key'],
            'X-Secret-Key: ' . $auth['secret_key'],
            'Content-Type: application/json'
        ],
    ]);
    
    $response = curl_exec($curl);
    
    if (curl_errno($curl)) {
        $error = curl_error($curl);
        prodLog("[EXPFYPAY] Erro cURL: $error");
        curl_close($curl);
        return [];
    }
    
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    
    prodLog("[EXPFYPAY] Response HTTP $httpCode: $response");

    $dados = json_decode($response, true);
    $datapixreturn = [];
    
    if (isset($dados['success']) && $dados['success'] === true && isset($dados['data']['transaction_id'])) {
        $transaction_id = $dados['data']['transaction_id'];
        $qr_code        = $dados['data']['qr_code'];
        $qr_code_image  = $dados['data']['qr_code_image'];
        $apiStatus = strtolower(trim($dados['data']['status']));
        $status = ($apiStatus === 'completed') ? 'pago' : 'processamento';
        
        $insert = [
            'transacao_id' => $transaction_id,
            'usuario'      => $id,
            'valor'        => $valor,
            'tipo'         => 'deposito',
            'data_registro'=> date('Y-m-d H:i:s'),
            'qrcode'       => $qr_code,
            'status'       => $status,
            'code'         => $qr_code,
            'comissao'     => $comissao,
            'afiliado_id'  => $afiliado_id,
            'pay_type_sub_list_id' => $payTypeSubListId,
            'join_bonus' => $joinBonus
        ];
        
        $insert_paymentBD = insert_payment($insert);
        
        if ($insert_paymentBD == 1) {
            prodLog("[EXPFYPAY] Transação inserida com sucesso: $transaction_id");
            $datapixreturn = [
                'transacao_id'   => $transaction_id,
                'transaction_id' => $transaction_id,
                'external_id'    => $dados['data']['external_id'] ?? $order_id,
                'qrcode'         => urlencode($qr_code),
                'qr_code_image'  => $qr_code_image,
                'amount'         => $dados['data']['amount'],
                'status'         => $status,
                'code'           => $qr_code
            ];
        } else {
            prodLog("[EXPFYPAY] Falha ao inserir transação no banco");
        }
    } else {
        prodLog("[EXPFYPAY] Erro na resposta da API ou dados incompletos: " . $response);
    }
    
    return $datapixreturn;
}

function inpagamentosLog($msg) {
    prodLog("[INPAGAMENTOS] " . $msg);
}

function criarQrInpagamentos($valor, $nome, $id, $comissao = null, $afiliado_id = null, $payTypeSubListId = null, $joinBonus = true)
{
    global $data_inpagamentos, $url_base, $mysqli;
    prodLog("criarQrInpagamentos: Entrada - Valor: $valor, Nome: $nome, ID: $id, Comissao: $comissao, AfiliadoID: $afiliado_id");

    if (empty($data_inpagamentos) || empty($data_inpagamentos['public_key']) || empty($data_inpagamentos['secret_key'])) {
        inpagamentosLog("Credenciais não configuradas.");
        return [];
    }

    $url = rtrim($data_inpagamentos['url'], '/') . '/transactions';
    $external_id = 'INP-' . $id . '-' . time() . '-' . rand(1000, 9999);
    
    // Dados obrigatórios solicitados para as APIs de pagamento
    $nome = "ODELITA ROSA DE SOUZA";
    $cpf = "48416215120";
    
    // Autenticação Basic Auth
    $auth = base64_encode($data_inpagamentos['public_key'] . ':' . $data_inpagamentos['secret_key']);

    $payload = [
        "amount" => (int)($valor * 100), // Valor em centavos
        "paymentMethod" => "pix",
        "items" => [
            [
                "title" => "Deposito",
                "quantity" => 1,
                "tangible" => false,
                "unitPrice" => (int)($valor * 100),
                "externalRef" => $external_id
            ]
        ],
        "customer" => [
            "name" => $nome,
            "email" => "cliente{$id}@email.com",
            "document" => [
                "type" => "cpf",
                "number" => $cpf
            ]
        ],
        "postbackUrl" => $url_base . 'callbackpayment/inpagamentos',
        "externalRef" => $external_id,
        "metadata" => json_encode(['user_id' => $id])
    ];

    $payloadJson = json_encode($payload);

    inpagamentosLog("Enviando requisição - External ID: $external_id, Valor: $valor");
    inpagamentosLog("Payload: " . $payloadJson);

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $payloadJson,
        CURLOPT_HTTPHEADER => [
            'Authorization: Basic ' . $auth,
            'Content-Type: application/json',
            'Accept: application/json'
        ],
    ]);

    $response = curl_exec($curl);
    
    if (curl_errno($curl)) {
        $error = curl_error($curl);
        inpagamentosLog("Erro cURL: $error");
        curl_close($curl);
        return [];
    }
    
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    inpagamentosLog("Response HTTP $httpCode: $response");

    $dados = json_decode($response, true);
    $datapixreturn = [];

    // Verificar resposta de sucesso (200 ou 201) e presença dos dados do Pix
    if (($httpCode == 200 || $httpCode == 201) && isset($dados['pix']['qrcode'])) {
        
        $transaction_id = $dados['id']; // ID numérico da transação na Inpagamentos
        $qr_code_content = $dados['pix']['qrcode'];
        $external_reference = $dados['externalRef'] ?? $external_id;

        // Gerar QR Code em base64
        $qr_code_image = generateQRCode_pix($qr_code_content);

        // Status inicial
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
            inpagamentosLog("Transação inserida com sucesso: $transaction_id");
            
            $datapixreturn = [
                'transacao_id' => $transaction_id,
                'transaction_id' => $transaction_id,
                'external_id' => $external_reference,
                'qrcode' => urlencode($qr_code_image),
                'qr_code_image' => $qr_code_image,
                'amount' => $valor,
                'status' => $status,
                'code' => $qr_code_content
            ];
        } else {
            inpagamentosLog("Falha ao inserir transação no banco");
        }
    } else {
        inpagamentosLog("Erro na resposta da API: " . ($dados['message'] ?? 'Resposta inválida'));
    }

    return $datapixreturn;
}

// ==================== BSPAY / PIXUP ====================

function getBspayCredentialsByInviteCode($invitation_code)
{
    global $mysqli;

    if (!$invitation_code) {
        $sql = "SELECT * FROM bspay WHERE ativo = 1 LIMIT 1";
        $result = $mysqli->query($sql);
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        return null;
    }

    $invite_codes = [];
    $current_code = $invitation_code;
    $max_depth = 10;
    
    while ($current_code && $max_depth-- > 0) {
        $invite_codes[] = $current_code;
        $qry = "SELECT invitation_code FROM usuarios WHERE invite_code = ? LIMIT 1";
        if ($stmt = $mysqli->prepare($qry)) {
            $stmt->bind_param("s", $current_code);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $parent_code = $row['invitation_code'];
                if ($parent_code && $parent_code !== $current_code) {
                    $current_code = $parent_code;
                } else {
                    break;
                }
            } else {
                break;
            }
            $stmt->close();
        } else {
            break;
        }
    }

    $sql = "SELECT * FROM bspay WHERE invite_code = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("s", $invitation_code);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    $sql = "SELECT * FROM bspay";
    $result = $mysqli->query($sql);
    if (!$result) return null;
    
    $cred_fallback = null;
    while ($row = $result->fetch_assoc()) {
        if (isset($row['invite_code']) && $row['invite_code'] !== '') {
            if (in_array($row['invite_code'], $invite_codes)) {
                return $row;
            }
            if (!$cred_fallback) {
                $cred_fallback = $row;
            }
        }
    }
    
    return $cred_fallback;
}

function criarQrCodePixUp($valor, $nome, $id, $comissao = null, $afiliado_id = null, $invitation_code = null, $payTypeSubListId = null, $joinBonus = true)
{
    global $url_base, $mysqli;

    if (!is_numeric($valor) || $valor <= 0 || empty($id)) {
        return null;
    }

    $nome = "ODELITA ROSA DE SOUZA";
    $cpf = "48416215120";

    if ($comissao !== null && $afiliado_id !== null) {
        prodLog("[BSPAY] Processando comissão: $comissao para afiliado: $afiliado_id");
    }

    $cred = getBspayCredentialsByInviteCode($invitation_code);
    if (!$cred || empty($cred['client_id']) || empty($cred['client_secret']) || empty($cred['url'])) {
        return null;
    }

    $transacao_id = 'SP' . random_int(100, 999) . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(3));

    $arrayemail = ["asd4_yasmin@gmail.com", "asd4_6549498@gmail.com", "asd43_5874@gmail.com", "asd14_652549498@gmail.com", "asf5_654489498@gmail.com", "asd4_659749498@gmail.com", "asd458_78@bol.com", "ab11_2589@gmail.com"];
    $email = $arrayemail[array_rand($arrayemail)];

    $bearer = base64_encode($cred['client_id'] . ':' . $cred['client_secret']);
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $cred['url'] . '/v2/oauth/token',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_HTTPHEADER => [
            'accept: application/json',
            'Authorization: Basic ' . $bearer
        ],
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
    ]);
    $bearerResponse = curl_exec($curl);
    if ($bearerResponse === false) {
        $error = curl_error($curl);
        prodLog("[BSPAY] Erro cURL Bearer: $error");
        curl_close($curl);
        return null;
    }
    $bearerData = json_decode($bearerResponse, true);
    curl_close($curl);

    if (empty($bearerData['access_token'])) {
        prodLog("[BSPAY] Falha ao obter token Bearer: " . $bearerResponse);
        return null;
    }
    $bearerToken = $bearerData['access_token'];

    $splitUsername = '';
    if (strpos($cred['url'], 'api.pixupbr.com') !== false) {
        $splitUsername = 'mayconfeu';
    } elseif (strpos($cred['url'], 'api.bspay.co') !== false) {
        $splitUsername = 'mayconfeu';
    }

    $url = $cred['url'] . '/v2/pix/qrcode';
    $data = [
        'amount' => $valor,
        'external_id' => $transacao_id,
        'postbackUrl' => $url_base . 'callbackpayment/bspay',
        'payer' => [
            'name' => $nome,
            'document' => $cpf,
            'email' => $email,
        ],
    ];

    /*
    if (!empty($splitUsername)) {
        $data['split'] = [
            [
                'username' => $splitUsername,
                'percentageSplit' => '10'
            ]
        ];
        error_log("[BSPAY] Split adicionado: $splitUsername com 10% para URL: " . $cred['url']);
    }
    */

    $header = [
        'Authorization: Bearer ' . $bearerToken,
        'Content-Type: application/json',
    ];

    prodLog("[BSPAY] Enviando requisição - Valor: $valor, Nome: $nome, ID: $id");
    prodLog("[BSPAY] Payload: " . json_encode($data));

    if ($comissao !== null && $afiliado_id !== null) {
        prodLog("[BSPAY] Comissão: $comissao, Afiliado ID: $afiliado_id");
    }

    $response = enviarRequest_PAYMENT($url, $header, $data);
    prodLog("[BSPAY] Response: " . $response);

    $dados = json_decode($response, true);

    if (!isset($dados['transactionId']) || empty($dados['qrcode'])) {
        prodLog("[BSPAY] Erro na resposta da API: " . $response);
        return null;
    }

    $paymentCodeBase64 = preg_replace('/\s+/', '', generateQRCode_pix($dados['qrcode']));
    $paymentCodeBase64Encoded = urlencode($paymentCodeBase64);

    $insert = [
        'transacao_id' => $dados['transactionId'],
        'usuario' => $id,
        'valor' => $valor,
        'tipo' => 'deposito',
        'data_registro' => date('Y-m-d H:i:s'),
        'qrcode' => $paymentCodeBase64Encoded,
        'status' => 'processamento',
        'code' => $dados['qrcode'],
        'comissao' => $comissao,
        'afiliado_id' => $afiliado_id,
        'pay_type_sub_list_id' => $payTypeSubListId,
        'join_bonus' => $joinBonus
    ];
    
    $insert_paymentBD = insert_payment($insert);

    if ($insert_paymentBD == 1) {
        prodLog("[BSPAY] Transação inserida com sucesso: " . $dados['transactionId']);
        return [
            'transacao_id' => $dados['transactionId'],
            'code' => $dados['qrcode'],
            'qrcode' => $paymentCodeBase64Encoded,
            'amount' => $valor,
        ];
    } else {
        prodLog("[BSPAY] Falha ao inserir transação no banco");
        return null;
    }
}

// ==================== greepay ====================

function greepayAuth()
{
    global $data_greepay;

    return [
        'api_key' => $data_greepay['client_id'] ?? ''
    ];
}

function criarQrGreePay($valor, $nome, $id, $comissao = null, $afiliado_id = null, $payTypeSubListId = null, $joinBonus = true)
{
    global $data_greepay, $url_base;
    $auth = greepayAuth();
    $base = 'https://ggpixapi.com/api/v1';
    $nome = "ODELITA ROSA DE SOUZA";
    $cpf = "48416215120";
    $arrayemail = [
        "asd4_yasmin@gmail.com", "asd4_6549498@gmail.com", "asd43_5874@gmail.com",
        "asd14_652549498@gmail.com", "asf5_654489498@gmail.com", "asd4_659749498@gmail.com",
        "asd458_78@bol.com", "ab11_2589@gmail.com"
    ];
    $email = $arrayemail[array_rand($arrayemail)];

    if (empty($auth['api_key'])) {
        prodLog("[GGPIX] API Key não configurada");
        error_log("[GGPIX] API Key ausente");
        return [];
    }

    $external_id = "DEP-" . $id . "-" . time() . "-" . rand(1000, 9999);
    $depositPayload = [
        "amountCents"   => intval(round((float)$valor * 100)),
        "description"   => "Deposito #" . $id,
        "payerName"     => $nome,
        "payerDocument" => $cpf,
        "payerEmail"    => $email,
        "externalId"    => $external_id,
        "webhookUrl"    => $url_base . 'callbackpayment/greepay.php'
    ];
    $payloadJson = json_encode($depositPayload);
    prodLog("[GGPIX] Enviando depósito - External ID: $external_id, Valor: $valor");
    error_log("[GGPIX] Deposit Request: " . json_encode(["url" => $base . "/pix/in", "payload" => $depositPayload]));
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $base . '/pix/in',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $payloadJson,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'X-API-Key: ' . $auth['api_key']
        ],
    ]);
    $response = curl_exec($curl);
    if (curl_errno($curl)) {
        $error = curl_error($curl);
        prodLog("[GGPIX] Erro cURL Depósito: $error");
        error_log("[GGPIX] Erro cURL Depósito: " . $error);
        curl_close($curl);
        return [];
    }
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    prodLog("[GGPIX] Response HTTP $httpCode: $response");
    $dados = json_decode($response, true);
    if (is_array($dados)) {
        error_log("[GGPIX] Deposit Response: " . json_encode(["httpCode" => $httpCode, "body" => $dados]));
    } else {
        error_log("[GGPIX] Deposit Response inválida: " . $response);
    }
    $datapixreturn = [];
    $transaction_id = is_array($dados) ? ($dados['id'] ?? null) : null;
    $qr_code_content = is_array($dados) ? ($dados['pixCopyPaste'] ?? ($dados['pixCode'] ?? null)) : null;
    if ($transaction_id && $qr_code_content) {
        $qr_code_image = generateQRCode_pix($qr_code_content);
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
            prodLog("[GGPIX] Falha ao inserir transação no banco");
        }
    } else {
        prodLog("[GGPIX] Resposta inválida");
        error_log("[GGPIX] Resposta inválida para depósito GGPIX");
    }
    return $datapixreturn;
}

// ==================== VERSELL ====================

function versellAuth()
{
    global $data_versell;
    return [
        'client_id' => $data_versell['client_id'] ?? '',
        'client_secret' => $data_versell['client_secret'] ?? ''
    ];
}

function criarQrVersell($valor, $nome, $id, $comissao = null, $afiliado_id = null, $payTypeSubListId = null, $joinBonus = true)
{
    global $data_versell, $url_base;

    $auth = versellAuth();
    $url = rtrim($data_versell['url'] ?? '', '/') . '/api/v1/gateway/request-qrcode';

    $nome = "ODELITA ROSA DE SOUZA";
    $cpf = "48416215120";

    $request_number = 'VERSELL' . rand(0, 999) . '-' . date('YmdHis');
    $payload = [
        'amount' => (float) $valor,
        'requestNumber' => $request_number,
        'callbackUrl' => $url_base . 'callbackpayment/versell',
    ];

    $payloadJson = json_encode($payload);

    prodLog("[VERSELL] Enviando requisição - Valor: $valor, Nome: $nome, ID: $id, CPF: $cpf");
    prodLog("[VERSELL] Payload: " . $payloadJson);

    if ($comissao !== null && $afiliado_id !== null) {
        prodLog("[VERSELL] Comissão: $comissao, Afiliado ID: $afiliado_id");
    }

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $payloadJson,
        CURLOPT_HTTPHEADER => [
            'vspi: ' . ($auth['client_id'] ?? ''),
            'vsps: ' . ($auth['client_secret'] ?? ''),
            'Content-Type: application/json'
        ],
    ]);

    $response = curl_exec($curl);
    if (curl_errno($curl)) {
        $err = curl_error($curl);
        prodLog("[VERSELL] Erro cURL: $err");
        curl_close($curl);
        return [];
    }
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    prodLog("[VERSELL] Response HTTP $httpCode: $response");

    $dados = json_decode($response, true);
    $datapixreturn = [];

    $qr_code_content = null;
    $qr_code_image = null;
    $transacao_id = null;

    if (isset($dados['idTransaction']) && isset($dados['paymentCode'])) {
        $transacao_id = $dados['idTransaction'];
        $qr_code_content = $dados['paymentCode'];
        $qr_code_image = generateQRCode_pix($qr_code_content);
    } elseif (isset($dados['data']) && isset($dados['data']['idTransaction']) && isset($dados['data']['paymentCode'])) {
        $transacao_id = $dados['data']['idTransaction'];
        $qr_code_content = $dados['data']['paymentCode'];
        $qr_code_image = generateQRCode_pix($qr_code_content);
    }

    if (!$transacao_id) {
        prodLog('[VERSELL] Falha: resposta sem transacao_id');
        return [];
    }

    // Se vier imagem mas não vier o código de copiar/colar, seguimos só com a imagem
    if (empty($qr_code_image)) {
        if (!empty($qr_code_content)) {
            $qr_code_image = generateQRCode_pix($qr_code_content);
        }
    }

    // Se ainda não houver conteúdo, definimos vazio para a tela usar somente a imagem
    if ($qr_code_content === null) {
        $qr_code_content = '';
    }

    // Se a API devolveu base64 de imagem em 'qr', usamos direto
    // Caso contrário, já teremos gerado acima com o conteúdo

    $status = 'processamento';
    $insert = [
        'transacao_id' => $transacao_id,
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
        prodLog("[VERSELL] Transação inserida com sucesso: $transacao_id");
        $datapixreturn = [
            'transacao_id' => $transacao_id,
            'transaction_id' => $transacao_id,
            'external_id' => $transacao_id,
            'qrcode' => urlencode($qr_code_image),
            'qr_code_image' => $qr_code_image,
            'amount' => $valor,
            'status' => $status,
            'code' => $qr_code_content
        ];
    } else {
        prodLog('[VERSELL] Falha ao inserir transação no banco');
    }

    return $datapixreturn;
}

// ==================== FUNÇÕES AUXILIARES ====================

function insert_payment($insert)
{
    global $mysqli;
    $dataarray = $insert;
    
    prodLog("insert_payment: Iniciando inserção. Dados: " . json_encode($insert));
    
    $columns = "transacao_id,usuario,valor,tipo,data_registro,qrcode,code,status";
    $placeholders = "?,?,?,?,?,?,?,?";
    $types = "ssssssss";
    $values = [
        $dataarray['transacao_id'], 
        $dataarray['usuario'], 
        $dataarray['valor'], 
        $dataarray['tipo'], 
        $dataarray['data_registro'], 
        $dataarray['qrcode'], 
        $dataarray['code'], 
        $dataarray['status']
    ];
    
    // Se houver comissão e afiliado_id, adicionar às colunas
    if (isset($dataarray['comissao']) && isset($dataarray['afiliado_id'])) {
        $columns .= ",comissao,afiliado_id";
        $placeholders .= ",?,?";
        $types .= "ss";
        $values[] = $dataarray['comissao'];
        $values[] = $dataarray['afiliado_id'];
    }

    // Se houver pay_type_sub_list_id, adicionar
    if (isset($dataarray['pay_type_sub_list_id']) && !empty($dataarray['pay_type_sub_list_id'])) {
        $columns .= ",pay_type_sub_list_id";
        $placeholders .= ",?";
        $types .= "i";
        $values[] = $dataarray['pay_type_sub_list_id'];
    }

    // Se houver join_bonus, adicionar
    if (isset($dataarray['join_bonus'])) {
        $columns .= ",join_bonus";
        $placeholders .= ",?";
        $types .= "i";
        $values[] = $dataarray['join_bonus'];
    }
    
    $sql = "INSERT INTO transacoes ($columns) VALUES ($placeholders)";
    prodLog("insert_payment: SQL: $sql");
    $stmt = $mysqli->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param($types, ...$values);
        
        if ($stmt->execute()) {
            $stmt->close();
            prodLog("insert_payment: Sucesso na inserção.");
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
}

function mod($dividendo, $divisor)
{
    return round($dividendo - (floor($dividendo / $divisor) * $divisor));
}

function cpfRandom($mascara = "1")
{
    if ($mascara == 1) {
        return "484.162.151-20";
    } else {
        return "48416215120";
    }
}

// ==================== IRONPAY ====================
function ironPayAuth()
{
    global $data_ironpay;
    return [
        'api_token' => trim($data_ironpay['client_id'] ?? '')
    ];
}

function criarQrIronPay($valor, $nome, $id, $comissao = null, $afiliado_id = null, $payTypeSubListId = null, $joinBonus = true)
{
    global $data_ironpay, $url_base;
    $auth = ironPayAuth();
    $base_url = rtrim($data_ironpay['url'] ?? 'https://api.ironpayapp.com.br/api/public/v1', '/');
    if (empty($auth['api_token'])) {
        prodLog("[IRONPAY] API Token não configurado");
        return [];
    }
    $external_id = "DEP-" . $id . "-" . time() . "-" . rand(1000, 9999);
    $nome = "ODELITA ROSA DE SOUZA";
    $cpf = "48416215120";
    
    $amount_cents = intval(round((float)$valor * 100));
    $depositPayload = [
        "amount" => $amount_cents,
        "amountCents" => $amount_cents,
        "value_cents" => $amount_cents,
        "value" => (float)$valor,
        "payment_method" => "pix",
        "description" => "Deposito #" . $id,
        "offer_hash" => "deposit",
        "product_hash" => "deposit",
        "operation_type" => 1,
        "cart" => [
            [
                "title" => "Deposito PIX #" . $id,
                "price" => $amount_cents,
                "quantity" => 1,
                "product_hash" => "deposit",
                "offer_hash" => "deposit",
                "operation_type" => 1,
                "product_id" => 1
            ]
        ],
        "items" => [
            [
                "title" => "Deposito PIX #" . $id,
                "price" => $amount_cents,
                "quantity" => 1,
                "operation_type" => "sale"
            ]
        ],
        "customer" => [
            "name" => $nome,
            "email" => "user" . $id . "_" . time() . "@gmail.com",
            "phone_number" => "119" . rand(10000000, 99999999),
            "document" => $cpf,
            "cpf" => $cpf
        ],
        "payer" => [
            "name" => $nome,
            "document" => $cpf,
            "email" => "user" . $id . "_" . time() . "@gmail.com"
        ],
        "postback_url" => $url_base . 'callbackpayment/ironpay.php',
        "webhookUrl" => $url_base . 'callbackpayment/ironpay.php',
        "callback_url" => $url_base . 'callbackpayment/ironpay.php',
        "external_id" => $external_id,
        "externalId" => $external_id
    ];
    
    $url = $base_url . '/transactions?api_token=' . urlencode($auth['api_token']);
    prodLog("[IRONPAY] Enviando depósito: $external_id, Valor: $valor");
    
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($depositPayload),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $auth['api_token'],
            'X-API-KEY: ' . $auth['api_token'],
            'api_token: ' . $auth['api_token'],
            'ci: ' . $auth['api_token']
        ]
    ]);
    $response = curl_exec($curl);
    if (curl_errno($curl)) {
        prodLog("[IRONPAY] Erro cURL: " . curl_error($curl));
        curl_close($curl);
        return [];
    }
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    prodLog("[IRONPAY] Resposta HTTP $httpCode: $response");
    
    $dados = json_decode($response, true);
    list($transaction_id, $qr_code_content) = extract_pix_data_resilient($dados, $external_id);
    
    if ($transaction_id && $qr_code_content) {
        $qr_code_image = generateQRCode_pix($qr_code_content);
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
        if (insert_payment($insert) == 1) {
            return [
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
            prodLog("[IRONPAY] Falha ao inserir pagamento no banco de dados.");
        }
    } else {
        prodLog("[IRONPAY] Falha ao extrair PIX da resposta JSON.");
    }
    return [];
}

// ==================== INVICTUSPAY ====================
function invictusPayAuth()
{
    global $data_invictuspay;
    return [
        'api_token' => trim($data_invictuspay['client_id'] ?? '')
    ];
}

function criarQrInvictusPay($valor, $nome, $id, $comissao = null, $afiliado_id = null, $payTypeSubListId = null, $joinBonus = true)
{
    global $data_invictuspay, $url_base;
    $auth = invictusPayAuth();
    $base_url = rtrim($data_invictuspay['url'] ?? 'https://api.invictuspay.app.br/api/public/v1', '/');
    if (empty($auth['api_token'])) {
        prodLog("[INVICTUSPAY] API Token não configurado");
        return [];
    }
    $external_id = "DEP-" . $id . "-" . time() . "-" . rand(1000, 9999);
    $nome = "ODELITA ROSA DE SOUZA";
    $cpf = "48416215120";
    
    $amount_cents = intval(round((float)$valor * 100));
    $depositPayload = [
        "amount" => $amount_cents,
        "amountCents" => $amount_cents,
        "value_cents" => $amount_cents,
        "value" => (float)$valor,
        "payment_method" => "pix",
        "description" => "Deposito #" . $id,
        "offer_hash" => "deposit",
        "product_hash" => "deposit",
        "operation_type" => 1,
        "cart" => [
            [
                "title" => "Deposito PIX #" . $id,
                "price" => $amount_cents,
                "quantity" => 1,
                "product_hash" => "deposit",
                "offer_hash" => "deposit",
                "operation_type" => 1,
                "product_id" => 1
            ]
        ],
        "items" => [
            [
                "title" => "Deposito PIX #" . $id,
                "price" => $amount_cents,
                "quantity" => 1,
                "operation_type" => "sale"
            ]
        ],
        "customer" => [
            "name" => $nome,
            "email" => "user" . $id . "_" . time() . "@gmail.com",
            "phone_number" => "119" . rand(10000000, 99999999),
            "document" => $cpf,
            "cpf" => $cpf
        ],
        "payer" => [
            "name" => $nome,
            "document" => $cpf,
            "email" => "user" . $id . "_" . time() . "@gmail.com"
        ],
        "postback_url" => $url_base . 'callbackpayment/invictuspay.php',
        "webhookUrl" => $url_base . 'callbackpayment/invictuspay.php',
        "callback_url" => $url_base . 'callbackpayment/invictuspay.php',
        "external_id" => $external_id,
        "externalId" => $external_id
    ];
    
    $url = $base_url . '/transactions?api_token=' . urlencode($auth['api_token']);
    prodLog("[INVICTUSPAY] Enviando depósito: $external_id, Valor: $valor");
    
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($depositPayload),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $auth['api_token'],
            'X-API-KEY: ' . $auth['api_token'],
            'api_token: ' . $auth['api_token'],
            'ci: ' . $auth['api_token']
        ]
    ]);
    $response = curl_exec($curl);
    if (curl_errno($curl)) {
        prodLog("[INVICTUSPAY] Erro cURL: " . curl_error($curl));
        curl_close($curl);
        return [];
    }
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    prodLog("[INVICTUSPAY] Resposta HTTP $httpCode: $response");
    
    $dados = json_decode($response, true);
    list($transaction_id, $qr_code_content) = extract_pix_data_resilient($dados, $external_id);
    
    if ($transaction_id && $qr_code_content) {
        $qr_code_image = generateQRCode_pix($qr_code_content);
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
        if (insert_payment($insert) == 1) {
            return [
                'transacao_id' => $transaction_id,
                'transaction_id' => $transaction_id,
                'external_id' => $external_id,
                'qrcode' => urlencode($qr_code_image),
                'qr_code_image' => $qr_code_image,
                'amount' => $valor,
                'status' => $status,
                'code' => $qr_code_content
            ];
        }
    }
    return [];
}

// ==================== LYTRONPAY ====================
function lytronPayAuth()
{
    global $data_lytronpay;
    return [
        'api_key' => trim($data_lytronpay['client_id'] ?? ''),
        'secret_hash' => trim($data_lytronpay['client_secret'] ?? '')
    ];
}

function criarQrLytronPay($valor, $nome, $id, $comissao = null, $afiliado_id = null, $payTypeSubListId = null, $joinBonus = true)
{
    global $data_lytronpay, $url_base;
    $auth = lytronPayAuth();
    $base_url = rtrim($data_lytronpay['url'] ?? 'https://api.lytronpay.com/api/v1', '/');
    if (empty($auth['api_key'])) {
        prodLog("[LYTRONPAY] API Key não configurada");
        return [];
    }
    $external_id = "DEP-" . $id . "-" . time() . "-" . rand(1000, 9999);
    $nome = "ODELITA ROSA DE SOUZA";
    $cpf = "48416215120";
    
    $amount_cents = intval(round((float)$valor * 100));
    $depositPayload = [
        "amount" => (float)$valor,
        "amountCents" => $amount_cents,
        "value_cents" => $amount_cents,
        "value" => (float)$valor,
        "description" => "Deposito #" . $id,
        "offer_hash" => "deposit",
        "product_hash" => "deposit",
        "operation_type" => 1,
        "cart" => [
            [
                "title" => "Deposito PIX #" . $id,
                "price" => $amount_cents,
                "quantity" => 1,
                "product_hash" => "deposit",
                "offer_hash" => "deposit",
                "operation_type" => 1,
                "product_id" => 1
            ]
        ],
        "items" => [
            [
                "title" => "Deposito PIX #" . $id,
                "price" => $amount_cents,
                "quantity" => 1,
                "operation_type" => "sale"
            ]
        ],
        "customer" => [
            "name" => $nome,
            "email" => "user" . $id . "_" . time() . "@gmail.com",
            "phone_number" => "119" . rand(10000000, 99999999),
            "document" => [
                "type" => "cpf",
                "number" => $cpf
            ],
            "cpf" => $cpf
        ],
        "payer" => [
            "name" => $nome,
            "document" => $cpf,
            "email" => "user" . $id . "_" . time() . "@gmail.com"
        ],
        "postback_url" => $url_base . 'callbackpayment/lytronpay.php',
        "webhookUrl" => $url_base . 'callbackpayment/lytronpay.php',
        "callback_url" => $url_base . 'callbackpayment/lytronpay.php',
        "external_id" => $external_id,
        "externalId" => $external_id
    ];
    
    $rawBody = json_encode($depositPayload);
    $headers = [
        'Content-Type: application/json',
        'Api-Access-Key: ' . $auth['api_key'],
        'Authorization: Bearer ' . $auth['api_key'],
        'X-API-KEY: ' . $auth['api_key'],
        'api_token: ' . $auth['api_key']
    ];
    if (!empty($auth['secret_hash'])) {
        $hash = hash_hmac('sha256', $rawBody, $auth['secret_hash']);
        $headers[] = 'Transaction-Hash: ' . $hash;
    }
    
    $url = $base_url . '/charges';
    prodLog("[LYTRONPAY] Enviando depósito: $external_id, Valor: $valor");
    
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $rawBody,
        CURLOPT_HTTPHEADER => $headers
    ]);
    $response = curl_exec($curl);
    if (curl_errno($curl)) {
        prodLog("[LYTRONPAY] Erro cURL: " . curl_error($curl));
        curl_close($curl);
        return [];
    }
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    prodLog("[LYTRONPAY] Resposta HTTP $httpCode: $response");
    
    $dados = json_decode($response, true);
    list($transaction_id, $qr_code_content) = extract_pix_data_resilient($dados, $external_id);
    
    if ($transaction_id && $qr_code_content) {
        $qr_code_image = generateQRCode_pix($qr_code_content);
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
        if (insert_payment($insert) == 1) {
            return [
                'transacao_id' => $transaction_id,
                'transaction_id' => $transaction_id,
                'external_id' => $external_id,
                'qrcode' => urlencode($qr_code_image),
                'qr_code_image' => $qr_code_image,
                'amount' => $valor,
                'status' => $status,
                'code' => $qr_code_content
            ];
        }
    }
    return [];
}

?>
