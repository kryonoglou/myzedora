<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

$languages_dir = __DIR__ . '/languages/';
$available_languages = [];
foreach (glob($languages_dir . '*.php') as $file) {
    $lang_code = basename($file, '.php');
    $available_languages[$lang_code] = strtoupper($lang_code);
}

$step = $_GET['step'] ?? 1;
$lang_code = $_SESSION['install_lang'] ?? 'en';

if (isset($_POST['language'])) {
    $lang_code = basename($_POST['language']);
    if (isset($available_languages[$lang_code])) {
        $_SESSION['install_lang'] = $lang_code;
        $step = 2;
    }
}

$lang_file = $languages_dir . $lang_code . '.php';
if (file_exists($lang_file)) {
    $lang = require $lang_file;
} else {
    $lang = require $languages_dir . 'en.php';
}

$errors = [];
$success = false;

if ($step == 2 && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['install'])) {
    
    $app_url = rtrim(filter_var($_POST['app_url'], FILTER_SANITIZE_URL), '/');
    $db_host = trim($_POST['db_host']);
    $db_name = trim($_POST['db_name']);
    $db_user = trim($_POST['db_user']);
    $db_pass = trim($_POST['db_pass']);
    $admin_user = trim($_POST['admin_user']);
    $admin_email = filter_var(trim($_POST['admin_email']), FILTER_SANITIZE_EMAIL);
    $admin_pass = $_POST['admin_pass'];
    $site_title = trim($_POST['site_title']);

    if (empty($app_url) || empty($db_host) || empty($db_name) || empty($db_user) || empty($admin_user) || empty($admin_email) || empty($admin_pass) || empty($site_title)) {
        $errors[] = 'All fields are required.';
    }
    if (!filter_var($app_url, FILTER_VALIDATE_URL)) {
        $errors[] = 'Invalid Application URL.';
    }
    if (!filter_var($admin_email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid Admin Email.';
    }
    if (strlen($admin_pass) < 8) {
        $errors[] = 'Admin password must be at least 8 characters long.';
    }

    if (empty($errors)) {
        try {
            $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            $errors[] = 'Database connection failed: ' . $e->getMessage();
        }
    }

    if (empty($errors)) {
        try {
            $env_content = [
                'APP_URL' => "\"$app_url\"",
                'DB_HOST' => "\"$db_host\"",
                'DB_PORT' => '"3306"',
                'DB_DATABASE' => "\"$db_name\"",
                'DB_USERNAME' => "\"$db_user\"",
                'DB_PASSWORD' => "\"$db_pass\"",
                'DB_CHARSET' => '"utf8mb4"',
            ];
            file_put_contents('../.env', implode("\n", array_map(function ($k, $v) { return "$k=$v"; }, array_keys($env_content), $env_content)));

            $sql = file_get_contents('database.sql');
            $pdo->exec($sql);

            $stmt = $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?)");
            $lang['site_settings']['site_title'] = $site_title;
            foreach ($lang['site_settings'] as $key => $value) {
                $stmt->execute([$key, $value]);
            }
            $pdo->prepare("UPDATE site_settings SET setting_value = ? WHERE setting_key = 'site_language'")->execute([$lang_code]);

            $password_hash = password_hash($admin_pass, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, full_name, is_admin, is_active) VALUES (?, ?, ?, ?, 1, 1)");
            $stmt->execute([$admin_user, $admin_email, $password_hash, 'Administrator']);
            $admin_id = $pdo->lastInsertId();

            $sample_post = $lang['sample_post'];
            $stmt = $pdo->prepare("INSERT INTO posts (user_id, title, slug, content, excerpt, is_published, published_at) VALUES (?, ?, ?, ?, ?, 1, NOW())");
            $stmt->execute([$admin_id, $sample_post['title'], $sample_post['slug'], $sample_post['content'], $sample_post['excerpt']]);

            foreach ($lang['sample_projects'] as $project) {
                 $stmt = $pdo->prepare("INSERT INTO projects (title, slug, description, technologies) VALUES (?, ?, ?, ?)");
                 $stmt->execute([$project['title'], $project['slug'], $project['description'], $project['technologies']]);
            }

            file_put_contents('install.lock', 'Installation completed on ' . date('Y-m-d H:i:s'));

            $success = true;

        } catch (Exception $e) {
            $errors[] = 'An error occurred during installation: ' . $e->getMessage();
        }
    }
}

$auto_app_url = "http" . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "s" : "") . "://" . $_SERVER['HTTP_HOST'] . str_replace('/install/index.php', '', $_SERVER['SCRIPT_NAME']);
$auto_app_url = rtrim($auto_app_url, '/');

if (file_exists('install.lock')) {
    $success = true;
    $step = 3;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang['installer_title']; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(270deg, #0f172a, #111827, #0f172a);
        }
        .form-label {
            @apply block text-gray-300 mb-2 font-semibold;
        }
        .btn-primary {
            @apply w-full bg-sky-500 text-white font-semibold py-3 rounded-lg hover:bg-sky-600 transition-colors duration-300 disabled:bg-sky-800 disabled:cursor-not-allowed;
        }
        .section-title {
            background: -webkit-linear-gradient(45deg, #38bdf8, #a78bfa);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
    </style>
</head>
<body class="antialiased text-gray-200">

    <div class="min-h-screen flex items-center justify-center p-4" data-aos="fade-up">

        <div class="w-full max-w-2xl">
            <header class="text-center mb-8">
                <h1 class="text-4xl font-bold tracking-wider section-title">myZedora OS</h1>
                <p class="text-gray-400 mt-1"><?php echo $lang['installer_title']; ?></p>
            </header>

            <div class="bg-gray-800/50 p-8 rounded-2xl shadow-2xl border border-gray-700/50">

                <?php if ($success): ?>
                <div class="text-center">
                     <svg class="mx-auto h-16 w-16 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <h2 class="text-3xl font-bold text-white mt-4"><?php echo $lang['success_title']; ?></h2>
                    <p class="text-gray-400 mt-4"><?php echo $lang['success_message']; ?></p>
                    <div class="mt-8">
                        <a href="../" class="btn-primary inline-block w-auto px-10"><?php echo $lang['visit_site_button']; ?></a>
                    </div>
                </div>

                <?php elseif ($step == 1): ?>
                <form action="index.php" method="post">
                    <h2 class="text-2xl font-bold text-center mb-2 text-white"><?php echo $lang['step1_title']; ?></h2>
                    <p class="text-center text-gray-400 mb-6"><?php echo $lang['welcome_message']; ?></p>
                    <div class="mb-6">
                        <label for="language" class="form-label">Language</label>
                        <select id="language" name="language" class="w-full bg-gray-700 border border-gray-600 rounded-lg py-2.5 px-4 text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-sky-500 transition">
                            <?php foreach ($available_languages as $code => $name): ?>
                                <option value="<?php echo $code; ?>" <?php if ($lang_code == $code) echo 'selected'; ?>><?php echo $name; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn-primary">Continue &rarr;</button>
                </form>

                <?php elseif ($step == 2): ?>
                <h2 class="text-2xl font-bold text-center mb-6 text-white"><?php echo $lang['step2_title']; ?></h2>
                
                <?php if (!empty($errors)): ?>
                    <div class="bg-red-500/20 text-red-300 p-4 rounded-lg mb-6">
                        <?php foreach ($errors as $error): ?>
                            <p><?php echo htmlspecialchars($error); ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form action="index.php?step=2" method="post" class="space-y-8">
                    
                    <fieldset>
                        <legend class="text-xl font-bold text-white border-b border-gray-700 pb-2 mb-4"><?php echo $lang['app_settings_title']; ?></legend>
                        <div>
                            <label for="site_title" class="form-label"><?php echo $lang['site_title_label']; ?></label>
                            <input type="text" id="site_title" name="site_title" class="w-full bg-gray-700 border border-gray-600 rounded-lg py-2.5 px-4 text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-sky-500 transition" placeholder="e.g., My Portfolio" required>
                            <p class="text-xs text-gray-500 mt-1"><?php echo $lang['site_title_hint']; ?></p>
                        </div>
                        <div class="mt-4">
                            <label for="app_url" class="form-label"><?php echo $lang['app_url_label']; ?></label>
                            <input type="url" id="app_url" name="app_url" class="w-full bg-gray-700 border border-gray-600 rounded-lg py-2.5 px-4 text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-sky-500 transition" value="<?php echo htmlspecialchars($auto_app_url); ?>" required>
                             <p class="text-xs text-gray-500 mt-1"><?php echo $lang['app_url_detected']; ?></p>
                        </div>
                    </fieldset>
                    
                    <fieldset>
                        <legend class="text-xl font-bold text-white border-b border-gray-700 pb-2 mb-4"><?php echo $lang['db_details_title']; ?></legend>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div><label for="db_host" class="form-label"><?php echo $lang['db_host_label']; ?></label><input type="text" id="db_host" name="db_host" class="w-full bg-gray-700 border border-gray-600 rounded-lg py-2.5 px-4 text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-sky-500 transition" value="localhost" required></div>
                            <div><label for="db_name" class="form-label"><?php echo $lang['db_name_label']; ?></label><input type="text" id="db_name" name="db_name" class="w-full bg-gray-700 border border-gray-600 rounded-lg py-2.5 px-4 text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-sky-500 transition" required></div>
                            <div><label for="db_user" class="form-label"><?php echo $lang['db_user_label']; ?></label><input type="text" id="db_user" name="db_user" class="w-full bg-gray-700 border border-gray-600 rounded-lg py-2.5 px-4 text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-sky-500 transition" required></div>
                            <div><label for="db_pass" class="form-label"><?php echo $lang['db_pass_label']; ?></label><input type="password" id="db_pass" name="db_pass" class="w-full bg-gray-700 border border-gray-600 rounded-lg py-2.5 px-4 text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-sky-500 transition"></div>
                        </div>
                    </fieldset>

                    <fieldset>
                        <legend class="text-xl font-bold text-white border-b border-gray-700 pb-2 mb-4"><?php echo $lang['admin_account_title']; ?></legend>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div><label for="admin_user" class="form-label"><?php echo $lang['admin_user_label']; ?></label><input type="text" id="admin_user" name="admin_user" class="w-full bg-gray-700 border border-gray-600 rounded-lg py-2.5 px-4 text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-sky-500 transition" required></div>
                            <div><label for="admin_email" class="form-label"><?php echo $lang['admin_email_label']; ?></label><input type="email" id="admin_email" name="admin_email" class="w-full bg-gray-700 border border-gray-600 rounded-lg py-2.5 px-4 text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-sky-500 transition" required></div>
                        </div>
                        <div class="mt-4"><label for="admin_pass" class="form-label"><?php echo $lang['admin_pass_label']; ?></label><input type="password" id="admin_pass" name="admin_pass" class="w-full bg-gray-700 border border-gray-600 rounded-lg py-2.5 px-4 text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-sky-500 transition" required></div>
                    </fieldset>

                    <button type="submit" name="install" class="btn-primary"><?php echo $lang['install_button']; ?></button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({
            duration: 800,
            once: true,
        });
    </script>
</body>
</html>