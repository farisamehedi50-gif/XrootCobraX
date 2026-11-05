<?php
declare(strict_types=1);
error_reporting(0);
ini_set('display_errors', '0');

$settingsFile = __DIR__ . '/api2_config.json';

// ডিফল্ট কনফিগারেশন ফাইল তৈরি (প্রথমবার রান করার জন্য)
if (!file_exists($settingsFile)) {
    $defaultSettings = [
        "admin_password" => "483236", // ডিফল্ট পাসওয়ার্ড
        "service_status" => "active", // active অথবা maintenance
        "alert_title" => "সতর্কবার্তা!",
        "alert_message" => "এই এপিআই সার্ভিসটি বর্তমানে আপডেট বা রক্ষণাবেক্ষণের কাজের জন্য সাময়িকভাবে বন্ধ রয়েছে।",
        "support_link" => "https://t.me/CobraX_Empire"
    ];
    file_put_contents($settingsFile, json_encode($defaultSettings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

$settings = json_decode(file_get_contents($settingsFile), true);

session_start();
$is_logged_in = isset($_SESSION['admin2_logged']) && $_SESSION['admin2_logged'] === true;

$error_msg = "";
$success_msg = "";

// লগইন হ্যান্ডেল
if (!$is_logged_in && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $input_pass = $_POST['password'] ?? '';
    if ($input_pass === $settings['admin_password']) {
        $_SESSION['admin2_logged'] = true;
        header("Location: admin2.php");
        exit;
    } else {
        $error_msg = "ভুল পাসওয়ার্ড দেওয়া হয়েছে!";
    }
}

// লগআউট হ্যান্ডেল
if (isset($_GET['logout'])) {
    unset($_SESSION['admin2_logged']);
    header("Location: admin2.php");
    exit;
}

// সেটিংস আপডেট হ্যান্ডেল
if ($is_logged_in && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_config'])) {
    $settings['service_status'] = $_POST['service_status'] === 'maintenance' ? 'maintenance' : 'active';
    $settings['alert_title'] = trim($_POST['alert_title']);
    $settings['alert_message'] = trim($_POST['alert_message']);
    $settings['support_link'] = trim($_POST['support_link']);

    // যদি নতুন পাসওয়ার্ড দেওয়া হয় তবে তা পরিবর্তন হবে
    if (!empty($_POST['new_password'])) {
        $settings['admin_password'] = trim($_POST['new_password']);
        $success_msg = "সেটিংস এবং অ্যাডমিন পাসওয়ার্ড সফলভাবে আপডেট করা হয়েছে!";
    } else {
        $success_msg = "সেটিংস সফলভাবে আপডেট করা হয়েছে!";
    }

    file_put_contents($settingsFile, json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    $settings = json_decode(file_get_contents($settingsFile), true);
}
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advanced API Control Panel</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, sans-serif; }
        body { background: #0f172a; color: #f1f5f9; padding: 20px; }
        .container { max-width: 650px; margin: 40px auto; background: #1e293b; border: 1px solid #334155; border-radius: 16px; padding: 30px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.5); }
        h1 { color: #f8fafc; font-size: 20px; margin-bottom: 20px; border-bottom: 1px solid #334155; padding-bottom: 12px; display: flex; justify-content: space-between; align-items: center; }
        .login-box { max-width: 400px; margin: 80px auto; background: #1e293b; padding: 30px; border-radius: 16px; border: 1px solid #334155; text-align: center; }
        input[type="text"], input[type="password"], select, textarea { width: 100%; padding: 12px 14px; background: #0f172a; border: 1px solid #475569; border-radius: 8px; color: #fff; margin-bottom: 15px; font-size: 14px; }
        textarea { resize: vertical; height: 90px; }
        button { background: #3b82f6; color: #fff; border: none; padding: 12px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: 0.2s; width: 100%; font-size: 15px; }
        button:hover { background: #2563eb; }
        .alert-success { background: rgba(16, 185, 129, 0.15); color: #34d399; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; border: 1px solid rgba(16, 185, 129, 0.3); }
        .alert-error { background: rgba(239, 68, 68, 0.15); color: #f87171; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; border: 1px solid rgba(239, 68, 68, 0.3); }
        .logout { color: #f43f5e; text-decoration: none; font-size: 14px; }
        label { color: #94a3b8; font-size: 13px; font-weight: 600; display: block; margin-bottom: 6px; }
    </style>
</head>
<body>
    <div class="container">
        <?php if (!$is_logged_in): ?>
            <div class="login-box">
                <h2 style="margin-bottom: 20px; color:#fff;">API Admin Login</h2>
                <?php if (!empty($error_msg)): ?>
                    <div class="alert-error"><?php echo $error_msg; ?></div>
                <?php endif; ?>
                <form method="POST">
                    <input type="hidden" name="login" value="1">
                    <input type="password" name="password" placeholder="অ্যাডমিন পাসওয়ার্ড দিন" required>
                    <button type="submit">লগইন করুন</button>
                </form>
            </div>
        <?php else: ?>
            <h1>
                <span>⚙️ Advanced API Control</span>
                <a href="?logout=true" class="logout">লগআউট</a>
            </h1>

            <?php if (!empty($success_msg)): ?>
                <div class="alert-success"><?php echo $success_msg; ?></div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="update_config" value="1">

                <label>সার্ভিস মোড (Service Mode):</label>
                <select name="service_status">
                    <option value="active" <?php echo ($settings['service_status'] === 'active') ? 'selected' : ''; ?>>🟢 সচল (Active)</option>
                    <option value="maintenance" <?php echo ($settings['service_status'] === 'maintenance') ? 'selected' : ''; ?>>🔴 মেইনটেন্স বা বন্ধ (Maintenance)</option>
                </select>

                <label>নোটিশের শিরোনাম (Alert Title):</label>
                <input type="text" name="alert_title" value="<?php echo htmlspecialchars($settings['alert_title']); ?>" required>

                <label>নোটিশের বার্তা (Alert Message):</label>
                <textarea name="alert_message" required><?php echo htmlspecialchars($settings['alert_message']); ?></textarea>

                <label>সাপোর্ট বা চ্যানেল লিংক (Support Link):</label>
                <input type="text" name="support_link" value="<?php echo htmlspecialchars($settings['support_link']); ?>" required>

                <label>নতুন পাসওয়ার্ড (পরিবর্তন করতে চাইলে লিখুন, খালি রাখলে পুরোটাই থাকবে):</label>
                <input type="password" name="new_password" placeholder="নতুন পাসওয়ার্ড দিন (ঐচ্ছিক)">

                <button type="submit" style="margin-top: 10px;">পরিবর্তনগুলো সেভ করুন</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
