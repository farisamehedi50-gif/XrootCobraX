<?php
declare(strict_types=1);
error_reporting(0);

$prompt = $_GET['prompt'] ?? $_GET['text'] ?? $_GET['q'] ?? '';
$prompt = trim($prompt);

if (empty($prompt)) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(["success" => false, "message" => "দয়া করে ?prompt=your_text লিখে দিন।"], JSON_UNESCAPED_UNICODE);
    exit;
}

// সরাসরি রিয়েল এআই ইমেজ জেনারেটর এপিআই লিংক রিডাইরেক্ট বা ফেচ করা
$encodedPrompt = urlencode($prompt);
$aiImageUrl = "https://image.pollinations.ai/prompt/{$encodedPrompt}?width=1024&height=1024&nologo=true";

// সরাসরি ছবি ব্রাউজারে দেখানোর জন্য রিডাইরেক্ট করে দিতে পারেন
header("Location: " . $aiImageUrl);
exit;
?>
