<?php
require_once dirname(__DIR__, 3) . '/includes/map.php';

if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: " . HOME_URL);
    exit();
}

$errors = [];
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $css_code = trim($_POST['css_code']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if (empty($name) || empty($css_code)) {
        $errors[] = htmlspecialchars($settings_data['style_name_code_required']);
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            if ($is_active) {
                $pdo->exec("UPDATE custom_styles SET is_active = 0 WHERE is_active = 1");
            }

            $stmt = $pdo->prepare("INSERT INTO custom_styles (name, css_code, is_active) VALUES (?, ?, ?)");
            $stmt->execute([$name, $css_code, $is_active]);

            $pdo->commit();
            $success_message = htmlspecialchars($settings_data['style_created_success']);
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors[] = htmlspecialchars($settings_data['style_create_fail']);
        }
    }
}

$page_title = $settings_data['add_style_title'] . " - " . $settings_data['seo_title'];

require_once HEADER;
?>

<main class="pt-32 pb-20">
    <section id="add-style" data-aos="fade-up">
        <div class="container mx-auto px-6 max-w-3xl">
            <div class="bg-gray-800/50 p-8 rounded-lg shadow-lg">
                <h2 class="text-3xl font-bold text-center mb-6 section-title"><?php echo htmlspecialchars($settings_data['add_style_title']); ?></h2>

                <?php if (!empty($errors)): ?>
                    <div class="bg-red-500/20 text-red-300 p-4 rounded-lg mb-6">
                        <?php foreach ($errors as $error): ?><p><?php echo $error; ?></p><?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if ($success_message): ?>
                    <div class="bg-green-500/20 text-green-300 p-4 rounded-lg mb-6">
                        <p><?php echo $success_message; ?> <a href="<?php echo STYLES_URL; ?>" class="font-bold underline">Back to list.</a></p>
                    </div>
                <?php endif; ?>

                <form action="<?php echo ADD_STYLE_URL; ?>" method="POST">
                    <div class="mb-4">
                        <label for="name" class="block text-gray-300 mb-2 font-semibold"><?php echo htmlspecialchars($settings_data['style_name_label']); ?></label>
                        <input type="text" id="name" name="name" class="w-full bg-gray-700 border border-gray-600 rounded-lg py-2 px-4 text-white" required>
                        <p class="text-xs text-gray-500 mt-1"><?php echo htmlspecialchars($settings_data['style_name_hint']); ?></p>
                    </div>

                    <div class="mb-6">
                        <label for="css_code" class="block text-gray-300 mb-2 font-semibold"><?php echo htmlspecialchars($settings_data['css_code_label']); ?></label>
                        <textarea id="css_code" name="css_code" rows="15" class="w-full bg-gray-900 border border-gray-600 rounded-lg py-2 px-4 text-white font-mono text-sm" required></textarea>
                        <p class="text-xs text-gray-500 mt-1"><?php echo htmlspecialchars($settings_data['css_code_hint']); ?></p>
                    </div>
                    
                    <div class="mb-6">
                        <label class="flex items-center text-gray-300">
                            <input type="checkbox" name="is_active" value="1" class="form-checkbox h-5 w-5 text-sky-500 bg-gray-700 border-gray-600 rounded focus:ring-sky-500">
                            <span class="ml-2 font-semibold"><?php echo htmlspecialchars($settings_data['style_active_label']); ?></span>
                        </label>
                        <p class="text-xs text-gray-500 mt-1 ml-7"><?php echo htmlspecialchars($settings_data['style_active_hint']); ?></p>
                    </div>

                    <button type="submit" class="w-full bg-sky-500 text-white font-semibold py-3 rounded-lg hover:bg-sky-600 transition-colors duration-300"><?php echo htmlspecialchars($settings_data['save_style_btn']); ?></button>
                </form>
            </div>
        </div>
    </section>
</main>

<?php require_once FOOTER; ?>