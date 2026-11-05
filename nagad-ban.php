<?php
declare(strict_types=1);
error_reporting(0);
ini_set('display_errors', '0');

header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");

// নম্বর ইনপুট গ্রহণ
$phone = $_GET['phone'] ?? $_GET['number'] ?? '';
$phone = preg_replace('/[^0-9+]/', '', $phone);

if (empty($phone) || strlen($phone) < 10) {
    http_response_code(400);
    echo json_encode([
        "status" => false,
        "response_code" => 400,
        "message" => "Invalid or missing mobile number parameter."
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// রিয়েলিস্টিক রেসপন্স ডেটা তৈরি
$responsePayload = [
    "status" => true,
    "response_code" => 200,
    "timestamp" => date("Y-m-d H:i:s"),
    "server_gateway" => "MFS_SECURE_GATEWAY_v4.2",
    "account_details" => [
        "mobile_number" => $phone,
        "account_type" => "Merchant / Personal",
        "kyc_status" => "Verified",
        "current_status" => "Suspended / Restricted",
        "action_taken" => "Temporary Security Lock & Restriction Applied",
        "reference_id" => "MFS-REF-" . strtoupper(bin2hex(random_bytes(4))),
        "tracking_id" => "TRX-" . time() . "-" . rand(1000, 9999)
    ],
    "message" => "Operation executed successfully. Account state modified.",
    "developer_info" => [
        "create" => "TG: @XrootCodeX",
        "Developer" => "TG: @MehediSanix"
    ]
];

http_response_code(200);
echo json_encode($responsePayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
exit;
?>
