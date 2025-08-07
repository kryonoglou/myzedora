<?php
require_once dirname(__DIR__, 2) . '/includes/map.php';

if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: " . HOME_URL);
    exit();
}

$filter = $_GET['filter'] ?? 'all';
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10; 
$offset = ($page - 1) * $limit;

$items = [];
$total_items = 0;

try {
    if ($filter === 'posts' || $filter === 'all') {
        $stmt_posts = $pdo->prepare("SELECT id, title, slug, 'post' as type, created_at, is_published FROM posts");
        $stmt_posts->execute();
        $items = array_merge($items, $stmt_posts->fetchAll());
    }
    if ($filter === 'projects' || $filter === 'all') {
        $stmt_projects = $pdo->prepare("SELECT id, title, slug, 'project' as type, created_at, is_published FROM projects");
        $stmt_projects->execute();
        $items = array_merge($items, $stmt_projects->fetchAll());
    }
    
    usort($items, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });

    $total_items = count($items);
    $total_pages = ceil($total_items / $limit);
    $paginated_items = array_slice($items, $offset, $limit);

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

$page_title = $settings_data['manage_content_title'] . " - " . $settings_data['seo_title'];

require_once HEADER;
?>
<script>
    if (history.scrollRestoration) {
        history.scrollRestoration = 'manual';
    }
    window.scrollTo(0, 0);
</script>

<main class="pt-32 pb-20">
    <section id="manage-content" data-aos="fade-up">
        <div class="container mx-auto px-6 max-w-5xl">
            <h1 class="text-4xl font-bold text-center mb-4 section-title"><?php echo htmlspecialchars($settings_data['manage_content_title']); ?></h1>
            <p class="text-center text-gray-400 mb-10"><?php echo htmlspecialchars($settings_data['manage_content_subtitle']); ?></p>

            <div class="flex flex-col sm:flex-row items-center justify-between mb-8 gap-4">
                <div class="flex justify-center border-b border-gray-700">
                    <a href="?filter=all" class="px-4 py-2 font-medium rounded-t-lg <?php echo $filter === 'all' ? 'bg-sky-500 text-white' : 'text-gray-400 hover:bg-gray-700'; ?>"><?php echo htmlspecialchars($settings_data['manage_filter_all']); ?></a>
                    <a href="?filter=posts" class="px-4 py-2 font-medium rounded-t-lg <?php echo $filter === 'posts' ? 'bg-sky-500 text-white' : 'text-gray-400 hover:bg-gray-700'; ?>"><?php echo htmlspecialchars($settings_data['manage_filter_posts']); ?></a>
                    <a href="?filter=projects" class="px-4 py-2 font-medium rounded-t-lg <?php echo $filter === 'projects' ? 'bg-sky-500 text-white' : 'text-gray-400 hover:bg-gray-700'; ?>"><?php echo htmlspecialchars($settings_data['manage_filter_projects']); ?></a>
                </div>
                <div class="flex gap-4">
                    <a href="<?php echo ADD_POST_URL; ?>" class="bg-green-600 text-white font-semibold py-2 px-4 rounded-lg hover:bg-green-700 transition-colors duration-300"><?php echo htmlspecialchars($settings_data['add_post_btn']); ?></a>
                    <a href="<?php echo ADD_PROJECT_URL; ?>" class="bg-blue-600 text-white font-semibold py-2 px-4 rounded-lg hover:bg-blue-700 transition-colors duration-300"><?php echo htmlspecialchars($settings_data['add_project_btn']); ?></a>
                </div>
            </div>

            <div class="bg-gray-800/50 rounded-lg shadow-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-gray-900/50">
                            <tr>
                                <th class="p-4 font-semibold text-white">Title</th>
                                <th class="p-4 font-semibold text-white">Type</th>
                                <th class="p-4 font-semibold text-white hidden md:table-cell"><?php echo htmlspecialchars($settings_data['manage_col_created']); ?></th>
                                <th class="p-4 font-semibold text-white text-center">Status</th>
                                <th class="p-4 font-semibold text-white"><?php echo htmlspecialchars($settings_data['manage_col_action']); ?></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-700">
                            <?php if (empty($paginated_items)): ?>
                                <tr><td colspan="5" class="p-4 text-center text-gray-400"><?php echo htmlspecialchars($settings_data['manage_no_content']); ?></td></tr>
                            <?php else: ?>
                                <?php foreach ($paginated_items as $item): ?>
                                    <tr>
                                        <td class="p-4 text-white font-medium">
                                            <?php
                                                $view_url = ($item['type'] === 'post') 
                                                    ? POST_URL_BASE . urlencode($item['slug']) 
                                                    : PROJECT_URL_BASE . urlencode($item['slug']);
                                            ?>
                                            <a href="<?php echo $view_url; ?>" class="hover:text-sky-400 transition-colors duration-200" target="_blank" title="View <?php echo $item['type']; ?>">
                                                <?php echo htmlspecialchars($item['title']); ?>
                                            </a>
                                        </td>
                                        <td class="p-4"><span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo $item['type'] === 'post' ? 'bg-sky-500/50 text-sky-200' : 'bg-blue-500/50 text-blue-200'; ?>"><?php echo htmlspecialchars($item['type'] === 'post' ? $settings_data['post_label'] : $settings_data['project_label']); ?></span></td>
                                        <td class="p-4 text-gray-400 hidden md:table-cell"><?php echo date("M j, Y", strtotime($item['created_at'])); ?></td>
                                        <td class="p-4 text-center"><span class="inline-block h-4 w-4 rounded-full <?php echo $item['is_published'] ? 'bg-green-500' : 'bg-gray-500'; ?>" title="<?php echo $item['is_published'] ? 'Published' : 'Draft'; ?>"></span></td>
                                        <td class="p-4">
                                            <div class="flex items-center gap-2">
                                                <?php
                                                    $use_pretty_urls = ($settings_data['enable_url_rewriting'] ?? '0') === '1';
                                                    $edit_url = ($item['type'] === 'post') ? EDIT_POST_URL_BASE . $item['id'] : EDIT_PROJECT_URL_BASE . $item['id'];
                                                    $confirm_message = ($item['type'] === 'post') ? $settings_data['delete_confirm_post'] : $settings_data['delete_confirm_project'];

                                                    if ($use_pretty_urls) {
                                                        $delete_url = DELETE_ITEM_URL_BASE . '?type=' . $item['type'] . '&id=' . $item['id'];
                                                    } else {
                                                        $delete_url = DELETE_ITEM_URL_BASE . '?type=' . $item['type'] . '&id=' . $item['id'];
                                                    }
                                                ?>
                                                <a href="<?php echo $edit_url; ?>" class="text-green-400 hover:text-green-300 font-semibold"><?php echo htmlspecialchars($settings_data['manage_col_edit']); ?></a>
                                                <a href="<?php echo $delete_url; ?>" onclick="return confirm('<?php echo htmlspecialchars($confirm_message); ?>');" class="text-red-400 hover:text-red-300 font-semibold"><?php echo htmlspecialchars($settings_data['manage_col_delete']); ?></a>
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
                    <a href="?filter=<?php echo htmlspecialchars($filter); ?>&page=<?php echo $i; ?>" class="px-4 py-2 rounded-lg <?php echo $i === $page ? 'bg-sky-500 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600'; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
            </div>

        </div>
    </section>
</main>
<?php require_once FOOTER; ?>