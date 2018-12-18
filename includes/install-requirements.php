<?php
function zwt_requirements_missing(){ 
	global $wp_version;
	$requirements= array('zwt_PHP_VERSION'=>1, 'zwt_WP_VERSION'=>1, 'Multisite'=>1);
	//require_once( ABSPATH .'/wp-admin/includes/plugin.php' );		// to get is_plugin_active() early
	
	if(version_compare(phpversion(), '5.2', '<'))
		$requirements['zwt_PHP_VERSION']=0;
	
	if( version_compare( $wp_version, GTP_REQUIRED_WP_VERSION, '<' ) )
		$requirements['zwt_WP_VERSION']=0;
		
	if ( !GTP_MULTISITE) 
	    $requirements['Multisite']=0;
	

	//if( !is_plugin_active( 'plugin-directory/plugin-file.php' ) )
		//return false;
	if( $requirements['zwt_PHP_VERSION']&& $requirements['zwt_WP_VERSION'] && $requirements['Multisite']==1)
	 return false; // No requirements missing
	 else
	return $requirements;
}

function zwt_deactivate_zanto(){
  
$active_plugins = get_option('active_plugins');

    $zwt_translation_index = array_search(GTP_PLUGIN_FOLDER.'/zanto.php', $active_plugins);
    if(false !== $zwt_translation_index){
        unset($active_plugins[$zwt_translation_index]);
        update_option('active_plugins', $active_plugins);
        unset($_GET['activate']);
        $recently_activated = get_option('recently_activated');
        if(!isset($recently_activated[GTP_PLUGIN_FOLDER.'/zanto.php'])){
            $recently_activated[GTP_PLUGIN_FOLDER.'/zanto.php'] = time();
            update_option('recently_activated', $recently_activated);
        }
    }


}

?>