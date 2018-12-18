<?php
/*
  Theme Name: ls 2015 v1
  Theme URI: http://www.zanto.org
  Description: Default language switcher for Zanto
  Version: 0.1
  Author: Ayebare Mucunguzi
  Theme URI: http://www.zanto.org
  License: GNU General Public License v2.0
  License URI: http://www.gnu.org/licenses/gpl-2.0.html

 */

/**
 * This is the most generic theme file for Zanto Language switchers *
 * To use your own modified version of language switchers,
 * create a directory folder named zanto in your theme base directory or child theme directory, create a file e.g my_switchers.zwt.php,
 * and place it in the directory you created. then copy this file content and paste it in my_switchers.zwt.php, you
 * can modify it as you want :). Go to Language Switcher settings in the admin panel, a new switcher theme will be available
 * @package Zanto Wordpress Translation
 */
$ls_types = array(
	'drop_down' => __( 'drop down menu ', 'Zanto' ),
	'horizontal' => __( 'horizontal list ', 'Zanto' ),
);

zwt_register_switcher_types( $ls_types );

function zwt_lang_switcher_fn( $ls_type ) {
	global $show_flag, $show_native_name, $show_translated_name;
	$languages = zwt_get_languages( 'skip_missing=0' );

	if ( !empty( $languages ) ) {
		foreach ( $languages as $lang_details ) {
			if ( $lang_details[ 'active' ] === 1 )
				$active_lang = $lang_details;
		}
		?>

		<?php if ( $ls_type == 'drop_down' ) { ?>
			<div class="lang_switcher">
				<ul  class="zwt_dd_switcher">
					<li class="zwt-dropdown">
						<a class="zwt-has-dropdown" href="#"><?php echo($show_flag) ? '<img class="drop-arrow" src="' . $active_lang[ "country_flag_url" ] . '"/>' : ''; ?> <span><?php echo $active_lang[ 'translated_name' ] ?></span></a>

						<ul>
							<?php
							foreach ( $languages as $lang ):
								if ( $lang[ 'active' ] === 1 )
									continue;
								$lang_native = ($show_native_name) ? $lang[ 'native_name' ] : false;
								$lang_translated = ($show_translated_name) ? $lang[ 'translated_name' ] : false;
								?>
								<li><a rel="alternate"  hreflang="<?php echo $lang[ 'language_code' ] ?>"  href="<?php echo $lang[ 'url' ] ?>">
										<?php echo($show_flag) ? '<img src="' . $lang[ "country_flag_url" ] . ' "/>' : ''; ?>
										<?php echo zwt_disp_language( $lang_native, $lang_translated ); ?>
									</a></li>
							<?php endforeach; ?>
						</ul>

					</li>
				</ul>
			</div>

		<?php } else { ?>
			<div class="zwt_horizontal">

				<?php foreach ( $languages as $lang ): ?>

					<?php $lang_native = ($show_native_name) ? $lang[ 'native_name' ] : false; ?>
					<?php $lang_translated = ($show_translated_name) ? $lang[ 'translated_name' ] : false; ?>
					<?php $flag = ($show_flag) ? '<img src ="' . $lang[ "country_flag_url" ] . '"/>' : ''; ?>

					<span>
						<a rel="alternate" hreflang="<?php echo $lang[ 'language_code' ] ?>" href="<?php echo $lang[ 'url' ] ?>">
							<?php echo $flag ?>
							<?php echo zwt_disp_language( $lang_native, $lang_translated ); ?>
						</a>
					</span>
				<?php endforeach; ?>                      			

			</div>

		<?php
		}
	}
}

function zwt_lang_switcher_css() {
	wp_enqueue_style( 'native_lang_select', GTP_PLUGIN_URL . 'css/ls_2015_v1.css', array( ), GTP_ZANTO_VERSION, 'all' );
}

add_action( 'zwt_lang_switcher', 'zwt_lang_switcher_css' );
add_action( 'zwt_lang_switcher', 'zwt_lang_switcher_fn' );
?>