<?php
require_once dirname(__DIR__, 2) . '/includes/map.php';
require_once PROJECT_ROOT . '/vendor/autoload.php';

use Verot\Upload\Upload;

if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: " . HOME_URL);
    exit();
}

$errors = $_SESSION['errors'] ?? [];
$success_message = $_SESSION['success_message'] ?? '';
unset($_SESSION['errors'], $_SESSION['success_message']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image_upload'])) {
    $current_success_message = '';
    $upload_dir = PROJECT_ROOT . '/img/gallery/';

    if (!is_dir($upload_dir)) {
        if (!@mkdir($upload_dir, 0755, true)) {
            $errors[] = 'Error: Could not create the gallery upload directory.';
        }
    }
    if (empty($errors) && !is_writable($upload_dir)) {
        $errors[] = 'Error: The gallery upload directory is not writable.';
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
                $stmt = $pdo->prepare("INSERT INTO gallery (filename) VALUES (?)");
                $stmt->execute([$handle->file_dst_name]);
                $current_success_message = htmlspecialchars($settings_data['gallery_upload_success']);
            } else {
                $errors[] = $handle->error;
            }
            $handle->clean();
        } else {
            $errors[] = htmlspecialchars($settings_data['gallery_upload_fail']);
        }
    }

    if (!empty($errors)) $_SESSION['errors'] = $errors;
    if (!empty($current_success_message)) $_SESSION['success_message'] = $current_success_message;
    
    header("Location: " . GALLERY_URL);
    exit();
}

$images_per_page = 15;
$total_images_stmt = $pdo->query("SELECT COUNT(*) FROM gallery");
$total_images = $total_images_stmt->fetchColumn();
$total_pages = ceil($total_images / $images_per_page);
$current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$current_page = max(1, min($current_page, $total_pages));
$offset = ($current_page - 1) * $images_per_page;

$images_stmt = $pdo->prepare("SELECT id, filename FROM gallery ORDER BY uploaded_at DESC LIMIT :limit OFFSET :offset");
$images_stmt->bindValue(':limit', $images_per_page, PDO::PARAM_INT);
$images_stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$images_stmt->execute();
$images = $images_stmt->fetchAll();

require_once HEADER;
?>
<style>
.toast-notification {
    position: fixed;
    top: 20px;
    left: 50%;
    transform: translateX(-50%);
    background-color: #22c55e;
    color: white;
    padding: 12px 24px;
    border-radius: 8px;
    z-index: 1000;
    opacity: 0;
    transition: opacity 0.3s ease-in-out, top 0.3s ease-in-out;
    font-weight: 600;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}
.toast-notification.show {
    top: 40px;
    opacity: 1;
}
</style>
<script>
    if (history.scrollRestoration) {
        history.scrollRestoration = 'manual';
    }
    window.scrollTo(0, 0);
</script>
<main class="pt-32 pb-20">
    <section id="gallery" data-aos="fade-up">
        <div class="container mx-auto px-6">
            <div class="bg-gray-800/50 p-8 rounded-lg shadow-lg">
                <div class="flex flex-col md:flex-row justify-between items-center mb-8 border-b border-gray-700 pb-4">
                    <div>
                        <h2 class="text-3xl font-bold section-title"><?php echo htmlspecialchars($settings_data['gallery_title']); ?></h2>
                        <p class="text-gray-400 mt-1"><?php echo htmlspecialchars($settings_data['gallery_subtitle']); ?></p>
                    </div>
                    <form action="<?php echo GALLERY_URL; ?>" method="POST" enctype="multipart/form-data" class="mt-4 md:mt-0">
                        <label for="image_upload" class="bg-sky-500 text-white font-semibold px-6 py-3 rounded-lg hover:bg-sky-600 transition-colors duration-300 cursor-pointer">
                            <?php echo htmlspecialchars($settings_data['gallery_upload_btn']); ?>
                        </label>
                        <input type="file" name="image_upload" id="image_upload" class="hidden" onchange="this.form.submit()">
                        <p class="text-xs text-gray-500 mt-6 text-center md:text-left"><?php echo htmlspecialchars($settings_data['gallery_upload_hint']); ?></p>
                    </form>
                </div>

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

                <?php if (empty($images)): ?>
                    <p class="text-gray-400 text-center py-12"><?php echo htmlspecialchars($settings_data['gallery_no_images']); ?></p>
                <?php else: ?>
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
                        <?php foreach ($images as $image): 
                            $image_url = $base_url . '/img/gallery/' . htmlspecialchars($image['filename']);
                        ?>
                            <div class="group relative bg-gray-900 rounded-lg overflow-hidden shadow-md">
                                <img src="<?php echo $image_url; ?>" alt="Gallery image" class="w-full h-48 object-cover">
                                <div class="absolute bottom-0 left-0 right-0 bg-black/60 p-2 flex justify-around items-center">
                                    <button onclick="copyToClipboard('<?php echo $image_url; ?>')" class="text-white hover:text-sky-400 transition-colors text-sm font-semibold">
                                        <?php echo htmlspecialchars($settings_data['gallery_copy_url']); ?>
                                    </button>
                                    <form action="<?php echo GALLERY_URL . 'delete_image.php'; ?>" method="POST" onsubmit="return confirm('<?php echo htmlspecialchars($settings_data['gallery_delete_confirm']); ?>');">
                                        <input type="hidden" name="image_id" value="<?php echo $image['id']; ?>">
                                        <button type="submit" class="text-white hover:text-red-400 transition-colors text-sm font-semibold">
                                            <?php echo htmlspecialchars($settings_data['gallery_delete']); ?>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if ($total_pages > 1): ?>
                    <nav class="mt-8 flex justify-center">
                        <ul class="flex items-center -space-x-px h-10 text-base">
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li>
                                <a href="?page=<?php echo $i; ?>" class="flex items-center justify-center px-4 h-10 leading-tight <?php echo $i == $current_page ? 'text-white bg-sky-600' : 'text-gray-400 bg-gray-800 hover:bg-gray-700 hover:text-white'; ?> border border-gray-700">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>
</main>

<script>
function showToast(message) {
    const toast = document.createElement('div');
    toast.className = 'toast-notification';
    toast.textContent = message;
    document.body.appendChild(toast);

    setTimeout(() => {
        toast.classList.add('show');
    }, 100);

    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => {
            document.body.removeChild(toast);
        }, 300);
    }, 2500);
}

function copyToClipboard(text) {
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(text).then(() => {
            showToast('<?php echo htmlspecialchars($settings_data['gallery_url_copied']); ?>');
        });
    } else {
        const textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.style.position = 'fixed';
        textarea.style.left = '-9999px';
        document.body.appendChild(textarea);
        textarea.select();
        try {
            document.execCommand('copy');
            showToast('<?php echo htmlspecialchars($settings_data['gallery_url_copied']); ?>');
        } catch (err) {
            console.error('Fallback: Oops, unable to copy', err);
        }
        document.body.removeChild(textarea);
    }
}
</script>

<?php require_once FOOTER; ?>