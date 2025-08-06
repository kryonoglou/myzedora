<?php
require_once dirname(__DIR__, 2) . '/includes/map.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: " . LOGIN_URL);
    exit();
}

use Verot\Upload\Upload;

$user_id = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die(htmlspecialchars($settings_data['edit_profile_error_fetch_user']));
}

$errors = $_SESSION['errors'] ?? [];
$success_message = $_SESSION['success_message'] ?? '';
unset($_SESSION['errors'], $_SESSION['success_message']);

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (isset($_FILES['profile_image_upload']) && $_FILES['profile_image_upload']['error'] === UPLOAD_ERR_OK) {
        
        $upload_dir = dirname(__DIR__, 2) . '/img/users/';

        if (!is_dir($upload_dir)) {
            if (!@mkdir($upload_dir, 0755, true)) {
                 $errors[] = htmlspecialchars($settings_data['edit_profile_error_upload_dir_creation']);
            }
        }
        if (empty($errors) && !is_writable($upload_dir)) {
            $errors[] = htmlspecialchars($settings_data['edit_profile_error_upload_dir_writable']);
        }

        if (empty($errors)) {
            $handle = new Upload($_FILES['profile_image_upload']);
            if ($handle->uploaded) {
                $handle->allowed = array('image/jpeg', 'image/png', 'image/gif');
                $handle->file_new_name_body = bin2hex(random_bytes(16));
                $handle->jpeg_quality = 90;
                $handle->png_compression = 2;
                $handle->image_resize = true;
                $handle->image_convert = 'webp';
                $handle->image_x = 450;
                $handle->image_y = 450;
                $handle->image_ratio_crop = true;
                $handle->process($upload_dir);

                if ($handle->processed) {
                    $_POST['profile_image_url'] = $base_url . '/img/users/' . $handle->file_dst_name;

                    $old_image_url = $user['profile_image_url'];
                    $local_path_segment = '/img/users/';
                    if (!empty($old_image_url) && strpos($old_image_url, $local_path_segment) !== false) {
                        $old_filename = basename($old_image_url);
                        $old_filepath = dirname(__DIR__, 2) . $local_path_segment . $old_filename;
                        if (file_exists($old_filepath)) {
                            @unlink($old_filepath);
                        }
                    }

                } else {
                    $errors[] = $handle->error;
                }
                $handle->clean();
            }
        }
    }
    
    $current_success_message = '';

    if (empty($errors)) {
        $full_name = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        $profile_image_url = filter_var(trim($_POST['profile_image_url']), FILTER_SANITIZE_URL);
        $bio = trim($_POST['bio']);
        $allow_announcements = isset($_POST['allow_announcements']) ? 1 : 0;

        if (empty($full_name) || empty($email)) {
            $errors[] = htmlspecialchars($settings_data['edit_profile_error_required']);
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = htmlspecialchars($settings_data['edit_profile_error_email_invalid']);
        }
        if (!empty($profile_image_url) && !filter_var($profile_image_url, FILTER_VALIDATE_URL)) {
             $errors[] = htmlspecialchars($settings_data['edit_profile_error_image_url_invalid']);
        }

        $email_check_stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $email_check_stmt->execute([$email, $user_id]);
        if ($email_check_stmt->fetch()) {
            $errors[] = htmlspecialchars($settings_data['edit_profile_error_email_taken']);
        }

        if (empty($errors)) {
            try {
                $update_stmt = $pdo->prepare(
                    "UPDATE users SET full_name = ?, email = ?, profile_image_url = ?, bio = ?, allow_announcements = ? WHERE id = ?"
                );
                $update_stmt->execute([$full_name, $email, $profile_image_url, $bio, $allow_announcements, $user_id]);

                $_SESSION['full_name'] = $full_name;
                $current_success_message = htmlspecialchars($settings_data['edit_profile_success']);

            } catch (PDOException $e) {
                $errors[] = htmlspecialchars($settings_data['edit_profile_fail']);
            }
        }
    }
    
    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
    }
    if (!empty($current_success_message)) {
        $_SESSION['success_message'] = $current_success_message;
    }
    
    header("Location: " . EDIT_PROFILE_URL);
    exit();
}

$page_title = htmlspecialchars($settings_data['edit_profile_title']) . " - " . htmlspecialchars($settings_data['site_title']);
require_once HEADER;
?>
<script>
    if (history.scrollRestoration) {
        history.scrollRestoration = 'manual';
    }
    window.scrollTo(0, 0);
</script>

<main class="pt-32 pb-20">
    <section id="edit-profile" data-aos="fade-up">
        <div class="container mx-auto px-6 max-w-2xl">
            <div class="bg-gray-800/50 p-8 rounded-lg shadow-lg">
                <h1 class="text-3xl font-bold text-center mb-6 section-title"><?php echo htmlspecialchars($settings_data['edit_profile_title']); ?></h1>
                
                <?php if ($success_message): ?>
                    <div class="bg-green-500/20 text-green-300 p-4 rounded-lg mb-6 text-center">
                        <?php echo $success_message; ?>
                    </div>
                <?php endif; ?>
                <?php if (!empty($errors)): ?>
                    <div class="bg-red-500/20 text-red-300 p-4 rounded-lg mb-6">
                        <ul class="list-disc list-inside">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form action="<?php echo EDIT_PROFILE_URL; ?>" method="POST" enctype="multipart/form-data" class="space-y-6">
                    <div>
                        <label for="full_name" class="block text-gray-300 mb-2 font-semibold"><?php echo htmlspecialchars($settings_data['edit_profile_full_name']); ?></label>
                        <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" required class="w-full bg-gray-700 border border-gray-600 rounded-lg py-2 px-4 text-white focus:outline-none focus:ring-2 focus:ring-sky-500">
                    </div>
                    
                    <div>
                        <label for="username" class="block text-gray-300 mb-2 font-semibold"><?php echo htmlspecialchars($settings_data['edit_profile_username']); ?></label>
                        <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>" readonly class="w-full bg-gray-900 border border-gray-700 rounded-lg py-2 px-4 text-gray-400 cursor-not-allowed">
                        <p class="text-xs text-gray-500 mt-1"><?php echo htmlspecialchars($settings_data['edit_profile_username_cant_change']); ?></p>
                    </div>

                    <div>
                        <label for="email" class="block text-gray-300 mb-2 font-semibold"><?php echo htmlspecialchars($settings_data['edit_profile_email']); ?></label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required class="w-full bg-gray-700 border border-gray-600 rounded-lg py-2 px-4 text-white focus:outline-none focus:ring-2 focus:ring-sky-500">
                    </div>

                    <div>
                        <label for="profile_image_url" class="block text-gray-300 mb-2 font-semibold"><?php echo htmlspecialchars($settings_data['edit_profile_image_url']); ?></label>
                        <input type="url" id="profile_image_url" name="profile_image_url" value="<?php echo htmlspecialchars($user['profile_image_url'] ?? ''); ?>" placeholder="https://..." class="w-full bg-gray-700 border border-gray-600 rounded-lg py-2 px-4 text-white focus:outline-none focus:ring-2 focus:ring-sky-500">
                        <p class="text-xs text-gray-500 mt-1"><?php echo htmlspecialchars($settings_data['edit_profile_image_url_hint']); ?></p>
                    </div>

                    <div class="text-center text-gray-400 font-semibold"><?php echo htmlspecialchars($settings_data['edit_profile_upload_or_divider']); ?></div>

                    <div>
                        <label for="profile_image_upload" class="block text-gray-300 mb-2 font-semibold"><?php echo htmlspecialchars($settings_data['edit_profile_upload_new_image_label']); ?></label>
                        <input type="file" id="profile_image_upload" name="profile_image_upload" class="w-full bg-gray-700 border border-gray-600 rounded-lg text-white file:bg-gray-800 file:border-none file:px-4 file:py-2 file:mr-4 file:text-white hover:file:bg-sky-500 file:cursor-pointer">
                        <p class="text-xs text-gray-500 mt-1"><?php echo htmlspecialchars($settings_data['edit_profile_upload_new_image_hint']); ?></p>
                    </div>
                    
                    <div>
                        <label for="bio" class="block text-gray-300 mb-2 font-semibold"><?php echo htmlspecialchars($settings_data['edit_profile_bio']); ?></label>
                        <textarea id="bio" name="bio" rows="4" class="w-full bg-gray-700 border border-gray-600 rounded-lg py-2 px-4 text-white focus:outline-none focus:ring-2 focus:ring-sky-500"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" id="allow_announcements" name="allow_announcements" value="1" <?php echo ($user['allow_announcements'] ?? 0) ? 'checked' : ''; ?> class="form-checkbox h-5 w-5 text-sky-500 bg-gray-700 border-gray-600 rounded focus:ring-sky-500">
                        <label for="allow_announcements" class="ml-2 text-gray-300"><?php echo htmlspecialchars($settings_data['edit_profile_announcements']); ?></label>
                    </div>
                    
                    <div class="flex items-center justify-between pt-4">
                        <button type="submit" class="bg-sky-500 text-white font-semibold px-8 py-3 rounded-lg hover:bg-sky-600 transition-colors duration-300">
                            <?php echo htmlspecialchars($settings_data['edit_profile_save_btn']); ?>
                        </button>
                        <a href="<?php echo PROFILE_URL_BASE . urlencode($user['username']); ?>" class="text-sky-400 hover:text-sky-300 transition-colors duration-300">
                            <?php echo htmlspecialchars($settings_data['edit_profile_view_profile_link']); ?> &rarr;
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </section>
</main>

<?php require_once FOOTER; ?>