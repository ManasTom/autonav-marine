<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../src/PHPMailer.php';
require __DIR__ . '/../src/SMTP.php';
require __DIR__ . '/../src/Exception.php';

// ==========================================================================
// CONFIGURATION
// ==========================================================================
$toEmail = "autonavproduct@gmail.com"; // Main receiver
$fromName = "AUTONAV MARINE FZE";
$recaptchaSecret = "6LdPbOIrAAAAAKBuMM7iXPmjRVClh2uKC7GkW_-7"; // <-- Replace with your secret key

// ==========================================================================
// SECURITY CHECKS
// ==========================================================================

// Honeypot
if (!empty($_POST['hidden_field'])) {
    http_response_code(400);
    echo "Spam detected.";
    exit;
}

// Verify reCAPTCHA
if (empty($_POST['g-recaptcha-response'])) {
    http_response_code(400);
    echo "Please verify the reCAPTCHA.";
    exit;
}

$recaptchaResponse = $_POST['g-recaptcha-response'];
$verify = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=$recaptchaSecret&response=$recaptchaResponse");
$responseData = json_decode($verify);

if (!$responseData->success) {
    http_response_code(400);
    echo "reCAPTCHA verification failed.";
    exit;
}

// ==========================================================================
// INPUT SANITIZATION
// ==========================================================================
function clean($value) {
    return htmlspecialchars(strip_tags(trim($value)), ENT_QUOTES, 'UTF-8');
}

$name     = clean($_POST['name'] ?? '');
$email    = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
$number   = clean($_POST['number'] ?? '');
$company  = clean($_POST['company'] ?? '');
$product  = clean($_POST['product'] ?? '');
$message  = clean($_POST['message'] ?? '');

if (!$name || !$email || !$message) {
    http_response_code(400);
    echo "Please fill out all required fields.";
    exit;
}

// ==========================================================================
// HTML EMAIL TEMPLATE
// ==========================================================================
$logoURL = "https://autonavmarine.com/assets/images/autonav-marine-logo.png";
$timestamp = date("d M Y, h:i A");

$adminBody = <<<EOD
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>New Enquiry</title>
<style>
body { font-family: 'Segoe UI', Arial, sans-serif; background:#f8f9fa; margin:0; padding:0; }
.container { max-width:600px; margin:20px auto; background:#fff; border-radius:10px; overflow:hidden; box-shadow:0 2px 10px rgba(0,0,0,0.1); }
.header { background:#002a5c; padding:20px; text-align:center; }
.header img { width:180px; }
.content { padding:25px; color:#333; }
h2 { color:#002a5c; margin-bottom:15px; }
table { width:100%; border-collapse:collapse; margin-top:10px; }
td { padding:10px; border-bottom:1px solid #eee; }
.footer { text-align:center; background:#f0f3f8; padding:15px; font-size:12px; color:#555; }
</style>
</head>
<body>
<div class="container">
  <div class="header">
    <img src="$logoURL" alt="AUTONAV MARINE FZE Logo">
  </div>
  <div class="content">
    <h2>New Enquiry Received</h2>
    <table>
      <tr><td><strong>Name:</strong></td><td>$name</td></tr>
      <tr><td><strong>Email:</strong></td><td>$email</td></tr>
      <tr><td><strong>Phone:</strong></td><td>$number</td></tr>
      <tr><td><strong>Company:</strong></td><td>$company</td></tr>
      <tr><td><strong>Product:</strong></td><td>$product</td></tr>
      <tr><td><strong>Message:</strong></td><td>$message</td></tr>
    </table>
    <p style="margin-top:15px;font-size:13px;color:#555;">Submitted on $timestamp</p>
  </div>
  <div class="footer">
    © AUTONAV MARINE FZE — Enquiry Notification
  </div>
</div>
</body>
</html>
EOD;

$userReply = <<<EOD
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Thank You - AUTONAV MARINE FZE</title>
<style>
body { font-family:'Segoe UI', Arial, sans-serif; background:#f5f7fa; margin:0; padding:0; }
.container { max-width:600px; margin:20px auto; background:#fff; border-radius:10px; box-shadow:0 2px 10px rgba(0,0,0,0.08); overflow:hidden; }
.header { background:#002a5c; padding:20px; text-align:center; }
.header img { width:150px; }
.content { padding:25px; color:#333; line-height:1.6; }
h2 { color:#002a5c; }
.footer { background:#f0f3f8; text-align:center; padding:15px; font-size:12px; color:#666; }
</style>
</head>
<body>
<div class="container">
  <div class="header">
    <img src="$logoURL" alt="AUTONAV MARINE FZE">
  </div>
  <div class="content">
    <h2>Thank You, $name!</h2>
    <p>We have received your enquiry regarding <strong>$product</strong>. Our team will get back to you shortly.</p>
    <p>If you have any urgent queries, feel free to reach us at <a href="mailto:autonavproduct@gmail.com">autonavproduct@gmail.com</a>.</p>
    <p>Warm regards,<br><strong>AUTONAV MARINE FZE</strong></p>
  </div>
  <div class="footer">
    © AUTONAV MARINE FZE — All Rights Reserved
  </div>
</div>
</body>
</html>
EOD;

// ==========================================================================
// SEND EMAILS USING PHPMailer
// ==========================================================================
$mail = new PHPMailer(true);

try {
    // Main mail (to admin)
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'autonavproduct@gmail.com';
    $mail->Password = 'povy vrli krbs lltd';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    $mail->setFrom('autonavproduct@gmail.com', $fromName);
    $mail->addAddress($toEmail);
    $mail->addReplyTo($email, $name);
    $mail->isHTML(true);
    $mail->Subject = "New Enquiry from $name";
    $mail->Body = $adminBody;

    $mail->send();

    // Auto reply to user
    $auto = new PHPMailer(true);
    $auto->isSMTP();
    $auto->Host = 'smtp.gmail.com';
    $auto->SMTPAuth = true;
    $auto->Username = 'autonavproduct@gmail.com';
    $auto->Password = 'povy vrli krbs lltd';
    $auto->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $auto->Port = 587;

    $auto->setFrom('autonavproduct@gmail.com', $fromName);
    $auto->addAddress($email, $name);
    $auto->isHTML(true);
    $auto->Subject = "Thank You for Contacting AUTONAV MARINE FZE";
    $auto->Body = $userReply;

    $auto->send();

    header("Location: https://autonavmarine.com/thankyou.html");

} catch (Exception $e) {
    http_response_code(500);
    echo "Mailer Error: {$mail->ErrorInfo}";
}
?>
