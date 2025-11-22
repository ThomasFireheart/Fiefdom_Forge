<?php

namespace FiefdomForge;

use Smarty\Smarty;

class View
{
    private static ?View $instance = null;
    private Smarty $smarty;
    private Config $config;

    private function __construct()
    {
        $this->config = Config::getInstance();
        $this->initSmarty();
    }

    public static function getInstance(): View
    {
        if (self::$instance === null) {
            self::$instance = new View();
        }
        return self::$instance;
    }

    private function initSmarty(): void
    {
        $this->smarty = new Smarty();

        $this->smarty->setTemplateDir($this->config->get('paths.templates'));
        $this->smarty->setCompileDir($this->config->get('paths.templates_c'));
        $this->smarty->setCacheDir($this->config->get('paths.cache'));

        // Development settings
        if ($this->config->isDevelopment()) {
            $this->smarty->setForceCompile(true);
            $this->smarty->setCaching(Smarty::CACHING_OFF);
        } else {
            $this->smarty->setForceCompile(false);
            $this->smarty->setCaching(Smarty::CACHING_LIFETIME_CURRENT);
        }

        // Add common variables
        $this->smarty->assign('app_url', $this->config->get('app.url'));
        $this->smarty->assign('is_debug', $this->config->isDebug());
    }

    public function assign(string $key, mixed $value): self
    {
        $this->smarty->assign($key, $value);
        return $this;
    }

    public function assignMultiple(array $data): self
    {
        foreach ($data as $key => $value) {
            $this->smarty->assign($key, $value);
        }
        return $this;
    }

    public function render(string $template): string
    {
        // Add user data if logged in
        if (User::isLoggedIn()) {
            $this->smarty->assign('current_user', [
                'id' => Session::get('user_id'),
                'username' => Session::get('username'),
                'role' => Session::get('user_role'),
            ]);
            $this->smarty->assign('is_logged_in', true);
        } else {
            $this->smarty->assign('current_user', null);
            $this->smarty->assign('is_logged_in', false);
        }

        // Add flash messages
        $this->smarty->assign('flash_success', Session::getFlash('success'));
        $this->smarty->assign('flash_error', Session::getFlash('error'));

        // Add CSRF token for forms
        $this->smarty->assign('csrf_token', Session::getCsrfToken());
        $this->smarty->assign('csrf_field', Session::csrfField());

        return $this->smarty->fetch($template);
    }

    public function display(string $template): void
    {
        echo $this->render($template);
    }

    public function getSmarty(): Smarty
    {
        return $this->smarty;
    }
}
