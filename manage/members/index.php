<?php
require_once dirname(__DIR__, 2) . '/includes/map.php';

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
<script>
    if (history.scrollRestoration) {
        history.scrollRestoration = 'manual';
    }
    window.scrollTo(0, 0);
</script>
<main class="pt-24 md:pt-32 pb-20 bg-slate-900 text-slate-300">
    <section id="member-list" data-aos="fade-up">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8 max-w-7xl">
            
            <div class="max-w-3xl mx-auto mb-6 space-y-4">
                <?php if ($success_message): ?>
                <div class="bg-green-500/10 border border-green-500/30 text-green-300 px-4 py-3 rounded-lg relative flex items-center gap-4" role="alert">
                    <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span class="block sm:inline"><?php echo $success_message; ?></span>
                </div>
                <?php endif; ?>
                <?php if (!empty($errors)): ?>
                <div class="bg-red-500/10 border border-red-500/30 text-red-300 px-4 py-3 rounded-lg relative flex items-center gap-4" role="alert">
                    <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <div><?php foreach($errors as $error) echo "<p>$error</p>"; ?></div>
                </div>
                <?php endif; ?>
            </div>

            <div class="bg-slate-800/50 border border-slate-700 rounded-xl shadow-lg overflow-hidden">
                <div class="p-4 sm:p-6 border-b border-slate-700 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div>
                        <h1 class="text-xl font-bold text-white section-title"><?php echo htmlspecialchars($settings_data['members_list_title']); ?></h1>
                        <p class="text-sm text-slate-400 mt-1"><?php echo htmlspecialchars($settings_data['manage_all_registered_users']); ?></p>
                    </div>
                    <form method="GET" action="<?php echo MEMBERS_LIST_URL; ?>" class="w-full sm:w-auto">
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                                <svg class="w-5 h-5 text-slate-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                                </svg>
                            </span>
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search_term); ?>" placeholder="<?php echo htmlspecialchars($settings_data['members_search_placeholder']); ?>" class="w-full sm:w-64 bg-slate-900/50 border border-slate-600 rounded-lg py-2 pl-10 pr-4 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-sky-500 transition">
                        </div>
                    </form>
                </div>

                <div class="md:overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="hidden md:table-header-group bg-slate-800">
                            <tr>
                                <th scope="col" class="p-4 text-xs font-semibold text-slate-400 uppercase tracking-wider"><?php echo htmlspecialchars($settings_data['members_col_full_name']); ?></th>
                                <th scope="col" class="p-4 text-xs font-semibold text-slate-400 uppercase tracking-wider"><?php echo htmlspecialchars($settings_data['members_col_email']); ?></th>
                                <th scope="col" class="p-4 text-xs font-semibold text-slate-400 uppercase tracking-wider"><?php echo htmlspecialchars($settings_data['members_col_role']); ?></th>
                                <th scope="col" class="p-4 text-xs font-semibold text-slate-400 uppercase tracking-wider text-right"><?php echo htmlspecialchars($settings_data['members_col_action']); ?></th>
                            </tr>
                        </thead>
                        <tbody class="block md:table-row-group divide-y divide-slate-700/50 md:divide-y-0">
                            <?php if (empty($members)): ?>
                                <tr class="block md:table-row"><td colspan="4" class="block md:table-cell p-6 text-center text-slate-400"><?php echo htmlspecialchars($settings_data['members_no_members_found']); ?></td></tr>
                            <?php else: ?>
                                <?php foreach ($members as $member): ?>
                                    <tr class="block p-4 md:p-0 md:table-row hover:bg-slate-800/50 transition-colors duration-150">
                                        <td class="flex justify-between items-center py-2 md:table-cell md:p-4 md:whitespace-nowrap" data-label="<?php echo htmlspecialchars($settings_data['members_col_full_name']); ?>">
                                            <span class="md:hidden text-xs font-semibold text-slate-400 uppercase tracking-wider"><?php echo htmlspecialchars($settings_data['members_col_full_name']); ?></span>
                                            <div>
                                                <div class="font-medium text-white text-right md:text-left"><?php echo htmlspecialchars($member['full_name']); ?></div>
                                                <div class="text-sm text-slate-400 text-right md:text-left">@<?php echo htmlspecialchars($member['username']); ?></div>
                                            </div>
                                        </td>
                                        <td class="flex justify-between items-center py-2 md:table-cell md:p-4 md:whitespace-nowrap" data-label="<?php echo htmlspecialchars($settings_data['members_col_email']); ?>">
                                            <span class="md:hidden text-xs font-semibold text-slate-400 uppercase tracking-wider"><?php echo htmlspecialchars($settings_data['members_col_email']); ?></span>
                                            <span class="text-right md:text-left"><?php echo htmlspecialchars($member['email']); ?></span>
                                        </td>
                                        <td class="flex justify-between items-center py-2 md:table-cell md:p-4 md:whitespace-nowrap" data-label="<?php echo htmlspecialchars($settings_data['members_col_role']); ?>">
                                            <span class="md:hidden text-xs font-semibold text-slate-400 uppercase tracking-wider"><?php echo htmlspecialchars($settings_data['members_col_role']); ?></span>
                                            <span class="px-2.5 py-1 text-xs font-semibold rounded-full <?php echo $member['is_admin'] ? 'bg-sky-500/20 text-sky-300' : 'bg-slate-600/50 text-slate-300'; ?>">
                                                <?php echo $member['is_admin'] ? htmlspecialchars($settings_data['role_administrator']) : htmlspecialchars($settings_data['role_member']); ?>
                                            </span>
                                        </td>
                                        <td class="flex justify-between items-start pt-4 pb-2 md:table-cell md:p-4 md:whitespace-nowrap md:text-right" data-label="Actions">
                                            <span class="md:hidden text-xs font-semibold text-slate-400 uppercase tracking-wider"><?php echo htmlspecialchars($settings_data['members_col_action']); ?></span>
                                            <?php if ($member['id'] !== $_SESSION['user_id']): ?>
                                            <form method="POST" action="<?php echo MEMBERS_LIST_URL; ?>?search=<?php echo htmlspecialchars($search_term); ?>&page=<?php echo $page; ?>" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 w-full sm:w-auto sm:inline-flex">
                                                <input type="hidden" name="user_id" value="<?php echo $member['id']; ?>">
                                                <select name="role" class="bg-slate-700 border-slate-600 text-white text-sm rounded-md focus:ring-sky-500 focus:border-sky-500 py-1.5 h-full w-full">
                                                    <option value="0" <?php if (!$member['is_admin']) echo 'selected'; ?>><?php echo htmlspecialchars($settings_data['role_member']); ?></option>
                                                    <option value="1" <?php if ($member['is_admin']) echo 'selected'; ?>><?php echo htmlspecialchars($settings_data['role_administrator']); ?></option>
                                                </select>
                                                <div class="flex gap-2 mt-2 sm:mt-0">
                                                    <button type="submit" name="change_role" class="flex-1 bg-green-600 text-white px-3 py-1.5 text-sm font-semibold rounded-md hover:bg-green-700 transition-colors disabled:opacity-50"><?php echo htmlspecialchars($settings_data['members_save_btn']); ?></button>
                                                    <a href="<?php echo DELETE_USER_URL_BASE . $member['id']; ?>"  
                                                       onclick="return confirm('Are you sure you want to delete this user? This will also delete all their posts and cannot be undone.');"  
                                                       class="flex-1 text-center bg-red-600 text-white px-3 py-1.5 text-sm font-semibold rounded-md hover:bg-red-700 transition-colors"><?php echo htmlspecialchars($settings_data['members_delete_btn']); ?></a>
                                                </div>
                                            </form>
                                            <?php else: ?>
                                                <span class="text-sm text-slate-500 italic text-right w-full"><?php echo htmlspecialchars($settings_data['current_user_members']); ?></span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                 <?php if ($total_pages > 1): ?>
                <div class="p-4 border-t border-slate-700">
                    <nav class="flex items-center justify-center" aria-label="Pagination">
                         <div class="flex justify-center flex-wrap gap-2">
                             <a href="<?php echo $page > 1 ? '?search=' . htmlspecialchars($search_term) . '&page=' . ($page - 1) : '#'; ?>" 
                                class="inline-flex items-center justify-center px-4 py-2 text-sm font-semibold rounded-md text-slate-300 bg-slate-800 ring-1 ring-inset ring-slate-700 hover:bg-slate-700 focus:z-20 focus:outline-offset-0 <?php echo $page <= 1 ? 'opacity-50 cursor-not-allowed' : ''; ?>">
                                 <svg class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 01-.02 1.06L8.832 10l3.938 3.71a.75.75 0 11-1.04 1.08l-4.5-4.25a.75.75 0 010-1.08l4.5-4.25a.75.75 0 011.06.02z" clip-rule="evenodd" /></svg>
                                 Prev
                             </a>
                             
                             <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                             <a href="?search=<?php echo htmlspecialchars($search_term); ?>&page=<?php echo $i; ?>" 
                                class="inline-flex items-center justify-center px-4 py-2 text-sm font-semibold rounded-md <?php echo $i === $page ? 'z-10 bg-sky-600 text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sky-600' : 'text-slate-300 bg-slate-800 ring-1 ring-inset ring-slate-700 hover:bg-slate-700 focus:outline-offset-0'; ?>">
                                 <?php echo $i; ?>
                             </a>
                             <?php endfor; ?>

                             <a href="<?php echo $page < $total_pages ? '?search=' . htmlspecialchars($search_term) . '&page=' . ($page + 1) : '#'; ?>" 
                                class="inline-flex items-center justify-center px-4 py-2 text-sm font-semibold rounded-md text-slate-300 bg-slate-800 ring-1 ring-inset ring-slate-700 hover:bg-slate-700 focus:z-20 focus:outline-offset-0 <?php echo $page >= $total_pages ? 'opacity-50 cursor-not-allowed' : ''; ?>">
                                 Next
                                 <svg class="h-5 w-5 ml-2" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" /></svg>
                             </a>
                         </div>
                    </nav>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
</main>
<?php require_once FOOTER; ?>