<?php

return [
    'autobackup_manual_button' => 'Backup Now',
    'autobackup_manual_success' => 'Manual backup completed successfully and sent to your email.',
    'autobackup_manual_fail' => 'Failed to create manual backup. Please check your SMTP settings and server permissions.',
    'autobackup_manual_desc' => 'Trigger a full database backup immediately. The backup file will be sent to the email address specified above.',

    'autobackup_manual_title' => 'Manual Backup',
    'autobackup_how_often' => 'How often the backup should be performed.',
    'autobackup_daily' => 'Daily',
    'autobackup_weekly' => 'Weekly',
    'autobackup_monthly' => 'Monthly',
    'autobackup_frequency' => 'Backup Frequency',
    'autobackup_email_sent' => 'The backup file will be sent to this email address.',
    'autobackup_email_address' => 'Backup Email Address',
    'autobackup_enabled_schedule' => 'When enabled, the database will be backed up and emailed automatically based on the schedule below.',
    'autobackup_enable_backups' => 'Enable Automatic Backups',
    'autobackup_automatic_backup' => 'Automatic Database Backup',

    'autobackup_restore_title' => 'Restore from Backup',
    'autobackup_restore_desc' => 'Upload a `.sql` backup file to restore your database. This action is irreversible.',
    'autobackup_restore_button' => 'Restore Database',
    'autobackup_restore_warning' => 'Are you sure you want to restore the database? This will overwrite all existing data.',
    'autobackup_select_file' => 'Select a backup file',
    'autobackup_restore_success' => 'Database restored successfully.',
    'autobackup_restore_fail' => 'Failed to restore database.',
    'autobackup_permission_denied' => 'Permission denied.',
    'autobackup_file_read_error' => 'Could not read the uploaded file.',
    'autobackup_no_file_uploaded' => 'No file was uploaded.',
    'autobackup_title' => 'Backup & Restore',
    'autobackup_email_not_set' => 'Warning: The backup email address is not set. Please configure it in the plugin settings to enable this functionality.',
];