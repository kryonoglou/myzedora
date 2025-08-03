<?php

require_once dirname(__DIR__) . '/includes/map.php';

if (file_exists(dirname(__DIR__) . '/includes/log_visit.php')) {
    require_once dirname(__DIR__) . '/includes/log_visit.php';
}


$post = null;
$show_404 = false;
$slug = $_GET['slug'] ?? null;

if (empty($slug)) {
    $show_404 = true;
} else {
    $stmt = $pdo->prepare("SELECT posts.*, users.full_name AS author_name, users.username AS author_username, users.profile_image_url AS author_image, posts.comments_enabled FROM posts JOIN users ON posts.user_id = users.id WHERE posts.slug = ? AND posts.is_published = 1");
    $stmt->execute([$slug]);
    $post = $stmt->fetch();
    if (!$post) {
        $show_404 = true;
    } else {
        if (function_exists('log_page_visit')) {
            log_page_visit($pdo, 'post', $post['id']);
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment_text']) && isset($_SESSION['user_id']) && $post) {
    $comment_text = trim($_POST['comment_text']);
    $user_id = $_SESSION['user_id'];
    $post_id = $post['id'];
    $comment_error = '';
    
    if (empty($comment_text)) {
        $comment_error = htmlspecialchars($settings_data['comments_error_empty']);
    }

    if (empty($comment_error)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO comments (post_id, user_id, comment_text) VALUES (?, ?, ?)");
            $stmt->execute([$post_id, $user_id, $comment_text]);
            header("Location: " . POST_URL_BASE . urlencode($post['slug']) . "#comments");
            exit();
        } catch (PDOException $e) {
        }
    }
}

if (isset($_GET['delete_comment_id']) && isset($_SESSION['user_id']) && $post) {
    $comment_id_to_delete = filter_input(INPUT_GET, 'delete_comment_id', FILTER_VALIDATE_INT);
    $user_id = $_SESSION['user_id'];
    $is_admin = $_SESSION['is_admin'] ?? false;

    if ($comment_id_to_delete) {
        $stmt_check = $pdo->prepare("SELECT user_id FROM comments WHERE id = ? AND post_id = ?");
        $stmt_check->execute([$comment_id_to_delete, $post['id']]);
        $comment_owner_id = $stmt_check->fetchColumn();
        
        if ($is_admin || ($comment_owner_id && $comment_owner_id == $user_id)) {
            $stmt_delete = $pdo->prepare("DELETE FROM comments WHERE id = ?");
            $stmt_delete->execute([$comment_id_to_delete]);
        }
    }
    header("Location: " . POST_URL_BASE . urlencode($post['slug']) . "#comments");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_comment_id']) && isset($_POST['edited_comment_text']) && isset($_SESSION['user_id']) && $post) {
    $comment_id_to_edit = filter_input(INPUT_POST, 'edit_comment_id', FILTER_VALIDATE_INT);
    $edited_comment_text = trim($_POST['edited_comment_text']);
    $user_id = $_SESSION['user_id'];
    $is_admin = $_SESSION['is_admin'] ?? false;

    if ($comment_id_to_edit && !empty($edited_comment_text)) {
        $stmt_check = $pdo->prepare("SELECT user_id FROM comments WHERE id = ? AND post_id = ?");
        $stmt_check->execute([$comment_id_to_edit, $post['id']]);
        $comment_owner_id = $stmt_check->fetchColumn();

        if ($is_admin || ($comment_owner_id && $comment_owner_id == $user_id)) {
            $stmt_update = $pdo->prepare("UPDATE comments SET comment_text = ? WHERE id = ?");
            $stmt_update->execute([$edited_comment_text, $comment_id_to_edit]);
        }
    }
    header("Location: " . POST_URL_BASE . urlencode($post['slug']) . "#comments");
    exit();
}

if ($post) {
    $page_title = $post['title'] . " - " . $settings_data['seo_title'];
    $published_date = date("F j, Y", strtotime($post['published_at']));
    $author_image = !empty($post['author_image']) ? $post['author_image'] : 'https://placehold.co/48x48/1f2937/38bdf8?text=' . strtoupper(substr($post['author_name'], 0, 2));
}

require_once HEADER;
?>

<main class="pt-32 pb-20 bg-gray-900/30">
    <?php if ($show_404): ?>
        <?php require_once NOT_FOUND_PAGE; ?>
    <?php else: ?>
        <article class="container mx-auto px-6 max-w-4xl" data-aos="fade-up">
            <header class="mb-12 text-center">
                <h1 class="text-4xl md:text-5xl font-bold text-white leading-tight"><?php echo htmlspecialchars($post['title']); ?></h1>
                <div class="mt-6 flex items-center justify-center gap-4">
                    <img class="h-12 w-12 rounded-full object-cover" src="<?php echo htmlspecialchars($author_image); ?>" alt="Author image">
                    <div>
                        <p class="text-lg font-semibold text-white"><a href="<?php echo PROFILE_URL_BASE . urlencode($post['author_username']); ?>" class="hover:underline"><?php echo htmlspecialchars($post['author_name']); ?></a></p>
                        <p class="text-gray-400"><?php echo $published_date; ?></p>
                    </div>
                </div>
            </header>
            <div class="max-w-none mx-auto text-gray-300 leading-relaxed">
                <?php echo nl2br(htmlspecialchars_decode($post['content'])); ?>
            </div>
            <div class="text-center mt-16">
                <a href="<?php echo POSTS_PAGE_URL; ?>" class="text-sky-400 hover:underline">&larr; <?php echo htmlspecialchars($settings_data['back_to_all_posts']); ?></a>
            </div>
        </article>
        
        <?php if ($post['comments_enabled']): ?>
        <section id="comments" class="container mx-auto px-6 mt-16 max-w-4xl" data-aos="fade-up">
            <h2 class="text-3xl font-bold text-white mb-8 section-title"><?php echo htmlspecialchars($settings_data['comments_title']); ?></h2>
            
            <div class="bg-gray-800/50 p-8 rounded-lg shadow-lg mb-8">
                <h3 class="text-xl font-bold text-white mb-4"><?php echo htmlspecialchars($settings_data['comments_leave_a_comment']); ?></h3>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <?php if (!empty($comment_error)): ?><div class="bg-red-500/20 text-red-300 p-4 rounded-lg mb-4"><p><?php echo $comment_error; ?></p></div><?php endif; ?>
                    <form action="<?php echo POST_URL_BASE . urlencode($post['slug']); ?>#comments" method="POST">
                        <textarea name="comment_text" rows="4" placeholder="<?php echo htmlspecialchars($settings_data['comments_placeholder']); ?>" class="w-full bg-gray-700 border border-gray-600 rounded-lg py-2 px-4 text-white focus:outline-none focus:ring-2 focus:ring-sky-500" required></textarea>
                        <button type="submit" class="mt-4 bg-sky-500 text-white font-semibold py-2 px-6 rounded-lg hover:bg-sky-600 transition-colors duration-300"><?php echo htmlspecialchars($settings_data['comments_post_btn']); ?></button>
                    </form>
                <?php else: ?>
                    <p class="text-gray-400 text-center"><?php echo htmlspecialchars($settings_data['comments_login_to_comment']); ?></p>
                    <div class="text-center mt-4">
                        <a href="<?php echo LOGIN_URL; ?>" class="inline-block bg-sky-500 text-white font-semibold py-2 px-6 rounded-lg hover:bg-sky-600 transition-colors duration-300"><?php echo htmlspecialchars($settings_data['menu_login']); ?></a>
                    </div>
                <?php endif; ?>
            </div>

            <?php
            $comments_page = isset($_GET['comments_page']) && is_numeric($_GET['comments_page']) ? (int)$_GET['comments_page'] : 1;
            $comments_limit = 7;
            $comments_offset = ($comments_page - 1) * $comments_limit;

            $total_comments_stmt = $pdo->prepare("SELECT COUNT(id) FROM comments WHERE post_id = ?");
            $total_comments_stmt->execute([$post['id']]);
            $total_comments = $total_comments_stmt->fetchColumn();
            $total_comment_pages = ceil($total_comments / $comments_limit);

            $comments_stmt = $pdo->prepare("
                SELECT c.id, c.comment_text, c.created_at, u.id AS user_id, u.full_name, u.username, u.profile_image_url, u.is_admin
                FROM comments c
                JOIN users u ON c.user_id = u.id
                WHERE c.post_id = :post_id
                ORDER BY c.created_at DESC
                LIMIT :limit OFFSET :offset
            ");
            $comments_stmt->bindValue(':post_id', $post['id'], PDO::PARAM_INT);
            $comments_stmt->bindValue(':limit', $comments_limit, PDO::PARAM_INT);
            $comments_stmt->bindValue(':offset', $comments_offset, PDO::PARAM_INT);
            $comments_stmt->execute();
            $comments = $comments_stmt->fetchAll();
            ?>

            <?php if (empty($comments) && $comments_page === 1): ?>
                <p class="text-center text-gray-400 mb-8"><?php echo htmlspecialchars($settings_data['comments_none']); ?></p>
            <?php else: ?>
                <div class="space-y-6">
                    <?php foreach ($comments as $comment): ?>
                        <div class="bg-gray-800/50 p-6 rounded-lg" x-data="{ editing: false }">
                            <div class="flex items-start justify-between">
                                <div class="flex items-center mb-4">
                                    <?php
                                    $commenter_image = !empty($comment['profile_image_url'])
                                        ? $comment['profile_image_url']
                                        : 'https://placehold.co/48x48/1f2937/38bdf8?text=' . strtoupper(substr($comment['full_name'], 0, 2));
                                    ?>
                                    <img class="h-10 w-10 rounded-full object-cover mr-4" src="<?php echo htmlspecialchars($commenter_image); ?>" alt="Profile image of <?php echo htmlspecialchars($comment['full_name']); ?>">
                                    <div>
                                        <p class="text-white font-semibold">
                                            <a href="<?php echo PROFILE_URL_BASE . urlencode($comment['username']); ?>" class="hover:underline">
                                                <?php echo htmlspecialchars($comment['full_name']); ?>
                                            </a>
                                        </p>
                                        <p class="text-gray-400 text-sm"><?php echo date("F j, Y, g:i a", strtotime($comment['created_at'])); ?></p>
                                    </div>
                                </div>
                                <?php if (isset($_SESSION['user_id']) && ($_SESSION['is_admin'] || $_SESSION['user_id'] == $comment['user_id'])): ?>
                                <div class="flex gap-2">
                                    <button @click="editing = true" class="text-sky-400 hover:text-sky-300 font-semibold text-sm"><?php echo htmlspecialchars($settings_data['comments_edit_btn']); ?></button>
                                    <a href="<?php echo POST_URL_BASE . urlencode($post['slug']) . (strpos(POST_URL_BASE, '?') === false ? '?' : '&') . 'delete_comment_id=' . $comment['id']; ?>" class="text-red-400 hover:text-red-300 font-semibold text-sm" onclick="return confirm('<?php echo htmlspecialchars($settings_data['comments_delete_confirm']); ?>');"><?php echo htmlspecialchars($settings_data['comments_delete_btn']); ?></a>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <p x-show="!editing" class="text-gray-300 leading-relaxed mt-2"><?php echo nl2br(htmlspecialchars($comment['comment_text'])); ?></p>

                            <form x-show="editing" method="POST" action="<?php echo POST_URL_BASE . urlencode($post['slug']); ?>#comments">
                                <input type="hidden" name="edit_comment_id" value="<?php echo $comment['id']; ?>">
                                <textarea name="edited_comment_text" rows="4" class="w-full bg-gray-700 border border-gray-600 rounded-lg py-2 px-4 text-white focus:outline-none focus:ring-2 focus:ring-sky-500" required><?php echo htmlspecialchars($comment['comment_text']); ?></textarea>
                                <div class="flex gap-2 mt-2">
                                    <button type="submit" class="bg-green-600 text-white px-3 py-1 text-sm rounded-md hover:bg-green-700"><?php echo htmlspecialchars($settings_data['comments_save_btn']); ?></button>
                                    <button type="button" @click="editing = false" class="bg-gray-600 text-white px-3 py-1 text-sm rounded-md hover:bg-gray-700"><?php echo htmlspecialchars($settings_data['comments_cancel_btn']); ?></button>
                                </div>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($total_comment_pages > 1): ?>
                <div class="flex justify-center items-center gap-4 mt-12">
                    <?php for ($i = 1; $i <= $total_comment_pages; $i++): ?>
                        <?php
                            $base_url = POST_URL_BASE . urlencode($post['slug']);
                            $separator = (strpos($base_url, '?') === false) ? '?' : '&';
                            $page_link = $base_url . $separator . 'comments_page=' . $i . '#comments';
                        ?>
                        <a href="<?php echo $page_link; ?>" class="px-4 py-2 rounded-lg <?php echo $i === $comments_page ? 'bg-sky-500 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600'; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </div>
                <?php endif; ?>

            <?php endif; ?>
        </section>
        <?php endif; ?>

    <?php endif; ?>
</main>

<style>
.prose p { margin-bottom: 1.25em; }
.prose h2 { font-size: 1.875rem; margin-top: 2em; margin-bottom: 1em; font-weight: bold; color: white; }
.prose h3 { font-size: 1.5rem; margin-top: 1.6em; margin-bottom: 0.6em; font-weight: bold; color: white; }
.prose ul { list-style-type: disc; padding-left: 1.5em; margin-bottom: 1.25em; }
.prose a { color: #38bdf8; text-decoration: underline; }
.prose b, .prose strong { color: white; font-weight: bold; }
</style>

<?php
require_once FOOTER;
?>