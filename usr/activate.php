<?php
require_once dirname(__DIR__) . '/includes/map.php';

$message = '';
$is_success = false;
$token = $_GET['token'] ?? null;

if (empty($token)) {
    $message = htmlspecialchars($settings_data['activation_fail']);
} else {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE activation_token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if ($user) {
        $stmt_update = $pdo->prepare("UPDATE users SET is_active = 1, activation_token = NULL WHERE id = ?");
        $stmt_update->execute([$user['id']]);
        
        $message = htmlspecialchars($settings_data['activation_success']);
        $is_success = true;
    } else {
        $message = htmlspecialchars($settings_data['activation_fail']);
    }
}

$page_title = $is_success ? "Account Activated" : "Activation Failed";
$page_title .= " - " . $settings_data['seo_title'];

require_once HEADER;
?>

<main class="pt-32 pb-20">
    <section id="activation" data-aos="fade-up">
        <div class="container mx-auto px-6">
            <div class="max-w-md mx-auto bg-gray-800/50 p-8 rounded-lg shadow-lg text-center">
                
                <?php if ($is_success): ?>
                    <div class="bg-green-500/20 text-green-300 p-4 rounded-lg mb-6">
                        <p><?php echo $message; ?></p>
                    </div>
                    <a href="<?php echo LOGIN_URL; ?>" class="inline-block bg-sky-500 text-white font-semibold px-6 py-3 rounded-lg hover:bg-sky-600 transition-colors duration-300">
                        <?php echo htmlspecialchars($settings_data['menu_login']); ?>
                    </a>
                <?php else: ?>
                     <div class="bg-red-500/20 text-red-300 p-4 rounded-lg mb-6">
                        <p><?php echo $message; ?></p>
                    </div>
                     <a href="<?php echo REGISTER_URL; ?>" class="inline-block bg-sky-500 text-white font-semibold px-6 py-3 rounded-lg hover:bg-sky-600 transition-colors duration-300">
                        <?php echo htmlspecialchars($settings_data['menu_register']); ?>
                    </a>
                <?php endif; ?>

            </div>
        </div>
    </section>
</main>

<?php require_once FOOTER; ?>