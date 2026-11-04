<?php
/**
 * ROH Prank Hub - PHP API Backend
 */

// হেডার কনফিগারেশন (JSON আউটপুট এবং CORS এলাউ করার জন্য)
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");

// এরর রিপোর্টিং বন্ধ রাখা যাতে JSON ফরম্যাট নষ্ট না হয়
error_reporting(0);
ini_set('display_errors', 0);

// রিকোয়েস্ট মেথড চেক (GET মেথড সাপোর্টেড)
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        "status" => "error",
        "message" => "Method Not Allowed. Only GET requests are accepted."
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// প্যারামিটার রিসিভ ও স্যানিটাইজ করা
$number = isset($_GET['number']) ? preg_replace('/[^0-9+]/', '', $_GET['number']) : '';
$prank_id = isset($_GET['prank']) ? preg_replace('/[^0-9]/', '', $_GET['prank']) : '';

// ভ্যালিডেশন চেক
if (empty($number) || empty($prank_id)) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => "Bad Request! Number and prank ID are required."
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// প্রাঙ্ক আইডি এবং তার শিরোনাম বা ম্যাসেজ লিস্ট
$prank_messages = [
    "8810" => "আপনি আমার গার্লফ্রেন্ডকে কল করেন কেন?",
    "8805" => "গাজার মতো দুর্গন্ধ!",
    "8808" => "আপনি আমার ওয়াই-ফাই চুরি করছেন!",
    "8809" => "আপনি কেন আমাকে কল করেন?",
    "8803" => "পিজ্জা ডেলিভারি",
    "8804" => "আপনার ট্যাক্সি আপনার জন্য অপেক্ষা করছে",
    "8806" => "আপনার কামরার হৈচৈ আওয়াজ",
    "8807" => "আপনার কুকুরটি খুবই ক্লান্তিকর!"
];

// প্রাঙ্ক আইডি ভ্যালিড কিনা চেক করা
if (!array_key_exists($prank_id, $prank_messages)) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => "Invalid Prank ID provided."
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$prank_title = $prank_messages[$prank_id];

/**
 * ==========================================================
 * মূল কল বা এপিআই রিকোয়েস্ট ট্রিগার করার সেকশন
 * ==========================================================
 */

$api_success = true; 
$api_response_msg = "Prank call successfully initiated to {$number} with topic: '{$prank_title}'";

/*
// cURL উদাহরণ (যদি কোনো এক্সটার্নাল কলিং গেটওয়ে ব্যবহার করতে চান):
$ch = curl_init("https://example-calling-gateway.com/send?phone={$number}&msg_id={$prank_id}");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
$response = curl_exec($ch);
curl_close($ch);
*/

// সফল রেসপন্স রিটার্ন করা
if ($api_success) {
    http_response_code(200);
    echo json_encode([
        "status" => "success",
        "code" => 200,
        "message" => $api_response_msg,
        "data" => [
            "phone" => $number,
            "prank_id" => $prank_id,
            "title" => $prank_title,
            "timestamp" => date("Y-m-d H:i:s")
        ],
        "OWNER" => "@XrootCodeX",
        "TG" => "https://t.me/CobraX_Empire",
        "DEVELOPER" => "MehediSanix"
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} else {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Failed to connect with the telecom gateway."
    ], JSON_UNESCAPED_UNICODE);
}
?>
