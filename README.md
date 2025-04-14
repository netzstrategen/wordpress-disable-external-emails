# disable-external-emails

Restricts outbound emails to a list of defined email domains and addresses.

Quick links: [Installation](#installation)

## Installation

### Install as Git submodule

1. Add the plugin as submodule.
    ```sh
    git submodule add --name disable-external-emails git@github.com:netzstrategen/disable-external-emails.git wp-content/mu-plugins/disable-external-emails
    ```

2. Add a must-use plugin loader file `wp-content/mu-plugins/disable-external-emails.php`:
    ```php
    <?php
    
    /*
      Plugin Name: Disable external emails
      Version: 1.0.0
      Description: Prevents accidental sending of emails to external recipients during development.
      Author: netzstrategen
      Author URI: https://netzstrategen.com
    */
    
    require_once __DIR__ . '/disable-external-emails/disable-external-emails.php';
    ```

3. Configure a list of allowed email domains and addresses in `wp-config.php`:
    ```php
    const DISABLE_EXTERNAL_EMAILS_EXCEPT = '@example.com, exact@example.com, @netzstrategen.com';
    ```

## Come create with us!

Originally authored by [Bogdan Arizancu](https://github.com/bogdanarizancu) and [Daniel Kudwien](https://github.com/sun).

<p align="center">
<a href="https://makers99.com/#jobs"><img src="https://raw.githubusercontent.com/makers99/makers99/main/assets/makers99-github-banner.png" width="100%"></a>
</p>
