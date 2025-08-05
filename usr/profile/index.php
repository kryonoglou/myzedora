<?php
require_once dirname(__DIR__, 2) . '/includes/map.php';

$user_to_display = null;
$show_404 = false;

if (isset($_GET['username']) && !empty(trim($_GET['username']))) {
    $username_to_display = trim($_GET['username']);
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username_to_display]);
    $user_to_display = $stmt->fetch();

    if (!$user_to_display) {
        $show_404 = true;
    }
} else {
    $show_404 = true;
}

if ($user_to_display) {
    $is_own_profile = (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $user_to_display['id']);
    $rank = $user_to_display['is_admin'] ? htmlspecialchars($settings_data['role_administrator']) : htmlspecialchars($settings_data['role_member']);
    $join_date = date("F j, Y", strtotime($user_to_display['created_at']));
    $profile_image_url = !empty($user_to_display['profile_image_url'])
        ? $user_to_display['profile_image_url']
        : 'https://placehold.co/128x128/1f2937/38bdf8?text=' . strtoupper(substr($user_to_display['username'], 0, 2));

    $page_title = $user_to_display['full_name'] . " (@" . $user_to_display['username'] . ") - " . $settings_data['seo_title'];
}

require_once HEADER;
?>

<main class="pt-32 pb-20 bg-gray-900/30">
    <?php if ($show_404): ?>
        <?php require_once NOT_FOUND_PAGE; ?>
    <?php else: ?>
        <section id="profile" data-aos="fade-up">
            <div class="container mx-auto px-6">
                <div class="max-w-4xl mx-auto bg-gray-800/50 rounded-2xl shadow-lg overflow-hidden">
                    <div class="p-8">
                        <div class="flex flex-col md:flex-row items-center gap-8">
                            <div class="flex-shrink-0">
                                <img class="h-32 w-32 rounded-full object-cover border-4 border-sky-500" src="<?php echo htmlspecialchars($profile_image_url); ?>" alt="Profile image of <?php echo htmlspecialchars($user_to_display['full_name']); ?>">
                            </div>
                            <div class="text-center md:text-left">
                                <h1 class="text-3xl font-bold text-white"><?php echo htmlspecialchars($user_to_display['full_name']); ?></h1>
                                <p class="text-sky-400 font-semibold text-lg"><?php echo htmlspecialchars($rank); ?></p>
                                <p class="text-gray-400 mt-1">@<?php echo htmlspecialchars($user_to_display['username']); ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-900/30 px-8 py-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <h3 class="text-gray-400 font-semibold text-sm uppercase tracking-wider"><?php echo htmlspecialchars($settings_data['profile_join_date']); ?></h3>
                                <p class="text-white text-lg mt-1"><?php echo $join_date; ?></p>
                            </div>
                            <?php if (isset($_SESSION['is_admin']) && ($_SESSION['is_admin'] || $is_own_profile)): ?>
                            <div>
                                <h3 class="text-gray-400 font-semibold text-sm uppercase tracking-wider"><?php echo htmlspecialchars($settings_data['profile_email']); ?></h3>
                                <p class="text-white text-lg mt-1"><?php echo htmlspecialchars($user_to_display['email']); ?></p>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="mt-6">
                            <h3 class="text-gray-400 font-semibold text-sm uppercase tracking-wider"><?php echo htmlspecialchars($settings_data['profile_bio']); ?></h3>
                            <p class="text-white text-lg mt-1 leading-relaxed">
                                <?php echo !empty($user_to_display['bio']) ? htmlspecialchars($user_to_display['bio']) : htmlspecialchars($settings_data['profile_no_bio']); ?>
                            </p>
                        </div>
                    </div>
                    <?php if ($is_own_profile): ?>
                    <div class="p-8 text-right flex flex-col sm:flex-row justify-end items-center gap-4">
                         <a href="<?php echo EDIT_PROFILE_URL; ?>" class="bg-sky-500 text-white font-semibold px-6 py-2 rounded-lg hover:bg-sky-600 transition-colors duration-300 w-full sm:w-auto text-center"><?php echo htmlspecialchars($settings_data['profile_edit_btn']); ?></a>
                         <a href="<?php echo CHANGE_PASSWORD_URL; ?>" class="bg-blue-500 text-white font-semibold px-6 py-2 rounded-lg hover:bg-blue-600 transition-colors duration-300 w-full sm:w-auto text-center"><?php echo htmlspecialchars($settings_data['change_password_btn']); ?></a>
                         <a href="<?php echo ERASE_ACCOUNT_URL; ?>" class="bg-red-500 text-white font-semibold px-6 py-2 rounded-lg hover:bg-red-600 transition-colors duration-300 w-full sm:w-auto text-center" onclick="return confirm('<?php echo htmlspecialchars($settings_data['erase_account_confirm_message']); ?>');"><?php echo htmlspecialchars($settings_data['erase_account_btn']); ?></a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>
</main>

<?php
require_once FOOTER;
?>