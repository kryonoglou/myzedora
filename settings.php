<?php
require_once __DIR__ . '/includes/map.php';

if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: " . HOME_URL);
    exit();
}

$errors = [];
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_settings = $_POST['settings'];
    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        foreach ($new_settings as $key => $value) {
            $stmt->execute([$key, trim($value)]);
        }
        $pdo->commit();
        $success_message = $settings_data['settings_msg_update_success'];
        
        $settings_stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings");
        $settings_data = $settings_stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        if (isset($settings_data['site_title'])) {
            $site_title = $settings_data['site_title'];
            foreach ($settings_data as $key => $value) {
                if (is_string($value)) {
                    $settings_data[$key] = str_replace('{{site_title}}', $site_title, $value);
                }
            }
        }

    } catch (Exception $e) {
        $pdo->rollBack();
        $errors[] = ($settings_data['settings_msg_update_fail'] ?? 'Failed to update settings:') . " " . $e->getMessage();
    }
}

$page_title = $settings_data['menu_site_settings'] . " - " . $settings_data['seo_title'];

require_once HEADER;

$raw_stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings");
$raw_settings_data = $raw_stmt->fetchAll(PDO::FETCH_KEY_PAIR);
?>

<main class="pt-32 pb-20">
    <section id="settings" data-aos="fade-up">
        <div class="container mx-auto px-6 max-w-4xl">
            <div class="bg-gray-800/50 p-8 rounded-lg shadow-lg">
                <h2 class="text-3xl font-bold text-center mb-8 section-title"><?php echo htmlspecialchars($settings_data['menu_site_settings']); ?></h2>

                <?php if (!empty($errors)): ?><div class="bg-red-500/20 text-red-300 p-4 rounded-lg mb-6"><?php foreach ($errors as $error): ?><p><?php echo $error; ?></p><?php endforeach; ?></div><?php endif; ?>
                <?php if ($success_message): ?><div class="bg-green-500/20 text-green-300 p-4 rounded-lg mb-6"><p><?php echo $success_message; ?></p></div><?php endif; ?>

                <form action="<?php echo SETTINGS_URL; ?>" method="POST" class="space-y-8">

                    <fieldset>
                        <legend class="text-xl font-bold text-white mb-4 border-b border-gray-700 pb-2"><?php echo htmlspecialchars($settings_data['settings_legend_main_identity']); ?></legend>
                        <div>
                            <label class="block text-gray-300 mb-2 font-semibold"><?php echo htmlspecialchars($settings_data['settings_label_site_name']); ?></label>
                            <input type="text" name="settings[site_title]" value="<?php echo htmlspecialchars($raw_settings_data['site_title'] ?? 'myZedora'); ?>" class="w-full bg-gray-700 rounded-lg py-2 px-4 text-white">
                            <p class="text-xs text-gray-500 mt-1"><?php echo htmlspecialchars($settings_data['settings_hint_site_name']); ?></p>
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
                        <legend class="text-xl font-bold text-white mb-4 border-b border-gray-700 pb-2"><?php echo htmlspecialchars($settings_data['settings_general_content_title']); ?></legend>
                        <div class="space-y-6">
                            <div><label class="block text-gray-300 mb-2 font-semibold"><?php echo htmlspecialchars($settings_data['settings_label_logo_text']); ?></label><input type="text" name="settings[logo_text]" value="<?php echo htmlspecialchars($raw_settings_data['logo_text'] ?? ''); ?>" class="w-full bg-gray-700 rounded-lg py-2 px-4 text-white"></div>
                            <div><label class="block text-gray-300 mb-2 font-semibold"><?php echo htmlspecialchars($settings_data['settings_label_hero_title']); ?></label><input type="text" name="settings[hero_title]" value="<?php echo htmlspecialchars($raw_settings_data['hero_title'] ?? ''); ?>" class="w-full bg-gray-700 rounded-lg py-2 px-4 text-white"></div>
                            <div><label class="block text-gray-300 mb-2 font-semibold"><?php echo htmlspecialchars($settings_data['settings_label_hero_subtitle']); ?></label><input type="text" name="settings[hero_subtitle_typed]" value="<?php echo htmlspecialchars($raw_settings_data['hero_subtitle_typed'] ?? ''); ?>" class="w-full bg-gray-700 rounded-lg py-2 px-4 text-white"></div>
                            
                            <div>
                                <label class="block text-gray-300 mb-2 font-semibold"><?php echo htmlspecialchars($settings_data['settings_label_hero_bg_url']); ?></label>
                                <input type="text" name="settings[hero_background_url]" value="<?php echo htmlspecialchars($raw_settings_data['hero_background_url'] ?? ''); ?>" class="w-full bg-gray-700 rounded-lg py-2 px-4 text-white">
                                <p class="text-xs text-gray-500 mt-1"><?php echo htmlspecialchars($settings_data['settings_hint_hero_bg_url']); ?></p>
                            </div>
                            
                            <div><label class="block text-gray-300 mb-2 font-semibold"><?php echo htmlspecialchars($settings_data['settings_label_about_p1']); ?></label><textarea name="settings[about_p1]" rows="4" class="w-full bg-gray-700 rounded-lg py-2 px-4 text-white"><?php echo htmlspecialchars($raw_settings_data['about_p1'] ?? ''); ?></textarea></div>
                            <div><label class="block text-gray-300 mb-2 font-semibold"><?php echo htmlspecialchars($settings_data['settings_label_about_p2']); ?></label><textarea name="settings[about_p2]" rows="4" class="w-full bg-gray-700 rounded-lg py-2 px-4 text-white"><?php echo htmlspecialchars($raw_settings_data['about_p2'] ?? ''); ?></textarea></div>
                            <div><label class="block text-gray-300 mb-2 font-semibold"><?php echo htmlspecialchars($settings_data['settings_label_contact_subtitle']); ?></label><input type="text" name="settings[contact_subtitle]" value="<?php echo htmlspecialchars($raw_settings_data['contact_subtitle'] ?? ''); ?>" class="w-full bg-gray-700 rounded-lg py-2 px-4 text-white"></div>
                            <div><label class="block text-gray-300 mb-2 font-semibold"><?php echo htmlspecialchars($settings_data['settings_label_contact_button']); ?></label><input type="text" name="settings[contact_button_text]" value="<?php echo htmlspecialchars($raw_settings_data['contact_button_text'] ?? ''); ?>" class="w-full bg-gray-700 rounded-lg py-2 px-4 text-white"></div>
                             <div><label class="block text-gray-300 mb-2 font-semibold"><?php echo htmlspecialchars($settings_data['settings_label_contact_email']); ?></label><input type="email" name="settings[contact_email]" value="<?php echo htmlspecialchars($raw_settings_data['contact_email'] ?? ''); ?>" class="w-full bg-gray-700 rounded-lg py-2 px-4 text-white"></div>
                            <div><label class="block text-gray-300 mb-2 font-semibold"><?php echo htmlspecialchars($settings_data['settings_label_footer_copyright']); ?></label><input type="text" name="settings[footer_copyright]" value="<?php echo htmlspecialchars($raw_settings_data['footer_copyright'] ?? ''); ?>" class="w-full bg-gray-700 rounded-lg py-2 px-4 text-white"></div>
                        </div>
                    </fieldset>

                    <fieldset>
                        <legend class="text-xl font-bold text-white mb-4 border-b border-gray-700 pb-2"><?php echo htmlspecialchars($settings_data['settings_seo_title_section']); ?></legend>
                        <div class="space-y-6">
                            <div><label class="block text-gray-300 mb-2 font-semibold"><?php echo htmlspecialchars($settings_data['settings_label_seo_title']); ?></label><input type="text" name="settings[seo_title]" value="<?php echo htmlspecialchars($raw_settings_data['seo_title'] ?? ''); ?>" class="w-full bg-gray-700 rounded-lg py-2 px-4 text-white"></div>
                            <div><label class="block text-gray-300 mb-2 font-semibold"><?php echo htmlspecialchars($settings_data['settings_label_meta_description']); ?></label><textarea name="settings[seo_description]" rows="3" class="w-full bg-gray-700 rounded-lg py-2 px-4 text-white"><?php echo htmlspecialchars($raw_settings_data['seo_description'] ?? ''); ?></textarea></div>
                            <div><label class="block text-gray-300 mb-2 font-semibold"><?php echo htmlspecialchars($settings_data['settings_label_meta_keywords']); ?></label><input type="text" name="settings[seo_keywords]" value="<?php echo htmlspecialchars($raw_settings_data['seo_keywords'] ?? ''); ?>" class="w-full bg-gray-700 rounded-lg py-2 px-4 text-white"></div>
                             <div><label class="block text-gray-300 mb-2 font-semibold"><?php echo htmlspecialchars($settings_data['settings_label_favicon_url']); ?></label><input type="text" name="settings[favicon_url]" value="<?php echo htmlspecialchars($raw_settings_data['favicon_url'] ?? ''); ?>" class="w-full bg-gray-700 rounded-lg py-2 px-4 text-white"><p class="text-xs text-gray-500 mt-1"><?php echo htmlspecialchars($settings_data['settings_hint_favicon_url']); ?></p></div>
                        </div>
                    </fieldset>
                    
                    <fieldset>
                        <legend class="text-xl font-bold text-white mb-4 border-b border-gray-700 pb-2"><?php echo htmlspecialchars($settings_data['settings_smtp_title']); ?></legend>
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

                    <button type="submit" class="w-full bg-sky-500 text-white font-semibold py-3 rounded-lg hover:bg-sky-600"><?php echo htmlspecialchars($settings_data['settings_save_all_btn']); ?></button>
                </form>
            </div>
        </div>
    </section>
</main>

<?php require_once FOOTER; ?>