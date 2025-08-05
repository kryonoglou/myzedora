<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once dirname(__DIR__) . '/vendor/autoload.php';

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