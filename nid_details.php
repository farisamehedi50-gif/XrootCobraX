<?php
declare(strict_types=1);

error_reporting(0);
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");

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

// নামের তালিকা (বাংলা ও ইংরেজি একই ইনডেক্সে রাখা হয়েছে)
$firstNames = [
    ["bn" => "রকিবুল", "en" => "Rakibul"],
    ["bn" => "তানভীর", "en" => "Tanvir"],
    ["bn" => "আরমান", "en" => "Arman"],
    ["bn" => "নাজমুল", "en" => "Nazmul"],
    ["bn" => "শরিফ", "en" => "Sharif"],
    ["bn" => "মাহবুব", "en" => "Mahbub"],
    ["bn" => "আনিসুর", "en" => "Anisur"],
    ["bn" => "রুবেল", "en" => "Rubel"],
    ["bn" => "ইমরান", "en" => "Imran"],
    ["bn" => "ফারহান", "en" => "Farhan"],
    ["bn" => "সুমাইয়া", "en" => "Sumaiya"],
    ["bn" => "ফাতেমা", "en" => "Fatema"],
    ["bn" => "নাসরিন", "en" => "Nasrin"],
    ["bn" => "আয়েশা", "en" => "Ayesha"],
    ["bn" => "জান্নাত", "en" => "Jannat"]
];

$lastNames = [
    ["bn" => "হাসান", "en" => "Hasan"],
    ["bn" => "ইসলাম", "en" => "Islam"],
    ["bn" => "আহমেদ", "en" => "Ahmed"],
    ["bn" => "হোসেন", "en" => "Hossain"],
    ["bn" => "আলী", "en" => "Ali"],
    ["bn" => "চৌধুরী", "en" => "Chowdhury"],
    ["bn" => "সরকার", "en" => "Sarker"],
    ["bn" => "মিয়া", "en" => "Mia"],
    ["bn" => "খান", "en" => "Khan"],
    ["bn" => "শেখ", "en" => "Sheikh"],
    ["bn" => "আক্তার", "en" => "Akter"],
    ["bn" => "তালুকদার", "en" => "Talukdar"]
];

$villages = [
    ["bn" => "দক্ষিণপাড়া", "en" => "South Para"],
    ["bn" => "উত্তরকান্দি", "en" => "Uttar Kandi"],
    ["bn" => "রামপুর", "en" => "Rampur"],
    ["bn" => "সৈয়দপুর", "en" => "Syedpur"],
    ["bn" => "কৃষ্ণপুর", "en" => "Krishnapur"],
    ["bn" => "কালীগঞ্জ", "en" => "Kaliganj"]
];

$postOffices = [
    ["bn" => "সৈয়দপুর - ৫৪০০", "en" => "Syedpur - 5400"],
    ["bn" => "কালীগঞ্জ বাজার - ১২৫০", "en" => "Kaliganj Bazar - 1250"],
    ["bn" => "রামপুর হাট - ৩২০০", "en" => "Rampur Hat - 3200"],
    ["bn" => "সাভার মডেল - ১৩৪০", "en" => "Savar Model - 1340"]
];

$upazilas = [
    ["bn" => "সদর", "en" => "Sadar"],
    ["bn" => "কালীগঞ্জ", "en" => "Kaliganj"],
    ["bn" => "সাভার", "en" => "Savar"],
    ["bn" => "রূপগঞ্জ", "en" => "Rupganj"]
];

$districts = [
    ["bn" => "ঢাকা", "en" => "Dhaka"],
    ["bn" => "গাজীপুর", "en" => "Gazipur"],
    ["bn" => "নারায়ণগঞ্জ", "en" => "Narayanganj"],
    ["bn" => "টাঙ্গাইল", "en" => "Tangail"],
    ["bn" => "চট্টগ্রাম", "en" => "Chittagong"]
];

$bloodGroupList = ["A+", "A-", "B+", "B-", "AB+", "AB-", "O+", "O-"];

// সিড সেট করা
$numericSeed = (int)abs(crc32($query));
mt_srand($numericSeed);

// ব্যক্তির নাম
$fIdx = mt_rand(0, count($firstNames) - 1);
$lIdx = mt_rand(0, count($lastNames) - 1);
$fullNameBn = "মোঃ " . $firstNames[$fIdx]["bn"] . " " . $lastNames[$lIdx]["bn"];
$fullNameEn = "Md. " . $firstNames[$fIdx]["en"] . " " . $lastNames[$lIdx]["en"];

// পিতার নাম (একই ইনডেক্স ম্যাচ করে)
$faIdx = mt_rand(0, count($firstNames) - 1);
$faLIdx = mt_rand(0, count($lastNames) - 1);
$fatherNameBn = "মোঃ " . $firstNames[$faIdx]["bn"] . " " . $lastNames[$faLIdx]["bn"];
$fatherNameEn = "Md. " . $firstNames[$faIdx]["en"] . " " . $lastNames[$faLIdx]["en"];

// মাতার নাম (একই ইনডেক্স ম্যাচ করে)
$moIdx = mt_rand(10, count($firstNames) - 1); // মহিলা নাম নির্বাচন
$motherNameBn = $firstNames[$moIdx]["bn"] . " বেগম";
$motherNameEn = $firstNames[$moIdx]["en"] . " Begum";

// ঠিকানা নির্বাচন
$vIdx = mt_rand(0, count($villages) - 1);
$poIdx = mt_rand(0, count($postOffices) - 1);
$upIdx = mt_rand(0, count($upazilas) - 1);
$dIdx = mt_rand(0, count($districts) - 1);

$bloodGroup = $bloodGroupList[mt_rand(0, count($bloodGroupList) - 1)];

$nid10Digit = (string)mt_rand(1000000000, 9999999999);
$nid17Digit = (string)mt_rand(1980, 2003) . $dIdx . mt_rand(100000000, 999999999);
$formNumber = "FORM-" . mt_rand(1000000, 9999999);
$pinNumber = "PIN" . mt_rand(100000000, 999999999);
$voterNumber = "VTR-" . mt_rand(10000000, 99999999);
$voterAreaCode = (string)mt_rand(1000, 9999);

$birthYear = mt_rand(1980, 2003);
$birthMonth = str_pad((string)mt_rand(1, 12), 2, '0', STR_PAD_LEFT);
$birthDay = str_pad((string)mt_rand(1, 28), 2, '0', STR_PAD_LEFT);
$dateOfBirth = "$birthYear-$birthMonth-$birthDay";

$isMale = true;
$genderEn = "Male";
$genderBn = "পুরুষ";

$mobileNumber = (strlen($query) === 11 && ctype_digit($query)) ? $query : ("01" . mt_rand(3, 9) . mt_rand(10000000, 99999999));

$responsePayload = [
    "code" => 200,
    "status" => true,
    "message" => "National Identity Record retrieved successfully.",
    "data" => [
        "searchedNumber" => $query,
        "nidInfo" => [
            "nationalId" => $nid10Digit,
            "nationalIdOld" => $nid17Digit,
            "smartCardNumber" => $nid10Digit,
            "pinNumber" => $pinNumber,
            "voterNumber" => $voterNumber,
            "formNumber" => $formNumber,
            "voterArea" => $villages[$vIdx]["bn"] . " নির্বাচনী এলাকা",
            "voterAreaCode" => $voterAreaCode,
            "cardStatus" => "Smart Card Printed & Issued"
        ],
        "personalInfo" => [
            "nameBangla" => $fullNameBn,
            "nameEnglish" => $fullNameEn,
            "dateOfBirth" => $dateOfBirth,
            "gender" => $genderEn,
            "genderBangla" => $genderBn,
            "bloodGroup" => $bloodGroup,
            "mobileNumber" => $mobileNumber,
            "occupation" => "ব্যবসা",
            "maritalStatus" => "বিবাহিত"
        ],
        "familyInfo" => [
            "fatherNameBangla" => $fatherNameBn,
            "fatherNameEnglish" => $fatherNameEn,
            "motherNameBangla" => $motherNameBn,
            "motherNameEnglish" => $motherNameEn,
            "spouseNameBangla" => "মোসাম্মৎ কুলসুমা বেগম",
            "spouseNameEnglish" => "Mst. Kulsuma Begum"
        ],
        "permanentAddress" => [
            "houseOrHolding" => "বাসা নং " . mt_rand(1, 150),
            "villageOrRoad" => $villages[$vIdx]["bn"],
            "villageOrRoadEnglish" => $villages[$vIdx]["en"],
            "unionOrWard" => "ওয়ার্ড নং " . mt_rand(1, 9),
            "postOffice" => $postOffices[$poIdx]["bn"],
            "postOfficeEnglish" => $postOffices[$poIdx]["en"],
            "upazila" => $upazilas[$upIdx]["bn"],
            "upazilaEnglish" => $upazilas[$upIdx]["en"],
            "district" => $districts[$dIdx]["bn"],
            "districtEnglish" => $districts[$dIdx]["en"],
            "division" => "ঢাকা",
            "divisionEnglish" => "Dhaka"
        ],
        "presentAddress" => [
            "houseOrHolding" => "বাসা নং " . mt_rand(1, 150),
            "villageOrRoad" => $villages[$vIdx]["bn"],
            "villageOrRoadEnglish" => $villages[$vIdx]["en"],
            "unionOrWard" => "ওয়ার্ড নং " . mt_rand(1, 9),
            "postOffice" => $postOffices[$poIdx]["bn"],
            "postOfficeEnglish" => $postOffices[$poIdx]["en"],
            "upazila" => $upazilas[$upIdx]["bn"],
            "upazilaEnglish" => $upazilas[$upIdx]["en"],
            "district" => $districts[$dIdx]["bn"],
            "districtEnglish" => $districts[$dIdx]["en"],
            "division" => "ঢাকা",
            "divisionEnglish" => "Dhaka"
        ]
    ],
    "OWNER" => "@XrootCodeX",
    "TG" => "@CobraX_Empire",
    "DEVELOPER" => "MehediSanix"
];

$jsonEncodedOutput = json_encode($responsePayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

@file_put_contents($cacheFile, $jsonEncodedOutput, LOCK_EX);

http_response_code(200);
echo $jsonEncodedOutput;
exit;
?>
