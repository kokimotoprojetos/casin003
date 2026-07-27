<?php
/**
 * Script de Carregamento de Jogos - MaxAPIGames v2
 * Versão 4.0 - Controle por Provedor Específico (usa o mesmo fluxo do iGameWin legado)
 * Use $PROVIDER_CODE com o nome EXATO retornado por provider_list da MaxAPIGames
 * (ex.: 'PG Soft', 'JILI', 'JDB', 'WG', 'PP'). Deixe vazio ('') para processar TODOS de uma vez.
 */

// ============================================
// CONFIGURAÇÕES DO PROVEDOR - EDITAR AQUI!
// ============================================
// Código exato do provedor da MaxAPIGames (deixe vazio para importar todos)
$PROVIDER_CODE = '';  // ex.: 'PG Soft', 'JILI', 'JDB', 'WG', 'PP' — ou '' para todos

// Nome de exibição (informativo, usado nos logs)
$PROVIDER_NAME = '';

// ============================================
// MAPEAMENTO MaxAPI -> Provedores locais (tabela `provedores.code`)
// ============================================
// MaxAPI retorna nomes como "PG Soft", "JILI", etc. Mapeamos para os códigos
// que a home do cassino já espera (ver api/v1/api.php home.platformList/home.list).
function mapMaxProviderCode($maxCode) {
    $map = [
        'PG Soft' => 'KKGAME',
        'PGSOFT'  => 'KKGAME',
        'PG'      => 'KKGAME',
        'JILI'    => 'slot-jili',
        'JDB'     => 'ONE_API_JDB',
        'WG'      => 'wg',
        'PP'      => 'PP',
    ];
    $key = trim($maxCode);
    return $map[$key] ?? $key;
}

// ============================================
// CONFIGURAÇÕES ANTI-TIMEOUT
// ============================================
ignore_user_abort(true);
set_time_limit(0);
ini_set('max_execution_time', 0);
ini_set('memory_limit', '512M');

// ============================================
// CONFIGURAÇÕES DE HEADERS
// ============================================
header('Content-Type: text/html; charset=utf-8');
header('X-Accel-Buffering: no');
header('Cache-Control: no-cache');
header('Connection: keep-alive');

// Flush imediato
ob_implicit_flush(true);
ob_end_flush();

// ============================================
// CONEXÃO COM BANCO: reaproveita o database.php do projeto
// (evita hardcode de credenciais que ficam desatualizadas entre hosts)
// Inclusão é feita aqui no topo para que $mysqli seja global em todo o script
// ============================================
include_once __DIR__ . '/admin/services/database.php';

// ============================================
// CONFIGURAÇÕES DA API
// ============================================
define('API_URL', 'https://maxapigames.com/api/v2');
define('API_BASE', 'https://maxapigames.com');
define('AGENT_CODE', 'ag_mp8onzrk5369d1aa');
define('AGENT_TOKEN', '397e87f04fac0b9198c54c414a56b81aefc7ec99444bb64953c2268a6d13dc0e');

// ============================================
// CONFIGURAÇÕES DE DIRETÓRIOS
// ============================================
define('BANNER_DIR', $_SERVER['DOCUMENT_ROOT'] . '/uploads');
define('FALLBACK_BANNER', '/uploads/no-image.png');

// ============================================
// CONFIGURAÇÕES DE DEBUG
// ============================================
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ============================================
// VARIÁVEIS GLOBAIS DE CONTROLE
// ============================================
$globalStats = [
    'games_added' => 0,
    'games_skipped' => 0,
    'games_failed' => 0,
    'provider_added' => 0,
    'provider_exists' => 0,
    'start_time' => microtime(true)
];

/**
 * Função de log com flush imediato
 */
function logMessage($message, $type = 'INFO') {
    $timestamp = date('H:i:s');
    $output = "[$timestamp] [$type] $message\n";
    echo $output;
    flush();
    
    // Log em arquivo também
    $logFile = __DIR__ . '/daanrox.txt';
    file_put_contents($logFile, date('Y-m-d H:i:s') . " [$type] $message\n", FILE_APPEND);
}

/**
 * Manter conexão ativa (anti-timeout)
 */
function keepAlive() {
    echo " ";
    flush();
}

/**
 * Conectar ao banco reaproveitando o database.php do projeto
 * (database.php é incluído no topo do script para criar $mysqli no escopo global)
 */
function connectDB($retries = 3) {
    global $mysqli;
    logMessage("Reutilizando conexao mysqli criada pelo database.php...");
    if (!isset($mysqli) || $mysqli->connect_errno) {
        die("ERRO FATAL: database.php não inicializou a conexão\n");
    }
    $mysqli->set_charset('utf8mb4');
    $mysqli->query("SET SESSION wait_timeout = 28800");
    $mysqli->query("SET SESSION interactive_timeout = 28800");
    logMessage("✓ Conectado ao banco de dados com sucesso!", 'SUCCESS');
    return $mysqli;
}

/**
 * Criar diretórios necessários
 */
function createDirectories() {
    logMessage("Verificando estrutura de diretórios...");
    
    // Criar diretório principal
    if (!file_exists(BANNER_DIR)) {
        if (mkdir(BANNER_DIR, 0755, true)) {
            logMessage("✓ Diretório criado: " . BANNER_DIR, 'SUCCESS');
        } else {
            logMessage("✗ Erro ao criar diretório: " . BANNER_DIR, 'ERROR');
            return false;
        }
    }
    
    // Criar imagem de fallback
    $fallbackFile = $_SERVER['DOCUMENT_ROOT'] . FALLBACK_BANNER;
    if (!file_exists($fallbackFile)) {
        createFallbackImage($fallbackFile);
    }
    
    return true;
}

/**
 * Criar imagem de fallback
 */
function createFallbackImage($filepath) {
    if (!function_exists('imagecreate')) {
        logMessage("GD não disponível, criando placeholder SVG", 'WARNING');
        $svg = '<?xml version="1.0" encoding="UTF-8"?>
<svg width="200" height="200" xmlns="http://www.w3.org/2000/svg">
    <rect width="200" height="200" fill="#333"/>
    <text x="50%" y="50%" fill="#fff" font-size="16" text-anchor="middle" dy=".3em">No Image</text>
</svg>';
        file_put_contents($filepath, $svg);
        return;
    }
    
    $im = imagecreate(200, 200);
    $bgColor = imagecolorallocate($im, 50, 50, 50);
    $textColor = imagecolorallocate($im, 255, 255, 255);
    imagestring($im, 5, 60, 90, 'No Image', $textColor);
    imagepng($im, $filepath);
    imagedestroy($im);
    logMessage("✓ Imagem fallback criada", 'SUCCESS');
}

/**
 * Fazer requisição para API com retry e timeout controlado
 */
function apiRequest($method, $providerCode = null, $retries = 3) {
    $data = [
        "method" => $method,
        "agent_code" => AGENT_CODE,
        "agent_token" => AGENT_TOKEN
    ];
    
    if ($providerCode) {
        $data["provider_code"] = $providerCode;
    }
    
    $jsonData = json_encode($data);
    
    $attempt = 0;
    while ($attempt < $retries) {
        keepAlive();
        
        try {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => API_URL,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $jsonData,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Accept: application/json',
                    'Content-Length: ' . strlen($jsonData)
                ],
                CURLOPT_TIMEOUT => 60,
                CURLOPT_CONNECTTIMEOUT => 30,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 3
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            
            if ($curlError) {
                $attempt++;
                logMessage("Tentativa $attempt - Erro cURL: $curlError", 'WARNING');
                sleep(2);
                continue;
            }
            
            if ($httpCode != 200) {
                $attempt++;
                logMessage("Tentativa $attempt - HTTP $httpCode", 'WARNING');
                sleep(2);
                continue;
            }
            
            $data = json_decode($response, true);
            
            if (!$data || !isset($data['status'])) {
                $attempt++;
                logMessage("Tentativa $attempt - Resposta inválida", 'WARNING');
                sleep(2);
                continue;
            }
            
            if ($data['status'] != 1) {
                logMessage("API retornou status: " . $data['status'] . " - " . ($data['msg'] ?? 'Sem mensagem'), 'WARNING');
                return null;
            }
            
            return $data;
            
        } catch (Exception $e) {
            $attempt++;
            logMessage("Tentativa $attempt - Exceção: " . $e->getMessage(), 'ERROR');
            sleep(2);
        }
    }
    
    logMessage("Falha após $retries tentativas para método: $method", 'ERROR');
    return null;
}

/**
 * Obter informações de um provedor específico
 */
function getProviderInfo($providerCode) {
    logMessage("Buscando informações do provedor: $providerCode...");
    
    // Primeiro tenta obter a lista completa (requisição pequena)
    $response = apiRequest("provider_list");
    
    if (!$response || !isset($response['providers'])) {
        logMessage("Falha ao obter lista de provedores", 'ERROR');
        return null;
    }
    
    // Procurar o provedor específico na lista
    foreach ($response['providers'] as $provider) {
        if ($provider['code'] === $providerCode && isset($provider['status']) && $provider['status'] == 1) {
            logMessage("✓ Provedor encontrado: " . $provider['name'], 'SUCCESS');
            return $provider;
        }
    }
    
    logMessage("✗ Provedor '$providerCode' não encontrado ou inativo!", 'ERROR');
    return null;
}

/**
 * Verificar e inserir provedor na tabela provedores
 */
function checkAndInsertProvider($conn, $providerCode, $providerName) {
    global $globalStats;
    
    logMessage("Verificando se o provedor $providerCode existe na tabela provedores...");
    
    // Verificar se o provedor já existe
    $stmt = $conn->prepare("SELECT id FROM provedores WHERE code = ? LIMIT 1");
    $stmt->bind_param("s", $providerCode);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        logMessage("✓ Provedor já existe com ID: " . $row['id'], 'SUCCESS');
        $globalStats['provider_exists']++;
        return $row['id'];
    }
    
    // Se não existe, inserir novo provedor
    logMessage("Provedor não encontrado. Inserindo na tabela provedores...");
    
    // Buscar logo do provedor (usando lista de exemplo ou fallback)
    $logo = getProviderLogo($providerCode);
    
    // Preparar inserção
    $type = 'slot';
    $status = 1;
    
    $insertStmt = $conn->prepare("INSERT INTO provedores (code, name, type, status, logo) VALUES (?, ?, ?, ?, ?)");
    $insertStmt->bind_param("sssss", $providerCode, $providerName, $type, $status, $logo);
    
    if ($insertStmt->execute()) {
        $newId = $conn->insert_id;
        logMessage("✓ Provedor inserido com sucesso! ID: $newId", 'SUCCESS');
        $globalStats['provider_added']++;
        return $newId;
    } else {
        logMessage("✗ Erro ao inserir provedor: " . $insertStmt->error, 'ERROR');
        return null;
    }
}

/**
 * Obter URL do logo do provedor (baseado na lista de exemplo)
 */
function getProviderLogo($providerCode) {
    // Mapeamento de logos baseado na lista de exemplo fornecida
    $logos = [
        'KKGAME' => 'https://upload-us.f-1-g-h.com/s1/1749118444385/PG.svg',
        'PP' => 'https://upload-us.bcbd123.com/1737463534536/PPlogo_2.svg',
        'PLAYSON' => 'https://upload-us.f-1-g-h.com/s1/1756098034177/playson.svg',
        'ONE_API_JDB' => 'https://upload-us.bcbd123.com/1721214234733/JDB.svg',
        'G759' => 'https://upload-us.bcbd123.com/s1/1747906061845/759.svg',
        'ONE_API_Tada' => 'https://upload-us.bcbd123.com/1721214178671/tada.svg',
        'CP' => 'https://upload-us.bcbd123.com/1740658737043/cp.svg',
        'ONE_API_FaChai' => 'https://upload-us.bcbd123.com/s1/1746524240581/Fachai.svg',
        'SPRIBE' => 'https://upload-us.bcbd123.com/1721214273694/Spribe.svg',
        'WHITECLIFF_Evolution' => 'https://upload-us.f-1-g-h.com/s1/1761635456512/Evolution2.svg',
        'PANDA' => 'https://upload-us.f-1-g-h.com/s1/1767697202660/PDgame.svg',
        'inout' => 'https://upload-us.f-1-g-h.com/s1/1762157841255/inout.svg',
        'POPOK' => 'https://upload-us.f-1-g-h.com/s1/1750833653232/Game_popk.svg',
        'FASTSPIN' => 'https://upload-us.f-1-g-h.com/s1/1763716795682/fast-spin.svg',
        'RUBYPLAY' => 'https://upload-us.f-1-g-h.com/s1/1758777485523/Rubyplay.svg',
        'slot-nolimitcity' => '/logo.png.webp' // Fallback para este provedor
    ];

    return $logos[$providerCode] ?? '/logo.png.webp';
}

/**
 * Verificar se jogo já existe no banco (otimizado com cache)
 */
function gameExists($conn, $gameCode, $providerCode) {
    static $cache = [];
    static $stmt = null;
    
    $cacheKey = $providerCode . '_' . $gameCode;
    
    if (isset($cache[$cacheKey])) {
        return $cache[$cacheKey];
    }
    
    if (!$stmt) {
        $stmt = $conn->prepare("SELECT id FROM games WHERE game_code = ? AND provider = ? LIMIT 1");
    }
    
    $stmt->bind_param("ss", $gameCode, $providerCode);
    $stmt->execute();
    $stmt->store_result();
    $exists = $stmt->num_rows > 0;
    
    $cache[$cacheKey] = $exists;
    return $exists;
}

/**
 * Inserir jogo no banco de dados
 */
function insertGame($conn, $game, $providerCode, $bannerPath) {
    global $globalStats;
    
    static $stmt = null;
    
    if (!$stmt) {
        $sql = "INSERT INTO games (game_code, game_name, banner, status, provider, popular, type, game_type, api) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            logMessage("Erro ao preparar statement: " . $conn->error, 'ERROR');
            return false;
        }
    }
    
    $gameCode = $game['game_code'];
    $gameName = sanitizeString($game['game_name']);
    $status = 1; // Status sempre 1 conforme solicitado
    $popular = 1; // popular sempre 1 conforme solicitado
    $type = 'slot'; // type sempre 'slot' conforme solicitado
    $gameType = 'ELECTRONIC'; // game_type sempre 'ELECTRONIC' conforme solicitado
    $api = 'igamewin'; // api sempre 'igamewin' conforme solicitado
    
    $stmt->bind_param("sssssssss", 
        $gameCode, 
        $gameName, 
        $bannerPath, 
        $status, 
        $providerCode,
        $popular, 
        $type, 
        $gameType, 
        $api
    );
    
    if (!$stmt->execute()) {
        logMessage("Erro ao inserir jogo $gameCode: " . $stmt->error, 'ERROR');
        $globalStats['games_failed']++;
        return false;
    }
    
    $globalStats['games_added']++;
    return true;
}

/**
 * Sanitizar string para UTF-8
 */
function sanitizeString($string) {
    $string = preg_replace('/[\x00-\x1F\x7F]/u', '', $string);
    
    if (!mb_check_encoding($string, 'UTF-8')) {
        $string = mb_convert_encoding($string, 'UTF-8', 'UTF-8');
    }
    
    return trim($string);
}

/**
 * Processar jogos de um provedor específico
 * $maxCode = código exato retornado pela MaxAPI (ex.: 'PG Soft')
 */
function processProviderGames($conn, $maxCode) {
    global $globalStats;

    $localCode = mapMaxProviderCode($maxCode);

    logMessage("========================================");
    logMessage("Processando jogos do provedor: $maxCode (local: $localCode)");
    logMessage("========================================");

    // Buscar jogos do provedor na MaxAPI usando o código exato dela
    $response = apiRequest("game_list", $maxCode);

    if (!$response || !isset($response['games'])) {
        logMessage("✗ Falha ao obter jogos do provedor $maxCode", 'ERROR');
        return false;
    }

    $games = $response['games'];
    $total = count($games);
    $processed = 0;
    $added = 0;
    $skipped = 0;

    logMessage("Total de jogos encontrados: $total");

    foreach ($games as $index => $game) {
        keepAlive();

        $gameCode = $game['game_code'];
        $gameName = $game['game_name'];
        $processed++;

        // Verificar duplicata (usando o código local pra bater com api/v1/api.php)
        if (gameExists($conn, $gameCode, $localCode)) {
            $skipped++;
            $globalStats['games_skipped']++;

            if ($skipped % 50 == 0) {
                logMessage("Pulados: $skipped jogos (já existem no banco)");
            }
            continue;
        }

        // Banner: MaxAPI retorna caminho relativo (/game/.../thumbnail.png).
        // Prepende a base se for relativo. Aceita também URL absoluta.
        $bannerPath = $game['banner'] ?? '';
        if (!empty($bannerPath) && strpos($bannerPath, 'http') !== 0) {
            $bannerPath = API_BASE . '/' . ltrim($bannerPath, '/');
        }
        if (empty($bannerPath)) {
            $bannerPath = FALLBACK_BANNER;
        }

        // Inserir no banco com o código local (para casar com a tabela `provedores`)
        if (insertGame($conn, $game, $localCode, $bannerPath)) {
            $added++;
            
            // Log a cada 10 jogos adicionados
            if ($added % 10 == 0) {
                $percent = round(($processed / $total) * 100, 1);
                logMessage("Progresso: $processed/$total ($percent%) - Adicionados: $added | Pulados: $skipped");
                
                // Mostrar exemplo do último jogo adicionado
                if ($added % 50 == 0) {
                    logMessage("Último adicionado: $gameName ($gameCode) - Banner: " . substr($bannerPath, 0, 50) . "...");
                }
            }
        }
        
        // Pequena pausa para não sobrecarregar
        if ($processed % 5 == 0) {
            usleep(100000); // 0.1 segundo
        }
    }
    
    logMessage("✓ Processamento de jogos do provedor $maxCode concluído!", 'SUCCESS');
    logMessage("  - Adicionados: $added");
    logMessage("  - Pulados (já existentes): $skipped");
    
    return true;
}

/**
 * Exibir estatísticas finais
 */
function displayFinalStats() {
    global $globalStats;
    
    $duration = round(microtime(true) - $globalStats['start_time'], 2);
    $minutes = floor($duration / 60);
    $seconds = (int)$duration % 60;
    
    logMessage("");
    logMessage("========================================");
    logMessage("PROCESSAMENTO CONCLUÍDO!");
    logMessage("========================================");
    logMessage("Provedores:");
    logMessage("  - Inseridos: " . $globalStats['provider_added']);
    logMessage("  - Já existentes: " . $globalStats['provider_exists']);
    logMessage("");
    logMessage("Jogos:");
    logMessage("  - Adicionados: " . $globalStats['games_added']);
    logMessage("  - Pulados (duplicados): " . $globalStats['games_skipped']);
    logMessage("  - Com erro: " . $globalStats['games_failed']);
    logMessage("");
    logMessage("Tempo total: {$minutes}m {$seconds}s");
    logMessage("Data/Hora: " . date('Y-m-d H:i:s'));
    logMessage("========================================");
    
    // Dica para próximo provedor
    logMessage("");
    logMessage("📌 PRÓXIMO PASSO:");
    logMessage("   Para processar outro provedor, altere as variáveis:");
    logMessage("   \$PROVIDER_CODE e \$PROVIDER_NAME no início do script");
    logMessage("   e execute novamente.");
    logMessage("========================================");
}

/**
 * Função principal
 * Se $providerCode estiver vazio: busca provider_list na MaxAPI e processa todos.
 * Caso contrário: processa apenas o provedor informado.
 */
function main($providerCode, $providerName) {
    logMessage("");
    logMessage("╔══════════════════════════════════════════════════════╗");
    logMessage("║  MAXAPIGAMES - IMPORTAÇÃO DE JOGOS                   ║");
    logMessage("║  Versão 4.0 - MaxAPI v2                              ║");
    logMessage("╚══════════════════════════════════════════════════════╝");
    logMessage("");

    if (!createDirectories()) {
        die("ERRO FATAL: Não foi possível criar diretórios necessários\n");
    }

    $conn = connectDB();

    // Modo "todos": $providerCode vazio
    if (empty($providerCode)) {
        logMessage("📌 MODO: importar TODOS os provedores retornados por provider_list");
        $resp = apiRequest('provider_list');
        if (!$resp || !isset($resp['providers'])) {
            logMessage("❌ Falha ao buscar provider_list. Abortando.", 'ERROR');
            $conn->close();
            return;
        }
        foreach ($resp['providers'] as $prov) {
            if (!isset($prov['status']) || $prov['status'] != 1) {
                logMessage("⏭️ Pulando '{$prov['code']}' (status != 1)");
                continue;
            }
            $maxCode = $prov['code'];
            $maxName = $prov['name'] ?? $maxCode;
            $localCode = mapMaxProviderCode($maxCode);
            $providerId = checkAndInsertProvider($conn, $localCode, $maxName);
            if ($providerId) {
                processProviderGames($conn, $maxCode);
            }
        }
        $conn->close();
        displayFinalStats();
        return;
    }

    // Modo "um provedor": processa apenas o informado
    logMessage("📌 PROVEDOR SELECIONADO:");
    logMessage("   Código (MaxAPI): $providerCode");
    logMessage("   Nome: " . ($providerName ?: $providerCode));
    logMessage("");

    $localCode = mapMaxProviderCode($providerCode);
    $providerId = checkAndInsertProvider($conn, $localCode, $providerName ?: $providerCode);

    if (!$providerId) {
        logMessage("❌ Falha ao processar provedor. Abortando...", 'ERROR');
        $conn->close();
        return;
    }

    logMessage("");
    logMessage("📊 STATUS DO PROVEDOR NO BANCO:");
    logMessage("   ID: " . $providerId);
    logMessage("   Código local: " . $localCode);
    logMessage("   Código MaxAPI: " . $providerCode);
    logMessage("   Nome: " . ($providerName ?: $providerCode));
    logMessage("   Type: slot");
    logMessage("   Status: 1 (Ativo)");
    logMessage("");

    $success = processProviderGames($conn, $providerCode);
    $conn->close();
    displayFinalStats();

    if ($success) {
        logMessage("");
        logMessage("✅ SUGESTÃO: Para processar outro provedor, altere \$PROVIDER_CODE no início");
        logMessage("   ou deixe vazio ('') para importar todos de uma vez.");
    }
}

// ============================================
// EXECUTAR SCRIPT COM O PROVEDOR ESPECIFICADO
// ============================================
try {
    main($PROVIDER_CODE, $PROVIDER_NAME);
} catch (Exception $e) {
    logMessage("ERRO FATAL: " . $e->getMessage(), 'ERROR');
    logMessage("Stack trace: " . $e->getTraceAsString(), 'ERROR');
}

?>