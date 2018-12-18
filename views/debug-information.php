<div class="wrap">

	<h1><?php _e( 'Zanto WP Translation Debug', 'Zanto' ) ?></h1>

	<div class="zwt-box">
		<div>
			<h4>Please include this information when requesting support: </h4>
			<p class="submit debug-report">
				<a class="button-primary" href="admin.php?page=zwt_settings&amp;zwt_action=lightdump&amp;nonce=<?php echo wp_create_nonce( 'zwtdbdump' ) ?>">Light Dump</a>
				<a class="button-primary" href="admin.php?page=zwt_settings&amp;zwt_action=fulldump&amp;nonce=<?php echo wp_create_nonce( 'zwtdbdump' ) ?>"><?php _e( 'Full Dump', 'Zanto' ) ?></a>


			</p>
		</div>
	</div>

	<div class="zwt_dg_tables">
		<h4>System Settings </h4>
		<p>
			<textarea style="font-size: 10px; width: 100%; height:245px; background: none repeat scroll 0% 0% white;" wrap="off" rows="16" readonly="readonly">
				<?php print_r( php_server_stgs() );
				print_r( $zanto_stgs ) ?>
			</textarea>
		</p>
	</div>
</div>
<?php

function php_server_stgs() {
	global $wpdb;

	$sys_settings = array( );
	$memory = zwt_letters_to_numbers( WP_MEMORY_LIMIT );

	$active_plugins = (array) get_option( 'active_plugins', array( ) );

	if ( is_multisite() )
		$active_plugins = array_merge( $active_plugins, get_site_option( 'active_sitewide_plugins', array( ) ) );

	$all_plugins = array( );

	foreach ( $active_plugins as $plugin ) {

		$plugin_data = @get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin );
		$dirname = dirname( $plugin );
		$version_string = '';

		if ( !empty( $plugin_data[ 'Name' ] ) ) {

			// link the plugin name to the plugin url if available
			$plugin_name = $plugin_data[ 'Name' ];
			if ( !empty( $plugin_data[ 'PluginURI' ] ) ) {
				$plugin_name = '<a href="' . $plugin_data[ 'PluginURI' ] . '" title="' . __( 'Visit plugin homepage', 'Zanto' ) . '">' . $plugin_name . '</a>';
			}

			$all_plugins[ ] = $plugin_name . ' ' . __( 'by', 'Zanto' ) . ' ' . $plugin_data[ 'Author' ] . ' ' . __( 'version', 'Zanto' ) . ' ' . $plugin_data[ 'Version' ] . $version_string;
		}
	}

	$sys_settings[ 'WP_Version' ] = get_bloginfo( 'version', 'display' );
	$sys_settings[ 'PHP_Version' ] = ( function_exists( 'phpversion' ) ) ? esc_html( phpversion() ) : '';
	$sys_settings[ 'MySQL_Version' ] = $wpdb->db_version();
	$sys_settings[ 'WP_MEMORY_LIMIT' ] = size_format( $memory );
	$sys_settings[ 'PHP_Time_Limit' ] = ( function_exists( 'ini_get' ) ) ? ini_get( 'max_execution_time' ) : '';
	$sys_settings[ 'Installed_Plugins' ] = ( sizeof( $all_plugins ) == 0 ) ? '-' : $all_plugins;
	$sys_settings[ 'Server_Info' ] = esc_html( $_SERVER[ 'SERVER_SOFTWARE' ] );
	return $sys_settings;
}

function zwt_letters_to_numbers( $size ) {
	$l = substr( $size, -1 );
	$ret = substr( $size, 0, -1 );
	switch ( strtoupper( $l ) ) {
		case 'P':
			$ret *= 1024;
		case 'T':
			$ret *= 1024;
		case 'G':
			$ret *= 1024;
		case 'M':
			$ret *= 1024;
		case 'K':
			$ret *= 1024;
	}
	return $ret;
}
?>