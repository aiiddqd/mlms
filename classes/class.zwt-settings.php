<?php

if ( $_SERVER[ 'SCRIPT_FILENAME' ] == __FILE__ )
	die( 'Access denied.' );

if ( !class_exists( 'ZWT_Settings' ) ) {

	/**
	 * Handles plugin settings form processing  and user profile meta fields
	 * @package ZWT_Base
	 * @author Zanto Translate
	 */
	class ZWT_Settings extends ZWT_Module {

		protected $settings;
		protected static $defaultSettings;
		protected static $readableProperties = array( 'settings' );
		protected static $writeableProperties = array( 'settings' );
		const REQUIRED_CAPABILITY = 'manage_network_plugins';

		protected function __construct() {
			$this->registerHookCallbacks();
		}

		/**
		 * Public setter for protected variables
		 * Updates settings outside of the Settings API or other subsystems
		 * @param string $variable
		 * @param array $value This will be merged with ZWT_Settings->settings, so it should mimic the structure of the ZWT_Settings::$defaultSettings. It only needs the contain the values that will change, though. See ZWT_Base->upgrade() for an example.
		 */
		public function __set( $variable, $value ) {
// Note: ZWT_Module::__set() is automatically called before this

			if ( $variable != 'settings' || empty( $value ) )
				return;

			$this->settings = self::validateSettings( $value );
			update_option( ZWT_Base::PREFIX . 'zanto_settings', $this->settings );
			do_action( 'zwt_save_settings_setter', $value );
		}

		public static function save_setting( $variable, $value ) {

			if ( $variable != 'settings' || empty( $value ) )
				return;

// $newSettings = shortcode_atts(self::getSettings(), $value);
			$newSettings = zwt_merge_atts( self::getSettings(), $value );
//validation				

			update_option( ZWT_Base::PREFIX . 'zanto_settings', $newSettings );
			do_action( 'zwt_save_settings', $value );
		}

		/**
		 * Register callbacks for actions and filters
		 */
		public function registerHookCallbacks() {
// NOTE: Make sure you update the did_action() parameter in the corresponding callback method when changing the hooks here
			add_filter( 'upload_mimes', array( $this, 'add_custom_mimes' ) );
			add_action( 'init', array( $this, 'init' ) );
			if ( !empty( $_POST ) ) {
				add_action( 'init', array( $this, 'form_action' ), 99 ); // needs to run after ZWT_Translation_Network init
			}
		}

		/**
		 * Prepares site to use the plugin during activation
		 * @param bool $networkWide
		 */
		public function activate() {
			global $wpdb, $EZSQL_ERROR;

			require_once(GTP_PLUGIN_PATH . '/includes/zanto-activation.php');
			zwt_initial_activate();
		}

		/**
		 * Rolls back activation procedures when de-activating the plugin
		 */
		public function deactivate() {
			
		}

		/**
		 * Initializes variables
		 */
		public function init() {

			self::$defaultSettings = self::getDefaultSettings();
			$this->settings = self::getSettings();
			if ( !defined( 'GTP_LOAD_LS_CSS' ) ) {
				($this->settings[ 'lang_switcher' ][ 'zwt_ls_theme' ] == 0) ? define( 'GTP_LOAD_LS_CSS', true ) : define( 'GTP_LOAD_LS_CSS', false );
			}
		}

		/**
		 * Executes the logic of upgrading from specific older versions of the plugin to the current version
		 * @param string $dbVersion
		 */
		public function upgrade( $dbVersion = 0 ) {
			/* all general upgrade procedures are implemented in the ZWT_Translation_Network class upgrade function */
		}

		/**
		 * Checks that the object is in a correct state
		 * @param string $property An individual property to check, or 'all' to check all of them
		 * @return bool
		 */
		protected function isValid( $property = 'all' ) {
// Note: __set() calls validateSettings(), so settings are never invalid

			return true;
		}

		/*
		 * Plugin Settings
		 */

		/**
		 * Establishes initial values for all settings
		 * @return array
		 */
		public static function getDefaultSettings() {
			global $wpdb;
//let defualt language be the default admin lang
			$default_admin_locale = get_locale();

			$zanto_settings = array(
				'db-version' => GTP_ZANTO_VERSION
			);

			$blog_setup = array(
				'auto_add_subscribers' => 0,
				'browser_lang_redirect' => 0,
				'browser_lr_time' => 24,
				'site_visibility' => 1
			);

			$translation_settings = array(
				'default_admin_locale' => $default_admin_locale,
				'lang_url_format' => 0,
				'download_wp_translations' => false
			);
			$language_switcher = array(
				'alt_lang_availability' => 0,
				'alt_lang_availability_text' => __( 'This article is also available in', 'Zanto' ),
				'show_footer_selector' => 1,
				'language_order' => null,
				'elements' => array( 'flag' => 1, 'native_name' => 1, 'translated_name' => 1 ),
				'skip_missing_trans' => 0,
				'front_page_trans' => 0,
				'post_trans_links' => 0,
				'post_tl_position' => 'below',
				'post_availability_text' => __( 'This post is also available in: %s', 'Zanto' ),
				'zwt_ls_theme' => 0,
				'zwt_ls_custom_css' => '',
				'switcher_in_wpmenu' => 0,
				'menu_for_ls' => '',
				'post_tl_style' => 0,
				'custom_flag_url' => 0,
				'custom_flag_ext' => 'png',
				'use_custom_flags' => 0
			);

			$setup_status = array(
				'setup_wizard' => 'incomplete',
				'setup_interface' => 'two'
			);

			return array(
				'zanto_settings' => $zanto_settings,
				'blog_setup' => $blog_setup,
				'translation_settings' => $translation_settings,
				'setup_status' => $setup_status,
				'lang_switcher' => $language_switcher
			);
		}

		/**
		 * Retrieves all of the settings from the database
		 * @return array
		 */
		public static function getSettings() {
			$settings = shortcode_atts(
			self::$defaultSettings, get_option( ZWT_Base::PREFIX . 'zanto_settings', array( ) )
			);

			return $settings;
		}

		/**
		 * Validates submitted setting values before they get saved to the database. Invalid data will be overwritten with defaults.
		 * @param array $newSettings
		 * @return array
		 */
		public function validateSettings( $newSettings ) {
			$newSettings = shortcode_atts( $this->settings, $newSettings );

			if ( !isset( $newSettings[ 'db-version' ] ) || !is_string( $newSettings[ 'db-version' ] ) )
				$newSettings[ 'db-version' ] = GTP_ZANTO_VERSION;

			return $newSettings;
		}

		/**
		 * This is to enable recognition of .mo files by wordpress uploading system.
		 */
		function add_custom_mimes( $existing_mimes=array( ) ) {
			$existing_mimes[ 'mo' ] = 'application/octet-stream';
			return $existing_mimes;
		}

		protected function validate_form_action( $post_values ) {
			if ( did_action( 'init' ) !== 1 ) {
				return;
			}
			global $wpdb, $blog_id;
			$user_id = get_current_user_id();
			$lang_codes = $wpdb->get_col( "SELECT default_locale FROM  {$wpdb->base_prefix}zwt_languages" );
			if ( current_user_can( 'manage_network_plugins' ) ) {

				$userblog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs WHERE deleted='0' AND spam='0'" );
			} else {
				$user_blogs = get_blogs_of_user( $user_id );
				$userblog_ids = array( );

				foreach ( $user_blogs as $user_site )
					$userblog_ids[ ] = $user_site->userblog_id;
			}
// interface 1 validation
			if ( isset( $post_values[ 'interface_1_finish' ] ) ) {
// sites that were checked in form
				$selected_sites = array( );
				foreach ( $post_values as $field => $value ) {
					if ( preg_match( "/^gtr_select_site_/", $field ) )
						if ( "on" == $value )
							$selected_sites[ ] = preg_replace( '/\D/', '', $field );
				}

// sites whose langs were chosen
				$site_langs = array( );

				foreach ( $post_values as $field => $value ) {
					if ( preg_match( "/^language_of_blog_/", $field ) ) {
						$id = preg_replace( '/\D/', '', $field );
						if ( in_array( $id, $selected_sites ) ) {
							if ( $id == '' || $value == '' ) {
								add_notice( __( 'Please make sure the form is properly filled.', 'Zanto' ), 'error' );
								return false;
							}

							if ( in_array( $id, $userblog_ids ) && in_array( $value, $lang_codes ) ) {
								$site_langs[ $id ] = $value;
							} else {
								add_notice( __( 'This operation is not permited.', 'Zanto' ), 'error' );
								return false;
							}
						}
					}
				}
				if ( empty( $site_langs ) ) {
					add_notice( __( 'No blogs were selected', 'Zanto' ), 'error' );
					return false;
				}
				if ( count( array_unique( $site_langs ) ) < count( $site_langs ) ) {
					add_notice( __( 'Blogs in a translation network should have unique languages.', 'Zanto' ), 'error' );
					return false;
				}

				return $site_langs;
			}
			/* interface 3 validation */
			if ( isset( $post_values[ 'interface_3_finish' ] ) ) {
				if ( empty( $post_values[ 'trans_network_id' ] ) || empty( $post_values[ 'language_of_blog' ] ) ) {
					add_notice( __( 'Please fill in all form values.', 'Zanto' ), 'error' );
					return false;
				}
				if ( current_user_can( 'manage_network_plugins' ) ) {
					$user_trans_ids = $wpdb->get_col( "SELECT trans_id FROM {$wpdb->base_prefix}zwt_trans_network" );
				} else {
					$user_trans_ids = get_user_meta( $user_id, 'zwt_installed_transnetwork', false );
				}

				if ( !in_array( $post_values[ 'trans_network_id' ], $user_trans_ids ) ) {
					add_notice( __( 'This operation is not permited.', 'Zanto' ), 'error' );
					return false;
				}
				$trans_id_langs = $wpdb->get_col( $wpdb->prepare( "SELECT lang_code FROM {$wpdb->base_prefix}zwt_trans_network WHERE trans_id = %d", $post_values[ 'trans_network_id' ] ) );
				if ( in_array( $post_values[ 'language_of_blog' ], $trans_id_langs ) ) {
					add_notice( __( 'The language you have chosen for the blog already exists with another blog in the chosen translation network.', 'Zanto' ), 'error' );
					return false;
				}


				if ( in_array( $blog_id, $userblog_ids ) && in_array( $post_values[ 'language_of_blog' ], $lang_codes ) ) {
					$clean_values[ 'trans_network_id' ] = $post_values[ 'trans_network_id' ];
					$clean_values[ 'language_of_blog' ] = $post_values[ 'language_of_blog' ];
				} else {
					add_notice( __( 'This operation is not permited.', 'Zanto' ), 'error' );
					return false;
				}
				if ( isset( $post_values[ 'language_of_blog' ] ) && isset( $_POST[ 'zwt_add_blog_trans' ] ) ) {// only applicable for translation network page
					if ( !is_numeric( $post_values[ 'id_of_blog' ] ) ) {
						add_notice( __( 'No Blog was chosen.', 'Zanto' ), 'error' );
						return false;
					}
					if ( !in_array( $post_values[ 'id_of_blog' ], $userblog_ids ) ) {
						add_notice( __( 'This operation is not permited.', 'Zanto' ), 'error' );
						return false;
					} else {
						$clean_values[ 'id_of_blog' ] = $post_values[ 'id_of_blog' ];
					}
				}
				return $clean_values;
			}
		}

		public function form_action() {
			if ( did_action( 'init' ) !== 1 )
				return;
			global $wpdb, $blog_id, $site_id, $zwt_site_obj;
			if ( isset( $_POST ) && current_user_can( 'manage_options' ) ) {
// Interface 1 operations
				if ( isset( $_POST[ 'interface_1_back' ] ) && wp_verify_nonce( $_POST[ 'zwt_translation_interface_1' ], 'zwt_translation_setting_nonce_1' ) ) {
					self::save_setting( 'settings', array( 'setup_status' =>
						array(
							'setup_wizard' => 'incomplete',
							'setup_interface' => 'two'
					) ) );
				}
				if ( isset( $_POST[ 'interface_1_finish' ] ) && wp_verify_nonce( $_POST[ 'zwt_translation_interface_1' ], 'zwt_translation_setting_nonce_1' ) ) {
					$site_langs = $this->validate_form_action( $_POST );
					if ( $site_langs ) {
//$_POST is clear...
//a translation network may belong to a different owner so new translation network id is calculated from all id's
						$trans_id_ceiling = $wpdb->get_var( "SELECT MAX(trans_id) FROM {$wpdb->base_prefix}zwt_trans_network" );
						$new_trans_id = $trans_id_ceiling + 1;
						$current_blog = $wpdb->blogid;
						foreach ( $site_langs as $current_blog_id => $current_blog_lang ) {
							switch_to_blog( $current_blog_id );
							if ( current_user_can( 'manage_options' ) ) {
								$wpdb->query( $wpdb->prepare( "INSERT INTO `$wpdb->options` (`option_name`, `option_value`, `autoload`) VALUES (%s, %s, %s) ON DUPLICATE KEY UPDATE `option_name` = VALUES(`option_name`), `option_value` = VALUES(`option_value`), `autoload` = VALUES(`autoload`)", "WPLANG", $current_blog_lang, "yes" ) );
								$wpdb->query( $wpdb->prepare( "INSERT INTO {$wpdb->base_prefix}zwt_trans_network (blog_id, trans_id, lang_code) VALUES (%d, %d, %s) ", $current_blog_id, $new_trans_id, $current_blog_lang ) );
								self::save_setting( 'settings', array( 'setup_status' =>
									array(
										'setup_wizard' => 'complete',
										'setup_interface' => 'four'
								) ) );

								zwt_add_links( $blog_id, $new_trans_id, 0 );
							}
						}
						switch_to_blog( $current_blog );
						add_user_meta( get_current_user_id(), 'zwt_installed_transnetwork', $new_trans_id, false );
						zwt_network_vars( $new_trans_id, 'update', 'main_lang_blog', $blog_id );
					}

					else
						return;
				}

				/* Interface 2 operations */
				if ( isset( $_POST[ 'interface_2_next' ] ) && wp_verify_nonce( $_POST[ 'zwt_translation_interface_2' ], 'zwt_translation_setting_nonce_2' ) ) {
					if ( isset( $_POST[ 'zwt-setup-interface' ] ) && $_POST[ 'zwt-setup-interface' ] == 1 )
						self::save_setting( 'settings', array( 'setup_status' =>
							array(
								'setup_wizard' => 'incomplete',
								'setup_interface' => 'one'
						) ) );
					else
						self::save_setting( 'settings', array( 'setup_status' =>
							array(
								'setup_wizard' => 'incomplete',
								'setup_interface' => 'three'
						) ) );
				}
				/* Interface 3 operations */
				if ( isset( $_POST[ 'interface_3_back' ] ) && wp_verify_nonce( $_POST[ 'zwt_translation_interface_3' ], 'zwt_translation_setting_nonce_3' ) ) {
					self::save_setting( 'settings', array( 'setup_status' =>
						array(
							'setup_wizard' => 'incomplete',
							'setup_interface' => 'two'
					) ) );
				}
				if ( isset( $_POST[ 'interface_3_finish' ] ) && wp_verify_nonce( $_POST[ 'zwt_translation_interface_3' ], 'zwt_translation_setting_nonce_3' ) ) {
					$clean_values = $this->validate_form_action( $_POST );
					if ( $clean_values ) {
						if ( current_user_can( 'manage_options' ) ) {
							$wpdb->query( $wpdb->prepare( "INSERT INTO `$wpdb->options` (`option_name`, `option_value`, `autoload`) VALUES (%s, %s, %s) ON DUPLICATE KEY UPDATE `option_name` = VALUES(`option_name`), `option_value` = VALUES(`option_value`), `autoload` = VALUES(`autoload`)", "WPLANG", $clean_values[ 'language_of_blog' ], "yes" ) );
							$wpdb->query( $wpdb->prepare( "INSERT INTO {$wpdb->base_prefix}zwt_trans_network (blog_id, trans_id, lang_code) VALUES (%d, %d, %s) ", $wpdb->blogid, $clean_values[ 'trans_network_id' ], $clean_values[ 'language_of_blog' ] ) );
							self::save_setting( 'settings', array( 'setup_status' =>
								array(
									'setup_wizard' => 'complete',
									'setup_interface' => 'four'
							) ) );

							zwt_add_links( $blog_id, $clean_values[ 'trans_network_id' ], 0 );

							$transnet_blogs = $zwt_site_obj->modules[ 'trans_network' ]->get_transnet_blogs( true );
							foreach ( $transnet_blogs as $trans_blog ) {// clear cache to update the translation network in the other blogs to include the new blog
								if ( $trans_blog[ 'blog_id' ] == $blog_id )
									continue;
								switch_to_blog( $trans_blog[ 'blog_id' ] );
								$blog_trans_cache = new zwt_cache( 'translation_network', true );
								$blog_trans_cache->clear();
								restore_current_blog();
							}
						}
					}
				}
				/* Interface 4 operations */
				if ( isset( $_POST[ 'interface_4_save' ] ) && wp_verify_nonce( $_POST[ 'zwt_translation_interface_4' ], 'zwt_translation_setting_nonce_4' ) ) {
					$c_trans_net = $zwt_site_obj->modules[ 'trans_network' ];


					if ( !isset( $_GET[ 'stg_scope' ] ) ) {
						do_action( 'zwt_stgs_pre_save', $_POST );

						if ( isset( $_POST[ 'primary_trans_lang_blog' ] ) ) {
							zwt_network_vars( $c_trans_net->transnet_id, 'update', 'main_lang_blog', $_POST[ 'primary_trans_lang_blog' ] );
							$c_trans_net->get_primary_lang_blog( true );
						}

						if ( isset( $_POST[ 'zwt_seo_headlangs' ] ) ) {
							zwt_network_vars( $c_trans_net->transnet_id, 'update', 'add_seo_headlangs', 1 );
						} else {
							zwt_network_vars( $c_trans_net->transnet_id, 'update', 'add_seo_headlangs', 0 );
						}

						if ( isset( $_POST[ 'zwt_url_format' ] ) ) {
							$parma_type = get_option( 'permalink_structure' );
							if ( empty( $parma_type ) && $_POST[ 'zwt_url_format' ] == 1 ) {
								add_notice( __( 'Your permalink structure does not support adding languages to directories option', 'Zanto' ), 'error' );
							} elseif ( !empty( $parma_type ) && $_POST[ 'zwt_url_format' ] == 2 ) {
								add_notice( __( 'Your permalink structure does not support adding language parameter to URL', 'Zanto' ), 'error' );
							} elseif ( in_array( $_POST[ 'zwt_url_format' ], array( 0, 1, 2 ) ) ) {
								self::save_setting( 'settings', array( 'translation_settings' =>
									array(
										'lang_url_format' => $_POST[ 'zwt_url_format' ]
								) ) );
								zwt_add_links( $blog_id, $c_trans_net->transnet_id, $_POST[ 'zwt_url_format' ] );
							}
						}
						if ( isset( $_POST[ 'zwt_auto_user' ] ) && in_array( $_POST[ 'zwt_auto_user' ], array( 0, 1 ) ) ) {
							self::save_setting( 'settings', array( 'blog_setup' =>
								array(
									'auto_add_subscribers' => $_POST[ 'zwt_auto_user' ]
							) ) );
						}

						if ( isset( $_POST[ 'zwt_site_visibility' ] ) && in_array( $_POST[ 'zwt_site_visibility' ], array( 0, 1 ) ) ) {
							$ls_exclude_list = get_metadata( 'site', $site_id, 'zwt_' . $c_trans_net->transnet_id . '_exclude', true );

							self::save_setting( 'settings', array( 'blog_setup' =>
								array(
									'site_visibility' => $_POST[ 'zwt_site_visibility' ]
							) ) );

							if ( $_POST[ 'zwt_site_visibility' ] ) {
								if ( isset( $ls_exclude_list[ $blog_id ] ) ) {
									unset( $ls_exclude_list[ $blog_id ] );
									update_metadata( 'site', $site_id, 'zwt_' . $c_trans_net->transnet_id . '_exclude', $ls_exclude_list );
								}
							} else {
								if ( !isset( $ls_exclude_list[ $blog_id ] ) ) {
									$ls_exclude_list[ $blog_id ] = 1;
									update_metadata( 'site', $site_id, 'zwt_' . $c_trans_net->transnet_id . '_exclude', $ls_exclude_list );
								}
							}
						}

						if ( isset( $_POST[ 'zwt_browser_lang_redct' ] ) and in_array( $_POST[ 'zwt_browser_lang_redct' ], array( 0, 1, 2 ) ) ) {
							self::save_setting( 'settings', array( 'blog_setup' =>
								array(
									'browser_lang_redirect' => $_POST[ 'zwt_browser_lang_redct' ]
							) ) );
						}

						if ( isset( $_POST[ 'zwt_browser_lang_redct_time' ] ) ) {
							self::save_setting( 'settings', array( 'blog_setup' =>
								array(
									'browser_lr_time' => absint( $_POST[ 'zwt_browser_lang_redct_time' ] )
							) ) );
						}
						do_action( 'zwt_stgs_post_save', $_POST );
					}

					if ( isset( $_GET[ 'stg_scope' ] ) && $_GET[ 'stg_scope' ] == 'lang_swchr' ) {
						do_action( 'zwt_ls_pre_save', $_POST );

						if ( isset( $_POST[ 'zwt_no_translation' ] ) && in_array( $_POST[ 'zwt_no_translation' ], array( 0, 1 ) ) ) {
							self::save_setting( 'settings', array( 'lang_switcher' =>
								array(
									'skip_missing_trans' => $_POST[ 'zwt_no_translation' ]
							) ) );
						}

						if ( isset( $_POST[ 'zwt_front_page_trans' ] ) && in_array( $_POST[ 'zwt_front_page_trans' ], array( 0, 1 ) ) ) {
							self::save_setting( 'settings', array( 'lang_switcher' =>
								array(
									'front_page_trans' => $_POST[ 'zwt_front_page_trans' ]
							) ) );
						}

						if ( isset( $_POST[ 'zwt_post_availabitlity_text' ] ) ) {
							self::save_setting( 'settings', array( 'lang_switcher' =>
								array(
									'post_availability_text' => sanitize_text_field( $_POST[ 'zwt_post_availabitlity_text' ] )
							) ) );
						}

						if ( isset( $_POST[ 'zwt_post_trans_links' ] ) ) {
							self::save_setting( 'settings', array( 'lang_switcher' =>
								array(
									'alt_lang_availability' => 1
							) ) );
						} else {
							self::save_setting( 'settings', array( 'lang_switcher' =>
								array(
									'alt_lang_availability' => 0
							) ) );
						}

						if ( isset( $_POST[ 'zwt_post_link_pos' ] ) && in_array( $_POST[ 'zwt_post_link_pos' ], array( 'above', 'below' ) ) ) {
							self::save_setting( 'settings', array( 'lang_switcher' =>
								array(
									'post_tl_position' => sanitize_text_field( $_POST[ 'zwt_post_link_pos' ] )
							) ) );
						}

						if ( isset( $_POST[ 'zwt_post_link_style' ] ) && in_array( $_POST[ 'zwt_post_link_style' ], array( 0, 1 ) ) ) {
							self::save_setting( 'settings', array( 'lang_switcher' =>
								array(
									'post_tl_style' => $_POST[ 'zwt_post_link_style' ]
							) ) );
						}


						if ( isset( $_POST[ 'zwt_footer_ls' ] ) ) {
							self::save_setting( 'settings', array( 'lang_switcher' =>
								array(
									'show_footer_selector' => 1
							) ) );
						} else {
							self::save_setting( 'settings', array( 'lang_switcher' =>
								array(
									'show_footer_selector' => 0
							) ) );
						}

						if ( isset( $_POST[ 'zwt_ls_elements' ] ) && is_array( $_POST[ 'zwt_ls_elements' ] ) ) {
							$elements = array( 'flag' => 0, 'native_name' => 0, 'translated_name' => 0 );

							foreach ( $_POST[ 'zwt_ls_elements' ] as $switcher_element => $value ) {
								if ( in_array( $switcher_element, array( 'flag', 'native_name', 'translated_name' ) ) ) {
									$elements[ $switcher_element ] = 1;
								} else {
									$elements[ $switcher_element ] = 0;
								}
							}
							self::save_setting( 'settings', array( 'lang_switcher' =>
								array(
									'elements' => $elements
							) ) );
							if ( empty( $_POST[ 'zwt_ls_elements' ] ) ) {
								add_notice( __( 'Atleast one element must be included in the footer langauge switcher.', 'Zanto' ), 'error' );
							}
						}

						if ( isset( $_POST[ 'zwt_ls_theme' ] ) ) {
							global $zwt_language_switcher;
							self::save_setting( 'settings', array( 'lang_switcher' =>
								array(
									'zwt_ls_theme' => $_POST[ 'zwt_ls_theme' ]
							) ) );
							$zwt_language_switcher->get_settings( true );
							$zwt_language_switcher->get_ls_theme();
						}

						if ( isset( $_POST[ 'zwt_lang_order' ] ) && !is_null( $_POST[ 'zwt_lang_order' ] ) && !empty( $_POST[ 'zwt_lang_order' ] ) ) {
							$lang_order = explode( ',', $_POST[ 'zwt_lang_order' ] );
							$transnet_blogs = $c_trans_net->transnet_blogs;
							foreach ( $transnet_blogs as $trans_blog )
								$blog_ids[ ] = $trans_blog[ 'blog_id' ];
							foreach ( $lang_order as $blog_lo ) {
								if ( in_array( $blog_lo, $blog_ids ) )
									continue;
								else
									$lang_order = null;
							}
							if ( !is_null( $lang_order ) )
								self::save_setting( 'settings', array( 'lang_switcher' =>
									array(
										'language_order' => $lang_order
								) ) );
							$c_trans_net->zwt_trans_cache[ 'zwt_trans_network_cache' ]->clear();
						}

						if ( isset( $_POST[ 'zwt_additional_css' ] ) ) {
							self::save_setting( 'settings', array( 'lang_switcher' =>
								array(
									'zwt_ls_custom_css' => wp_filter_nohtml_kses( $_POST[ 'zwt_additional_css' ] )
							) ) );
						}
						do_action( 'zwt_ls_post_save', $_POST );
					}
				}


				/* end interface operations

				  Translation Network Page Operations */
				if ( isset( $_POST[ 'zwt_add_blog_trans' ] ) && wp_verify_nonce( $_POST[ 'zwt_updatetrans_nonce' ], 'zwt_update_transnetwork_nonce' ) ) {

					if ( isset( $_POST[ 'hidden_blog_ids' ] ) && isset( $_POST[ 'zwt_blog_id' ] ) ) {
						if ( is_numeric( $_POST[ 'hidden_blog_ids' ] ) && is_numeric( $_POST[ 'zwt_blog_id' ] ) ) {
							add_notice( __( 'Zanto got confused with the submission, please make sure javascript is enabled.', 'Zanto' ), 'error' );
							return false;
						} else {
							$chosen_blog_id = is_numeric( $_POST[ 'hidden_blog_ids' ] ) ? $_POST[ 'hidden_blog_ids' ] : $_POST[ 'zwt_blog_id' ];
						}
					} else {
						$chosen_blog_id = $_POST[ 'zwt_blog_id' ];
					}

					if ( !isset( $_POST[ 'blog_trans_ids' ] ) || !isset( $_POST[ 'language_of_blog' ] ) ) {
						add_notice( __( 'Nothing was selected.', 'Zanto' ), 'error' );
						return false;
					}
					$validate_array = array( );
					$validate_array[ 'interface_3_finish' ] = 1;
					$validate_array[ 'trans_network_id' ] = $_POST[ 'blog_trans_ids' ];
					$validate_array[ 'language_of_blog' ] = $_POST[ 'language_of_blog' ];
					$validate_array[ 'id_of_blog' ] = $chosen_blog_id;
					$clean_values = $this->validate_form_action( $validate_array );
					switch_to_blog( $clean_values[ 'id_of_blog' ] );
					$wpdb->query( $wpdb->prepare( "INSERT INTO `$wpdb->options` (`option_name`, `option_value`, `autoload`) VALUES (%s, %s, %s) ON DUPLICATE KEY UPDATE `option_name` = VALUES(`option_name`), `option_value` = VALUES(`option_value`), `autoload` = VALUES(`autoload`)", "WPLANG", $clean_values[ 'language_of_blog' ], "yes" ) );
					$wpdb->query( $wpdb->prepare( "INSERT INTO {$wpdb->base_prefix}zwt_trans_network (blog_id, trans_id, lang_code) VALUES (%d, %d, %s) ", $wpdb->blogid, $clean_values[ 'trans_network_id' ], $clean_values[ 'language_of_blog' ] ) );

					ZWT_Settings::save_setting( 'settings', array( 'setup_status' =>
						array(
							'setup_wizard' => 'complete',
							'setup_interface' => 'four'
					) ) );

					zwt_add_links( $blog_id, $clean_values[ 'trans_network_id' ], 0 );

					$transnet_blogs = $zwt_site_obj->modules[ 'trans_network' ]->get_transnet_blogs( true );

					restore_current_blog();

					foreach ( $transnet_blogs as $trans_blog ) {// clear cache to update the translation network in the other blogs to include the new blog
						if ( $trans_blog[ 'blog_id' ] == $clean_values[ 'id_of_blog' ] )
							continue;
						switch_to_blog( $trans_blog[ 'blog_id' ] );
						$blog_trans_cache = new zwt_cache( 'translation_network', true );
						$blog_trans_cache->clear();
						restore_current_blog();
					}
				}

				/* end Translation Network Page Operations. */ else {
					if ( isset( $_POST[ 'add_trans_network' ] ) ) {
						print 'nonce error: Please try loging in again for security reasons!';
						exit;
					}
				}
			}
		}

	}

// end ZWT_Settings
}
