<?php
if ( $_SERVER[ 'SCRIPT_FILENAME' ] == __FILE__ )
	die( 'Access denied.' );

class ZWT_MO {

	private static $instance;

	function __construct() {

		$this->language_switched = false;
		$this->download_lang = false;
		$this->settings = get_option( 'wp_languages_options' );
		$this->current_scope = 'back-end';

		$this->lang_data = array( );
		$this->locale_data = array( );
		$this->registerHookCallbacks();

		require_once(GTP_PLUGIN_PATH . '/includes/language-data.php');

		foreach ( $langs_names as $key => $val ) {
			if ( strpos( $key, 'Norwegian Bokm' ) === 0 ) {
				$key = 'Norwegian Bokmål';
				$lang_codes[ $key ] = 'nb';
			} // exception for norwegian
			if ( strpos( $key, 'Portuguese, Portugal' ) === 0 ) {
				$key = 'Portuguese';
				$lang_codes[ $key ] = 'pt';
			}
			if ( strpos( $key, 'Portuguese, Brazil' ) === 0 ) {
				continue;
			}
			$default_locale = isset( $lang_locales[ $lang_codes[ $key ] ] ) ? $lang_locales[ $lang_codes[ $key ] ] : $lang_codes[ $key ];

			$this->lang_data[ $lang_codes[ $key ] ] = array( 'english_name' => $key, 'code' => $lang_codes[ $key ], 'major' => $val[ 'major' ], 'active' => 0, 'default_locale' => $default_locale, 'locales' => array( $default_locale ) );

			$this->locale_data[ $default_locale ] = $lang_codes[ $key ];
		}

		$this->lang_translations = array( );

		foreach ( $langs_names as $lang => $val ) {
			if ( strpos( $lang, 'Norwegian Bokm' ) === 0 ) {
				$lang = 'Norwegian Bokmål';
				$lang_codes[ $lang ] = 'nb';
			}
			if ( strpos( $lang, 'Portuguese, Portugal' ) === 0 ) {
				$lang = 'Portuguese';
				$lang_codes[ $lang ] = 'pt';
			}
			if ( strpos( $lang, 'Portuguese, Brazil' ) === 0 ) {
				continue;
			}

			$this->lang_translations[ $lang_codes[ $lang ] ] = array( );
			foreach ( $val[ 'tr' ] as $k => $display ) {
				if ( strpos( $k, 'Norwegian Bokm' ) === 0 ) {
					$k = 'Norwegian Bokmål';
				}
				if ( strpos( $k, 'Portuguese, Portugal' ) === 0 ) {
					$k = 'Portuguese';
				}
				if ( strpos( $k, 'Portuguese, Brazil' ) === 0 ) {
					continue;
				}
				if ( !trim( $display ) ) {
					$display = $lang;
				}

				$this->lang_translations[ $lang_codes[ $lang ] ][ $lang_codes[ $k ] ] = $display;
			}
		}
	}

	function registerHookCallbacks() {

		if ( is_admin() ) {
			add_action( 'plugins_loaded', array( $this, 'save_selected_locale' ) );
			add_action( 'admin_footer', array( $this, 'render_lang_switch_popup' ) );


			if ( isset( $_GET[ 'scope' ] ) && $_GET[ 'scope' ] == 'front-end' ) {
				$this->current_scope = 'front-end';
			}
		}
	}

	public static function getSingleton() {
		if ( !isset( self::$instance ) ) {
			$class = __CLASS__;
			self::$instance = new $class;
		}

		return self::$instance;
	}

	function get_locale( $lang_code ) {
		global $zwt_site_obj;
		if ( isset( $this->lang_data[ $lang_code ][ 'default_locale' ] ) ) {
			return $this->lang_data[ $lang_code ][ 'default_locale' ];
		} else {
			return $zwt_site_obj->modules[ 'trans_network' ]->get_locale_from_code( $lang_code );
		}
	}

	function get_languages() {
		return $this->lang_data;
	}

	function save_selected_locale() {
		global $zwt_site_obj;
		if ( isset( $_POST[ 'interface_1_mo' ] ) && wp_verify_nonce( $_POST[ 'zwt_mo_interface_1' ], 'zwt_mo_actions_nonce_1' ) ) {
			static $save_started = false;

			if ( !$save_started ) {

				$save_started = true;
				$change_lang = !empty( $_POST[ 'zwt_switch_lang' ] ) ? true : false;
				$download_mo = !empty( $_POST[ 'zwt_download_mo' ] ) ? true : false;

				if ( isset( $_POST[ 'switch_to_locale' ] ) ) {
					$new_lang = $_POST[ 'switch_to_locale' ];
				} elseif ( isset( $_POST[ 'wp_lang_locale' ] ) && is_array( $_POST[ 'wp_lang_locale' ] ) ) {
					$new_lang = $_POST[ 'wp_lang_locale' ][ 0 ];
				} else {
					$new_lang = null;
				}

				$installed_lang_array = get_available_languages();

				if ( !is_null( $new_lang ) && $download_mo ) {

					if ( !in_array( $new_lang, $installed_lang_array ) && $new_lang !== 'en_US' ) {

						$installing_translations = true;

						if ( $this->download_mo( $new_lang ) )
							$this->download_lang = $new_lang;
					}
				}

				if ( !is_null( $new_lang ) && isset( $_GET[ 'scope' ] ) && $change_lang ) {
					if ( isset( $_GET[ 'scope' ] ) && $_GET[ 'scope' ] == 'front-end' ) {
						if ( in_array( $new_lang, $installed_lang_array ) || $this->download_lang == $new_lang ) {
							update_option( 'WPLANG', $new_lang );
						} else {
							$zwt_site_obj->modules[ 'trans_network' ]->force_lang_change( $new_lang );
						}
					} else {

						$user_id = get_current_user_id();
						update_user_meta( $user_id, 'zwt_adminlang_lang', $new_lang );
					}
					$this->language_switched = true;
				}
			}
		}
	}

	function download_mo( $lang=null ) {

		if ( $lang == null ) {
			if ( isset( $_REQUEST[ 'scope' ] ) && $_REQUEST[ 'scope' ] == 'front-end' ) {
				$lang = get_option( 'WPLANG' );
			} else {
				$lang = get_locale();
			}
		}
		$ZWT_Download_MO = new ZWT_Download_MO();

		if ( $lang == 'en_US' ) {
			return 'success';
		}

		$translations = $ZWT_Download_MO->get_translations( $lang );

		if ( $translations !== false ) {

			return 'success';
		} else {
			return false;
		}
	}

	function downloading_div() {
		global $zwt_site_obj;
		if ( $this->current_scope == 'front-end' ) {
			$current_locale = get_option( 'WPLANG' );
		} else {
			$current_locale = get_locale();
		}


		if ( $current_locale == 'en_US' )
			return;

		$current_lang_code = $zwt_site_obj->modules[ 'trans_network' ]->get_lang_code( $current_locale );
		$current_lang = $zwt_site_obj->modules[ 'trans_network' ]->get_display_language_name( $current_lang_code );
		?>
		<div id="wp_language_downloading">
			<strong>
		<?php echo sprintf( __( 'Downloading and installing %s translations', 'wordpress-language' ), $current_lang . ' (' . $current_locale . ')' ); ?>
			</strong>
			<img src="<?php echo GTP_PLUGIN_URL . 'images/spin-big.gif' ?>" style="border: 2px solid rgb(221, 221, 221)/>  

				 </div>
		<?php
	}

	function download_complete_div( $status ) {
		global $zwt_site_obj;
		$download_locale = $this->download_lang;
		$downloaded_lang = $zwt_site_obj->modules[ 'trans_network' ]->get_display_language_name( $download_locale );
		if ( $status ) {
			?>

					 <div id="wp_language_download_complete" >
					 <strong>
			<?php echo sprintf( __( 'Translations for %s installed.', 'wordpress-language' ), $downloaded_lang . ' (' . $download_locale . ')' ); ?>
				</strong>
				<i class="fa-check fa-2x fa zwt-icon-green"></i>
			</div>
			<br />

			<?php
		} else {
			?>
			<div id="wp_language_no_translation_available">
				<strong>
			<?php echo sprintf( __( 'There is no translation available for %s.', 'wordpress-language' ), $downloaded_lang . ' (' . $download_locale . ')' ); ?>
				</strong>
			</div>
			<br />

			<?php
		}
	}

	function render_lang_switch_popup() {
		add_thickbox();
		echo '<div id="wp_lang_switch_popup" style="display:none;"><div id="wp_lang_switch_form" style="padding:20px;">';
		echo '<strong>' . __( 'Fetching language information ...', 'wordpress-language' ) . '</strong> <img src="' . GTP_PLUGIN_URL . 'images/spin-big.gif" syle="border: 2px solid rgb(221, 221, 221);">';
		echo '</div>';
		wp_nonce_field( 'wp_lang_get_lang_info', 'wp_lang_get_lang_info' );
		echo '</div>';
	}

	function switch_language( $lang_code ) {
		$default_locale = $this->languages->get_locale( $lang_code );
		update_option( 'wp_language_locale', $default_locale );
	}

	function switch_locale( $locale ) {
		update_option( 'wp_language_locale', $locale );
	}

}
