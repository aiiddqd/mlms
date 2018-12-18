<?php

class ZWT_Browser_Lang_Redirect {

	static function init() {
		if ( !is_admin() && !isset( $_GET[ 'redirect_to' ] ) && !preg_match( '#wp-login\.php$#', preg_replace( "@\?(.*)$@", '', $_SERVER[ 'REQUEST_URI' ] ) ) ) {
			add_action( 'wp_print_scripts', array( 'ZWT_Browser_Lang_Redirect', 'scripts' ) );
		}
	}

	static function scripts() {
		global $zwt_language_switcher, $zwt_site_obj;


		if ( $zwt_site_obj->modules[ 'settings' ]->settings[ 'blog_setup' ][ 'browser_lang_redirect' ] ) {
			wp_enqueue_script( 'zwt_jquery_cookie' );
			wp_enqueue_script( 'zwt_browser_lang_redirect' );
		}

		$args[ 'skip_missing' ] = intval( $zwt_site_obj->modules[ 'settings' ]->settings[ 'blog_setup' ][ 'browser_lang_redirect' ] == 2 );

		// Build multi language urls array
		$languages = $zwt_language_switcher->get_current_ls( $args );
		$language_urls = array( );
		foreach ( $languages as $locale => $language ) {
			$language_urls[ $locale ] = $language[ 'url' ];
		}
		// Cookie parameters
		$http_host = $_SERVER[ 'HTTP_HOST' ] == 'localhost' ? '' : $_SERVER[ 'HTTP_HOST' ];
		$cookie = array(
			'name' => '_zwt_visitor_lang_js',
			'domain' => (defined( 'COOKIE_DOMAIN' ) && COOKIE_DOMAIN ? COOKIE_DOMAIN : $http_host),
			'path' => (defined( 'COOKIEPATH' ) && COOKIEPATH ? COOKIEPATH : '/'),
			'expiration' => $zwt_site_obj->modules[ 'settings' ]->settings[ 'blog_setup' ][ 'browser_lr_time' ]
		);

		// Send params to javascript
		$params = array(
			'ajaxurl' => admin_url( 'admin-ajax.php', 'relative' ),
			'pageLanguage' => defined( 'GTP_LANGUAGE_CODE' ) ? GTP_LANGUAGE_CODE : get_option( 'WPLANG' ),
			'languageUrls' => $language_urls,
			'cookie' => $cookie
		);
		wp_localize_script( 'zwt_browser_lang_redirect', 'ZWT_Browser_Lang_Redirect_params', $params );
	}

}

add_action( 'init', array( 'ZWT_Browser_Lang_Redirect', 'init' ) );
?>
