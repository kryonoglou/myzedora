<?php

require_once dirname(__DIR__, 2) . '/includes/map.php';

$project = null;
$show_404 = false;

if (isset($_GET['slug']) && !empty(trim($_GET['slug']))) {
    $project_slug = trim($_GET['slug']);
    $stmt = $pdo->prepare("SELECT * FROM projects WHERE slug = ?");
    $stmt->execute([$project_slug]);
    $project = $stmt->fetch();

    if (!$project) {
        $show_404 = true;
    }
} else {
    $show_404 = true;
}

$page_title = $project ? htmlspecialchars($project['title']) . " - " . $settings_data['seo_title'] : '404 Not Found';

require_once HEADER;
?>

<main class="pt-32 pb-20">
    <?php if ($show_404): ?>
        <?php require_once NOT_FOUND_PAGE; ?>
    <?php else: ?>
        <section id="project-single" data-aos="fade-up">
            <div class="container mx-auto px-6 max-w-4xl">
                <div class="bg-gray-800/50 rounded-2xl shadow-lg overflow-hidden">
                    <?php if ($project['image_url']): ?>
                        <div class="card-image-wrapper h-80 overflow-hidden">
                            <img class="w-full h-full object-cover" src="<?php echo htmlspecialchars($project['image_url']); ?>" alt="<?php echo htmlspecialchars($project['title']); ?>">
                        </div>
                    <?php endif; ?>
                    <div class="p-8 md:p-12">
                        <h1 class="text-3xl md:text-4xl font-bold text-white mb-4"><?php echo htmlspecialchars($project['title']); ?></h1>
                        <div class="prose max-w-none text-gray-300 leading-relaxed">
                            <?php 
                                echo apply_filters('the_content', nl2br($project['description'])); 
                            ?>
                        </div>

                        <?php 
                        $technologies = !empty($project['technologies']) ? array_map('trim', explode(',', $project['technologies'])) : [];
                        if (!empty($technologies)):
                        ?>
                        <div class="mt-8">
                            <h3 class="text-sm font-bold uppercase text-gray-400 tracking-wider mb-4"><?php echo htmlspecialchars($settings_data['been_used_for_project']); ?></h3>
                            <div class="flex flex-wrap gap-3">
                                <?php foreach ($technologies as $tech): ?>
                                    <span class="bg-sky-500/50 text-sky-200 text-xs font-semibold px-3 py-1 rounded-full"><?php echo trim(htmlspecialchars($tech)); ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="flex flex-col sm:flex-row items-center gap-4 mt-8">
                            <a href="<?php echo HOME_URL; ?>#portfolio" class="w-full sm:w-auto bg-gray-700 text-white font-semibold py-3 px-6 rounded-lg hover:bg-gray-600 transition-colors duration-300 text-center">
                                &larr; <?php echo htmlspecialchars($settings_data['back_to_portfolio']); ?>
                            </a>
                            <?php if ($project['project_url']): ?>
                                <a href="<?php echo htmlspecialchars($project['project_url']); ?>" target="_blank" class="w-full sm:w-auto bg-sky-500 text-white font-semibold py-3 px-6 rounded-lg hover:bg-sky-600 transition-colors duration-300 text-center">
                                    <?php echo htmlspecialchars($settings_data['visit_project']); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    <?php endif; ?>
</main>
<?php require_once FOOTER; ?>