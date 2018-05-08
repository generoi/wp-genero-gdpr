<?php
/*
Plugin Name:        WP Genero GDPR
Plugin URI:         http://genero.fi
Description:        Various tools for becoming GDPR compliant
Version:            1.0.0
Author:             Genero
Author URI:         http://genero.fi/
License:            MIT License
License URI:        http://opensource.org/licenses/MIT
*/
namespace GeneroWP\GDPR;

use Puc_v4_Factory;
use GeneroWP\GDPR\Gravityforms\EncryptedField;

if (!defined('ABSPATH')) {
    exit;
}

if (file_exists($composer = __DIR__ . '/vendor/autoload.php')) {
    require_once $composer;
}

class Plugin
{
    use Singleton;

    public $version = '1.0.0';
    public $plugin_name = 'wp-genero-gdpr';
    public $plugin_path;
    public $plugin_url;
    public $github_url = 'https://github.com/generoi/wp-genero-gdpr';

    public static $modules = [
        Gravityforms\EncryptedField::class,
        Gravityforms\ExpireSubmissions::class,
    ];

    public function __construct()
    {
        $this->plugin_path = plugin_dir_path(__FILE__);
        $this->plugin_url = plugin_dir_url(__FILE__);

        register_activation_hook(__FILE__, [__CLASS__, 'activate']);
        register_deactivation_hook(__FILE__, [__CLASS__, 'deactivate']);

        Puc_v4_Factory::buildUpdateChecker($this->github_url, __FILE__, $this->plugin_name);

        add_action('plugins_loaded', [$this, 'init']);
    }

    public static function module_action($action, $with_instance = true)
    {
        foreach (self::$modules as $module) {
            if ($with_instance) {
                $instance = $module::get_instance();
                if ($instance::is_active() && method_exists($instance, $action)) {
                    $instance->$action();
                }
            } else {
                if ($module::is_active() && method_exists($module, $action)) {
                    $module::$action();
                }
            }
        }
    }

    public function init()
    {
        self::module_action('plugins_loaded');
    }

    public static function activate()
    {
        self::module_action('activate', false);
    }

    public static function deactivate()
    {
        self::module_action('deactivate', false);
    }
}

Plugin::get_instance();
