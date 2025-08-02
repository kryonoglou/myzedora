<?php
require_once dirname(__DIR__) . '/includes/map.php';

if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: " . HOME_URL);
    exit();
}

$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;
$filter = $_GET['filter'] ?? 'all';

$where_clause = '';
switch ($filter) {
    case 'members': $where_clause = 'AND user_id IS NOT NULL AND is_bot = 0'; break;
    case 'visitors': $where_clause = 'AND user_id IS NULL AND is_bot = 0'; break;
    case 'bots': $where_clause = 'AND is_bot = 1'; break;
    default: $where_clause = 'AND is_bot = 0';
}

$total_query = $pdo->prepare("SELECT COUNT(*) FROM (SELECT id FROM statistics WHERE view_date >= DATE_SUB(NOW(), INTERVAL 30 DAY) $where_clause GROUP BY page_type, item_id) as grouped_items");
$total_query->execute();
$total_items = $total_query->fetchColumn();
$total_pages = ceil($total_items / $limit);

$views_query = $pdo->prepare("
    SELECT page_type, item_id, COUNT(id) as view_count
    FROM statistics 
    WHERE view_date >= DATE_SUB(NOW(), INTERVAL 30 DAY) $where_clause
    GROUP BY page_type, item_id 
    ORDER BY view_count DESC
    LIMIT :limit OFFSET :offset
");
$views_query->bindParam(':limit', $limit, PDO::PARAM_INT);
$views_query->bindParam(':offset', $offset, PDO::PARAM_INT);
$views_query->execute();
$views_30_days = $views_query->fetchAll();

$top_items = [];
foreach ($views_30_days as $view) {
    $title = 'N/A';
    if ($view['page_type'] === 'post' && $view['item_id']) {
        $title = $pdo->query("SELECT title FROM posts WHERE id = {$view['item_id']}")->fetchColumn();
    } elseif ($view['page_type'] === 'project' && $view['item_id']) {
        $title = $pdo->query("SELECT title FROM projects WHERE id = {$view['item_id']}")->fetchColumn();
    } elseif ($view['page_type'] === 'home') {
        $title = 'Homepage';
    }
    $top_items[] = ['title' => $title ?: 'Deleted Item', 'type' => ucfirst($view['page_type']), 'views' => $view['view_count']];
}

require_once HEADER;
?>
<main class="pt-32 pb-20">
    <section id="full-stats" data-aos="fade-up">
        <div class="container mx-auto px-6 max-w-4xl">
            <h1 class="text-4xl font-bold text-center mb-4 section-title"><?php echo htmlspecialchars($settings_data['full_report_title']); ?></h1>
            <p class="text-center text-gray-400 mb-10"><?php echo htmlspecialchars($settings_data['full_report_subtitle']); ?></p>

            <div class="bg-gray-800/50 p-8 rounded-lg shadow-lg">
                <div class="flex justify-center border-b border-gray-700 mb-6">
                    <a href="?filter=all" class="px-4 py-2 font-medium rounded-t-lg focus:outline-none <?php echo $filter === 'all' ? 'bg-sky-500 text-white' : 'text-gray-400 hover:bg-gray-700'; ?>"><?php echo htmlspecialchars($settings_data['filter_all']); ?></a>
                    <a href="?filter=members" class="px-4 py-2 font-medium rounded-t-lg focus:outline-none <?php echo $filter === 'members' ? 'bg-sky-500 text-white' : 'text-gray-400 hover:bg-gray-700'; ?>"><?php echo htmlspecialchars($settings_data['filter_members']); ?></a>
                    <a href="?filter=visitors" class="px-4 py-2 font-medium rounded-t-lg focus:outline-none <?php echo $filter === 'visitors' ? 'bg-sky-500 text-white' : 'text-gray-400 hover:bg-gray-700'; ?>"><?php echo htmlspecialchars($settings_data['filter_visitors']); ?></a>
                    <a href="?filter=bots" class="px-4 py-2 font-medium rounded-t-lg focus:outline-none <?php echo $filter === 'bots' ? 'bg-sky-500 text-white' : 'text-gray-400 hover:bg-gray-700'; ?>"><?php echo htmlspecialchars($settings_data['filter_bots']); ?></a>
                </div>

                <div class="space-y-4">
                    <?php if (empty($top_items)): ?>
                        <p class="text-gray-400 text-center py-8"><?php echo htmlspecialchars($settings_data['no_data_for_filter']); ?></p>
                    <?php else: ?>
                        <?php foreach ($top_items as $item): ?>
                            <div class="bg-gray-900/50 p-4 rounded-lg flex justify-between items-center">
                                <div>
                                    <span class="text-xs font-semibold uppercase px-2 py-1 rounded-full <?php echo $item['type'] === 'Post' ? 'bg-sky-500/50 text-sky-200' : 'bg-blue-500/50 text-blue-200'; ?>">
                                        <?php echo $item['type']; ?>
                                    </span>
                                    <p class="text-lg text-white mt-2"><?php echo htmlspecialchars($item['title']); ?></p>
                                </div>
                                <p class="text-2xl font-bold text-green-400"><?php echo $item['views']; ?> <?php echo htmlspecialchars($settings_data['stats_views']); ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <div class="flex justify-center items-center gap-4 mt-8">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?filter=<?php echo $filter; ?>&page=<?php echo $i; ?>" class="px-4 py-2 rounded-lg <?php echo $i === $page ? 'bg-sky-500 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600'; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </div>
            </div>
        </div>
    </section>
</main>
<?php require_once FOOTER; ?>
