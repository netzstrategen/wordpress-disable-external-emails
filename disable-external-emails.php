<?php

/*
Plugin Name: Disable external emails
Description: Prevents accidental sending of emails to external recipients during development.
Version: 1.0.0
Author: netzstrategen
Author URI: https://netzstrategen.com
*/

namespace Netzstrategen\DisableExternalEmails;

if (isset($_SERVER['SERVER_NAME']) && preg_match('@(nest|local|test)$@', $_SERVER['SERVER_NAME'])) {
  add_action('muplugins_loaded', __NAMESPACE__ . '\Plugin::muplugins_loaded');
}

/**
 * Prevents accidental sending of emails to external recipients during development.
 */
class Plugin {

  /**
   * @implements muplugins_loaded
   */
  public static function muplugins_loaded() {
    if (!defined('DISABLE_EXTERNAL_EMAILS_EXCEPT')) {
      define('DISABLE_EXTERNAL_EMAILS_EXCEPT', '@netzstrategen.com');
    }
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
    if (stripos($args['to'], DISABLE_EXTERNAL_EMAILS_EXCEPT) === FALSE) {
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
  public static function phpmailer_init(&$phpmailer) {
    if (is_array($phpmailer->getToAddresses()) && !empty($phpmailer->getToAddresses()[0])) {
      foreach (array_filter($phpmailer->getToAddresses()[0]) as $address) {
        if (strpos($address, DISABLE_EXTERNAL_EMAILS_EXCEPT) === FALSE) {
          $phpmailer->ClearAllRecipients();
          $phpmailer->ClearAttachments();
          $phpmailer->ClearCustomHeaders();
          $phpmailer->ClearReplyTos();
        }
      }
    }
    return $phpmailer;
  }

}
