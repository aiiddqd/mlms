<?php

if ( $_SERVER[ 'SCRIPT_FILENAME' ] == __FILE__ )
	die( 'Access denied.' );

if ( !class_exists( 'ZWT_Cron' ) ) {
	/*	 * FOR FUTURE USE
	 * Handles cron jobs and intervals
	 * Note: Because WP-Cron only fires hooks when HTTP requests are made
	 * 
	 * @package ZWT_Base
	 * @author Ayebare Mucunguzi
	 */

	class ZWT_Cron extends ZWT_Module {

		protected static $readableProperties = array( );
		protected static $writeableProperties = array( );

		/*
		 * Magic methods
		 */

		protected function __construct() {
			$this->registerHookCallbacks();
		}

		/**
		 * Adds custom intervals to the cron schedule.
		 * @param array $schedules
		 * @return array
		 */
		public static function addCustomCronIntervals( $schedules ) {
			$schedules[ ZWT_Base::PREFIX . 'debug' ] = array(
				'interval' => 5,
				'display' => 'Every 5 seconds'
			);

			$schedules[ ZWT_Base::PREFIX . 'ten_minutes' ] = array(
				'interval' => 60 * 10,
				'display' => 'Every 10 minutes'
			);

			$schedules[ ZWT_Base::PREFIX . 'one_day' ] = array(
				'interval' => 60 * 60 * 12,
				'display' => 'Every 1 day'
			);

			return $schedules;
		}

		/**
		 * Fires a cron job at a specific time of day, rather than on an interval
		 */
		public static function fireJobAtTime() {
			if ( did_action( ZWT_Base::PREFIX . 'cron_timed_jobs' ) !== 1 )
				return;

			$now = current_time( 'timestamp' );

			// Example job to fire between 1am and 3am
			if ( (int) date( 'G', $now ) >= 1 && (int) date( 'G', $now ) <= 3 ) {
				if ( !get_transient( ZWT_Base::PREFIX . 'cron_example_timed_job' ) ) {
					//do stuff
					set_transient( ZWT_Base::PREFIX . 'cron_example_timed_job', true, 60 * 60 * 6 );
				}
			}
		}

		/**
		 * Check for self hosted plugins updates
		 * @param array $schedules
		 * @return array
		 */
		public static function check4Updates() {
			if ( did_action( ZWT_Base::PREFIX . 'plugins_updater_cron' ) !== 1 )
				return;

			// Do stuff

			add_notice( __METHOD__ . ' cron job fired.' );
		}

		/**
		 * Register callbacks for actions and filters
		 */
		public function registerHookCallbacks() {
			// NOTE: Make sure you update the did_action() parameter in the corresponding callback method when changing the hooks here
			add_action( ZWT_Base::PREFIX . 'cron_timed_jobs', __CLASS__ . '::fireJobAtTime' );
			add_action( ZWT_Base::PREFIX . 'plugins_updater_cron', __CLASS__ . '::check4Updates' );
			add_action( 'init', array( $this, 'init' ) );

			add_filter( 'cron_schedules', __CLASS__ . '::addCustomCronIntervals' );
		}

		/**
		 * Prepares site to use the plugin during activation
		 */
		public function activate() {
			if ( wp_next_scheduled( ZWT_Base::PREFIX . 'cron_timed_jobs' ) === false ) {
				wp_schedule_event(
				current_time( 'timestamp' ), ZWT_Base::PREFIX . 'ten_minutes', ZWT_Base::PREFIX . 'cron_timed_jobs'
				);
			}

			if ( wp_next_scheduled( ZWT_Base::PREFIX . 'plugins_updater_cron' ) === false ) {
				wp_schedule_event(
				current_time( 'timestamp' ), ZWT_Base::PREFIX . 'one_day', ZWT_Base::PREFIX . 'plugins_updater_cron'
				);
			}
		}

		/**
		 * Rolls back activation procedures when de-activating the plugin
		 */
		public function deactivate() {
			wp_clear_scheduled_hook( ZWT_Base::PREFIX . 'timed_jobs' );
			wp_clear_scheduled_hook( ZWT_Base::PREFIX . 'example_job' );
		}

		/**
		 * Initializes variables
		 */
		public function init() {
			if ( did_action( 'init' ) !== 1 )
				return;
		}

		/**
		 * Executes the logic of upgrading from specific older versions of the plugin to the current version
		 * @param string $dbVersion
		 */
		public function upgrade( $dbVersion = 0 ) {
			/*
			  if( version_compare( $dbVersion, 'x.y.z', '<' ) )
			  {
			  // Do stuff
			  }
			 */
		}

		/**
		 * Checks that the object is in a correct state
		 * @mvc Model
		 * @author Zanto Translate
		 * @param string $property An individual property to check, or 'all' to check all of them
		 * @return bool
		 */
		protected function isValid( $property = 'all' ) {
			return true;
		}

	}

	// end ZWT_Cron
}
?>