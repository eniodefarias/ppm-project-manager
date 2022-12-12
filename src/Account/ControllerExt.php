<?php

declare(strict_types=1);

namespace App\Account;

use Diversen\Lang;
use Pebble\CSRF;
use App\Google\GoogleUtils;
use App\Account\Controller;
use Exception;

/**
 * Extends the normal controler to include google auth
 */
class ControllerExt extends Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @route /account/signin
     * @verbs GET
     */
    public function index(): void
    {
        $google_auth_url = $this->getGoogleAuthUrl();
        $template_vars = ['title' => Lang::translate('Sign out')];
        if ($this->auth->isAuthenticated()) {
            $this->renderPage(
                'Account/views/signout.php',
                $template_vars,
            );
        } else {
            $template_vars = [
                'google_auth_url' => $google_auth_url,
                'title' => Lang::translate('Sign in'),
                'csrf_token' => (new CSRF())->getToken(),
            ];

            $this->renderPage(
                'Account/views/signin_ext.php',
                $template_vars
            );
        }
    }

    private function getGoogleAuthUrl(): string
    {
        if (!$this->config->get('Account.google')) {
            return throw new Exception("Configurations file 'google.php' is not enabled", 500);
        }

        $google_helpers = new GoogleUtils();
        $google_auth_url = $google_helpers->getAuthUrl();
        return $google_auth_url;
    }
}
