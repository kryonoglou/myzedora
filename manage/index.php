<?php
require_once dirname(__DIR__) . '/includes/map.php';

if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: " . HOME_URL);
    exit();
}

$page_title = $settings_data['manage_dashboard_title'] . " - " . $settings_data['seo_title'];

require_once HEADER;
?>
<script>
    if (history.scrollRestoration) {
        history.scrollRestoration = 'manual';
    }
    window.scrollTo(0, 0);
</script>
<?php

function create_management_card($url, $icon_svg, $title, $description) {
    return <<<HTML
    <a href="{$url}" class="card block bg-gray-800/50 p-8 rounded-lg shadow-lg hover:bg-gray-700/60 transition-all duration-300 ease-in-out transform hover:-translate-y-1">
        <div class="flex items-center justify-center h-16 w-16 rounded-full bg-sky-500/20 mb-6">
            {$icon_svg}
        </div>
        <h3 class="text-2xl font-bold text-white mb-3">{$title}</h3>
        <p class="text-gray-400">{$description}</p>
    </a>
HTML;
}

$content_icon = '<svg class="w-8 h-8 text-sky-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>';
$home_icon = '<svg class="w-8 h-8 text-sky-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h7.5" /></svg>';
$members_icon = '<svg class="w-8 h-8 text-sky-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="10" r="3" /><path d="M4 20c1.5-3 5-5 8-5s6.5 2 8 5" stroke-linecap="round" stroke-linejoin="round"/></svg>';
$gallery_icon = '<svg class="w-8 h-8 text-sky-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" /></svg>';
$announcements_icon = '<svg class="w-8 h-8 text-sky-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" /></svg>';
$styles_icon = '<svg class="w-8 h-8 text-sky-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.53 16.122a3 3 0 00-5.78 1.128 2.25 2.25 0 01-2.4 2.245 4.5 4.5 0 008.4-2.245c0-.399-.078-.78-.22-1.128zm0 0a15.998 15.998 0 003.388-1.62m-5.043-.025a15.998 15.998 0 011.622-3.385m5.043.025a15.998 15.998 0 001.622-3.385m3.388 1.62a15.998 15.998 0 00-1.622-3.385m-5.043.025a15.998 15.998 0 01-3.388-1.621m-5.043.025a15.998 15.998 0 00-3.388 1.622m-1.622 3.385a15.998 15.998 0 001.622 3.385m5.043-.025a15.998 15.998 0 013.388 1.622m5.043-.025a15.998 15.998 0 003.388-1.622m-5.043.025a15.998 15.998 0 01-1.622-3.385" /></svg>';
$library_icon = '<svg class="w-8 h-8 text-sky-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0012 9.75c-2.551 0-5.056.2-7.5.582V21M3 21h18M12 6.75h.008v.008H12V6.75z" /></svg>';
$settings_icon = '<svg class="w-8 h-8 text-sky-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M10 6v12M4 12h16M14 12v6M4 18h16" /></svg>';

?>

<main class="pt-32 pb-20">
    <section id="manage-dashboard" data-aos="fade-up">
        <div class="container mx-auto px-6">
            <div class="text-center mb-12">
                <h1 class="text-4xl md:text-5xl font-bold section-title mb-4"><?php echo htmlspecialchars($settings_data['manage_dashboard_title']); ?></h1>
                <p class="text-lg text-gray-400 max-w-2xl mx-auto"><?php echo htmlspecialchars($settings_data['manage_dashboard_subtitle']); ?></p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 max-w-6xl mx-auto">
                <?php echo create_management_card(MANAGE_CONTENT_URL, $content_icon, htmlspecialchars($settings_data['card_content_title']), htmlspecialchars($settings_data['card_content_desc'])); ?>
                <?php echo create_management_card(MANAGE_HOME_URL, $home_icon, htmlspecialchars($settings_data['card_home_title']), htmlspecialchars($settings_data['card_home_desc'])); ?>
                <?php echo create_management_card(MEMBERS_LIST_URL, $members_icon, htmlspecialchars($settings_data['card_members_title']), htmlspecialchars($settings_data['card_members_desc'])); ?>
                <?php echo create_management_card(GALLERY_URL, $gallery_icon, htmlspecialchars($settings_data['card_gallery_title']), htmlspecialchars($settings_data['card_gallery_desc'])); ?>
                <?php echo create_management_card(SEND_ANNOUNCEMENTS_URL, $announcements_icon, htmlspecialchars($settings_data['card_announcements_title']), htmlspecialchars($settings_data['card_announcements_desc'])); ?>
                <?php echo create_management_card(STYLES_URL, $styles_icon, htmlspecialchars($settings_data['card_styles_title']), htmlspecialchars($settings_data['card_styles_desc'])); ?>
                <?php echo create_management_card(LIBRARY_URL, $library_icon, htmlspecialchars($settings_data['card_library_title']), htmlspecialchars($settings_data['card_library_desc'])); ?>
                <?php echo create_management_card(SETTINGS_URL, $settings_icon, htmlspecialchars($settings_data['card_settings_title']), htmlspecialchars($settings_data['card_settings_desc'])); ?>
            </div>
        </div>
    </section>
</main>

<?php require_once FOOTER; ?>