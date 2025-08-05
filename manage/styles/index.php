<?php
require_once dirname(__DIR__, 2) . '/includes/map.php';

if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: " . HOME_URL);
    exit();
}

$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

try {
    $total_styles = $pdo->query("SELECT COUNT(id) FROM custom_styles")->fetchColumn();
    $total_pages = ceil($total_styles / $limit);

    $stmt = $pdo->prepare("SELECT id, name, created_at, is_active FROM custom_styles ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $styles = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

$page_title = $settings_data['styles_title'] . " - " . $settings_data['seo_title'];

require_once HEADER;
?>
<script>
    if (history.scrollRestoration) {
        history.scrollRestoration = 'manual';
    }
    window.scrollTo(0, 0);
</script>

<main class="pt-32 pb-20">
    <section id="manage-styles" data-aos="fade-up">
        <div class="container mx-auto px-6 max-w-5xl">
            <h1 class="text-4xl font-bold text-center mb-4 section-title"><?php echo htmlspecialchars($settings_data['styles_title']); ?></h1>
            <p class="text-center text-gray-400 mb-10"><?php echo htmlspecialchars($settings_data['styles_subtitle']); ?></p>

            <div class="flex justify-end mb-8">
                <a href="<?php echo ADD_STYLE_URL; ?>" class="bg-green-600 text-white font-semibold py-2 px-4 rounded-lg hover:bg-green-700 transition-colors duration-300"><?php echo htmlspecialchars($settings_data['add_style_btn']); ?></a>
            </div>

            <div class="bg-gray-800/50 rounded-lg shadow-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-gray-900/50">
                            <tr>
                                <th class="p-4 font-semibold text-white">Name</th>
                                <th class="p-4 font-semibold text-white hidden md:table-cell">Created On</th>
                                <th class="p-4 font-semibold text-white text-center">Status</th>
                                <th class="p-4 font-semibold text-white">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-700">
                            <?php if (empty($styles)): ?>
                                <tr><td colspan="4" class="p-4 text-center text-gray-400">No styles have been created yet.</td></tr>
                            <?php else: ?>
                                <?php foreach ($styles as $style): ?>
                                    <tr>
                                        <td class="p-4 text-white font-medium"><?php echo htmlspecialchars($style['name']); ?></td>
                                        <td class="p-4 text-gray-400 hidden md:table-cell"><?php echo date("M j, Y", strtotime($style['created_at'])); ?></td>
                                        <td class="p-4 text-center"><span class="inline-block h-4 w-4 rounded-full <?php echo $style['is_active'] ? 'bg-green-500' : 'bg-gray-500'; ?>" title="<?php echo $style['is_active'] ? 'Active' : 'Inactive'; ?>"></span></td>
                                        <td class="p-4">
                                            <div class="flex items-center gap-2">
                                                <a href="<?php echo EDIT_STYLE_URL_BASE . $style['id']; ?>" class="text-green-400 hover:text-green-300 font-semibold">Edit</a>
                                                <a href="<?php echo DELETE_STYLE_URL_BASE . $style['id']; ?>" onclick="return confirm('<?php echo htmlspecialchars($settings_data['delete_confirm_style']); ?>');" class="text-red-400 hover:text-red-300 font-semibold">Delete</a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="flex justify-center items-center gap-4 mt-8">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?php echo $i; ?>" class="px-4 py-2 rounded-lg <?php echo $i === $page ? 'bg-sky-500 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600'; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
            </div>

        </div>
    </section>
</main>
<?php require_once FOOTER; ?>