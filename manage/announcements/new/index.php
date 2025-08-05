<?php
require_once dirname(__DIR__, 3) . '/includes/map.php';

if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: " . HOME_URL);
    exit();
}

$errors = [];
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (empty($subject) || empty($message)) {
        $errors[] = htmlspecialchars($settings_data['announcement_error_empty']);
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO announcements (subject, message) VALUES (?, ?)");
            if ($stmt->execute([$subject, $message])) {
                $success_message = htmlspecialchars($settings_data['announcement_saved_success']);
            } else {
                $errors[] = htmlspecialchars($settings_data['announcement_save_fail']);
            }
        } catch (PDOException $e) {
            $errors[] = htmlspecialchars($settings_data['announcement_save_fail']) . ": " . $e->getMessage();
        }
    }
}

$page_title = $settings_data['create_announcement_title'] . " - " . $settings_data['seo_title'];

require_once HEADER;
?>

<main class="pt-32 pb-20">
    <section id="new-announcement" data-aos="fade-up">
        <div class="container mx-auto px-6 max-w-3xl">
            <h1 class="text-4xl font-bold text-center mb-10 section-title"><?php echo htmlspecialchars($settings_data['create_announcement_title']); ?></h1>

            <div class="bg-gray-800/50 p-8 rounded-lg shadow-lg">
                <?php if ($success_message): ?><div class="bg-green-500/20 text-green-300 p-4 rounded-lg mb-6"><p><?php echo $success_message; ?> <a href="<?php echo SEND_ANNOUNCEMENTS_URL; ?>" class="font-bold underline">Back to list.</a></p></div><?php endif; ?>
                <?php if (!empty($errors)): ?><div class="bg-red-500/20 text-red-300 p-4 rounded-lg mb-6"><?php foreach($errors as $error) echo "<p>$error</p>"; ?></div><?php endif; ?>

                <form action="<?php echo ADD_ANNOUNCEMENT_URL; ?>" method="POST">
                    <div class="mb-4">
                        <label for="subject" class="block text-gray-300 mb-2 font-semibold"><?php echo htmlspecialchars($settings_data['announcement_subject_label']); ?></label>
                        <input type="text" id="subject" name="subject" class="w-full bg-gray-700 border border-gray-600 rounded-lg py-2 px-4 text-white focus:outline-none focus:ring-2 focus:ring-sky-500" required>
                    </div>

                    <div class="mb-6">
                        <label for="message" class="block text-gray-300 mb-2 font-semibold"><?php echo htmlspecialchars($settings_data['announcement_message_label']); ?></label>
                        <textarea id="message" name="message" rows="10" class="w-full bg-gray-700 border border-gray-600 rounded-lg py-2 px-4 text-white focus:outline-none focus:ring-2 focus:ring-sky-500" required></textarea>
                        <p class="text-xs text-gray-500 mt-1"><?php echo htmlspecialchars($settings_data['announcement_message_hint']); ?></p>
                    </div>

                    <div class="flex items-center justify-between">
                        <a href="<?php echo SEND_ANNOUNCEMENTS_URL; ?>" class="text-sky-400 hover:underline">&larr; Back to Announcements</a>
                        <button type="submit" class="bg-sky-500 text-white font-semibold py-3 px-6 rounded-lg hover:bg-sky-600 transition-colors duration-300"><?php echo htmlspecialchars($settings_data['save_announcement_btn']); ?></button>
                    </div>
                </form>
            </div>
        </div>
    </section>
</main>

<?php require_once FOOTER; ?>