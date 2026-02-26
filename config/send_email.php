<?php
require __DIR__ . "/../vendor/PHPMailer/src/Exception.php";
require __DIR__ . "/../vendor/PHPMailer/src/PHPMailer.php";
require __DIR__ . "/../vendor/PHPMailer/src/SMTP.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function send_client_email($to, $toName, $subject, $bodyText){

    $mail = new PHPMailer(true);

    try {

        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'chiemela039@gmail.com';
        $mail->Password   = 'nemx skyn dmaz jmkt
';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('YOUR_GMAIL@gmail.com', 'Law Office');
        $mail->addAddress($to, $toName);

        $mail->isHTML(false);
        $mail->Subject = $subject;
        $mail->Body    = $bodyText;

        return $mail->send();

    } catch (Exception $e) {
        return false;
    }
}