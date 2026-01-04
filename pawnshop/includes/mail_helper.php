<?php
// includes/mail_helper.php

/**
 * Very simple mail helper using PHP's mail().
 * For local XAMPP this may or may not actually send;
 * but it's enough for code / demo. Later you can replace
 * with PHPMailer + Gmail SMTP if needed.
 */
function send_app_email(string $toEmail, string $subject, string $message): bool
{
    // Change this to a valid email if you configure a real SMTP
    $from = 'no-reply@pawni-track.local';

    $headers  = "From: PawniTrack <{$from}>\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    return mail($toEmail, $subject, $message, $headers);
}