<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

function willekeurig($lengte, $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ') {
    $str = '';
    $max = mb_strlen($keyspace, '8bit') - 1;
    for ($i = 0; $i < $lengte; ++$i) {
        $str .= $keyspace[random_int(0, $max)];
    }
    return $str;
}

function sendMail(array $to, string $subject, string $body) {
    $mail = new PHPMailer(true);

    try {
        $mail->SMTPDebug = 0;
        $mail->isSMTP();
        $mail->Host       = $_ENV['SMTP_HOST'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $_ENV['SMTP_USERNAME'];
        $mail->Password   = $_ENV['SMTP_PASSWORD'];
        $mail->SMTPSecure = $_ENV['SMTP_ENCRYPTION'] ?? 'tls';
        $mail->Port       = $_ENV['SMTP_PORT'] ?? '587';

        $mail->setFrom($_ENV['SMTP_FROM'] ?? 'no-reply@ward.nl', 'NerdyGadgets');
        $mail->addReplyTo($_ENV['SMTP_REPLY_TO'] ?? 'no-reply@ward.nl', 'NerdyGadgets');

        $has_recipients = false;
        foreach ($to as $email) {
            if(filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $has_recipients = true;
                $mail->addAddress($email);
            }
        }

        if (!$has_recipients) return false;

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;

        return $mail->send();
    } catch (Exception $e) {
        return false;
    }
}