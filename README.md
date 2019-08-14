# WP Pie Slack
Send messages to slack when an event happens on WordPress

## Description
WP Pie Slack is a free, easy-to-use, automated notification tool that integrates with Slack. You can elect to send notifications when certain events are triggered on your WordPress website.

* Logins
* Failed Login Attempts
* User Creation
* User Removal
* User Role Change
* Page Updates
* Media Uploaded
* Media Deleted
* Plugin Activation
* Plugin Deactivation

## How to
The plugin requires to use the [Incoming Webhook](https://slack.com/services/BLP20SJNN) App made by Slack. Once the App is properly installed in your Slack instance, you can use the Webhook URL offered in the page.

In the options page you can add this Webhook URL, the channel you wish the messages to be sent, the Bot name, and its relavant emoji.

## TODO
- [ ] Custom Channel+Emoji options (with fallback) per action.
- [x] Improvements on the messaging format