<?php
// Local email settings for XAMPP. Copy this file to email_config.php (which git
// ignores) and fill it in. On Render, Docker or Kubernetes, set environment
// variables instead: see app/helpers/mailer.php.

// SMTP settings (Gmail needs an app password, not your account password)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_USERNAME', 'you@example.com');
define('SMTP_PASSWORD', 'your-app-password');
define('SMTP_PORT', 465);          // 465 for SSL, 587 for TLS
define('SMTP_ENCRYPTION', 'ssl');  // 'ssl' or 'tls'

// Sender shown to recipients
define('DEFAULT_FROM_EMAIL', 'you@example.com');
define('DEFAULT_FROM_NAME', 'SheShield');
