<?php
/** POST /api/contact_submit.php {name, email, phone, message, lang} */
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/lib/PHPMailer/Exception.php';
require_once __DIR__ . '/lib/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/lib/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'error' => 'Method not allowed'], 405);
}

if (!rate_limit('contact_submit', 6, 300)) {
    json_response(['success' => false, 'error' => 'Príliš mnoho pokusov. Skúste to neskôr.'], 429);
}

$data = get_json_body();
if (empty($data)) { $data = $_POST; }

$name = sanitize_str($data['name'] ?? '');
$email = sanitize_str($data['email'] ?? '');
$phone = sanitize_str($data['phone'] ?? '');
$message = sanitize_str($data['message'] ?? '');
$lang = sanitize_str($data['lang'] ?? 'sk');
$honeypot = sanitize_str($data['website'] ?? '');

if ($honeypot !== '') {
    json_response(['success' => true]); // silently drop bots
}

if ($name === '' || $email === '' || $message === '') {
    json_response(['success' => false, 'error' => 'Vyplňte všetky povinné polá (meno, e-mail, správa).'], 422);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response(['success' => false, 'error' => 'Zadajte platnú e-mailovú adresu.'], 422);
}
if (mb_strlen($message) > 5000 || mb_strlen($name) > 150) {
    json_response(['success' => false, 'error' => 'Vstup je príliš dlhý.'], 422);
}

$pdo = get_pdo();
$stmt = $pdo->prepare("INSERT INTO contact_submissions (name, email, phone, message, lang, ip_address) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->execute([$name, $email, $phone, $message, $lang, client_ip()]);
$id = $pdo->lastInsertId();

$mail_sent = 0;
$mail_error = null;
$subject = "Nová správa z kontaktného formulára kmssk.sk";
$body = "Meno: $name\nE-mail: $email\nTelefón: $phone\n\nSpráva:\n$message\n\n--\nOdoslané z kontaktného formulára na kmssk.sk (#$id)";

if (SMTP_HOST !== '') {
    // SMTP is configured in api/config.php - send via PHPMailer (widely
    // compatible with Websupport webmail, Gmail, and every other provider).
    $deliverTo = SMTP_DELIVERY_EMAIL ?: CONTACT_EMAIL;
    $mailer = new PHPMailer(true);
    try {
        $mailer->isSMTP();
        $mailer->Host = SMTP_HOST;
        $mailer->Port = (int)SMTP_PORT;
        $mailer->SMTPAuth = true;
        $mailer->Username = SMTP_USERNAME;
        $mailer->Password = SMTP_PASSWORD;
        $mailer->SMTPSecure = smtp_encryption_for_port(SMTP_PORT); // 'ssl' (465) or 'tls' (587) - see helpers.php
        $mailer->CharSet = 'UTF-8';
        $mailer->Timeout = 12;

        $mailer->setFrom(SMTP_FROM_EMAIL ?: (SMTP_USERNAME ?: MAIL_FROM), SMTP_FROM_NAME ?: 'KMS webová stránka');
        $mailer->addAddress($deliverTo);
        $mailer->addReplyTo($email, $name);
        $mailer->Subject = $subject;
        $mailer->Body = $body;
        $mailer->isHTML(false);

        $mailer->send();
        $mail_sent = 1;
    } catch (PHPMailerException $e) {
        $mail_error = $mailer->ErrorInfo ?: $e->getMessage();
        error_log('[kmssk contact] PHPMailer SMTP send failed: ' . $mail_error);
    } catch (\Throwable $e) {
        $mail_error = $e->getMessage();
        error_log('[kmssk contact] Unexpected mail error: ' . $mail_error);
    }
} else {
    // No SMTP configured in api/config.php - fall back to PHP's built-in mail().
    $headers = "From: " . MAIL_FROM . "\r\nReply-To: " . $email . "\r\nContent-Type: text/plain; charset=UTF-8";
    if (function_exists('mail')) {
        $mail_sent = @mail(CONTACT_EMAIL, $subject, $body, $headers) ? 1 : 0;
        if (!$mail_sent) { $mail_error = 'PHP mail() returned false (no SMTP configured in api/config.php).'; }
    } else {
        $mail_error = 'Neither SMTP nor PHP mail() is available on this server.';
    }
}

$pdo->prepare("UPDATE contact_submissions SET mail_sent = ?, mail_error = ? WHERE id = ?")
    ->execute([$mail_sent, $mail_error, $id]);

json_response(['success' => true, 'id' => (int)$id, 'mail_sent' => (bool)$mail_sent]);
