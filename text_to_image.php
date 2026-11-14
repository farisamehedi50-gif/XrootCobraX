<?php
declare(strict_types=1);
error_reporting(0);
ini_set('display_errors', '0');

// ==========================================================
// [SECURITY & CORS HEADERS]
// ==========================================================
header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");

// ==========================================================
// [REQUEST & PARAMETER VALIDATION]
// ==========================================================
$inputPrompt = $_GET['prompt'] ?? $_GET['text'] ?? $_GET['q'] ?? '';
$inputPrompt = trim(filter_var($inputPrompt, FILTER_SANITIZE_STRING));

if (empty($inputPrompt)) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "error_code" => 4001,
        "message" => "দয়া করে 'prompt' প্যারামিটারে আপনার টেক্সট লিখুন। উদাহরণ: ?prompt=a realistic portrait of a Bangladeshi cyberpunk city"
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// ==========================================================
// [PERMANENT STORAGE & CACHE ENGINE]
// ==========================================================
$storageDir = __DIR__ . '/ai_images_cache';
if (!is_dir($storageDir)) {
    mkdir($storageDir, 0755, true);
}

$promptHash = hash('sha256', $inputPrompt);
$jsonCacheFile = $storageDir . '/' . $promptHash . '.json';

if (file_exists($jsonCacheFile)) {
    $cachedData = json_decode(file_get_contents($jsonCacheFile), true);
    if (is_array($cachedData)) {
        http_response_code(200);
        echo json_encode($cachedData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}

// ==========================================================
// [POWERFUL AI IMAGE GENERATION ENGINE ROUTE]
// ==========================================================
// এখানে বিশ্বের অন্যতম শক্তিশালী ও ফাস্ট এআই ইমেজ মডেল (Pollinations & Stable Diffusion Flux) ব্যবহার করা হয়েছে
$encodedPrompt = urlencode($inputPrompt);
$seed = crc32($inputPrompt) % 999999;

// হাই-কোয়ালিটি এবং পাওয়ারফুল রেন্ডারিং ডাইরেক্ট ইমেজ লিংক
$aiImageUrl = "https://image.pollinations.ai/prompt/{$encodedPrompt}?width=1024&height=1024&seed={$seed}&model=flux&nologo=true&private=true";

// ==========================================================
// [FINAL JSON PAYLOAD & RESPONSE]
// ==========================================================
$responsePayload = [
    "success" => true,
    "status" => 200,
    "timestamp" => time(),
    "engine" => "Advanced Neural Flux AI Image Synthesizer v3.0",
    "data" => [
        "user_prompt" => $inputPrompt,
        "image_url" => $aiImageUrl,
        "format" => "image/jpeg",
        "resolution" => "1024x1024",
        "cache_status" => "FRESH_AI_GENERATED"
    ],
    "developer_info" => [
        "created_by" => "MehediSanix",
        "channel" => "https://t.me/CobraX_Empire"
    ]
];

$jsonEncodedOutput = json_encode($responsePayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

@file_put_contents($jsonCacheFile, $jsonEncodedOutput, LOCK_EX);

http_response_code(200);
echo $jsonEncodedOutput;
exit;
?>
