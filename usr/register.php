<?php
require_once dirname(__DIR__) . '/includes/map.php';

$errors = [];
$success_message = '';
$registration_mode = $settings_data['registration_mode'] ?? '0';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($registration_mode == '2') {
        $errors[] = htmlspecialchars($settings_data['register_disabled_message']);
    } else {
        $full_name = trim($_POST['full_name']);
        $username = trim($_POST['username']);
        $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'];
        $password_confirm = $_POST['password_confirm'];

        if (empty($full_name) || empty($username) || empty($email) || empty($password)) { $errors[] = htmlspecialchars($settings_data['register_error_fields_required']); }
        if ($password !== $password_confirm) { $errors[] = htmlspecialchars($settings_data['register_error_password_mismatch']); }
        if (strlen($password) < 8) { $errors[] = htmlspecialchars($settings_data['register_error_password_length']); }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors[] = htmlspecialchars($settings_data['register_error_invalid_email']); }
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) { $errors[] = htmlspecialchars($settings_data['register_error_invalid_username']); }

        if (empty($errors)) {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            if ($stmt->fetch()) { $errors[] = htmlspecialchars($settings_data['register_error_username_email_taken']); }
        }

        if (empty($errors)) {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            if ($registration_mode == '0') {
                $stmt = $pdo->prepare("INSERT INTO users (full_name, username, email, password_hash, is_active) VALUES (?, ?, ?, ?, 1)");
                if ($stmt->execute([$full_name, $username, $email, $password_hash])) {
                    $success_message = htmlspecialchars($settings_data['register_success_message']);
                    $body_replacements = ['{{name}}' => htmlspecialchars($full_name), '{{username}}' => htmlspecialchars($username)];
                    $welcome_subject = $settings_data['register_welcome_email_subject'];
                    $welcome_body = str_replace(array_keys($body_replacements), array_values($body_replacements), $settings_data['register_welcome_email_body']);
                    send_email($email, $welcome_subject, $welcome_body, $settings_data);
                } else {
                    $errors[] = htmlspecialchars($settings_data['register_error_server_fail']);
                }
            } 
            elseif ($registration_mode == '1') {
                $activation_token = bin2hex(random_bytes(32));
                $stmt = $pdo->prepare("INSERT INTO users (full_name, username, email, password_hash, activation_token) VALUES (?, ?, ?, ?, ?)");
                if ($stmt->execute([$full_name, $username, $email, $password_hash, $activation_token])) {
                    $success_message = htmlspecialchars($settings_data['register_success_email_activation']);
                    $activation_link = ACTIVATE_URL_BASE . '?token=' . $activation_token;
                    $body_replacements = ['{{name}}' => htmlspecialchars($full_name), '{{activation_link}}' => $activation_link];
                    $activation_subject = $settings_data['activation_subject'];
                    $activation_body = str_replace(array_keys($body_replacements), array_values($body_replacements), $settings_data['activation_email_body']);
                    send_email($email, $activation_subject, $activation_body, $settings_data);
                } else {
                    $errors[] = htmlspecialchars($settings_data['register_error_server_fail']);
                }
            }
        }
    }
}

$page_title = $settings_data['register_title'] . " - " . $settings_data['seo_title'];

require_once HEADER;
?>

<main class="pt-32 pb-20">
    <section id="register" data-aos="fade-up">
        <div class="container mx-auto px-6">
            <div class="max-w-md mx-auto bg-gray-800/50 p-8 rounded-lg shadow-lg">
                <h2 class="text-3xl font-bold text-center mb-6 section-title"><?php echo htmlspecialchars($settings_data['register_title']); ?></h2>

                <?php if (!empty($errors)): ?>
                    <div class="bg-red-500/20 text-red-300 p-4 rounded-lg mb-6">
                        <?php foreach ($errors as $error): ?><p><?php echo $error; ?></p><?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if ($success_message): ?>
                    <div class="bg-green-500/20 text-green-300 p-4 rounded-lg mb-6">
                        <p><?php echo $success_message; ?>
                           <?php if ($registration_mode == '0'): ?>
                           <a href="<?php echo LOGIN_URL; ?>" class="font-bold underline"><?php echo htmlspecialchars($settings_data['menu_login']); ?></a>.
                           <?php endif; ?>
                        </p>
                    </div>
                <?php elseif ($registration_mode == '2'): ?>
                    <div class="bg-yellow-500/20 text-yellow-300 p-4 rounded-lg mb-6 text-center">
                        <p><?php echo htmlspecialchars($settings_data['register_disabled_message']); ?></p>
                    </div>
                <?php else: ?>
                    <form action="<?php echo REGISTER_URL; ?>" method="POST">
                        <div class="mb-4">
                            <label for="full_name" class="block text-gray-300 mb-2"><?php echo htmlspecialchars($settings_data['edit_profile_full_name']); ?></label>
                            <input type="text" id="full_name" name="full_name" class="w-full bg-gray-700 border border-gray-600 rounded-lg py-2 px-4 text-white" required>
                        </div>
                        <div class="mb-4">
                            <label for="username" class="block text-gray-300 mb-2"><?php echo htmlspecialchars($settings_data['edit_profile_username']); ?></label>
                            <input type="text" id="username" name="username" class="w-full bg-gray-700 border border-gray-600 rounded-lg py-2 px-4 text-white" required>
                        </div>
                        <div class="mb-4">
                            <label for="email" class="block text-gray-300 mb-2"><?php echo htmlspecialchars($settings_data['edit_profile_email']); ?></label>
                            <input type="email" id="email" name="email" class="w-full bg-gray-700 border border-gray-600 rounded-lg py-2 px-4 text-white" required>
                        </div>
                        <div class="mb-4">
                            <label for="password" class="block text-gray-300 mb-2"><?php echo htmlspecialchars($settings_data['login_password']); ?></label>
                            <input type="password" id="password" name="password" class="w-full bg-gray-700 border border-gray-600 rounded-lg py-2 px-4 text-white" required>
                        </div>
                        <div class="mb-6">
                            <label for="password_confirm" class="block text-gray-300 mb-2"><?php echo htmlspecialchars($settings_data['register_confirm_pass_label']); ?></label>
                            <input type="password" id="password_confirm" name="password_confirm" class="w-full bg-gray-700 border border-gray-600 rounded-lg py-2 px-4 text-white" required>
                        </div>
                        <button type="submit" class="w-full bg-sky-500 text-white font-semibold py-3 rounded-lg hover:bg-sky-600"><?php echo htmlspecialchars($settings_data['menu_register']); ?></button>
                    </form>
                <?php endif; ?>

                <?php if (empty($success_message)): ?>
                <p class="text-center text-gray-400 mt-6">
                    <?php echo htmlspecialchars($settings_data['register_already_have_account']); ?> <a href="<?php echo LOGIN_URL; ?>" class="text-sky-400 hover:underline"><?php echo htmlspecialchars($settings_data['menu_login']); ?></a>
                </p>
                <?php endif; ?>
            </div>
        </div>
    </section>
</main>

<?php require_once FOOTER; ?>