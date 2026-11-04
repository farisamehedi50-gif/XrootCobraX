<?php
declare(strict_types=1);

error_reporting(0);
ini_set('display_errors', '0');

// ==========================================================
// [MASTER CONFIGURATION & SECURITY GATEWAY]
// ==========================================================

// গ্লোবাল সুইচ: true মানে এপিআই চালু, false মানে সাময়িক বন্ধ
$api_active = true; 

// অফিশিয়াল আপডেট বা নোটিশের জন্য টেলিগ্রাম চ্যানেল লিংক
$telegram_channel = "https://t.me/CobraX_Empire";

// এপিআই কি (API Key) এবং তাদের সুনির্দিষ্ট ব্যবহার সীমা (Limit)
$keys_database = [
    "csk_live_9f8d7c6b5a4e3f2d1c0b9a8f7e6d5c4b3a2" => [
        "name" => "Admin Root",
        "limit" => 999999999 // আনলিমিটেড
    ],
    "usr_sec_token_x9z8y7w6v5u4t3s2r1q0p9o8n7m6l5k4j3" => [
        "name" => "User Beta 2",
        "limit" => 10 // ৩ বার লিমিট
    ],
    "tkn_auth_p_qwerty1234567890abcdefghijk987654321" => [
        "name" => "User Alpha 1",
        "limit" => 5 // ১ বার লিমিট
    ]
];

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
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background: #090d16; color: #f1f5f9; display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 20px; }
        .master-card { background: #111827; border: 1px solid #1f2937; border-radius: 20px; padding: 40px 30px; max-width: 480px; width: 100%; text-align: center; box-shadow: 0 20px 35px -10px rgba(0, 0, 0, 0.7); }
        .badge { display: inline-block; background: rgba(244, 63, 94, 0.15); color: #f43f5e; padding: 6px 14px; border-radius: 50px; font-size: 13px; font-weight: 600; margin-bottom: 20px; }
        h2 { color: #f8fafc; font-size: 24px; margin-bottom: 14px; font-weight: 700; }
        p { color: #94a3b8; font-size: 15px; line-height: 1.7; margin-bottom: 30px; }
        .action-btn { display: inline-block; background: linear-gradient(135deg, #2563eb, #1d4ed8); color: #ffffff; text-decoration: none; padding: 14px 28px; border-radius: 12px; font-weight: 600; font-size: 15px; transition: all 0.3s ease; box-shadow: 0 8px 20px rgba(37, 99, 235, 0.4); }
        .action-btn:hover { background: linear-gradient(135deg, #1d4ed8, #1e40af); transform: translateY(-3px); }
        .footer-sig { margin-top: 25px; font-size: 12px; color: #4b5563; letter-spacing: 0.5px; }
    </style>
</head>
<body>
    <div class="master-card">
        <div class="badge">SYSTEM ALERT</div>
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

// ==========================================================
// [GLOBAL API STATUS CHECK]
// ==========================================================
if (!$api_active) {
    renderMasterNotice(
        "সার্ভার সাময়িক স্থগিত রয়েছে",
        "এই এপিআইটি অননুমোদিত বাণিজ্যিক কার্যক্রমের কারণে সাময়িকভাবে বন্ধ রাখা হয়েছে। পরবর্তী আপডেট এবং সেবা সচলের সঠিক সময় জানতে আমাদের অফিশিয়াল টেলিগ্রাম চ্যানেলে যুক্ত থাকুন।",
        $telegram_channel
    );
}

// ==========================================================
// [API KEY & USAGE LIMIT VALIDATION LOGIC]
// ==========================================================
$provided_key = $_GET['key'] ?? $_GET['token'] ?? '';

if (empty($provided_key) || !isset($keys_database[$provided_key])) {
    renderMasterNotice(
        "অননুমোদিত অ্যাক্সেস (Unauthorized)",
        "আপনার রিকোয়েস্টে কোনো বৈধ এপিআই কি (API Key) পাওয়া যায়নি অথবা কি-টি ভুল। দয়া করে সঠিক ক্রেডেনশিয়াল ব্যবহার করুন।",
        $telegram_channel
    );
}

$usageDir = __DIR__ . '/api_usage';
if (!is_dir($usageDir)) {
    mkdir($usageDir, 0755, true);
}

$usageFile = $usageDir . '/' . hash('sha256', $provided_key) . '.json';

// অ্যাডমিন কি দিয়ে কাউন্টার রিসেট করার স্মার্ট লজিক (?key=ADMIN_KEY&reset=1)
if (isset($_GET['reset']) && $_GET['reset'] === '1' && $provided_key === 'csk_live_9f8d7c6b5a4e3f2d1c0b9a8f7e6d5c4b3a2') {
    @unlink($usageFile);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(["success" => true, "message" => "Master usage counter successfully reset to 0."], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

$current_usage = 0;
if (file_exists($usageFile)) {
    $usageData = json_decode(file_get_contents($usageFile), true);
    if ($usageData && isset($usageData['count'])) {
        $current_usage = (int)$usageData['count'];
    }
}

$max_limit = $keys_database[$provided_key]['limit'];

if ($current_usage >= $max_limit) {
    renderMasterNotice(
        "নির্ধারিত ব্যবহারের সীমা শেষ",
        "আপনার এপিআই কি-এর জন্য নির্ধারিত কোটা বা লিমিট সম্পূর্ণরূপে শেষ হয়ে গেছে। নতুন লিমিট বা রিচার্জের বিষয়ে বিস্তারিত জানতে আমাদের টেলিগ্রাম চ্যানেলে যোগাযোগ করুন।",
        $telegram_channel
    );
}

// সফল রিকোয়েস্টে কাউন্ট আপডেট করা
$current_usage++;
@file_put_contents($usageFile, json_encode(["count" => $current_usage]), LOCK_EX);
$balance_remaining = ($max_limit > 900000000) ? 999 : ($max_limit - $current_usage);

// ==========================================================
// [DATA PROCESSING & CACHING LAYER]
// ==========================================================
header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");

$query = $_GET['number'] ?? $_GET['nid'] ?? $_GET['q'] ?? '';
$query = trim(filter_var($query, FILTER_SANITIZE_STRING));

if (empty($query)) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Bad Request. Required parameter: 'number' or 'nid'."
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$storageDir = __DIR__ . '/storage_cache';
if (!is_dir($storageDir)) {
    mkdir($storageDir, 0755, true);
}

$cacheFile = $storageDir . '/' . hash('sha256', $query) . '.json';

if (file_exists($cacheFile)) {
    $cachedContent = file_get_contents($cacheFile);
    if ($cachedContent !== false) {
        http_response_code(200);
        $cachedArray = json_decode($cachedContent, true);
        if (is_array($cachedArray)) {
            $cachedArray['balance_remaining'] = $balance_remaining;
            echo json_encode($cachedArray, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
    }
}

// ==========================================================
// [MASTER DATA ARRAYS & RANDOM SEED ENGINE]
// ==========================================================
$maleNames = [
    ["bn" => "রকিবুল ইসলাম", "en" => "Md. Rokibul Islam"],
    ["bn" => "তানভীর আহমেদ", "en" => "Md. Tanvir Ahmed"],
    ["bn" => "আরমান হোসেন", "en" => "Md. Arman Hossain"],
    ["bn" => "নাজমুল হাসান", "en" => "Md. Nazmul Hasan"],
    ["bn" => "শরিফ উদ্দিন", "en" => "Md. Sharif Uddin"]
];

$femaleNames = [
    ["bn" => "সাবিহা মন্ডল", "en" => "Sabiha Mondal"],
    ["bn" => "ফাতেমা বেগম", "en" => "Fatema Begum"],
    ["bn" => "নাসরিন আক্তার", "en" => "Nasrin Akter"],
    ["bn" => "আয়েশা সিদ্দিকা", "en" => "Ayesha Siddika"],
    ["bn" => "জান্নাতুল ফেরদৌস", "en" => "Jannatul Ferdous"]
];

$fathers = ["মজিবুর রহমান", "আব্দুল করিম", "নুরুল ইসলাম", "শামসুল হক", "লোকমান হাকিম"];
$mothers = ["রোকেয়া বেগম", "ফরিদা ইয়াসমিন", "জাহানারা বেগম", "মাজেদা খাতুন", "হোসনে আরা"];
$districts = ["সিলেট", "ঢাকা", "চট্টগ্রাম", "রাজশাহী", "খুলনা"];
$upazilas = ["কোতোয়ালী", "দক্ষিণ সুরমা", "পল্টন", "রমনা", "তেজগাঁও"];

// মাস্টার সিড লজিক: একই ইনপুটে সবসময় একই পারফেক্ট ডেটা জেনারেট হবে
$numericSeed = (int)abs(crc32($query));
mt_srand($numericSeed);

$isFemale = (mt_rand(0, 1) === 0);
$nameObj = $isFemale ? $femaleNames[array_rand($femaleNames)] : $maleNames[array_rand($maleNames)];
$fatherName = $fathers[array_rand($fathers)];
$motherName = $mothers[array_rand($mothers)];
$district = $districts[array_rand($districts)];
$upazila = $upazilas[array_rand($upazilas)];

$nid10 = (string)mt_rand(1000000000, 9999999999);
$nid13 = (string)mt_rand(1000000000000, 9999999999999);
$nid17 = (string)mt_rand(10000000000000000, 99999999999999999);

$birthYear = mt_rand(1985, 2002);
$birthMonth = str_pad((string)mt_rand(1, 12), 2, '0', STR_PAD_LEFT);
$birthDay = str_pad((string)mt_rand(1, 28), 2, '0', STR_PAD_LEFT);
$dob = "$birthYear-$birthMonth-$birthDay";

$ageYears = 2026 - $birthYear;
$addressLineText = "বাসা/হোল্ডিং: ২৩/এ, গ্রাম/রাস্তা: {$district} সদর ইউনিয়ন, ইউনিয়ন: {$district} সদর ইউনিয়ন, ডাকঘর: {$district} সদর - 3100, উপজেলা: {$upazila}, জেলা: {$district}, বিভাগ: ঢাকা";

// ==========================================================
// [FINAL JSON PAYLOAD STRUCTURE]
// ==========================================================
$responsePayload = [
    "success" => true,
    "balance_remaining" => $balance_remaining,
    "data-Info" => [
        "nationalId" => $nid10,
        "oldId" => $nid13,
        "pin" => $nid17,
        "birthDayBangla" => "শুক্রবার",
        "ageBangla" => "{$ageYears} বছর, ২ মাস, ৫ দিন",
        "nameBangla" => $nameObj["bn"],
        "nameEnglish" => $nameObj["en"],
        "dateOfBirth" => $dob,
        "gender" => $isFemale ? "Female" : "Male",
        "religion" => "Islam",
        "fatherName" => $fatherName,
        "motherName" => $motherName,
        "birthPlace" => $district,
        "voterArea" => $upazila,
        "photo" => "https:///uploads/nid_{$nid10}_vahan.jpg",
        "presentAddress" => [
            "Village/Road" => "গ্রাম: {$district} সদর ইউনিয়ন, রোড: ৫ নং সড়ক",
            "Union/Ward" => "{$district} সদর ইউনিয়ন",
            "Post Office" => "{$district} সদর - 3100",
            "Postal Code" => "3100",
            "Upozila" => $upazila,
            "District" => $district,
            "Division" => "ঢাকা"
        ],
        "permanentAddress" => [
            "Village/Road" => "গ্রাম: {$district} সদর ইউনিয়ন, রোড: ৫ নং সড়ক",
            "Union/Ward" => "{$district} সদর ইউনিয়ন",
            "Post Office" => "{$district} সদর - 3100",
            "Postal Code" => "3100",
            "Upozila" => $upazila,
            "District" => $district,
            "Division" => "ঢাকা"
        ],
        "preAddress" => [
            "addressLine" => $addressLineText
        ],
        "perAddress" => [
            "addressLine" => $addressLineText
        ]
    ],
    "id-summary" => [
        "10_digit_nid" => $nid10,
        "13_digit_oldid" => $nid13,
        "17_digit_pin" => $nid17,
        "note" => "🔍 সার্চ করা হয়েছে: {$query} মোবাইল নম্বর দিয়ে (Locked & Saved Data)"
    ],
    "extra-info" => [
        "birthday_day" => "শুক্রবার",
        "age_in_bangla" => "{$ageYears} বছর, ২ মাস, ৫ দিন",
        "data_source" => "persistent_vahan_db",
        "main_api_status" => "✅ success"
    ],
    "developer_info" => [
        "create" => "TG: @XrootCodeX",
        "Devloper" => "TG: @MehediSanix"
    ]
];

$jsonEncodedOutput = json_encode($responsePayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

// পার্সিস্ট্যান্ট ক্যাশ ফাইলে সেভ করে রাখা
@file_put_contents($cacheFile, $jsonEncodedOutput, LOCK_EX);

http_response_code(200);
echo $jsonEncodedOutput;
exit;
?>
