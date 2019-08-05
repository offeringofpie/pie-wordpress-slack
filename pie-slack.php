<?php
/*
  Plugin Name: WP Pie Slack
  Plugin URI: https://github.com/offeringofpie/pie-wordpress-slack.git
  description: Send messages to slack when an event happens on WordPress.
  Version: 0.0.1
  Author: J Lopes
  Author URI: https://jlopes.eu
  License: MIT
*/

include __DIR__ . '/settings/index.php';
include __DIR__ . '/PieSlack.php';

new PieSlack();
