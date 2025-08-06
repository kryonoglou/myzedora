<?php

if (!file_exists(__DIR__ . '/.env')) {
    header('Location: install/');
    exit();
}

require_once __DIR__ . '/includes/map.php';
require_once __DIR__ . '/includes/repair.php';

function truncate_text($text, $length) {
    if (strlen($text) > $length) {
        $text = substr($text, 0, $length);
        $text = substr($text, 0, strrpos($text, ' '));
        $text .= '...';
    }
    return $text;
}

$hero_bg_style = '';
if (!empty($settings_data['hero_background_url'])) {
    $bg_url = htmlspecialchars($settings_data['hero_background_url']);
    $hero_bg_style = "style=\"background-image: url('{$bg_url}'); background-size: cover; background-position: center; background-repeat: no-repeat;\"";
}

$projects_stmt = $pdo->query("SELECT * FROM projects WHERE is_published = 1 ORDER BY display_order ASC, created_at DESC LIMIT 3");
$projects = $projects_stmt->fetchAll();

$posts_stmt = $pdo->query("
    SELECT posts.*, users.full_name AS author_name, users.username AS author_username, users.profile_image_url AS author_image
    FROM posts
    JOIN users ON posts.user_id = users.id
    WHERE posts.is_published = 1
    ORDER BY posts.published_at DESC
    LIMIT 2
");
$posts = $posts_stmt->fetchAll();

$home_buttons_stmt = $pdo->query("SELECT * FROM home_buttons ORDER BY display_order ASC");
$home_buttons = $home_buttons_stmt->fetchAll();


require_once HEADER;
?>
<style>
<?php foreach ($home_buttons as $button): ?>
a.hero-button-<?php echo $button['id']; ?> {
    background-color: <?php echo htmlspecialchars($button['color']); ?>;
    transition: filter 0.3s ease;
}
a.hero-button-<?php echo $button['id']; ?>:hover {
    filter: brightness(110%);
}
<?php endforeach; ?>
</style>

    <main class="">
        <section id="home" class="min-h-screen flex items-center bg-transparent relative" <?php echo $hero_bg_style; ?>>
            <?php if (!empty($settings_data['hero_background_url'])): ?>
                <div class="absolute inset-0 bg-black/50"></div>
            <?php endif; ?>
            <div class="container mx-auto px-6 text-center relative z-10">
                <h1 class="text-4xl md:text-6xl font-bold text-white leading-tight mb-4" data-aos="fade-down">
                    <?php echo htmlspecialchars($settings_data['hero_title']); ?>
                </h1>
                <p class="text-xl md:text-2xl font-light text-sky-300 mb-8" data-aos="fade-up" data-aos-delay="200">
                    <?php echo htmlspecialchars($settings_data['hero_subtitle_prefix']); ?> <span id="typed-text"></span>
                </p>
                <div data-aos="fade-up" data-aos-delay="400" class="flex flex-col md:flex-row items-center justify-center gap-4">
                    <?php foreach ($home_buttons as $button): ?>
                        <a href="<?php echo htmlspecialchars($button['url']); ?>"
                           class="hero-button-<?php echo $button['id']; ?> text-white font-semibold px-8 py-3 rounded-lg transition-all duration-300 transform hover:scale-105"
                           <?php if ($button['new_tab']) echo 'target="_blank" rel="noopener noreferrer"'; ?>>
                            <?php echo htmlspecialchars($button['text']); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section id="about" class="py-20 md:py-32 bg-gray-900/30">
            <div class="container mx-auto px-6" data-aos="fade-up">
                <div class="max-w-4xl mx-auto text-center">
                    <h2 class="text-3xl md:text-4xl font-bold mb-6 section-title"><?php echo htmlspecialchars($settings_data['about_title']); ?></h2>
                    <div class="text-lg text-gray-300 leading-relaxed text-left prose prose-invert max-w-none">
                        <?php echo nl2br($settings_data['about_content'] ?? ''); ?>
                    </div>
                </div>
            </div>
        </section>

        <section id="portfolio" class="py-20 md:py-32 bg-transparent">
            <div class="container mx-auto px-6">
                <h2 class="text-3xl md:text-4xl font-bold text-center mb-12 section-title" data-aos="fade-up"><?php echo htmlspecialchars($settings_data['portfolio_title']); ?></h2>

                <?php
                $projectCount = count($projects);
                $portfolioContainerClasses = 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-10';
                if ($projectCount < 3) {
                    $portfolioContainerClasses = 'flex flex-wrap justify-center gap-10';
                }
                ?>
                <div class="<?php echo $portfolioContainerClasses; ?>">

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
                
                <div class="text-center mt-12" data-aos="fade-up">
                    <a href="<?php echo PROJECTS_PAGE_URL; ?>" class="bg-sky-500 text-white font-semibold px-8 py-3 rounded-lg hover:bg-sky-600 transition-colors duration-300">
                        <?php echo htmlspecialchars($settings_data['view_all_projects_btn']); ?>
                    </a>
                </div>
            </div>
        </section>

        <section id="blog" class="py-20 md:py-32 bg-gray-900/30">
            <div class="container mx-auto px-6">
                <h2 class="text-3xl md:text-4xl font-bold text-center mb-12 section-title" data-aos="fade-up"><?php echo htmlspecialchars($settings_data['blog_title']); ?></h2>
                <div class="max-w-4xl mx-auto space-y-8">

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
                                <h3 class="text-2xl font-bold text-white mb-3 text-center md:text-left"><a href="<?php echo POST_URL_BASE . urlencode($post['slug']); ?>" class="hover:text-sky-400"><?php echo htmlspecialchars($post['title']); ?></a></h3>
                                <p class="text-gray-400 mb-4"><?php echo strip_tags(nl2br($post['excerpt']), '<b><strong><i><em><br>'); ?></p>
                                <a href="<?php echo POST_URL_BASE . urlencode($post['slug']); ?>" class="text-sky-400 hover:text-sky-300 font-semibold"><?php echo htmlspecialchars($settings_data['read_more_btn']); ?></a>
                            </div>
                        </div>
                    <?php endforeach; ?>

                </div>

                <div class="text-center mt-12" data-aos="fade-up">
                    <a href="<?php echo POSTS_PAGE_URL; ?>" class="bg-sky-500 text-white font-semibold px-8 py-3 rounded-lg hover:bg-sky-600 transition-colors duration-300">
                        <?php echo htmlspecialchars($settings_data['view_all_posts_btn']); ?>
                    </a>
                </div>

            </div>
        </section>

        <section id="contact" class="py-20 md:py-32 bg-transparent">
            <div class="container mx-auto px-6" data-aos="fade-up">
                <div class="max-w-2xl mx-auto text-center">
                    <h2 class="text-3xl md:text-4xl font-bold mb-4 section-title"><?php echo htmlspecialchars($settings_data['contact_title']); ?></h2>
                    <p class="text-lg text-gray-300 mb-8">
                        <?php echo htmlspecialchars($settings_data['contact_subtitle']); ?>
                    </p>
                    <a href="mailto:<?php echo htmlspecialchars($settings_data['contact_email']); ?>" class="inline-block bg-sky-500 text-white font-semibold px-8 py-3 rounded-lg hover:bg-sky-600 transition-all duration-300 transform hover:scale-105">
                        <?php echo htmlspecialchars($settings_data['contact_button_text']); ?>
                    </a>
                </div>
            </div>
        </section>
    </main>

<?php require_once FOOTER; ?>