<?php

namespace CzarSoft\WP_CLI_Freemius_Toolkit\Helpers;

use Dotenv\Dotenv;
use Symfony\Component\Yaml\Yaml;
use WP_CLI;
use WP_CLI\Utils;
use Exception;

class Freemius
{
    /**
     * @var \Freemius_Api[]
     */
    static $instance = array();
    static $freemius_conf = null;

    public static function get_api($scope)
    {
        if (isset(self::$instance[$scope])) {
            return self::$instance[$scope];
        }
        try {
            $config_dir = dirname(WP_CLI::get_runner()->get_global_config_path());
            $user_config_dir = Utils\get_home_dir() . '/.wp-cli';
            if (file_exists($user_config_dir . '/.freemius')) {
                $config_dir = $user_config_dir;
            }
            if (file_exists($config_dir . '/.freemius')) {
                $dotenv = Dotenv::createImmutable($config_dir, '.freemius');
                $dotenv->load();
            }
            $err = array();
            foreach (array('FS__API_DEV_ID', 'FS__API_PUBLIC_KEY', 'FS__API_SECRET_KEY') as $key) {
                if (empty($_ENV[$key])) {
                    $err[] = $key;
                }
            }
            if (!empty($err)) {
                throw new Exception(sprintf('One or more environment variables is missing: %s.', join(', ', $err)));
            }
        } catch (\Exception $e) {
            WP_CLI::error($e->getMessage());
        }

        self::$instance[$scope] = new \Freemius_Api($scope, $_ENV['FS__API_DEV_ID'], $_ENV['FS__API_PUBLIC_KEY'], $_ENV['FS__API_SECRET_KEY']);

        return self::$instance[$scope];
    }

    public static function get_conf()
    {
        if (self::$freemius_conf !== null) {
            return self::$freemius_conf;
        }

        try {
            self::$freemius_conf = Yaml::parseFile(getcwd() . '/.freemius.yml');
        } catch (\Exception $e) {
            WP_CLI::error($e->getMessage());
        }

        if (empty(self::$freemius_conf['plugin_id'])) {
            WP_CLI::error('The .freemius file does not contain the "plugin_id" key.');
        }

        return self::$freemius_conf;
    }
}
