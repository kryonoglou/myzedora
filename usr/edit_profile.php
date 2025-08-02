<?php
require_once dirname(__DIR__) . '/includes/map.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: " . LOGIN_URL);
    exit();
}

$user_id = $_SESSION['user_id'];
$errors = [];
$success_message = '';

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $bio = trim($_POST['bio']);
    $profile_image_url = filter_var(trim($_POST['profile_image_url']), FILTER_SANITIZE_URL);
    $allow_announcements = isset($_POST['allow_announcements']) ? 1 : 0;

    if (empty($full_name) || empty($email)) { $errors[] = htmlspecialchars($settings_data['edit_profile_error_required']); }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors[] = htmlspecialchars($settings_data['edit_profile_error_email_invalid']); }
    if (!empty($profile_image_url) && !filter_var($profile_image_url, FILTER_VALIDATE_URL)) { $errors[] = htmlspecialchars($settings_data['edit_profile_error_image_url_invalid']); }

    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->execute([$email, $user_id]);
    if ($stmt->fetch()) { $errors[] = htmlspecialchars($settings_data['edit_profile_error_email_taken']); }

    if (empty($errors)) {
        $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, bio = ?, profile_image_url = ?, allow_announcements = ? WHERE id = ?");
        if ($stmt->execute([$full_name, $email, $bio, $profile_image_url, $allow_announcements, $user_id])) {
            $success_message = htmlspecialchars($settings_data['edit_profile_success']);
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
        } else {
            $errors[] = htmlspecialchars($settings_data['edit_profile_fail']);
        }
    }
}

$page_title = $settings_data['edit_profile_title'] . " - " . $settings_data['seo_title'];

require_once HEADER;
?>
<main class="pt-32 pb-20">
    <section id="edit-profile" data-aos="fade-up">
        <div class="container mx-auto px-6">
            <div class="max-w-2xl mx-auto bg-gray-800/50 p-8 rounded-lg shadow-lg">
                <h2 class="text-3xl font-bold text-center mb-6 section-title"><?php echo htmlspecialchars($settings_data['edit_profile_title']); ?></h2>
                
                <?php if (!empty($errors)): ?>
                    <div class="bg-red-500/20 text-red-300 p-4 rounded-lg mb-6">
                        <?php foreach ($errors as $error): ?><p><?php echo $error; ?></p><?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <?php if ($success_message): ?>
                    <div class="bg-green-500/20 text-green-300 p-4 rounded-lg mb-6">
                        <p><?php echo $success_message; ?> <a href="<?php echo PROFILE_URL_BASE . urlencode($user['username']); ?>" class="font-bold underline"><?php echo htmlspecialchars($settings_data['edit_profile_view_profile_link']); ?></a>.</p>
                    </div>
                <?php endif; ?>

                <form action="<?php echo EDIT_PROFILE_URL; ?>" method="POST">
                    <div class="mb-4">
                        <label for="full_name" class="block text-gray-300 mb-2"><?php echo htmlspecialchars($settings_data['edit_profile_full_name']); ?></label>
                        <input type="text" id="full_name" name="full_name" class="w-full bg-gray-700 border border-gray-600 rounded-lg py-2 px-4 text-white" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                    </div>
                    <div class="mb-4">
                        <label for="username" class="block text-gray-300 mb-2"><?php echo htmlspecialchars($settings_data['edit_profile_username']); ?></label>
                        <input type="text" id="username" class="w-full bg-gray-900/50 border border-gray-700 rounded-lg py-2 px-4 text-gray-400" value="@<?php echo htmlspecialchars($user['username']); ?>" disabled>
                        <p class="text-xs text-gray-500 mt-1"><?php echo htmlspecialchars($settings_data['edit_profile_username_cant_change']); ?></p>
                    </div>
                    <div class="mb-4">
                        <label for="email" class="block text-gray-300 mb-2"><?php echo htmlspecialchars($settings_data['edit_profile_email']); ?></label>
                        <input type="email" id="email" name="email" class="w-full bg-gray-700 border border-gray-600 rounded-lg py-2 px-4 text-white" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                    <div class="mb-4">
                        <label for="profile_image_url" class="block text-gray-300 mb-2"><?php echo htmlspecialchars($settings_data['edit_profile_image_url']); ?></label>
                        <input type="text" id="profile_image_url" name="profile_image_url" class="w-full bg-gray-700 border border-gray-600 rounded-lg py-2 px-4 text-white" value="<?php echo htmlspecialchars($user['profile_image_url'] ?? ''); ?>">
                        <p class="text-xs text-gray-500 mt-1"><?php echo htmlspecialchars($settings_data['edit_profile_image_url_hint']); ?></p>
                    </div>
                    <div class="mb-6">
                        <label for="bio" class="block text-gray-300 mb-2"><?php echo htmlspecialchars($settings_data['edit_profile_bio']); ?></label>
                        <textarea id="bio" name="bio" rows="4" class="w-full bg-gray-700 border border-gray-600 rounded-lg py-2 px-4 text-white"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                    </div>
                    <div class="mb-6">
                        <label class="flex items-center text-gray-300">
                            <input type="checkbox" name="allow_announcements" value="1" class="form-checkbox h-5 w-5 text-sky-500 bg-gray-700 border-gray-600 rounded focus:ring-sky-500" <?php if($user['allow_announcements']) echo 'checked'; ?>>
                            <span class="ml-2"><?php echo htmlspecialchars($settings_data['edit_profile_announcements']); ?></span>
                        </label>
                    </div>
                    <button type="submit" class="w-full bg-sky-500 text-white font-semibold py-3 rounded-lg hover:bg-sky-600"><?php echo htmlspecialchars($settings_data['edit_profile_save_btn']); ?></button>
                </form>
            </div>
        </div>
    </section>
</main>
<?php require_once FOOTER; ?>