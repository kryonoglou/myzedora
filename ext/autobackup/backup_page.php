<?php
require_once dirname(__DIR__, 2) . '/includes/map.php';

if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: " . HOME_URL);
    exit();
}

$page_title = plugin_lang('autobackup', 'autobackup_title') . " - " . $settings_data['seo_title'];

$backup_email_is_set = !empty($settings_data['autobackup_email']) && filter_var($settings_data['autobackup_email'], FILTER_VALIDATE_EMAIL);

require_once HEADER;
?>

<main id="backup-page" class="pb-20">
    <section id="manual-backup-restore" data-aos="fade-up">
        <div class="container mx-auto px-6 max-w-2xl">
            <div class="bg-gray-800/50 p-8 rounded-lg shadow-lg">

                <h2 class="text-3xl font-bold section-title mb-6"><?php echo plugin_lang('autobackup', 'autobackup_manual_title'); ?></h2>
                
                <?php if (isset($_SESSION['autobackup_message'])): ?>
                    <?php 
                        $message = $_SESSION['autobackup_message'];
                        $message_class = $message['type'] === 'success' 
                            ? 'bg-green-500/20 text-green-300' 
                            : 'bg-red-500/20 text-red-300';
                        unset($_SESSION['autobackup_message']);
                    ?>
                    <div class="<?php echo $message_class; ?> p-4 rounded-lg mb-6 text-center">
                        <p><?php echo htmlspecialchars($message['text']); ?></p>
                    </div>
                <?php endif; ?>

                <?php if (!$backup_email_is_set): ?>
                    <div class="bg-yellow-500/20 text-yellow-300 p-4 rounded-lg mb-6 text-center">
                        <p><?php echo plugin_lang('autobackup', 'autobackup_email_not_set'); ?></p>
                    </div>
                <?php endif; ?>

                <p class="text-gray-400 mb-6"><?php echo plugin_lang('autobackup', 'autobackup_manual_desc'); ?></p>

                <form method="POST" action="<?php echo HOME_URL . 'ext/autobackup/handler.php'; ?>">
                    <input type="hidden" name="action" value="manual_backup">
                    <button type="submit" 
                            class="w-full bg-sky-500 text-white font-semibold py-3 rounded-lg transition-colors duration-300 <?php echo $backup_email_is_set ? 'hover:bg-sky-600' : 'opacity-50 cursor-not-allowed'; ?>"
                            <?php echo $backup_email_is_set ? '' : 'disabled'; ?>>
                        <?php echo plugin_lang('autobackup', 'autobackup_manual_button'); ?>
                    </button>
                </form>

                <hr class="my-8 border-gray-700">

                <h2 class="text-3xl font-bold section-title mb-6"><?php echo plugin_lang('autobackup', 'autobackup_restore_title'); ?></h2>
                <p class="text-gray-400 mb-6"><?php echo plugin_lang('autobackup', 'autobackup_restore_desc'); ?></p>
                
                <form method="POST" action="<?php echo HOME_URL . 'ext/autobackup/handler.php'; ?>" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="restore_backup">
                    <div class="mb-4">
                        <label for="backup_file" class="sr-only"><?php echo plugin_lang('autobackup', 'autobackup_select_file'); ?></label>
                        <input type="file" name="backup_file" id="backup_file" accept=".sql" required 
                               class="w-full text-white bg-gray-700 border border-gray-600 rounded-lg py-2 px-4 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-sky-500 file:text-white hover:file:bg-sky-600">
                    </div>
                    <button type="submit" 
                            onclick="return confirm('<?php echo htmlspecialchars(plugin_lang('autobackup', 'autobackup_restore_warning')); ?>');"
                            class="w-full bg-red-600 text-white font-semibold py-3 rounded-lg transition-colors duration-300 <?php echo $backup_email_is_set ? 'hover:bg-red-700' : 'opacity-50 cursor-not-allowed'; ?>"
                            <?php echo $backup_email_is_set ? '' : 'disabled'; ?>>
                        <?php echo plugin_lang('autobackup', 'autobackup_restore_button'); ?>
                    </button>
                </form>

            </div>
        </div>
    </section>
</main>

<?php require_once FOOTER; ?>