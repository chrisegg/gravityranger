<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Load the Gravity Forms Add-On Framework.
GFForms::include_addon_framework();

class GFSendFoxAddon extends GFAddOn {

    protected $_version = '1.0';
    protected $_min_gravityforms_version = '2.5';
    protected $_slug = 'gf-sendfox-addon';
    protected $_path = 'sendfox-for-gravity-forms/gf-sendfox-addon.php';
    protected $_full_path = __FILE__;
    protected $_title = 'SendFox for Gravity Forms';
    protected $_short_title = 'SendFox';

    // Enable feeds.
    protected $_capabilities = array( 'gravityforms_sendfox' );
    protected $_capabilities_settings_page = 'gravityforms_sendfox';
    protected $_capabilities_form_settings = 'gravityforms_sendfox';
    protected $_capabilities_uninstall = 'gravityforms_sendfox_uninstall';

    private static $_instance = null;

    // Singleton pattern.
    public static function get_instance() {
        if ( self::$_instance === null ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    // Initialize hooks and other functionality.
    public function init() {
        parent::init();
        add_filter( 'gform_entry_post_save', array( $this, 'process_feed' ), 10, 2 );
    }

    // Define global settings fields for API token.
    public function plugin_settings_fields() {
        return array(
            array(
                'title'  => esc_html__( 'SendFox Settings', 'sendfox-for-gravity-forms' ),
                'fields' => array(
                    array(
                        'label'   => esc_html__( 'Personal Access Token', 'sendfox-for-gravity-forms' ),
                        'type'    => 'text',
                        'name'    => 'sendfox_api_key',
                        'tooltip' => esc_html__( 'Enter your SendFox Personal Access Token. This is required to connect to the SendFox API.', 'sendfox-for-gravity-forms' ),
                        'class'   => 'medium',
                    ),
                ),
            ),
        );
    }

    // Feed settings for each form.
    public function feed_settings_fields() {
        return array(
            array(
                'title'  => esc_html__( 'SendFox Feed Settings', 'sendfox-for-gravity-forms' ),
                'fields' => array(
                    array(
                        'label'   => esc_html__( 'SendFox List ID', 'sendfox-for-gravity-forms' ),
                        'type'    => 'text',
                        'name'    => 'list_id',
                        'tooltip' => esc_html__( 'Enter the SendFox List ID where the contact will be added.', 'sendfox-for-gravity-forms' ),
                        'class'   => 'medium',
                    ),
                    array(
                        'label'   => esc_html__( 'Map Fields', 'sendfox-for-gravity-forms' ),
                        'type'    => 'field_map',
                        'name'    => 'field_map',
                        'field_map' => array(
                            array(
                                'name'     => 'email',
                                'label'    => esc_html__( 'Email', 'sendfox-for-gravity-forms' ),
                                'required' => true,
                            ),
                            array(
                                'name'     => 'first_name',
                                'label'    => esc_html__( 'First Name', 'sendfox-for-gravity-forms' ),
                                'required' => false,
                            ),
                            array(
                                'name'     => 'last_name',
                                'label'    => esc_html__( 'Last Name', 'sendfox-for-gravity-forms' ),
                                'required' => false,
                            ),
                        ),
                    ),
                ),
            ),
        );
    }

    // Process feed and send data to SendFox.
    public function process_feed( $entry, $form ) {
        // Get feed data.
        $feed = $this->get_single_submission_feed( $form );
        if ( ! $feed || ! $this->is_feed_condition_met( $feed, $form, $entry ) ) {
            return;
        }

        // Retrieve API key from global settings.
        $api_key = $this->get_plugin_setting( 'sendfox_api_key' );
        if ( empty( $api_key ) ) {
            GFCommon::log_debug( __METHOD__ . '(): API key is missing.' );
            return;
        }

        $list_id = rgar( $feed['meta'], 'list_id' );
        $email = rgar( $entry, $feed['meta']['field_map']['email'] );
        $first_name = rgar( $entry, $feed['meta']['field_map']['first_name'] );
        $last_name = rgar( $entry, $feed['meta']['field_map']['last_name'] );

        // Send data to SendFox.
        $data = array(
            'email'      => $email,
            'first_name' => $first_name,
            'last_name'  => $last_name,
            'lists'      => array( $list_id ),
        );

        $this->send_to_sendfox( $api_key, $data );
    }

    // Send the data to SendFox via their API.
    private function send_to_sendfox( $api_key, $data ) {
        $response = wp_remote_post( 'https://api.sendfox.com/contacts', array(
            'method'    => 'POST',
            'body'      => json_encode( $data ),
            'headers'   => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type'  => 'application/json',
            ),
        ));

        // Log response or errors for debugging.
        if ( is_wp_error( $response ) ) {
            GFCommon::log_debug( __METHOD__ . '(): Error sending data to SendFox: ' . $response->get_error_message() );
        } else {
            GFCommon::log_debug( __METHOD__ . '(): Successfully sent data to SendFox.' );
        }
    }

    // Feed conditions for running the feed.
    public function is_feed_condition_met( $feed, $form, $entry ) {
        return true; // Modify if you want conditional logic.
    }
}
