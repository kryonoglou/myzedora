<?php
require_once dirname(__DIR__, 2) . '/includes/map.php';

if (isset($_SESSION['user_id'])) {
    header("Location: " . HOME_URL);
    exit();
}

$message = '';
$is_success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);

    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $stmt = $pdo->prepare("SELECT id, full_name FROM users WHERE email = ? AND is_active = 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', time() + 3600);

            $update_stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_token_expires_at = ? WHERE id = ?");
            $update_stmt->execute([$token, $expires, $user['id']]);

            $reset_link = RESET_PASSWORD_URL_BASE . '?token=' . $token;
            $body_replacements = ['{{name}}' => htmlspecialchars($user['full_name']), '{{reset_link}}' => $reset_link];
            
            $reset_subject = $settings_data['reset_password_email_subject'];
            $reset_body = str_replace(array_keys($body_replacements), array_values($body_replacements), $settings_data['reset_password_email_body']);
            
            send_email($email, $reset_subject, $reset_body, $settings_data);
        }
        
        $message = htmlspecialchars($settings_data['reset_password_email_sent']);
        $is_success = true;
    } else {
        $message = htmlspecialchars($settings_data['reset_password_email_sent']);
        $is_success = true;
    }
}

$page_title = $settings_data['reset_password_title'] . " - " . $settings_data['seo_title'];

require_once HEADER;
?>

<main class="pt-32 pb-20">
    <section id="forgot-password" data-aos="fade-up">
        <div class="container mx-auto px-6">
            <div class="max-w-md mx-auto bg-gray-800/50 p-8 rounded-lg shadow-lg">
                <h2 class="text-3xl font-bold text-center mb-4 section-title"><?php echo htmlspecialchars($settings_data['reset_password_title']); ?></h2>
                
                <?php if ($message): ?>
                    <div class="bg-green-500/20 text-green-300 p-4 rounded-lg mb-6 text-center">
                        <p><?php echo $message; ?></p>
                    </div>
                <?php else: ?>
                    <p class="text-center text-gray-400 mb-6"><?php echo htmlspecialchars($settings_data['reset_password_instructions']); ?></p>
                    <form action="<?php echo FORGOT_PASSWORD_URL; ?>" method="POST">
                        <div class="mb-4">
                            <label for="email" class="block text-gray-300 mb-2"><?php echo htmlspecialchars($settings_data['reset_password_email_label']); ?></label>
                            <input type="email" id="email" name="email" class="w-full bg-gray-700 border border-gray-600 rounded-lg py-2 px-4 text-white" required>
                        </div>
                        <button type="submit" class="w-full bg-sky-500 text-white font-semibold py-3 rounded-lg hover:bg-sky-600"><?php echo htmlspecialchars($settings_data['reset_password_send_link_btn']); ?></button>
                    </form>
                <?php endif; ?>
                 <div class="text-center mt-6">
                    <a href="<?php echo LOGIN_URL; ?>" class="text-sm text-sky-400 hover:underline">&larr; Back to Login</a>
                </div>
            </div>
        </div>
    </section>
</main>

<?php require_once FOOTER; ?>