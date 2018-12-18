<?php
if ( $_SERVER[ 'SCRIPT_FILENAME' ] == __FILE__ )
	die( 'Access denied.' );

global $zwt_icon_url;
?>
<div class="wrap">
	<div class="icon32" style='background:url("<?php echo $zwt_icon_url; ?>") no-repeat;'><br /></div>
	<h2><?php echo __( 'Zanto Advanced Tools', 'Zanto' ); ?></h2>
	<p><?php _e( 'This page allows operations that are helpfull in troubleshooting Zanto and Automating certain tasks', 'Zanto' ) ?></p>

	<?php do_action( 'pre_zwt_advanced_tools' ); ?>	
	<div class="zwt_advanced_box">
		<h3><?php _e( 'Clear Network Cache', 'Zanto' ) ?></h3>
		<div class= "zwt_advanced_box_inner">
			<label><input type="radio" name="zwt_clear_cache" value="1" checked="checked"> <?php _e( 'Translation Network', 'Zanto' ) ?></label>&nbsp;&nbsp;

			<label><input type="radio" name="zwt_clear_cache" value="2"> <?php _e( 'Languages and Locales', 'Zanto' ) ?> </label>&nbsp;&nbsp;

			<label><input type="radio" name="zwt_clear_cache" value="3"> <?php _e( 'All plugin cache', 'Zanto' ) ?></label>&nbsp;&nbsp;

			<p><input type="button" name="zwt_reset_cache" id="zwt_reset_cache" class="button button-primary" value="Clear cache"/></p>
		</div>	
	</div>

	</br>

	<div class="zwt_advanced_box">
		<h3><?php _e( 'Copy Taxonomy Translations', 'Zanto' ) ?></h3>
		<div class= "zwt_advanced_box_inner">

			<label> <?php _e( 'Choose blog', 'Zanto' ) ?><br/>
				<select class= "zwt_input_class" name="zwt_from_blog" id = 'zwt_from_blog'>
					<?php
					foreach ( $trans_network->transnet_blogs as $trans_blog ):
						if ( $blog_id == $trans_blog[ 'blog_id' ] )
							continue;
						$c_blog_details = get_blog_details( $trans_blog[ 'blog_id' ] );
						?>
						<option value = <?php echo $c_blog_details->blog_id ?>><?php echo $c_blog_details->blogname ?></option>
<?php endforeach; ?>
				</select></label>

			<p><label> <?php _e( 'Select Taxonomy', 'Zanto' ) ?><br/>
					<select name="zwt_taxonomy_name" class = "zwt_input_class" id='zwt_taxonomy_name'>
						<?php foreach ( $taxonomies as $taxonomy ): ?> 
							<option> <?php echo $taxonomy ?></option>
<?php endforeach; ?>
					</select></label></p>

			<p class="description"><?php _e( 'show_ui and public parameters should be true for a taxonomy to be shown in this list', 'Zanto' ) ?></p>
			<p><input type="button" name="zwt_copy_taxonomy" id="zwt_copy_taxonomy" class="button button-primary" value="Import"/></p>
		</div>
	</div>
	<br/>

	<div class="zwt_advanced_box">
		<h3><?php _e( 'reset Zanto settings', 'Zanto' ) ?></h3>
		<div class= "zwt_advanced_box_inner">
			<label><input type="checkbox" autocomplete="off" name="zwt_reset_settings" id="zwt_reset_settings" value="pages"> <?php _e( 'reset Zanto settings for this blog. Translations are not affected', 'Zanto' ) ?></label>
			<p><input type="button" name="zwt_reset_zanto" id="zwt_reset_zanto" class="button button-primary" value="reset"/>&nbsp;</p>
<?php $ajax_nonce = wp_create_nonce( "zwt-advanced-tools" ); ?>
			<input type="hidden" value="<?php echo $ajax_nonce ?>" name="_wpnonce" id="zwt_advanced_tools">
			<script type="text/javascript">
				var zwt_pluginUrl = '<?php echo GTP_PLUGIN_URL; ?>'
			</script>
		</div>	
	</div>
<?php do_action( 'zwt_advanced_tools' ); ?>
<?php do_action( 'zwt_menu_footer' ); ?>
</div>
