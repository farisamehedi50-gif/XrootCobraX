<?php
// হেডার ডিক্লারেশন (JSON রেসপন্স এবং CORS অ্যালাউ করার জন্য)
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');

// রিকোয়েস্ট থেকে ফোন নম্বর গ্রহণ (GET অথবা POST)
$phone = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = $_POST['phone'] ?? $_POST['number'] ?? '';
} else {
    $phone = $_GET['phone'] ?? $_GET['number'] ?? '';
}

// ফোন নম্বর ক্লিন করা
$phone = trim(strip_tags($phone));

// যদি নম্বর না দেওয়া হয়
if (empty($phone)) {
    $phone = "01540758983"; 
}

// র্যান্ডম ট্রানজ্যাকশন আইডি বা রেফারেন্স জেনারেট করা
$trxId = 'NAGAD' . strtoupper(substr(md5(mt_rand()), 0, 8));

// আরও তথ্যযুক্ত JSON রেসপন্স
$response = [
    "status" => "Nagad ban Successfully",
    "status_code" => 200,
    "number" => $phone,
    "trx_id" => $trxId,
    "action_type" => "Permanent Block",
    "timestamp" => date("Y-m-d H:i:s"),
    "owner" => "XrootCodeX"
];

echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>
