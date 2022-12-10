<?php

declare(strict_types=1);

namespace App\Utils;

use Aidantwoods\SecureHeaders\SecureHeaders;
use Pebble\Service\ConfigService;

trait CSP
{
    private static $nonce;
    public static function getNonce()
    {
        return self::$nonce;
    }

    public function sendCSPHeaders()
    {
        $config = (new ConfigService())->getConfig();

        if (!$config->get("CSP.enabled")) {
            return;
        }

        self::$nonce = $config->get('CSP.nonce');

        /**
         * @var SecureHeaders $headers
         */
        $headers = $config->get('CSP.headers');
        $headers->apply();
    }
}
