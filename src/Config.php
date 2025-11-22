<?php

namespace FiefdomForge;

use Dotenv\Dotenv;

class Config
{
    private static ?Config $instance = null;
    private array $settings = [];

    private function __construct()
    {
        $this->loadEnvironment();
        $this->loadSettings();
    }

    public static function getInstance(): Config
    {
        if (self::$instance === null) {
            self::$instance = new Config();
        }
        return self::$instance;
    }

    private function loadEnvironment(): void
    {
        $dotenv = Dotenv::createImmutable(dirname(__DIR__));

        if (file_exists(dirname(__DIR__) . '/.env')) {
            $dotenv->load();
        }
    }

    private function loadSettings(): void
    {
        $rootPath = dirname(__DIR__);
        $dbPath = $_ENV['DB_PATH'] ?? 'database/fiefdom_forge.sqlite';

        // Make DB path absolute if relative
        if (!str_starts_with($dbPath, '/') && !preg_match('/^[A-Za-z]:/', $dbPath)) {
            $dbPath = $rootPath . '/' . $dbPath;
        }

        $this->settings = [
            'db' => [
                'path' => $dbPath,
            ],
            'app' => [
                'env' => $_ENV['APP_ENV'] ?? 'development',
                'debug' => filter_var($_ENV['APP_DEBUG'] ?? true, FILTER_VALIDATE_BOOLEAN),
                'url' => $_ENV['APP_URL'] ?? 'http://localhost:8000',
            ],
            'session' => [
                'name' => $_ENV['SESSION_NAME'] ?? 'fiefdom_session',
                'lifetime' => (int)($_ENV['SESSION_LIFETIME'] ?? 3600),
            ],
            'paths' => [
                'root' => $rootPath,
                'src' => $rootPath . '/src',
                'templates' => $rootPath . '/templates',
                'templates_c' => $rootPath . '/templates_c',
                'cache' => $rootPath . '/cache',
                'public' => $rootPath . '/public_html',
                'database' => dirname($dbPath),
            ],
        ];
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $keys = explode('.', $key);
        $value = $this->settings;

        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return $default;
            }
            $value = $value[$k];
        }

        return $value;
    }

    public function isDebug(): bool
    {
        return $this->get('app.debug', false);
    }

    public function isDevelopment(): bool
    {
        return $this->get('app.env') === 'development';
    }
}
