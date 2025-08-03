<?php
require_once dirname(__DIR__) . '/includes/map.php';

if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: " . HOME_URL);
    exit();
}

$errors = [];
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and retrieve all POST data
    $buttons = $_POST['buttons'] ?? [];
    $about_title = trim($_POST['about_title'] ?? '');
    $about_content = trim($_POST['about_content'] ?? '');
    $hero_background_url = filter_var(trim($_POST['hero_background_url']), FILTER_SANITIZE_URL);
    $hero_title = trim($_POST['hero_title'] ?? '');
    $hero_subtitle_typed = trim($_POST['hero_subtitle_typed'] ?? '');
    $contact_subtitle = trim($_POST['contact_subtitle'] ?? '');
    $contact_button_text = trim($_POST['contact_button_text'] ?? '');
    $contact_email = filter_var(trim($_POST['contact_email']), FILTER_SANITIZE_EMAIL);
    
    try {
        $pdo->beginTransaction();

        // 1. Save Buttons
        $pdo->exec("DELETE FROM home_buttons");
        $stmt_buttons = $pdo->prepare(
            "INSERT INTO home_buttons (text, url, color, new_tab, display_order) VALUES (:text, :url, :color, :new_tab, :display_order)"
        );
        foreach ($buttons as $i => $button) {
            $text = trim($button['text']);
            $url = trim($button['url']);
            if (empty($text) || empty($url)) continue;
            $stmt_buttons->execute([
                ':text' => $text,
                ':url' => $url,
                ':color' => trim($button['color'] ?? '#38bdf8'),
                ':new_tab' => isset($button['new_tab']) ? 1 : 0,
                ':display_order' => $i
            ]);
        }

        // 2. Save Other Homepage Settings
        $settings_to_save = [
            'about_title' => $about_title,
            'about_content' => $about_content,
            'hero_background_url' => $hero_background_url,
            'hero_title' => $hero_title,
            'hero_subtitle_typed' => $hero_subtitle_typed,
            'contact_subtitle' => $contact_subtitle,
            'contact_button_text' => $contact_button_text,
            'contact_email' => $contact_email,
        ];
        $stmt_settings = $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        foreach ($settings_to_save as $key => $value) {
            $stmt_settings->execute([$key, $value]);
        }

        $pdo->commit();
        $success_message = $settings_data['button_update_success'];

        // 3. Re-fetch the main settings_data for the template (header/footer)
        $settings_stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings");
        $settings_data = $settings_stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        if (isset($settings_data['site_title'])) {
            $site_title_val = $settings_data['site_title'];
            foreach ($settings_data as $key => $value) {
                if (is_string($value)) {
                    $settings_data[$key] = str_replace('{{site_title}}', $site_title_val, $value);
                }
            }
        }

    } catch (Exception $e) {
        $pdo->rollBack();
        $errors[] = $settings_data['button_update_fail'] . " " . $e->getMessage();
    }
}


$stmt = $pdo->query("SELECT * FROM home_buttons ORDER BY display_order ASC");
$home_buttons = $stmt->fetchAll();

// Fetch a fresh, RAW copy of settings specifically for the form inputs
$raw_stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings");
$raw_settings_data = $raw_stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$page_title = $settings_data['manage_home_title'] . " - " . $settings_data['seo_title'];
$use_tinymce = ($settings_data['enable_tinymce'] ?? '0') === '1' && !empty($settings_data['tinymce_api_key']);

require_once HEADER;
?>

<?php if ($use_tinymce): ?>
<script src="https://cdn.tiny.cloud/1/<?php echo htmlspecialchars($settings_data['tinymce_api_key']); ?>/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<script>
  tinymce.init({
    selector: 'textarea.tinymce-editor',
    plugins: 'code image link lists preview wordcount',
    toolbar: 'undo redo | blocks | bold italic underline | forecolor backcolor | bullist numlist | image link | preview code',
    skin: 'oxide-dark',
    content_css: 'dark',
    height: 350,
    menubar: false,
  });
</script>
<?php endif; ?>

<style>
    .tab-button { padding: 0.75rem 1.5rem; cursor: pointer; background-color: #374151; color: #d1d5db; border-radius: 0.5rem; font-weight: 600; transition: background-color 0.2s ease-in-out, color 0.2s ease-in-out; }
    .tab-button:hover { background-color: #4b5563; }
    .tab-button.active { background-color: #0ea5e9; color: #ffffff; }
</style>

<main class="pt-32 pb-20">
    <section id="manage-home" data-aos="fade-up">
        <div class="container mx-auto px-6 max-w-4xl">
            <h1 class="text-4xl font-bold text-center mb-4 section-title"><?php echo htmlspecialchars($settings_data['manage_home_title']); ?></h1>
            <p class="text-center text-gray-400 mb-10"><?php echo htmlspecialchars($settings_data['manage_home_subtitle']); ?></p>

            <div class="bg-gray-800/50 p-8 rounded-lg shadow-lg" x-data="{ tab: 'hero' }">

                <div class="flex flex-wrap items-center justify-center gap-4 mb-8 border-b border-gray-700 pb-4">
                    <button type="button" @click="tab = 'hero'" :class="{ 'active': tab === 'hero' }" class="tab-button"><?php echo htmlspecialchars($settings_data['hero_section_title']); ?></button>
                    <button type="button" @click="tab = 'buttons'" :class="{ 'active': tab === 'buttons' }" class="tab-button"><?php echo htmlspecialchars($settings_data['home_buttons_title']); ?></button>
                    <button type="button" @click="tab = 'about'" :class="{ 'active': tab === 'about' }" class="tab-button"><?php echo htmlspecialchars($settings_data['about_section_title']); ?></button>
                    <button type="button" @click="tab = 'contact'" :class="{ 'active': tab === 'contact' }" class="tab-button"><?php echo htmlspecialchars($settings_data['contact_section_title']); ?></button>
                </div>

                <form action="<?php echo MANAGE_HOME_URL; ?>" method="POST">
                    
                    <?php if (!empty($errors)): ?><div class="bg-red-500/20 text-red-300 p-4 rounded-lg mb-6"><?php foreach ($errors as $error): ?><p><?php echo $error; ?></p><?php endforeach; ?></div><?php endif; ?>
                    <?php if ($success_message): ?><div class="bg-green-500/20 text-green-300 p-4 rounded-lg mb-6"><p><?php echo $success_message; ?></p></div><?php endif; ?>

                    <div x-show="tab === 'hero'" style="display: none;">
                        <fieldset>
                            <legend class="sr-only"><?php echo htmlspecialchars($settings_data['hero_section_title']); ?></legend>
                            <div class="space-y-4">
                                <div>
                                    <label for="hero_title" class="block text-gray-300 mb-2"><?php echo htmlspecialchars($settings_data['hero_title_label']); ?></label>
                                    <input type="text" id="hero_title" name="hero_title" value="<?php echo htmlspecialchars($raw_settings_data['hero_title'] ?? ''); ?>" class="w-full bg-gray-700 rounded-lg py-2 px-4 text-white">
                                </div>
                                 <div>
                                    <label for="hero_subtitle_typed" class="block text-gray-300 mb-2"><?php echo htmlspecialchars($settings_data['hero_subtitle_label']); ?></label>
                                    <input type="text" id="hero_subtitle_typed" name="hero_subtitle_typed" value="<?php echo htmlspecialchars($raw_settings_data['hero_subtitle_typed'] ?? ''); ?>" class="w-full bg-gray-700 rounded-lg py-2 px-4 text-white">
                                </div>
                                <div>
                                    <label for="hero_background_url" class="block text-gray-300 mb-2"><?php echo htmlspecialchars($settings_data['hero_bg_url_label']); ?></label>
                                    <input type="text" id="hero_background_url" name="hero_background_url" value="<?php echo htmlspecialchars($raw_settings_data['hero_background_url'] ?? ''); ?>" class="w-full bg-gray-700 rounded-lg py-2 px-4 text-white">
                                    <p class="text-xs text-gray-500 mt-1"><?php echo htmlspecialchars($settings_data['hero_bg_url_hint']); ?></p>
                                </div>
                            </div>
                        </fieldset>
                    </div>

                    <div x-show="tab === 'buttons'" style="display: none;">
                        <fieldset>
                            <legend class="sr-only"><?php echo htmlspecialchars($settings_data['home_buttons_title']); ?></legend>
                            <div id="buttons-container" class="space-y-6">
                                <?php foreach ($home_buttons as $index => $button): ?>
                                    <div class="button-item bg-gray-700/50 p-4 rounded-lg">
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-gray-300 mb-2 text-sm"><?php echo htmlspecialchars($settings_data['btn_text_label']); ?></label>
                                                <input type="text" name="buttons[<?php echo $index; ?>][text]" value="<?php echo htmlspecialchars($button['text']); ?>" class="w-full bg-gray-700 rounded-lg py-2 px-4 text-white">
                                            </div>
                                            <div>
                                                <label class="block text-gray-300 mb-2 text-sm"><?php echo htmlspecialchars($settings_data['btn_url_label']); ?></label>
                                                <input type="text" name="buttons[<?php echo $index; ?>][url]" value="<?php echo htmlspecialchars($button['url']); ?>" class="w-full bg-gray-700 rounded-lg py-2 px-4 text-white">
                                            </div>
                                        </div>
                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4 items-end">
                                            <div>
                                                <label class="block text-gray-300 mb-2 text-sm"><?php echo htmlspecialchars($settings_data['btn_color_label']); ?></label>
                                                <input type="color" name="buttons[<?php echo $index; ?>][color]" value="<?php echo htmlspecialchars($button['color']); ?>" class="w-16 h-10 bg-gray-700 rounded-lg p-1">
                                            </div>
                                            <div class="flex items-center h-10">
                                                <input type="checkbox" name="buttons[<?php echo $index; ?>][new_tab]" value="1" class="form-checkbox h-5 w-5 text-sky-500" <?php if ($button['new_tab']) echo 'checked'; ?>>
                                                <label class="ml-2 text-gray-300 text-sm"><?php echo htmlspecialchars($settings_data['btn_new_tab_label']); ?></label>
                                            </div>
                                            <div class="text-right">
                                                 <button type="button" class="remove-button-btn text-red-400 hover:text-red-300 font-semibold py-2 px-4 rounded-lg">Remove</button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="mt-6">
                                <button type="button" id="add-button-btn" class="bg-blue-600 text-white font-semibold py-2 px-4 rounded-lg hover:bg-blue-700">+ <?php echo htmlspecialchars($settings_data['add_button_btn']); ?></button>
                            </div>
                        </fieldset>
                    </div>

                    <div x-show="tab === 'about'" style="display: none;">
                        <fieldset>
                            <legend class="sr-only"><?php echo htmlspecialchars($settings_data['about_section_title']); ?></legend>
                            <div class="space-y-4">
                                <div>
                                    <label for="about_title" class="block text-gray-300 mb-2"><?php echo htmlspecialchars($settings_data['about_title_label']); ?></label>
                                    <input type="text" id="about_title" name="about_title" value="<?php echo htmlspecialchars($raw_settings_data['about_title'] ?? ''); ?>" class="w-full bg-gray-700 rounded-lg py-2 px-4 text-white">
                                </div>
                                <div>
                                    <label for="about_content" class="block text-gray-300 mb-2"><?php echo htmlspecialchars($settings_data['about_content_label']); ?></label>
                                    <?php
                                        $editor_class = $use_tinymce ? 'tinymce-editor' : 'w-full bg-gray-900 border border-gray-600 rounded-lg py-2 px-4 text-white font-mono text-sm';
                                    ?>
                                    <textarea id="about_content" name="about_content" class="<?php echo $editor_class; ?>" <?php if (!$use_tinymce) echo 'style="height: 350px;"'; ?>><?php echo $raw_settings_data['about_content'] ?? ''; ?></textarea>
                                </div>
                            </div>
                        </fieldset>
                    </div>
                    
                    <div x-show="tab === 'contact'" style="display: none;">
                        <fieldset>
                            <legend class="sr-only"><?php echo htmlspecialchars($settings_data['contact_section_title']); ?></legend>
                            <div class="space-y-4">
                                 <div>
                                    <label for="contact_subtitle" class="block text-gray-300 mb-2"><?php echo htmlspecialchars($settings_data['contact_subtitle_label']); ?></label>
                                    <input type="text" id="contact_subtitle" name="contact_subtitle" value="<?php echo htmlspecialchars($raw_settings_data['contact_subtitle'] ?? ''); ?>" class="w-full bg-gray-700 rounded-lg py-2 px-4 text-white">
                                </div>
                                 <div>
                                    <label for="contact_button_text" class="block text-gray-300 mb-2"><?php echo htmlspecialchars($settings_data['contact_button_label']); ?></label>
                                    <input type="text" id="contact_button_text" name="contact_button_text" value="<?php echo htmlspecialchars($raw_settings_data['contact_button_text'] ?? ''); ?>" class="w-full bg-gray-700 rounded-lg py-2 px-4 text-white">
                                </div>
                                <div>
                                    <label for="contact_email" class="block text-gray-300 mb-2"><?php echo htmlspecialchars($settings_data['contact_email_label']); ?></label>
                                    <input type="email" id="contact_email" name="contact_email" value="<?php echo htmlspecialchars($raw_settings_data['contact_email'] ?? ''); ?>" class="w-full bg-gray-700 rounded-lg py-2 px-4 text-white">
                                </div>
                            </div>
                        </fieldset>
                    </div>

                    <div class="mt-8">
                        <button type="submit" class="w-full bg-sky-500 text-white font-semibold py-3 rounded-lg hover:bg-sky-600"><?php echo htmlspecialchars($settings_data['save_changes_btn']); ?></button>
                    </div>
                </form>
            </div>
        </div>
    </section>
</main>

<div id="button-template" style="display: none;">
    <div class="button-item bg-gray-700/50 p-4 rounded-lg">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-gray-300 mb-2 text-sm"><?php echo htmlspecialchars($settings_data['btn_text_label']); ?></label>
                <input type="text" name="buttons[__INDEX__][text]" class="w-full bg-gray-700 rounded-lg py-2 px-4 text-white">
            </div>
            <div>
                <label class="block text-gray-300 mb-2 text-sm"><?php echo htmlspecialchars($settings_data['btn_url_label']); ?></label>
                <input type="text" name="buttons[__INDEX__][url]" class="w-full bg-gray-700 rounded-lg py-2 px-4 text-white">
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4 items-end">
            <div>
                <label class="block text-gray-300 mb-2 text-sm"><?php echo htmlspecialchars($settings_data['btn_color_label']); ?></label>
                <input type="color" name="buttons[__INDEX__][color]" value="#38bdf8" class="w-16 h-10 bg-gray-700 rounded-lg p-1">
            </div>
            <div class="flex items-center h-10">
                <input type="checkbox" name="buttons[__INDEX__][new_tab]" value="1" class="form-checkbox h-5 w-5 text-sky-500">
                <label class="ml-2 text-gray-300 text-sm"><?php echo htmlspecialchars($settings_data['btn_new_tab_label']); ?></label>
            </div>
            <div class="text-right">
                    <button type="button" class="remove-button-btn text-red-400 hover:text-red-300 font-semibold py-2 px-4 rounded-lg">Remove</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('buttons-container');
    const addButton = document.getElementById('add-button-btn');
    const template = document.getElementById('button-template');

    let buttonIndex = <?php echo count($home_buttons); ?>;

    addButton.addEventListener('click', function() {
        const newButtonHTML = template.innerHTML.replace(/__INDEX__/g, buttonIndex);
        const newButtonDiv = document.createElement('div');
        newButtonDiv.innerHTML = newButtonHTML;
        container.appendChild(newButtonDiv.firstElementChild);
        buttonIndex++;
    });

    container.addEventListener('click', function(e) {
        if (e.target && e.target.classList.contains('remove-button-btn')) {
            e.target.closest('.button-item').remove();
        }
    });
});
</script>

<?php require_once FOOTER; ?>