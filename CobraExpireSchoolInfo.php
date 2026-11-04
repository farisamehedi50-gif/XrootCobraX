<?php
// DAVLOPER BY :- KALYAN KING Telegram : KGF CYBER TEAM LINK : https://t.me/+e5Rr0q5ixnxlNTNl
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// DAVLOPER BY :- KALYAN KING Telegram : KGF CYBER TEAM LINK : https://t.me/+e5Rr0q5ixnxlNTNl

$eiin   = isset($_GET['eiin'])   ? trim($_GET['eiin'])   : '';
$page   = isset($_GET['page'])   ? intval($_GET['page']) : 0;
$size   = isset($_GET['size'])   ? intval($_GET['size']) : 1000;

// DAVLOPER BY :- MehediSanix/MehediSanix2 TEAM LINK : https://t.me/CobraX_Empire
if (empty($eiin)) {
    http_response_code(400);
    echo json_encode(['error' => 'eiin parameter is required'], JSON_UNESCAPED_UNICODE);
    exit();
}

$portalUrl = 'https://emis.gov.bd/EMIS/portalone';

// DAVLOPER BY :- KALYAN KING Telegram : KGF CYBER TEAM LINK : https://t.me/+e5Rr0q5ixnxlNTNl
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL            => $portalUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HEADER         => true,
    CURLOPT_HTTPHEADER     => [
        'User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36',
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        'Accept-Language: en-US,en;q=0.9,bn;q=0.8',
    ],
    CURLOPT_TIMEOUT        => 30,
    CURLOPT_CONNECTTIMEOUT => 15,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_COOKIEJAR      => '/tmp/emis_cookie_' . md5($eiin) . '.txt',
    CURLOPT_COOKIEFILE     => '/tmp/emis_cookie_' . md5($eiin) . '.txt',
    CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_2_0,
]);

$portalResponse = curl_exec($ch);
$portalErr      = curl_error($ch);
curl_close($ch);

// DAVLOPER BY :- KALYAN KING Telegram : KGF CYBER TEAM LINK : https://t.me/+e5Rr0q5ixnxlNTNl
if ($portalErr || empty($portalResponse)) {
    http_response_code(502);
    echo json_encode(['error' => 'Portal fetch failed: ' . $portalErr], JSON_UNESCAPED_UNICODE);
    exit();
}

$csrfToken = '';

// DAVLOPER BY :- KALYAN KING Telegram : KGF CYBER TEAM LINK : https://t.me/+e5Rr0q5ixnxlNTNl
if (preg_match('/<meta[^>]+name=["\']csrf-token["\'][^>]+content=["\']([^"\']+)["\']/', $portalResponse, $m)) {
    $csrfToken = $m[1];
}
if (empty($csrfToken) && preg_match('/<input[^>]+name=["\']__RequestVerificationToken["\'][^>]+value=["\']([^"\']+)["\']/', $portalResponse, $m)) {
    $csrfToken = $m[1];
}
if (empty($csrfToken) && preg_match('/CSRF-TOKEN=([^;\s]+)/', $portalResponse, $m)) {
    $csrfToken = $m[1];
}
if (empty($csrfToken) && preg_match('/["\']csrf["\'\s]*:[\s]*["\']([^"\']+)["\']/', $portalResponse, $m)) {
    $csrfToken = $m[1];
}

// DAVLOPER BY :- KALYAN KING Telegram : KGF CYBER TEAM LINK : https://t.me/+e5Rr0q5ixnxlNTNl
if (empty($csrfToken)) {
    http_response_code(502);
    echo json_encode([
        'error' => 'Could not extract CSRF token from portal',
        'hint'  => 'Portal page structure may have changed',
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

$apiUrl  = 'https://emis.gov.bd/emis/Portal/GetTeacherDetails';
$postData = http_build_query([
    'instituteId' => '',
    'EIIN'        => $eiin,
    'isTeacher'   => '',
]);

// DAVLOPER BY :- KALYAN KING Telegram : KGF CYBER TEAM LINK : https://t.me/+e5Rr0q5ixnxlNTNl
$ch2 = curl_init();
curl_setopt_array($ch2, [
    CURLOPT_URL            => $apiUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $postData,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
        'Accept: application/json, text/javascript, */*; q=0.01',
        'Accept-Language: en-US,en;q=0.9,bn;q=0.8',
        'User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36',
        'Origin: https://emis.gov.bd',
        'Referer: https://emis.gov.bd/EMIS/portalone',
        'X-Requested-With: XMLHttpRequest',
        'X-CSRF-TOKEN: ' . $csrfToken,
        'sec-ch-ua: "Not(A:Brand";v="8", "Chromium";v="144", "Google Chrome";v="144"',
        'sec-ch-ua-mobile: ?0',
        'sec-ch-ua-platform: "Linux"',
        'Sec-Fetch-Site: same-origin',
        'Sec-Fetch-Mode: cors',
        'Sec-Fetch-Dest: empty',
    ],
    CURLOPT_TIMEOUT        => 30,
    CURLOPT_CONNECTTIMEOUT => 15,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_ENCODING       => '',
    CURLOPT_COOKIEFILE     => '/tmp/emis_cookie_' . md5($eiin) . '.txt',
    CURLOPT_COOKIEJAR      => '/tmp/emis_cookie_' . md5($eiin) . '.txt',
    CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_2_0,
]);

$apiResponse = curl_exec($ch2);
$httpCode    = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
$apiErr      = curl_error($ch2);
curl_close($ch2);

// DAVLOPER BY :- KALYAN KING Telegram : KGF CYBER TEAM LINK : https://t.me/+e5Rr0q5ixnxlNTNl
@unlink('/tmp/emis_cookie_' . md5($eiin) . '.txt');

if ($apiErr) {
    http_response_code(502);
    echo json_encode(['error' => 'API call failed: ' . $apiErr], JSON_UNESCAPED_UNICODE);
    exit();
}

$data = json_decode($apiResponse, true);

// DAVLOPER BY :- KALYAN KING Telegram : KGF CYBER TEAM LINK : https://t.me/+e5Rr0q5ixnxlNTNl
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(502);
    echo json_encode([
        'error'       => 'Non-JSON response',
        'httpCode'    => $httpCode,
        'rawResponse' => substr($apiResponse, 0, 300),
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

// DAVLOPER BY :- KALYAN KING Telegram : KGF CYBER TEAM LINK : https://t.me/+e5Rr0q5ixnxlNTNl
foreach ($data as &$emp) {
    $img = $emp['Image'] ?? '-';

    if ($img === '-' || empty($img)) {
        $emp['Image'] = null;
        continue;
    }

    if (preg_match("/href='([^']+)'/", $img, $match)) {
        $path = $match[1];
        if (strpos($path, 'http') === 0) {
            $emp['Image'] = $path;
        } else {
            $emp['Image'] = 'https://emis.gov.bd' . $path;
        }
    } else {
        $emp['Image'] = null;
    }
}
unset($emp);

// DAVLOPER BY :- KALYAN KING Telegram : KGF CYBER TEAM LINK : https://t.me/+e5Rr0q5ixnxlNTNl
http_response_code(200);
echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
