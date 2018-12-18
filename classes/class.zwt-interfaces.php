<?php

if ( $_SERVER[ 'SCRIPT_FILENAME' ] == __FILE__ )
	die( 'Access denied.' );

if ( !class_exists( 'ZWT_Interfaces' ) ) {

	/**
	 * Handles plugin pages and interface field dispalys 
	 * @package ZWT_Base
	 * @author Ayebare Mucunguzi
	 */
	class ZWT_Interfaces {
		const REQUIRED_CAPABILITY = 'manage_network_plugins';

		/* Constructor
		 * @mvc Controller
		 * @author Zanto Translate
		 */

		function __construct() {
			$this->registerHookCallbacks();
		}

		/**
		 * Public setter for protected variables
		 * Updates settings outside of the Settings API or other subsystems
		 * @param array $value This will be merged with ZWT_Settings->settings, so it should mimic the structure of the ZWT_Settings::$defaultSettings. It only needs the contain the values that will change, though. See ZWT_Base->upgrade() for an example.
		 */
		public function __set( $variable, $value ) {
			/* Note: ZWT_Module::__set() is automatically called before this */
		}

		/**
		 * Register callbacks for actions and filters
		 * @mvc Controller
		 * @author Zanto Translate
		 */
		public function registerHookCallbacks() {

			add_action( 'admin_menu', array( $this, 'registerSettingsPages' ) );
			add_action( 'init', array( $this, 'init' ), 90 );
			//add_filter('plugin_action_links_' . plugin_basename(dirname(__DIR__)) . '/zanto.php', array($this, 'addPluginActionLinks'));
			add_action( 'zwt_debug_info', array( $this, 'debug_info' ) );
			add_filter( 'plugin_row_meta', array( $this, 'plugin_support_link' ), 10, 2 );
		}

		public function add_tabs() {
			$screen = get_current_screen();
			$zanto_help_tabs = array(
				'tab1' => 'Overview', 'tab2' => 'Translation'
			);


			foreach ( $zanto_help_tabs as $id => $title ) {
				$screen->add_help_tab( array(
					'id' => $id,
					'title' => __( $title, 'Zanto' ),
					'callback' => array( $this, 'display_help_tabs' )
				) );
			}
			$screen->set_help_sidebar(
			'<ul>
                <li><a href="http://zanto.org/documentation">' . __( 'Documentation on Zanto Settings', 'Zanto' ) . '</a></li>
                <li><a href="http://zanto.org/support">Support Forums</a></li>
                </ul>'
			);
		}

		public function display_help_tabs( $screen, $tab ) {
			require( dirname( __DIR__ ) . '/views/zwt-help-tabs.php' );
		}

		/* since 0.3.2 */

		public function debug_info() {
			$zanto_stgs = get_option( ZWT_Base::PREFIX . 'zanto_settings', array( ) );
			require( dirname( __DIR__ ) . '/views/debug-information.php' );
		}

		public function activate() {
			global $wpdb, $EZSQL_ERROR;

			require_once(GTP_PLUGIN_PATH . '/includes/zanto-activation.php');
			zwt_initial_activate();
		}

		/**
		 * Rolls back activation procedures when de-activating the plugin
		 * @mvc Controller
		 * @author Zanto Translate
		 */
		public function deactivate() {
			
		}

		/**
		 * Initializes variables
		 * @mvc Controller
		 * @author Zanto Translate
		 */
		public function init() {
			if ( did_action( 'init' ) !== 1 )
				return;
		}

		/**
		 * Adds links to the plugin's action link section on the Plugins page
		 * @mvc Model
		 * @author Zanto Translate
		 * @param array $links The links currently mapped to the plugin
		 * @return array
		 */
		public static function addPluginActionLinks( $links ) {
			array_unshift( $links, '<a href="http://zanto.org/support/">' . __( 'Support', 'Zanto' ) . '</a>' );
			array_unshift( $links, '<a href="options-general.php?page=' . ZWT_Base::PREFIX . 'settings">' . __( 'Settings', 'Zanto' ) . '</a>' );

			return $links;
		}

		function plugin_support_link( $links, $file ) {
			if ( $file == GTP_PLUGIN_FOLDER . '/zanto.php' ) {
				return array_merge( $links, array( sprintf( '<a href="http://zanto.org/support">%s</a>', __( 'Support', 'Zanto' ) ) ), array( sprintf( '<a href="http://shop.zanto.org">%s</a>', __( 'Addons', 'Zanto' ) ) )
				);
			}
			return $links;
		}

		/**
		 * Adds language switcher to Admin tool bar
		 * @mvc Controller
		 * @author Zanto Translate
		 */
		public function registerSettingsPages() {
			global $wp_version, $zwt_site_obj, $zwt_menu_url;

			if ( did_action( 'admin_menu' ) !== 1 )
				return;

			$zwt_site_obj->modules[ 'settings' ]->init();
			$zwt_settings_page = add_menu_page(
			GTP_NAME . ' Dashboard', 'Zanto', self::REQUIRED_CAPABILITY, ZWT_Base::PREFIX . 'dashboard', array( $this, 'markupDashboardPage' ), $zwt_menu_url
			);

			add_submenu_page(
			ZWT_Base::PREFIX . 'dashboard', GTP_NAME . __( 'Settings', 'Zanto' ), __( 'Blog Settings', 'Zanto' ), self::REQUIRED_CAPABILITY, ZWT_Base::PREFIX . 'settings', array( $this, 'markupSettingsPage' )
			);

			if ( 'complete' == $zwt_site_obj->modules[ 'settings' ]->settings[ 'setup_status' ][ 'setup_wizard' ] ) {
				add_submenu_page(
				ZWT_Base::PREFIX . 'dashboard', GTP_NAME . __( 'Dashboard', 'Zanto' ), __( 'Dashboard', 'Zanto' ), self::REQUIRED_CAPABILITY, ZWT_Base::PREFIX . 'dashboard', array( $this, 'markupDashboardPage' )
				);

				add_submenu_page(
				ZWT_Base::PREFIX . 'dashboard', __( 'Translation Network', 'Zanto' ), __( 'Translation Network', 'Zanto' ), 'manage_options', ZWT_Base::PREFIX . 'trans_network', array( $this, 'user_trans_networks' ) );

				add_submenu_page(
				ZWT_Base::PREFIX . 'dashboard', __( 'Language Manager ', 'Zanto' ), __( 'Language Manager', 'Zanto' ), 'read', ZWT_Base::PREFIX . 'manage_locales', array( $this, 'mo_management' ) );

				add_submenu_page(
				ZWT_Base::PREFIX . 'dashboard', __( 'Advanced Tools ', 'Zanto' ), __( 'Advanced Tools', 'Zanto' ), 'manage_options', ZWT_Base::PREFIX . 'advanced_tools', array( $this, 'advanced_tools' ) );
			}
			add_submenu_page(
			ZWT_Base::PREFIX . 'dashboard', __( 'Debug ', 'Zanto' ), __( 'Debug', 'Zanto' ), 'manage_options', ZWT_Base::PREFIX . 'debug', array( $this, 'debug_page' ) );

			if ( version_compare( $wp_version, '3.3' ) >= 0 ) {
				add_action( 'load-' . $zwt_settings_page, array( $this, 'add_tabs' ) );
			}
		}

		/**
		 * Creates the markup for the Dashboard page
		 * @mvc Controller
		 * @author Zanto Translate
		 */
		public function markupDashboardPage() {
			require_once( dirname( __DIR__ ) . '/views/dashboard.php' );
		}

		/**
		 * Creates the markup for the Settings page
		 * @mvc Controller
		 * @author Zanto Translate
		 */
		public function markupSettingsPage() {
			global $wpdb, $blog_id, $site_id, $zwt_site_obj, $zwt_icon_url;
			$c_settings_obj = $zwt_site_obj->modules[ 'settings' ];
			$user_id = get_current_user_id();
			if ( current_user_can( self::REQUIRED_CAPABILITY ) ) {

				$trans_network_sites = $wpdb->get_results( "SELECT blog_id, trans_id, lang_code FROM {$wpdb->base_prefix}zwt_trans_network", ARRAY_A );


				$trans_blogs = array( );

				foreach ( $trans_network_sites as $tid )
					$trans_blogs[ ] = $tid[ 'blog_id' ];

				/* - Check if this blog has a trans id, if it does no installation should take place */
				$zwt_trans_id_flag = (in_array( $blog_id, $trans_blogs )) ? true : false;
				if ( !$zwt_trans_id_flag ) {

					/* setup data for new site. */
					$blog_current_lang = 'en';
					$langs_array = $wpdb->get_results( "SELECT default_locale, english_name FROM {$wpdb->base_prefix}zwt_languages", ARRAY_A );
					/* - Check for user blogs */

					if ( current_user_can( 'manage_network_plugins' ) ) {
						$user_blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs WHERE deleted='0' AND spam='0'" );
					} else {
						$user_blogs = get_blogs_of_user( $user_id );
						$user_blog_ids = array( );
						foreach ( $user_blogs as $user_site )
							$user_blog_ids[ ] = $user_site->userblog_id;
					}

					/* - Check if any of the blogs has a trans_id */
					$user_trans_blogs = array_intersect( $trans_blogs, $user_blog_ids );

					/* completely new installation of Zanto */
					$zwt_first_install_flag = (empty( $user_trans_blogs )) ? true : false;
					if ( $zwt_first_install_flag ) {
						$c_settings_obj->settings = array( 'setup_status' =>
							array(
								'setup_wizard' => 'incomplete',
								'setup_interface' => 'one'
						) );
					}
					/* remove blogs with a trans_id from userblogs to ensure intallation is only done once on them */ else {
						$user_blog_ids = array_diff( $user_blog_ids, $trans_blogs );
					}
					/* trans_blog is used on interface2 */
					$trans_blog = array( );
					foreach ( $trans_network_sites as $trans_sites ) {
						foreach ( $user_trans_blogs as $index => $trans_blog_id ) {
							if ( $trans_sites[ 'blog_id' ] == $trans_blog_id ) {
								$trans_blog[ $trans_sites[ 'trans_id' ] ][ ] = array(
									'blog_id' => $trans_blog_id,
									'lang_code' => $trans_sites[ 'lang_code' ]
								);
							}
						}
					}
					/* if not super_admin remove translation networks that don't belong to the user from $trans_blog */
					if ( !current_user_can( 'manage_network_plugins' ) ) {
						$user_trans_ids = get_user_meta( $user_id, 'zwt_installed_transnetwork', false );
						/* user belongs to some blogs in a translation network but the networks were not setup by him */
						if ( empty( $user_trans_ids ) ) {
							$c_settings_obj->settings = array( 'setup_status' =>
								array(
									'setup_wizard' => 'incomplete',
									'setup_interface' => 'one'
							) );
							$zwt_first_install_flag = true;
						} else {
							foreach ( $trans_blog as $trans_id => $details ) {
								if ( !in_array( $trans_id, $user_trans_ids ) )
									unset( $trans_blog[ $trans_id ] );
							}
						}
					}
					$c_settings_obj->init();
					$settings = $c_settings_obj->settings;
					$default_lang = $c_settings_obj->settings[ 'translation_settings' ][ 'default_admin_locale' ];
					$script_params = array(
						'current_blog_id' => $blog_id,
						'all_blog_ids' => $user_blog_ids,
						'default_lang' => $default_lang,
						'duplicate_lang' => __( 'You have already chosen this language for another blog', 'Zanto' ),
						'select' => __( '- Select -', 'Zanto' )
					);
					wp_localize_script( ZWT_Base::PREFIX . 'installation', ZWT_Base::PREFIX . 'install_params', $script_params );
				}
				else {
					$c_settings_obj->init();
					if ( 'complete' !== $c_settings_obj->settings[ 'setup_status' ][ 'setup_wizard' ] ) {
						$c_settings_obj->settings = array( 'setup_status' =>
							array(
								'setup_wizard' => 'complete',
								'setup_interface' => 'four'
						) );
					}
					/* set up blog translation settings data	 */
					$c_trans_net = $zwt_site_obj->modules[ 'trans_network' ];
					/* capture translation network changes on reload */
					$c_trans_net->init();
					$blog_trans_network = $c_trans_net->transnet_blogs;
					$c_primary_blog_lang = $c_trans_net->primary_lang_blog;
					$parma_type = get_option( 'permalink_structure' );
					$c_blog_locale = get_option( 'WPLANG' );
					$c_blog_lang_code = $c_trans_net->get_lang_code( $c_blog_locale );
					$rewrite_on = (empty( $parma_type )) ? false : true;
					/* Check if the translation Manager is active */
					$ztm_global_stgs = get_metadata( 'site', $site_id, 'ztm_install', $single = true );
					$tm_active = (isset( $ztm_global_stgs[ 'active' ] ) && in_array( $c_primary_blog_lang, $ztm_global_stgs[ 'active' ] )) ? true : false;
					$add_langs2head = (zwt_network_vars( $c_trans_net->transnet_id, 'get', 'add_seo_headlangs' )) ? 1 : 0;
					$script_params = array(
						__( 'Changing of the primary language will lead to loss of unfinished translations in the Translation Manager!', 'Zanto' )//0
					);
					wp_localize_script( ZWT_Base::PREFIX . 'installation', ZWT_Base::PREFIX . 'settings_params', $script_params );
				}
				$settings = $c_settings_obj->settings;
				require_once( dirname( __DIR__ ) . '/views/page-settings.php' );
			}
			else
				wp_die( 'Access denied.' );
		}

		/**
		 * call back function for translation networks page
		 * @mvc Controller
		 * @author Zanto Translate
		 */
		public function user_trans_networks() {
			global $wpdb, $blog_id;
			$user_id = get_current_user_id();

			if ( current_user_can( self::REQUIRED_CAPABILITY ) ) {
				/* prepare form data */
				$current_user_name = get_userdata( $user_id )->display_name;
				$trans_network_sites = $wpdb->get_results( "SELECT blog_id, trans_id, lang_code FROM {$wpdb->base_prefix}zwt_trans_network", ARRAY_A );
				$langs_array = $wpdb->get_results( "SELECT default_locale, english_name FROM {$wpdb->base_prefix}zwt_languages", ARRAY_A );
				if ( current_user_can( 'manage_network_plugins' ) ) {
					$user_blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs WHERE deleted='0' AND spam='0'" );
				} else {
					$user_blogs = get_blogs_of_user( $user_id );
					$user_blog_ids = array( );
					foreach ( $user_blogs as $user_site )
						$user_blog_ids[ ] = $user_site->userblog_id;
				}

				$flag_found = false;
				$no_trans_blog = array( );
				$trans_blog = array( );
				foreach ( $user_blog_ids as $current_blog_id ) {
					foreach ( $trans_network_sites as $tid ) {
						if ( $current_blog_id == $tid[ 'blog_id' ] ) {
							$trans_blog[ ] = $tid;
							$flag_found = true;
							break;
						}
					}
					if ( !$flag_found ) {
						$no_trans_blog[ ] = $current_blog_id;
					}
					$flag_found = false;
				}
				/* get unique trans_ids for user display all for super admin */

				if ( !empty( $trans_blog ) ) {
					$unique_trans_ids = array( );
					if ( current_user_can( 'manage_network_plugins' ) ) {
						foreach ( $trans_blog as $trans_ids ) {
							$unique_trans_ids[ ] = $trans_ids[ 'trans_id' ];
						}
					} else {
						$user_trans_ids = get_user_meta( $user_id, 'zwt_installed_transnetwork', false );
						foreach ( $trans_blog as $trans_ids ) {
							if ( !in_array( $trans_ids[ 'trans_id' ], $user_trans_ids ) ) {
								continue;
							}
							$unique_trans_ids[ ] = $trans_ids[ 'trans_id' ];
						}
					}
					$unique_trans_ids = array_unique( $unique_trans_ids );
				}

				require_once( dirname( __DIR__ ) . '/menus/translation-network.php' );
			}
			else
				wp_die( 'Access denied.' );
		}

		/**
		 * call back function for .mo managment page
		 * @mvc Controller
		 * @author Zanto Translate
		 */
		public function mo_management() {
			global $zwt_icon_url;
			if ( !isset( $_GET[ 'edit_langs' ] ) ) {
				$WordPress_language = ZWT_MO::getSingleton();
				$script_params = array(
					'admin_url' => admin_url( 'admin.php?page=zwt_manage_locales' ),
					'wp_language_show_switcher' => get_option( 'wp_language_show_switcher', 'on' ),
					'current_scope' => $WordPress_language->current_scope,
					'plugin_url' => GTP_PLUGIN_URL
				);
				wp_localize_script( ZWT_Base::PREFIX . 'mo_management', ZWT_Base::PREFIX . 'mo_params', $script_params );


				require_once( dirname( __DIR__ ) . '/menus/zwt-locale-management.php');
			} else {
				require_once( dirname( __DIR__ ) . '/classes/class.zwt-edit-languages.php' );
			}
			return;
		}

		public function advanced_tools() {
			global $zwt_site_obj, $blog_id;
			$trans_network = $zwt_site_obj->modules[ 'trans_network' ];
			$tax_args = array(
				'show_ui' => true,
				'public' => true
			);
			$taxonomies = get_taxonomies( $tax_args );
			require_once( dirname( __DIR__ ) . '/menus/zwt-advanced-tools.php');
		}

		public function debug_page() {
			do_action( 'zwt_debug_info' );
		}

		/**
		 * Executes the logic of upgrading from specific older versions of the plugin to the current version
		 * @mvc Model
		 * @author Zanto Translate
		 * @param string $dbVersion
		 */
		public function upgrade( $dbVersion = 0 ) {
			/* all general upgrade procedures are implemented in the ZWT_Translation_Network class upgrade function */
		}

		/**
		 * Checks that the object is in a correct state
		 * @mvc Model
		 * @author Zanto Translate
		 * @param string $property An individual property to check, or 'all' to check all of them
		 * @return bool
		 */
		protected function isValid( $property = 'all' ) {
			/* Note: __set() calls validateSettings(), so settings are never invalid */

			return true;
		}

// end  ZWT_Interfaces
	}

}