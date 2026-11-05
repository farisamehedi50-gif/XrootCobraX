<?php
header('Content-Type: application/json; charset=utf-8');

// Step 1: প্রম্পট রিসিভ করা (GET বা POST উভয় মাধ্যমের জন্য)
$prompt = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $prompt = isset($input['prompt']) ? trim($input['prompt']) : (isset($_POST['prompt']) ? trim($_POST['prompt']) : '');
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $prompt = isset($_GET['prompt']) ? trim($_GET['prompt']) : (isset($_GET['q']) ? trim($_GET['q']) : '');
}

// প্রম্পট না থাকলে এরর রেসপন্স
if ($prompt === '') {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "status"  => "error",
        "message" => "অনুগ্রহ করে ছবির জন্য একটি সঠিক 'prompt' প্রদান করুন।"
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// Step 2: API endpoint তৈরি
$apiUrl = 'https://yabes-api.pages.dev/api/ai/image/imagen3-0?prompt=' . urlencode($prompt) . '&ratio=1:1';

// Step 3: cURL সেটআপ
$ch = curl_init($apiUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT        => 60,
    CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_2_0,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_HTTPHEADER     => [
        'User-Agent: Mozilla/5.0 (Linux; Android 13; 220333QAG Build/TKQ1.221114.001) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.7390.43 Mobile Safari/537.36',
        'Accept: application/json, text/plain, */*'
    ],
]);

$response = curl_exec($ch);
$error = curl_error($ch);
curl_close($ch);

// Step 4: নেটওয়ার্ক বা cURL এরর হ্যান্ডেলিং
if ($response === false) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "status"  => "error",
        "message" => "নেটওয়ার্ক এরর: " . $error
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

$imageUrl = '';

// Step 5: JSON রেসপন্স পার্স করা
$decoded = json_decode($response, true);
if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
    if (!empty($decoded['url'])) {
        $imageUrl = $decoded['url'];
    }
}

// Step 6: যদি API সরাসরি প্লেইন টেক্সট URL রিটার্ন করে
if (empty($imageUrl) && filter_var(trim($response), FILTER_VALIDATE_URL)) {
    $imageUrl = trim($response);
}

// Step 7: সফল বা ব্যর্থ রেসপন্স আউটপুট ফরম্যাট
if (!empty($imageUrl)) {
    http_response_code(200);
    echo json_encode([
        "success"          => true,
        "status"           => "SUCCESS",
        "prompt"           => $prompt,
        "image_url"        => $imageUrl,
        "developer_info"   => [
            "create"   => "TG: @XrootCodeX",
            "Developer" => "TG: @MehediSanix"
        ]
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
} else {
    http_response_code(500);
    echo json_encode([
        "success"          => false,
        "status"           => "ERROR",
        "message"          => "এপিআই থেকে কোনো ইমেজ লিংক পাওয়া যায়নি।",
        "raw_response"     => $decoded ?? $response,
        "developer_info"   => [
            "create"   => "TG: @XrootCodeX",
            "Developer" => "TG: @MehediSanix"
        ]
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}
exit;
?>
