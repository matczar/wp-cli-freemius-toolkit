<?php
// Only load this plugin once and bail if WP CLI is not present
if ( defined( 'WP_CLI_FREEMIUS_TOOLKIT_VERSION' ) || ! defined( 'WP_CLI' ) ) {
	return;
}

define( 'WP_CLI_FREEMIUS_TOOLKIT_VERSION', 'v0.4.2' );
define( 'WP_CLI_FREEMIUS_TOOLKIT_PATH', dirname( __FILE__ ) );
define( 'WP_CLI_FREEMIUS_TOOLKIT_COMMANDS_PATH', WP_CLI_FREEMIUS_TOOLKIT_PATH . '/includes/commands' );

require_once( WP_CLI_FREEMIUS_TOOLKIT_PATH . '/includes/helpers/JsonFormatter.php' );
require_once( WP_CLI_FREEMIUS_TOOLKIT_PATH . '/includes/helpers/Freemius.php' );
require_once( WP_CLI_FREEMIUS_TOOLKIT_PATH . '/includes/helpers/Utils.php' );

require_once( WP_CLI_FREEMIUS_TOOLKIT_COMMANDS_PATH . '/Freemius_Toolkit_Command.php' );
require_once( WP_CLI_FREEMIUS_TOOLKIT_COMMANDS_PATH . '/Version_Command.php' );
