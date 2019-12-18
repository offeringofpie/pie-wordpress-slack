<?php
/**
 * Plugin Name: WP Pie Slack
 * Plugin URI: https://github.com/offeringofpie/pie-wordpress-slack.git
 * description: Send messages to slack when an event happens on WordPress.
 * Version: 0.0.3
 * Author: J Lopes
 * Author URI: https://jlopes.eu
 * License: MIT
 */

define('PLUGIN_FILE_DIR', plugin_basename(__FILE__));
define('PLUGIN_FILE_NAME', __FILE__);

include __DIR__ . '/settings/index.php';
include __DIR__ . '/PieSlack.php';

new PieSlack();
