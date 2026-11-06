<?php
function configure_session_storage() {
    static $configured = false;

    if ($configured || session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $sessionDir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . 'sessions';
    if (!is_dir($sessionDir)) {
        @mkdir($sessionDir, 0777, true);
    }

    if (is_dir($sessionDir) && is_writable($sessionDir)) {
        session_save_path($sessionDir);
    } else {
        $fallback = sys_get_temp_dir();
        if ($fallback && is_writable($fallback)) {
            session_save_path($fallback);
        }
    }

    $configured = true;
}

/* ─── Mobile app sign-in ──────────────────────────────────────────────
   Browsers use the PHP session cookie. The mobile app instead sends
   "Authorization: Bearer <token>". Tokens are random, stored only as a
   SHA-256 hash in api_tokens, and survive server restarts. Each request
   with a token gets the same $_SESSION fields a browser login sets, so
   every endpoint works for both without changes. */

function ss_bearer_token() {
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
    if ($header === '' && function_exists('apache_request_headers')) {
        foreach (apache_request_headers() as $name => $value) {
            if (strcasecmp($name, 'Authorization') === 0) {
                $header = $value;
                break;
            }
        }
    }
    return preg_match('/^Bearer\s+([a-f0-9]{64})$/i', trim((string)$header), $m) ? strtolower($m[1]) : '';
}

function ss_token_db() {
    require_once __DIR__ . '/../config/database.php';
    $db = (new Database())->getConnection();
    $db->exec("CREATE TABLE IF NOT EXISTS api_tokens (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        token_hash CHAR(64) NOT NULL UNIQUE,
        device VARCHAR(100) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        last_used_at TIMESTAMP NULL DEFAULT NULL,
        revoked_at TIMESTAMP NULL DEFAULT NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    return $db;
}

// Creates a token for a user who has just proven who they are. Returns the plain token once.
function ss_issue_token($userId, $device = '') {
    $token = bin2hex(random_bytes(32));
    $stmt = ss_token_db()->prepare('INSERT INTO api_tokens (user_id, token_hash, device) VALUES (?, ?, ?)');
    $stmt->execute([(int)$userId, hash('sha256', $token), mb_substr(trim((string)$device), 0, 100) ?: null]);
    return $token;
}

function ss_revoke_token($token) {
    if ($token === '') return;
    ss_token_db()->prepare('UPDATE api_tokens SET revoked_at = NOW() WHERE token_hash = ? AND revoked_at IS NULL')
        ->execute([hash('sha256', $token)]);
}

// Starts the session for a browser (cookie) or the mobile app (bearer token).
function ss_session_start() {
    configure_session_storage();
    $token = ss_bearer_token();
    if ($token === '') {
        session_start();
        return;
    }

    $hash = hash('sha256', $token);
    // A server-side session per token, so repeat requests skip the database lookup.
    ini_set('session.use_cookies', '0');
    session_id('m' . substr($hash, 0, 47));
    session_start();
    if (($_SESSION['token_hash'] ?? '') === $hash && !empty($_SESSION['user_id'])) {
        return;
    }

    $_SESSION = [];
    try {
        $db = ss_token_db();
        $stmt = $db->prepare(
            'SELECT u.id, u.first_name, u.last_name, u.email, u.phone, a.id AS admin_id
             FROM api_tokens t JOIN users u ON u.id = t.user_id LEFT JOIN admins a ON a.user_id = u.id
             WHERE t.token_hash = ? AND t.revoked_at IS NULL LIMIT 1'
        );
        $stmt->execute([$hash]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $_SESSION['logged_in'] = true;
            $_SESSION['user_id'] = (int)$row['id'];
            $_SESSION['first_name'] = $row['first_name'];
            $_SESSION['last_name'] = $row['last_name'];
            $_SESSION['user_name'] = trim($row['first_name'] . ' ' . $row['last_name']);
            $_SESSION['email'] = $row['email'];
            $_SESSION['phone'] = $row['phone'] ?? '';
            $_SESSION['is_admin'] = $row['admin_id'] !== null;
            $_SESSION['token_hash'] = $hash;
            $db->prepare('UPDATE api_tokens SET last_used_at = NOW() WHERE token_hash = ?')->execute([$hash]);
        }
    } catch (Throwable $e) {
        error_log('Token sign-in failed: ' . $e->getMessage());
    }
}
