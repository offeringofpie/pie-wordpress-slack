<?php

class PieSlack {
  /**
   * PieSlack constructor.
   */
  public function __construct() {
    add_filter('plugin_action_links_' . PLUGIN_FILE_DIR, [$this, 'add_links']);
    add_action('admin_menu', [$this, 'register_page']);

    add_action('admin_enqueue_scripts', [$this, 'add_stylesheet']);

    if (null !== get_option('pie_slack_endpoint') && null !== get_option('pie_slack_channel')) {
      $this->hook_actions();
    }
  }

  /**
   * @param $hook
   */
  public function add_stylesheet($hook) {
    wp_enqueue_style('pie_slack_admin_css', plugins_url('settings/style.css', PLUGIN_FILE_NAME));
  }

  /**
   * @param  $links
   * @return array
   */
  public function add_links($links) {
    $mylinks = ['<a href="options-general.php?page=pie_slack">' . __('Settings', 'General') . '</a>'];

    return array_merge($links, $mylinks);
  }

  /**
   *
   */
  public function register_page() {
    add_options_page('WP Pie Slack Settings', 'Slack', 'manage_options', 'pie_slack', 'pie_slack_options_page_html');
  }

  /**
   *
   */
  public function hook_actions() {
    if (!empty(get_option('pie_slack_on_page_update'))) {
      add_action('save_post', [$this, 'page_updated']);
    }
    if (!empty(get_option('pie_slack_on_user_deleted'))) {
      add_action('delete_user', [$this, 'user_deleted']);
    }
    if (!empty(get_option('pie_slack_on_user_login'))) {
      add_action('wp_login', [$this, 'user_login']);
    }
    if (!empty(get_option('pie_slack_on_user_login_failed'))) {
      add_action('wp_login_failed', [$this, 'user_login_failed']);
    }
    if (!empty(get_option('pie_slack_on_user_created'))) {
      add_action('user_register', [$this, 'user_created']);
    }
    if (!empty(get_option('pie_slack_on_user_role_changed'))) {
      add_action('set_user_role', [$this, 'user_role_changed'], 10, 3);
    }
    if (!empty(get_option('pie_slack_on_upload'))) {
      add_action('add_attachment', [$this, 'media_upload']);
    }
    if (!empty(get_option('pie_slack_on_upload_delete'))) {
      add_action('delete_attachment', [$this, 'media_delete']);
    }
    if (!empty(get_option('pie_slack_on_plugin_activated'))) {
      add_action('activated_plugin', [$this, 'plugin_activated']);
    }
    if (!empty(get_option('pie_slack_on_plugin_deactivated'))) {
      add_action('deactivated_plugin', [$this, 'plugin_deactivated']);
    }
    // if ( !empty( get_option( 'pie_slack_on_update' ) ) ) {
    //   register_activation_hook(__FILE__, 'my_activation');
    //   function my_activation() {
    //     if (! wp_next_scheduled ( 'pie_slack_daily_check' )) {
    //       wp_schedule_event(time(), 'daily', 'pie_slack_daily_check');
    //     }
    //   }
    //   add_action('pie_slack_daily_check', 'run_daily');
    // } else {
    //   wp_clear_scheduled_hook('pie_slack_daily_check');
    // }
  }

  /**
   * @param $id
   */
  public function page_updated($id) {
    $post_title  = esc_html(get_the_title($id));
    $post_type   = get_post_type($id);
    $post_url    = get_permalink($id);
    $post_status = get_post_status($id);
    $post_author = get_userdata(get_post_meta($id, '_edit_last', true))->user_email;
    $data        = [
      "title"    => "Page updated",
      "subtitle" => $post_title,
      "text"     => 'Url: ' . $post_url . '
Author: ' . $post_author . '
Type: ' . $post_type . '
Status: ' . $post_status,
    ];

    // don't send if the post is a revision or autodraft
    if (wp_is_post_revision($id) || $post_status === 'autodraft') {
      return;
    }

    $this->pie_send_to_slack($data);

  }

  /**
   * @param $id
   */
  public function user_created($id) {
    $user       = get_userdata($id);
    $user_email = $user->user_email;
    $user_role  = implode($user->roles);
    $message    = 'Account created for ' . $user_email . ' (' . $user_role . ').';
    $data       = [
      "title" => "Account Created",
      "text"  => 'E-mail: ' . $user_email . '
Role(s): ' . $user_role,
    ];

    $this->pie_send_to_slack($data);
  }

  /**
   * @param $id
   */
  public function user_deleted($id) {
    $user = get_userdata($id)->user_email;
    $data = [
      "title" => "Account Deleted",
      "text"  => 'E-mail: ' . $user,
      "color" => "#e51670",
    ];

    $this->pie_send_to_slack($data);
  }

  /**
   * @param $id
   * @param $role
   * @param $old_roles
   */
  public function user_role_changed($id, $role, $old_roles) {
    $user = get_userdata($id)->user_email;
    $data = [
      "title" => "Account Roles Changed",
      "text"  => 'E-mail: ' . $user . '
Before: ' . implode(", ", $old_roles),
    ];

    $this->pie_send_to_slack($data);
  }

  /**
   * @param $user_login
   */
  public function user_login($user_login) {
    $user       = get_user_by('login', $user_login);
    $user_email = $user->user_email;
    $data       = [
      "title" => "User Login",
      "text"  => 'E-mail: ' . $user_email,
    ];

    $this->pie_send_to_slack($data);
  }

  /**
   * @param $user_login_failed
   */
  public function user_login_failed($username) {
    $data = [
      "title" => "Login Failed",
      "text"  => $username,
      "color" => "#e51670",
    ];

    $this->pie_send_to_slack($data);
  }

  /**
   * @param $id
   */
  public function media_upload($id) {
    $path        = get_post_meta($id, '_wp_attached_file', true);
    $filesize    = number_format(filesize(get_attached_file($id)) / 1024, 2) . 'KB';
    $post_author = get_userdata(get_post($id)->post_author)->user_email;
    $data        = [
      "title"    => "Media uploaded",
      "subtitle" => $path,
      "text"     => 'Author: ' . $post_author . '
Size: ' . $filesize,
    ];

    $this->pie_send_to_slack($data);
  }

  /**
   * @param $id
   */
  public function media_delete($id) {
    $path        = get_post_meta($id, '_wp_attached_file', true);
    $post_author = get_userdata(get_post($id)->post_author)->user_email;
    $data        = [
      "title"    => "Media removed",
      "subtitle" => $path,
      "text"     => 'Author: ' . $post_author,
      "color"    => "#e51670",
    ];

    $this->pie_send_to_slack($data);
  }

  /**
   * @param $plugin
   */
  public function plugin_activated($plugin) {
    $data = [
      "title"    => "Plugin Activated",
      "subtitle" => $plugin,
    ];

    $this->pie_send_to_slack($data);
  }

  /**
   * @param $plugin
   */
  public function plugin_deactivated($plugin) {
    $data = [
      "title"    => "Plugin Deactivated",
      "subtitle" => $plugin,
      "color"    => "#e51670",
    ];

    $this->pie_send_to_slack($data);
  }

  // public function run_daily() {
  //   do_action( "wp_update_plugins" );
  //   $update_plugins = get_site_transient( 'update_plugins' );
  //   if ( !empty( $update_plugins->response ) ) {
  //     $plugins_need_update = $update_plugins->response;
  //     $active_plugins      = array_flip( get_option( 'active_plugins' ) );
  //     $plugins_need_update = array_intersect_key( $plugins_need_update, $active_plugins );
  //   }
  // }

  /**
   * function that sends the event to slack
   *
   * @param string $body Body message sent to slack
   */
  public function pie_send_to_slack($data) {
    $endpoint = get_option('pie_slack_endpoint');
    $channel  = get_option('pie_slack_channel');
    $bot_name = get_option('pie_slack_bot_name');
    $emoji    = get_option('pie_slack_bot_emoji');

    $data = [
      'payload' => json_encode(
        [
          'channel'    => $channel,
          'text'       => false,
          "fallback"   => $data['title'] . ':' . $data['text'],
          "pretext"    => $data['title'],
          "color"      => $data['color'] ? $data['color'] : "#49c39e",
          "fields"     => [
            [
              "title" => $data['subtitle'] ? $data['subtitle'] : $data['title'], // The title may not contain markup and will be escaped for you
              "value" => $data['text'],
              "short" => false, // Optional flag indicating whether the `value` is short enough to be displayed side-by-side with other values
            ],
          ],
          'username'   => $bot_name,
          'icon_emoji' => $emoji,
        ]
      ),
    ];

    wp_remote_post(
      $endpoint,
      [
        'method'      => 'POST',
        'timeout'     => 30,
        'redirection' => 5,
        'httpversion' => '1.0',
        'blocking'    => true,
        'headers'     => [],
        'body'        => $data,
        'cookies'     => [],
      ]
    );
  }
}
