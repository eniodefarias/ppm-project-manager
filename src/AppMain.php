<?php

declare(strict_types=1);

namespace App;

use Pebble\Router;
use Pebble\App\AppBase;
use App\Settings\SettingsModel;
use Diversen\Lang;

/**
 * App class. This is the main class for the app. 
 * It is used in www/index.php
 */
class AppMain extends AppBase
{
    use \App\CSP;

    public const VERSION = "2.0.0";

    /**
     * Load user language and timezone if set else load default language
     * Init translations
     */
    public function setupIntl()
    {
        // Set a default language in case of an early error
        $translations = new Lang();
        $translations->setSingleDir("../src");
        $translations->loadLanguage('en');

        $settings = new SettingsModel();

        $auth_id = $this->getAuth()->getAuthId();
        $user_settings = $settings->getUserSetting($auth_id, 'profile');

        $timezone = $user_settings['timezone'] ?? $this->getConfig()->get('App.timezone');
        date_default_timezone_set($timezone);

        $language = $user_settings['language'] ?? $this->getRequestLanguage();
        $translations->loadLanguage($language);
    }

    public function run()
    {

        // Add . to include path
        $this->addBaseToIncudePath();

        // Add src/ to include path
        $this->addSrcToIncludePath();

        $this->setErrorHandler();
        $this->sendSSLHeaders();
        $this->sendCSPHeaders();
        $this->sessionStart();
        $this->setupIntl();
        $this->setDebug();

        $router = new Router();
        $router->setFasterRouter();
        $router->addClass(\App\Test\Controller::class);
        $router->addClass(\App\Info\Controller::class);
        $router->addClass(\App\Admin\Controller::class);
        $router->addClass(\App\Notification\Controller::class);
        $router->addClass(\App\Account\ControllerExt::class);
        $router->addClass(\App\Home\Controller::class);
        $router->addClass(\App\Google\Controller::class);
        $router->addClass(\App\Overview\Controller::class);
        $router->addClass(\App\Project\Controller::class);
        $router->addClass(\App\Settings\Controller::class);
        $router->addClass(\App\Task\Controller::class);
        $router->addClass(\App\Time\Controller::class);
        $router->addClass(\App\Error\Controller::class);
        $router->addClass(\App\TwoFactor\Controller::class);
        $router->run();
    }
}
