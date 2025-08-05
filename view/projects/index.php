<?php

require_once dirname(__DIR__, 2) . '/includes/map.php';

function truncate_text($text, $length) {
    if (strlen($text) > $length) {
        $text = substr($text, 0, $length);
        $text = substr($text, 0, strrpos($text, ' '));
        $text .= '...';
    }
    return $text;
}

$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 6;
$offset = ($page - 1) * $limit;

$total_projects = $pdo->query("SELECT COUNT(id) FROM projects WHERE is_published = 1")->fetchColumn();
$total_pages = ceil($total_projects / $limit);

$stmt = $pdo->prepare("
    SELECT *
    FROM projects
    WHERE is_published = 1
    ORDER BY display_order ASC, created_at DESC
    LIMIT :limit OFFSET :offset
");
$stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$projects = $stmt->fetchAll();

$page_title = $settings_data['portfolio_title'] . " - " . $settings_data['seo_title'];

require_once HEADER;
?>
<main class="pt-32 pb-20">
    <section id="all-projects" data-aos="fade-up">
        <div class="container mx-auto px-6">
            <h1 class="text-4xl font-bold text-center mb-12 section-title"><?php echo htmlspecialchars($settings_data['portfolio_title']); ?></h1>

            <?php if (empty($projects)): ?>
                <p class="text-center text-gray-400">No projects have been added yet.</p>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-10">
                    <?php foreach ($projects as $project): ?>
                        <div class="card bg-gray-800 rounded-lg overflow-hidden lg:max-w-sm flex flex-col" data-aos="fade-up">
                            <?php if (!empty($project['image_url'])): ?>
                                <div class="card-image-wrapper">
                                   <img src="<?php echo htmlspecialchars($project['image_url']); ?>" alt="<?php echo htmlspecialchars($project['title']); ?>" class="w-full h-48 object-cover">
                                </div>
                            <?php endif; ?>
                            <div class="p-6 flex flex-col flex-grow">
                                <h3 class="text-xl font-bold text-white mb-2"><?php echo htmlspecialchars($project['title']); ?></h3>
                                <p class="text-gray-400 mb-4 flex-grow"><?php echo truncate_text(strip_tags($project['description']), 150); ?></p>
                                <a href="<?php echo PROJECT_URL_BASE . urlencode($project['slug']); ?>" class="text-sky-400 hover:text-sky-300 font-semibold"><?php echo htmlspecialchars($settings_data['view_details_btn']); ?></a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="flex justify-center items-center gap-4 mt-12">
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