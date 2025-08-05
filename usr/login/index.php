<?php
require_once dirname(__DIR__, 2) . '/includes/map.php';

if (isset($_SESSION['user_id'])) {
    header("Location: " . HOME_URL);
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error = "Username and password are required.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            if ($user['is_active'] == 1) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['is_admin'] = $user['is_admin'];
                header("Location: " . HOME_URL);
                exit();
            } else {
                $error = htmlspecialchars($settings_data['login_error_inactive']);
            }
        } else {
            $error = htmlspecialchars($settings_data['login_error_message']);
        }
    }
}

$page_title = $settings_data['login_title'] . " - " . $settings_data['seo_title'];

require_once HEADER;
?>

<main class="pt-32 pb-20">
    <section id="login" data-aos="fade-up">
        <div class="container mx-auto px-6">
            <div class="max-w-md mx-auto bg-gray-800/50 p-8 rounded-lg shadow-lg">
                <h2 class="text-3xl font-bold text-center mb-6 section-title"><?php echo htmlspecialchars($settings_data['login_title']); ?></h2>

                <?php if ($error): ?>
                    <div class="bg-red-500/20 text-red-300 p-4 rounded-lg mb-6">
                        <p><?php echo $error; ?></p>
                    </div>
                <?php endif; ?>

                <form action="<?php echo LOGIN_URL; ?>" method="POST">
                    <div class="mb-4">
                        <label for="username" class="block text-gray-300 mb-2"><?php echo htmlspecialchars($settings_data['login_username_or_email']); ?></label>
                        <input type="text" id="username" name="username" class="w-full bg-gray-700 border border-gray-600 rounded-lg py-2 px-4 text-white" required>
                    </div>
                    <div class="mb-6">
                        <label for="password" class="block text-gray-300 mb-2"><?php echo htmlspecialchars($settings_data['login_password']); ?></label>
                        <input type="password" id="password" name="password" class="w-full bg-gray-700 border border-gray-600 rounded-lg py-2 px-4 text-white" required>
                    </div>
                    <button type="submit" class="w-full bg-sky-500 text-white font-semibold py-3 rounded-lg hover:bg-sky-600"><?php echo htmlspecialchars($settings_data['menu_login']); ?></button>
                    <div class="text-right mt-4">
                        <a href="<?php echo FORGOT_PASSWORD_URL; ?>" class="text-sm text-sky-400 hover:underline"><?php echo htmlspecialchars($settings_data['login_forgot_password']); ?></a>
                    </div>
                </form>

                <?php if (($settings_data['registration_mode'] ?? '0') != '2'): ?>
                <p class="text-center text-gray-400 mt-6">
                    <?php echo htmlspecialchars($settings_data['login_dont_have_account']); ?> <a href="<?php echo REGISTER_URL; ?>" class="text-sky-400 hover:underline"><?php echo htmlspecialchars($settings_data['login_register_now']); ?></a>
                </p>
                <?php endif; ?>
                
            </div>
        </div>
    </section>
</main>

<?php require_once FOOTER; ?>