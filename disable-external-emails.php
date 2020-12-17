<?php

/*
Plugin Name: Disable external emails
Description: Prevents accidental sending of emails to external recipients during development.
Version: 1.0.0
Author: netzstrategen
Author URI: https://netzstrategen.com
*/

namespace Netzstrategen\DisableExternalEmails;

if (!defined('WP_SITEURL') || preg_match('/(sandbox|stage|staging|qa)\.|\.(nest|local|test)/', WP_SITEURL)) {
  add_action('muplugins_loaded', __NAMESPACE__ . '\Plugin::muplugins_loaded');
}

/**
 * Prevents accidental sending of emails to external recipients during development.
 *
 * The constant DISABLE_EXTERNAL_EMAILS_EXCEPT can be comma-separated list of
 * email addresses or email domains that should still receive emails. Examples:
 *
 * ```
 * const DISABLE_EXTERNAL_EMAILS_EXCEPT = '@example.com, @netzstrategen.com';
 * const DISABLE_EXTERNAL_EMAILS_EXCEPT = 'only.me@netzstrategen.com';
 * ```
 */
class Plugin {

  private static $allowedEmails = [];

  /**
   * @implements muplugins_loaded
   */
  public static function muplugins_loaded() {
    if (defined('DISABLE_EXTERNAL_EMAILS_EXCEPT')) {
      static::$allowedEmails = array_map('trim', explode(',', DISABLE_EXTERNAL_EMAILS_EXCEPT));
    }
    else {
      static::$allowedEmails = ['@netzstrategen.com'];
    }
    static::$allowedEmails = '/' . implode('|', array_map('preg_quote', static::$allowedEmails)) . '/i';

    add_filter('option_active_plugins', __CLASS__ . '::option_active_plugins');
    add_action('phpmailer_init', __CLASS__ . '::phpmailer_init', 99, 1);
    add_action('wp_mail', __CLASS__ . '::wp_mail');
  }

  /**
   * Disables common SMTP plugins.
   *
   * Ensures wp_mail() uses PHP sendmail, so that Mailhog can intercept them.
   *
   * @todo Check whether Mailhog is installed. On Linux/Unix based systems
   *   (including MacOS) and Windows with Gnutils installed, sendmail exists
   *   and the email will therefore be actually be sent out.
   *
   * @implements option_active_plugins
   */
  public static function option_active_plugins(array $plugins): array {
    $plugins = array_diff($plugins, [
      'gmail-smtp/main.php',
      'wp-mail-smtp/wp_mail_smtp.php',
    ]);
    return $plugins;
  }

  /**
   * Ensures emails are only sent to internal recipient email addresses.
   *
   * @implements wp_mail
   */
  public static function wp_mail($args) {
    if (!preg_match(static::$allowedEmails, $args['to'])) {
      unset($args['to']);
      if (!empty($args['headers'])) {
        if (is_array($args['headers'])) {
          unset($args['headers']['cc']);
          unset($args['headers']['bcc']);
        }
        else {
          $args['headers'] = preg_replace("/Cc: (.*)/", '', $args['headers']);
          $args['headers'] = preg_replace("/Bcc: (.*)/", '', $args['headers']);
        }
      }
    }
    return $args;
  }

  /**
   * Prevents sending emails to external email addresses.
   *
   * @implements phpmailer_init
   */
  public static function phpmailer_init($phpmailer) {
    foreach ($phpmailer->getToAddresses() as $recipient) {
      if (preg_match(static::$allowedEmails, $recipient[0]) === FALSE) {
        $phpmailer->ClearAllRecipients();
        $phpmailer->ClearAttachments();
        $phpmailer->ClearCustomHeaders();
        $phpmailer->ClearReplyTos();
        break;
      }
    }
    return $phpmailer;
  }

}
