<?php
declare(strict_types=1);
error_reporting(0);
ini_set('display_errors', '0');

// সিক্রেট পাসওয়ার্ড (যা দিয়ে আপনি অ্যাডমিন প্যানেলে লগইন করবেন)
$admin_password = "CobraX_Secure_Admin_2026"; 

$configFile = __DIR__ . '/config.json';

// ডিফল্ট কনফিগারেশন ফাইল তৈরি (যদি না থাকে)
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

// লগইন হ্যান্ডলার
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

// ফর্ম সাবমিট হ্যান্ডলার (স্ট্যাটাস বা নতুন কি আপডেট করতে)
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

        // কি ডিলিট করা
        if (isset($_POST['delete_key']) && !empty($_POST['delete_key'])) {
            $delKey = $_POST['delete_key'];
            unset($config['keys'][$delKey]);
            $usageF = __DIR__ . '/api_usage/' . hash('sha256', $delKey) . '.json';
            if (file_exists($usageF)) @unlink($usageF);
        }

        file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $success_msg = "সেটিংস সফলভাবে আপডেট করা হয়েছে!";
        $config = json_decode(file_get_contents($configFile), true);
    }
}
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CobraX - Admin Control Panel</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, sans-serif; }
        body { background: #090d16; color: #f1f5f9; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; background: #111827; border: 1px solid #1f2937; border-radius: 16px; padding: 30px; box-shadow: 0 15px 30px rgba(0,0,0,0.5); }
        h1 { color: #f8fafc; font-size: 22px; margin-bottom: 20px; border-bottom: 1px solid #1f2937; padding-bottom: 10px; display: flex; justify-content: space-between; align-items: center; }
        .login-box { max-width: 400px; margin: 100px auto; background: #111827; padding: 30px; border-radius: 16px; border: 1px solid #1f2937; text-align: center; }
        input, select { width: 100%; padding: 10px 14px; background: #1f2937; border: 1px solid #374151; border-radius: 8px; color: #fff; margin-bottom: 15px; font-size: 14px; }
        button { background: #2563eb; color: #fff; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: 0.2s; }
        button:hover { background: #1d4ed8; }
        .btn-danger { background: #dc2626; padding: 5px 10px; font-size: 12px; }
        .btn-danger:hover { background: #b91c1c; }
        .alert { background: rgba(16, 185, 129, 0.15); color: #34d399; padding: 10px; border-radius: 8px; margin-bottom: 15px; font-size: 14px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #1f2937; font-size: 14px; }
        th { color: #94a3b8; }
        .switch-container { display: flex; align-items: center; gap: 10px; margin-bottom: 20px; background: #1f2937; padding: 12px; border-radius: 8px; }
        .logout { color: #f43f5e; text-decoration: none; font-size: 14px; }
    </style>
</head>
<body>
    <div class="container">
        <?php if (!$is_logged_in): ?>
            <div class="login-box">
                <h2 style="margin-bottom: 20px; color:#fff;">Admin Login</h2>
                <?php if (isset($_POST['password'])): ?>
                    <p style="color: #f43f5e; margin-bottom: 10px; font-size:13px;">ভুল পাসওয়ার্ড!</p>
                <?php endif; ?>
                <form method="POST">
                    <input type="password" name="password" placeholder="অ্যাডমিন পাসওয়ার্ড দিন" required>
                    <button type="submit" style="width: 100%;">লগইন করুন</button>
                </form>
            </div>
        <?php else: ?>
            <h1>
                <span>🛡️ CobraX Admin Dashboard</span>
                <a href="?logout=true" class="logout">লগআউট</a>
            </h1>

            <?php if (!empty($success_msg)): ?>
                <div class="alert"><?php echo $success_msg; ?></div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="update_settings" value="1">
                
                <div class="switch-container">
                    <input type="checkbox" name="api_active" id="api_active" <?php echo $config['api_active'] ? 'checked' : ''; ?> style="width: 20px; height: 20px; margin:0;">
                    <label for="api_active" style="font-weight: 600; cursor: pointer;">এপিআই সার্ভার চালু রাখুন (অফ করলে সবার সামনে এইচটিএমএল নোটিশ যাবে)</label>
                </div>

                <label style="color: #94a3b8; font-size: 13px;">টেলিগ্রাম চ্যানেল লিংক:</label>
                <input type="text" name="telegram_channel" value="<?php echo htmlspecialchars($config['telegram_channel']); ?>" required>

                <h3 style="margin: 20px 0 10px 0; font-size: 16px; color: #38bdf8;">🔑 এপিআই কি ও লিমিট ম্যানেজমেন্ট</h3>
                <table>
                    <tr>
                        <th>নাম / ইউজার</th>
                        <th>এপিআই কি (API Key)</th>
                        <th>লিমিট</th>
                        <th>অ্যাকশন</th>
                    </tr>
                    <?php foreach ($config['keys'] as $keyStr => $info): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($info['name']); ?></td>
                        <td style="font-family: monospace; font-size: 12px; color: #cbd5e1;"><?php echo htmlspecialchars($keyStr); ?></td>
                        <td>
                            <input type="number" name="limits[<?php echo htmlspecialchars($keyStr); ?>]" value="<?php echo $info['limit']; ?>" style="margin:0; width: 100px;">
                        </td>
                        <td>
                            <button type="submit" name="delete_key" value="<?php echo htmlspecialchars($keyStr); ?>" class="btn-danger" onclick="return confirm('নিশ্চিত ডিলিট করতে চান?')">ডিলিট</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>

                <h3 style="margin: 25px 0 10px 0; font-size: 16px; color: #38bdf8;">➕ নতুন এপিআই কি যুক্ত করুন</h3>
                <div style="display: grid; grid-template-columns: 1fr 1fr 100px; gap: 10px;">
                    <input type="text" name="new_name" placeholder=" ইউজারের নাম (যেমন: Friend)">
                    <input type="text" name="new_key" placeholder=" কাস্টম এপিআই কি (Key)">
                    <input type="number" name="new_limit" placeholder="লিমিট" value="5">
                </div>

                <button type="submit" style="margin-top: 15px; width: 100%; padding: 12px;">পরিবর্তনগুলো সেভ করুন</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
