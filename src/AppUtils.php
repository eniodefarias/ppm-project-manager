<?php

declare(strict_types=1);

namespace App;

use Pebble\App\StdUtils;
use Pebble\Service\Container;
use App\AppACL;

/**
 * A base app class with some utilities
 */
class AppUtils extends StdUtils {

    /**
     * @var \App\AppACL
     */
    protected $app_acl;

    public function __construct()
    {
        parent::__construct();
        $this->app_acl = $this->getAppACL();
    }

    /**
     * @return \App\AppACL
     */
    public function getAppACL()
    {
        $container = new Container();
        if (!$container->has('app_acl')) {
            $auth_cookie_settings = $this->getConfig()->getSection('Auth');
            $app_acl = new AppAcl($this->getDB(), $auth_cookie_settings);
            $container->set('app_acl', $app_acl);
        }
        return $container->get('app_acl');

    }
}