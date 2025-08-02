<?php
require_once dirname(__DIR__) . '/includes/map.php';

if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: " . HOME_URL);
    exit();
}

$total_members = $pdo->query("SELECT COUNT(id) FROM users")->fetchColumn();
$total_posts = $pdo->query("SELECT COUNT(id) FROM posts")->fetchColumn();
$total_projects = $pdo->query("SELECT COUNT(id) FROM projects")->fetchColumn();
$total_visits = $pdo->query("SELECT COUNT(DISTINCT visitor_id) FROM statistics WHERE is_bot = 0")->fetchColumn();

$latest_members = $pdo->query("SELECT full_name, created_at FROM users ORDER BY created_at DESC LIMIT 5")->fetchAll();

$views_query = $pdo->query("
    SELECT page_type, item_id, COUNT(id) as view_count
    FROM statistics 
    WHERE view_date >= DATE_SUB(NOW(), INTERVAL 30 DAY) AND is_bot = 0
    GROUP BY page_type, item_id 
    ORDER BY view_count DESC
    LIMIT 5
");
$top_5_items_raw = $views_query->fetchAll();

$top_items = [];
foreach ($top_5_items_raw as $view) {
    $title = 'N/A';
    if ($view['page_type'] === 'post' && $view['item_id']) {
        $title = $pdo->query("SELECT title FROM posts WHERE id = {$view['item_id']}")->fetchColumn();
    } elseif ($view['page_type'] === 'project' && $view['item_id']) {
        $title = $pdo->query("SELECT title FROM projects WHERE id = {$view['item_id']}")->fetchColumn();
    } elseif ($view['page_type'] === 'home') {
        $title = $settings_data['stats_homepage'];
    }
    $top_items[] = ['title' => $title ?: 'Deleted Item', 'type' => ucfirst($view['page_type']), 'views' => $view['view_count']];
}

$page_title = $settings_data['stats_dashboard_title'] . " - " . $settings_data['seo_title'];

require_once HEADER;
?>
<main class="pt-32 pb-20">
    <section id="statistics" data-aos="fade-up">
        <div class="container mx-auto px-6">
            <h1 class="text-4xl font-bold text-center mb-10 section-title"><?php echo htmlspecialchars($settings_data['stats_dashboard_title']); ?></h1>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-12 text-center">
                <div class="bg-gray-800/50 p-6 rounded-lg"><p class="text-4xl font-bold text-sky-400"><?php echo $total_visits; ?></p><p class="text-gray-400 mt-2"><?php echo htmlspecialchars($settings_data['stats_key_visitors']); ?></p></div>
                <div class="bg-gray-800/50 p-6 rounded-lg"><p class="text-4xl font-bold text-sky-400"><?php echo $total_members; ?></p><p class="text-gray-400 mt-2"><?php echo htmlspecialchars($settings_data['stats_key_members']); ?></p></div>
                <div class="bg-gray-800/50 p-6 rounded-lg"><p class="text-4xl font-bold text-sky-400"><?php echo $total_posts; ?></p><p class="text-gray-400 mt-2"><?php echo htmlspecialchars($settings_data['stats_key_posts']); ?></p></div>
                <div class="bg-gray-800/50 p-6 rounded-lg"><p class="text-4xl font-bold text-sky-400"><?php echo $total_projects; ?></p><p class="text-gray-400 mt-2"><?php echo htmlspecialchars($settings_data['stats_key_projects']); ?></p></div>
            </div>
            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <div class="lg:col-span-1 bg-gray-800/50 p-8 rounded-lg shadow-lg">
                    <h2 class="text-2xl font-bold text-white mb-6"><?php echo htmlspecialchars($settings_data['stats_latest_members_title']); ?></h2>
                    <div class="space-y-4">
                        <?php foreach($latest_members as $member): ?>
                        <div class="flex justify-between items-center bg-gray-900/50 p-3 rounded-md">
                            <span class="text-white"><?php echo htmlspecialchars($member['full_name']); ?></span>
                            <span class="text-sm text-gray-400"><?php echo date("M j, Y", strtotime($member['created_at'])); ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <a href="<?php echo MEMBERS_LIST_URL; ?>" class="block w-full text-center bg-sky-500 text-white font-semibold py-2 mt-6 rounded-lg hover:bg-sky-600 transition-colors duration-300"><?php echo htmlspecialchars($settings_data['view_all_members_btn']); ?></a>
                </div>

                <div class="lg:col-span-2 bg-gray-800/50 p-8 rounded-lg shadow-lg">
                    <h2 class="text-2xl font-bold text-white mb-6"><?php echo htmlspecialchars($settings_data['stats_top_content_title']); ?></h2>
                    <div class="space-y-4">
                        <?php if (empty($top_items)): ?>
                            <p class="text-gray-400 text-center py-8">No viewing data available yet.</p>
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
                    <a href="<?php echo FULL_STATS_URL; ?>" class="block w-full text-center bg-blue-500 text-white font-semibold py-2 mt-6 rounded-lg hover:bg-blue-600 transition-colors duration-300"><?php echo htmlspecialchars($settings_data['view_full_report_btn']); ?></a>
                </div>
            </div>
        </div>
    </section>
</main>
<?php require_once FOOTER; ?>
