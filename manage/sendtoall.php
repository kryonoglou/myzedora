<?php

require_once dirname(__DIR__) . '/includes/map.php';

if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: " . HOME_URL);
    exit();
}

$errors = [];
$success_message = '';
$sent_count = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);

    if (isset($settings_data['site_title'])) {
        $subject = str_replace('{{site_title}}', $settings_data['site_title'], $subject);
        $message = str_replace('{{site_title}}', $settings_data['site_title'], $message);
    }

    if (empty($subject) || empty($message)) {
        $errors[] = htmlspecialchars($settings_data['send_announcements_error_empty']);
    }

    if (empty($errors)) {
        $stmt = $pdo->query("SELECT email, full_name FROM users WHERE allow_announcements = 1");
        $subscribers = $stmt->fetchAll();

        if (empty($subscribers)) {
            $errors[] = htmlspecialchars($settings_data['send_announcements_error_no_subscribers']);
        } else {
            foreach ($subscribers as $subscriber) {
                $personalized_message = str_replace('{{name}}', htmlspecialchars($subscriber['full_name']), $message);

                if (send_email($subscriber['email'], $subject, $personalized_message, $settings_data)) {
                    $sent_count++;
                }
            }
            $success_message = str_replace('{{count}}', $sent_count, $settings_data['send_announcements_success_message']);
        }
    }
}

$page_title = $settings_data['send_announcements_title'] . " - " . $settings_data['seo_title'];

require_once HEADER;
?>

<main class="pt-32 pb-20">
    <section id="send-announcements" data-aos="fade-up">
        <div class="container mx-auto px-6 max-w-3xl">
            <div class="bg-gray-800/50 p-8 rounded-lg shadow-lg">
                <h2 class="text-3xl font-bold text-center mb-6 section-title"><?php echo htmlspecialchars($settings_data['send_announcements_title']); ?></h2>
                <p class="text-center text-gray-400 mb-8"><?php echo htmlspecialchars($settings_data['send_announcements_subtitle']); ?></p>

                <?php if (!empty($errors)): ?><div class="bg-red-500/20 text-red-300 p-4 rounded-lg mb-6"><?php foreach ($errors as $error): ?><p><?php echo $error; ?></p><?php endforeach; ?></div><?php endif; ?>
                <?php if ($success_message): ?><div class="bg-green-500/20 text-green-300 p-4 rounded-lg mb-6"><p><?php echo $success_message; ?></p></div><?php endif; ?>

                <form action="<?php echo SEND_ANNOUNCEMENTS_URL; ?>" method="POST">
                    <div class="mb-4">
                        <label for="subject" class="block text-gray-300 mb-2 font-semibold"><?php echo htmlspecialchars($settings_data['send_announcements_subject_label']); ?></label>
                        <input type="text" id="subject" name="subject" class="w-full bg-gray-700 border border-gray-600 rounded-lg py-2 px-4 text-white" required>
                    </div>
                    <div class="mb-6">
                        <label for="message" class="block text-gray-300 mb-2 font-semibold"><?php echo htmlspecialchars($settings_data['send_announcements_message_label']); ?></label>
                        <textarea id="message" name="message" rows="12" class="w-full bg-gray-700 border border-gray-600 rounded-lg py-2 px-4 text-white" required></textarea>
                        <p class="text-xs text-gray-500 mt-1"><?php echo htmlspecialchars($settings_data['send_announcements_message_hint']); ?></p>
                    </div>
                    <button type="submit" class="w-full bg-sky-500 text-white font-semibold py-3 rounded-lg hover:bg-sky-600 transition-colors duration-300"><?php echo htmlspecialchars($settings_data['send_announcements_send_btn']); ?></button>
                </form>
            </div>
        </div>
    </section>
</main>

<?php require_once FOOTER; ?>