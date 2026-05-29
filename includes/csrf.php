<?php
// Helper CSRF dùng chung cho các form POST.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!function_exists('csrf_token')) {
    function csrf_token($form_key) {
        if (!isset($_SESSION['_csrf_tokens']) || !is_array($_SESSION['_csrf_tokens'])) {
            $_SESSION['_csrf_tokens'] = [];
        }

        if (empty($_SESSION['_csrf_tokens'][$form_key])) {
            $_SESSION['_csrf_tokens'][$form_key] = bin2hex(random_bytes(32));
        }

        return $_SESSION['_csrf_tokens'][$form_key];
    }
}

if (!function_exists('csrf_validate')) {
    function csrf_validate($form_key, $token) {
        if (
            !isset($_SESSION['_csrf_tokens']) ||
            !is_array($_SESSION['_csrf_tokens']) ||
            empty($_SESSION['_csrf_tokens'][$form_key]) ||
            !is_string($token)
        ) {
            return false;
        }

        return hash_equals($_SESSION['_csrf_tokens'][$form_key], $token);
    }
}
?>
