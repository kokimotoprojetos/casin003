<?php
header('Content-Type: text/plain');
$dir = __DIR__ . '/../uploads/game-logos';
if (!is_dir($dir)) mkdir($dir, 0755, true);

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    // Fetch from API
    $ch = curl_init('https://casin003.vercel.app/api/frontend/trpc/game.list');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, '{}');
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $resp = curl_exec($ch);
    curl_close($ch);
    $data = json_decode($resp, true);
}

$games = $data['result']['data']['json'] ?? [];
$count = 0;
$errors = [];

foreach ($games as $g) {
    foreach ($g['gameList'] ?? [] as $game) {
        $logo = $game['logo'] ?? '';
        $name = $game['name'] ?? '';
        $code = $game['code'] ?? '';
        $plat = $game['platformCode'] ?? '';
        if (!$logo) continue;
        
        $ext = pathinfo(parse_url($logo, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'png';
        $fname = "{$plat}_{$code}.{$ext}";
        $path = "$dir/$fname";
        
        if (file_exists($path) && filesize($path) > 100) {
            echo "EXISTS: $fname\n";
            continue;
        }
        
        $img = @file_get_contents($logo);
        if ($img === false) {
            $errors[] = "FAIL: $name → $logo";
            continue;
        }
        
        file_put_contents($path, $img);
        echo "OK: $fname (" . strlen($img) . "B)\n";
        $count++;
    }
}

echo "\nDownloaded: $count\nErrors: " . count($errors) . "\n";
foreach ($errors as $e) echo "$e\n";
