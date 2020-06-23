<?php
/**
 * @package CzarSoft\WP_CLI_Freemius_Toolkit
 */

namespace CzarSoft\WP_CLI_Freemius_Toolkit\Commands;

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
        WP_CLI::line('WP-CLI Freemius Toolkit version: v' . WP_CLI_FREEMIUS_TOOLKIT_VERSION);
        WP_CLI::line();
        WP_CLI::line('Created by Mateusz Czardybon <mczardybon.czarsoft@gmail.com>');
//		WP_CLI::line('Gitlab: https://git.netizens.pl/wp/wp-cli-neti');
        WP_CLI::line();
    }
}

WP_CLI::add_command('freemius-toolkit', __NAMESPACE__ . '\\Freemius_Toolkit_Command', array('when' => 'before_wp_load'));
