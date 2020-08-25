<?php

namespace Netzstrategen\LocalhostSettings;

if (class_exists('DisableEmailsOnLocalhost')) {
  return;
}

/*
Plugin Name: Disable emails on localhost.
Description: Makes sure no accidental emails are sent during development
localhost work.
Version: 1.0.0
Author: netzstrategen
Author URI: https://netzstrategen.com
 */

/**
 * Makes sure no accidental emails are sent during development.
 */
class DisableEmailsOnLocalhost {

  /**
   * Hooks into 'muplugins_loaded'.
   *
   * @implements muplugins_loaded
   */
  public static function muplugins_loaded() {
    if (self::isLocalMachine() === FALSE) {
      return;
    }

    add_filter('option_active_plugins', __CLASS__ . '::option_active_plugins');
    add_action('phpmailer_init', __CLASS__ . '::phpmailer_init', 99, 1);
  }

  /**
   * Disables usual smtp related plugins.
   * This is to make sure only 'wp_mail()' is used, so that Mailhog would not be
   * overridden, so it sometimes is the case.
   *
   * @implements option_active_plugins
   */
  public static function option_active_plugins(array $plugins): array {
    $disabled_plugins = [
      'gmail-smtp/main.php',
      'wp-mail-smtp/wp_mail_smtp.php',
    ];
    $plugins = array_diff($plugins, $disabled_plugins);
    return $plugins;
  }

  /**
   * Ensures mail is sent only if recipient's email address is internal.
   *
   * @implements wp_mail
   */
  public static function wp_mail($args) {
    if (stripos($args['to'], '@netzstrategen.com') === FALSE) {
      unset($args['to']);
    }
    return $args;
  }

  /**
   * Prevents sending emails except to netzstrategen.com.
   *
   * @implements phpmailer_init
   */
  public static function phpmailer_init(&$phpmailer) {
    if (is_array($phpmailer->getToAddresses()) && !empty($phpmailer->getToAddresses()[0])) {
      foreach (array_filter($phpmailer->getToAddresses()[0]) as $address) {
        if (strpos($address, '@netzstrategen.com') === FALSE) {
          $phpmailer->ClearAllRecipients();
          $phpmailer->ClearAttachments();
          $phpmailer->ClearCustomHeaders();
          $phpmailer->ClearReplyTos();
        }
      }
    }
    return $phpmailer;
  }

  /**
   * Checks if this is a local machine, using SERVER_NAME.
   */
  private static function isLocalMachine(): bool {
    if (!empty($_SERVER['SERVER_NAME']) && preg_match('@(nest|local|test)$@', $_SERVER['SERVER_NAME'])) {
      return TRUE;
    }
    return FALSE;
  }

}

add_action('muplugins_loaded', __NAMESPACE__ . '\DisableEmailsOnLocalhost::muplugins_loaded');
