<?php

namespace GeneroWP\GDPR\Gravityforms;

use GFAPI;
use GeneroWP\GDPR\Singleton;

class AnonymizeIp
{
    use Singleton;

    public static function is_active()
    {
        return true;
    }

    public function plugins_loaded()
    {
        add_filter('gform_ip_address', [$this, 'anonymize_ip']);
    }

    public function anonymize_ip($ip)
    {
        if (function_exists('wp_privacy_anonymize_ip')) {
            return wp_privacy_anonymize_ip($ip);
        }
        return '0.0.0.0';
    }
}


