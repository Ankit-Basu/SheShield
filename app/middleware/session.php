<?php
require_once __DIR__ . '/session_bootstrap.php';

class Session {
    // Start session
    public static function start() {
        if (session_status() === PHP_SESSION_NONE) {
            configure_session_storage();
            session_start();
        }
    }

    // Check if user is logged in
    public static function isLoggedIn() {
        self::start();
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }

    // Get current user ID
    public static function getUserId() {
        self::start();
        return isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    }

    // Get current user name
    public static function getUserName() {
        self::start();
        if (isset($_SESSION['user_name']) && $_SESSION['user_name'] !== '') {
            return $_SESSION['user_name'];
        }
        $first = isset($_SESSION['first_name']) ? trim((string)$_SESSION['first_name']) : '';
        $last = isset($_SESSION['last_name']) ? trim((string)$_SESSION['last_name']) : '';
        $name = trim($first . ' ' . $last);
        return $name !== '' ? $name : null;
    }

    // Set session data
    public static function set($key, $value) {
        self::start();
        $_SESSION[$key] = $value;
    }

    // Get session data
    public static function get($key) {
        self::start();
        return isset($_SESSION[$key]) ? $_SESSION[$key] : null;
    }

    // Remove session data
    public static function remove($key) {
        self::start();
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
        }
    }

    // Destroy session
    public static function destroy() {
        self::start();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }

    // Set flash message
    public static function setFlash($key, $message) {
        self::start();
        $_SESSION['flash_' . $key] = $message;
    }

    // Add this method to Session class
    public static function isAdmin() {
        self::start();
        return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
    }

    // Get flash message and remove it
    public static function getFlash($key) {
        self::start();
        $message = isset($_SESSION['flash_' . $key]) ? $_SESSION['flash_' . $key] : null;
        if ($message) {
            unset($_SESSION['flash_' . $key]);
        }
        return $message;
    }
}
?>
