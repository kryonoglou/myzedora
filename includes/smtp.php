<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/phpmailer/src/Exception.php';
require_once __DIR__ . '/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/phpmailer/src/SMTP.php';

/**
 * Sends an email using the SMTP settings defined in the database.
 *
 * @param string $to The recipient's email address.
 * @param string $subject The email subject.
 * @param string $body The HTML email body.
 * @param array $settings_data The global array of site settings.
 * @return bool True on success, false on failure.
 */

function send_email($to, $subject, $body, $settings_data) {
    $mail = new PHPMailer(true);

    try {
        $mail->CharSet = 'UTF-8';

        $mail->isSMTP();
        $mail->Host       = $settings_data['smtp_host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $settings_data['smtp_username'];
        $mail->Password   = $settings_data['smtp_password'];
        $mail->SMTPSecure = $settings_data['smtp_secure'];
        $mail->Port       = $settings_data['smtp_port'];

        $mail->setFrom($settings_data['smtp_from_email'], $settings_data['smtp_from_name']);
        $mail->addAddress($to);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = strip_tags($body);

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}