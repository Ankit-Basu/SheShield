<?php
// Outgoing email. Settings come from environment variables, or from
// app/config/email_config.php on a local XAMPP install.
//
//   BREVO_API_KEY      send through Brevo's HTTPS API. Works on Render's free plan,
//                      which blocks SMTP ports 25, 465 and 587.
//   SMTP_HOST, SMTP_PORT, SMTP_USERNAME, SMTP_PASSWORD, SMTP_ENCRYPTION (ssl|tls)
//                      send over SMTP with PHPMailer (needs a paid Render instance).
//   MAIL_FROM_EMAIL, MAIL_FROM_NAME   sender shown to recipients.
//   APP_URL            public address of the site, used for links in emails.

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailerException;

function ss_mail_settings() {
    $file = __DIR__ . '/../config/email_config.php';
    if (is_file($file)) {
        require_once $file;
    }
    $const = fn ($name, $default = '') => defined($name) ? constant($name) : $default;
    $env = fn ($name, $fallback) => getenv($name) !== false && getenv($name) !== '' ? getenv($name) : $fallback;

    return [
        'brevo_key'  => $env('BREVO_API_KEY', ''),
        'smtp_host'  => $env('SMTP_HOST', $const('SMTP_HOST')),
        'smtp_port'  => (int)$env('SMTP_PORT', $const('SMTP_PORT', 465)),
        'smtp_user'  => $env('SMTP_USERNAME', $const('SMTP_USERNAME')),
        'smtp_pass'  => $env('SMTP_PASSWORD', $const('SMTP_PASSWORD')),
        'smtp_enc'   => strtolower($env('SMTP_ENCRYPTION', $const('SMTP_ENCRYPTION', 'ssl'))),
        'from_email' => $env('MAIL_FROM_EMAIL', $const('DEFAULT_FROM_EMAIL', $env('SMTP_USERNAME', $const('SMTP_USERNAME')))),
        'from_name'  => $env('MAIL_FROM_NAME', $const('DEFAULT_FROM_NAME', 'SheShield')),
    ];
}

// Public base URL of the site, ending in a slash.
function ss_app_url() {
    $configured = getenv('APP_URL');
    if ($configured) {
        return rtrim($configured, '/') . '/';
    }
    $proto = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http');
    $host = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? ($_SERVER['HTTP_HOST'] ?? 'localhost');
    $proto = explode(',', $proto)[0];
    $host = explode(',', $host)[0];
    // This file lives two folders below the project root.
    $docRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: '';
    $root = realpath(__DIR__ . '/../..') ?: '';
    $path = ($docRoot && strpos($root, $docRoot) === 0) ? str_replace('\\', '/', substr($root, strlen($docRoot))) : '';
    return trim($proto) . '://' . trim($host) . rtrim($path, '/') . '/';
}

function ss_send_mail($to, $toName, $subject, $htmlBody) {
    $s = ss_mail_settings();

    if ($s['brevo_key'] !== '') {
        if ($s['from_email'] === '') {
            return ['sent' => false, 'error' => 'MAIL_FROM_EMAIL is not set'];
        }
        $payload = json_encode([
            'sender'      => ['email' => $s['from_email'], 'name' => $s['from_name']],
            'to'          => [['email' => $to, 'name' => $toName ?: $to]],
            'subject'     => $subject,
            'htmlContent' => $htmlBody,
        ]);
        $context = stream_context_create(['http' => [
            'method'        => 'POST',
            'header'        => "api-key: {$s['brevo_key']}\r\nContent-Type: application/json\r\nAccept: application/json\r\n",
            'content'       => $payload,
            'timeout'       => 15,
            'ignore_errors' => true,
        ]]);
        $response = @file_get_contents('https://api.brevo.com/v3/smtp/email', false, $context);
        $status = 0;
        foreach ($http_response_header ?? [] as $line) {
            if (preg_match('#^HTTP/\S+\s+(\d{3})#', $line, $m)) {
                $status = (int)$m[1];
            }
        }
        if ($status >= 200 && $status < 300) {
            return ['sent' => true];
        }
        $detail = json_decode((string)$response, true);
        return ['sent' => false, 'error' => 'Brevo: ' . ($detail['message'] ?? ($status ? "HTTP $status" : 'no response'))];
    }

    if ($s['smtp_host'] === '') {
        return ['sent' => false, 'error' => 'Email is not configured'];
    }

    require_once __DIR__ . '/../lib/PHPMailer/Exception.php';
    require_once __DIR__ . '/../lib/PHPMailer/PHPMailer.php';
    require_once __DIR__ . '/../lib/PHPMailer/SMTP.php';

    $mail = new PHPMailer(true);
    try {
        $mail->SMTPDebug = 0;
        $mail->Timeout = 15;
        $mail->isSMTP();
        $mail->Host = $s['smtp_host'];
        $mail->SMTPAuth = true;
        $mail->Username = $s['smtp_user'];
        $mail->Password = $s['smtp_pass'];
        $mail->SMTPSecure = $s['smtp_enc'] === 'tls' ? PHPMailer::ENCRYPTION_STARTTLS : PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = $s['smtp_port'];
        $mail->CharSet = 'UTF-8';
        $mail->setFrom($s['from_email'], $s['from_name']);
        $mail->addAddress($to, $toName);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $htmlBody;
        $mail->send();
        return ['sent' => true];
    } catch (MailerException $e) {
        return ['sent' => false, 'error' => $mail->ErrorInfo];
    }
}
