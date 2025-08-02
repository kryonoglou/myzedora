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
    $password_confirmation = $_POST['password_confirmation'] ?? '';

    if (!password_verify($password_confirmation, $user['password_hash'])) {
        $errors[] = htmlspecialchars($settings_data['erase_account_wrong_pass']);
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            $stmt_posts = $pdo->prepare("DELETE FROM posts WHERE user_id = ?");
            $stmt_posts->execute([$user_id]);

            $stmt_user = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt_user->execute([$user_id]);

            $pdo->commit();

            $_SESSION = [];
            session_destroy();
            header("Location: " . HOME_URL . "?account_deleted=true");
            exit();

        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors[] = htmlspecialchars($settings_data['erase_account_fail']) . " " . $e->getMessage();
        }
    }
}

require_once HEADER;
?>
<main class="pt-32 pb-20">
    <section id="erase-account" data-aos="fade-up">
        <div class="container mx-auto px-6">
            <div class="max-w-md mx-auto bg-gray-800/50 p-8 rounded-lg shadow-lg">
                <h2 class="text-3xl font-bold text-center mb-6 section-title text-red-400"><?php echo htmlspecialchars($settings_data['erase_account_title']); ?></h2>
                <p class="text-center text-gray-400 mb-8"><?php echo htmlspecialchars($settings_data['erase_account_warning']); ?></p>
                
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

                <form action="<?php echo ERASE_ACCOUNT_URL; ?>" method="POST">
                    <div class="mb-4">
                        <label for="password_confirmation" class="block text-gray-300 mb-2"><?php echo htmlspecialchars($settings_data['erase_account_confirm_pass_label']); ?></label>
                        <input type="password" id="password_confirmation" name="password_confirmation" class="w-full bg-gray-700 border border-gray-600 rounded-lg py-2 px-4 text-white" required>
                    </div>
                    <button type="submit" class="w-full bg-red-600 text-white font-semibold py-3 rounded-lg hover:bg-red-700"><?php echo htmlspecialchars($settings_data['erase_account_confirm_btn']); ?></button>
                </form>
            </div>
        </div>
    </section>
</main>
<?php require_once FOOTER; ?>