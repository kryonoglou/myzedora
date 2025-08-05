<?php
require_once dirname(__DIR__, 2) . '/includes/map.php';

if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: " . HOME_URL);
    exit();
}

try {
    $announcements_stmt = $pdo->query("SELECT * FROM announcements ORDER BY created_at DESC");
    $announcements = $announcements_stmt->fetchAll();

    $subscribers_stmt = $pdo->query("SELECT COUNT(id) FROM users WHERE allow_announcements = 1");
    $subscriber_count = $subscribers_stmt->fetchColumn();
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

$page_title = $settings_data['announcements_title'] . " - " . $settings_data['seo_title'];

$message = $_SESSION['message'] ?? null;
$message_type = $_SESSION['message_type'] ?? 'success';
unset($_SESSION['message'], $_SESSION['message_type']);


require_once HEADER;
?>
<script>
    if (history.scrollRestoration) {
        history.scrollRestoration = 'manual';
    }
    window.scrollTo(0, 0);
</script>

<main class="pt-32 pb-20">
    <section id="manage-announcements" data-aos="fade-up">
        <div class="container mx-auto px-6 max-w-5xl">
            <h1 class="text-4xl font-bold text-center mb-4 section-title"><?php echo htmlspecialchars($settings_data['announcements_title']); ?></h1>
            <p class="text-center text-gray-400 mb-10"><?php echo htmlspecialchars($settings_data['announcements_subtitle']); ?></p>

            <div class="flex justify-end mb-6">
                <a href="<?php echo ADD_ANNOUNCEMENT_URL; ?>" class="bg-sky-500 text-white font-semibold px-6 py-2 rounded-lg hover:bg-sky-600 transition-colors duration-300">
                    <?php echo htmlspecialchars($settings_data['add_announcement_btn']); ?>
                </a>
            </div>

            <?php if ($message): ?>
                <div class="p-4 rounded-lg mb-6 text-center <?php echo $message_type === 'success' ? 'bg-green-500/20 text-green-300' : 'bg-red-500/20 text-red-300'; ?>">
                    <p><?php echo htmlspecialchars($message); ?></p>
                </div>
            <?php endif; ?>

            <div class="bg-gray-800/50 rounded-lg shadow-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-gray-900/50">
                            <tr>
                                <th class="p-4 font-semibold text-white"><?php echo htmlspecialchars($settings_data['col_subject']); ?></th>
                                <th class="p-4 font-semibold text-white hidden md:table-cell"><?php echo htmlspecialchars($settings_data['col_last_sent']); ?></th>
                                <th class="p-4 font-semibold text-white text-right"><?php echo htmlspecialchars($settings_data['col_actions']); ?></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-700">
                            <?php if (empty($announcements)): ?>
                                <tr><td colspan="3" class="p-8 text-center text-gray-400"><?php echo htmlspecialchars($settings_data['no_announcements_yet']); ?></td></tr>
                            <?php else: ?>
                                <?php foreach ($announcements as $item): ?>
                                    <tr>
                                        <td class="p-4 text-white font-semibold"><?php echo htmlspecialchars($item['subject']); ?></td>
                                        <td class="p-4 text-gray-400 hidden md:table-cell">
                                            <?php echo $item['last_sent_at'] ? date("M j, Y, g:i a", strtotime($item['last_sent_at'])) : htmlspecialchars($settings_data['status_never_sent']); ?>
                                        </td>
                                        <td class="p-4">
                                            <div class="flex items-center justify-end gap-2">
                                                <?php 
                                                    $confirm_send_message = str_replace('{{count}}', $subscriber_count, htmlspecialchars($settings_data['send_announcement_confirm']));
                                                ?>
                                                <a href="<?php echo SEND_ANNOUNCEMENT_URL_BASE . $item['id']; ?>" class="bg-green-600 text-white px-3 py-1 text-sm rounded-md hover:bg-green-700" onclick="return confirm('<?php echo $confirm_send_message; ?>');">
                                                    <?php echo htmlspecialchars($settings_data['action_send']); ?>
                                                </a>
                                                <a href="<?php echo EDIT_ANNOUNCEMENT_URL_BASE . $item['id']; ?>" class="bg-blue-600 text-white px-3 py-1 text-sm rounded-md hover:bg-blue-700">
                                                    <?php echo htmlspecialchars($settings_data['action_edit']); ?>
                                                </a>
                                                <a href="<?php echo DELETE_ANNOUNCEMENT_URL_BASE . $item['id']; ?>" class="bg-red-600 text-white px-3 py-1 text-sm rounded-md hover:bg-red-700" onclick="return confirm('<?php echo htmlspecialchars($settings_data['delete_announcement_confirm']); ?>');">
                                                    <?php echo htmlspecialchars($settings_data['action_delete']); ?>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
</main>
<?php require_once FOOTER; ?>