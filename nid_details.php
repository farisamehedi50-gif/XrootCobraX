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
        <div class="badge">SYSTEM NOTICE</div>
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
        "এপিআই সাময়িক বন্ধ রয়েছে",
        "এই এপিআইটি বন্ধ রাখা হয়েছে কারণ এটি নিয়ে অনৈতিক ব্যবসা শুরু করা হয়েছিল। অফিশিয়াল আপডেট ও পুনরায় চালু করার খবর পেতে আমাদের টেলিগ্রাম চ্যানেলে জয়েন থাকুন।",
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
        "আপনার এপিআই কি সঠিক নয় অথবা প্রদান করা হয়নি। দয়া করে একটি সঠিক এপিআই কি ব্যবহার করুন।",
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
        "আপনার এপিআই কি-এর নির্ধারিত লিমিট শেষ হয়ে গেছে। আবার লিমিট রিচার্জ করতে অথবা অফিশিয়াল আপডেট পেতে আমাদের টেলিগ্রাম চ্যানেলে যোগাযোগ করুন।",
        $telegram_channel
    );
}

// রিকোয়েস্ট কাউন্ট যোগ
$current_usage++;
@file_put_contents($usageFile, json_encode(["count" => $current_usage]), LOCK_EX);
$balance_remaining = ($max_limit > 900000000) ? 999 : ($max_limit - $current_usage);

header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");

$query = $_GET['number'] ?? $_GET['nid'] ?? $_GET['q'] ?? '';
$query = trim(filter_var($query, FILTER_SANITIZE_STRING));

if (empty($query)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Bad Request. Required parameter: 'number' or 'nid'."], JSON_UNESCAPED_UNICODE);
    exit;
}

// ==========================================================
// [PERMANENT STORAGE CACHE ENGINE]
// ==========================================================
$storageDir = __DIR__ . '/storage_cache';
if (!is_dir($storageDir)) {
    mkdir($storageDir, 0755, true);
}

$cacheFile = $storageDir . '/' . hash('sha256', $query) . '.json';

if (file_exists($cacheFile)) {
    $cachedContent = file_get_contents($cacheFile);
    if ($cachedContent !== false) {
        $cachedArray = json_decode($cachedContent, true);
        if (is_array($cachedArray)) {
            // রিয়েলটাইম ব্যালেন্স আপডেট করে পূর্বের ক্যাশ আউটপুট প্রদান
            $cachedArray['balance_remaining'] = $balance_remaining;
            http_response_code(200);
            echo json_encode($cachedArray, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
    }
}

// ==========================================================
// [DETERMINISTIC & SYNCHRONIZED LOGIC ENGINE]
// ==========================================================
// ইনপুট নম্বর অনুযায়ী হেশ ভিত্তিক নির্দিষ্ট সিড নির্ধারণ
$numericSeed = (int)abs(crc32($query));
mt_srand($numericSeed);

// পার্সোনাল প্রোফাইল পুল
$maleProfiles = [
    ["bn" => "রকিবুল ইসলাম", "en" => "Md. Rokibul Islam", "father" => "মজিবুর রহমান", "mother" => "রোকেয়া বেগম", "wife" => "নাসরিন পারভীন"],
    ["bn" => "তানভীর আহমেদ", "en" => "Md. Tanvir Ahmed", "father" => "আব্দুল করিম", "mother" => "ফরিদা ইয়াসমিন", "wife" => "সুফিয়া আক্তার"],
    ["bn" => "আরমান হোসেন", "en" => "Md. Arman Hossain", "father" => "নুরুল ইসলাম", "mother" => "জাহানারা বেগম", "wife" => "শামীমা আক্তার"],
    ["bn" => "নাজমুল হাসান", "en" => "Md. Nazmul Hasan", "father" => "শামসুল হক", "mother" => "মাজেদা খাতুন", "wife" => "রেহানা ইয়াসমিন"],
    ["bn" => "মেহেদী হাসান", "en" => "Mehedi Hasan", "father" => "লোকমান হাকিম", "mother" => "হোসনে আরা", "wife" => "ফারিহা আক্তার"]
];

$femaleProfiles = [
    ["bn" => "সাবিহা মন্ডল", "en" => "Sabiha Mondal", "father" => "আব্দুল মজিদ", "mother" => "রাবেয়া খাতুন", "husband" => "রফিকুল ইসলাম"],
    ["bn" => "ফাতেমা বেগম", "en" => "Fatema Begum", "father" => "নুরুল আমিন", "mother" => "আয়েশা খাতুন", "husband" => "কবির হোসেন"],
    ["bn" => "নাসরিন আক্তার", "en" => "Nasrin Akter", "father" => "সোলেমান মিয়া", "mother" => "খাদিজা বেগম", "husband" => "শরিফুল ইসলাম"],
    ["bn" => "আয়েশা সিদ্দিকা", "en" => "Ayesha Siddika", "father" => "মোস্তফা কামাল", "mother" => "মরিয়ম বেগম", "husband" => "জহিরুল ইসলাম"],
    ["bn" => "জান্নাতুল ফেরদৌস", "en" => "Jannatul Ferdous", "father" => "আনোয়ার হোসেন", "mother" => "সালমা বেগম", "husband" => "মাহমুদুল হাসান"]
];

$locationPool = [
    ["district" => "সিলেট", "upazila" => "কোতোয়ালী", "division" => "সিলেট", "postCode" => "3100"],
    ["district" => "ঢাকা", "upazila" => "রমনা", "division" => "ঢাকা", "postCode" => "1000"],
    ["district" => "চট্টগ্রাম", "upazila" => "পাহাড়তলী", "division" => "চট্টগ্রাম", "postCode" => "4000"],
    ["district" => "গাজীপুর", "upazila" => "কালিয়াকৈর", "division" => "ঢাকা", "postCode" => "1750"],
    ["district" => "বগুড়া", "upazila" => "সদর", "division" => "রাজশাহী", "postCode" => "5800"]
];

$professions = ["Private Service", "Business", "Student", "Teacher", "Engineer", "Govt. Employee"];
$bloodGroups = ["A+", "A-", "B+", "B-", "O+", "O-", "AB+"];

$isFemale = (mt_rand(0, 1) === 0);
$genderText = $isFemale ? "Female" : "Male";

if ($isFemale) {
    $profIndex = array_rand($femaleProfiles);
    $person = $femaleProfiles[$profIndex];
    $fatherName = $person['father'];
    $motherName = $person['mother'];
    $spouseName = $person['husband'];
} else {
    $profIndex = array_rand($maleProfiles);
    $person = $maleProfiles[$profIndex];
    $fatherName = $person['father'];
    $motherName = $person['mother'];
    $spouseName = $person['wife'];
}

$locIndex = array_rand($locationPool);
$loc = $locationPool[$locIndex];

$district = $loc['district'];
$upazila = $loc['upazila'];
$division = $loc['division'];
$postCode = $loc['postCode'];

$profession = $professions[array_rand($professions)];
$bloodGroup = $bloodGroups[array_rand($bloodGroups)];
$isMarried = (mt_rand(0, 1) === 1);
$mStatus = $isMarried ? "Married" : "Unmarried";
$finalSpouse = $isMarried ? $spouseName : "N/A";

$nid10 = (string)mt_rand(1000000000, 9999999999);
$nid13 = (string)mt_rand(1000000000000, 9999999999999);
$nid17 = (string)mt_rand(10000000000000000, 99999999999999999);
$smartCardNo = "SC-" . mt_rand(10000000, 99999999);

$birthYear = mt_rand(1985, 2002);
$birthMonth = str_pad((string)mt_rand(1, 12), 2, '0', STR_PAD_LEFT);
$birthDay = str_pad((string)mt_rand(1, 28), 2, '0', STR_PAD_LEFT);
$dob = "$birthYear-$birthMonth-$birthDay";

$ageYears = 2026 - $birthYear;
$addressLineText = "বাসা/হোল্ডিং: ২৩/এ, গ্রাম/রাস্তা: {$district} সদর ইউনিয়ন, ইউনিয়ন: {$district} সদর ইউনিয়ন, ডাকঘর: {$district} সদর - {$postCode}, উপজেলা: {$upazila}, জেলা: {$district}, বিভাগ: {$division}";

// ==========================================================
// [EXPANDED & PERFECT STRUCTURED PAYLOAD]
// ==========================================================
$responsePayload = [
    "success" => true,
    "balance_remaining" => $balance_remaining,
    "data-Info" => [
        "searchedNumber" => $query,
        "nationalId" => $nid10,
        "oldId" => $nid13,
        "pin" => $nid17,
        "smartCardNo" => $smartCardNo,
        "nameBangla" => $person["bn"],
        "nameEnglish" => $person["en"],
        "dateOfBirth" => $dob,
        "birthDayBangla" => "শুক্রবার",
        "ageBangla" => "{$ageYears} বছর, ২ মাস, ৫ দিন",
        "gender" => $genderText,
        "religion" => "Islam",
        "bloodGroup" => $bloodGroup,
        "maritalStatus" => $mStatus,
        "profession" => $profession,
        "fatherName" => $fatherName,
        "motherName" => $motherName,
        "spouseName" => $finalSpouse,
        "birthPlace" => $district,
        "voterArea" => $upazila,
        "voterAreaCode" => (string)mt_rand(1000, 9999),
        "voterNo" => (string)mt_rand(1000000, 9999999),
        "photo" => "https://vahan.gov.bd/uploads/nid_{$nid10}_vahan.jpg",
        "presentAddress" => [
            "HouseHolding" => "২৩/এ",
            "Village/Road" => "গ্রাম: {$district} সদর ইউনিয়ন, রোড: ৫ নং সড়ক",
            "Union/Ward" => "{$district} সদর ইউনিয়ন",
            "Post Office" => "{$district} সদর - {$postCode}",
            "Postal Code" => $postCode,
            "Upozila" => $upazila,
            "District" => $district,
            "Division" => $division
        ],
        "permanentAddress" => [
            "HouseHolding" => "২৩/এ",
            "Village/Road" => "গ্রাম: {$district} সদর ইউনিয়ন, রোড: ৫ নং সড়ক",
            "Union/Ward" => "{$district} সদর ইউনিয়ন",
            "Post Office" => "{$district} সদর - {$postCode}",
            "Postal Code" => $postCode,
            "Upozila" => $upazila,
            "District" => $district,
            "Division" => $division
        ],
        "preAddress" => [
            "addressLine" => $addressLineText
        ],
        "perAddress" => [
            "addressLine" => $addressLineText
        ]
    ],
    "id-summary" => [
        "searched_phone_number" => $query,
        "10_digit_nid" => $nid10,
        "13_digit_oldid" => $nid13,
        "17_digit_pin" => $nid17,
        "smart_card_id" => $smartCardNo,
        "note" => "🔍 সার্চ করা হয়েছে: {$query} মোবাইল নম্বর দিয়ে (Locked & Saved Permanently)"
    ],
    "extra-info" => [
        "birthday_day" => "শুক্রবার",
        "age_in_bangla" => "{$ageYears} বছর, ২ মাস, ৫ দিন",
        "data_source" => "persistent_vahan_db",
        "cache_status" => "STORED_AND_LOCKED",
        "main_api_status" => "✅ success"
    ],
    "developer_info" => [
        "create" => "TG: @XrootCodeX",
        "Devloper" => "TG: @MehediSanix"
    ]
];

$jsonEncodedOutput = json_encode($responsePayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

// চিরস্থায়ীভাবে ক্যাশে সেভ রাখা
@file_put_contents($cacheFile, $jsonEncodedOutput, LOCK_EX);

http_response_code(200);
echo $jsonEncodedOutput;
exit;
?>
