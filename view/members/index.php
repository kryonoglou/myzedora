<?php
session_start();
require_once dirname(__DIR__, 2) . '/includes/map.php';

$visibility = $settings_data['members_page_visibility'] ?? 'members';

if ($visibility === 'members' && !isset($_SESSION['user_id'])) {
    header("Location: " . HOME_URL);
    exit();
} elseif ($visibility === 'admins' && (!isset($_SESSION['user_id']) || !$_SESSION['is_admin'])) {
    header("Location: " . HOME_URL);
    exit();
}

try {
    $search_term = trim($_GET['search'] ?? '');
    $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 10;
    $offset = ($page - 1) * $limit;

    $count_query = "SELECT COUNT(id) FROM users";
    $select_query = "SELECT id, full_name, username, email, created_at, profile_image_url FROM users";
    $params = [];

    if (!empty($search_term)) {
        $where_clause = " WHERE full_name LIKE ? OR username LIKE ? OR email LIKE ?";
        $count_query .= $where_clause;
        $select_query .= $where_clause;
        $search_param = '%' . $search_term . '%';
        $params = [$search_param, $search_param, $search_param];
    }

    $total_stmt = $pdo->prepare($count_query);
    $total_stmt->execute($params);
    $total_users = $total_stmt->fetchColumn();
    $total_pages = ceil($total_users / $limit);

    $select_query .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;

    $stmt = $pdo->prepare($select_query);
    $stmt->execute($params);
    $members = $stmt->fetchAll();

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

require_once HEADER;
?>
<script>
    if (history.scrollRestoration) {
        history.scrollRestoration = 'manual';
    }
    window.scrollTo(0, 0);
</script>
<main class="pt-32 pb-20">
    <section id="member-list" data-aos="fade-up">
        <div class="container mx-auto px-6 max-w-5xl">
            <h1 class="text-4xl font-bold text-center mb-10 section-title"><?php echo htmlspecialchars($settings_data['members_list_title']); ?></h1>

            <form method="GET" action="<?php echo MEMBERS_PLIST_URL; ?>" class="mb-8 max-w-lg mx-auto">
                <div class="relative">
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search_term); ?>" placeholder="<?php echo htmlspecialchars($settings_data['members_search_placeholder']); ?>" class="w-full bg-gray-700 border border-gray-600 rounded-lg py-3 px-4 text-white focus:outline-none focus:ring-2 focus:ring-sky-500">
                    <button type="submit" class="absolute right-2 top-1/2 -translate-y-1/2 bg-sky-500 text-white px-4 py-1.5 rounded-md hover:bg-sky-600"><?php echo htmlspecialchars($settings_data['members_search_btn']); ?></button>
                </div>
            </form>

            <?php if (empty($members)): ?>
                <div class="bg-gray-800/50 p-8 rounded-lg shadow-lg text-center text-gray-400">
                    <p><?php echo htmlspecialchars($settings_data['members_no_members_found']); ?></p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($members as $member): ?>
                        <?php
                            $profile_image_src = !empty($member['profile_image_url']) ? htmlspecialchars($member['profile_image_url']) : '/img/notset.webp';
                        ?>
                        <div class="bg-gray-800/50 p-6 rounded-lg shadow-lg text-center transform transition-all duration-300 ease-in-out hover:scale-105 hover:shadow-2xl">
                            <img src="<?php echo $profile_image_src; ?>" alt="<?php echo htmlspecialchars($member['full_name']); ?>'s Profile" class="w-24 h-24 rounded-full mx-auto mb-4 border-2 border-sky-500 object-cover">
                            <h3 class="text-xl font-bold text-white mb-1"><?php echo htmlspecialchars($member['full_name']); ?></h3>
                            <p class="text-sky-400 mb-2">@<?php echo htmlspecialchars($member['username']); ?></p>
                            <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                                <p class="text-gray-400 text-sm mb-1"><?php echo htmlspecialchars($member['email']); ?></p>
                            <?php endif; ?>
                            <p class="text-gray-500 text-xs mt-4">Joined: <?php echo date('F j, Y', strtotime($member['created_at'])); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="flex justify-center items-center gap-4 mt-8">
                <?php if ($total_pages > 1): ?>
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?search=<?php echo htmlspecialchars($search_term); ?>&page=<?php echo $i; ?>" class="px-4 py-2 rounded-lg <?php echo $i === $page ? 'bg-sky-500 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600'; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>
</main>
<?php require_once FOOTER; ?>