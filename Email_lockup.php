<?php
header('Content-Type: application/json; charset=utf-8');

// ইমেইল বা কুয়েরি রিসিভ করা (GET বা POST উভয় মাধ্যমের জন্য)
$searched_email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $searched_email = isset($input['query']) ? trim($input['query']) : (isset($input['email']) ? trim($input['email']) : (isset($_POST['query']) ? trim($_POST['query']) : (isset($_POST['email']) ? trim($_POST['email']) : '')));
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $searched_email = isset($_GET['query']) ? trim($_GET['query']) : (isset($_GET['q']) ? trim($_GET['q']) : (isset($_GET['email']) ? trim($_GET['email']) : ''));
}

// ইমেইল বা কুয়েরি না থাকলে এরর রেসপন্স
if (empty($searched_email)) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => "অনুগ্রহ করে একটি সঠিক ইমেইল বা কুয়েরি প্রদান করুন।"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ইমেইল ভ্যালিডেশন
if (!filter_var($searched_email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => "অনুগ্রহ করে একটি সঠিক ইমেইল অ্যাড্রেস লিখুন।"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Telefunc পেলোড প্রস্তুত করা
$payload = [
    "file" => "/app/rpc/search.telefunc.ts",
    "name" => "public_search",
    "args" => [
        [
            "piis" => [
                [
                    "type" => "email",
                    "value" => $searched_email,
                    "pii_id" => "1"
                ]
            ],
            "main_breach_id" => "!undefined"
        ]
    ]
];

$jsonData = json_encode($payload, JSON_UNESCAPED_SLASHES);

$headers = [
    "User-Agent: Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36",
    "Content-Type: text/plain",
    "Origin: https://databreach.com",
    "Referer: https://databreach.com/"
];

// cURL রিকোয়েস্ট শুরু
$ch = curl_init("https://databreach.com/_telefunc");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $jsonData,
    CURLOPT_HTTPHEADER     => $headers,
    CURLOPT_TIMEOUT        => 30,
    CURLOPT_SSL_VERIFYPEER => false
]);

$response = curl_exec($ch);
$curl_error = curl_error($ch);
curl_close($ch);

if ($curl_error) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "নেটওয়ার্ক এরর: " . $curl_error
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$decoded = json_decode($response, true);
$raw_data = $decoded['return'] ?? $decoded;

// রেজل্ট থেকে ব্রিচ এবং পাসওয়ার্ড সংক্রান্ত তথ্য ফিল্টার ও পার্স করা
$results_array = $raw_data['ret']['results'] ?? ($raw_data['results'] ?? (is_array($raw_data) ? $raw_data : []));
$formatted_breaches = [];

if (is_array($results_array)) {
    foreach ($results_array as $item) {
        if (is_array($item)) {
            $label = $item['label'] ?? ($item['breach_id'] ?? 'অজ্ঞাত ডাটাবেজ');
            $attack_date = $item['attack_date'] ?? 'তারিখ পাওয়া যায়নি';
            $upload_date = $item['upload_date'] ?? 'N/A';
            $description = isset($item['description_md']) ? strip_tags($item['description_md']) : 'বিবরণ নেই';

            $found_items = $item['found'] ?? [];
            $has_pass = false;
            $found_types = [];

            if (is_array($found_items)) {
                foreach ($found_items as $f) {
                    if (isset($f['type'])) {
                        $found_types[] = $f['type'];
                        if ($f['type'] === 'pass') {
                            $has_pass = true;
                        }
                    }
                }
            }

            $formatted_breaches[] = [
                "database_name" => $label,
                "attack_date"   => $attack_date,
                "upload_date"   => $upload_date,
                "has_password"  => $has_pass,
                "exposed_types" => $found_types,
                "description"   => $description
            ];
        }
    }
}

$total_found = count($formatted_breaches);
$is_safe = ($total_found === 0);

// সুন্দর ও গোছানো JSON রেসপন্স আউটপুট
echo json_encode([
    "status"         => "success",
    "target_email"   => $searched_email,
    "total_breaches" => $total_found,
    "is_safe"        => $is_safe,
    "breaches"       => $formatted_breaches
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>
