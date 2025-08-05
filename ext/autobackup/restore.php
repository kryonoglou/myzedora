<?php

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'restore_backup') {

    if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
        $_SESSION['autobackup_message'] = [
            'type' => 'error',
            'text' => plugin_lang('autobackup', 'autobackup_restore_fail') . ' ' . plugin_lang('autobackup', 'autobackup_permission_denied')
        ];

        return;
    }

    if (isset($_FILES['backup_file']) && $_FILES['backup_file']['error'] === UPLOAD_ERR_OK) {
        $file_content = file_get_contents($_FILES['backup_file']['tmp_name']);

        if ($file_content === false) {
            $_SESSION['autobackup_message'] = [
                'type' => 'error',
                'text' => plugin_lang('autobackup', 'autobackup_restore_fail') . ' ' . plugin_lang('autobackup', 'autobackup_file_read_error')
            ];
            return;
        }

        try {
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

            preg_match_all('/CREATE TABLE `([^`]+)`/', $file_content, $matches);
            $tables_to_drop = $matches[1];

            foreach ($tables_to_drop as $table) {
                $pdo->exec("DROP TABLE IF EXISTS `$table`;");
            }
            
            $sql_query = '';
            $lines = explode("\n", $file_content);
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line) || substr($line, 0, 2) === '--' || substr($line, 0, 1) === '#') {
                    continue;
                }
                $sql_query .= $line;
                if (substr($line, -1) === ';') {
                    $pdo->exec($sql_query);
                    $sql_query = '';
                }
            }

            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

            $_SESSION['autobackup_message'] = [
                'type' => 'success',
                'text' => plugin_lang('autobackup', 'autobackup_restore_success')
            ];

        } catch (PDOException $e) {
            try {
                $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
            } catch (PDOException $e_fk) {
            }
            $_SESSION['autobackup_message'] = [
                'type' => 'error',
                'text' => plugin_lang('autobackup', 'autobackup_restore_fail')
            ];
        } catch (Exception $e) {
            try {
                $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
            } catch (PDOException $e_fk) {
            }
            $_SESSION['autobackup_message'] = [
                'type' => 'error',
                'text' => plugin_lang('autobackup', 'autobackup_restore_fail')
            ];
        }
    } else {
        $_SESSION['autobackup_message'] = [
            'type' => 'error',
            'text' => plugin_lang('autobackup', 'autobackup_restore_fail') . ' ' . plugin_lang('autobackup', 'autobackup_no_file_uploaded')
        ];
    }
}
?>