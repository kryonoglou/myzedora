<?php
require_once dirname(__DIR__) . '/includes/map.php';

if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: " . HOME_URL);
    exit();
}

try {
    $errors = [];
    $success_message = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_role'])) {
        $user_id_to_change = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
        $new_role = filter_input(INPUT_POST, 'role', FILTER_VALIDATE_INT);

        if ($user_id_to_change === $_SESSION['user_id']) {
            $errors[] = "You cannot change your own role.";
        } elseif ($user_id_to_change && ($new_role === 0 || $new_role === 1)) {
            $stmt = $pdo->prepare("UPDATE users SET is_admin = ? WHERE id = ?");
            if ($stmt->execute([$new_role, $user_id_to_change])) {
                $success_message = "User role updated successfully!";
            } else {
                $errors[] = "Failed to update user role.";
            }
        } else {
            $errors[] = "Invalid data provided for role change.";
        }
    }

    $search_term = trim($_GET['search'] ?? '');
    $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 10;
    $offset = ($page - 1) * $limit;

    $count_query = "SELECT COUNT(id) FROM users";
    $select_query = "SELECT id, full_name, username, email, created_at, is_admin FROM users";
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
    $param_index = 1;
    foreach ($params as $key => &$val) {
        $stmt->bindParam($param_index, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
        $param_index++;
    }
    
    $stmt->execute();
    $members = $stmt->fetchAll();

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

require_once HEADER;
?>
<main class="pt-32 pb-20">
    <section id="member-list" data-aos="fade-up">
        <div class="container mx-auto px-6 max-w-5xl">
            <h1 class="text-4xl font-bold text-center mb-10 section-title"><?php echo htmlspecialchars($settings_data['members_list_title']); ?></h1>

            <form method="GET" action="<?php echo MEMBERS_LIST_URL; ?>" class="mb-8 max-w-lg mx-auto">
                <div class="relative">
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search_term); ?>" placeholder="<?php echo htmlspecialchars($settings_data['members_search_placeholder']); ?>" class="w-full bg-gray-700 border border-gray-600 rounded-lg py-3 px-4 text-white focus:outline-none focus:ring-2 focus:ring-sky-500">
                    <button type="submit" class="absolute right-2 top-1/2 -translate-y-1/2 bg-sky-500 text-white px-4 py-1.5 rounded-md hover:bg-sky-600"><?php echo htmlspecialchars($settings_data['members_search_btn']); ?></button>
                </div>
            </form>

            <?php if ($success_message): ?><div class="bg-green-500/20 text-green-300 p-4 rounded-lg mb-6 max-w-lg mx-auto text-center"><p><?php echo $success_message; ?></p></div><?php endif; ?>
            <?php if (!empty($errors)): ?><div class="bg-red-500/20 text-red-300 p-4 rounded-lg mb-6 max-w-lg mx-auto text-center"><?php foreach($errors as $error) echo "<p>$error</p>"; ?></div><?php endif; ?>

            <div class="bg-gray-800/50 rounded-lg shadow-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-gray-900/50">
                            <tr>
                                <th class="p-4 font-semibold text-white"><?php echo htmlspecialchars($settings_data['members_col_full_name']); ?></th>
                                <th class="p-4 font-semibold text-white"><?php echo htmlspecialchars($settings_data['members_col_username']); ?></th>
                                <th class="p-4 font-semibold text-white hidden md:table-cell"><?php echo htmlspecialchars($settings_data['members_col_email']); ?></th>
                                <th class="p-4 font-semibold text-white"><?php echo htmlspecialchars($settings_data['members_col_role']); ?></th>
                                <th class="p-4 font-semibold text-white"><?php echo htmlspecialchars($settings_data['members_col_action']); ?></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-700">
                            <?php if (empty($members)): ?>
                                <tr><td colspan="5" class="p-4 text-center text-gray-400"><?php echo htmlspecialchars($settings_data['members_no_members_found']); ?></td></tr>
                            <?php else: ?>
                                <?php foreach ($members as $member): ?>
                                    <tr>
                                        <td class="p-4 text-white"><?php echo htmlspecialchars($member['full_name']); ?></td>
                                        <td class="p-4 text-gray-300">@<?php echo htmlspecialchars($member['username']); ?></td>
                                        <td class="p-4 text-gray-300 hidden md:table-cell"><?php echo htmlspecialchars($member['email']); ?></td>
                                        <td class="p-4 text-gray-300">
                                            <span class="font-semibold <?php echo $member['is_admin'] ? 'text-sky-400' : 'text-gray-400'; ?>">
                                                <?php echo $member['is_admin'] ? htmlspecialchars($settings_data['role_administrator']) : htmlspecialchars($settings_data['role_member']); ?>
                                            </span>
                                        </td>
                                        <td class="p-4">
                                            <?php if ($member['id'] !== $_SESSION['user_id']): ?>
                                            <div class="flex items-center gap-2">
                                                <form method="POST" action="<?php echo MEMBERS_LIST_URL; ?>?search=<?php echo htmlspecialchars($search_term); ?>&page=<?php echo $page; ?>" class="flex items-center gap-2">
                                                    <input type="hidden" name="user_id" value="<?php echo $member['id']; ?>">
                                                    <select name="role" class="bg-gray-700 border-gray-600 text-white text-sm rounded-md focus:ring-sky-500 focus:border-sky-500">
                                                        <option value="0" <?php if (!$member['is_admin']) echo 'selected'; ?>><?php echo htmlspecialchars($settings_data['role_member']); ?></option>
                                                        <option value="1" <?php if ($member['is_admin']) echo 'selected'; ?>><?php echo htmlspecialchars($settings_data['role_administrator']); ?></option>
                                                    </select>
                                                    <button type="submit" name="change_role" class="bg-green-600 text-white px-3 py-1 text-sm rounded-md hover:bg-green-700"><?php echo htmlspecialchars($settings_data['members_save_btn']); ?></button>
                                                </form>
                                                <a href="<?php echo DELETE_USER_URL_BASE . $member['id']; ?>" 
                                                   onclick="return confirm('Are you sure you want to delete this user? This will also delete all their posts and cannot be undone.');" 
                                                   class="bg-red-600 text-white px-3 py-1 text-sm rounded-md hover:bg-red-700"><?php echo htmlspecialchars($settings_data['members_delete_btn']); ?></a>
                                            </div>
                                            <?php endif; ?>
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
                    <a href="?search=<?php echo htmlspecialchars($search_term); ?>&page=<?php echo $i; ?>" class="px-4 py-2 rounded-lg <?php echo $i === $page ? 'bg-sky-500 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600'; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
            </div>
        </div>
    </section>
</main>
<?php require_once FOOTER; ?>