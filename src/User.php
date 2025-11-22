<?php

namespace FiefdomForge;

class User
{
    private ?int $id = null;
    private ?string $username = null;
    private ?string $email = null;
    private ?string $role = null;
    private ?string $createdAt = null;

    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public static function register(string $username, string $email, string $password): array
    {
        $db = Database::getInstance();

        // Validate inputs
        if (strlen($username) < 3 || strlen($username) > 50) {
            return ['success' => false, 'error' => 'Username must be 3-50 characters'];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => 'Invalid email address'];
        }

        if (strlen($password) < 6) {
            return ['success' => false, 'error' => 'Password must be at least 6 characters'];
        }

        // Check if username exists
        $existing = $db->fetch(
            "SELECT id FROM users WHERE username = ? OR email = ?",
            [$username, $email]
        );

        if ($existing) {
            return ['success' => false, 'error' => 'Username or email already exists'];
        }

        // Hash password and insert
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        try {
            $userId = $db->insert('users', [
                'username' => $username,
                'email' => $email,
                'password' => $hashedPassword,
                'role' => 'player',
            ]);

            return ['success' => true, 'user_id' => $userId];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'Registration failed'];
        }
    }

    public static function login(string $username, string $password): array
    {
        $db = Database::getInstance();

        $userData = $db->fetch(
            "SELECT id, username, email, password, role, created_at FROM users WHERE username = ? OR email = ?",
            [$username, $username]
        );

        if (!$userData) {
            return ['success' => false, 'error' => 'Invalid username or password'];
        }

        if (!password_verify($password, $userData['password'])) {
            return ['success' => false, 'error' => 'Invalid username or password'];
        }

        // Set session data
        Session::regenerate();
        Session::set('user_id', $userData['id']);
        Session::set('username', $userData['username']);
        Session::set('user_role', $userData['role']);

        return ['success' => true, 'user' => [
            'id' => $userData['id'],
            'username' => $userData['username'],
            'email' => $userData['email'],
            'role' => $userData['role'],
        ]];
    }

    public static function logout(): void
    {
        Session::destroy();
    }

    public static function isLoggedIn(): bool
    {
        return Session::has('user_id');
    }

    public static function current(): ?User
    {
        if (!self::isLoggedIn()) {
            return null;
        }

        $user = new User();
        return $user->loadById(Session::get('user_id')) ? $user : null;
    }

    public static function requireAuth(): void
    {
        if (!self::isLoggedIn()) {
            header('Location: /login');
            exit;
        }

        // Verify the user still exists in the database (handles fresh DB case)
        $db = Database::getInstance();
        $userId = Session::get('user_id');
        $user = $db->fetch("SELECT id FROM users WHERE id = ?", [$userId]);

        if (!$user) {
            // User no longer exists (database was reset), clear session and redirect
            Session::destroy();
            header('Location: /login');
            exit;
        }
    }

    public static function requireAdmin(): void
    {
        self::requireAuth();

        if (Session::get('user_role') !== 'admin') {
            header('Location: /dashboard');
            exit;
        }
    }

    public function loadById(int $id): bool
    {
        $userData = $this->db->fetch(
            "SELECT id, username, email, role, created_at FROM users WHERE id = ?",
            [$id]
        );

        if (!$userData) {
            return false;
        }

        $this->id = $userData['id'];
        $this->username = $userData['username'];
        $this->email = $userData['email'];
        $this->role = $userData['role'];
        $this->createdAt = $userData['created_at'];

        return true;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }

    public function updatePassword(string $newPassword): bool
    {
        if (!$this->id) {
            return false;
        }

        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        return $this->db->update(
            'users',
            ['password' => $hashedPassword],
            'id = ?',
            [$this->id]
        ) > 0;
    }
}
