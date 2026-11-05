<?php
/**
 * Nagad Ban API - PHP Backend
 * (ROH Prank Hub লজিক অনুসরণে তৈরি)
 */

// হেডার কনফিগারেশন (JSON আউটপুট এবং CORS এলাউ করার জন্য)
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");

// এরর রিপোর্টিং বন্ধ রাখা যাতে JSON ফরম্যাট নষ্ট না হয়
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
// ব্যাকওয়ার্ড কম্প্যাটিবিলিটি: phone প্যারামিটারও সাপোর্ট
if (empty($number) && isset($_GET['phone'])) {
    $number = preg_replace('/[^0-9+]/', '', $_GET['phone']);
}

// ভ্যালিডেশন চেক
if (empty($number)) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => "Bad Request! Number is required. Use ?number=01XXXXXXXXX"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// বাংলাদেশি ফোন নম্বর ভ্যালিডেশন
if (!preg_match('/^01[3-9]\d{8}$/', $number)) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => "Invalid Bangladeshi phone number format.",
        "number" => $number
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * ==========================================================
 * মূল "ব্যান" অ্যাকশন ট্রিগার করার সেকশন
 * (Prank Hub-এর মতোই সিমুলেটেড)
 * ==========================================================
 */

$api_success = true;

// র্যান্ডম ট্রানজ্যাকশন আইডি জেনারেট
$trxId = 'NAGAD' . strtoupper(substr(md5(mt_rand() . microtime()), 0, 8));

$api_response_msg = "Nagad account permanently banned for {$number}";

/*
// cURL উদাহরণ (যদি ভবিষ্যতে আসল গেটওয়ে যোগ করতে চান):
$ch = curl_init("https://example-gateway.com/ban?phone={$number}");
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
            "trx_id" => $trxId,
            "action_type" => "Permanent Block",
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
        "message" => "Failed to connect with the Nagad gateway."
    ], JSON_UNESCAPED_UNICODE);
}
