<?php
require_once __DIR__ . '/../../app/models/mysqli_db.php';
require_once __DIR__ . '/../../PHPMailer/Exception.php';
require_once __DIR__ . '/../../PHPMailer/PHPMailer.php';
require_once __DIR__ . '/../../PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

function sheshieldEmailTemplate($title, $greeting, $bodyContent, $ctaText = '', $ctaUrl = '') {
    $cta = '';
    if ($ctaText && $ctaUrl) {
        $cta = "
        <tr><td style='padding:20px 40px 0'>
            <a href='{$ctaUrl}' style='display:inline-block;padding:14px 36px;background:linear-gradient(135deg,#e91e8c,#7c3aed);color:#fff;text-decoration:none;border-radius:10px;font-weight:700;font-size:15px;letter-spacing:0.3px'>{$ctaText}</a>
        </td></tr>";
    }
    return "
    <!DOCTYPE html>
    <html><head><meta charset='UTF-8'><meta name='viewport' content='width=device-width,initial-scale=1.0'></head>
    <body style='margin:0;padding:0;background:#0d0d14;font-family:Segoe UI,Arial,sans-serif'>
    <table width='100%' cellpadding='0' cellspacing='0' style='background:#0d0d14;padding:40px 0'>
    <tr><td align='center'>
    <table width='600' cellpadding='0' cellspacing='0' style='background:#13131f;border-radius:20px;overflow:hidden;border:1px solid rgba(255,255,255,0.06)'>
        <!-- Header -->
        <tr><td style='background:linear-gradient(135deg,#e91e8c,#7c3aed);padding:32px 40px;text-align:center'>
            <div style='font-size:28px;font-weight:800;color:#fff;letter-spacing:1px'>🛡️ SheShield</div>
            <div style='font-size:12px;color:rgba(255,255,255,0.7);margin-top:6px;letter-spacing:2px;text-transform:uppercase'>Empowering Women's Safety</div>
        </td></tr>
        <!-- Title -->
        <tr><td style='padding:32px 40px 0'>
            <div style='font-size:22px;font-weight:700;color:#fff'>{$title}</div>
            <div style='width:50px;height:2px;background:linear-gradient(90deg,#e91e8c,#7c3aed);margin-top:12px;border-radius:2px'></div>
        </td></tr>
        <!-- Greeting -->
        <tr><td style='padding:20px 40px 0'>
            <p style='color:rgba(255,255,255,0.7);font-size:15px;line-height:1.7;margin:0'>{$greeting}</p>
        </td></tr>
        <!-- Body -->
        <tr><td style='padding:20px 40px 0'>
            <div style='background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.08);border-radius:14px;padding:24px 28px'>
                {$bodyContent}
            </div>
        </td></tr>
        {$cta}
        <!-- Footer -->
        <tr><td style='padding:32px 40px;border-top:1px solid rgba(255,255,255,0.06);margin-top:32px'>
            <p style='color:rgba(255,255,255,0.3);font-size:12px;line-height:1.6;margin:0;text-align:center'>
                This is an automated message from SheShield.<br>
                © " . date('Y') . " SheShield — Empowering Women's Safety<br>
                <span style='color:rgba(255,255,255,0.2)'>24/7 Protection • Real-time Tracking • Community Safety</span>
            </p>
        </td></tr>
    </table>
    </td></tr>
    </table>
    </body></html>";
}

function sendMail($to, $toName, $subject, $htmlBody) {
    $emailConfig = __DIR__ . '/../../app/config/email_config.php';
    if (!file_exists($emailConfig)) {
        return ['sent' => false, 'error' => 'Email config not found'];
    }
    require_once $emailConfig;
    
    $mail = new PHPMailer(true);
    try {
        $mail->SMTPDebug = 0;
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_ENCRYPTION === 'tls' ? PHPMailer::ENCRYPTION_STARTTLS : PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = SMTP_PORT;
        $mail->setFrom(DEFAULT_FROM_EMAIL, DEFAULT_FROM_NAME);
        $mail->addAddress($to, $toName);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $htmlBody;
        $mail->send();
        return ['sent' => true];
    } catch (Exception $e) {
        return ['sent' => false, 'error' => $mail->ErrorInfo];
    }
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data || !isset($data['type'])) {
        throw new Exception('Invalid request');
    }

    $type = $data['type'];
    $userName = $data['userName'] ?? 'User';
    $userEmail = $data['userEmail'] ?? '';

    if (!$userEmail) {
        throw new Exception('User email not available. Please log in again.');
    }

    if ($type === 'walk_request') {
        $pickup = $data['pickupLocation'] ?? '';
        $dest = $data['destination'] ?? '';
        $time = $data['requestTime'] ?? date('Y-m-d H:i');
        $walkId = 'WALK-' . date('Ymd') . '-' . substr(uniqid(), -6);

        if (!$pickup || !$dest) {
            throw new Exception('Pickup and destination are required');
        }

        $bodyContent = "
            <table width='100%' cellpadding='0' cellspacing='0'>
                <tr><td style='padding:6px 0'><span style='color:rgba(255,255,255,0.4);font-size:12px;text-transform:uppercase;letter-spacing:1px'>Walk ID</span><br><span style='color:#fff;font-size:15px;font-weight:600'>{$walkId}</span></td></tr>
                <tr><td style='padding:6px 0;border-top:1px solid rgba(255,255,255,0.06)'><span style='color:rgba(255,255,255,0.4);font-size:12px;text-transform:uppercase;letter-spacing:1px'>Pickup</span><br><span style='color:#e91e8c;font-size:15px;font-weight:600'>📍 {$pickup}</span></td></tr>
                <tr><td style='padding:6px 0;border-top:1px solid rgba(255,255,255,0.06)'><span style='color:rgba(255,255,255,0.4);font-size:12px;text-transform:uppercase;letter-spacing:1px'>Destination</span><br><span style='color:#7c3aed;font-size:15px;font-weight:600'>📍 {$dest}</span></td></tr>
                <tr><td style='padding:6px 0;border-top:1px solid rgba(255,255,255,0.06)'><span style='color:rgba(255,255,255,0.4);font-size:12px;text-transform:uppercase;letter-spacing:1px'>Requested Time</span><br><span style='color:#fff;font-size:15px'>{$time}</span></td></tr>
                <tr><td style='padding:6px 0;border-top:1px solid rgba(255,255,255,0.06)'><span style='color:rgba(255,255,255,0.4);font-size:12px;text-transform:uppercase;letter-spacing:1px'>Status</span><br><span style='color:#22c55e;font-size:15px;font-weight:700'>✅ Request Confirmed</span></td></tr>
            </table>";

        $html = sheshieldEmailTemplate(
            'Walk Request Confirmed',
            "Hi {$userName}, your walk request has been received and a verified walker will be assigned shortly.",
            $bodyContent,
            'View Dashboard',
            'http://localhost/sheshield/views/pages/walkwithus.php'
        );

        $result = sendMail($userEmail, $userName, "Walk Request Confirmed - {$walkId} - SheShield", $html);

        echo json_encode([
            'success' => true,
            'message' => 'Walk request submitted successfully',
            'walkId' => $walkId,
            'emailSent' => $result['sent'],
            'emailError' => $result['error'] ?? null
        ]);

    } elseif ($type === 'walker_register') {
        $from = $data['availableFrom'] ?? '';
        $until = $data['availableUntil'] ?? '';
        $areas = $data['preferredAreas'] ?? [];

        if (!$from || !$until || empty($areas)) {
            throw new Exception('Availability and areas are required');
        }

        $areasHtml = '';
        foreach ($areas as $area) {
            $areasHtml .= "<span style='display:inline-block;padding:5px 14px;margin:3px;background:rgba(233,30,140,0.12);border:1px solid rgba(233,30,140,0.25);border-radius:8px;color:#f472b6;font-size:13px;font-weight:600'>{$area}</span>";
        }

        $bodyContent = "
            <table width='100%' cellpadding='0' cellspacing='0'>
                <tr><td style='padding:6px 0'><span style='color:rgba(255,255,255,0.4);font-size:12px;text-transform:uppercase;letter-spacing:1px'>Registration Status</span><br><span style='color:#22c55e;font-size:15px;font-weight:700'>✅ Successfully Registered</span></td></tr>
                <tr><td style='padding:6px 0;border-top:1px solid rgba(255,255,255,0.06)'><span style='color:rgba(255,255,255,0.4);font-size:12px;text-transform:uppercase;letter-spacing:1px'>Available From</span><br><span style='color:#fff;font-size:15px;font-weight:600'>🕐 {$from}</span></td></tr>
                <tr><td style='padding:6px 0;border-top:1px solid rgba(255,255,255,0.06)'><span style='color:rgba(255,255,255,0.4);font-size:12px;text-transform:uppercase;letter-spacing:1px'>Available Until</span><br><span style='color:#fff;font-size:15px;font-weight:600'>🕐 {$until}</span></td></tr>
                <tr><td style='padding:12px 0;border-top:1px solid rgba(255,255,255,0.06)'><span style='color:rgba(255,255,255,0.4);font-size:12px;text-transform:uppercase;letter-spacing:1px'>Patrol Areas</span><br><div style='margin-top:8px'>{$areasHtml}</div></td></tr>
            </table>";

        $html = sheshieldEmailTemplate(
            'Walker Registration Confirmed',
            "Hi {$userName}, thank you for volunteering as a SheShield walker! Your registration has been confirmed.",
            $bodyContent,
            'Open Dashboard',
            'http://localhost/sheshield/views/pages/walkwithus.php'
        );

        $result = sendMail($userEmail, $userName, "Walker Registration Confirmed - SheShield", $html);

        echo json_encode([
            'success' => true,
            'message' => 'Walker registration successful',
            'emailSent' => $result['sent'],
            'emailError' => $result['error'] ?? null
        ]);

    } else {
        throw new Exception('Unknown request type');
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
