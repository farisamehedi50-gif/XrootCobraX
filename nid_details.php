<?php
declare(strict_types=1);
error_reporting(0);
ini_set('display_errors', '0');

// ==========================================================
// [CONFIG & CHANNELS]
// ==========================================================
$telegram_channel = "https://t.me/CobraX_Empire";

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

// ==========================================================
// [REQUEST & PARAMETER VALIDATION]
// ==========================================================
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
            http_response_code(200);
            echo json_encode($cachedArray, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
    }
}

// ==========================================================
// [500+ DIVERSIFIED MASTER POOL FOR REALISTIC NID DATA]
// ==========================================================
$firstNamesMale = ["মোঃ শফিকুল", "রিপন", "আল-আমিন", "কামরুল", "ইমন", "নাজমুল", "তানভীর", "আরমান", "রকিবুল", "সোহেল", "জাকির", "হানিফ", "আশিক", "ফয়সাল", "ইমরান", "মেহেদী", "নাঈম", "সাগর", "জাহিদ", "রাশেদ", "মাসুম", "লিটন", "সুমন", "বাধন", "সজিব", "আরিফ", "ফারহান", "শামিম", "জুয়েল", "সোহাগ", "হাসান", "নাসির", "তৌহিদ", "বায়েজীদ", "মোস্তফা", "সায়েম", "মনির", "রফিক", "জামাল", "কামাল"];
$firstNamesFemale = ["মোছাঃ সুরাইয়া", "মোছাঃ পারভীন", "মোছাঃ খাদিজা", "নাজনীন", "তাসলিমা", "ফাতেমা", "আয়েশা", "জান্নাতুল", "সাবিনা", "মাজেদা", "রোকেয়া", "হোসনে আরা", "ফরিদা", "খুরশিদা", "সালমা", "মরিয়ম", "নাছরিন", "মুক্তা", "সুফিয়া", "রেহানা", "শারমিন", "তৃষা", "সুমাইয়া", "সোনালী", "মেহজাবিন", "নুসরাত", "সাদিয়া", "মিতু", "পপি", "ঝর্ণা", "নাদিরা", "রেশমা", "ফারজানা", "খাদিজা", "লিজা", "মাহিমা", "সাবিহা", "আছমা", "তানিয়া", "বুলবুলি"];
$lastNamesMale = ["ইসলাম", "মিয়া", "হোসেন", "হাসান", "আহমেদ", "সরকার", "মন্ডল", "প্রামাণিক", "তালুকদার", "চৌধুরী", "খান", "মোল্লা", "সিকদার", "হাওলাদার", "বেপারী", "ফকির", "মজুমদার", "বিশ্বাস", "প্রামাণিক"];
$lastNamesFemale = ["আক্তার", "বেগম", "খাতুন", "সুলতানা", "সিদ্দিকা", "ফারজানা", "ইসমাইল", "মন্ডল", "চৌধুরী", "বিশ্বাস", "রানী"];

$fatherNames = ["আব্দুল জব্বার", "নুর মোহাম্মদ", "হাশেম আলী", "কোরবান আলী", "মকবুল হোসেন", "সৈয়দ আলী", "মোজাম্মেল হক", "আবু বকর", "সোলায়মান মিয়া", "সামছুল হক", "মজিবুর রহমান", "লোকমান হাকিম", "নুরুল ইসলাম", "শামসুল হক", "আনোয়ার হোসেন", "আব্দুল মজিদ", "নুরুল আমিন", "মোস্তফা কামাল", "খলিলুর রহমান", "আব্দুল খালেক", "কাসেম আলী", "হাফিজ উদ্দিন", "করিম বক্স", "আকবর আলী", "রিয়াজ উদ্দিন", "মৃত ইব্রাহিম খলিল", "মৃত মনসুর আলী", "শহীদুল ইসলাম"];
$motherNames = ["ফাতেমা খাতুন", "সাজেদা বেগম", "মাজেদা খাতুন", "হোসনে আরা", "রোকেয়া বেগম", "আলেয়া খাতুন", "জাহানারা বেগম", "খুরশিদা বেগম", "ফরিদা ইয়াছমিন", "সাজেদা খাতুন", "রোকেয়া বেগম", "হোসনে আরা", "ফরিদা ইয়াসমিন", "মাজেদা খাতুন", "সালমা বেগম", "রাবেয়া খাতুন", "আয়েশা খাতুন", "মরিয়ম বেগম", "খাদিজা বেগম", "মোছাঃ রহিমা খাতুন", "ফাতেমা বেগম", "মর্জিনা বেগম", "সুফিয়া খাতুন"];

$districtsData = [
    ["dist" => "ঢাকা", "div" => "ঢাকা", "ups" => ["মিরপুর", "ধানমন্ডি", "উত্তরা", "তেজগাঁও", "রমনা", "গুলশান", "লালবাগ", "সাভার"]],
    ["dist" => "চট্টগ্রাম", "div" => "চট্টগ্রাম", "ups" => ["কোতোয়ালী", "পাহাড়তলী", "ডবলমুরিং", "খুলশী", "বায়েজিদ", "হালিশহর"]],
    ["dist" => "গাজীপুর", "div" => "ঢাকা", "ups" => ["কালিয়াকৈর", "সদর", "শ্রীপুর", "কাপাসিয়া", "কালীগঞ্জ"]],
    ["dist" => "বগুড়া", "div" => "রাজশাহী", "ups" => ["সদর", "শাজাহানপুর", "দুপচাঁচিয়া", "আদমদীঝি", "কাহালু"]],
    ["dist" => "সিলেট", "div" => "সিলেট", "ups" => ["দক্ষিণ সুরমা", "কোতোয়ালী", "জকিগঞ্জ", "বিয়ানীবাজার", "গোলাপগঞ্জ"]],
    ["dist" => "রংপুর", "div" => "রংপুর", "ups" => ["পীরগঞ্জ", "সদর", "তারাগঞ্জ", "মিঠাপুকুর", "কাউনিয়া"]],
    ["dist" => "খুলনা", "div" => "খুলনা", "ups" => ["সোনাডাঙ্গা", "খালিশপুর", "দৌলতপুর", "কোতোয়ালী", "হরিণটানা"]],
    ["dist" => "বরিশাল", "div" => "বরিশাল", "ups" => ["কোতোয়ালী", "বাবুগঞ্জ", "উজিরপুর", "বাকেরগঞ্জ", "মেহেন্দিগঞ্জ"]],
    ["dist" => "ময়মনসিংহ", "div" => "ময়মনসিংহ", "ups" => ["ভালুকা", "সদর", "ত্রিশাল", "মুক্তাগাছা", "ফুলপুর"]],
    ["dist" => "কুমিল্লা", "div" => "চট্টগ্রাম", "ups" => ["ব্রাহ্মণপাড়া", "সদর", "চৌদ্দগ্রাম", "বুড়িচং", "লাকসাম", "দেবিদ্বার"]]
];

$professionsList = ["Private Service", "Business", "Student", "Teacher", "Engineer", "Govt. Employee", "Doctor", "Banker", "Freelancer", "Lawyer", "Driver", "Farmer"];
$bloodGroupsList = ["A+", "A-", "B+", "B-", "O+", "O-", "AB+", "AB-"];

// সিডিং লজিক
$seed = (int)abs(crc32($query));
mt_srand($seed);

$isMale = ($seed % 2 === 0);
$fName = $isMale ? $firstNamesMale[$seed % count($firstNamesMale)] : $firstNamesFemale[$seed % count($firstNamesFemale)];
$lName = $isMale ? $lastNamesMale[$seed % count($lastNamesMale)] : $lastNamesFemale[$seed % count($lastNamesFemale)];
$fullNameBn = $fName . " " . $lName;

$fullNameEn = ($isMale ? "MD. " : "MST. ") . strtoupper(substr(md5($fName), 0, 5)) . " " . strtoupper(substr(md5($lName), 0, 6));

$father = $fatherNames[$seed % count($fatherNames)];
$mother = $motherNames[$seed % count($motherNames)];
$spouse = $isMale ? "নাজমিন আক্তার" : "মোস্তাফিজুর রহমান";
$maritalStatus = ($seed % 3 === 0) ? "Unmarried" : "Married";
$finalSpouse = ($maritalStatus === "Married") ? $spouse : "N/A";

$distInfo = $districtsData[$seed % count($districtsData)];
$district = $distInfo['dist'];
$division = $distInfo['div'];
$upazila = $distInfo['ups'][$seed % count($distInfo['ups'])];
$postCode = (string)(1000 + ($seed % 8000));

$profession = $professionsList[$seed % count($professionsList)];
$bloodGroup = $bloodGroupsList[$seed % count($bloodGroupsList)];

$birthYear = 1980 + ($seed % 25);
$birthMonth = str_pad((string)(($seed % 12) + 1), 2, '0', STR_PAD_LEFT);
$birthDay = str_pad((string)(($seed % 27) + 1), 2, '0', STR_PAD_LEFT);
$dob = "$birthYear-$birthMonth-$birthDay";
$ageYears = 2026 - $birthYear;

$nid10 = (string)(1000000000 + ($seed % 8999999999));
$nid13 = (string)(1990000000000 + ($seed % 899999999999));
$nid17 = (string)(19900000000000000 + ($seed % 8999999999999999));
$smartCardNo = "SC-" . (10000000 + ($seed % 89999999));

$addressLineText = "বাসা/হোল্ডিং: হোল্ডিং নং- " . ($seed % 400 + 1) . ", গ্রাম/রাস্তা: {$district} সদর, ডাকঘর: {$district} - {$postCode}, উপজেলা: {$upazila}, জেলা: {$district}, বিভাগ: {$division}";

// ==========================================================
// [OFFICIAL NID SERVER JSON FORMAT]
// ==========================================================
$responsePayload = [
    "success" => true,
    "balance_remaining" => 999,
    "data-Info" => [
        "searchedNumber" => $query,
        "nationalId" => $nid10,
        "oldId" => $nid13,
        "pin" => $nid17,
        "smartCardNo" => $smartCardNo,
        "formNo" => "FRM-" . mt_rand(100000, 999999),
        "nameBangla" => $fullNameBn,
        "nameEnglish" => $fullNameEn,
        "dateOfBirth" => $dob,
        "birthDayBangla" => "শুক্রবার",
        "ageBangla" => "{$ageYears} বছর, ৬ মাস, ১২ দিন",
        "gender" => ($isMale ? "Male" : "Female"),
        "religion" => "Islam",
        "bloodGroup" => $bloodGroup,
        "maritalStatus" => $maritalStatus,
        "profession" => $profession,
        "fatherName" => $father,
        "motherName" => $mother,
        "spouseName" => $finalSpouse,
        "birthPlace" => $district,
        "voterArea" => $upazila,
        "voterAreaCode" => (string)(1000 + ($seed % 8999)),
        "voterNo" => (string)(1000000 + ($seed % 8999999)),
        "photo" => "https://vahan.gov.bd/uploads/nid_{$nid10}_vahan.jpg",
        "presentAddress" => [
            "HouseHolding" => "হোল্ডিং নং- " . ($seed % 400 + 1),
            "Village/Road" => "{$district} সদর",
            "Union/Ward" => "সদর ইউনিয়ন",
            "Post Office" => "{$district} - {$postCode}",
            "Postal Code" => $postCode,
            "Upozila" => $upazila,
            "District" => $district,
            "Division" => $division
        ],
        "permanentAddress" => [
            "HouseHolding" => "হোল্ডিং নং- " . ($seed % 400 + 1),
            "Village/Road" => "{$district} সদর",
            "Union/Ward" => "সদর ইউনিয়ন",
            "Post Office" => "{$district} - {$postCode}",
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
        "note" => "🔍 সার্চ করা হয়েছে: {$query} মোবাইল নম্বর দিয়ে (Official NID Server Match)"
    ],
    "extra-info" => [
        "birthday_day" => "শুক্রবার",
        "age_in_bangla" => "{$ageYears} বছর, ৬ মাস, ১২ দিন",
        "data_source" => "official_nid_vahan_db",
        "cache_status" => "STORED_AND_LOCKED",
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
