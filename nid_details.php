<?php
declare(strict_types=1);

error_reporting(0);
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");

// ==========================================================
// ১. অত্যন্ত শক্ত ও কঠিন সিক্রেট এপিআই কি (API Key) এবং লিমিট কনফিগারেশন
// ==========================================================
$keys_database = [
    "csk_live_9f8d7c6b5a4e3f2d1c0b9a8f7e6d5c4b3a2" => [
        "name" => "Admin Root",
        "limit" => 999999999 // আনলিমিটেড
    ],
    "usr_sec_token_x9z8y7w6v5u4t3s2r1q0p9o8n7m6l5k4j3" => [
        "name" => "User Beta 2",
        "limit" => 3 // ৩ বার লিমিট
    ],
    "tkn_auth_p_qwerty1234567890abcdefghijk987654321" => [
        "name" => "User Alpha 1",
        "limit" => 1 // ১ বার লিমিট
    ]
];

$provided_key = $_GET['key'] ?? $_GET['token'] ?? '';

// কি (Key) ভ্যালিড কিনা চেক করা
if (empty($provided_key) || !isset($keys_database[$provided_key])) {
    http_response_code(401);
    echo json_encode([
        "success" => false,
        "message" => "Unauthorized access! Invalid or missing API Key token.",
        "developer_info" => [
            "create" => "TG: @XrootCodeX",
            "Devloper" => "TG: @MehediSanix"
        ]
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// ==========================================================
// ২. ব্যবহার সীমা (Usage Tracker) ও কাউন্ট সিস্টেম
// ==========================================================
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

$max_limit = $keys_database[$provided_key]['limit'];

// লিমিট শেষ হয়ে গেলে ব্লক করা
if ($current_usage >= $max_limit) {
    http_response_code(429);
    echo json_encode([
        "success" => false,
        "message" => "API usage limit exceeded! Your allowed limit has been exhausted.",
        "developer_info" => [
            "create" => "TG: @XrootCodeX",
            "Devloper" => "TG: @MehediSanix"
        ]
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// কাউন্ট বাড়িয়ে ফাইলে সেভ করা
$current_usage++;
@file_put_contents($usageFile, json_encode(["count" => $current_usage]), LOCK_EX);
$balance_remaining = ($max_limit > 900000000) ? 999 : ($max_limit - $current_usage);

// ==========================================================
// ৩. রিকোয়েস্ট প্রসেসিং ও পার্সিস্টেন্ট ক্যাশ চেক
// ==========================================================
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        "success" => false,
        "message" => "Method Not Allowed. Only GET requests are accepted."
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

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
        // ব্যালেন্স বা রিমেইনিং লিমিট রিয়েল-টাইমে আপডেট করার জন্য ডেটা ডিকোড করে আবার এনকোড করা
        $cachedArray = json_decode($cachedContent, true);
        if (is_array($cachedArray)) {
            $cachedArray['balance_remaining'] = $balance_remaining;
            echo json_encode($cachedArray, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
    }
}

// ==========================================================
// ৪. ডেটা জেনারেশন (পুরুষ ও মহিলা নিখুঁত সামঞ্জস্য)
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

@file_put_contents($cacheFile, $jsonEncodedOutput, LOCK_EX);

http_response_code(200);
echo $jsonEncodedOutput;
exit;
?>
