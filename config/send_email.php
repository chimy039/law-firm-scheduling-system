<?php
require __DIR__ . "/../vendor/PHPMailer/src/Exception.php";
require __DIR__ . "/../vendor/PHPMailer/src/PHPMailer.php";
require __DIR__ . "/../vendor/PHPMailer/src/SMTP.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function send_client_email(string $to, string $toName, string $subject, string $bodyText): bool
{
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = "smtp.gmail.com";
        $mail->SMTPAuth   = true;

        // FILL THESE LOCALLY ONLY (do NOT commit)
        $mail->Username   = "";
        $mail->Password   = "";

        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom($mail->Username, "Law Office");
        $mail->addAddress($to, $toName);

        $mail->isHTML(false);
        $mail->Subject = $subject;
        $mail->Body    = $bodyText;

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}