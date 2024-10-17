<?php
/**
 * Plugin Name: SendFox for Gravity Forms
 * Plugin URI:  https://gravityranger.com/sendfox-addon
 * Description: Integrates Gravity Forms with SendFox via a feed-based system with global API settings.
 * Version:     0.1-beta
 * Author:      Chris Eggleston
 * Author URI:  https://gravityranger.com
 * License:     GPL-2.0+
 * Text Domain: sendfox-for-gravity-forms
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Load the class when Gravity Forms is initialized.
add_action( 'gform_loaded', array( 'GF_SendFox_Addon_Bootstrap', 'load' ), 5 );

class GF_SendFox_Addon_Bootstrap {
    public static function load() {
        if ( class_exists( 'GFForms' ) ) {
            require_once( 'includes/class-gf-sendfox-addon.php' );
            GFAddOn::register( 'GFSendFoxAddon' );
        }
    }
}

function gf_sendfox_addon() {
    return GFSendFoxAddon::get_instance();
}
