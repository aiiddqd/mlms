<?php

if ( $_SERVER[ 'SCRIPT_FILENAME' ] == __FILE__ )
	die( 'Access denied.' );

class ZWT_Download_MO {
	const CONTEXT = 'WordPress';
	private $settings;
	private $WordPress_language;

	function __construct() {
		global $wp_version, $zwt_site_obj, $site_id;
		$wpversion = preg_replace( '#-(.+)$#', '', $wp_version );

		$fh = fopen( GTP_PLUGIN_PATH . '/includes/lang-map.csv', 'r' );
		while ( list($locale, $code) = fgetcsv( $fh ) ) {
			$this->lang_map[ $locale ] = $code;
		}
		$this->lang_map_rev = & array_flip( $this->lang_map );


		$this->settings = get_metadata( 'site', $site_id, 'zwt_locale_settings', $single = true );
		$this->WordPress_language = ZWT_MO::getSingleton();

		$languages = get_available_languages();
		$current_locale = get_locale();
		if ( $current_locale !== 'en_US' ) {
			if ( !empty( $languages ) ) {
				if ( !in_array( $current_locale, $languages ) ) {
					$this->get_translations( $current_locale );
				}
			} else {

				$this->get_translations( $current_locale );
			}
		}
		if ( empty( $this->settings[ 'wp_version' ] ) || version_compare( $wp_version, $this->settings[ 'wp_version' ], '>' ) ) {

			$this->update( $current_locale );
		}

		$this->registerHookCallbacks();
	}

	function update( $locale ) {
		global $wp_version, $zwt_site_obj;
		$wpversion = preg_replace( '#-(.+)$#', '', $wp_version );

		//$this->get_translations($locale);
		$this->settings[ 'wp_version' ] = $wpversion;
		$this->settings[ 'last_time_download_mo' ] = time();
		$this->save_settings();

		return;
	}

	function get_locales( $locale ) {
		$lang_code = strtolower( substr( $locale, 0, 2 ) );
		if ( in_array( $lang_code, array( 'es', 'en', 'pt', 'cn' ) ) ) {
			$locales = array( );
			$locale_xml = new SimpleXMLElement( GTP_PLUGIN_PATH . '/includes/wp-multi-locales.xml', NULL, true );
			$locales_result = $locale_xml->xpath( $lang_code );
			if ( $locales_result ) {
				foreach ( $locales_result[ 0 ] as $locale => $data ) {
					$locales[ ] = $this->lang_map_rev[ $locale ];
				}
			}
		} else {
			$locales[ ] = $locale;
		}
		return $locales;
	}

	function save_settings() {
		global $site_id;
		update_metadata( 'site', $site_id, 'zwt_locale_settings', $this->settings );
	}

	function get_option( $name ) {
		return isset( $this->settings[ $name ] ) ? $this->settings[ $name ] : null;
	}

	function get_translations( $language ) {
		global $wp_version;
		require_once(ABSPATH . "wp-admin" . '/includes/file.php');
		$decimal = explode( ".", $wp_version );
		$root_tagged_version = $decimal[ 0 ] . '.' . $decimal[ 1 ];
		$tagged_version = $root_tagged_version;
		if ( isset( $decimal[ 2 ] ) )
			$tagged_version .= '.' . $decimal[ 2 ];


		//check for internet connectivity
		$i_connectivity = check_internet_connection();
		if ( !$i_connectivity ) {
			add_notice( __( '<strong>Notice: </strong>Please connect to the Internet for the language files to be downloaded', 'Zanto' ) . ' &nbsp;<i class="fa fa-2x fa-cloud-download error"></i>' );
			return false;
		}

		//start Download
		$tmp = download_url( "http://svn.automattic.com/wordpress-i18n/" . $language . "/tags/" . $tagged_version . "/messages/" . $language . ".mo" );

		if ( is_wp_error( $tmp ) ) {

			if ( $tmp->get_error_code() == 'http_404' ) { // try to get .mo files of a lower version
				if ( isset( $decimal[ 2 ] ) && $decimal[ 2 ] > 1 ) {
					$x = 1;
					while ( is_wp_error( $tmp ) && ($decimal[ 2 ] - $x) > 0 ) {
						$tagged_version = $root_tagged_version . '.' . ($decimal[ 2 ] - $x);
						$tmp = download_url( "http://svn.automattic.com/wordpress-i18n/" . $language . "/tags/" . $tagged_version . "/messages/" . $language . ".mo" );
						$x++;
					}
				}
				if ( $tmp->get_error_code() == 'http_404' ) { // try to get .mo files from the root version up to 3 root versions below
					$y = 0;
					while ( is_wp_error( $tmp ) && ($decimal[ 1 ] - $y) > 0 && $y < 3 ) {
						$root_tagged_version = $decimal[ 0 ] . '.' . ($decimal[ 1 ] - $y);
						$tmp = download_url( "http://svn.automattic.com/wordpress-i18n/" . $language . "/tags/" . $root_tagged_version . "/messages/" . $language . ".mo" );
						$y++;
					}
				}
			}

			if ( is_wp_error( $tmp ) ) {
				if ( $tmp->get_error_code() == 'http_404' ) {
					add_notice( __( 'No recent translations for the chosen language were found. Try manually downloading the older versions at http://svn.automattic.com/wordpress-i18n/.', 'Zanto' ) );
				} else {
					echo
					add_notice( $tmp->get_error_code() . ': ' . $tmp->get_error_message() );
				}
				@unlink( $file_array[ 'tmp_name' ] );
				return false;
			}
		}
		// override filename, reconstruct server path

		$filename = $language;
		$tmppath = pathinfo( $tmp );
		$new = $tmppath[ 'dirname' ] . "/" . $filename . "." . $tmppath[ 'extension' ];
		rename( $tmp, $new );
		$tmp = $new;
		//basename($url );

		$file_array = array(
			'name' => $language . '.mo',
			'tmp_name' => $tmp
		);

		$_POST[ 'action' ] = 'wp_handle_sideload';
		add_filter( 'upload_dir', array( $this, 'mo_upload_dir' ) );
		$id = wp_handle_sideload( $file_array, 0 );
		remove_filter( 'upload_dir', array( $this, 'mo_upload_dir' ) );

		// Check for handle sideload errors.
		if ( is_wp_error( $id ) ) {
			@unlink( $file_array[ 'tmp_name' ] );
			add_notice( $id->get_error_code() . ': ' . $id->get_error_message(), 'error', 'error' );
			return false;
		}

		return true;
	}

	function mo_upload_dir( $upload ) {
		// use wp-content/languages 
		$upload[ 'path' ] = WP_CONTENT_DIR . '/languages';
		$upload[ 'url' ] = content_url() . '/languages';
		return $upload;
	}

	function registerHookCallbacks() {
		
	}

}

?>