<?php
declare(strict_types=1);

error_reporting(0);
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");

// ==========================================================
// ১. কাস্টম এপিআই কি (API Key) এবং লিমিট কনফিগারেশন
// ==========================================================
$keys_database = [
    "csaqwedsert" => [
        "name" => "Admin User",
        "limit" => 999999999 // আনলিমিটেড
    ],
    "asoieurysbs" => [
        "name" => "User 2",
        "limit" => 3 // ৩ বার লিমিট
    ],
    "Qwertyuiopa" => [
        "name" => "User 1",
        "limit" => 1 // ১ বার লিমিট
    ]
];

$provided_key = $_GET['key'] ?? $_GET['token'] ?? '';

// কি (Key) ভ্যালিড কিনা চেক
if (empty($provided_key) || !isset($keys_database[$provided_key])) {
    http_response_code(401);
    echo json_encode([
        "code" => 401,
        "status" => false,
        "message" => "Unauthorized access! Invalid or missing API Key.",
        "OWNER" => "@XrootCodeX",
        "TG" => "https://t.me/CobraX_Empire",
        "DEVELOPER" => "MehediSanix"
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// ==========================================================
// ২. ব্যবহার সীমা (Usage Tracker) মেকানিজম
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

// লিমিট অতিক্রম করলে ব্লক করা
if ($current_usage >= $max_limit) {
    http_response_code(429);
    echo json_encode([
        "code" => 429,
        "status" => false,
        "message" => "API usage limit exceeded! Allowed limit is {$max_limit} request(s). Contact Admin.",
        "OWNER" => "@XrootCodeX",
        "TG" => "https://t.me/CobraX_Empire",
        "DEVELOPER" => "MehediSanix"
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// কাউন্ট বাড়িয়ে ফাইলে সেভ করা
$current_usage++;
@file_put_contents($usageFile, json_encode(["count" => $current_usage]), LOCK_EX);

// ==========================================================
// ৩. রিকোয়েস্ট প্রসেসিং ও ডাটা জেনারেশন
// ==========================================================
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        "code" => 405,
        "status" => false,
        "message" => "Method Not Allowed. Only GET requests are accepted.",
        "OWNER" => "@XrootCodeX",
        "TG" => "https://t.me/CobraX_Empire",
        "DEVELOPER" => "MehediSanix"
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$query = $_GET['number'] ?? $_GET['nid'] ?? $_GET['q'] ?? '';
$query = trim(filter_var($query, FILTER_SANITIZE_STRING));

if (empty($query)) {
    http_response_code(400);
    echo json_encode([
        "code" => 400,
        "status" => false,
        "message" => "Bad Request. Required parameter: 'number' or 'nid'.",
        "OWNER" => "@XrootCodeX",
        "TG" => "https://t.me/CobraX_Empire",
        "DEVELOPER" => "MehediSanix"
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
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
        echo $cachedContent;
        exit;
    }
}

// পুরুষ ও মহিলা নামের সামঞ্জস্যপূর্ণ তালিকা
$maleNames = [
    ["bn" => "রকিবুল", "en" => "Rakibul"], ["bn" => "তানভীর", "en" => "Tanvir"],
    ["bn" => "আরমান", "en" => "Arman"], ["bn" => "নাজমুল", "en" => "Nazmul"],
    ["bn" => "শরিফ", "en" => "Sharif"], ["bn" => "মাহবুব", "en" => "Mahbub"],
    ["bn" => "আনিসুর", "en" => "Anisur"], ["bn" => "রুবেল", "en" => "Rubel"]
];

$femaleNames = [
    ["bn" => "সুমাইয়া", "en" => "Sumaiya"], ["bn" => "ফাতেমা", "en" => "Fatema"],
    ["bn" => "নাসরিন", "en" => "Nasrin"], ["bn" => "আয়েশা", "en" => "Ayesha"],
    ["bn" => "জান্নাত", "en" => "Jannat"], ["bn" => "সাদিয়া", "en" => "Sadia"]
];

$lastNames = [
    ["bn" => "হাসান", "en" => "Hasan"], ["bn" => "ইসলাম", "en" => "Islam"],
    ["bn" => "আহমেদ", "en" => "Ahmed"], ["bn" => "হোসেন", "en" => "Hossain"],
    ["bn" => "আলী", "en" => "Ali"], ["bn" => "চৌধুরী", "en" => "Chowdhury"]
];

$villages = [
    ["bn" => "দক্ষিণপাড়া", "en" => "South Para"], ["bn" => "উত্তরকান্দি", "en" => "Uttar Kandi"],
    ["bn" => "রামপুর", "en" => "Rampur"], ["bn" => "সৈয়দপুর", "en" => "Syedpur"]
];

$postOffices = [
    ["bn" => "সৈয়দপুর - ৫৪০০", "en" => "Syedpur - 5400"],
    ["bn" => "কালীগঞ্জ বাজার - ১২৫০", "en" => "Kaliganj Bazar - 1250"]
];

$upazilas = [
    ["bn" => "সদর", "en" => "Sadar"], ["bn" => "কালীগঞ্জ", "en" => "Kaliganj"]
];

$districts = [
    ["bn" => "ঢাকা", "en" => "Dhaka"], ["bn" => "গাজীপুর", "en" => "Gazipur"],
    ["bn" => "নারায়ণগঞ্জ", "en" => "Narayanganj"]
];

$bloodGroupList = ["A+", "A-", "B+", "B-", "O+"];

$numericSeed = (int)abs(crc32($query));
mt_srand($numericSeed);

$mIdx = mt_rand(0, count($maleNames) - 1);
$lIdx = mt_rand(0, count($lastNames) - 1);
$fullNameBn = "মোঃ " . $maleNames[$mIdx]["bn"] . " " . $lastNames[$lIdx]["bn"];
$fullNameEn = "Md. " . $maleNames[$mIdx]["en"] . " " . $lastNames[$lIdx]["en"];

$faIdx = mt_rand(0, count($maleNames) - 1);
$faLIdx = mt_rand(0, count($lastNames) - 1);
$fatherNameBn = "মোঃ " . $maleNames[$faIdx]["bn"] . " " . $lastNames[$faLIdx]["bn"];
$fatherNameEn = "Md. " . $maleNames[$faIdx]["en"] . " " . $lastNames[$faLIdx]["en"];

$moIdx = mt_rand(0, count($femaleNames) - 1);
$motherNameBn = $femaleNames[$moIdx]["bn"] . " বেগম";
$motherNameEn = $femaleNames[$moIdx]["en"] . " Begum";

$spIdx = mt_rand(0, count($femaleNames) - 1);
$spouseNameBn = "মোসাম্মৎ " . $femaleNames[$spIdx]["bn"] . " বেগম";
$spouseNameEn = "Mst. " . $femaleNames[$spIdx]["en"] . " Begum";

$vIdx = mt_rand(0, count($villages) - 1);
$poIdx = mt_rand(0, count($postOffices) - 1);
$upIdx = mt_rand(0, count($upazilas) - 1);
$dIdx = mt_rand(0, count($districts) - 1);

$bloodGroup = $bloodGroupList[mt_rand(0, count($bloodGroupList) - 1)];

$nid10Digit = (string)mt_rand(1000000000, 9999999999);
$nid17Digit = (string)mt_rand(1980, 2003) . $dIdx . mt_rand(100000000, 999999999);
$pinNumber = "PIN" . mt_rand(100000000, 999999999);
$voterNumber = "VTR-" . mt_rand(10000000, 99999999);

$birthYear = mt_rand(1980, 2003);
$birthMonth = str_pad((string)mt_rand(1, 12), 2, '0', STR_PAD_LEFT);
$birthDay = str_pad((string)mt_rand(1, 28), 2, '0', STR_PAD_LEFT);
$dateOfBirth = "$birthYear-$birthMonth-$birthDay";

$mobileNumber = (strlen($query) === 11 && ctype_digit($query)) ? $query : ("01" . mt_rand(3, 9) . mt_rand(10000000, 99999999));

$responsePayload = [
    "code" => 200,
    "status" => true,
    "message" => "National Identity Record retrieved successfully.",
    "remaining_limit" => ($max_limit > 900000000) ? "Unlimited" : ($max_limit - $current_usage),
    "data" => [
        "searchedNumber" => $query,
        "nidInfo" => [
            "nationalId" => $nid10Digit,
            "nationalIdOld" => $nid17Digit,
            "smartCardNumber" => $nid10Digit,
            "pinNumber" => $pinNumber,
            "voterNumber" => $voterNumber,
            "cardStatus" => "Smart Card Printed & Issued"
        ],
        "personalInfo" => [
            "nameBangla" => $fullNameBn,
            "nameEnglish" => $fullNameEn,
            "dateOfBirth" => $dateOfBirth,
            "gender" => "Male",
            "genderBangla" => "পুরুষ",
            "bloodGroup" => $bloodGroup,
            "mobileNumber" => $mobileNumber
        ],
        "familyInfo" => [
            "fatherNameBangla" => $fatherNameBn,
            "fatherNameEnglish" => $fatherNameEn,
            "motherNameBangla" => $motherNameBn,
            "motherNameEnglish" => $motherNameEn,
            "spouseNameBangla" => $spouseNameBn,
            "spouseNameEnglish" => $spouseNameEn
        ],
        "permanentAddress" => [
            "villageOrRoad" => $villages[$vIdx]["bn"],
            "postOffice" => $postOffices[$poIdx]["bn"],
            "upazila" => $upazilas[$upIdx]["bn"],
            "district" => $districts[$dIdx]["bn"],
            "division" => "ঢাকা"
        ]
    ],
    "OWNER" => "@XrootCodeX",
    "TG" => "https://t.me/CobraX_Empire",
    "DEVELOPER" => "MehediSanix"
];

$jsonEncodedOutput = json_encode($responsePayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

@file_put_contents($cacheFile, $jsonEncodedOutput, LOCK_EX);

http_response_code(200);
echo $jsonEncodedOutput;
exit;
?>
