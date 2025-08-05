<?php
$active_style_stmt = $pdo->query("SELECT css_code FROM custom_styles WHERE is_active = 1 LIMIT 1");
$active_style = $active_style_stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($settings_data['site_language'] ?? 'en'); ?>" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <title><?php
    if (isset($page_title)) {
        echo $page_title;
    } else {
        echo htmlspecialchars($settings_data['seo_title']);
    }
?></title>
    <meta name="description" content="<?php echo htmlspecialchars($settings_data['seo_description']); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($settings_data['seo_keywords']); ?>">
    <link rel="icon" href="<?php echo htmlspecialchars($settings_data['favicon_url']); ?>" type="image/x-icon">
    <link rel="shortcut icon" href="<?php echo htmlspecialchars($settings_data['favicon_url']); ?>" type="image/x-icon">

    <script src="https://cdn.tailwindcss.com"></script>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <style>
        @keyframes background-pan {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(270deg, #0f172a, #111827, #0f172a);
            background-size: 400% 400%;
            animation: background-pan 15s ease infinite;
            color: #F9FAFB;
        }

        .nav-link {
            position: relative;
            transition: color 0.3s ease;
        }
        .nav-link:hover {
            color: #38bdf8;
        }
        .nav-link::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: -4px;
            left: 50%;
            transform: translateX(-50%);
            background-color: #38bdf8;
            transition: width 0.3s ease;
        }
        .nav-link:hover::after,
        .nav-link.active::after {
            width: 100%;
        }
        .nav-link.active {
            color: #38bdf8;
        }

        .card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 25px -5px rgba(56, 189, 248, 0.15), 0 10px 10px -5px rgba(56, 189, 248, 0.1);
        }
        .card .card-image-wrapper {
            overflow: hidden;
        }
        .card:hover img {
            transform: scale(1.05);
        }
        .card img {
            transition: transform 0.4s ease-in-out;
        }

        .section-title {
            background: -webkit-linear-gradient(45deg, #38bdf8, #a78bfa);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
    </style>
    <?php if ($active_style): ?>
    <style id="myzedora-custom-styles">
        <?php echo $active_style; ?>
    </style>
    <?php endif; ?>
    <?php do_action('app_head'); ?>
</head>
<body class="antialiased">
<?php do_action('app_body_start'); ?>
    <header id="header" class="bg-gray-900/70 backdrop-blur-lg fixed top-0 left-0 right-0 z-50 border-b border-gray-800">
        <div class="container mx-auto px-6 py-4">
            <div class="flex items-center justify-between">
                <a href="<?php echo HOME_URL; ?>#home" class="text-2xl font-bold text-white tracking-wider"><?php echo htmlspecialchars($settings_data['logo_text']); ?></a>
                <nav id="desktop-nav" class="hidden md:flex items-center space-x-8">
                    <?php 
                    if (function_exists('has_action') && has_action('render_desktop_menu')) {
                        do_action('render_desktop_menu');
                    } else {

                        echo '<a href="' . HOME_URL . '#about" class="nav-link text-gray-300">' . htmlspecialchars($settings_data['menu_about']) . '</a>';
                        echo '<a href="' . HOME_URL . '#portfolio" class="nav-link text-gray-300">' . htmlspecialchars($settings_data['menu_portfolio']) . '</a>';
                        echo '<a href="' . HOME_URL . '#blog" class="nav-link text-gray-300">' . htmlspecialchars($settings_data['menu_blog']) . '</a>';
                        echo '<a href="' . HOME_URL . '#contact" class="nav-link text-gray-300">' . htmlspecialchars($settings_data['menu_contact']) . '</a>';

                        $plugin_manifests = glob(PROJECT_ROOT . '/ext/*/plugin.json');
                        foreach ($plugin_manifests as $manifest_file) {
                            $plugin_folder_name = basename(dirname($manifest_file));
                            if ($plugin_folder_name === 'menu_manager') continue;
                            $enabled_key = 'plugin_' . $plugin_folder_name . '_enabled';

                            if (($settings_data[$enabled_key] ?? '0') === '1') {
                                $manifest_data = json_decode(file_get_contents($manifest_file), true);
                                if (isset($manifest_data['pages']) && is_array($manifest_data['pages'])) {
                                    foreach ($manifest_data['pages'] as $page) {

                                        if (isset($page['admin_only']) && $page['admin_only']) {
                                            continue;
                                        }
                                        if (isset($page['menu_title'], $page['slug'])) {
                                            if ($use_pretty_urls) {
                                                $page_url = HOME_URL . '' . $plugin_folder_name . '/' . $page['slug'];
                                            } else {
                                                $page_url = HOME_URL . 'view/page.php/' . $plugin_folder_name . '/' . $page['slug'];
                                            }
                                            echo '<a href="' . htmlspecialchars($page_url) . '" class="nav-link text-gray-300">' . htmlspecialchars($page['menu_title']) . '</a>';
                                        }
                                    }
                                }
                            }
                        }
                    }
                    ?>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <?php
                            $visibility = $settings_data['members_page_visibility'] ?? 'members';
                            if ($visibility === 'everyone' || $visibility === 'members' || ($visibility === 'admins' && $_SESSION['is_admin'])) {
                                echo '<a href="' . MEMBERS_PLIST_URL . '" class="nav-link text-gray-300">' . htmlspecialchars($settings_data['menu_members']) . '</a>';
                            }
                        ?>
                        <a href="<?php echo PROFILE_URL_BASE . urlencode($_SESSION['username']); ?>" class="nav-link text-gray-300"><?php echo htmlspecialchars($settings_data['menu_profile']); ?></a>
                        <?php if ($_SESSION['is_admin']): ?>
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open" class="nav-link text-gray-300 inline-flex items-center">
                                <?php echo htmlspecialchars($settings_data['menu_admin']); ?> <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </button>
                            <div x-show="open" @click.away="open = false" class="absolute right-0 mt-2 w-48 bg-gray-800 rounded-md shadow-lg py-1 z-20" style="display: none;">
                                <a href="<?php echo MANAGE_URL; ?>" class="block px-4 py-2 text-sm text-gray-300 hover:bg-sky-500 hover:text-white"><?php echo htmlspecialchars($settings_data['menu_admin_dashboard']); ?></a>
                                <a href="<?php echo MANAGE_PLUGINS_URL; ?>" class="block px-4 py-2 text-sm text-gray-300 hover:bg-sky-500 hover:text-white"><?php echo htmlspecialchars($settings_data['menu_plugin_settings']); ?></a>
                                <?php

                                    $plugin_manifests = glob(PROJECT_ROOT . '/ext/*/plugin.json');
                                    foreach ($plugin_manifests as $manifest_file) {
                                        $plugin_folder_name = basename(dirname($manifest_file));
                                        $enabled_key = 'plugin_' . $plugin_folder_name . '_enabled';

                                        if (($settings_data[$enabled_key] ?? '0') === '1') {
                                            $manifest_data = json_decode(file_get_contents($manifest_file), true);
                                            if (isset($manifest_data['pages']) && is_array($manifest_data['pages'])) {
                                                foreach ($manifest_data['pages'] as $page) {

                                                    if (isset($page['admin_only']) && $page['admin_only'] && isset($page['menu_title'], $page['slug'])) {
                                                        if ($use_pretty_urls) {
                                                            $page_url = HOME_URL . '' . $plugin_folder_name . '/' . $page['slug'];
                                                        } else {
                                                            $page_url = HOME_URL . 'view/page.php/' . $plugin_folder_name . '/' . $page['slug'];
                                                        }
                                                        echo '<a href="' . htmlspecialchars($page_url) . '" class="block px-4 py-2 text-sm text-gray-300 hover:bg-sky-500 hover:text-white">' . htmlspecialchars($page['menu_title']) . '</a>';
                                                    }
                                                }
                                            }
                                        }
                                    }
                                ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        <a href="<?php echo LOGOUT_URL; ?>" class="nav-link text-gray-300"><?php echo htmlspecialchars($settings_data['menu_logout']); ?></a>
                    <?php else: ?>
                        <?php
                            $visibility = $settings_data['members_page_visibility'] ?? 'members';
                            if ($visibility === 'everyone') {
                                echo '<a href="' . MEMBERS_PLIST_URL . '" class="nav-link text-gray-300">' . htmlspecialchars($settings_data['menu_members']) . '</a>';
                            }
                        ?>
                        <a href="<?php echo LOGIN_URL; ?>" class="nav-link text-gray-300"><?php echo htmlspecialchars($settings_data['menu_login']); ?></a>
                        <?php if (($settings_data['registration_mode'] ?? '0') != '2'): ?>
                            <a href="<?php echo REGISTER_URL; ?>" class="bg-sky-500 text-white font-semibold px-4 py-2 rounded-lg hover:bg-sky-600 transition-colors duration-300"><?php echo htmlspecialchars($settings_data['menu_register']); ?></a>
                        <?php endif; ?>
                    <?php endif; ?>
                </nav>
                <button id="mobile-menu-button" class="md:hidden text-gray-300 focus:outline-none">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path></svg>
                </button>
            </div>
        </div>
        <div id="mobile-menu" class="hidden md:hidden px-6 pb-4">
            <?php 
            if (function_exists('has_action') && has_action('render_mobile_menu')) {
                do_action('render_mobile_menu');
            } else {

                echo '<a href="' . HOME_URL . '#about" class="block py-2 text-gray-300">' . htmlspecialchars($settings_data['menu_about']) . '</a>';
                echo '<a href="' . HOME_URL . '#portfolio" class="block py-2 text-gray-300">' . htmlspecialchars($settings_data['menu_portfolio']) . '</a>';
                echo '<a href="' . HOME_URL . '#blog" class="block py-2 text-gray-300">' . htmlspecialchars($settings_data['menu_blog']) . '</a>';
                echo '<a href="' . HOME_URL . '#contact" class="block py-2 text-gray-300">' . htmlspecialchars($settings_data['menu_contact']) . '</a>';

                $plugin_manifests = glob(PROJECT_ROOT . '/ext/*/plugin.json');
                foreach ($plugin_manifests as $manifest_file) {
                    $plugin_folder_name = basename(dirname($manifest_file));
                    if ($plugin_folder_name === 'menu_manager') continue;
                    $enabled_key = 'plugin_' . $plugin_folder_name . '_enabled';

                    if (($settings_data[$enabled_key] ?? '0') === '1') {
                        $manifest_data = json_decode(file_get_contents($manifest_file), true);
                        if (isset($manifest_data['pages']) && is_array($manifest_data['pages'])) {
                            foreach ($manifest_data['pages'] as $page) {

                                if (isset($page['admin_only']) && $page['admin_only']) {
                                    continue;
                                }
                                if (isset($page['menu_title'], $page['slug'])) {
                                    if ($use_pretty_urls) {
                                        $page_url = HOME_URL . '' . $plugin_folder_name . '/' . $page['slug'];
                                    } else {
                                        $page_url = HOME_URL . 'page.php/' . $plugin_folder_name . '/' . $page['slug'];
                                    }
                                    echo '<a href="' . htmlspecialchars($page_url) . '" class="block py-2 text-gray-300">' . htmlspecialchars($page['menu_title']) . '</a>';
                                }
                            }
                        }
                    }
                }
            }
            ?>
            <hr class="my-2 border-gray-700">
            <?php if (isset($_SESSION['user_id'])): ?>
                <?php
                    $visibility = $settings_data['members_page_visibility'] ?? 'members';
                    if ($visibility === 'everyone' || $visibility === 'members' || ($visibility === 'admins' && $_SESSION['is_admin'])) {
                        echo '<a href="' . MEMBERS_PLIST_URL . '" class="block py-2 text-gray-300">' . htmlspecialchars($settings_data['menu_members']) . '</a>';
                    }
                ?>
                <a href="<?php echo PROFILE_URL_BASE . urlencode($_SESSION['username']); ?>" class="block py-2 text-gray-300"><?php echo htmlspecialchars($settings_data['menu_profile']); ?></a>
                <?php if ($_SESSION['is_admin']): ?>
                <a href="<?php echo MANAGE_URL; ?>" class="block py-2 text-gray-300"><?php echo htmlspecialchars($settings_data['menu_admin_dashboard']); ?></a>
                <a href="<?php echo MANAGE_PLUGINS_URL; ?>" class="block py-2 text-gray-300"><?php echo htmlspecialchars($settings_data['menu_plugin_settings']); ?></a>
                <?php
                
                    $plugin_manifests = glob(PROJECT_ROOT . '/ext/*/plugin.json');
                    foreach ($plugin_manifests as $manifest_file) {
                        $plugin_folder_name = basename(dirname($manifest_file));
                        $enabled_key = 'plugin_' . $plugin_folder_name . '_enabled';
                        if (($settings_data[$enabled_key] ?? '0') === '1') {
                            $manifest_data = json_decode(file_get_contents($manifest_file), true);
                            if (isset($manifest_data['pages']) && is_array($manifest_data['pages'])) {
                                foreach ($manifest_data['pages'] as $page) {
                                    if (isset($page['admin_only']) && $page['admin_only'] && isset($page['menu_title'], $page['slug'])) {
                                        if ($use_pretty_urls) {
                                            $page_url = HOME_URL . '' . $plugin_folder_name . '/' . $page['slug'];
                                        } else {
                                            $page_url = HOME_URL . 'page.php/' . $plugin_folder_name . '/' . $page['slug'];
                                        }
                                        echo '<a href="' . htmlspecialchars($page_url) . '" class="block px-4 py-2 text-sm text-gray-300 hover:bg-sky-500 hover:text-white">' . htmlspecialchars($page['menu_title']) . '</a>';
                                    }
                                }
                            }
                        }
                    }
                ?>
                <hr class="my-2 border-gray-700">
                <?php endif; ?>
                <a href="<?php echo LOGOUT_URL; ?>" class="block py-2 text-gray-300"><?php echo htmlspecialchars($settings_data['menu_logout']); ?></a>
            <?php else: ?>
                <a href="<?php echo LOGIN_URL; ?>" class="block py-2 text-gray-300"><?php echo htmlspecialchars($settings_data['menu_login']); ?></a>
                <?php if (($settings_data['registration_mode'] ?? '0') != '2'): ?>
                    <a href="<?php echo REGISTER_URL; ?>" class="block py-2 text-gray-300"><?php echo htmlspecialchars($settings_data['menu_register']); ?></a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </header>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
</body>
</html>