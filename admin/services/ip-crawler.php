<?php
// IP Crawler - Browser/OS detection
$browser = "Unknown Browser";
$os = "Unknown OS Platform";

if (isset($_SERVER['HTTP_USER_AGENT'])) {
    $ua = $_SERVER['HTTP_USER_AGENT'];
    
    // Browser detection
    if (strpos($ua, 'Firefox') !== false) $browser = "Firefox";
    elseif (strpos($ua, 'Edg') !== false) $browser = "Edge";
    elseif (strpos($ua, 'Chrome') !== false) $browser = "Chrome";
    elseif (strpos($ua, 'Safari') !== false) $browser = "Safari";
    elseif (strpos($ua, 'Opera') !== false || strpos($ua, 'OPR') !== false) $browser = "Opera";
    
    // OS detection
    if (strpos($ua, 'Windows') !== false) $os = "Windows";
    elseif (strpos($ua, 'Mac OS') !== false) $os = "Mac OS";
    elseif (strpos($ua, 'Linux') !== false) $os = "Linux";
    elseif (strpos($ua, 'Android') !== false) $os = "Android";
    elseif (strpos($ua, 'iPhone') !== false || strpos($ua, 'iPad') !== false) $os = "iOS";
}

function ip_F($ip) {
    $data = @file_get_contents("http://ip-api.com/json/{$ip}?fields=country,countryCode,region,regionName,city,zip,lat,lon,timezone,isp,org,as,mobile,proxy,hosting,query");
    if ($data) {
        $json = json_decode($data, true);
        if ($json && $json['status'] === 'success') {
            return [
                'pais' => $json['country'] ?? '',
                'cidade' => $json['city'] ?? '',
                'regiao' => $json['regionName'] ?? '',
            ];
        }
    }
    return ['pais' => '', 'cidade' => '', 'regiao' => ''];
}
?>
