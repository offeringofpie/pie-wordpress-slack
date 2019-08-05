<?php
class PieSlack
{
  function __construct()
  {
    add_filter('plugin_action_links_pie-slack/pie-slack.php', [$this, 'add_links']);
    add_action('admin_menu', [$this, 'register_page']);

    add_action('admin_enqueue_scripts', [$this, 'add_stylesheet']);

    if (null !== get_option('pie_slack_endpoint') && null !== get_option('pie_slack_channel')) {
      $this->hook_actions();
    }
  }

  function add_stylesheet($hook)
  {
    wp_enqueue_style('pie_slack_admin_css', plugins_url('settings/style.css', __FILE__));
  }

  public function add_links($links)
  {
    $mylinks = ['settings' => '<a href="options-general.php?page=pie_slack">' . __('Settings', 'General') . '</a>'];

    return array_merge($links, $mylinks);
  }

  public function register_page()
  {
    add_options_page('WP Pie Slack Settings', 'Slack', 'manage_options', 'pie_slack', 'pie_slack_options_page_html');
  }

  function hook_actions()
  {
    if (null !== get_option('pie_slack_on_page_update')) {
      add_action('save_post', [$this, 'page_updated']);
    }
    if (null !== get_option('pie_slack_on_user_deleted')) {
      add_action('delete_user', [$this, 'user_deleted']);
    }
    if (null !== get_option('pie_slack_on_user_login')) {
      add_action('wp_login', [$this, 'user_login']);
    }
    if (null !== get_option('pie_slack_on_user_created')) {
      add_action('user_register', [$this, 'user_created']);
    }
    if (null !== get_option('pie_slack_on_user_role_changed')) {
      add_action('set_user_role', [$this, 'user_role_changed'], 10, 3);
    }
    if (null !== get_option('pie_slack_on_upload')) {
      add_action('add_attachment', [$this, 'media_upload']);
    }
    if (null !== get_option('pie_slack_on_upload_delete')) {
      add_action('delete_attachment', [$this, 'media_delete']);
    }
  }

  function page_updated($id)
  {
    // don't send if the post is a revision
    if (wp_is_post_revision($id)) {
      return;
    }

    $post_title = esc_html(get_the_title($id));
    $post_type = get_post_type($id);
    $post_url = get_permalink($id);
    $post_status = get_post_status($id);
    $post_author = get_userdata(get_post_meta($id, '_edit_last', true))->user_email;

    $message = $post_url . " \"" . $post_title . "\" [" . $post_type . "][" . $post_status . "] was updated by " . $post_author;

    $this->pie_send_to_slack($message);
  }

  function user_created($id)
  {
    $user = get_userdata($id);
    $user_email = $user->user_email;
    $user_role = implode($user->roles);
    $message = "Account created for " . $user_email . " (" . $user_role . ").";

    $this->pie_send_to_slack($message);
  }

  function user_deleted($id)
  {
    $user = get_userdata($id)->user_email;
    $message = "Account deleted for " . $user;

    $this->pie_send_to_slack($message);
  }

  function user_role_changed($id, $role, $old_roles)
  {
    $user = get_userdata($id)->user_email;
    $message = "Role changes for " . $user . ': ' . implode($old_roles) . ' > ' . $role;

    $this->pie_send_to_slack($message);
  }

  function user_login($user_login)
  {
    $user = get_user_by('login', $user_login);
    $user_email = $user->user_email;
    $message = $user_email . " just logged in";

    $this->pie_send_to_slack($message);
  }

  function media_upload($id)
  {
    $path = get_post_meta($id, '_wp_attached_file', true);
    $filesize = number_format(filesize(get_attached_file($id)) / 1024, 2) . "KB";
    $post_author = get_userdata(get_post($id)->post_author)->user_email;

    $message = $path . " [" . $filesize . "]" . " has been uploaded by " . $post_author;

    $this->pie_send_to_slack($message);
  }

  function media_delete($id)
  {
    $path = get_post_meta($id, '_wp_attached_file', true);
    $post_author = get_userdata(get_post($id)->post_author)->user_email;

    $message = $path . " has been removed by " . $post_author;

    $this->pie_send_to_slack($message);
  }

  /**
   * function that sends the event to slack
   *
   * @param   string  $body   Body message sent to slack
   * @param   string  $channel   What channel it sends the message to
   * @param   string  $bot_name  Name that is displayed in the slack channel
   * @param   string  $emoji     Emoji icon for the bot
   */
  function pie_send_to_slack($body)
  {
    $endpoint = get_option('pie_slack_endpoint');
    $channel = get_option('pie_slack_channel');
    $bot_name = get_option('pie_slack_bot_name');
    $emoji = get_option('pie_slack_bot_emoji');

    $data = array(
      'payload'   => json_encode(
        array(
          "channel" => $channel,
          "text" => $body,
          "username" => $bot_name,
          "icon_emoji" => $emoji
        )
      )
    );

    wp_remote_post(
      $endpoint,
      array(
        'method' => 'POST',
        'timeout' => 30,
        'redirection' => 5,
        'httpversion' => '1.0',
        'blocking' => true,
        'headers' => array(),
        'body' => $data,
        'cookies' => array()
      )
    );
  }
}
