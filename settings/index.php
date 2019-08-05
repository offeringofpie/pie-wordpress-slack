<?php
global $option_sections;
$option_sections = [
  "main_options" => [
    "classes" => "pie_slack_options-section main_options lg__fb_50 bc_green",
    "title" => __('Main options', 'pie_slack'),
    "fields" => [
      "pie_slack_endpoint" => [
        "type" => "text",
        "label" => __('Slack Endpoint', "pie_slack"),
        "value" => get_option('pie_slack_endpoint'),
        "placeholder" => "https://hooks.slack.com/services/<extra_code>",
        "description" => __('More information <a href="https://api.slack.com/incoming-webhooks" alt="Slack - Incoming Webhooks" target="_blank">here</a>.', 'pie_slack')
      ],
      "pie_slack_channel" => [
        "type" => "text",
        "label" => __('Send to Channel', "pie_slack"),
        "value" => get_option('pie_slack_channel'),
        "placeholder" => "#general",
        "description" => ""
      ],
      "pie_slack_bot_name" => [
        "type" => "text",
        "label" => __('Bot Name', "pie_slack"),
        "value" => get_option('pie_slack_bot_name'),
        "placeholder" => "Wordpress Name",
        "description" => ""
      ],
      "pie_slack_bot_emoji" => [
        "type" => "text",
        "label" => __('Emoji', "pie_slack"),
        "value" => get_option('pie_slack_bot_emoji'),
        "placeholder" => ":watermelon:",
        "description" => __('More information <a href="https://www.webfx.com/tools/emoji-cheat-sheet/" alt="Slack - Emoji list" target="_blank">here</a>.', 'pie_slack')
      ],

    ]
  ],
  "hooks" => [
    "classes" => "pie_slack_options-section hooks lg__fb_50 bc_pink",
    "title" => __('Send on Events', 'pie_slack'),
    "fields" => [
      "pie_slack_on_user_login" => [
        "type" => "checkbox",
        "label" => __('User Login', "pie_slack"),
        "value" => get_option('pie_slack_on_user_login'),
        "placeholder" => "",
        "description" => ""
      ],
      "pie_slack_on_user_deleted" => [
        "type" => "checkbox",
        "label" => __('User Removed', "pie_slack"),
        "value" => get_option('pie_slack_on_user_deleted'),
        "placeholder" => "",
        "description" => ""
      ],
      "pie_slack_on_user_created" => [
        "type" => "checkbox",
        "label" => __('User Created', "pie_slack"),
        "value" => get_option('pie_slack_on_user_created'),
        "placeholder" => "",
        "description" => ""
      ],
      "pie_slack_on_user_role_changed" => [
        "type" => "checkbox",
        "label" => __('User Role Change', "pie_slack"),
        "value" => get_option('pie_slack_on_user_role_changed'),
        "placeholder" => "",
        "description" => ""
      ],
      "pie_slack_on_page_update" => [
        "type" => "checkbox",
        "label" => __('Page Update', "pie_slack"),
        "value" => get_option('pie_slack_on_page_update'),
        "placeholder" => "",
        "description" => ""
      ],
      "pie_slack_on_upload" => [
        "type" => "checkbox",
        "label" => __('Media uploaded', "pie_slack"),
        "value" => get_option('pie_slack_on_upload'),
        "placeholder" => "",
        "description" => ""
      ],
      "pie_slack_on_upload_delete" => [
        "type" => "checkbox",
        "label" => __('Media removed', "pie_slack"),
        "value" => get_option('pie_slack_on_upload_delete'),
        "placeholder" => "",
        "description" => ""
      ],
    ]
  ]
];

function handle_form()
{
  global $option_sections;
  if (
    !isset($_POST['pie_slack_form']) || !wp_verify_nonce($_POST['pie_slack_form'], 'pie_slack_update')
  ) {
    echo '<div class="error">
            <p>' . __('Something prevented your fields to update. Please try again', 'pie_slack') . '</p>
          </div>';
    exit;
  } else {

    foreach ($option_sections as $section_name => $section) {
      foreach ($section['fields'] as $field_name => $field) {
        $pie_slack_field = sanitize_text_field($_POST[$field_name]);
        update_option($field_name, $pie_slack_field);

        $option_sections[$section_name]['fields'][$field_name]['value'] = $_POST[$field_name];
      }
    }

    echo '<div class="updated">
            <p>' . __('Your Settings were successfully saved.', 'pie_slack') . '</p>
          </div>';
  }
}

function pie_slack_options_page_html()
{
  global $option_sections;

  if ($_POST['updated'] === 'true') {
    handle_form();
  }

  if (!current_user_can('manage_options')) {
    return;
  }

  if (isset($_GET['settings-updated'])) {
    add_settings_error('pie_slack_messages', 'pie_slack_message', __('Settings Saved', 'pie_slack'), 'updated');
  }

  settings_errors('pie_slack_messages');
  ?>
  <div class="wrap">
    <h2><?= esc_html(get_admin_page_title()); ?></h2>
    <form method="POST" class="lg__d_f lg__fw_w">
      <input type="hidden" name="updated" value="true" />
      <?php wp_nonce_field('pie_slack_update', 'pie_slack_form'); ?>
      <? foreach ($option_sections as $section_name => $section) { ?>
        <section class="<?= $section['classes']; ?>">
          <div class="pie_slack_options-wrapper">
            <h2><?= $section['title']; ?></h2>
            <div class="pie_slack_options-fields">
              <? foreach ($section['fields'] as $field_name => $field) {
                $field_markup = '';

                switch ($field['type']) {
                  case 'checkbox':
                    $checked = $field['value'] ? 'checked="checked"' : '';
                    $field_markup = '<div class="checkbox mb_1rem">
                <input name="' . $field_name . '" id="' . $field_name . '" type="checkbox" value="1"' . $checked . ' class="regular-text" />
                <label for="' . $field_name . '">' . $field['label'] . '</label>
                </div>';
                    break;
                  default:
                    $field_markup = '<div class="mb_1rem"><label for="' . $field_name . '">
              <span class="pie_slack-label d_ib">' . $field['label'] . '</span>
              <input name="' . $field_name . '" id="' . $field_name . '" type="text" value="' . $field["value"] . '" placeholder="' . $field["placeholder"] . '" class="regular-text" />
                        </label>';
                    $field_markup .= $field['description'] ? '<p class="description ta_r">' . $field['description'] . '</p>' : '';
                    $field_markup .= '</div>';
                    break;
                }

                echo $field_markup;
              } ?>
            </div>
          </div>
        </section>
      <? } ?>
      <p class="submit lg__fb_100">
        <input type="submit" name="submit" id="submit" class="button button-primary" value="<?= __('Update', 'general'); ?>">
      </p>
    </form>
  </div>
<?php
}
