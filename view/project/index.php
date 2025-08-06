<?php
require_once dirname(__DIR__, 2) . '/includes/map.php';

$project = null;
$show_404 = false;
$slug = $_GET['slug'] ?? null;

if (empty($slug)) {
    $show_404 = true;
} else {
    $stmt = $pdo->prepare("SELECT * FROM projects WHERE slug = ? AND is_published = 1");
    $stmt->execute([$slug]);
    $project = $stmt->fetch();
    if (!$project) {
        $show_404 = true;
    } else {
        if (function_exists('log_page_visit')) {
            log_page_visit($pdo, 'project', $project['id']);
        }
    }
}

if ($project) {
    $page_title = $project['title'] . " - " . $settings_data['seo_title'];
}

require_once HEADER;
?>

<main class="pt-32 pb-20 bg-gray-900/30">
    <?php if ($show_404): ?>
        <?php require_once NOT_FOUND_PAGE; ?>
    <?php else: ?>
        <article class="container mx-auto px-6 max-w-4xl" data-aos="fade-up">
            <header class="mb-12">
                <h1 class="text-4xl md:text-5xl font-bold text-white leading-tight"><?php echo htmlspecialchars($project['title']); ?></h1>
            </header>
            
            <?php if (!empty($project['image_url'])): ?>
                <div class="mb-12">
                    <img src="<?php echo htmlspecialchars($project['image_url']); ?>" alt="<?php echo htmlspecialchars($project['title']); ?>" class="rounded-lg shadow-xl w-full h-auto">
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-12">
                <div class="md:col-span-2 text-gray-300 leading-relaxed prose">
                    <h2 class="text-2xl font-bold text-white mb-4"><?php echo htmlspecialchars($settings_data['about_this_project']); ?></h2>
                    <?php echo nl2br(htmlspecialchars($project['description'])); ?>
                </div>
                <aside>
                    <div class="bg-gray-800/50 p-6 rounded-lg">
                        <h3 class="text-xl font-bold text-white mb-4"><?php echo htmlspecialchars($settings_data['been_used_for_project']); ?></h3>
                        <div class="flex flex-wrap gap-2">
                            <?php 
                            $technologies = array_map('trim', explode(',', $project['technologies']));
                            foreach ($technologies as $tech): 
                            ?>
                                <span class="bg-sky-500/20 text-sky-300 text-sm font-semibold px-3 py-1 rounded-full"><?php echo htmlspecialchars($tech); ?></span>
                            <?php endforeach; ?>
                        </div>
                        <?php if (!empty($project['project_url'])): ?>
                        <a href="<?php echo htmlspecialchars($project['project_url']); ?>" target="_blank" rel="noopener noreferrer" class="inline-block mt-6 bg-sky-500 text-white font-semibold w-full text-center py-3 rounded-lg hover:bg-sky-600 transition-colors duration-300">
                            <?php echo htmlspecialchars($settings_data['visit_project']); ?>
                        </a>
                        <?php endif; ?>
                    </div>
                </aside>
            </div>
            
            <div class="text-center mt-16 flex justify-center items-center gap-6">
                <a href="<?php echo PROJECTS_PAGE_URL; ?>" class="text-sky-400 hover:underline">
                    &larr; <?php echo htmlspecialchars($settings_data['back_to_portfolio']); ?>
                </a>

                <?php if (isset($_SESSION['user_id']) && $_SESSION['is_admin']): ?>
                    <a href="<?php echo EDIT_PROJECT_URL_BASE . $project['id']; ?>" class="text-green-400 hover:underline">
                        Edit Project
                    </a>
                <?php endif; ?>
            </div>

        </article>
    <?php endif; ?>
</main>

<style>
.prose p { margin-bottom: 1em; }
</style>

<?php
require_once FOOTER;
?>