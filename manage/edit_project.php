<?php
require_once dirname(__DIR__) . '/includes/map.php';

if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) { header("Location: " . HOME_URL); exit(); }

$project_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$project_id) { header("Location: " . MANAGE_URL); exit(); }

$errors = [];
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $slug_input = trim($_POST['slug']);
    $description = trim($_POST['description']);
    $image_url = filter_var(trim($_POST['image_url']), FILTER_SANITIZE_URL);
    $project_url = filter_var(trim($_POST['project_url']), FILTER_SANITIZE_URL);
    $technologies = trim($_POST['technologies']);

    if (empty($title) || empty($description)) { $errors[] = htmlspecialchars($settings_data['title_content_required']); }

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
            $success_message = htmlspecialchars($settings_data['post_updated_successfully']);
        } else {
            $errors[] = htmlspecialchars($settings_data['post_not_updated']);
        }
    }
}

$stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
$stmt->execute([$project_id]);
$project = $stmt->fetch();
if (!$project) { header("Location: " . MANAGE_URL); exit(); }

require_once HEADER;
?>
<style>
    .wysiwyg-toolbar { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; padding: 8px; background-color: #374151; border-top-left-radius: 0.5rem; border-top-right-radius: 0.5rem; border: 1px solid #4b5563; border-bottom: none; }
    .wysiwyg-toolbar button, .wysiwyg-toolbar select, .wysiwyg-toolbar input { background-color: #4b5563; color: white; border: 1px solid #6b7280; border-radius: 0.375rem; padding: 4px 8px; font-size: 14px; cursor: pointer; }
    .wysiwyg-toolbar button:hover, .wysiwyg-toolbar select:hover { background-color: #6b7280; }
    .wysiwyg-toolbar input[type="color"] { padding: 0; width: 28px; height: 28px; border: none; background: none; cursor: pointer; }
    .wysiwyg-editor { min-height: 250px; padding: 1rem; background-color: #1f2937; border: 1px solid #4b5563; border-bottom-left-radius: 0.5rem; border-bottom-right-radius: 0.5rem; color: white; outline: none; }
    .wysiwyg-html-view { width: 100%; min-height: 250px; padding: 1rem; background-color: #111827; border: 1px solid #4b5563; border-bottom-left-radius: 0.5rem; border-bottom-right-radius: 0.5rem; color: white; font-family: monospace; }
</style>

<main class="pt-32 pb-20">
    <section id="edit-project" data-aos="fade-up">
        <div class="container mx-auto px-6 max-w-3xl">
            <div class="bg-gray-800/50 p-8 rounded-lg shadow-lg">
                <h2 class="text-3xl font-bold text-center mb-6 section-title"><?php echo htmlspecialchars($settings_data['edit_post']); ?></h2>
                <?php if (!empty($errors)): ?><div class="bg-red-500/20 text-red-300 p-4 rounded-lg mb-6"><?php foreach ($errors as $error): ?><p><?php echo $error; ?></p><?php endforeach; ?></div><?php endif; ?>
                <?php if ($success_message): ?><div class="bg-green-500/20 text-green-300 p-4 rounded-lg mb-6"><p><?php echo $success_message; ?> <a href="<?php echo MANAGE_URL; ?>" class="font-bold underline"><?php echo htmlspecialchars($settings_data['back_to_list']); ?></a></p></div><?php endif; ?>
                <form id="project-form" action="<?php echo EDIT_PROJECT_URL_BASE . $project_id; ?>" method="POST">
                    <div class="mb-4"><label for="title" class="block text-gray-300 mb-2"><?php echo htmlspecialchars($settings_data['add_project_title_label']); ?></label><input type="text" name="title" value="<?php echo htmlspecialchars($project['title']); ?>" class="w-full bg-gray-700 border border-gray-600 rounded-lg py-2 px-4 text-white" required></div>
                    <div class="mb-4"><label for="slug" class="block text-gray-300 mb-2"><?php echo htmlspecialchars($settings_data['add_project_slug_label']); ?></label><input type="text" name="slug" value="<?php echo htmlspecialchars($project['slug']); ?>" class="w-full bg-gray-700 border border-gray-600 rounded-lg py-2 px-4 text-white"></div>
                    <div class="mb-4">
                        <label for="description" class="block text-gray-300 mb-2"><?php echo htmlspecialchars($settings_data['add_project_desc_label']); ?></label>
                        <div class="wysiwyg-container">
                             <div class="wysiwyg-toolbar">
                                <button type="button" data-command="bold" title="Bold"><b>B</b></button>
                                <button type="button" data-command="italic" title="Italic"><i>I</i></button>
                                <button type="button" data-command="underline" title="Underline"><u>U</u></button>
                                <button type="button" data-command="insertImage" title="Insert Image"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M6.002 5.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/><path d="M2.002 1a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V3a2 2 0 0 0-2-2h-12zm12 1a1 1 0 0 1 1 1v6.5l-3.777-1.947a.5.5 0 0 0-.577.093l-3.71 3.71-2.66-1.772a.5.5 0 0 0-.63.062L1.002 12V3a1 1 0 0 1 1-1h12z"/></svg></button>
                                <select data-command="fontSize" title="Font Size">
                                    <option value="3">Normal</option><option value="5">Large</option><option value="7">Heading</option><option value="1">Small</option>
                                </select>
                                <input type="color" data-command="foreColor" title="Font Color" value="#FFFFFF">
                                <button type="button" data-mode="html" title="HTML Source">&lt;/&gt;</button>
                            </div>
                            <div class="wysiwyg-editor" contenteditable="true"><?php echo $project['description']; ?></div>
                            <textarea class="wysiwyg-html-view" style="display:none;"></textarea>
                            <textarea id="description" name="description" style="display:none;" required><?php echo htmlspecialchars($project['description']); ?></textarea>
                        </div>
                        </div>
                    <div class="mb-4"><label for="image_url" class="block text-gray-300 mb-2"><?php echo htmlspecialchars($settings_data['add_project_img_url_label']); ?></label><input type="text" name="image_url" value="<?php echo htmlspecialchars($project['image_url']); ?>" class="w-full bg-gray-700 border border-gray-600 rounded-lg py-2 px-4 text-white"></div>
                    <div class="mb-4"><label for="project_url" class="block text-gray-300 mb-2"><?php echo htmlspecialchars($settings_data['add_project_url_label']); ?></label><input type="text" name="project_url" value="<?php echo htmlspecialchars($project['project_url']); ?>" class="w-full bg-gray-700 border border-gray-600 rounded-lg py-2 px-4 text-white"></div>
                    <div class="mb-6"><label for="technologies" class="block text-gray-300 mb-2"><?php echo htmlspecialchars($settings_data['add_project_tech_label']); ?></label><input type="text" name="technologies" value="<?php echo htmlspecialchars($project['technologies']); ?>" class="w-full bg-gray-700 border border-gray-600 rounded-lg py-2 px-4 text-white"><p class="text-xs text-gray-500 mt-1"><?php echo htmlspecialchars($settings_data['add_project_tech_hint']); ?></p></div>
                    <button type="submit" class="w-full bg-sky-500 text-white font-semibold py-3 rounded-lg hover:bg-sky-600"><?php echo htmlspecialchars($settings_data['add_project_save_btn']); ?></button>
                </form>
            </div>
        </div>
    </section>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const toolbar = document.querySelector('.wysiwyg-toolbar');
    const editor = document.querySelector('.wysiwyg-editor');
    const htmlView = document.querySelector('.wysiwyg-html-view');
    const hiddenTextarea = document.getElementById('description');
    const form = document.getElementById('project-form');
    let currentView = 'wysiwyg';

    toolbar.addEventListener('mousedown', function(e) {
        e.preventDefault();
    });

    toolbar.addEventListener('click', function(e) {
        let target = e.target;
        if (target.tagName === 'B' || target.tagName === 'I' || target.tagName === 'U' || target.tagName === 'svg' || target.tagName === 'path') {
            target = target.closest('button');
        }
        if (!target || target.tagName !== 'BUTTON') return;

        const command = target.dataset.command;
        if (command === 'insertImage') {
            const url = prompt('Enter image URL:');
            if (url) document.execCommand(command, false, url);
        } else if (target.dataset.mode === 'html') {
            if (currentView === 'wysiwyg') {
                htmlView.value = editor.innerHTML;
                editor.style.display = 'none';
                htmlView.style.display = 'block';
                target.innerHTML = 'WYSIWYG';
                currentView = 'html';
            } else {
                editor.innerHTML = htmlView.value;
                htmlView.style.display = 'none';
                editor.style.display = 'block';
                target.innerHTML = '&lt;/&gt;';
                currentView = 'wysiwyg';
            }
        } else {
            document.execCommand(command, false, null);
        }
        editor.focus();
    });

    toolbar.addEventListener('change', function(e) {
        if (e.target.tagName !== 'SELECT' && e.target.tagName !== 'INPUT') return;
        const command = e.target.dataset.command;
        const value = e.target.value;
        document.execCommand(command, false, value);
        editor.focus();
    });

    form.addEventListener('submit', function() {
        if (currentView === 'html') {
            editor.innerHTML = htmlView.value;
        }
        hiddenTextarea.value = editor.innerHTML;
    });
});
</script>

<?php require_once FOOTER; ?>