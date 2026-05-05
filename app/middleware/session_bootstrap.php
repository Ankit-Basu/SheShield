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
