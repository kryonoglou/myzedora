<fieldset>
    <legend class="sr-only"><?php echo htmlspecialchars($settings_data['settings_smtp_title']); ?></legend>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div><label class="block text-gray-300 mb-2 font-semibold"><?php echo htmlspecialchars($settings_data['settings_label_smtp_host']); ?></label><input type="text" name="settings[smtp_host]" value="<?php echo htmlspecialchars($raw_settings_data['smtp_host'] ?? ''); ?>" class="w-full bg-gray-700 rounded-lg py-2 px-4 text-white"></div>
        <div><label class="block text-gray-300 mb-2 font-semibold"><?php echo htmlspecialchars($settings_data['settings_label_smtp_port']); ?></label><input type="text" name="settings[smtp_port]" value="<?php echo htmlspecialchars($raw_settings_data['smtp_port'] ?? ''); ?>" class="w-full bg-gray-700 rounded-lg py-2 px-4 text-white"></div>
        <div><label class="block text-gray-300 mb-2 font-semibold"><?php echo htmlspecialchars($settings_data['settings_label_smtp_username']); ?></label><input type="text" name="settings[smtp_username]" value="<?php echo htmlspecialchars($raw_settings_data['smtp_username'] ?? ''); ?>" class="w-full bg-gray-700 rounded-lg py-2 px-4 text-white"></div>
        <div><label class="block text-gray-300 mb-2 font-semibold"><?php echo htmlspecialchars($settings_data['settings_label_smtp_password']); ?></label><input type="password" name="settings[smtp_password]" value="<?php echo htmlspecialchars($raw_settings_data['smtp_password'] ?? ''); ?>" class="w-full bg-gray-700 rounded-lg py-2 px-4 text-white"></div>
        <div><label class="block text-gray-300 mb-2 font-semibold"><?php echo htmlspecialchars($settings_data['settings_label_from_email']); ?></label><input type="email" name="settings[smtp_from_email]" value="<?php echo htmlspecialchars($raw_settings_data['smtp_from_email'] ?? ''); ?>" class="w-full bg-gray-700 rounded-lg py-2 px-4 text-white"></div>
        <div><label class="block text-gray-300 mb-2 font-semibold"><?php echo htmlspecialchars($settings_data['settings_label_from_name']); ?></label><input type="text" name="settings[smtp_from_name]" value="<?php echo htmlspecialchars($raw_settings_data['smtp_from_name'] ?? ''); ?>" class="w-full bg-gray-700 rounded-lg py-2 px-4 text-white"></div>
        <div class="md:col-span-2">
            <label class="block text-gray-300 mb-2 font-semibold"><?php echo htmlspecialchars($settings_data['settings_label_encryption']); ?></label>
            <select name="settings[smtp_secure]" class="w-full bg-gray-700 rounded-lg py-2 px-4 text-white">
                <option value="tls" <?php if (($raw_settings_data['smtp_secure'] ?? '') == 'tls') echo 'selected'; ?>><?php echo htmlspecialchars($settings_data['settings_option_tls']); ?></option>
                <option value="ssl" <?php if (($raw_settings_data['smtp_secure'] ?? '') == 'ssl') echo 'selected'; ?>><?php echo htmlspecialchars($settings_data['settings_option_ssl']); ?></option>
                <option value="" <?php if (($raw_settings_data['smtp_secure'] ?? '') == '') echo 'selected'; ?>><?php echo htmlspecialchars($settings_data['settings_option_none']); ?></option>
            </select>
        </div>
    </div>
</fieldset>