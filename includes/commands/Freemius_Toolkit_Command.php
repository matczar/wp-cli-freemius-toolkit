<?php
/**
 * @package CzarSoft\WP_CLI_Freemius_Toolkit
 */

namespace CzarSoft\WP_CLI_Freemius_Toolkit\Commands;

use CzarSoft\WP_CLI_Freemius_Toolkit\Helpers\Freemius;
use WP_CLI;

/**
 * Freemius Toolkit
 */
class Freemius_Toolkit_Command extends \WP_CLI_Command
{
    /**
     * Displays General Info about WP-CLI Freemius Toolkit
     *
     * @param array $args
     * @param array $assoc_args
     */
    public function info($args, $assoc_args)
    {
        WP_CLI::line('WP-CLI Freemius Toolkit version: ' . WP_CLI_FREEMIUS_TOOLKIT_VERSION);
        WP_CLI::line();
        WP_CLI::line('Created by Mateusz Czardybon <mczardybon.czarsoft@gmail.com>');
        WP_CLI::line('GitHub: https://github.com/matczar/wp-cli-freemius-toolkit');
        WP_CLI::line();
    }

    /**
     * Test connection with Freemius API
     *
     * @param array $args
     * @param array $assoc_args
     */
    public function ping($args, $assoc_args)
    {
        $api = Freemius::get_api('none');
        $result = $api->Api('ping.json');
        if (isset($result->error->message)) {
            WP_CLI::error($result->error->message);
        }
        if (!isset($result->api)) {
            WP_CLI::error('Invalid response.');
        }
        WP_CLI::success($result->api);
    }
}

WP_CLI::add_command('freemius-toolkit', __NAMESPACE__ . '\\Freemius_Toolkit_Command', array('when' => 'before_wp_load'));
