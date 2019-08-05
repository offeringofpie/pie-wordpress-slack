<?

/**
 * custom option and settings
 */

function pie_slack_settings_init()
{
  // register a new setting for "pie_slack" page
  register_setting('pie_slack', 'pie_slack_options');
  add_settings_section('main_settings', 'Main Settings', false, 'pie_slack');
  add_settings_section('select_notification_type', 'Select Notifications', false, 'pie_slack');
}
