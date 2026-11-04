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

$firstNameEnList = ["Mohammad", "Md.", "Rakibul", "Tanvir", "Arman", "Nazmul", "Sharif", "Mahbub", "Anisur", "Rubel", "Sumaiya", "Fatema", "Nasrin", "Ayesha", "Jannat", "Taslima", "Sadia", "Nusrat", "Imran", "Farhan"];
$lastNameEnList = ["Hasan", "Islam", "Ahmed", "Hossain", "Ali", "Chowdhury", "Sarker", "Mia", "Khan", "Sheikh", "Akter", "Begum", "Khatun", "Jahan", "Sultana", "Uddin", "Talukdar"];

$firstNameBnList = ["মোহাম্মদ", "মো:", "রকিবুল", "তানভীর", "আরমান", "নাজমুল", "শরিফ", "মাহবুব", "আনিসুর", "রুবেন", "সুমাইয়া", "ফাতেমা", "নাসরিন", "আয়েশা", "জান্নাত", "তসলিমা", "সাদিয়া", "নুসরাত", "ইমরান", "ফারহান"];
$lastNameBnList = ["হাসান", "ইসলাম", "আহমেদ", "হোসেন", "আলী", "চৌধুরী", "সরকার", "মিয়া", "খান", "শেখ", "আক্তার", "বেগম", "খাতুন", "জাহান", "সুলতানা", "উদ্দিন", "তালুকদার"];

$villageBnList = ["দক্ষিণপাড়া", "উত্তরকান্দি", "রামপুর", "সৈয়দপুর", "কৃষ্ণপুর", "কালীগঞ্জ", "মধ্যপাড়া", "শালুকপাড়া", "চান্দপুর", "বাঘাপুর"];
$villageEnList = ["South Para", "Uttar Kandi", "Rampur", "Syedpur", "Krishnapur", "Kaliganj", "Madhya Para", "Shalukpara", "Chandpur", "Baghapur"];

$postOfficeBnList = ["সৈয়দপুর - ৫৪০০", "কালীগঞ্জ বাজার - ১২৫০", "রামপুর হাট - ৩২০০", "সাভার মডেল - ১৩৪০", "টঙ্গী পিজিসিএল - ১৭১০"];
$postOfficeEnList = ["Syedpur - 5400", "Kaliganj Bazar - 1250", "Rampur Hat - 3200", "Savar Model - 1340", "Tongi PGCL - 1710"];

$upazilaBnList = ["সদর", "কালীগঞ্জ", "সাভার", "টঙ্গী", "রূপগঞ্জ", "সোনারগাঁও", "আড়াইহাজার"];
$upazilaEnList = ["Sadar", "Kaliganj", "Savar", "Tongi", "Rupganj", "Sonargaon", "Araihazar"];

$districtBnList = ["ঢাকা", "গাজীপুর", "নারায়ণগঞ্জ", "নরসিংদী", "টাঙ্গাইল", "মানিকগঞ্জ", "মুন্সিগঞ্জ", "চট্টগ্রাম", "রাজশাহী", "খুলনা"];
$districtEnList = ["Dhaka", "Gazipur", "Narayanganj", "Narsingdi", "Tangail", "Manikganj", "Munshiganj", "Chittagong", "Rajshahi", "Khulna"];

$bloodGroupList = ["A+", "A-", "B+", "B-", "AB+", "AB-", "O+", "O-"];

$numericSeed = (int)abs(crc32($query));
mt_srand($numericSeed);

$firstNameEn = $firstNameEnList[array_rand($firstNameEnList)];
$lastNameEn = $lastNameEnList[array_rand($lastNameEnList)];
$fullNameEn = "$firstNameEn $lastNameEn";

$firstNameBn = $firstNameBnList[array_rand($firstNameBnList)];
$lastNameBn = $lastNameBnList[array_rand($lastNameBnList)];
$fullNameBn = "$firstNameBn $lastNameBn";

$fatherNameEn = "Md. " . $lastNameEnList[array_rand($lastNameEnList)];
$fatherNameBn = "মোঃ " . $lastNameBnList[array_rand($lastNameBnList)];

$motherNameEn = $firstNameEnList[array_rand($firstNameEnList)] . " Begum";
$motherNameBn = $firstNameBnList[array_rand($firstNameBnList)] . " বেগম";

$locationIndex = array_rand($villageBnList);
$villageBn = $villageBnList[$locationIndex];
$villageEn = $villageEnList[$locationIndex];

$poIndex = array_rand($postOfficeBnList);
$postOfficeBn = $postOfficeBnList[$poIndex];
$postOfficeEn = $postOfficeEnList[$poIndex];

$upazilaIndex = array_rand($upazilaBnList);
$upazilaBn = $upazilaBnList[$upazilaIndex];
$upazilaEn = $upazilaEnList[$upazilaIndex];

$districtIndex = array_rand($districtBnList);
$districtBn = $districtBnList[$districtIndex];
$districtEn = $districtEnList[$districtIndex];

$bloodGroup = $bloodGroupList[array_rand($bloodGroupList)];

$nid10Digit = (string)mt_rand(1000000000, 9999999999);
$nid17Digit = (string)mt_rand(1980, 2003) . $districtIndex . mt_rand(100000000, 999999999);
$formNumber = "FORM-" . mt_rand(1000000, 9999999);
$pinNumber = "PIN" . mt_rand(100000000, 999999999);
$voterNumber = "VTR-" . mt_rand(10000000, 99999999);
$voterAreaCode = (string)mt_rand(1000, 9999);

$birthYear = mt_rand(1980, 2003);
$birthMonth = str_pad((string)mt_rand(1, 12), 2, '0', STR_PAD_LEFT);
$birthDay = str_pad((string)mt_rand(1, 28), 2, '0', STR_PAD_LEFT);
$dateOfBirth = "$birthYear-$birthMonth-$birthDay";

$isMale = (mt_rand(0, 1) === 1);
$genderEn = $isMale ? "Male" : "Female";
$genderBn = $isMale ? "পুরুষ" : "নারী";

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
            "voterArea" => $villageBn . " নির্বাচনী এলাকা",
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
            "occupation" => $isMale ? "ব্যবসা" : "গৃহিনী",
            "maritalStatus" => "বিবাহিত"
        ],
        "familyInfo" => [
            "fatherNameBangla" => $fatherNameBn,
            "fatherNameEnglish" => $fatherNameEn,
            "motherNameBangla" => $motherNameBn,
            "motherNameEnglish" => $motherNameEn,
            "spouseNameBangla" => $isMale ? "মোসাম্মৎ কুলসুমা বেগম" : "মোঃ রফিকুল ইসলাম",
            "spouseNameEnglish" => $isMale ? "Mst. Kulsuma Begum" : "Md. Rafiqul Islam"
        ],
        "permanentAddress" => [
            "houseOrHolding" => "বাসা নং " . mt_rand(1, 150),
            "villageOrRoad" => $villageBn,
            "villageOrRoadEnglish" => $villageEn,
            "unionOrWard" => "ওয়ার্ড নং " . mt_rand(1, 9),
            "postOffice" => $postOfficeBn,
            "postOfficeEnglish" => $postOfficeEn,
            "upazila" => $upazilaBn,
            "upazilaEnglish" => $upazilaEn,
            "district" => $districtBn,
            "districtEnglish" => $districtEn,
            "division" => "ঢাকা",
            "divisionEnglish" => "Dhaka"
        ],
        "presentAddress" => [
            "houseOrHolding" => "বাসা নং " . mt_rand(1, 150),
            "villageOrRoad" => $villageBn,
            "villageOrRoadEnglish" => $villageEn,
            "unionOrWard" => "ওয়ার্ড নং " . mt_rand(1, 9),
            "postOffice" => $postOfficeBn,
            "postOfficeEnglish" => $postOfficeEn,
            "upazila" => $upazilaBn,
            "upazilaEnglish" => $upazilaEn,
            "district" => $districtBn,
            "districtEnglish" => $districtEn,
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
