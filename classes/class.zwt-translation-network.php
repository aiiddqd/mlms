<?php

if ( $_SERVER[ 'SCRIPT_FILENAME' ] == __FILE__ )
	die( 'Access denied.' );

if ( !class_exists( 'ZWT_Translation_Network' ) ) {

	/**
	 * Translation Network instance class
	 * @package ZWT_Base
	 */
	class ZWT_Translation_Network extends ZWT_Module {

		protected $transnet_id;
		protected $transnet_blogs = array( );
		public $primary_lang_blog;
		protected $zwt_trans_cache = array( );
		protected $ZWT_Settings;
		protected static $readableProperties = array( 'transnet_blogs', 'transnet_id', 'zwt_trans_cache' );
		protected static $writeableProperties = array( );

		/**
		 * Constructor
		 */
		public function __construct() {
			require_once (GTP_PLUGIN_PATH . '/includes/cache.php' );
			$this->ZWT_Settings = ZWT_Settings::getInstance();
			$this->ZWT_Settings->init();
			$this->registerHookCallbacks();
		}

		public function initialize_cache() {
			if ( did_action( 'init' ) !== 1 )
				return;

			$this->zwt_trans_cache[ 'zwt_trans_network_cache' ] = new zwt_cache( 'translation_network', true );
			$this->zwt_trans_cache[ 'zwt_locale_cache' ] = new zwt_cache( 'locale_code', true );
			$this->zwt_trans_cache[ 'zwt_lang_name_cache' ] = new zwt_cache( 'lang_name', true );
		}

		/**
		 * Initializes variables
		 */
		public function init() {
			if ( did_action( 'init' ) !== 1 )
				return;
			$this->transnet_id = $this->get_trans_id( true );
			$this->transnet_blogs = $this->get_transnet_blogs( true );
			$this->primary_lang_blog = $this->get_primary_lang_blog();
			if ( $this->ZWT_Settings->settings[ 'blog_setup' ][ 'auto_add_subscribers' ] == 1 ) {
				$this->auto_add_user_to_site();
			}
		}

		public function auto_add_user_to_site( $r_blog_id=null ) {
			global $blog_id, $current_user;
			if ( $r_blog_id == null )
				$r_blog_id = $blog_id;
			//verify user is logged in before proceeding
			if ( !is_user_logged_in() )
				return false;
			//verify user is not a member of this site
			if ( !is_user_member_of_blog() ) {
				//add user to this site as a subscriber
				add_user_to_blog( $r_blog_id, $current_user->ID, 'subscriber' );
			}
		}

		public function get_primary_lang_blog( $refresh=false ) {
			global $site_id;
			if ( !$this->primary_lang_blog || $refresh ) {
				$primary_lang_blog = zwt_network_vars( $this->transnet_id, 'get', 'main_lang_blog' );
				if ( !$primary_lang_blog ) {
					$trans_id = $this->get_trans_id();
					if ( isset( $trans_id ) ) {
						if ( !isset( $_REQUEST[ 'page' ] ) || $_REQUEST[ 'page' ] != 'zwt_settings' ) {
							function_exists('add_notice') && add_notice( __( 'Please set the new Primary Language for this translation network.', 'Zanto' ) . '&nbsp;<a class="button-primary" href="' . admin_url( 'admin.php?page=zwt_settings' ) . '">' . __( 'Set Primary Translation Language', 'Zanto' ) . '</a>' );
						}
					}
				}
				$this->primary_lang_blog = $primary_lang_blog;
			}
			return $this->primary_lang_blog;
		}

		public function get_trans_id( $refresh = false ) {
			global $wpdb, $blog_id;

			if ( $refresh || !$this->transnet_id ) {

				if ( isset( $this->zwt_trans_cache[ 'zwt_trans_network_cache' ] ) ) {
					$trans_net_id = $this->zwt_trans_cache[ 'zwt_trans_network_cache' ]->get( 'trans_net_id' . $blog_id );
				} else {
					$trans_net_id = null;
				}
				if ( !is_numeric( $trans_net_id ) ) {
					$trans_net_id = $wpdb->get_var( $wpdb->prepare(
					"SELECT  trans_id 
					FROM {$wpdb->base_prefix}zwt_trans_network
                    WHERE  blog_id =%d", $blog_id ) );

					if ( isset( $this->zwt_trans_cache[ 'zwt_trans_network_cache' ] ) ) {
						$this->zwt_trans_cache[ 'zwt_trans_network_cache' ]->set( 'trans_net_id' . $blog_id, $trans_net_id );
					}
				}

				$this->transnet_id = $trans_net_id;
			}
			return $this->transnet_id;
		}

		public function get_transnet_blogs( $refresh = false ) {
			global $wpdb, $blog_id, $switched;
			if ( $refresh || !$this->transnet_blogs ) {

				if ( isset( $this->zwt_trans_cache[ 'zwt_trans_network_cache' ] ) ) {
					$trans_blog_array = $this->zwt_trans_cache[ 'zwt_trans_network_cache' ]->get( 'trans_network_' . $blog_id );
				} else {
					$trans_blog_array = null;
				}

				if ( !$trans_blog_array || !is_array( $trans_blog_array ) || count( $trans_blog_array, 1 ) < 2 ) {

					if ( $switched ) {
						global $blog_id;
						return $wpdb->get_results( $wpdb->prepare(
						"SELECT blog_id, lang_code
                                                    FROM {$wpdb->base_prefix}zwt_trans_network
                                                    WHERE trans_id = ( 
                                                    SELECT trans_id
                                                    FROM {$wpdb->base_prefix}zwt_trans_network
                                                    WHERE blog_id = %d ) ", $blog_id ), ARRAY_A );
					}

					unset( $trans_blog_array );
					$trans_blog_array = $wpdb->get_results( $wpdb->prepare(
					"SELECT blog_id, lang_code
                    FROM {$wpdb->base_prefix}zwt_trans_network
                    WHERE trans_id = %d", $this->get_trans_id() ), ARRAY_A );

					$ordered_blog_langs = $this->order_lang_blogs( $trans_blog_array );
					$trans_blog_array = $ordered_blog_langs;
					if ( isset( $this->zwt_trans_cache[ 'zwt_trans_network_cache' ] ) ) {
						$this->zwt_trans_cache[ 'zwt_trans_network_cache' ]->set( 'trans_network_' . $blog_id, $trans_blog_array );
					}
				}

				$this->transnet_blogs = $trans_blog_array;
			}

			/* hide languages for front end here use user meta to show him hidden languages when logged in but don't remove them if not logged in
			  global $current_user;
			 */
			return $this->transnet_blogs;
		}

		//function is provided with raw results form the translation network table
		public function order_lang_blogs( $trans_blog_array ) {
			$order_array = $this->ZWT_Settings->settings[ 'lang_switcher' ][ 'language_order' ];
			$orderd_array = array( );
			if ( is_array( $order_array ) ) {
				foreach ( $order_array as $order_id ) {
					foreach ( $trans_blog_array as $trans_blog ) {
						if ( $trans_blog[ 'blog_id' ] == $order_id )
							$orderd_array[ ] = $trans_blog;
					}
				}
				if ( count( $orderd_array ) == count( $trans_blog_array ) )
					return $orderd_array;
				else {
					add_notice( __( 'The number of language blog sites in the network was changed. Please update the language order', 'Zanto' ) );
					ZWT_Settings::save_setting( 'settings', array( 'lang_switcher' =>
						array(
							'language_order' => null
					) ) );
				}
			}
			else
				return $trans_blog_array;
		}

		public function remove_trans_blog() {
			// remove a blog from the network
		}

		public function update_global_cache() {
			zwt_update_site_links( $this->transnet_id );
		}

		/**
		 * Public getter for protected variables
		 * @param string $variable
		 * @return mixed
		 */
		public function activate() {
			
		}

		public function __set( $variable, $value ) {
			
		}

		/**
		 * Checks that the object is in a correct state
		 * @param string $property An individual property to check, or 'all' to check all of them
		 * @return bool
		 */
		protected function isValid( $property = 'all' ) {

			if ( isset( $_GET[ 'action' ] ) && $_REQUEST[ 'action' ] != 'activate' )
				return true;
			if ( !empty( $this->trans_network_id ) && !empty( $this->site_owner_id ) )
				return true;

			else
				return false;
		}

		/**
		 * Executes the logic of upgrading from specific older versions of the plugin to the current version
		 * @param string $dbVersion
		 */
		public function upgrade( $dbVersion = 0, $networkwide = false ) {
			global $wpdb;

			if ( $networkwide ) {//these upgrade procedures affect the entire network at once
				if ( version_compare( $dbVersion, '0.2.0', '<' ) ) {
					global $blog_id;
					zwt_add_links( $blog_id, $this->transnet_id, $this->ZWT_Settings->settings[ 'translation_settings' ][ 'lang_url_format' ] );
				}
				if ( version_compare( $dbVersion, '0.2.4', '<' ) ) {

					$settings_array = get_option( 'zwt_zanto_settings' );
					update_option( '_zwt_settings_buckup', $settings_array );
					if ( isset( $settings_array[ 'lang_switcher' ] ) ) { //add use_custom_flag setting and modify the way custom flag and language switcher urls  are stored
						$lang_switchr_stgs = $settings_array[ 'lang_switcher' ];

						if ( isset( $lang_switchr_stgs[ 'zwt_ls_theme' ] ) && $lang_switchr_stgs[ 'zwt_ls_theme' ] ) {
							$lang_switchr_stgs[ 'zwt_ls_theme' ] = str_replace( WP_CONTENT_DIR, '', get_template_directory() ) . '/zanto/' . $lang_switchr_stgs[ 'zwt_ls_theme' ] . '.zwt.php';
						}
						if ( isset( $lang_switchr_stgs[ 'custom_flag_url' ] ) && $lang_switchr_stgs[ 'custom_flag_url' ] ) {
							$lang_switchr_stgs[ 'custom_flag_url' ] = str_replace( content_url(), '', $lang_switchr_stgs[ 'custom_flag_url' ] );
							$lang_switchr_stgs[ 'use_custom_flags' ] = 1;
						}

						$settings_array[ 'lang_switcher' ] = $lang_switchr_stgs;
						update_option( 'zwt_zanto_settings', $settings_array );
					}

					//Allow for duplicate language code to support codes with multiple locales e.g de for de_DE, de_LI, de_CH
					$wpdb->query( 'ALTER TABLE ' . $wpdb->base_prefix . 'zwt_languages DROP INDEX code' );
					$wpdb->query( 'ALTER TABLE ' . $wpdb->base_prefix . 'zwt_languages ADD UNIQUE (default_locale)' );
					$wpdb->show_errors();
				}
			} else {// these upgrade procedure affect a single site
			}
		}

		/**
		 * Rolls back activation procedures when de-activating the plugin
		 */
		public function deactivate() {
			
		}

		/**
		 * Does instance stuff
		 * @return bool
		 */
		function get_english_name( $code ) {
			global $wpdb;

			if ( isset( $this->zwt_trans_cache[ 'zwt_lang_name_cache' ] ) ) {
				$eng_name = $this->zwt_trans_cache[ 'zwt_lang_name_cache' ]->get( 'eglish_name_' . $code );
			} else {
				$eng_name = null;
			}
			if ( !$eng_name ) {
				$eng_name = $wpdb->get_var( $wpdb->prepare( "SELECT english_name FROM {$wpdb->base_prefix}zwt_languages WHERE default_locale=%s ", $code ) );
				if ( isset( $this->zwt_trans_cache[ 'zwt_lang_name_cache' ] ) ) {
					$this->zwt_trans_cache[ 'zwt_lang_name_cache' ]->set( 'eglish_name_' . $code, $eng_name );
				}
			}
			return $eng_name;
		}

		function get_language_details( $code ) {
			global $wpdb;

			$dcode = $code;

			if ( isset( $this->zwt_trans_cache[ 'zwt_lang_name_cache' ] ) ) {
				$details = $this->zwt_trans_cache[ 'zwt_lang_name_cache' ]->get( 'language_details_' . $code . $dcode );
			} else {
				$details = null;
			}
			if ( !$details ) {
				$details = $wpdb->get_row( $wpdb->prepare( "
                SELECT
                    default_locale, english_name, lt.name AS display_name
                FROM {$wpdb->base_prefix}zwt_languages l
                    JOIN {$wpdb->base_prefix}zwt_languages_translations lt ON l.default_locale=lt.language_code
                WHERE lt.display_language_code = %s AND code= %s
                ORDER BY major DESC, english_name ASC", $dcode, $code ), ARRAY_A );
				if ( isset( $this->zwt_trans_cache[ 'zwt_lang_name_cache' ] ) ) {
					$this->zwt_trans_cache[ 'zwt_lang_name_cache' ]->set( 'language_details_' . $code . $dcode, $details );
				}
			}

			return $details;
		}

		function get_display_language_name( $lang_code, $display_code=null ) {
			global $wpdb;
			if ( null == $display_code ) {
				$display_code = $lang_code;
			}
			if ( isset( $this->zwt_trans_cache[ 'zwt_lang_name_cache' ] ) ) {
				$translated_name = $this->zwt_trans_cache[ 'zwt_lang_name_cache' ]->get( $lang_code . $display_code );
			} else {
				$translated_name = null;
			}
			if ( !$translated_name ) {
				$translated_name = $wpdb->get_var( $wpdb->prepare( "SELECT name FROM {$wpdb->base_prefix}zwt_languages_translations WHERE language_code=%s AND display_language_code=%s", $lang_code, $display_code ) );
				if ( isset( $this->zwt_trans_cache[ 'zwt_lang_name_cache' ] ) ) {
					$this->zwt_trans_cache[ 'zwt_lang_name_cache' ]->set( $lang_code . $display_code, $translated_name );
				}
			}
			return $translated_name;
		}

		function get_languages( $lang=false ) {
			global $wpdb;
			if ( !$lang ) {
				$lang = get_locale();
			}
			$res = $wpdb->get_results( $wpdb->prepare( "
            SELECT
                code, english_name, default_locale, lt.name AS display_name
            FROM {$wpdb->base_prefix}zwt_languages l
                JOIN {$wpdb->base_prefix}zwt_languages_translations lt ON l.default_locale=lt.language_code
            WHERE lt.display_language_code = %s
            ORDER BY english_name ASC", $lang ), ARRAY_A );
			$languages = array( );
			foreach ( (array) $res as $r ) {
				$languages[ ] = $r;
			}
			return $languages;
		}

		function mod_home_url( $url, $path, $orig_scheme, $blog_id ) {

			// only apply this for home url - not for posts or page permalinks since this filter is called there too
			if ( did_action( 'template_redirect' ) && rtrim( $url, '/' ) == rtrim( get_option( 'home' ), '/' ) ) {
				$url = $this->add_url_lang( $url, $blog_id );
			}

			if ( $path == '/' )
				$url = $this->add_url_lang( $url, $blog_id );

			return $url;
		}

		function get_active_languages() {
			global $zwt_site_obj, $wpdb;

			$trans_network = $zwt_site_obj->modules[ 'trans_network' ];
			$transnet_blogs = $trans_network->transnet_blogs;
			$zwt_al_array = array( );
			$current_language = get_locale();
			foreach ( $transnet_blogs as $trans_blog ) {

				if ( isset( $this->zwt_trans_cache[ 'zwt_lang_name_cache' ] ) ) {
					$alar = $this->zwt_trans_cache[ 'zwt_lang_name_cache' ]->get( 'active_language_' . $trans_blog[ 'lang_code' ] );
				} else {
					$alar = null;
				}

				if ( !$alar || !is_array( $alar ) ) {
					$lang_ary = $wpdb->get_results( $wpdb->prepare( "
                    SELECT id, code, english_name, default_locale
                    FROM {$wpdb->base_prefix}zwt_languages 
                    WHERE
                        default_locale = %s", $trans_blog[ 'lang_code' ] ), ARRAY_A );
					$alar = array_shift( $lang_ary );
					if ( isset( $this->zwt_trans_cache[ 'zwt_lang_name_cache' ] ) ) {
						$this->zwt_trans_cache[ 'zwt_lang_name_cache' ]->set( 'active_language_' . $trans_blog[ 'lang_code' ], $alar );
					}
				}


				$id = $alar[ 'id' ];
				$native_name = $trans_network->get_display_language_name( $trans_blog[ 'lang_code' ] );
				$english_name = $alar[ 'english_name' ];
				if ( !$native_name )
					$native_name = $english_name;
				$translated_name = $trans_network->get_display_language_name( $trans_blog[ 'lang_code' ], $current_language );
				if ( !$translated_name )
					$translated_name = $english_name;
				$language_code = $alar[ 'code' ];

				$zwt_al_array[ $trans_blog[ 'lang_code' ] ] = array( 'id' => $id, 'english_name' => $english_name, 'native_name' => $native_name,
					'code' => $language_code, 'display_name' => $translated_name, 'default_locale' => $trans_blog[ 'lang_code' ] );
			}

			return $zwt_al_array;
		}

		function get_custom_langs() {
			global $wpdb;

			$c_language = get_locale();

			if ( isset( $this->zwt_trans_cache[ 'zwt_lang_name_cache' ] ) ) {
				$custm_lgs = $this->zwt_trans_cache[ 'zwt_lang_name_cache' ]->get( 'custom_langs_' . $c_language );
			} else {
				$custm_lgs = null;
			}

			if ( !$custm_lgs || !is_array( $custm_lgs ) ) {
				$custm_lgs = $wpdb->get_results( $wpdb->prepare( "
                    SELECT l.id, code, default_locale, custom, english_name, lt.name AS display_name
                    FROM {$wpdb->base_prefix}zwt_languages l
                        JOIN {$wpdb->base_prefix}zwt_languages_translations lt ON l.default_locale=lt.language_code
                    WHERE
                        custom=1 AND lt.display_language_code = %s
                    ORDER BY english_name ASC", $c_language ), ARRAY_A );
				if ( isset( $this->zwt_trans_cache[ 'zwt_lang_name_cache' ] ) ) {
					$this->zwt_trans_cache[ 'zwt_lang_name_cache' ]->set( 'custom_langs_' . $c_language, $custm_lgs );
				}
			}

			$languages = array( );
			if ( $custm_lgs ) {
				foreach ( $custm_lgs as $r ) {
					$languages[ $r[ 'default_locale' ] ] = $r;
				}
			} else {
				return $languages;
			}

			if ( isset( $this->zwt_trans_cache[ 'zwt_lang_name_cache' ] ) ) {
				$custm_lgs = $this->zwt_trans_cache[ 'zwt_lang_name_cache' ]->get( 'languages' );
			} else {
				$custm_lgs = null;
			}



			if ( !$custm_lgs ) {

				$custm_lgs = $wpdb->get_results( "
                    SELECT language_code, name
                    FROM {$wpdb->base_prefix}zwt_languages_translations
                    WHERE language_code IN ('" . join( "','", array_keys( $languages ) ) . "') AND language_code = display_language_code
                " );
				if ( isset( $this->zwt_trans_cache[ 'zwt_lang_name_cache' ] ) ) {
					$this->zwt_trans_cache[ 'zwt_lang_name_cache' ]->set( 'languages', $custm_lgs );
				}
			}

			foreach ( $custm_lgs as $row ) {
				$languages[ $row->language_code ][ 'native_name' ] = $row->name;
			}


			return $languages;
		}

//@todo, revise special codes
		function get_lang_code( $locale, $database=false ) {
			if ( $database ) {
				global $wpdb, $blog_id;

				if ( isset( $this->zwt_trans_cache[ 'zwt_locale_cache' ] ) ) {
					$code = $this->zwt_trans_cache[ 'zwt_locale_cache' ]->get( 'code_' . $locale );
				} else {
					$code = null;
				}

				if ( !$code ) {
					$code = $wpdb->get_var( $wpdb->prepare( "SELECT code FROM {$wpdb->base_prefix}zwt_languages WHERE default_locale = %s", $locale ) );
					if ( isset( $this->zwt_trans_cache[ 'zwt_locale_cache' ] ) ) {
						$this->zwt_trans_cache[ 'zwt_locale_cache' ]->set( 'code_' . $locale, $code );
					}
				}
			}
			else
				$code = strtolower( substr( $locale, 0, 2 ) );

			return apply_filters( 'zwt_lang_codes', $code, $locale );
		}

		function get_locale_from_code( $lang_code ) {
			global $wpdb;
			if ( isset( $this->zwt_trans_cache[ 'zwt_locale_cache' ] ) ) {
				$locale = $this->zwt_trans_cache[ 'zwt_locale_cache' ]->get( 'locale_' . $lang_code );
			} else {
				$locale = null;
			}

			if ( !$locale ) {
				$locale = $wpdb->get_var( $wpdb->prepare( "SELECT default_locale FROM {$wpdb->base_prefix}zwt_languages WHERE default_locale = %s", $lang_code ) );
				if ( isset( $this->zwt_trans_cache[ 'zwt_locale_cache' ] ) ) {
					$this->zwt_trans_cache[ 'zwt_locale_cache' ]->set( 'locale_' . $lang_code, $locale );
				}
			}
		}

		function get_locale( $blog_id_x ) {
			global $blog_id;

			if ( $blog_id == $blog_id_x )
				return get_option( 'WPLANG' );
			else {
				$transnet_blogs = $this->transnet_blogs;
				foreach ( $transnet_blogs as $index => $trans_blog ) {
					if ( $trans_blog[ 'blog_id' ] == $blog_id_x )
						return $trans_blog[ 'lang_code' ];
				}
			}
			return false;
		}

		function filter_wplang( $value ) {
			if ( empty( $value ) ) {
				$value = 'en_US';
			}
			return $value;
		}

// Adds the language code to the url.
		function add_url_lang( $url, $c_blog_id=null, $url_format=null ) {
			global $blog_id;
			if ( $c_blog_id == null ) {
				$c_blog_id = $blog_id;
			}
			if ( $url_format == null ) {
				$url_format = $this->ZWT_Settings->settings[ 'translation_settings' ][ 'lang_url_format' ];
			}
			$code = $this->get_lang_code( $this->get_locale( $c_blog_id ), true );

			$abshome = preg_replace( '@\?lang=' . $code . '@i', '', get_blog_option( $c_blog_id, 'home' ) );

			switch ( $url_format ) {
				case '1':
					if ( 0 === strpos( $url, 'https://' ) ) {
						$abshome = preg_replace( '#^http://#', 'https://', $abshome );
					}
					if ( $abshome == $url )
						$url .= '/';
					if ( 0 !== strpos( $url, $abshome . '/' . $code . '/' ) ) {
						// only replace if it is there already
						$url = str_replace( $abshome, $abshome . '/' . $code, $url );
					}
					break;

				case '2':
					// remove any previous value.
					if ( strpos( $url, '?lang=' . $code . '&' ) !== FALSE ) {
						$url = str_replace( '?lang=' . $code . '&', '', $url );
					} elseif ( strpos( $url, '?lang=' . $code . '/' ) !== FALSE ) {
						$url = str_replace( '?lang=' . $code . '/', '', $url );
					} elseif ( strpos( $url, '?lang=' . $code ) !== FALSE ) {
						$url = str_replace( '?lang=' . $code, '', $url );
					} elseif ( strpos( $url, '&lang=' . $code . '/' ) !== FALSE ) {
						$url = str_replace( '&lang=' . $code . '/', '', $url );
					} elseif ( strpos( $url, '&lang=' . $code ) !== FALSE ) {
						$url = str_replace( '&lang=' . $code, '', $url );
					}

					if ( false === strpos( $url, '?' ) ) {
						$url_glue = '?';
					} else {

						// special case post preview link
						$db = debug_backtrace();
						if ( is_admin() && (@$db[ 6 ][ 'function' ] == 'post_preview') ) {
							$url_glue = '&';
						} elseif ( isset( $_POST[ 'comment' ] ) || defined( 'zwt_DOING_REDIRECT' ) ) { // will be used for a redirect
							$url_glue = '&';
						} else {
							$url_glue = '&amp;';
						}
					}
					$url .= $url_glue . 'lang=' . $code;
					break;
				default:
					return $url;
			}
			return $url;
		}

		function permalink_filter( $p, $pid ) {
			if ( is_object( $pid ) ) {
				$pid = $pid->ID;
			}

			if ( $pid == (int) get_option( 'page_on_front' ) ) {
				return $p;
			}

			$p = $this->add_url_lang( $p );

			if ( is_feed() ) {
				$p = str_replace( "&lang=", "&#038;lang=", $p );
			}
			return $p;
		}

		function tax_permalink_filter( $p, $tag ) {
			$p = $this->add_url_lang( $p );
			return $p;
		}

		function category_permalink_filter( $p, $cat_id ) {
			$p = $this->add_url_lang( $p );

			return $p;
		}

		function attachment_link_filter( $link, $id ) {
			return $this->add_url_lang( $link );
		}

// feeds links


		function feed_link( $out ) {
			return $this->add_url_lang( $out );
		}

		function mod_trackback_url( $out ) {
			return $this->add_url_lang( $out );
		}

		function archive_url( $url, $lang ) {
			$url = $this->add_url_lang( $url, $lang );
			return $url;
		}

		function author_link( $url ) {
			$url = $this->add_url_lang( $url );
			return preg_replace( '#^http://(.+)//(.+)$#', 'http://$1/$2', $url );
		}

		function rewrite_rules_filter( $value ) {
			global $blog_id;
			$code = $this->get_lang_code( $this->get_locale( $blog_id ), true );
			foreach ( (array) $value as $k => $v ) {
				$value[ $code . '/' . $k ] = $v;
				unset( $value[ $k ] );
			}
			$value[ $code . '/?$' ] = 'index.php';
			return $value;
		}

		function post_type_archive_link_filter( $link, $post_type ) {
			return $this->add_url_lang( $link );
		}

		function archives_link( $out ) {
			return $this->add_url_lang( $out );
		}

		function change_permalink_structure( $old_permalink_structure, $permalink_structure ) {
			$zwt_url_format = $this->ZWT_Settings->settings[ 'translation_settings' ][ 'lang_url_format' ];
			if ( $zwt_url_format ) {
				$change_notice = __( 'Zanto: Language url format has been changed to support new Permalink', 'Zanto' );
				if ( empty( $permalink_structure ) ) {
					($zwt_url_format == 2)? : ZWT_Settings::save_setting( 'settings', array( 'translation_settings' =>
						array(
							'lang_url_format' => 2
					) ) );
					add_notice( $change_notice );
				} else {
					($zwt_url_format == 1)? : ZWT_Settings::save_setting( 'settings', array( 'translation_settings' =>
						array(
							'lang_url_format' => 1
					) ) );
					add_notice( $change_notice );
				}
			}
		}

		function options_page_languages( $output, $lang_files, $current ) {
			if ( !in_array( $current, $lang_files ) ) {
				$translated = $this->get_display_language_name( $current, get_locale() );
				$output[ $translated ] = '<option value="' . esc_attr( $current ) . '"' . ' selected="true"> ' . esc_html( $translated ) . '</option>';
			}
			if ( isset( $output[ 0 ] ) ) {
				$translated = $this->get_display_language_name( 'en_US', get_locale() );
				$output[ 0 ] = '<option value="en_US"' . selected( $current, 'en_US', false ) . '> ' . esc_html( $translated ) . '</option>';
			}
			return $output;
		}

		function update_wplang( $option, $oldvalue, $_newvalue ) { // function is called before init therfoere the class is not fully initialised
			global $blog_id, $wpdb;
			if ( $option == 'WPLANG' ) {

				$wpdb->update( $wpdb->base_prefix . 'zwt_trans_network', array( 'lang_code' => $_newvalue ), array( 'blog_id' => $blog_id ), array( '%s' ), array( '%d' ) );
				$trans_net_cache = new zwt_cache( 'translation_network', true );
				$locale_cache = new zwt_cache( 'locale_code', true );
				$lang_name_cache = new zwt_cache( 'lang_name', true );
				if ( isset( $trans_net_cache ) ) {
					$transnet_blogs = $trans_net_cache->get( 'trans_network_' . $blog_id );
				} else {
					$transnet_blogs = null;
				}

				if ( !$transnet_blogs || !is_array( $transnet_blogs ) || count( $transnet_blogs, 1 ) < 2 ) {
					unset( $transnet_blogs );
					$transnet_blogs = $wpdb->get_results( $wpdb->prepare(
					"SELECT blog_id, lang_code
                    FROM {$wpdb->base_prefix}zwt_trans_network
                    WHERE trans_id =(SELECT trans_id FROM {$wpdb->base_prefix}zwt_trans_network WHERE blog_id= %d )", $blog_id ), ARRAY_A );
				}


				foreach ( $transnet_blogs as $trans_blog ) {
					switch_to_blog( $trans_blog[ 'blog_id' ] );
					$trans_net_cache->clear();
					$locale_cache->clear();
					$lang_name_cache->clear();
					delete_option( '_zwt_cache' );
					restore_current_blog();
				}
			}
		}

		function update_wplang_filter( $newvalue, $oldvalue ) {

			if ( $newvalue == '' ) {
				$newvalue = 'en_US';
			}
			if ( $newvalue !== $oldvalue ) {
				foreach ( $this->transnet_blogs as $trans_blog ) {
					if ( $newvalue == $trans_blog[ 'lang_code' ] ) {
						add_notice( __( 'The language you have chosen for the blog already exists with another blog in this translation network.', 'Zanto' ), 'error' );
						return $oldvalue;
					}
				}
			}

			return $newvalue;
		}

		function force_lang_change( $new_lang ) {
			if ( get_option( 'WPLANG' ) == $new_lang )
				return;
			global $blog_id, $wpdb, $wp_object_cache;

			$new_lang = preg_replace( '/[^a-zA-Z_\-]/', '', $new_lang );

			if ( $new_lang == '' ) {
				$new_lang = 'en_US';
			}
			$trans_net_cache = new zwt_cache( 'translation_network', true );
			if ( isset( $trans_net_cache ) ) {
				$transnet_blogs = $trans_net_cache->get( 'trans_network_' . $blog_id );
			} else {
				$transnet_blogs = null;
			}

			if ( !$transnet_blogs || !is_array( $transnet_blogs ) || count( $transnet_blogs, 1 ) < 2 ) {
				unset( $transnet_blogs );
				$transnet_blogs = $wpdb->get_results( $wpdb->prepare(
				"SELECT blog_id, lang_code
                    FROM {$wpdb->base_prefix}zwt_trans_network
                    WHERE trans_id =(SELECT trans_id FROM {$wpdb->base_prefix}zwt_trans_network WHERE blog_id= %d )", $blog_id ), ARRAY_A );
			}

			foreach ( $transnet_blogs as $trans_blog ) {
				if ( $new_lang == $trans_blog[ 'lang_code' ] ) {
					add_notice( __( 'The language you have chosen for the blog already exists with another blog in its translation network.', 'Zanto' ) );
					return false;
				}
			}

			$wpdb->query( $wpdb->prepare( "INSERT INTO `$wpdb->options` (`option_name`, `option_value`, `autoload`) VALUES (%s, %s, %s) ON DUPLICATE KEY UPDATE `option_name` = VALUES(`option_name`), `option_value` = VALUES(`option_value`), `autoload` = VALUES(`autoload`)", "WPLANG", $new_lang, "yes" ) );

			$wpdb->update(
			$wpdb->base_prefix . 'zwt_trans_network', array( 'lang_code' => $new_lang ), array( 'blog_id' => $blog_id ), array( '%s' ), array( '%d' )
			);

			foreach ( $transnet_blogs as $trans_blog ) {
				switch_to_blog( $trans_blog[ 'blog_id' ] );
				delete_option( '_zwt_cache' );
				restore_current_blog();
			}


			$alloptions = wp_load_alloptions();
			if ( is_array( $alloptions ) ) {
				if ( isset( $alloptions[ 'WPLANG' ] ) )
					unset( $alloptions[ 'WPLANG' ] );
				if ( isset( $alloptions[ '_zwt_cache' ] ) )
					unset( $alloptions[ '_zwt_cache' ] );
				wp_cache_set( 'alloptions', $alloptions, 'options' );
			}

			$changed = get_option( 'WPLANG' );
		}

		public function delete_blog( $blog_id ) {

			global $wpdb, $site_id;


			$b_switch = false;
			if ( get_current_blog_id() != $blog_id ) {
				$b_switch = true;
				switch_to_blog( $blog_id );
			}
			$transnet_id = $this->get_trans_id( true );
			$transnet_blogs = $this->get_transnet_blogs( true );
			if ( !empty( $transnet_blogs ) ) {

				if ( $wpdb->delete( $wpdb->base_prefix . 'zwt_trans_network', array( 'blog_id' => $blog_id ), array( '%d' ) ) ) {
					add_notice( __( 'A blog was deleted, Zanto Trans Network table was successfuly updated', 'Zanto' ) );
				} else { //@todo make it persistent
					wp_die( __( 'There was an error updating the Trans Network table!', 'Zanto' ) );
				}

				if ( count( $transnet_blogs ) < 2 ) {
					if ( !$wpdb->delete( $wpdb->base_prefix . 'usermeta', array( 'meta_key' => 'zwt_installed_transnetwork', 'meta_value' => $transnet_id ), array( '%s', '%d' ) ) ) {
						add_notice( __( 'There was an error deleting the zwt_trans_network value from usermeta table', 'Zanto' ), 'error' );
					}
				}

				if ( $this->get_primary_lang_blog( true ) == $blog_id ) {
					delete_metadata( 'site', $site_id, 'zwt_' . $transnet_id . '_network_vars' );
				}

				$zwt_global_cache = get_metadata( 'site', $site_id, 'zwt_' . $transnet_id . '_site_cache', true );
				if ( isset( $zwt_global_cache[ $blog_id ] ) ) {
					unset( $zwt_global_cache[ $blog_id ] );
					update_metadata( 'site', $site_id, 'zwt_' . $transnet_id . '_site_cache', $zwt_global_cache );
				}
				zwt_clean_blog_tax( $blog_id );

				if ( $b_switch )
					restore_current_blog();
				foreach ( $transnet_blogs as $trans_blog ) {
					if ( $trans_blog == $blog_id )
						continue;
					switch_to_blog( $trans_blog[ 'blog_id' ] );
					$c_trans_net_cache = new zwt_cache( 'translation_network', true );
					$c_trans_net_cache->clear();
					zwt_clean_blog_tax( $blog_id );
					restore_current_blog();
				}
			}else {
				restore_current_blog();
			}
		}

		public function doInstanceStuff() {
			// do instance stuff

			return true;
		}

		public function registerHookCallbacks() {
			if ( 'complete' == $this->ZWT_Settings->settings[ 'setup_status' ][ 'setup_wizard' ] ) {
				add_filter( 'pre_update_option_WPLANG', array( $this, 'update_wplang_filter' ), 10, 2 );
				add_filter( 'mu_dropdown_languages', array( $this, 'options_page_languages' ), 1, 3 );
				add_action( 'updated_option', array( $this, 'update_wplang' ), 10, 3 );
				add_action( 'admin_init', array( $this, 'update_global_cache' ) );
				add_filter( 'option_WPLANG', array( $this, 'filter_wplang' ) );

				if ( '0' != $this->ZWT_Settings->settings[ 'translation_settings' ][ 'lang_url_format' ] ) {
					add_filter( 'home_url', array( $this, 'mod_home_url' ), 1, 4 );
					add_filter( 'post_link', array( $this, 'permalink_filter' ), 1, 2 );
					add_filter( 'page_link', array( $this, 'permalink_filter' ), 1, 2 );
					add_filter( 'post_type_link', array( $this, 'permalink_filter' ), 1, 2 );
					if ( '1' == $this->ZWT_Settings->settings[ 'translation_settings' ][ 'lang_url_format' ] ) {
						add_filter( 'option_rewrite_rules', array( $this, 'rewrite_rules_filter' ) );
					}
					add_filter( 'term_link', array( $this, 'tax_permalink_filter' ), 1, 2 );
					add_filter( 'feed_link', array( $this, 'feed_link' ) );
					add_filter( 'trackback_url', array( $this, 'mod_trackback_url' ) );
					add_filter( 'author_link', array( $this, 'author_link' ) );
					add_filter( 'post_type_archive_link', array( $this, 'post_type_archive_link_filter' ), 10, 2 );
					add_filter( 'year_link', array( $this, 'archives_link' ) );
					add_filter( 'month_link', array( $this, 'archives_link' ) );
					add_filter( 'day_link', array( $this, 'archives_link' ) );
					if ( !is_admin() ) {
						add_filter( 'attachment_link', array( $this, 'attachment_link_filter', 10, 2 ) );
					}

					if ( version_compare( preg_replace( '#-RC[0-9]+(-[0-9]+)?$#', '', $GLOBALS[ 'wp_version' ] ), '3.1', '<' ) ) {
						add_filter( 'category_link', array( $this, 'category_permalink_filter', 1, 2 ) );
						add_filter( 'tag_link', array( $this, 'tax_permalink_filter', 1, 2 ) );
					}
					add_action( 'permalink_structure_changed', array( $this, 'change_permalink_structure' ), 10, 2 );
				}
			}
			add_action( 'init', array( $this, 'initialize_cache' ), 0 );
			add_action( 'init', array( $this, 'init' ) );
			add_action( 'delete_blog', array( $this, 'delete_blog' ) );
		}

	}

	// end ZWT_Translation_Network
}
?>