<?php
/**
 * Plugin Name:     Awesome Support - Messages
 * Plugin URI:      https://wordpress.org/plugins/awesome-support-messages
 * Description:     Customize frontend messages returned by Awesome Support.
 * Version:         1.0.0
 * Author:          Tsunoa
 * Author URI:      https://tsunoa.com
 * Text Domain:     awesome-support-messages
 *
 * @package         Awesome_Support\Messages
 * @author          Tsunoa
 * @copyright       Copyright (c) Tsunoa
 */


// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) exit;

if( !class_exists( 'Awesome_Support_Messages' ) ) {

    /**
     * Main Awesome_Support_Messages class
     *
     * @since       1.0.0
     */
    class Awesome_Support_Messages {

        /**
         * @var         Awesome_Support_Messages $instance The one true Awesome_Support_Messages
         * @since       1.0.0
         */
        private static $instance;


        /**
         * Get active instance
         *
         * @access      public
         * @since       1.0.0
         * @return      object self::$instance The one true Awesome_Support_Messages
         */
        public static function instance() {
            if( ! self::$instance ) {
                self::$instance = new Awesome_Support_Messages();
                self::$instance->setup_constants();
                self::$instance->load_textdomain();
                self::$instance->hooks();
            }

            return self::$instance;
        }

        /**
         * Setup plugin constants
         *
         * @access      private
         * @since       1.0.0
         * @return      void
         */
        private function setup_constants() {
            // Plugin version
            define( 'AWESOME_SUPPORT_MESSAGES_VER', '1.0.0' );

            // Plugin path
            define( 'AWESOME_SUPPORT_MESSAGES_DIR', plugin_dir_path( __FILE__ ) );

            // Plugin URL
            define( 'AWESOME_SUPPORT_MESSAGES_URL', plugin_dir_url( __FILE__ ) );
        }

        /**
         * Internationalization
         *
         * @access      public
         * @since       1.0.0
         * @return      void
         */
        public function load_textdomain() {
            // Set filter for language directory
            $lang_dir = AWESOME_SUPPORT_MESSAGES_DIR . '/languages/';
            $lang_dir = apply_filters( 'awesome_support_messages_languages_directory', $lang_dir );

            // Traditional WordPress plugin locale filter
            $locale = apply_filters( 'plugin_locale', get_locale(), 'awesome-support-messages' );
            $mofile = sprintf( '%1$s-%2$s.mo', 'awesome-support-messages', $locale );

            // Setup paths to current locale file
            $mofile_local   = $lang_dir . $mofile;
            $mofile_global  = WP_LANG_DIR . '/awesome-support-messages/' . $mofile;

            if( file_exists( $mofile_global ) ) {
                // Look in global /wp-content/languages/awesome-support-messages/ folder
                load_textdomain( 'awesome-support-messages', $mofile_global );
            } elseif( file_exists( $mofile_local ) ) {
                // Look in local /wp-content/plugins/awesome-support-messages/languages/ folder
                load_textdomain( 'awesome-support-messages', $mofile_local );
            } else {
                // Load the default language files
                load_plugin_textdomain( 'awesome-support-messages', false, $lang_dir );
            }
        }

        /**
         * Run action and filter hooks
         *
         * @access      private
         * @since       1.0.0
         * @return      void
         */
        private function hooks() {
            // Awesome support admin settings
            add_filter( 'wpas_plugin_settings', array( $this, 'settings' ) );

            // Override awesome support frontend messages
            add_filter( 'wpas_notification_markup', array( $this, 'override_notification_markup' ), 10, 2 );
        }

        private function get_messages() {
            return array(
                array(
                    'id' => 'messages_no_tickets',
                    'label' => __( 'Empty tickets list', 'awesome-support' ),
                    'message' => 'You haven\'t submitted a ticket yet. <a href="%s">Click here to submit your first ticket</a>.',
                    'message_args' => array( wpas_get_submission_page_url() ),
                ),
                array(
                    'id' => 'messages_redirecting',
                    'label' => __( 'Redirecting', 'awesome-support' ),
                    'message' => 'You are being redirected...',
                ),
                array(
                    'id' => 'messages_need_login',
                    'label' => __( 'User is not logged in', 'awesome-support' ),
                    'message' => 'You need to <a href="%s">log-in</a> to submit a ticket.',
                    'message_args' => array( esc_url( '' ) ),
                ),
                array(
                    'id' => 'messages_access_denied',
                    'label' => __( 'User can not submit tickets', 'awesome-support' ),
                    'message' => 'You are not allowed to submit a ticket.',
                ),
                array(
                    'id' => 'messages_support_team_member',
                    'label' => __( 'Team member tries to submit a ticket from frontend', 'awesome-support' ),
                    'message' => 'Sorry, support team members cannot submit tickets from here. If you need to open a ticket, please go to your admin panel or <a href="%s">click here to open a new ticket</a>.',
                    'message_args' => array( add_query_arg( array( 'post_type' => 'ticket' ), admin_url( 'post-new.php' ) ) )
                ),
            );
        }

        /**
         * Get message args based on localized message (compare localized original message with received one)
         *
         * @param $located_message
         * @return bool
         */
        private function get_original_message( $located_message ) {
            foreach( $this->get_messages() as $message ) {
                if( ! isset( $message['message_args'] ) ) {
                    $message['message_args'] = array();
                }

                if( vsprintf( __( $message['message'], 'awesome-support' ), $message['message_args'] ) == $located_message ) {
                    return $message;
                }
            }

            return false;
        }

        /**
         * Awesome support admin settings
         *
         * @param array $def
         * @return array
         */
        public function settings( $def ) {
            $options = array();

            foreach( $this->get_messages() as $message ) {
                $options[] = array(
                    'id'      => $message['id'],
                    'name'    => $message['label'],
                    'type'    => 'textarea',
                    'desc'    => esc_html( $message['message'] ),
                );
            }

            $settings = array(
                'messages' => array(
                    'name'    => __( 'Messages', 'awesome-support' ),
                    'options' => $options
                ),
            );

            return array_merge( $def, $settings );
        }

        /**
         * Override awesome support frontend messages
         *
         * @param string $markup
         * @param string $type
         * @return string
         */
        public function override_notification_markup( $markup, $type ) {
            $classes = apply_filters( 'wpas_notification_classes', array(
                'success' => 'wpas-alert wpas-alert-success',
                'failure' => 'wpas-alert wpas-alert-danger',
                'info'    => 'wpas-alert wpas-alert-info',
            ) );

            $markup_wrapper = apply_filters( 'wpas_notification_wrapper', '<div class="%s">%s</div>' ); // Keep this filter for backwards compatibility

            // Pattern: '<div class="wpas-alert wpas-alert-success">(.*?)</div>'si
            $pattern = "'" . sprintf( $markup_wrapper, $classes[ $type ], '(.*?)' ) . "'si";

            preg_match( $pattern, $markup, $match );

            if( is_array( $match ) && isset( $match[1] ) ) { // Pattern matches notification wrapper content (basically the notification localized content)
                $located_message = $match[1];
                $message = $this->get_original_message( $located_message );

                if( $message !== false ) { // Original message args found
                    $message_option = wpas_get_option( $message['id'] );

                    if( ! empty( $message_option ) ) { // User wants to override this message
                        $message_option = str_replace( '\"', '"', $message_option ); // Revert back esc_html()

                        if( ! isset( $message['message_args'] ) ) {
                            $message['message_args'] = array();
                        }

                        $markup = sprintf( $markup_wrapper, $classes[ $type ], vsprintf( __( $message_option, 'awesome-support' ), $message['message_args'] ) );
                    }
                }
            }

            return $markup;
        }
    }
}


/**
 * The main function responsible for returning the one true Awesome_Support_Messages instance
 *
 * @since       1.0.0
 * @return      \Awesome_Support_Messages The one true Awesome_Support_Messages
 */
function awesome_support_messages() {
    return Awesome_Support_Messages::instance();
}
add_action( 'plugins_loaded', 'awesome_support_messages' );