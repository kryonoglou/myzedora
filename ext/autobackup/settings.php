<?php

if (isset($_SESSION['autobackup_message'])) {
    $message = $_SESSION['autobackup_message'];
    $message_class = $message['type'] === 'success' 
        ? 'bg-green-500/20 text-green-300' 
        : 'bg-red-500/20 text-red-300';
    echo '<div class="' . $message_class . ' p-4 rounded-lg mb-6 text-center"><p>' . htmlspecialchars($message['text']) . '</p></div>';
    unset($_SESSION['autobackup_message']);
}
?>
<fieldset class="border-t border-gray-700 pt-6 mt-6">
    <legend class="text-xl font-bold text-white mb-4"><?php echo plugin_lang('autobackup', 'autobackup_automatic_backup'); ?></legend>
    <div class="space-y-4">
        <div>
            <label class="flex items-center text-gray-300">
                <input type="hidden" name="settings[plugin_autobackup_enabled]" value="0">
                <input 
                    type="checkbox" 
                    name="settings[plugin_autobackup_enabled]" 
                    value="1" 
                    <?php if (($raw_settings_data['plugin_autobackup_enabled'] ?? '0') == '1') echo 'checked'; ?> 
                    class="form-checkbox h-5 w-5 text-sky-500 bg-gray-700 border-gray-600 rounded focus:ring-sky-500"
                >
                <span class="ml-2 font-semibold"><?php echo plugin_lang('autobackup', 'autobackup_enable_backups'); ?></span>
            </label>
            <p class="text-xs text-gray-500 mt-1 ml-7"><?php echo plugin_lang('autobackup', 'autobackup_enabled_schedule'); ?></p>
        </div>
        <div>
            <label for="autobackup_email" class="block text-gray-300 mb-2 font-semibold"><?php echo plugin_lang('autobackup', 'autobackup_email_address'); ?></label>
            <input 
                type="email" 
                id="autobackup_email" 
                name="settings[autobackup_email]" 
                value="<?php echo htmlspecialchars($raw_settings_data['autobackup_email'] ?? ''); ?>" 
                class="w-full bg-gray-700 rounded-lg py-2 px-4 text-white" 
                placeholder="your-email@example.com"
            >
            <p class="text-xs text-gray-500 mt-1"><?php echo plugin_lang('autobackup', 'autobackup_email_sent'); ?></p>
        </div>
        <div>
            <label for="autobackup_frequency" class="block text-gray-300 mb-2 font-semibold"><?php echo plugin_lang('autobackup', 'autobackup_frequency'); ?></label>
            <select 
                id="autobackup_frequency" 
                name="settings[autobackup_frequency]" 
                class="w-full bg-gray-700 rounded-lg py-2 px-4 text-white"
            >
                <option value="daily" <?php if (($raw_settings_data['autobackup_frequency'] ?? 'weekly') == 'daily') echo 'selected'; ?>><?php echo plugin_lang('autobackup', 'autobackup_daily'); ?></option>
                <option value="weekly" <?php if (($raw_settings_data['autobackup_frequency'] ?? 'weekly') == 'weekly') echo 'selected'; ?>><?php echo plugin_lang('autobackup', 'autobackup_weekly'); ?></option>
                <option value="monthly" <?php if (($raw_settings_data['autobackup_frequency'] ?? 'weekly') == 'monthly') echo 'selected'; ?>><?php echo plugin_lang('autobackup', 'autobackup_monthly'); ?></option>
            </select>
            <p class="text-xs text-gray-500 mt-1"><?php echo plugin_lang('autobackup', 'autobackup_how_often'); ?></p>
        </div>
    </div>
</fieldset>