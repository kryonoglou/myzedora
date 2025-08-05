<?php

function autobackup_init_check() {
    global $settings_data, $pdo;
    
    if (($settings_data['plugin_autobackup_enabled'] ?? '0') !== '1') {
        return;
    }

    $last_run_timestamp = (int)($settings_data['autobackup_last_run'] ?? 0);
    $frequency = $settings_data['autobackup_frequency'] ?? 'weekly';
    $backup_email = $settings_data['autobackup_email'] ?? null;

    if (empty($backup_email) || !filter_var($backup_email, FILTER_VALIDATE_EMAIL)) {
        return;
    }

    $interval_seconds = 0;
    switch ($frequency) {
        case 'daily':
            $interval_seconds = 86400;
            break;
        case 'weekly':
            $interval_seconds = 604800;
            break;
        case 'monthly':
            $interval_seconds = 2592000;
            break;
    }

    if (time() > ($last_run_timestamp + $interval_seconds)) {
        autobackup_perform_backup($pdo, $settings_data, $backup_email);
    }
}
add_action('init', 'autobackup_init_check');

function autobackup_perform_backup($pdo, $settings_data, $backup_email) {
    try {
        $pdo->beginTransaction();

        $tables = [];
        $result = $pdo->query("SHOW TABLES");
        while ($row = $result->fetch(PDO::FETCH_NUM)) {
            $tables[] = $row[0];
        }

        $sql_content = "-- myZedora CMS Database Backup\n";
        $sql_content .= "-- Generation Time: " . date('Y-m-d H:i:s') . "\n\n";
        $sql_content .= "SET NAMES utf8mb4;\n";
        $sql_content .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";

        foreach ($tables as $table) {
            $sql_content .= "--\n-- Table structure for table `$table`\n--\n";
            $create_stmt = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_ASSOC);
            $sql_content .= $create_stmt['Create Table'] . ";\n\n";

            $sql_content .= "--\n-- Dumping data for table `$table`\n--\n";
            $data_stmt = $pdo->query("SELECT * FROM `$table`");
            while ($row = $data_stmt->fetch(PDO::FETCH_ASSOC)) {
                $sql_content .= "INSERT INTO `$table` (";
                $sql_content .= implode(", ", array_map(function($key) { return "`$key`"; }, array_keys($row)));
                $sql_content .= ") VALUES (";
                $sql_content .= implode(", ", array_map(function($value) use ($pdo) {
                    return is_null($value) ? 'NULL' : $pdo->quote($value);
                }, $row));
                $sql_content .= ");\n";
            }
            $sql_content .= "\n";
        }

        $sql_content .= "SET FOREIGN_KEY_CHECKS = 1;\n";
        $pdo->commit();

        $backup_filename = 'myzedora_backup_' . date('Y-m-d_H-i-s') . '.sql';
        $temp_file_path = sys_get_temp_dir() . '/' . $backup_filename;
        file_put_contents($temp_file_path, $sql_content);

        $site_title = $settings_data['site_title'] ?? 'myZedora CMS';
        $subject = "Database Backup for " . $site_title;
        $body = "Attached is the automated database backup for your website, " . $site_title . ", generated on " . date('Y-m-d H:i:s') . ".";

        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = $settings_data['smtp_host'];
        $mail->SMTPAuth = true;
        $mail->Username = $settings_data['smtp_username'];
        $mail->Password = $settings_data['smtp_password'];
        $mail->SMTPSecure = $settings_data['smtp_secure'];
        $mail->Port = $settings_data['smtp_port'];
        $mail->setFrom($settings_data['smtp_from_email'], $settings_data['smtp_from_name']);
        $mail->addAddress($backup_email);
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->addAttachment($temp_file_path, $backup_filename);

        $mail->send();
        unlink($temp_file_path);

        $update_stmt = $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES ('autobackup_last_run', ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        $update_stmt->execute([time()]);
        
        return true;

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        return false;
    }
}
?>