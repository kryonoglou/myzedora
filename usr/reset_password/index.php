<?php
require_once dirname(__DIR__, 2) . '/includes/map.php';

if (isset($_SESSION['user_id'])) {
    header("Location: " . HOME_URL);
    exit();
}

$errors = [];
$message = '';
$token = $_GET['token'] ?? null;
$show_form = false;

if (empty($token)) {
    $errors[] = htmlspecialchars($settings_data['reset_password_invalid_token']);
} else {
    $stmt = $pdo->prepare("SELECT id, reset_token_expires_at FROM users WHERE reset_token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if ($user) {
        if (new DateTime() > new DateTime($user['reset_token_expires_at'])) {
            $errors[] = htmlspecialchars($settings_data['reset_password_invalid_token']);
        } else {
            $show_form = true;
        }
    } else {
        $errors[] = htmlspecialchars($settings_data['reset_password_invalid_token']);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $show_form) {
    $posted_token = $_GET['token'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_new_password'] ?? '';

    if ($posted_token !== $token) {
        $errors[] = "Form submission error. Please try again.";
    }
    
    if (empty($new_password)) {
        $errors[] = htmlspecialchars($settings_data['password_change_empty_new_pass']);
    } elseif (strlen($new_password) < 8) {
        $errors[] = htmlspecialchars($settings_data['register_error_password_length']);
    } elseif ($new_password !== $confirm_password) {
        $errors[] = htmlspecialchars($settings_data['register_error_password_mismatch']);
    }

    if (empty($errors)) {
        $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
        
        $update_stmt = $pdo->prepare(
            "UPDATE users SET password_hash = ?, reset_token = NULL, reset_token_expires_at = NULL WHERE reset_token = ?"
        );

        if ($update_stmt->execute([$password_hash, $token])) {
            $message = htmlspecialchars($settings_data['reset_password_success']);
            $show_form = false;
        } else {
            $errors[] = "Failed to update password. Please try again.";
        }
    }
}

$page_title = $settings_data['reset_password_new_password_title'] . " - " . $settings_data['seo_title'];

require_once HEADER;
?>

<main class="pt-32 pb-20">
    <section id="reset-password" data-aos="fade-up">
        <div class="container mx-auto px-6">
            <div class="max-w-md mx-auto bg-gray-800/50 p-8 rounded-lg shadow-lg">
                <h2 class="text-3xl font-bold text-center mb-6 section-title"><?php echo htmlspecialchars($settings_data['reset_password_new_password_title']); ?></h2>
                
                <?php if (!empty($errors)): ?>
                    <div class="bg-red-500/20 text-red-300 p-4 rounded-lg mb-6">
                        <?php foreach ($errors as $error): ?><p><?php echo $error; ?></p><?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if ($message): ?>
                    <div class="bg-green-500/20 text-green-300 p-4 rounded-lg mb-6 text-center">
                        <p><?php echo $message; ?></p>
                    </div>
                <?php endif; ?>

                <?php if ($show_form): ?>
                    <form action="<?php echo RESET_PASSWORD_URL_BASE . '?token=' . htmlspecialchars($token); ?>" method="POST">
                        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                        <div class="mb-4">
                            <label for="new_password" class="block text-gray-300 mb-2"><?php echo htmlspecialchars($settings_data['password_change_new_pass_label']); ?></label>
                            <input type="password" id="new_password" name="new_password" class="w-full bg-gray-700 border border-gray-600 rounded-lg py-2 px-4 text-white" required>
                        </div>
                        <div class="mb-6">
                            <label for="confirm_new_password" class="block text-gray-300 mb-2"><?php echo htmlspecialchars($settings_data['password_change_confirm_new_pass_label']); ?></label>
                            <input type="password" id="confirm_new_password" name="confirm_new_password" class="w-full bg-gray-700 border border-gray-600 rounded-lg py-2 px-4 text-white" required>
                        </div>
                        <button type="submit" class="w-full bg-sky-500 text-white font-semibold py-3 rounded-lg hover:bg-sky-600"><?php echo htmlspecialchars($settings_data['reset_password_update_btn']); ?></button>
                    </form>
                <?php else: ?>
                    <div class="text-center mt-6">
                         <a href="<?php echo LOGIN_URL; ?>" class="text-sky-400 hover:underline">&larr; Back to Login</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
</main>

<?php require_once FOOTER; ?>