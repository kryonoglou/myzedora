<fieldset x-data="{ enabled: <?php echo ($raw_settings_data['enable_tinymce'] ?? '0') == '1' ? 'true' : 'false'; ?> }">
    <legend class="sr-only"><?php echo htmlspecialchars($settings_data['settings_api_title']); ?></legend>
    <div class="space-y-4">
        <div>
            <label class="flex items-center text-gray-300">
                <input type="checkbox" name="settings[enable_tinymce]" value="1" x-model="enabled" <?php if (($raw_settings_data['enable_tinymce'] ?? '0') == '1') echo 'checked'; ?> class="form-checkbox h-5 w-5 text-sky-500 bg-gray-700 border-gray-600 rounded focus:ring-sky-500">
                <span class="ml-2 font-semibold"><?php echo htmlspecialchars($settings_data['settings_enable_tinymce_label']); ?></span>
            </label>
            <p class="text-xs text-gray-500 mt-1 ml-7"><?php echo htmlspecialchars($settings_data['settings_enable_tinymce_hint']); ?></p>
        </div>
        <div x-show="enabled" style="display: none;">
            <label for="tinymce_api_key" class="block text-gray-300 mb-2 font-semibold"><?php echo htmlspecialchars($settings_data['settings_tinymce_api_key_label']); ?></label>
            <input type="text" id="tinymce_api_key" name="settings[tinymce_api_key]" value="<?php echo htmlspecialchars($raw_settings_data['tinymce_api_key'] ?? ''); ?>" class="w-full bg-gray-700 rounded-lg py-2 px-4 text-white">
            <p class="text-xs text-gray-500 mt-1"><?php echo htmlspecialchars($settings_data['settings_tinymce_api_key_hint']); ?></p>
        </div>
    </div>
</fieldset>