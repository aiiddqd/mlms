<?php
/*
  Plugin Name: MLMS
  Plugin URI: https://github.com/uptimizt/mlms
  Description: Multilingual Multisite - make sites in a multisite translations of each other
  Version: 0.4
  Author: uptimizt
  Author URI: https://github.com/uptimizt
  Text Domain: Zanto
  Domain Path: /languages/
 */

if ( $_SERVER[ 'SCRIPT_FILENAME' ] == __FILE__ )
  die( 'Access denied.' );
define( 'GTP_ZANTO_VERSION', '0.3.4' );
define( 'GTP_NAME', 'Zanto Wordpress Translation Plugin' );
define( 'GTP_REQUIRED_WP_VERSION', '3.1' ); // because of esc_textarea()
define( 'GTP_PLUGIN_PATH', dirname( __FILE__ ) );
define( 'GTP_PLUGIN_FOLDER', basename( GTP_PLUGIN_PATH ) );
define( 'GTP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
(!function_exists( 'is_multisite' ) || !is_multisite()) ? define( 'GTP_MULTISITE', false ) : define( 'GTP_MULTISITE', true );

/**
 * Loads plugin translations
 */
function zanto_load_lang_files() {
  $lang_dir = GTP_PLUGIN_FOLDER . '/languages/';
  load_plugin_textdomain( 'Zanto', false, $lang_dir );
}

add_filter( 'wp_loaded', 'zanto_load_lang_files' );

/**
 * Checks if the system requirements are met
 * @author Ayebare Mucunguzi
 * @return array 0 to indicate un-met conditions.
 */
$zwt_icon_url = GTP_PLUGIN_URL . 'images/logo-admin.gif';
$zwt_menu_url = GTP_PLUGIN_URL . 'images/menu-icon.gif';
if ( is_admin() ) {
  require_once(GTP_PLUGIN_PATH . '/includes/notices/admin-notice-helper.php');
  require_once(GTP_PLUGIN_PATH . '/includes/notices/email-notifications.php');
}
require_once(GTP_PLUGIN_PATH . '/includes/install-requirements.php');
$zwt_unfullfilled_requirments = zwt_requirements_missing();


// Check if requirements are missing and load main class
// The main program needs to be in a separate file that only gets loaded if the plugin requirements are met. Otherwise older PHP installations could crash when trying to parse it.


if ( !$zwt_unfullfilled_requirments ) {
  require_once(GTP_PLUGIN_PATH . '/classes/class.zwt-module.php');
  require_once(GTP_PLUGIN_PATH . '/classes/class.zwt-base.php');
  require_once(GTP_PLUGIN_PATH . '/classes/class.zwt-lang-switcher.php');
  require_once(GTP_PLUGIN_PATH . '/classes/class.zwt-widgets.php');
  require_once(GTP_PLUGIN_PATH . '/classes/class.zwt-mo.php');
  require_once(GTP_PLUGIN_PATH . '/classes/class.zwt-download-mo.php');
  require_once(GTP_PLUGIN_PATH . '/includes/functions.php');
  require_once(GTP_PLUGIN_PATH . '/includes/template-functions.php');


  if ( class_exists( 'ZWT_Base' ) ) {

    $zwt_site_obj = ZWT_Base::getInstance();
    $zwt_language_switcher = new ZWT_Lang_Switcher();

    register_activation_hook( __FILE__, array(
      $zwt_site_obj,
      'activate'
    ) );
    register_deactivation_hook( __FILE__, array(
      $zwt_site_obj,
      'deactivate'
    ) );
  }
} else {
  add_action( 'admin_notices', 'zwt_requirements_error' );
  zwt_deactivate_zanto();
}

/**
 * Prints an error and de-activates Zanto when the system requirements aren't met.
 * @author Zanto Translate
 */
function zwt_requirements_error() {
  global $wp_version;
  require_once(GTP_PLUGIN_PATH . '/views/requirements-error.php');
}

/**
 * The main function responsible for returning Zanto WP Translation objec
 * Instance to functions everywhere.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * Example: <?php $nf = Zanto_WT(); ?>
 *
 * @since 0.3.2
 * @return object The Zanto Translation object Instance
 */
function Zanto_WT() {
  global $zwt_site_obj;
  return $zwt_site_obj;
}

?>
