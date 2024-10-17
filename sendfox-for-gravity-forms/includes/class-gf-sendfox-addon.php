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
        $this->init_ajax();
        add_filter( 'gform_entry_post_save', array( $this, 'process_feed' ), 10, 2 );
    }

    // Handle AJAX for API verification.
    public function init_ajax() {
        parent::init_ajax();
        add_action( 'wp_ajax_gf_sendfox_verify_token', array( $this, 'verify_api_token' ) );
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
                        'tooltip' => esc_html__( 'Enter your SendFox Personal Access Token.', 'sendfox-for-gravity-forms' ),
                        'class'   => 'medium',
                        'after_input' => '<button id="verify-token" class="button">Verify Token</button> <div id="token-verification-result"></div>',
                    ),
                ),
            ),
        );
    }

    // Function to verify the API token.
    public function verify_api_token() {
        // Check nonce for security
        if ( ! isset( $_POST['token'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'gf_sendfox_verify_token' ) ) {
            wp_send_json_error( array( 'message' => 'Invalid request.' ) );
        }

        $api_token = sanitize_text_field( $_POST['token'] );
        $response = wp_remote_get( 'https://api.sendfox.com/me', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_token,
            ),
        ) );

        if ( is_wp_error( $response ) ) {
            wp_send_json_error( array( 'message' => 'Unable to connect to SendFox.' ) );
        }

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body );

        if ( isset( $data->email ) ) {
            wp_send_json_success( array( 'message' => 'Valid API token. Connected as ' . $data->email ) );
        } else {
            wp_send_json_error( array( 'message' => 'Invalid API token.' ) );
        }
    }

    // Enqueue the script for token verification.
    public function scripts() {
        $scripts = array(
            array(
                'handle'  => 'sendfox-verification',
                'src'     => $this->get_base_url() . '/js/sendfox-verification.js',
                'deps'    => array( 'jquery' ),
                'version' => $this->_version,
                'enqueue' => array( array( 'admin_page' => array( 'plugin_settings' ) ) ),
                'strings' => array(
                    'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
                    'nonce'    => wp_create_nonce( 'gf_sendfox_verify_token' ),
                ),
            ),
        );
        return array_merge( parent::scripts(), $scripts );
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
                        'tooltip' => esc_html__( 'Enter the SendFox List ID.', 'sendfox-for-gravity-forms' ),
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
        // Log the start of feed processing.
        GFCommon::log_debug( __METHOD__ . '(): Starting feed processing for form ID ' . $form['id'] );

        // Get feed data.
        $feed = $this->get_single_submission_feed( $form );
        if ( ! $feed || ! $this->is_feed_condition_met( $feed, $form, $entry ) ) {
            GFCommon::log_debug( __METHOD__ . '(): No valid feed found or feed condition not met.' );
            return;
        }

        // Retrieve API key from global settings.
        $api_key = $this->get_plugin_setting( 'sendfox_api_key' );
        if ( empty( $api_key ) ) {
            GFCommon::log_error( __METHOD__ . '(): API key is missing in the global settings.' );
            return;
        }

        GFCommon::log_debug( __METHOD__ . '(): API key found. Continuing with feed processing.' );

        $list_id = rgar( $feed['meta'], 'list_id' );
        $email = rgar( $entry, $feed['meta']['field_map']['email'] );
        $first_name = rgar( $entry, $feed['meta']['field_map']['first_name'] );
        $last_name = rgar( $entry, $feed['meta']['field_map']['last_name'] );

        GFCommon::log_debug( __METHOD__ . "(): Mapped values - Email: {$email}, First Name: {$first_name}, Last Name: {$last_name}" );

        // Send data to SendFox.
        $data = array(
            'email'      => $email,
            'first_name' => $first_name,
            'last_name'  => $last_name,
            'lists'      => array( $list_id ),
        );

        $this->send_to_sendfox( $api_key, $data );
    }

    // Send data to SendFox API.
    private function send_to_sendfox( $api_key, $data ) {
        GFCommon::log_debug( __METHOD__ . '(): Sending data to SendFox: ' . json_encode( $data ) );

        $response = wp_remote_post( 'https://api.sendfox.com/contacts', array(
            'method'    => 'POST',
            'body'      => json_encode( $data ),
            'headers'   => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type'  => 'application/json',
            ),
        ));

        if ( is_wp_error( $response ) ) {
            GFCommon::log_error( __METHOD__ . '(): Error sending data to SendFox: ' . $response->get_error_message() );
        } else {
            GFCommon::log_debug( __METHOD__ . '(): Successfully sent data to SendFox. Response: ' . wp_remote_retrieve_body( $response ) );
        }
    }
}
