<?php
require_once __DIR__ . '/includes/map.php';

$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$total_posts = $pdo->query("SELECT COUNT(id) FROM posts WHERE is_published = 1")->fetchColumn();
$total_pages = ceil($total_posts / $limit);

$stmt = $pdo->prepare("
    SELECT posts.*, users.full_name AS author_name, users.username AS author_username, users.profile_image_url AS author_image
    FROM posts 
    JOIN users ON posts.user_id = users.id 
    WHERE posts.is_published = 1 
    ORDER BY posts.published_at DESC 
    LIMIT :limit OFFSET :offset
");
$stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$posts = $stmt->fetchAll();

require_once HEADER;
?>
<main class="pt-32 pb-20">
    <section id="all-posts" data-aos="fade-up">
        <div class="container mx-auto px-6 max-w-4xl">
            <h1 class="text-4xl font-bold text-center mb-10 section-title"><?php echo htmlspecialchars($settings_data['blog_title']); ?></h1>

            <div class="space-y-8">
                <?php if (empty($posts)): ?>
                    <p class="text-center text-gray-400"><?php echo htmlspecialchars($settings_data['no_posts_yet']); ?></p>
                <?php else: ?>
                    <?php foreach ($posts as $post): ?>
                        <div class="bg-gray-800/50 p-6 rounded-lg flex flex-col md:flex-row items-center md:items-start gap-6" data-aos="fade-up">
                            <div class="w-full md:w-1/4 flex-shrink-0 flex flex-col items-center text-center md:text-left">
                                <?php
                                $author_image = !empty($post['author_image'])
                                    ? htmlspecialchars($post['author_image'])
                                    : 'https://placehold.co/64x64/1f2937/38bdf8?text=' . strtoupper(substr($post['author_name'], 0, 2));
                                ?>
                                <img src="<?php echo $author_image; ?>" alt="Profile image of <?php echo htmlspecialchars($post['author_name']); ?>" class="w-16 h-16 rounded-full mb-3 object-cover">
                                <p class="text-gray-500 text-sm"><?php echo date("F j, Y", strtotime($post['published_at'])); ?></p>
                                <p class="text-gray-400 text-sm mt-1"><?php echo htmlspecialchars($settings_data['by_author']); ?> <a href="<?php echo PROFILE_URL_BASE . urlencode($post['author_username']); ?>" class="hover:underline"><?php echo htmlspecialchars($post['author_name']); ?></a></p>
                            </div>
                            <div class="w-full md:w-3/4">
                                <h3 class="text-2xl font-bold text-white mb-3 text-center md:text-left">
                                    <a href="<?php echo POST_URL_BASE . urlencode($post['slug']); ?>" class="hover:text-sky-400 transition-colors duration-300">
                                        <?php echo htmlspecialchars($post['title']); ?>
                                    </a>
                                </h3>
                                <p class="text-gray-400 mb-4"><?php echo strip_tags(nl2br($post['excerpt']), '<b><strong><i><em><br>'); ?></p>
                                <a href="<?php echo POST_URL_BASE . urlencode($post['slug']); ?>" class="text-sky-400 hover:text-sky-300 font-semibold"><?php echo htmlspecialchars($settings_data['read_more_btn']); ?></a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

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