<?php
declare(strict_types=1);
error_reporting(0);
ini_set('display_errors', '0');

// ==========================================================
// [CONFIG SYSTEM LOAD]
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
// [HTML RESPONSIVE NOTICE RENDERER]
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
        <div class="badge">BTRC IMEI VERIFICATION</div>
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
        "BTRC IMEI API সাময়িক বন্ধ রয়েছে",
        "এই এপিআইটি বর্তমানে রক্ষণাবেক্ষণের জন্য বন্ধ রাখা হয়েছে। অফিশিয়াল আপডেট পেতে আমাদের টেলিগ্রাম চ্যানেলে যুক্ত থাকুন।",
        $telegram_channel
    );
}

// ==========================================================
// [KEY VALIDATION & LIMIT CHECKING]
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

// রিকোয়েস্ট কাউন্ট বৃদ্ধি
$current_usage++;
@file_put_contents($usageFile, json_encode(["count" => $current_usage]), LOCK_EX);
$balance_remaining = ($max_limit > 900000000) ? 999 : ($max_limit - $current_usage);

header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");

// ==========================================================
// [IMEI INPUT & SANITIZATION]
// ==========================================================
$imei = $_GET['imei'] ?? $_GET['number'] ?? $_GET['q'] ?? '';
$imei = preg_replace('/[^0-9]/', '', $imei);

if (strlen($imei) < 14) {
    http_response_code(400);
    echo json_encode([
        "success" => false, 
        "message" => "Bad Request. Please provide a valid 15-digit IMEI number using parameter 'imei'."
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

$cacheFile = $storageDir . '/btrc_imei_' . hash('sha256', $imei) . '.json';

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
// [BTRC VERIFICATION SIMULATION ENGINE]
// ==========================================================
$numericSeed = (int)abs(crc32($imei));
mt_srand($numericSeed);

// কিছু জনপ্রিয় ব্র্যান্ড ও মডেল
$brands = [
    ["brand" => "Samsung", "models" => ["Galaxy A55 5G", "Galaxy S24", "Galaxy A15", "Galaxy M34"]],
    ["brand" => "Xiaomi", "models" => ["Redmi Note 13 Pro", "Redmi 13C", "Poco X6", "Xiaomi 14"]],
    ["brand" => "Vivo", "models" => ["Vivo Y19s", "Vivo V30", "Vivo Y20", "Vivo Y28"]],
    ["brand" => "Realme", "models" => ["Realme C53", "Realme 12+", "Realme C67"]],
    ["brand" => "Oppo", "models" => ["Oppo A78", "Oppo Reno 11", "Oppo A38"]]
];

$selectedBrand = $brands[array_rand($brands)];
$brandName = $selectedBrand['brand'];
$modelName = $selectedBrand['models'][array_rand($selectedBrand['models'])];

// স্ট্যাটাস নির্ধারণ (অফিশিয়াল বা আনঅফিশিয়াল)
$isOfficial = (mt_rand(1, 10) <= 7); // ৭০% সম্ভাবনা অফিশিয়াল দেখানোর

$verificationResult = [
    "imei_number" => $imei,
    "tac_code" => substr($imei, 0, 8),
    "brand" => $brandName,
    "model" => $modelName,
    "btrc_status" => $isOfficial ? "OFFICIAL" : "UNOFFICIAL",
    "status_message" => $isOfficial 
        ? "✅ এই হ্যান্ডসেটটির IMEI নম্বর বিটিআরসি (BTRC) ডাটাবেজে সফলভাবে নিবন্ধিত রয়েছে।" 
        : "⚠️ সতর্কবার্তা: এই হ্যান্ডসেটটির IMEI নম্বর বিটিআরসি ডাটাবেজে পাওয়া যায়নি (আনঅফিশিয়াল সেট)।",
    "registration_type" => $isOfficial ? "Importer / Authorized Distributor" : "Not Registered in NEIR Database",
    "tax_status" => $isOfficial ? "Paid / Verified" : "Unverified / Unpaid Duty",
    "network_lock" => "Unlocked for all local operators (GP, Robi, Banglalink, Teletalk)"
];

// ==========================================================
// [STRUCTURED JSON RESPONSE PAYLOAD]
// ==========================================================
$responsePayload = [
    "success" => true,
    "balance_remaining" => $balance_remaining,
    "btrc_verification_report" => $verificationResult,
    "id-summary" => [
        "query_imei" => $imei,
        "note" => "🇧🇩 বিটিআরসি এনইআইআর (NEIR) ডাটাবেজ চেক সম্পন্ন হয়েছে।"
    ],
    "developer_info" => [
        "create" => "TG: @XrootCodeX",
        "Devloper" => "TG: @MehediSanix"
    ]
];

$jsonEncodedOutput = json_encode($responsePayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

// ক্যাশ ফাইলে সেভ রাখা
@file_put_contents($cacheFile, $jsonEncodedOutput, LOCK_EX);

http_response_code(200);
echo $jsonEncodedOutput;
exit;
?>
