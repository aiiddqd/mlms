<?php
/*
Name:        Email Notifications Class
Author:      Mucunguzi Ayebare Brooks
License:     GPLv2
*/


if ( ! defined( 'ABSPATH' ) ) {
	die( 'Access denied.' );
}

if ( ! class_exists( 'Mail_Notifications' ) ) {

	class Mail_Notifications {
		// Declare variables and constants
		protected static $instance;
		protected $mails, $mails_were_updated;

		/**
		 * Constructor
		 */
		protected function __construct() {
			$this->init();         // needs to run as early as possible
			add_action( 'shutdown',  array( $this, 'send_mails' ) ); //send mails at the last hook to run
		}

		/**
		 * Provides access to a single instances of the class using the singleton pattern
		 * @mvc    Controller
		 * @return object
		 */
		public static function get_singleton() {
			if ( ! isset( self::$instance ) ) {
				self::$instance = new Mail_Notifications();
			}

			return self::$instance;
		}

		/**
		 * Initializes variables
		 */
		public function init() {
			$this->mails               = array();
			$this->mails_were_updated  = false;
		}

		/**
		 * Queues up a mail to be displayed to the user
		 *
		 * @param string $mail The text to show the user
		 * @param string $type    'update' for a success or notification mail, or 'error' for an error mail
		 */
		public function enqueue($mail) {
			
			if(!is_email($mail['to'])){
			    return false;
			}

			$this->mails[$mail['to']][$mail['subject']]['body'][] = $mail['body'];
			$this->mails_were_updated = true;
		}

		/**
		 * Send emails 
		 */
		public function send_mails() {
		
		if ( $this->mails_were_updated && count( $this->mails)) {
			
                foreach ($this->mails as $to => $subjects) {
                    $body_to_send = '';
                    foreach ($subjects as $subject => $content) {
                        $body = $content['body'];
                        $body_to_send .= $body_to_send . "\n\n" . implode("\n\n\n\n", $body) . "\n\n\n\n";
						
                        $footer = "\n--\n" . sprintf(__("This message was automatically sent by Zanto Translation Management running on %s. To stop receiving these notifications, go to Your Settings, or contact the system administrator at %s.", 'Zanto'), get_bloginfo('name'), get_option('home'));
                        
                        $body_to_send .= $footer;
                        
                        if (isset($content['attachment'])) {
                            $attachments = $content['attachment'];
                        } else {
                            $attachments = array();
                        }
                        
                        $body_to_send = apply_filters('ZANTO_new_mail_body', $body_to_send);
                        $attachments = apply_filters('ZANTO_new_mail_attachments', $attachments);
                        wp_mail($to, $subject, $body_to_send, '', $attachments);                        
                    }
                }
            
		}
		}

		
	} // end Mail_Notifications

	Mail_Notifications::get_singleton(); // Create the instance immediately to make sure hook callbacks are registered in time

	if ( ! function_exists( 'add_mail' ) ) {
		function add_mail( $mail, $type = 'update' ) {
			Mail_Notifications::get_singleton()->enqueue($mail);
		}
	}
}
