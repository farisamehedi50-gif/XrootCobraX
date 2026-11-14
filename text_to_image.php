<?php
declare(strict_types=1);
error_reporting(0);
ini_set('display_errors', '0');

// ==========================================================
// [CORS & SECURITY HEADERS]
// ==========================================================
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");

// ইউজারের দেওয়া প্রম্পট বা টেক্সট চেক করা
$prompt = $_GET['prompt'] ?? $_GET['text'] ?? $_GET['q'] ?? '';
$prompt = trim(filter_var($prompt, FILTER_SANITIZE_STRING));

if (empty($prompt)) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "দয়া করে ?prompt=your_text লিখে আপনার টেক্সট দিন।"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ==========================================================
// [POWERFUL AI ENGINE - STABLE DIFFUSION / FLUX ULTRA]
// ==========================================================
// এখানে বিশ্বের সবচেয়ে শক্তিশালী ফ্রি এআই ইমেজ জেনারেশন এপিআই রুট ব্যবহার করা হয়েছে
$encodedPrompt = urlencode($prompt);
$randomSeed = random_int(10000, 999999);

// হাই-কোয়ালিটি এবং নিখুঁত রিয়্যালিস্টিক মডেল কনফিগারেশন (Flux / SDXL Ultra)
$powerfulAiUrl = "https://image.pollinations.ai/prompt/{$encodedPrompt}?width=1024&height=1024&seed={$randomSeed}&model=flux&nologo=true&enhance=true";

// সরাসরি JSON বা টেক্সট না পাঠিয়ে, সরাসরি রিয়েল এআই ইমেজ ফাইল ব্রাউজারে বা বটে রিডাইরেক্ট করে দেওয়া
header("Location: " . $powerfulAiUrl);
exit;
?>
