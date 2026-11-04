<?php
declare(strict_types=1);
error_reporting(0);
ini_set('display_errors', '0');

// আপনার নতুন অ্যাডমিন পাসওয়ার্ড
$admin_password = "483236"; 

$configFile = __DIR__ . '/config.json';
$usageDir = __DIR__ . '/api_usage';

// ডিফল্ট কনফিগারেশন ফাইল তৈরি
if (!file_exists($configFile)) {
    $defaultConfig = [
        "api_active" => true,
        "telegram_channel" => "https://t.me/CobraX_Empire",
        "keys" => [
            "csk_live_9f8d7c6b5a4e3f2d1c0b9a8f7e6d5c4b3a2" => ["name" => "Admin Root", "limit" => 999999999],
            "tkn_auth_p_qwerty1234567890abcdefghijk987654321" => ["name" => "User Alpha 1", "limit" => 1],
            "usr_sec_token_x9z8y7w6v5u4t3s2r1q0p9o8n7m6l5k4j3" => ["name" => "User Beta 2", "limit" => 3]
        ]
    ];
    file_put_contents($configFile, json_encode($defaultConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

$config = json_decode(file_get_contents($configFile), true);

$is_logged_in = false;
session_start();
if (isset($_POST['password']) && $_POST['password'] === $admin_password) {
    $_SESSION['admin_logged'] = true;
}
if (isset($_GET['logout'])) {
    unset($_SESSION['admin_logged']);
    header("Location: admin.php");
    exit;
}
if (isset($_SESSION['admin_logged']) && $_SESSION['admin_logged'] === true) {
    $is_logged_in = true;
}

$success_msg = "";
if ($is_logged_in && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_settings'])) {
        $config['api_active'] = isset($_POST['api_active']) ? true : false;
        $config['telegram_channel'] = trim($_POST['telegram_channel']);
        
        // নতুন কি যোগ করা
        if (!empty($_POST['new_key']) && !empty($_POST['new_name'])) {
            $newKey = trim($_POST['new_key']);
            $config['keys'][$newKey] = [
                "name" => trim($_POST['new_name']),
                "limit" => (int)$_POST['new_limit']
            ];
        }
        
        // বিদ্যমান কি-এর লিমিট আপডেট করা
        if (isset($_POST['limits']) && is_array($_POST['limits'])) {
            foreach ($_POST['limits'] as $k => $lim) {
                if (isset($config['keys'][$k])) {
                    $config['keys'][$k]['limit'] = (int)$lim;
                }
            }
        }

        // নির্দিষ্ট কি-এর ইউসেজ কাউন্টার রিসেট করা
        if (isset($_POST['reset_usage']) && !empty($_POST['reset_usage'])) {
            $resetKey = $_POST['reset_usage'];
            $usageF = $usageDir . '/' . hash('sha256', $resetKey) . '.json';
            if (file_exists($usageF)) {
                @unlink($usageF);
            }
        }

        // কি ডিলিট করা
        if (isset($_POST['delete_key']) && !empty($_POST['delete_key'])) {
            $delKey = $_POST['delete_key'];
            unset($config['keys'][$delKey]);
            $usageF = $usageDir . '/' . hash('sha256', $delKey) . '.json';
            if (file_exists($usageF)) @unlink($usageF);
        }

        file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $success_msg = "অ্যাডমিন সেটিংস সফলভাবে আপডেট করা হয়েছে!";
        $config = json_decode(file_get_contents($configFile), true);
    }
}
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CobraX - Advanced Admin Control Panel</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, sans-serif; }
        body { background: #090d16; color: #f1f5f9; padding: 20px; }
        .container { max-width: 900px; margin: 0 auto; background: #111827; border: 1px solid #1f2937; border-radius: 16px; padding: 30px; box-shadow: 0 15px 30px rgba(0,0,0,0.5); }
        h1 { color: #f8fafc; font-size: 22px; margin-bottom: 20px; border-bottom: 1px solid #1f2937; padding-bottom: 10px; display: flex; justify-content: space-between; align-items: center; }
        .login-box { max-width: 400px; margin: 100px auto; background: #111827; padding: 30px; border-radius: 16px; border: 1px solid #1f2937; text-align: center; }
        input, select { width: 100%; padding: 10px 14px; background: #1f2937; border: 1px solid #374151; border-radius: 8px; color: #fff; margin-bottom: 15px; font-size: 14px; }
        button { background: #2563eb; color: #fff; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: 0.2s; }
        button:hover { background: #1d4ed8; }
        .btn-danger { background: #dc2626; padding: 6px 12px; font-size: 12px; border-radius: 6px; }
        .btn-danger:hover { background: #b91c1c; }
        .btn-warning { background: #d97706; padding: 6px 12px; font-size: 12px; border-radius: 6px; color: #fff; }
        .btn-warning:hover { background: #b45309; }
        .alert { background: rgba(16, 185, 129, 0.15); color: #34d399; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; border: 1px solid rgba(16, 185, 129, 0.3); }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 12px 10px; text-align: left; border-bottom: 1px solid #1f2937; font-size: 14px; }
        th { color: #94a3b8; font-weight: 600; }
        .switch-container { display: flex; align-items: center; gap: 10px; margin-bottom: 20px; background: #1f2937; padding: 14px; border-radius: 8px; border: 1px solid #374151; }
        .logout { color: #f43f5e; text-decoration: none; font-size: 14px; }
        .tag-used { background: rgba(239, 68, 68, 0.15); color: #f87171; padding: 3px 8px; border-radius: 4px; font-size: 12px; font-weight: 600; }
        .tag-safe { background: rgba(16, 185, 129, 0.15); color: #34d399; padding: 3px 8px; border-radius: 4px; font-size: 12px; font-weight: 600; }
    </style>
</head>
<body>
    <div class="container">
        <?php if (!$is_logged_in): ?>
            <div class="login-box">
                <h2 style="margin-bottom: 20px; color:#fff;">Admin Security Login</h2>
                <?php if (isset($_POST['password'])): ?>
                    <p style="color: #f43f5e; margin-bottom: 10px; font-size:13px;">ভুল পাসওয়ার্ড!</p>
                <?php endif; ?>
                <form method="POST">
                    <input type="password" name="password" placeholder="অ্যাডমিন পাসওয়ার্ড দিন (483236)" required>
                    <button type="submit" style="width: 100%;">লগইন করুন</button>
                </form>
            </div>
        <?php else: ?>
            <h1>
                <span>🛡️ CobraX Advanced Dashboard</span>
                <a href="?logout=true" class="logout">লগআউট</a>
            </h1>

            <?php if (!empty($success_msg)): ?>
                <div class="alert"><?php echo $success_msg; ?></div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="update_settings" value="1">
                
                <div class="switch-container">
                    <input type="checkbox" name="api_active" id="api_active" <?php echo $config['api_active'] ? 'checked' : ''; ?> style="width: 22px; height: 22px; margin:0; cursor: pointer;">
                    <label for="api_active" style="font-weight: 600; cursor: pointer;">এপিআই সার্ভার চালু রাখুন (বন্ধ করলে সবার সামনে স্টাইলিশ এইচটিএমএল নোটিশ যাবে)</label>
                </div>

                <label style="color: #94a3b8; font-size: 13px; font-weight: 600;">টেলিগ্রাম চ্যানেল বা নোটিশ লিংক:</label>
                <input type="text" name="telegram_channel" value="<?php echo htmlspecialchars($config['telegram_channel']); ?>" required>

                <h3 style="margin: 25px 0 10px 0; font-size: 16px; color: #38bdf8;">📊 এপিআই কি, লিমিট ও রিয়েল-টাইম ট্র্যাকিং</h3>
                <table>
                    <tr>
                        <th>ইউজার / নাম</th>
                        <th>এপিআই কি (API Key)</th>
                        <th>ব্যাবহার (Used / Limit)</th>
                        <th>স্ট্যাটাস</th>
                        <th>লিমিট এডিট</th>
                        <th>অ্যাকশন</th>
                    </tr>
                    <?php foreach ($config['keys'] as $keyStr => $info): 
                        // ইউজারে রিকোয়েস্ট কাউন্ট চেক করা
                        $usageF = $usageDir . '/' . hash('sha256', $keyStr) . '.json';
                        $usedCount = 0;
                        if (file_exists($usageF)) {
                            $uData = json_decode(file_get_contents($usageF), true);
                            if ($uData && isset($uData['count'])) {
                                $usedCount = (int)$uData['count'];
                            }
                        }
                        $limitVal = (int)$info['limit'];
                        $isExhausted = ($usedCount >= $limitVal);
                    ?>
                    <tr>
                        <td style="font-weight: 600;"><?php echo htmlspecialchars($info['name']); ?></td>
                        <td style="font-family: monospace; font-size: 12px; color: #94a3b8;"><?php echo htmlspecialchars($keyStr); ?></td>
                        <td style="font-weight: 600;"><?php echo $usedCount; ?> / <?php echo ($limitVal > 900000) ? 'Unlimited' : $limitVal; ?></td>
                        <td>
                            <?php if ($limitVal > 900000): ?>
                                <span class="tag-safe">Unlimited</span>
                            <?php elseif ($isExhausted): ?>
                                <span class="tag-used">Limit Over (Locked)</span>
                            <?php else: ?>
                                <span class="tag-safe">Active</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <input type="number" name="limits[<?php echo htmlspecialchars($keyStr); ?>]" value="<?php echo $limitVal; ?>" style="margin:0; width: 90px; text-align: center;">
                        </td>
                        <td>
                            <div style="display: flex; gap: 5px;">
                                <button type="submit" name="reset_usage" value="<?php echo htmlspecialchars($keyStr); ?>" class="btn-warning" title="কাউন্টার জিরো করুন">রিসেট</button>
                                <button type="submit" name="delete_key" value="<?php echo htmlspecialchars($keyStr); ?>" class="btn-danger" onclick="return confirm('নিশ্চিত এই কি-টি ডিলিট করতে চান?')">ডিলিট</button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>

                <h3 style="margin: 30px 0 10px 0; font-size: 16px; color: #38bdf8;">➕ নতুন এপিআই কি যুক্ত করুন</h3>
                <div style="display: grid; grid-template-columns: 1fr 1fr 100px; gap: 10px; background: #1f2937; padding: 15px; border-radius: 8px;">
                    <input type="text" name="new_name" placeholder=" ইউজারের নাম (যেমন: Client 01)" style="margin:0;">
                    <input type="text" name="new_key" placeholder=" কাস্টম সিক্রেট এপিআই কি" style="margin:0;">
                    <input type="number" name="new_limit" placeholder="লিমিট" value="5" style="margin:0;">
                </div>

                <button type="submit" style="margin-top: 20px; width: 100%; padding: 14px; font-size: 16px;">পরিবর্তনগুলো সেভ করুন</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
