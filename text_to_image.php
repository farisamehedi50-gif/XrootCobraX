<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '0');

// ==========================================================
// [SECURITY & CORS HEADERS]
// ==========================================================
header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");

// ==========================================================
// [IMAGE GENERATION RENDER ENGINE ROUTE]
// ==========================================================
if (isset($_GET['render']) && $_GET['render'] === 'true') {
    $promptText = $_GET['prompt'] ?? 'AI Art Canvas';
    $promptText = trim(filter_var($promptText, FILTER_SANITIZE_STRING));
    if (empty($promptText)) {
        $promptText = "Abstract Design";
    }

    // ক্যানভাস সাইজ নির্ধারণ
    $width = 800;
    $height = 800;
    $image = imagecreatetruecolor($width, $height);

    // কালার প্যালেট তৈরি (প্রফেশনাল গ্রুডিয়েন্ট ও ডিজাইন ব্যাকগ্রাউন্ড)
    $bgTop = imagecolorallocate($image, 15, 23, 42);      // Dark Slate
    $bgBottom = imagecolorallocate($image, 30, 58, 138);   // Deep Blue
    
    // ব্যাকগ্রাউন্ড গ্রেডিয়েন্ট ফিল আপ
    for ($y = 0; $y < $height; $y++) {
        $r = (int)($ibaseR = 15 + ($y / $height) * 15);
        $g = (int)($ibaseG = 23 + ($y / $height) * 35);
        $b = (int)($ibaseB = 42 + ($y / $height) * 96);
        $rowColor = imagecolorallocate($image, $r, $g, $b);
        imageline($image, 0, $y, $width, $y, $rowColor);
    }

    // র্যান্ডম শৈল্পিক জিওমেট্রিক শেপ বা ডিজাইন রেন্ডার করা (প্রম্পটের সিড অনুযায়ী)
    $seed = crc32($promptText);
    mt_srand($seed);

    for ($i = 0; $i < 15; $i++) {
        $shapeX = mt_rand(50, $width - 50);
        $shapeY = mt_rand(50, $height - 50);
        $shapeSize = mt_rand(40, 200);
        $randColor = imagecolorallocatealpha($image, mt_rand(50, 255), mt_rand(100, 255), mt_rand(150, 255), 60);
        
        if ($i % 2 === 0) {
            imagefilledellipse($image, $shapeX, $shapeY, $shapeSize, $shapeSize, $randColor);
        } else {
            imagefilledrectangle($image, $shapeX, $shapeY, $shapeX + $shapeSize, $shapeY + $shapeSize, $randColor);
        }
    }

    // টেক্সট বক্স এবং বর্ডার ডিজাইন
    $boxColor = imagecolorallocatealpha($image, 15, 23, 42, 40);
    imagefilledrectangle($image, 50, $height - 150, $width - 50, $height - 50, $boxColor);
    
    $textColor = imagecolorallocate($image, 255, 255, 255);
    $accentColor = imagecolorallocate($image, 96, 165, 250);

    // পিএইচপির বিল্ট-ইন ফন্ট দিয়ে টেক্সট বসানো
    imagestring($image, 5, 80, $height - 130, "AI GENERATED ARTWORK:", $accentColor);
    
    // বড় প্রম্পট টেক্সট লাইনে ভাগ করে প্রিন্ট করা
    $wrappedText = wordwrap($promptText, 40, "\n", true);
    $lines = explode("\n", $wrappedText);
    $currentY = $height - 105;
    
    foreach ($lines as $line) {
        imagestring($image, 4, 80, $currentY, $line, $textColor);
        $currentY += 20;
    }

    // ছবি ব্রাউজার বা আউটপুটে পাঠানো
    header('Content-Type: image/jpeg');
    imagejpeg($image, null, 95);
    imagedestroy($image);
    exit;
}

// ==========================================================
// [MAIN API REQUEST HANDLER]
// ==========================================================
$inputPrompt = $_GET['prompt'] ?? $_GET['text'] ?? $_GET['q'] ?? '';
$inputPrompt = trim(filter_var($inputPrompt, FILTER_SANITIZE_STRING));

if (empty($inputPrompt)) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "error_code" => 4001,
        "message" => "দয়া করে 'prompt' প্যারামিটারে আপনার টেক্সট লিখুন। উদাহরণ: ?prompt=a beautiful digital sunset"
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// ==========================================================
// [STORAGE & CACHE ENGINE FOR PERMANENT LINKS]
// ==========================================================
$storageDir = __DIR__ . '/generated_images_cache';
if (!is_dir($storageDir)) {
    mkdir($storageDir, 0755, true);
}

$promptHash = hash('sha256', $inputPrompt);
$jsonCacheFile = $storageDir . '/' . $promptHash . '.json';

// যদি আগে থেকেই এই প্রম্পটের ডেটা থাকে, তবে ক্যাশ থেকে রিটার্ন করবে
if (file_exists($jsonCacheFile)) {
    $cachedData = json_decode(file_get_contents($jsonCacheFile), true);
    if (is_array($cachedData)) {
        http_response_code(200);
        echo json_encode($cachedData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}

// বর্তমান ডোমেইন ও প্রটোকল বের করা ডাইনামিক লিংকের জন্য
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$hostName = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptPath = $_SERVER['PHP_SELF'];

// সরাসরি রেন্ডার ইমেজ লিংক তৈরি
$directImageUrl = $protocol . $hostName . $scriptPath . "?render=true&prompt=" . urlencode($inputPrompt);

// ফাইনাল রেসপন্স অ্যারে
$responsePayload = [
    "success" => true,
    "status" => 200,
    "timestamp" => time(),
    "engine" => "Self-Hosted PHP GD Art Synthesizer v2.0",
    "data" => [
        "user_prompt" => $inputPrompt,
        "image_url" => $directImageUrl,
        "format" => "image/jpeg",
        "resolution" => "800x800",
        "cache_status" => "FRESH_GENERATED"
    ],
    "developer_info" => [
        "created_by" => "MehediSanix",
        "channel" => "https://t.me/CobraX_Empire"
    ]
];

$jsonEncodedOutput = json_encode($responsePayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

// ক্যাশ ফাইলে সেভ করে রাখা যাতে পরবর্তীতে দ্রুত রেসপন্স দেয়
@file_put_contents($jsonCacheFile, $jsonEncodedOutput, LOCK_EX);

http_response_code(200);
echo $jsonEncodedOutput;
exit;
?>
