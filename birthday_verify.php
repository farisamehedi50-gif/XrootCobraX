<?php
/**
 * Birth Certificate Verification API
 * Optimized & Fast Working Version - Error Free
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

// ============================================
// লগ কনফিগারেশন
// ============================================
define('LOG_FILE', __DIR__ . '/api_log.txt');
define('LOG_ENABLED', true);

function writeLog($message, $type = 'INFO') {
    if (!LOG_ENABLED) return;
    $timestamp = date('H:i:s');
    $logEntry = "[$timestamp] [$type] $message" . PHP_EOL;
    @file_put_contents(LOG_FILE, $logEntry, FILE_APPEND | LOCK_EX);
}

if (isset($_GET['action'])) {
    if ($_GET['action'] === 'view_log') {
        $lines = isset($_GET['lines']) ? (int)$_GET['lines'] : 50;
        $content = file_exists(LOG_FILE) ? @file_get_contents(LOG_FILE) : "No logs found.";
        $lines_array = explode(PHP_EOL, $content);
        $total_lines = count($lines_array);
        $start = max(0, $total_lines - $lines);
        header('Content-Type: text/plain; charset=utf-8');
        echo implode(PHP_EOL, array_slice($lines_array, $start));
        exit;
    }
    if ($_GET['action'] === 'clear_log') {
        if (file_exists(LOG_FILE)) @unlink(LOG_FILE);
        echo json_encode(['success' => true]);
        exit;
    }
}

define('BASE_URL', 'https://everify.bdris.gov.bd');
define('OCR_API_KEY', 'K87822842088957');

// ============================================
// ১. GIF কে PNG তে কনভার্ট (GD Check সহ)
// ============================================
function gifToPng($gifData) {
    if (!extension_loaded('gd')) {
        return $gifData;
    }
    try {
        $img = @imagecreatefromstring($gifData);
        if (!$img) return $gifData;
        
        ob_start();
        @imagepng($img);
        $pngData = ob_get_clean();
        @imagedestroy($img);
        
        return $pngData;
    } catch (Exception $e) {
        return $gifData;
    }
}

// ============================================
// ২. ইমেজ প্রি-প্রসেসিং (Fast & Optimized)
// ============================================
function preprocessImage($imageData) {
    if (!extension_loaded('gd')) {
        return $imageData;
    }
    try {
        $img = @imagecreatefromstring($imageData);
        if (!$img) return $imageData;
        
        $width = @imagesx($img);
        $height = @imagesy($img);
        
        $newWidth = $width * 2;
        $newHeight = $height * 2;
        $newImg = @imagecreatetruecolor($newWidth, $newHeight);
        @imagecopyresampled($newImg, $img, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        @imagefilter($newImg, IMG_FILTER_CONTRAST, -50);
        @imagefilter($newImg, IMG_FILTER_BRIGHTNESS, 10);
        
        ob_start();
        @imagepng($newImg);
        $processedData = ob_get_clean();
        
        @imagedestroy($img);
        @imagedestroy($newImg);
        
        return $processedData;
    } catch (Exception $e) {
        return $imageData;
    }
}

// ============================================
// ৩. ম্যাথ পার্সিং
// ============================================
function parseAndSolveMath($text) {
    if (empty(trim($text))) return null;
    
    $patterns = [
        '/(\d+)\s*\+\s*(\d+)\s*=\s*\?/i',
        '/(\d+)\s*\+\s*(\d+)\s*/i',
        '/(\d+)\s*([\+\-])\s*(\d+)\s*=\s*\?/i',
        '/(\d+)\s*([\+\-])\s*(\d+)/i',
        '/(\d+)\s*\+\s*(\d+)/i',
        '/(\d+)\s*\-\s*(\d+)/i',
    ];
    
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $text, $match)) {
            if (isset($match[3]) && isset($match[2])) {
                $num1 = (int)$match[1];
                $operator = $match[2];
                $num2 = (int)$match[3];
                $answer = ($operator == '+') ? $num1 + $num2 : $num1 - $num2;
                if ($answer >= 0 && $answer <= 100) return $answer;
            }
            if (isset($match[2]) && !isset($match[3])) {
                $num1 = (int)$match[1];
                $num2 = (int)$match[2];
                $answer = $num1 + $num2;
                if ($answer >= 0 && $answer <= 100) return $answer;
            }
        }
    }
    return null;
}

// ============================================
// ৪. জেন্ডার ডিটেক্ট
// ============================================
function detectGender($nameBn, $nameEn) {
    $nameLower = strtolower(trim($nameEn));
    $nameBnLower = strtolower(trim($nameBn));
    
    $maleNames = ['md', 'mohammad', 'muhammad', 'mohammed', 'mamun', 'rahim', 'karim',
                  'jabbar', 'salam', 'kader', 'shah', 'ali', 'hasan', 'hossain',
                  'yousuf', 'yusuf', 'ibrahim', 'ismail', 'abdul', 'abdullah',
                  'umar', 'usman', 'hanif', 'rashid', 'aziz', 'rahman',
                  'latif', 'saleh', 'nasir', 'tariq', 'zaman', 'ahmed',
                  'shihab', 'pervej', 'zahed', 'chowdhury', 'kabir', 'sarker',
                  'mia', 'islam', 'khan', 'sheikh'];
    
    $femaleNames = ['mst', 'begum', 'khatun', 'akter', 'sultana', 'nur', 'nasima',
                    'nargis', 'jahan', 'farida', 'fatema', 'fatima', 'hamida',
                    'halima', 'khadija', 'mariam', 'maria', 'monira', 'mousumi',
                    'nadia', 'nasrin', 'nazma', 'naznin', 'nurjahan', 'parvin',
                    'rokeya', 'sabina', 'safina', 'sahida', 'salma', 'samira',
                    'sanjida', 'sathi', 'selina', 'shahida', 'shakila', 'shamima',
                    'shanta', 'sheuli', 'shila', 'shirin', 'shobha', 'sima',
                    'tabassum', 'taslima', 'tina', 'tripti', 'uma', 'urmi', 'yasmin', 'zannat', 'zohra'];
    
    foreach ($maleNames as $name) {
        if (strpos($nameLower, $name) !== false) return ['genderBn' => 'পুরুষ', 'genderEn' => 'Male'];
    }
    foreach ($femaleNames as $name) {
        if (strpos($nameLower, $name) !== false) return ['genderBn' => 'নারী', 'genderEn' => 'Female'];
    }
    
    $maleBn = ['মো', 'মোঃ', 'মোহাম্মদ', 'মুহাম্মদ', 'আব্দুল', 'আলী', 'হাসান', 
               'হোসেন', 'ইসলাম', 'খান', 'শেখ', 'সরকার', 'মিয়া', 'কাদের',
               'সালাম', 'রহিম', 'করিম', 'জব্বার', 'শাহ', 'ইউসুফ', 'ইব্রাহিম'];
    
    $femaleBn = ['মোসা', 'মোছা', 'বেগম', 'খাতুন', 'আক্তার', 'সুলতানা', 'নুর', 
                 'নাসিমা', 'নার্গিস', 'জাহান', 'ফরিদা', 'ফাতেমা', 'হামিদা', 'হালিমা'];
    
    foreach ($maleBn as $name) {
        if (strpos($nameBnLower, $name) !== false) return ['genderBn' => 'পুরুষ', 'genderEn' => 'Male'];
    }
    foreach ($femaleBn as $name) {
        if (strpos($nameBnLower, $name) !== false) return ['genderBn' => 'নারী', 'genderEn' => 'Female'];
    }
    
    return ['genderBn' => 'অজানা', 'genderEn' => 'Unknown'];
}

// ============================================
// ৫. ধর্ম নির্ণয়
// ============================================
function determineReligion($nameBn, $nameEn, $fatherName, $motherName) {
    $full = strtolower($nameBn . ' ' . $nameEn . ' ' . $fatherName . ' ' . $motherName);
    $islam = ['md', 'mst', 'মোঃ', 'বেগম', 'মিয়া', 'সরকার', 'মন্ডল', 'ইসলাম', 'islam', 'আক্তার', 'আল'];
    $hindu = ['শ্রী', 'কুমার', 'চন্দ্র', 'দাস', 'রায়', 'দেব'];
    $christian = ['costa', 'কস্তা', 'ক্রুস', 'ক্রুজ', 'পালমা'];
    
    foreach ($islam as $key) if (strpos($full, $key) !== false) return ['bn' => 'ইসলাম', 'en' => 'Islam'];
    foreach ($hindu as $key) if (strpos($full, $key) !== false) return ['bn' => 'হিন্দু', 'en' => 'Hindu'];
    foreach ($christian as $key) if (strpos($full, $key) !== false) return ['bn' => 'খ্রিস্টান', 'en' => 'Christian'];
    return ['bn' => 'N/A', 'en' => 'N/A'];
}

// ============================================
// ৬. ক্যাপচা সলভ (OCR) - Fast
// ============================================
function solveCaptchaWithOCR($imageData) {
    try {
        $pngData = gifToPng($imageData);
        $processedImage = preprocessImage($pngData);
        $imageBase64 = base64_encode($processedImage);
        
        $engines = [2, 3, 1];
        
        foreach ($engines as $engine) {
            $postData = [
                'apikey' => OCR_API_KEY,
                'language' => 'eng',
                'isOverlayRequired' => 'false',
                'base64Image' => 'data:image/png;base64,' . $imageBase64,
                'OCREngine' => $engine,
                'detectOrientation' => 'false',
                'scale' => 'true'
            ];
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://api.ocr.space/parse/image');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 8);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200 && $response) {
                $json = json_decode($response, true);
                if (isset($json['OCRExitCode']) && $json['OCRExitCode'] == 1) {
                    $text = $json['ParsedResults'][0]['ParsedText'] ?? '';
                    if (!empty(trim($text))) {
                        $result = parseAndSolveMath($text);
                        if ($result !== null) {
                            return $result;
                        }
                    }
                }
            }
        }
        return null;
    } catch (Exception $e) {
        return null;
    }
}

// ============================================
// ৭. ব্রুট ফোর্স (ফ্যালব্যাক) - Fast
// ============================================
function bruteForceCaptcha($sessionCookies, $token, $captchaToken, $ubrn, $birthDate) {
    $answers = [42, 50, 55, 60, 65, 70, 75, 80, 25, 30, 35, 40, 45];
    
    foreach ($answers as $testAns) {
        $postData = [
            '__RequestVerificationToken' => $token,
            'UBRN' => $ubrn,
            'BirthDate' => $birthDate,
            'CaptchaDeText' => $captchaToken,
            'CaptchaInputText' => (string)$testAns
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, BASE_URL . '/UBRNVerification/Search');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 6);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded',
            'Origin: ' . BASE_URL,
            'Referer: ' . BASE_URL . '/'
        ]);
        if ($sessionCookies) {
            curl_setopt($ch, CURLOPT_COOKIE, $sessionCookies);
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode == 200 && $response) {
            $responseLower = strtolower($response);
            if (strpos($responseLower, 'captcha') === false && strpos($responseLower, 'not valid') === false) {
                if (strpos($responseLower, 'invalid') !== false && strpos($responseLower, 'ubrn') !== false) {
                    return ['error' => 'Invalid UBRN or DOB'];
                }
                return ['html' => $response, 'answer' => $testAns];
            }
        }
    }
    return null;
}

// ============================================
// ৮. হেল্পার ফাংশন
// ============================================
function generateRequestId() {
    return 'req-' . substr(uniqid(), 0, 8) . '-' . substr(uniqid(), 0, 12);
}

function enToBnNumber($number) {
    $en = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
    $bn = ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'];
    return str_replace($en, $bn, $number);
}

function todayDateBangla() {
    return enToBnNumber(date('d/m/Y'));
}

function isAdult($dob) {
    try {
        $birthDate = new DateTime($dob);
        $today = new DateTime();
        $age = $today->diff($birthDate)->y;
        return $age >= 18 ? 'true' : 'false';
    } catch (Exception $e) {
        return 'false';
    }
}

function dobInWords($dateStr) {
    try {
        $dt = new DateTime($dateStr);
        $months = [1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
                   5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
                   9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'];
        $day = $dt->format('j');
        $month = $months[(int)$dt->format('n')];
        $year = $dt->format('Y');
        return $day . ' of ' . $month . ' ' . $year;
    } catch (Exception $e) {
        return $dateStr;
    }
}

function extractDataFromHtml($html) {
    $data = [
        'personNameBn' => '', 'personNameEn' => '', 'ubrn' => '',
        'personDob' => '', 'gender' => '', 'fatherNameBn' => '',
        'fatherNameEn' => '', 'motherNameBn' => '', 'motherNameEn' => '',
        'birthPlaceBn' => '', 'birthPlaceEn' => '',
        'officeAddressBn' => '', 'officeAddressEn' => '',
        'officeId' => 0, 'phone' => '', 'foreign' => false
    ];
    
    if (preg_match('/নিবন্ধিত ব্যক্তির নাম\s*<\/td>\s*<td[^>]*>([^<]+)/', $html, $match)) {
        $data['personNameBn'] = trim($match[1]);
    }
    if (preg_match('/Registered Person Name\s*<\/td>\s*<td[^>]*>([^<]+)/', $html, $match)) {
        $data['personNameEn'] = trim($match[1]);
    }
    if (preg_match('/Birth Registration Number.*?<b>(.*?)<\/b>/s', $html, $match)) {
        $data['ubrn'] = trim($match[1]);
    }
    if (preg_match('/Date of Birth.*?<tr>.*?<td[^>]*>(.*?)<\/td>/s', $html, $match)) {
        $data['personDob'] = trim($match[1]);
    }
    if (preg_match('/Sex.*?<tr>.*?<td[^>]*>.*?<\/td>.*?<td[^>]*>.*?<\/td>.*?<td[^>]*>(.*?)<\/td>/s', $html, $match)) {
        $data['gender'] = trim($match[1]);
    }
    if (preg_match('/পিতার নাম\s*<\/td>\s*<td[^>]*>([^<]+)/', $html, $match)) {
        $data['fatherNameBn'] = trim($match[1]);
    }
    if (preg_match("/Father's Name\s*<\/td>\s*<td[^>]*>([^<]+)/", $html, $match)) {
        $data['fatherNameEn'] = trim($match[1]);
    }
    if (preg_match('/মাতার নাম\s*<\/td>\s*<td[^>]*>([^<]+)/', $html, $match)) {
        $data['motherNameBn'] = trim($match[1]);
    }
    if (preg_match("/Mother's Name\s*<\/td>\s*<td[^>]*>([^<]+)/", $html, $match)) {
        $data['motherNameEn'] = trim($match[1]);
    }
    if (preg_match('/জন্মস্থান\s*<\/td>\s*<td[^>]*>([^<]+)/', $html, $match)) {
        $data['birthPlaceBn'] = trim($match[1]);
    }
    if (preg_match('/Place of Birth\s*<\/td>\s*<td[^>]*>([^<]+)/', $html, $match)) {
        $data['birthPlaceEn'] = trim($match[1]);
    }
    if (preg_match('/Registration Office.*?<tr>.*?<td[^>]*>.*?<\/td>.*?<td[^>]*>(.*?)<\/td>/s', $html, $match)) {
        $data['officeAddressEn'] = trim($match[1]);
        $data['officeAddressBn'] = trim($match[1]);
    }
    
    return $data;
}

// ============================================
// ৯. JSON ফরম্যাট তৈরি
// ============================================
function formatResponse($data, $dobOriginal) {
    $gender = detectGender($data['personNameBn'], $data['personNameEn']);
    
    if (!empty($data['gender'])) {
        $htmlGender = strtoupper(trim($data['gender']));
        if (strpos($htmlGender, 'MALE') !== false || strpos($htmlGender, 'পুরুষ') !== false) {
            $gender = ['genderBn' => 'পুরুষ', 'genderEn' => 'Male'];
        } elseif (strpos($htmlGender, 'FEMALE') !== false || strpos($htmlGender, 'নারী') !== false) {
            $gender = ['genderBn' => 'নারী', 'genderEn' => 'Female'];
        }
    }
    
    $religion = determineReligion(
        $data['personNameBn'],
        $data['personNameEn'],
        $data['fatherNameBn'],
        $data['motherNameBn']
    );
    
    $today = date('d/m/Y');
    $todayBn = todayDateBangla();
    
    $dobFormatted = $data['personDob'];
    if (!empty($dobFormatted)) {
        $parts = explode(' ', $dobFormatted);
        if (count($parts) == 3) {
            $day = str_pad($parts[0], 2, '0', STR_PAD_LEFT);
            $monthMap = ['January'=>'01', 'February'=>'02', 'March'=>'03', 'April'=>'04',
                        'May'=>'05', 'June'=>'06', 'July'=>'07', 'August'=>'08',
                        'September'=>'09', 'October'=>'10', 'November'=>'11', 'December'=>'12'];
            $month = $monthMap[$parts[1]] ?? '01';
            $year = $parts[2];
            $dobFormatted = "$day/$month/$year";
        }
    }
    
    return [
        "code" => 200,
        "status" => true,
        "message" => "তথ্য সফল ভাবে পাওয়া গেছে!!",
        "data" => [
            "requestId" => generateRequestId(),
            "nameBangla" => $data['personNameBn'],
            "nameEnglish" => $data['personNameEn'],
            "genderBangla" => $gender['genderBn'],
            "genderEnglish" => $gender['genderEn'],
            "brn" => $data['ubrn'],
            "dateOfBirth" => $data['personDob'],
            "dobInWord" => dobInWords($dobOriginal),
            "convertedDob" => $dobFormatted,
            "phoneNumber" => $data['phone'],
            "religionBangla" => $religion['bn'],
            "religionEnglish" => $religion['en'],
            "todayDateEnglish" => $today,
            "todayDateBangla" => $todayBn,
            "fatherNameBangla" => $data['fatherNameBn'],
            "fatherNameEnglish" => $data['fatherNameEn'],
            "fathersNationalityBangla" => $data['foreign'] ? "" : "বাংলাদেশি",
            "fathersNationalityEnglish" => $data['foreign'] ? "" : "Bangladeshi",
            "motherNameBangla" => $data['motherNameBn'],
            "motherNameEnglish" => $data['motherNameEn'],
            "mothersNationalityBangla" => $data['foreign'] ? "" : "বাংলাদেশি",
            "mothersNationalityEnglish" => $data['foreign'] ? "" : "Bangladeshi",
            "birthPlaceBangla" => $data['birthPlaceBn'],
            "birthPlaceEnglish" => $data['birthPlaceEn'],
            "officeId" => $data['officeId'],
            "addressBangla" => $data['officeAddressBn'],
            "addressEnglish" => $data['officeAddressEn'],
            "registerOfficeBangla" => $data['officeAddressBn'],
            "registerOfficeEnglish" => $data['officeAddressEn'],
            "registerOfficeLocationBangla" => $data['officeAddressBn'],
            "registerOfficeLocationEnglish" => $data['officeAddressEn'],
            "isAdult" => isAdult($dobOriginal)
        ],
        "OWNER" => "@XrootCodeX",
        "DEVLOPER" => "MehediSanix"
    ];
}

// ============================================
// ১০. মেইন ফাংশন (Ultra Fast)
// ============================================
function autoVerifyBirth($ubrn, $birthDate) {
    for ($attempt = 1; $attempt <= 2; $attempt++) {
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, BASE_URL);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
            curl_setopt($ch, CURLOPT_HEADER, true);
            $response = curl_exec($ch);
            $info = curl_getinfo($ch);
            curl_close($ch);
            
            if (!$response) continue;
            
            preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $response, $matches);
            $cookies = [];
            foreach ($matches[1] as $item) {
                $parts = explode('=', $item, 2);
                if (count($parts) == 2) {
                    $cookies[$parts[0]] = $parts[1];
                }
            }
            $sessionCookies = '';
            foreach ($cookies as $key => $value) {
                $sessionCookies .= $key . '=' . $value . '; ';
            }
            
            $html = substr($response, $info['header_size']);
            
            if (!preg_match('/name="__RequestVerificationToken"\s+value="([^"]+)"/', $html, $match) &&
                !preg_match('/__RequestVerificationToken[^>]+value="([^"]+)"/', $html, $match)) {
                continue;
            }
            $token = $match[1];
            
            if (!preg_match('/id="CaptchaDeText"\s+value="([^"]+)"/', $html, $match) &&
                !preg_match('/CaptchaDeText[^>]+value="([^"]+)"/', $html, $match)) {
                continue;
            }
            $captchaToken = $match[1];
            
            $imgUrl = BASE_URL . '/DefaultCaptcha/Generate?t=' . $captchaToken;
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $imgUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
            if ($sessionCookies) {
                curl_setopt($ch, CURLOPT_COOKIE, $sessionCookies);
            }
            $imageData = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode != 200 || !$imageData) continue;
            
            $answer = solveCaptchaWithOCR($imageData);
            
            if ($answer === null) {
                $result = bruteForceCaptcha($sessionCookies, $token, $captchaToken, $ubrn, $birthDate);
                if ($result && isset($result['html'])) {
                    $extractedData = extractDataFromHtml($result['html']);
                    return formatResponse($extractedData, $birthDate);
                }
                if ($result && isset($result['error'])) {
                    return ["code" => 400, "status" => false, "message" => $result['error']];
                }
                continue;
            }
            
            $postData = [
                '__RequestVerificationToken' => $token,
                'UBRN' => $ubrn,
                'BirthDate' => $birthDate,
                'CaptchaDeText' => $captchaToken,
                'CaptchaInputText' => (string)$answer
            ];
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, BASE_URL . '/UBRNVerification/Search');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/x-www-form-urlencoded',
                'Origin: ' . BASE_URL,
                'Referer: ' . BASE_URL . '/'
            ]);
            if ($sessionCookies) {
                curl_setopt($ch, CURLOPT_COOKIE, $sessionCookies);
            }
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode != 200 || !$response) continue;
            
            $responseLower = strtolower($response);
            
            if (strpos($responseLower, 'captcha') !== false || strpos($responseLower, 'not valid') !== false) {
                $result = bruteForceCaptcha($sessionCookies, $token, $captchaToken, $ubrn, $birthDate);
                if ($result && isset($result['html'])) {
                    $extractedData = extractDataFromHtml($result['html']);
                    return formatResponse($extractedData, $birthDate);
                }
                continue;
            }
            
            if (strpos($responseLower, 'invalid') !== false && strpos($responseLower, 'ubrn') !== false) {
                return ["code" => 400, "status" => false, "message" => "Invalid UBRN or DOB"];
            }
            
            $extractedData = extractDataFromHtml($response);
            return formatResponse($extractedData, $birthDate);
            
        } catch (Exception $e) {
            continue;
        }
    }
    
    return ["code" => 500, "status" => false, "message" => "Could not verify after attempts"];
}

// ============================================
// ১১. API এন্ডপয়েন্ট
// ============================================

$ubrn = $_GET['ubrn'] ?? $_POST['ubrn'] ?? null;
$dob = $_GET['dob'] ?? $_POST['dob'] ?? null;

if (!$ubrn || !$dob) {
    echo json_encode([
        "code" => 400,
        "status" => false,
        "message" => "Please provide UBRN and DOB",
        "usage" => [
            "GET" => "/birthday_verify.php?ubrn=20073213010044211&dob=2007-09-07",
            "POST" => "curl -X POST /birthday_verify.php -d \"ubrn=20073213010044211&dob=2007-09-07\"",
            "parameters" => ["ubrn" => "17 digit number", "dob" => "YYYY-MM-DD"]
        ]
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

if (!preg_match('/^\d{17}$/', $ubrn)) {
    echo json_encode(["code" => 400, "status" => false, "message" => "UBRN must be 17 digits"]);
    exit;
}

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dob)) {
    echo json_encode(["code" => 400, "status" => false, "message" => "DOB must be YYYY-MM-DD"]);
    exit;
}

$result = autoVerifyBirth($ubrn, $dob);
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>
