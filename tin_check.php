<?php
declare(strict_types=1);
error_reporting(0);
ini_set('display_errors', '0');

header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");

// ==========================================================
// [INPUT VALIDATION]
// ==========================================================
$tin = $_GET['tin'] ?? $_GET['number'] ?? $_GET['q'] ?? '';
$tin = preg_replace('/[^0-9]/', '', $tin);

if (empty($tin)) {
    http_response_code(400);
    echo json_encode([
        "success" => false, 
        "message" => "Please provide a valid TIN number. Example: ?tin=123456789012"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ==========================================================
// [NBR LIVE SCRAPING ENGINE]
// ==========================================================
$url = "https://nbr.sblesheba.com/IncomeTax/Payment";

$cookieFile = tempnam(sys_get_temp_dir(), 'cookie');
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_COOKIEJAR => $cookieFile,
    CURLOPT_COOKIEFILE => $cookieFile,
    CURLOPT_USERAGENT => "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36",
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_TIMEOUT => 15
]);
$response = curl_exec($ch);

libxml_use_internal_errors(true);
$doc = new DOMDocument();
@$doc->loadHTML($response);
$xpath = new DOMXPath($doc);
$tokenNode = $xpath->query("//input[@name='__RequestVerificationToken']")->item(0);
$token = $tokenNode ? $tokenNode->getAttribute('value') : null;

if (!$token) {
    curl_close($ch);
    @unlink($cookieFile);
    http_response_code(502);
    echo json_encode(["success" => false, "message" => "Unable to connect or fetch verification token from NBR server."], JSON_UNESCAPED_UNICODE);
    exit;
}

$postData = http_build_query([
    '__RequestVerificationToken' => $token,
    'TIN' => $tin,
    'btn' => 'Search',
    'AssessmentYear' => '2025-26'
]);

curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $postData,
    CURLOPT_HTTPHEADER => [
        "Content-Type: application/x-www-form-urlencoded",
        "Referer: $url"
    ]
]);
$html = curl_exec($ch);
curl_close($ch);
@unlink($cookieFile);

$doc = new DOMDocument();
@$doc->loadHTML($html);
$xpath = new DOMXPath($doc);

function getInputValue($xpath, $name) {
    $el = $xpath->query("//input[@name='$name']")->item(0);
    if ($el) {
        $val = trim($el->getAttribute('value'));
        return $val === '' ? null : $val;
    }
    return null;
}

function getTextareaValue($xpath, $name) {
    $el = $xpath->query("//textarea[@name='$name']")->item(0);
    return $el ? trim($el->nodeValue) : null;
}

$applicantName = getInputValue($xpath, "ApplicantName");

if (empty($applicantName)) {
    http_response_code(404);
    echo json_encode([
        "success" => false,
        "message" => "No records found for this TIN or invalid TIN provided."
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// ==========================================================
// [JSON RESPONSE]
// ==========================================================
$responsePayload = [
    "success" => true,
    "tin_verification_report" => [
        "Tin" => $tin,
        "Name" => $applicantName,
        "Address" => getTextareaValue($xpath, "Address"),
        "TaxZone" => getInputValue($xpath, "TaxZone"),
        "TaxCircle" => getInputValue($xpath, "TaxCircle"),
        "AccountCode" => getInputValue($xpath, "AccountCode"),
        "OldAccountCode" => getInputValue($xpath, "OldAccountCode")
    ],
    "developer_info" => [
        "create" => "TG: @XrootCodeX",
        "Developer" => "TG: @MehediSanix"
    ]
];

http_response_code(200);
echo json_encode($responsePayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
exit;
?>
