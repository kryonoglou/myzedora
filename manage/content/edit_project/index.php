<?php
require_once dirname(__DIR__, 3) . '/includes/map.php';

use Verot\Upload\Upload;

if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: " . HOME_URL);
    exit();
}

$project_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$project_id) {
    header("Location: " . MANAGE_URL);
    exit();
}

$stmt_fetch = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
$stmt_fetch->execute([$project_id]);
$project = $stmt_fetch->fetch();
if (!$project) {
    header("Location: " . MANAGE_URL);
    exit();
}

$errors = $_SESSION['errors'] ?? [];
$success_message = $_SESSION['success_message'] ?? '';
unset($_SESSION['errors'], $_SESSION['success_message']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_success_message = '';

    $current_image_url = $project['image_url'];
    $submitted_image_url = trim($_POST['image_url']);
    if (empty($submitted_image_url) && !empty($current_image_url) && strpos($current_image_url, '/img/projects/') !== false) {
        $filename_to_delete = basename($current_image_url);
        $file_path_to_delete = PROJECT_ROOT . '/img/projects/' . $filename_to_delete;
        if (file_exists($file_path_to_delete)) {
            @unlink($file_path_to_delete);
        }
    }

    if (isset($_FILES['image_upload']) && $_FILES['image_upload']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = PROJECT_ROOT . '/img/projects/';
        if (!is_dir($upload_dir)) {
            if (!@mkdir($upload_dir, 0755, true)) {
                $errors[] = htmlspecialchars($settings_data['project_error_upload_dir_creation']);
            }
        }
        if (empty($errors) && !is_writable($upload_dir)) {
            $errors[] = htmlspecialchars($settings_data['project_error_upload_dir_writable']);
        }

        if (empty($errors)) {
            $handle = new Upload($_FILES['image_upload']);
            if ($handle->uploaded) {
                $handle->allowed = array('image/jpeg', 'image/png', 'image/gif');
                $handle->file_new_name_body = bin2hex(random_bytes(16));
                
                $handle->image_convert = 'webp';
                $handle->webp_quality = 90;
                
                $handle->process($upload_dir);

                if ($handle->processed) {
                    $_POST['image_url'] = $base_url . '/img/projects/' . $handle->file_dst_name;

                    $old_image_url = $project['image_url'];
                    if (!empty($old_image_url) && strpos($old_image_url, '/img/projects/') !== false) {
                        $old_filename = basename($old_image_url);
                        $old_filepath = PROJECT_ROOT . '/img/projects/' . $old_filename;
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

    if (empty($errors)) {
        $title = trim($_POST['title']);
        $slug_input = trim($_POST['slug']);
        $description = trim($_POST['description']);
        $image_url = filter_var(trim($_POST['image_url']), FILTER_SANITIZE_URL);
        $project_url = filter_var(trim($_POST['project_url']), FILTER_SANITIZE_URL);
        $technologies = trim($_POST['technologies']);

        if (empty($title) || empty($description)) {
            $errors[] = htmlspecialchars($settings_data['title_content_required']);
        }

        if (empty($slug_input)) {
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
        } else {
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $slug_input)));
        }

        $stmt = $pdo->prepare("SELECT id FROM projects WHERE slug = ? AND id != ?");
        $stmt->execute([$slug, $project_id]);
        if ($stmt->fetch()) {
            $errors[] = htmlspecialchars($settings_data['slug_problem_exist']);
        }

        if (empty($errors)) {
            $stmt = $pdo->prepare("UPDATE projects SET title = ?, slug = ?, description = ?, image_url = ?, project_url = ?, technologies = ? WHERE id = ?");
            if ($stmt->execute([$title, $slug, $description, $image_url, $project_url, $technologies, $project_id])) {
                $current_success_message = htmlspecialchars($settings_data['post_updated_successfully']);
            } else {
                $errors[] = htmlspecialchars($settings_data['post_not_updated']);
            }
        }
    }
    
    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
    }
    if (!empty($current_success_message)) {
        $_SESSION['success_message'] = $current_success_message;
    }
    header("Location: " . EDIT_PROJECT_URL_BASE . $project_id);
    exit();
}

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

<main class="pt-32 pb-20">
    <section id="edit-project" data-aos="fade-up">
        <div class="container mx-auto px-6 max-w-3xl">
            <div class="bg-gray-800/50 p-8 rounded-lg shadow-lg">
                <h2 class="text-3xl font-bold text-center mb-6 section-title"><?php echo htmlspecialchars($settings_data['edit_project_title']); ?></h2>
                
                <?php if (!empty($errors)): ?>
                    <div class="bg-red-500/20 text-red-300 p-4 rounded-lg mb-6">
                        <?php foreach ($errors as $error): ?><p><?php echo $error; ?></p><?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <?php if ($success_message): ?>
                    <div class="bg-green-500/20 text-green-300 p-4 rounded-lg mb-6">
                        <p><?php echo $success_message; ?> <a href="<?php echo MANAGE_CONTENT_URL; ?>" class="font-bold underline"><?php echo htmlspecialchars($settings_data['back_to_list']); ?></a></p>
                    </div>
                <?php endif; ?>
                
                <form id="project-form" action="<?php echo EDIT_PROJECT_URL_BASE . $project_id; ?>" method="POST" enctype="multipart/form-data">
                    <div class="mb-4"><label for="title" class="block text-gray-300 mb-2"><?php echo htmlspecialchars($settings_data['add_project_title_label']); ?></label><input type="text" name="title" value="<?php echo htmlspecialchars($project['title']); ?>" class="w-full bg-gray-700 border border-gray-600 rounded-lg py-2 px-4 text-white" required></div>
                    <div class="mb-4"><label for="slug" class="block text-gray-300 mb-2"><?php echo htmlspecialchars($settings_data['add_project_slug_label']); ?></label><input type="text" name="slug" value="<?php echo htmlspecialchars($project['slug']); ?>" class="w-full bg-gray-700 border border-gray-600 rounded-lg py-2 px-4 text-white"></div>
                    <div class="mb-4">
                        <label for="description" class="block text-gray-300 mb-2"><?php echo htmlspecialchars($settings_data['add_project_desc_label']); ?></label>
                        <?php
                            $editor_class = $use_tinymce 
                                ? 'tinymce-editor' 
                                : 'w-full bg-gray-900 border border-gray-600 rounded-lg py-2 px-4 text-white font-mono text-sm';
                        ?>
                        <textarea id="description" name="description" class="<?php echo $editor_class; ?>" <?php if (!$use_tinymce) echo 'style="height: 350px;"'; ?>><?php echo htmlspecialchars($project['description']); ?></textarea>
                    </div>
                    <div class="mb-4">
                        <label for="image_url" class="block text-gray-300 mb-2"><?php echo htmlspecialchars($settings_data['add_project_img_url_label']); ?></label>
                        <input type="text" name="image_url" value="<?php echo htmlspecialchars($project['image_url']); ?>" class="w-full bg-gray-700 border border-gray-600 rounded-lg py-2 px-4 text-white">
                    </div>

                    <div class="text-center text-gray-400 font-semibold"><?php echo htmlspecialchars($settings_data['add_project_upload_or_divider']); ?></div>
                    
                    <div class="mb-4">
                        <label for="image_upload" class="block text-gray-300 mb-2"><?php echo htmlspecialchars($settings_data['add_project_upload_image_label']); ?></label>
                        <input type="file" id="image_upload" name="image_upload" class="w-full bg-gray-700 border border-gray-600 rounded-lg text-white file:bg-gray-800 file:border-none file:px-4 file:py-2 file:mr-4 file:text-white hover:file:bg-sky-500 file:cursor-pointer">
                        <p class="text-xs text-gray-500 mt-1"><?php echo htmlspecialchars($settings_data['add_project_upload_image_hint']); ?></p>
                    </div>

                    <div class="mb-4"><label for="project_url" class="block text-gray-300 mb-2"><?php echo htmlspecialchars($settings_data['add_project_url_label']); ?></label><input type="text" name="project_url" value="<?php echo htmlspecialchars($project['project_url']); ?>" class="w-full bg-gray-700 border border-gray-600 rounded-lg py-2 px-4 text-white"></div>
                    <div class="mb-6"><label for="technologies" class="block text-gray-300 mb-2"><?php echo htmlspecialchars($settings_data['add_project_tech_label']); ?></label><input type="text" name="technologies" value="<?php echo htmlspecialchars($project['technologies']); ?>" class="w-full bg-gray-700 border border-gray-600 rounded-lg py-2 px-4 text-white"><p class="text-xs text-gray-500 mt-1"><?php echo htmlspecialchars($settings_data['add_project_tech_hint']); ?></p></div>
                    <button type="submit" class="w-full bg-sky-500 text-white font-semibold py-3 rounded-lg hover:bg-sky-600"><?php echo htmlspecialchars($settings_data['add_project_save_btn']); ?></button>
                </form>

                <div class="mt-6 text-center">
                    <a href="<?php echo DELETE_ITEM_URL_BASE; ?>?type=project&id=<?php echo $project_id; ?>"
                       class="text-red-400 hover:text-red-300 hover:underline"
                       onclick="return confirm('<?php echo htmlspecialchars($settings_data['delete_confirm_project']); ?>');">
                        <?php echo htmlspecialchars($settings_data['delete_project_btn']); ?>
                    </a>
                </div>

            </div>
        </div>
    </section>
</main>

<?php require_once FOOTER; ?>