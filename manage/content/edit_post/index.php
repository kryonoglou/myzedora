<?php
require_once dirname(__DIR__, 3) . '/includes/map.php';

if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) { header("Location: " . HOME_URL); exit(); }

$post_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$post_id) { header("Location: " . MANAGE_URL); exit(); }

$errors = [];
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $slug_input = trim($_POST['slug']);
    $content = trim($_POST['content']);
    $excerpt = trim($_POST['excerpt']);
    $is_published = isset($_POST['is_published']) ? 1 : 0;
    $comments_enabled = isset($_POST['comments_enabled']) ? 1 : 0;
    
    if (empty($slug_input)) {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
    } else {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $slug_input)));
    }

    if (empty($title) || empty($content)) { $errors[] = htmlspecialchars($settings_data['title_content_required']); }
    
    $stmt = $pdo->prepare("SELECT id FROM posts WHERE slug = ? AND id != ?");
    $stmt->execute([$slug, $post_id]);
    if ($stmt->fetch()) {
        $errors[] = htmlspecialchars($settings_data['slug_problem_exist']);
    }

    if (empty($errors)) {
        $published_at = $is_published ? date('Y-m-d H:i:s') : null;
        $stmt = $pdo->prepare("UPDATE posts SET title = ?, slug = ?, content = ?, excerpt = ?, is_published = ?, comments_enabled = ?, published_at = ? WHERE id = ?");
        if ($stmt->execute([$title, $slug, $content, $excerpt, $is_published, $comments_enabled, $published_at, $post_id])) {
            $success_message = htmlspecialchars($settings_data['post_updated_successfully']);
        } else {
            $errors[] = htmlspecialchars($settings_data['post_not_updated']);
        }
    }
}

$stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ?");
$stmt->execute([$post_id]);
$post = $stmt->fetch();
if (!$post) { header("Location: " . MANAGE_URL); exit(); }

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
    <section id="edit-post" data-aos="fade-up">
        <div class="container mx-auto px-6 max-w-3xl">
            <div class="bg-gray-800/50 p-8 rounded-lg shadow-lg">
                <h2 class="text-3xl font-bold text-center mb-6 section-title"><?php echo htmlspecialchars($settings_data['edit_post']); ?></h2>
                <?php if (!empty($errors)): ?><div class="bg-red-500/20 text-red-300 p-4 rounded-lg mb-6"><?php foreach ($errors as $error): ?><p><?php echo $error; ?></p><?php endforeach; ?></div><?php endif; ?>
                <?php if ($success_message): ?><div class="bg-green-500/20 text-green-300 p-4 rounded-lg mb-6"><p><?php echo $success_message; ?> <a href="<?php echo MANAGE_URL; ?>" class="font-bold underline"><?php echo htmlspecialchars($settings_data['back_to_list']); ?></a></p></div><?php endif; ?>
                <form id="post-form" action="<?php echo EDIT_POST_URL_BASE . $post_id; ?>" method="POST">
                    <div class="mb-4"><label for="title" class="block text-gray-300 mb-2"><?php echo htmlspecialchars($settings_data['add_post_title_label']); ?></label><input type="text" name="title" value="<?php echo htmlspecialchars($post['title']); ?>" class="w-full bg-gray-700 border border-gray-600 rounded-lg py-2 px-4 text-white" required></div>
                    <div class="mb-4">
                        <label for="slug" class="block text-gray-300 mb-2"><?php echo htmlspecialchars($settings_data['add_project_slug_label']); ?></label>
                        <input type="text" id="slug" name="slug" value="<?php echo htmlspecialchars($post['slug']); ?>" class="w-full bg-gray-700 border border-gray-600 rounded-lg py-2 px-4 text-white">
                        <p class="text-xs text-gray-500 mt-1"><?php echo htmlspecialchars($settings_data['add_project_slug_hint']); ?></p>
                    </div>
                    <div class="mb-4">
                        <label for="content" class="block text-gray-300 mb-2"><?php echo htmlspecialchars($settings_data['add_post_content_label']); ?></label>
                        <?php
                            $editor_class = $use_tinymce 
                                ? 'tinymce-editor' 
                                : 'w-full bg-gray-900 border border-gray-600 rounded-lg py-2 px-4 text-white font-mono text-sm';
                        ?>
                        <textarea id="content" name="content" class="<?php echo $editor_class; ?>" <?php if (!$use_tinymce) echo 'style="height: 350px;"'; ?>><?php echo $post['content']; ?></textarea>
                    </div>
                    <div class="mb-6"><label for="excerpt" class="block text-gray-300 mb-2"><?php echo htmlspecialchars($settings_data['add_post_excerpt_label']); ?></label><textarea name="excerpt" rows="3" class="w-full bg-gray-700 border border-gray-600 rounded-lg py-2 px-4 text-white"><?php echo $post['excerpt']; ?></textarea></div>
                    
                    <div class="mb-6">
                        <label class="flex items-center text-gray-300">
                            <input type="checkbox" name="comments_enabled" value="1" class="form-checkbox h-5 w-5 text-sky-500" <?php if($post['comments_enabled']) echo 'checked'; ?>>
                            <span class="ml-2"><?php echo htmlspecialchars($settings_data['post_comments_label']); ?></span>
                        </label>
                    </div>

                    <div class="mb-6"><label class="flex items-center text-gray-300"><input type="checkbox" name="is_published" value="1" class="form-checkbox h-5 w-5 text-sky-500" <?php if($post['is_published']) echo 'checked'; ?>><span class="ml-2"><?php echo htmlspecialchars($settings_data['add_post_publish_label']); ?></span></label></div>
                    <button type="submit" class="w-full bg-sky-500 text-white font-semibold py-3 rounded-lg hover:bg-sky-600"><?php echo htmlspecialchars($settings_data['edit_profile_save_btn']); ?></button>
                </form>
            </div>
        </div>
    </section>
</main>

<?php require_once FOOTER; ?>