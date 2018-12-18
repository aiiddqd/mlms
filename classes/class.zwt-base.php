<?php

if ( $_SERVER[ 'SCRIPT_FILENAME' ] == __FILE__ )
	die( 'Access denied.' );

if ( !class_exists( 'ZWT_Base' ) ) {

	/**
	 * Main / front controller class
	 * ZWT_Base is an object-oriented/MVC base for building WordPress plugins
	 * 
	 * @package ZWT_Base
	 * @author Zanto Translate
	 */
	class ZWT_Base extends ZWT_Module {

		public static $notices;		 // Needs to be static so static methods can call enqueue notices. Needs to be public so other modules can enqueue notices.
		protected static $readableProperties = array( 'modules' );  // These should really be constants, but PHP doesn't allow class constants to be arrays
		protected static $writeableProperties = array( );
		protected $modules;

		const PREFIX = 'zwt_';
		const DEBUG_MODE = false;


		/*
		 * Magic methods
		 */

		/**
		 * Constructor
		 * @mvc Controller
		 * @author Zanto Translate
		 */
		protected function __construct() {
			$this->registerHookCallbacks();

			$this->modules = array(
				'settings' => ZWT_Settings::getInstance(),
				'trans_network' => ZWT_Translation_Network::getInstance(),
			//'ZWT_Cron'			=> ZWT_Cron::getInstance()
			);

			if ( !defined( 'GTP_SETUP_COMPLETE' ) ) {
				if ( $this->modules[ 'settings' ]->settings[ 'setup_status' ][ 'setup_wizard' ] == 'complete' ) {
					define( 'GTP_SETUP_COMPLETE', true );
				} else {
					define( 'GTP_SETUP_COMPLETE', false );
				}
			}
			add_action( 'wp_ajax_zwt_all_ajax', array( $this, 'zwt_all_ajax' ) );
			add_action( 'wp_ajax_nopriv_zwt_all_ajax', array( $this, 'zwt_all_ajax' ) );
			if ( isset( $_GET[ 'switch_to' ] ) )
				ZWT_MO::getSingleton();
			if ( $this->modules[ 'settings' ]->settings[ 'blog_setup' ][ 'browser_lang_redirect' ] ) {
				require_once( dirname( __FILE__ ) . '/class.zwt-browser-lang-redirect.php' );
			}
		}

		/*
		 * Static methods
		 */

		/**
		 * Enqueues CSS, JavaScript, etc
		 * @mvc Controller
		 * @author Zanto Translate
		 */
		//@todo load script only where they are needed
		public static function loadResources( $hook_suffix ) {
			global $wp_version;
			if ( did_action( 'wp_enqueue_scripts' ) !== 1 && did_action( 'admin_enqueue_scripts' ) !== 1 )
				return;

			wp_register_script(
			self::PREFIX . 'zanto-translation-main', plugins_url( 'javascript/zanto-main.js', dirname( __FILE__ ) ), array( 'jquery' ), GTP_ZANTO_VERSION, true
			);

			wp_register_script(
			self::PREFIX . 'installation', plugins_url( 'javascript/zanto-installation.js', dirname( __FILE__ ) ), array( 'jquery-ui-sortable' ), GTP_ZANTO_VERSION, true
			);

			wp_register_script(
			self::PREFIX . 'mo_management', plugins_url( 'javascript/mo-management.js', dirname( __FILE__ ) ), array( 'jquery' ), GTP_ZANTO_VERSION, true
			);

			wp_register_script(
			self::PREFIX . 'jquery_cookie', plugins_url( 'javascript/jquery.cookie.js', dirname( __FILE__ ) ), array( 'jquery' ), GTP_ZANTO_VERSION, true
			);

			wp_register_script(
			self::PREFIX . 'browser_lang_redirect', plugins_url( 'javascript/browser-lang-redirect.js', dirname( __FILE__ ) ), array( 'jquery', self::PREFIX . 'jquery_cookie' ), GTP_ZANTO_VERSION, true
			);

			wp_register_style(
			self::PREFIX . 'admin', plugins_url( 'css/admin.css', dirname( __FILE__ ) ), array( ), GTP_ZANTO_VERSION, 'all'
			);
			wp_register_style(
			self::PREFIX . 'icon_font', plugins_url( 'css/icon-font/css/font-awesome.min.css', dirname( __FILE__ ) ), array( ), GTP_ZANTO_VERSION, 'all'
			);


			if ( is_admin() ) {
				wp_enqueue_style( self::PREFIX . 'admin' );
				wp_enqueue_style( self::PREFIX . 'icon_font' );
				wp_enqueue_script( self::PREFIX . 'zanto-translation-main' );

				$script_params = array( get_userdata( get_current_user_id() )->display_name, //0
					__( 'Translations for this blog will be overwritten!', 'Zanto' ), //1
					__( 'Add to Translation Network', 'Zanto' ), //2
					__( 'Cancel', 'Zanto' ), //3
					__( 'Nothing Selected', 'Zanto' ), //4
					__( 'Your current blog settings will be lost. The default settings will be applied', 'Zanto' ), //5
					__( 'The post you have selected already has a translation in this language! Do you want to continue?', 'Zanto' ) //6
				);

				wp_localize_script( ZWT_Base::PREFIX . 'zanto-translation-main', ZWT_Base::PREFIX . 'main_i8n', $script_params );


				global $post;

				if(!empty($post) && $langs = zwt_get_languages( 'skip_missing=0' )) {
					wp_enqueue_style(
						'select2',
						BASE_THEME_COMPONENTS_URI . 'select2/dist/css/select2.min.css',
						[],
						BASE_THEME_VERSION
					);

					wp_enqueue_script(
						'select2',
						BASE_THEME_COMPONENTS_URI . 'select2/dist/js/select2.full.js',
						[],
						BASE_THEME_VERSION, true
					);

					wp_enqueue_script(
						'select2-extended-ajax',
						BASE_THEME_COMPONENTS_URI . 'select2/src/js/select2/data/select2_extended_ajax_adapter.js',
						[],
						BASE_THEME_VERSION, true
					);

					wp_enqueue_script(
						'select2-ru',
						BASE_THEME_COMPONENTS_URI . 'select2/dist/js/i18n/ru.js',
						[],
						BASE_THEME_VERSION, true
					);

					$preload = [];

					foreach ([BLOG_ID_BMR, BLOG_ID_BMR_EN, BLOG_ID_BMR_UA, BLOG_ID_BMR_AM] as $blog) {
						$preload[$blog] = zwt_select2_get_posts([
							'postType' => $post->post_type,
							'blog'     => $blog,
							'order'    => ' p.post_date DESC '
						]);
						$preload[$blog]['total_count'] = count($preload[$blog]['items']);
					}

					$zwt_preload = [
						'current' => get_current_blog_id(),
						'preload' => $preload
					];

					wp_localize_script(self::PREFIX . 'zanto-translation-main', 'zwt_preload', $zwt_preload);
				}
			}

			if ( 'zanto_page_zwt_settings' == $hook_suffix ) {
				wp_enqueue_script( self::PREFIX . 'installation' );
			}

			if ( 'zanto_page_zwt_manage_locales' == $hook_suffix ) {
				wp_enqueue_script( self::PREFIX . 'mo_management' );
			}
		}

		/**
		 * Clears caches of content generated by caching plugins like WP Super Cache
		 * @mvc Model
		 * @author Zanto Translate
		 */
		protected static function clearCachingPlugins() {
			// WP Super Cache
			if ( function_exists( 'wp_cache_clear_cache' ) )
				wp_cache_clear_cache();

			// W3 Total Cache
			if ( class_exists( 'W3_Plugin_TotalCacheAdmin' ) ) {
				$w3TotalCache = & w3_instance( 'W3_Plugin_TotalCacheAdmin' );

				if ( method_exists( $w3TotalCache, 'flush_all' ) )
					$w3TotalCache->flush_all();
			}
		}

		function zwt_all_ajax() {
			require( dirname( __DIR__ ) . '/includes/ajax.php' );
		}

		/*
		 * Instance methods
		 */

		/**
		 * Prepares sites to use the plugin during single or network-wide activation
		 * @mvc Controller
		 * @author Zanto Translate
		 * @param bool $networkWide
		 */
		public function activate() {
			global $wpdb;

			if ( did_action( 'activate_' . plugin_basename( dirname( __DIR__ ) . '/zanto.php' ) ) !== 1 )
				return;


			/* 	if( $networkWide )
			  {
			  $blogs = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );

			  foreach( $blogs as $b )
			  {
			  switch_to_blog( $b );
			  $this->singleActivate( $networkWide );
			  }

			  restore_current_blog();
			  }

			 */
			$this->singleActivate();
		}

		/**
		 * Runs activation code on a new WPMS site when it's created
		 * @mvc Controller
		 * @author Zanto Translate
		 * @param int $blogID
		 */
		public function activateNewSite( $blogID ) {
			if ( did_action( 'wpmu_new_blog' ) !== 1 )
				return;

			switch_to_blog( $blogID );
			$this->singleActivate();
			restore_current_blog();
		}

		/**
		 * Prepares a single blog to use the plugin
		 * @mvc Controller
		 * @author Zanto Translate
		 * @param bool $networkWide
		 */
		protected function singleActivate() {
			foreach ( $this->modules as $module )
				$module->activate();

			flush_rewrite_rules();
		}

		/**
		 * Rolls back activation procedures when de-activating the plugin
		 * @mvc Controller
		 * @author Zanto Translate
		 */
		public function deactivate() {
			foreach ( $this->modules as $module )
				$module->deactivate();

			flush_rewrite_rules();
		}

		/**
		 * Register callbacks for actions and filters
		 * @mvc Controller
		 * @author Zanto Translate
		 */
		public function registerHookCallbacks() {
			// NOTE: Make sure you update the did_action() parameter in the corresponding callback method when changing the hooks here
			//add_action( 'wpmu_new_blog', 	        array( $this, 'activateNewSite') );
			add_action( 'wp_enqueue_scripts', __CLASS__ . '::loadResources' );
			add_action( 'admin_enqueue_scripts', __CLASS__ . '::loadResources' );

			add_action( 'init', array( $this, 'init' ) );
			add_action( 'init', array( $this, 'upgrade' ), 11 );
			if ( !is_admin() ) {
				add_action( 'wp_head', array( $this, 'meta_generator_tag' ) );
			}
		}

		/**
		 * Initializes variables
		 * @mvc Controller
		 * @author Zanto Translate
		 */
		public function init() {
			if ( did_action( 'init' ) !== 1 )
				return;

			if ( !defined( 'GTP_LANGUAGE_CODE' ) ) {
				define( 'GTP_LANGUAGE_CODE', get_option( 'WPLANG' ) );
			}

			if ( self::DEBUG_MODE )
				self::$notices->debugMode = true;

			if ( is_admin() ) {
				$zwt_interfaces = new ZWT_Interfaces();
				if ( GTP_SETUP_COMPLETE ) {
					new ZWT_Tax();
					new ZWT_WP_POST();
				} else {
					if ( !isset( $_REQUEST[ 'page' ] ) || $_REQUEST[ 'page' ] != 'zwt_settings' )
						add_notice( __( 'Zanto Installation is not complete, please click the button to finish installation procedure', 'Zanto' ) . '&nbsp;<a class="button-primary" href="' . admin_url( 'admin.php?page=zwt_settings' ) . '">' . __( 'Configure Zanto', 'Zanto' ) . '</a>' );
				}
			}
		}

		/**
		 * Adds Zanto Version to the <head> tag
		 * @since 0.3.0
		 * @return void
		 */
		function meta_generator_tag() {
			$tm = (defined( 'ZTM_VERSION' )) ? ZTM_VERSION : '0';
			printf( '<meta name="generator" content="Zanto ver:%s tm:%s" />' . PHP_EOL, GTP_ZANTO_VERSION, $tm );
		}

		/**
		 * Checks if the plugin was recently updated and upgrades if necessary
		 * @mvc Controller
		 * @author Zanto Translate
		 * @param string $dbVersion
		 */
		public function upgrade( $dbVersion = 0 ) {
			global $site_id;
			/* all general upgrade procedures are implemented in the ZWT_Translation_Network class upgrade function */
			if ( did_action( 'init' ) !== 1 )
				return;

			if ( isset( $this->modules[ 'settings' ]->settings[ 'zanto_settings' ][ 'db-version' ] ) ) {
				if ( version_compare( $this->modules[ 'settings' ]->settings[ 'zanto_settings' ][ 'db-version' ], GTP_ZANTO_VERSION, '==' ) )
					return;
			}

			$zwt_old_settings = get_metadata( 'site', $site_id, 'zwt_zanto_settings', $single = true );

			if ( isset( $zwt_old_settings[ 'zwt_installed_version' ] ) ) {// this upgrade procedure is done only once on the first site upgrade is carried out
				if ( version_compare( $zwt_old_settings[ 'zwt_installed_version' ], GTP_ZANTO_VERSION, '!=' ) ) {
					$this->modules[ 'trans_network' ]->upgrade( $this->modules[ 'settings' ]->settings[ 'zanto_settings' ][ 'db-version' ], true );
					update_metadata( 'site', $site_id, 'zwt_zanto_settings', array( 'zwt_installed_version' => GTP_ZANTO_VERSION ) );
					add_notice( sprintf( __( 'Zanto has been updated on this Network to version %s', 'Zanto' ), GTP_ZANTO_VERSION ) );
				}
			}

			foreach ( $this->modules as $module ) {// this upgrade procedure is caried out whenever a site in the tanslation network is visited.
				$module->upgrade( $this->modules[ 'settings' ]->settings[ 'zanto_settings' ][ 'db-version' ] );
			}

			ZWT_Settings::save_setting( 'settings', array( 'zanto_settings' => array( 'db-version' => GTP_ZANTO_VERSION ) ) );
			self::clearCachingPlugins();
			if ( is_admin() )
				add_notice( __( 'Zanto has been updated on this blog', 'Zanto' ) );
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

	// end ZWT_Base
	require_once( dirname( __FILE__ ) . '/class.zwt-settings.php' );
	//require_once( dirname(__FILE__) . '/class.zwt-cron.php' );
	require_once( dirname( __FILE__ ) . '/class.zwt-translation-network.php' );
	require_once( dirname( __FILE__ ) . '/class.zwt-interfaces.php' );
	require_once( dirname( __FILE__ ) . '/class.zwt-download-mo.php' );
	require_once( dirname( __FILE__ ) . '/class.zwt-wp-post.php' );
	require_once( dirname( __FILE__ ) . '/class.zwt-wp-tax.php' );
}
?>