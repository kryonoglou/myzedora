<fieldset>
    <legend class="text-xl font-bold text-white mb-4 border-b border-gray-700 pb-2"><?php echo htmlspecialchars($settings_data['settings_legend_main_identity']); ?></legend>
    <div class="space-y-6">
        <div>
            <label class="block text-gray-300 mb-2 font-semibold"><?php echo htmlspecialchars($settings_data['settings_global_url']); ?></label>
            <input type="text" name="app_url" value="<?php echo htmlspecialchars($env_vars['APP_URL'] ?? ''); ?>" class="w-full bg-gray-700 rounded-lg py-2 px-4 text-white">
            <p class="text-xs text-red-400 mt-1"><?php echo htmlspecialchars($settings_data['settings_break_site']); ?></p>
        </div>
        <div>
            <label class="block text-gray-300 mb-2 font-semibold"><?php echo htmlspecialchars($settings_data['settings_label_site_name']); ?></label>
            <input type="text" name="settings[site_title]" value="<?php echo htmlspecialchars($raw_settings_data['site_title'] ?? 'myZedora'); ?>" class="w-full bg-gray-700 rounded-lg py-2 px-4 text-white">
            <p class="text-xs text-gray-500 mt-1"><?php echo htmlspecialchars($settings_data['settings_hint_site_name']); ?></p>
        </div>
        <div>
            <label class="block text-gray-300 mb-2 font-semibold"><?php echo htmlspecialchars($settings_data['settings_label_logo_text']); ?></label>
            <input type="text" name="settings[logo_text]" value="<?php echo htmlspecialchars($raw_settings_data['logo_text'] ?? ''); ?>" class="w-full bg-gray-700 rounded-lg py-2 px-4 text-white">
        </div>
        <div>
            <label class="block text-gray-300 mb-2 font-semibold"><?php echo htmlspecialchars($settings_data['settings_label_footer_copyright']); ?></label>
            <input type="text" name="settings[footer_copyright]" value="<?php echo htmlspecialchars($raw_settings_data['footer_copyright'] ?? ''); ?>" class="w-full bg-gray-700 rounded-lg py-2 px-4 text-white">
        </div>
    </div>
</fieldset>

<fieldset>
    <legend class="text-xl font-bold text-white mb-4 border-b border-gray-700 pb-2"><?php echo htmlspecialchars($settings_data['settings_registration_title']); ?></legend>
    <div>
        <label class="block text-gray-300 mb-2 font-semibold"><?php echo htmlspecialchars($settings_data['settings_registration_mode_label']); ?></label>
        <select name="settings[registration_mode]" class="w-full bg-gray-700 border border-gray-600 rounded-lg py-2 px-4 text-white">
            <option value="0" <?php if (($raw_settings_data['registration_mode'] ?? '0') == '0') echo 'selected'; ?>><?php echo htmlspecialchars($settings_data['settings_registration_mode_instant']); ?></option>
            <option value="1" <?php if (($raw_settings_data['registration_mode'] ?? '0') == '1') echo 'selected'; ?>><?php echo htmlspecialchars($settings_data['settings_registration_mode_email']); ?></option>
            <option value="2" <?php if (($raw_settings_data['registration_mode'] ?? '0') == '2') echo 'selected'; ?>><?php echo htmlspecialchars($settings_data['settings_registration_mode_disabled']); ?></option>
        </select>
        <p class="text-xs text-gray-500 mt-1"><?php echo htmlspecialchars($settings_data['settings_registration_hint']); ?></p>
    </div>
</fieldset>

<fieldset>
    <legend class="text-xl font-bold text-white mb-4 border-b border-gray-700 pb-2"><?php echo htmlspecialchars($settings_data['settings_url_structure_title']); ?></legend>
    <div>
        <label class="block text-gray-300 mb-2 font-semibold"><?php echo htmlspecialchars($settings_data['settings_label_url_format']); ?></label>
        <select name="settings[enable_url_rewriting]" class="w-full bg-gray-700 border border-gray-600 rounded-lg py-2 px-4 text-white">
            <option value="0" <?php if (($raw_settings_data['enable_url_rewriting'] ?? '0') == '0') echo 'selected'; ?>><?php echo htmlspecialchars($settings_data['settings_url_classic']); ?></option>
            <option value="1" <?php if (($raw_settings_data['enable_url_rewriting'] ?? '0') == '1') echo 'selected'; ?>><?php echo htmlspecialchars($settings_data['settings_url_pretty']); ?></option>
        </select>
        <p class="text-xs text-gray-500 mt-1"><?php echo htmlspecialchars($settings_data['settings_url_hint']); ?></p>
    </div>
</fieldset>
<fieldset>
    <legend class="text-xl font-bold text-white mb-4 border-b border-gray-700 pb-2"><?php echo htmlspecialchars($settings_data['members_visibility_title']); ?></legend>
    <div>
        <label class="block text-gray-300 mb-2 font-semibold"><?php echo htmlspecialchars($settings_data['members_visibility_label']); ?></label>
        <select name="settings[members_page_visibility]" class="w-full bg-gray-700 border border-gray-600 rounded-lg py-2 px-4 text-white">
            <option value="everyone" <?php if (($raw_settings_data['members_page_visibility'] ?? 'members') == 'everyone') echo 'selected'; ?>><?php echo htmlspecialchars($settings_data['members_visibility_option_everyone']); ?></option>
            <option value="members" <?php if (($raw_settings_data['members_page_visibility'] ?? 'members') == 'members') echo 'selected'; ?>><?php echo htmlspecialchars($settings_data['members_visibility_option_members']); ?></option>
            <option value="admins" <?php if (($raw_settings_data['members_page_visibility'] ?? 'members') == 'admins') echo 'selected'; ?>><?php echo htmlspecialchars($settings_data['members_visibility_option_admins']); ?></option>
        </select>
        <p class="text-xs text-gray-500 mt-1"><?php echo htmlspecialchars($settings_data['members_visibility_hint']); ?></p>
    </div>
</fieldset>
<fieldset>
    <legend class="text-xl font-bold text-white mb-4 border-b border-gray-700 pb-2"><?php echo htmlspecialchars($settings_data['settings_environment_title']); ?></legend>
    <div class="space-y-6">
        <div class="flex items-center">
            <input type="hidden" name="settings_app_env" value="production">
            <input type="checkbox" id="app_env_checkbox" name="settings_app_env" value="development" class="form-checkbox h-5 w-5 text-sky-500" <?php if (($env_vars['APP_ENV'] ?? '') === 'development') echo 'checked'; ?>>
            <label for="app_env_checkbox" class="ml-2 text-gray-300 text-sm font-semibold"><?php echo htmlspecialchars($settings_data['settings_set_development']); ?></label>
        </div>
    </div>
</fieldset>