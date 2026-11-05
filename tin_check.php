<?php
declare(strict_types=1);
error_reporting(0);
ini_set('display_errors', '0');

// ==========================================================
// [CONFIG & CENTRAL SYSTEM LOAD]
// ==========================================================
$configFile = __DIR__ . '/config.json';
if (!file_exists($configFile)) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Configuration file missing! Please open admin.php first."], JSON_UNESCAPED_UNICODE);
    exit;
}

$config = json_decode(file_get_contents($configFile), true);
$api_active = $config['api_active'] ?? true;
$telegram_channel = $config['telegram_channel'] ?? "https://t.me/CobraX_Empire";
$keys_database = $config['keys'] ?? [];

// ==========================================================
// [HTML RESPONSIVE NOTICE RENDERER (Master Switch Handler)]
// ==========================================================
function renderMasterNotice(string $title, string $message, string $tgLink): void {
    header('Content-Type: text/html; charset=utf-8');
    echo <<<HTML
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$title}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, sans-serif; }
        body { background: #090d16; color: #f1f5f9; display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 20px; }
        .master-card { background: #111827; border: 1px solid #1f2937; border-radius: 20px; padding: 40px 30px; max-width: 480px; width: 100%; text-align: center; box-shadow: 0 20px 35px -10px rgba(0, 0, 0, 0.7); }
        .badge { display: inline-block; background: rgba(244, 63, 94, 0.15); color: #f43f5e; padding: 6px 14px; border-radius: 50px; font-size: 13px; font-weight: 600; margin-bottom: 20px; }
        h2 { color: #f8fafc; font-size: 24px; margin-bottom: 14px; font-weight: 700; }
        p { color: #94a3b8; font-size: 15px; line-height: 1.7; margin-bottom: 30px; }
        .action-btn { display: inline-block; background: linear-gradient(135deg, #2563eb, #1d4ed8); color: #ffffff; text-decoration: none; padding: 14px 28px; border-radius: 12px; font-weight: 600; font-size: 15px; transition: all 0.3s ease; box-shadow: 0 8px 20px rgba(37, 99, 235, 0.4); }
        .action-btn:hover { background: linear-gradient(135deg, #1d4ed8, #1e40af); transform: translateY(-3px); }
        .footer-sig { margin-top: 25px; font-size: 12px; color: #4b5563; }
    </style>
</head>
<body>
    <div class="master-card">
        <div class="badge">TIN VERIFICATION API</div>
        <h2>{$title}</h2>
        <p>{$message}</p>
        <a href="{$tgLink}" target="_blank" class="action-btn">📢 অফিশিয়াল চ্যানেলে যুক্ত হোন</a>
        <div class="footer-sig">Secured by @XrootCodeX | Dev: @MehediSanix</div>
    </div>
</body>
</html>
HTML;
    exit;
}

if (!$api_active) {
    renderMasterNotice(
        "TIN Verification API সাময়িক বন্ধ রয়েছে",
        "এই এপিআইটি বর্তমানে রক্ষণাবেক্ষণের জন্য বন্ধ রাখা হয়েছে। অফিশিয়াল আপডেট পেতে আমাদের টেলিগ্রাম চ্যানেলে যুক্ত থাকুন।",
        $telegram_channel
    );
}

// ==========================================================
// [CENTRAL KEY VALIDATION & LIMIT CHECKING]
// ==========================================================
$provided_key = $_GET['key'] ?? $_GET['token'] ?? '';
if (empty($provided_key) || !isset($keys_database[$provided_key])) {
    renderMasterNotice(
        "অননুমোদিত অ্যাক্সেস (Unauthorized)",
        "আপনার এপিআই কি সঠিক নয় অথবা প্রদান করা হয়নি। সঠিক এপিআই কি ব্যবহার করে আবার চেষ্টা করুন।",
        $telegram_channel
    );
}

$usageDir = __DIR__ . '/api_usage';
if (!is_dir($usageDir)) {
    mkdir($usageDir, 0755, true);
}

$usageFile = $usageDir . '/' . hash('sha256', $provided_key) . '.json';
$current_usage = 0;
if (file_exists($usageFile)) {
    $usageData = json_decode(file_get_contents($usageFile), true);
    if ($usageData && isset($usageData['count'])) {
        $current_usage = (int)$usageData['count'];
    }
}

$max_limit = (int)$keys_database[$provided_key]['limit'];

if ($current_usage >= $max_limit) {
    renderMasterNotice(
        "আপনার ব্যবহারের সীমা শেষ!",
        "আপনার এপিআই কি-এর নির্ধারিত লিমিট শেষ হয়ে গেছে। লিমিট রিচার্জ করতে টেলিগ্রাম চ্যানেলে যোগাযোগ করুন।",
        $telegram_channel
    );
}

// রিকোয়েস্ট কাউন্ট বৃদ্ধি ও সেভ করা
$current_usage++;
@file_put_contents($usageFile, json_encode(["count" => $current_usage]), LOCK_EX);
$balance_remaining = ($max_limit > 900000000) ? 999 : ($max_limit - $current_usage);

header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");

// ==========================================================
// [TIN INPUT & SANITIZATION]
// ==========================================================
$tin = $_GET['tin'] ?? $_GET['number'] ?? $_GET['q'] ?? '';
$tin = preg_replace('/[^0-9]/', '', $tin);

if (empty($tin)) {
    http_response_code(400);
    echo json_encode([
        "success" => false, 
        "message" => "Bad Request. Please provide a valid TIN number using parameter 'tin'."
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ==========================================================
// [STORAGE CACHE CHECK]
// ==========================================================
$storageDir = __DIR__ . '/storage_cache';
if (!is_dir($storageDir)) {
    mkdir($storageDir, 0755, true);
}

$cacheFile = $storageDir . '/tin_' . hash('sha256', $tin) . '.json';

if (file_exists($cacheFile)) {
    $cachedContent = file_get_contents($cacheFile);
    if ($cachedContent !== false) {
        $cachedArray = json_decode($cachedContent, true);
        if (is_array($cachedArray)) {
            $cachedArray['balance_remaining'] = $balance_remaining;
            http_response_code(200);
            echo json_encode($cachedArray, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
    }
}

// ==========================================================
// [NBR LIVE SCRAPING & VERIFICATION ENGINE]
// ==========================================================
$url = "https://nbr.sblesheba.com/IncomeTax/Payment";

$cookieFile = tempnam(sys_get_temp_dir(), 'cookie');
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_COOKIEJAR => $cookieFile,
    CURLOPT_COOKIEFILE => $cookieFile,
    CURLOPT_USERAGENT => "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36",
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_TIMEOUT => 15
]);
$response = curl_exec($ch);

libxml_use_internal_errors(true);
$doc = new DOMDocument();
@$doc->loadHTML($response);
$xpath = new DOMXPath($doc);
$tokenNode = $xpath->query("//input[@name='__RequestVerificationToken']")->item(0);
$token = $tokenNode ? $tokenNode->getAttribute('value') : null;

if (!$token) {
    curl_close($ch);
    @unlink($cookieFile);
    http_response_code(502);
    echo json_encode(["success" => false, "message" => "Unable to fetch verification token from NBR server."], JSON_UNESCAPED_UNICODE);
    exit;
}

$postData = http_build_query([
    '__RequestVerificationToken' => $token,
    'TIN' => $tin,
    'btn' => 'Search',
    'AssessmentYear' => '2025-26'
]);

curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $postData,
    CURLOPT_HTTPHEADER => [
        "Content-Type: application/x-www-form-urlencoded",
        "Referer: $url"
    ]
]);
$html = curl_exec($ch);
curl_close($ch);
@unlink($cookieFile);

$doc = new DOMDocument();
@$doc->loadHTML($html);
$xpath = new DOMXPath($doc);

function getInputValue($xpath, $name) {
    $el = $xpath->query("//input[@name='$name']")->item(0);
    if ($el) {
        $val = trim($el->getAttribute('value'));
        return $val === '' ? null : $val;
    }
    return null;
}

function getTextareaValue($xpath, $name) {
    $el = $xpath->query("//textarea[@name='$name']")->item(0);
    return $el ? trim($el->nodeValue) : null;
}

$applicantName = getInputValue($xpath, "ApplicantName");

if (empty($applicantName)) {
    http_response_code(404);
    echo json_encode([
        "success" => false,
        "balance_remaining" => $balance_remaining,
        "message" => "No records found for this TIN or invalid TIN provided."
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// ==========================================================
// [STRUCTURED JSON RESPONSE PAYLOAD]
// ==========================================================
$responsePayload = [
    "success" => true,
    "balance_remaining" => $balance_remaining,
    "tin_verification_report" => [
        "Tin" => $tin,
        "Name" => $applicantName,
        "Address" => getTextareaValue($xpath, "Address"),
        "TaxZone" => getInputValue($xpath, "TaxZone"),
        "TaxCircle" => getInputValue($xpath, "TaxCircle"),
        "AccountCode" => getInputValue($xpath, "AccountCode"),
        "OldAccountCode" => getInputValue($xpath, "OldAccountCode")
    ],
    "id-summary" => [
        "query_tin" => $tin,
        "note" => "🇧🇩 এনবিআর (NBR) টিআইএন ভেরিফিকেশন সফলভাবে সম্পন্ন হয়েছে।"
    ],
    "developer_info" => [
        "create" => "TG: @XrootCodeX",
        "Developer" => "TG: @MehediSanix"
    ]
];

$jsonEncodedOutput = json_encode($responsePayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

// ক্যাশ ফাইলে সেভ রাখা
@file_put_contents($cacheFile, $jsonEncodedOutput, LOCK_EX);

http_response_code(200);
echo $jsonEncodedOutput;
exit;
?>
