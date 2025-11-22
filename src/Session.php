<?php

namespace FiefdomForge;

class Session
{
    private static bool $started = false;

    public static function start(): void
    {
        if (self::$started) {
            return;
        }

        $config = Config::getInstance();

        session_name($config->get('session.name', 'fiefdom_session'));

        session_set_cookie_params([
            'lifetime' => $config->get('session.lifetime', 3600),
            'path' => '/',
            'secure' => !$config->isDevelopment(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_start();
        self::$started = true;
    }

    public static function set(string $key, mixed $value): void
    {
        self::start();
        $_SESSION[$key] = $value;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        self::start();
        return $_SESSION[$key] ?? $default;
    }

    public static function has(string $key): bool
    {
        self::start();
        return isset($_SESSION[$key]);
    }

    public static function remove(string $key): void
    {
        self::start();
        unset($_SESSION[$key]);
    }

    public static function destroy(): void
    {
        self::start();
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
        self::$started = false;
    }

    public static function flash(string $key, mixed $value): void
    {
        self::set('_flash.' . $key, $value);
    }

    public static function getFlash(string $key, mixed $default = null): mixed
    {
        $value = self::get('_flash.' . $key, $default);
        self::remove('_flash.' . $key);
        return $value;
    }

    public static function regenerate(): void
    {
        self::start();
        session_regenerate_id(true);
    }

    /**
     * Generate or retrieve CSRF token
     */
    public static function getCsrfToken(): string
    {
        self::start();

        if (!isset($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['_csrf_token'];
    }

    /**
     * Validate CSRF token from request
     */
    public static function validateCsrfToken(?string $token): bool
    {
        if ($token === null) {
            return false;
        }

        $sessionToken = self::get('_csrf_token');
        if ($sessionToken === null) {
            return false;
        }

        return hash_equals($sessionToken, $token);
    }

    /**
     * Get CSRF token input field HTML
     */
    public static function csrfField(): string
    {
        return '<input type="hidden" name="_csrf_token" value="' . htmlspecialchars(self::getCsrfToken()) . '">';
    }
}
