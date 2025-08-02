<?php
require_once dirname(__DIR__) . '/includes/map.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: " . LOGIN_URL);
    exit();
}

$user_id = $_SESSION['user_id'];
$errors = [];
$success_message = '';

$stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    header("Location: " . LOGIN_URL);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_new_password = $_POST['confirm_new_password'] ?? '';

    if (!password_verify($current_password, $user['password_hash'])) {
        $errors[] = htmlspecialchars($settings_data['password_change_current_pass_error']);
    }

    if (empty($new_password) || empty($confirm_new_password)) {
        $errors[] = htmlspecialchars($settings_data['password_change_empty_new_pass']);
    } elseif ($new_password !== $confirm_new_password) {
        $errors[] = htmlspecialchars($settings_data['password_change_pass_mismatch']);
    } elseif (strlen($new_password) < 8) {
        $errors[] = htmlspecialchars($settings_data['password_change_pass_length_error']);
    }

    if (empty($errors)) {
        $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        if ($stmt->execute([$new_password_hash, $user_id])) {
            $success_message = htmlspecialchars($settings_data['password_change_success']);
        } else {
            $errors[] = htmlspecialchars($settings_data['password_change_fail']);
        }
    }
}

require_once HEADER;
?>
<main class="pt-32 pb-20">
    <section id="change-password" data-aos="fade-up">
        <div class="container mx-auto px-6">
            <div class="max-w-md mx-auto bg-gray-800/50 p-8 rounded-lg shadow-lg">
                <h2 class="text-3xl font-bold text-center mb-6 section-title"><?php echo htmlspecialchars($settings_data['password_change_title']); ?></h2>
                
                <?php if (!empty($errors)): ?>
                    <div class="bg-red-500/20 text-red-300 p-4 rounded-lg mb-6">
                        <?php foreach ($errors as $error): ?><p><?php echo $error; ?></p><?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <?php if ($success_message): ?>
                    <div class="bg-green-500/20 text-green-300 p-4 rounded-lg mb-6">
                        <p><?php echo $success_message; ?></p>
                    </div>
                <?php endif; ?>

                <form action="<?php echo CHANGE_PASSWORD_URL; ?>" method="POST">
                    <div class="mb-4">
                        <label for="current_password" class="block text-gray-300 mb-2"><?php echo htmlspecialchars($settings_data['password_change_current_pass_label']); ?></label>
                        <input type="password" id="current_password" name="current_password" class="w-full bg-gray-700 border border-gray-600 rounded-lg py-2 px-4 text-white" required>
                    </div>
                    <div class="mb-4">
                        <label for="new_password" class="block text-gray-300 mb-2"><?php echo htmlspecialchars($settings_data['password_change_new_pass_label']); ?></label>
                        <input type="password" id="new_password" name="new_password" class="w-full bg-gray-700 border border-gray-600 rounded-lg py-2 px-4 text-white" required>
                    </div>
                    <div class="mb-6">
                        <label for="confirm_new_password" class="block text-gray-300 mb-2"><?php echo htmlspecialchars($settings_data['password_change_confirm_new_pass_label']); ?></label>
                        <input type="password" id="confirm_new_password" name="confirm_new_password" class="w-full bg-gray-700 border border-gray-600 rounded-lg py-2 px-4 text-white" required>
                    </div>
                    <button type="submit" class="w-full bg-sky-500 text-white font-semibold py-3 rounded-lg hover:bg-sky-600"><?php echo htmlspecialchars($settings_data['password_change_save_btn']); ?></button>
                </form>
            </div>
        </div>
    </section>
</main>
<?php require_once FOOTER; ?>