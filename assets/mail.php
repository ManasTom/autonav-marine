<?php
// =======================
// CONFIGURATION
// =======================
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'src/PHPMailer.php';
require 'src/SMTP.php';
require 'src/Exception.php';

// Google reCAPTCHA secret key
$recaptcha_secret = '6LdPbOIrAAAAAKBuMM7iXPmjRVClh2uKC7GkW_-7'; // 🔹 replace this with your actual secret key

// Email settings
$to = 'autonavproduct@gmail.com';  // 🔹 receiver (company inbox)
$from_email = 'autonavproduct@gmail.com'; // same account used for SMTP
$from_name = 'Autonav Marine FZE';

// Logo for email styling
$logo_url = 'https://autonavmarine.com/alnakheel-logo.webp';

// =======================
// SECURITY CHECKS
// =======================

// 1️⃣ Honeypot field
if (!empty($_POST['website'])) {
    header("Location: 404.html");
    exit();
}

// 2️⃣ reCAPTCHA verification
if (empty($_POST['g-recaptcha-response'])) {
    header("Location: 404.html");
    exit();
}

$recaptcha_response = $_POST['g-recaptcha-response'];
$verify_response = file_get_contents(
    "https://www.google.com/recaptcha/api/siteverify?secret={$recaptcha_secret}&response={$recaptcha_response}"
);
$response_data = json_decode($verify_response);

if (!$response_data->success) {
    header("Location: 404.html");
    exit();
}

// =======================
// SANITIZE INPUTS
// =======================
function clean_input($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

$name     = clean_input($_POST['name'] ?? '');
$email    = clean_input($_POST['email'] ?? '');
$number   = clean_input($_POST['number'] ?? '');
$company  = clean_input($_POST['company'] ?? '');
$product  = clean_input($_POST['product'] ?? '');
$message  = clean_input($_POST['message'] ?? '');

// Validate required fields
if (empty($name) || empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: 404.html");
    exit();
}

// =======================
// EMAIL CONTENT
// =======================
$email_subject = "New Enquiry from Autonav Marine Website";

$email_body = "
<html>
<head>
<style>
body {
    font-family: Arial, sans-serif;
    color: #333;
    background-color: #f9f9f9;
    padding: 20px;
}
.container {
    background: #fff;
    border-radius: 8px;
    padding: 25px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    max-width: 600px;
    margin: auto;
}
.logo {
    text-align: center;
    margin-bottom: 20px;
}
.logo img {
    width: 100px;
}
h2 {
    color: #0a58ca;
}
p {
    line-height: 1.6;
    font-size: 15px;
}
.footer {
    margin-top: 20px;
    font-size: 13px;
    color: #777;
    text-align: center;
}
</style>
</head>
<body>
<div class='container'>
    <div class='logo'>
        <img src='{$logo_url}' alt='Autonav Marine FZE'>
    </div>
    <h2>New Enquiry Received</h2>
    <p><strong>Name:</strong> {$name}</p>
    <p><strong>Email:</strong> {$email}</p>
    <p><strong>Phone:</strong> {$number}</p>
    <p><strong>Company:</strong> {$company}</p>
    <p><strong>Product:</strong> {$product}</p>
    <p><strong>Message:</strong><br>{$message}</p>
    <div class='footer'>This email was sent from the Autonav Marine FZE website.</div>
</div>
</body>
</html>
";

// =======================
// SEND MAIL VIA GMAIL SMTP
// =======================
$mail = new PHPMailer(true);

try {
    // Server settings
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = $from_email;
    $mail->Password = 'povy vrli krbs lltd'; // 🔹 App password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    // Recipients
    $mail->setFrom($from_email, $from_name);
    $mail->addAddress($to);
    $mail->addReplyTo($email, $name);

    // Content
    $mail->isHTML(true);
    $mail->Subject = $email_subject;
    $mail->Body = $email_body;

    $mail->send();

    // =======================
    // AUTO REPLY TO SENDER
    // =======================
    $reply = new PHPMailer(true);
    $reply->isSMTP();
    $reply->Host = 'smtp.gmail.com';
    $reply->SMTPAuth = true;
    $reply->Username = $from_email;
    $reply->Password = 'povy vrli krbs lltd';
    $reply->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $reply->Port = 587;

    $reply->setFrom($from_email, 'Autonav Marine FZE');
    $reply->addAddress($email, $name);
    $reply->isHTML(true);
    $reply->Subject = "Thank you for contacting Autonav Marine FZE";
    $reply->Body = "
    <html><body style='font-family:Arial,sans-serif;color:#333;'>
    <div style='max-width:600px;margin:auto;padding:25px;background:#f9f9f9;border-radius:8px;'>
        <div style='text-align:center;margin-bottom:20px;'>
            <img src='{$logo_url}' width='100' alt='Autonav Marine FZE'>
        </div>
        <h2 style='color:#0a58ca;'>Thank You, {$name}!</h2>
        <p>We have received your enquiry regarding <strong>{$product}</strong>.</p>
        <p>Our team will get back to you shortly.</p>
        <p style='margin-top:20px;'>Warm regards,<br><strong>Autonav Marine FZE Team</strong></p>
    </div>
    </body></html>";

    $reply->send();

    // Redirect to thank you page
    header("Location: ../thankyou.html");
    exit();

} catch (Exception $e) {
    header("Location: ../404.html");
    exit();
}
?>
