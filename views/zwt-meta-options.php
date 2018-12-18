<?php zwt_noscript_notice() ?>
<?php if ( $box[ 'id' ] == ZWT_Base::PREFIX . 'choose_translation' ): ?>
	<?php if ( !isset( $primary_lang ) ): ?>
		<div class="zwt_notice"><?php _e( '<strong>Notice</strong>: Please set the Primary Translation Language of this translation network to translate.', 'Zanto' ) ?> <i class="fa fa-warning error"></i></div>
		<p><?php echo '<a class="button" href="' . admin_url( 'admin.php?page=zwt_settings' ) . '">' . __( 'Set Primary Translation Language', 'Zanto' ) . '</a>' ?></p>
		<?php return; ?>
	<?php endif ?>
	<?php if ( $c_trans_network->primary_lang_blog == $blog_id || isset( $_REQUEST[ 'make_primary' ] ) || ($translated_flag && !$primary_post_exists) ):
// start of primary language post display ?>
		<table class="widefat">

			<tbody>

				<?php
				foreach ( $transnet_blogs as $trans_blog ):
					$c_id = $trans_blog[ 'blog_id' ];
					$lang_c = $trans_blog[ 'lang_code' ];
					if ( $c_id == $blog_id ) {
						continue;
					}
					if ( defined( 'ZTM_VERSION' ) ) {
						$translation_meta = get_post_meta( $post->ID, '_translation_meta_', true );
						if ( isset( $translation_meta[ $c_id ][ 'status' ] ) && $translation_meta[ $c_id ][ 'status' ] !== 'translated' ) {
							?>
							<tr class="alternate">
								<td><b><?php echo format_code_lang( $lang_c ) ?></b></td>
								<td align="left"><span><i style="color:#AAA" class="fa fa-lock"></i> <?php _e( 'In Translation', 'Zanto' ) ?></span></td>
								<td align="right">
								</td>
							</tr>
							<?php
							continue;
						}
					}
					?>

					<tr class="alternate">
						<td>

							<b><?php echo format_code_lang( $lang_c ) ?>
							</b>
						</td>
						<td align="left">
							<select class="transln_method_mthds" autocomplete="off" name="transln_method_mthds<?php echo $c_id ?>" id="transln_method_mthds<?php echo $c_id ?>">
								<option value="1">Translate</option>
								<option value="2">Translation of</option>
								<option value="3">Custom Link</option>
							</select>
						</td>
						<td td align="right">
							<div class="transln_method" id="transln_methd_div<?php echo $c_id ?>">
								<span id="transln_method_img<?php echo $c_id ?>"><i class="fa fa-plus-square btp-post-icon"></i><a href="<?php echo add_query_arg( array( 'zwt_translate' => $post->ID, 'source_b' => $blog_id ), $blog_parameters[ $c_id ][ 'admin_url' ] . 'post-new.php' . $post_type_string ) ?>" target="_blank"> Translate</a></span>       
								<input type="text" value="" autocomplete="off" size="16"  name="transln_method_text[<?php echo $lang_c ?>]" id="transln_method_text<?php echo $c_id ?>">
								<div style="display:none" id="zwt_select_secondary_div<?php echo $c_id ?>" >
									<span class="spinner" style="display: inline; float:left"></span>
									<select class="zwt_select_secondary"
											name="zwt_select_secondary[<?php echo $lang_c ?>]"
											id="zwt_select_secondary<?php echo $c_id ?>"
									></select>
								</div>
							</div>
						</td>
					</tr>
				<?php
				endforeach;
// translations exist for post
				if ( $post_network ):
					foreach ( $post_network as $p_translns ):
						$c_bid = $p_translns[ 'blog_id' ];
						if ( isset( $p_translns[ 'post_id' ] ) )
							$c_pid = $p_translns[ 'post_id' ];
						else
							$c_link = $p_translns[ 't_link' ];
						?>
						<tr class="alternate">
							<td>
								<b><?php echo format_code_lang( $tld_lang_blog[ $c_bid ] ) ?>
								</b>
							</td>
							<td>
								<a href="<?php echo isset( $c_pid ) ? $blog_parameters[ $c_bid ][ 'site_url' ] . '?p=' . $c_pid : $c_link ?>">Visit this translation</a>
							</td>
							<td align="right">
								<?php if ( isset( $c_pid ) ): ?>
									<a href="<?php echo $blog_parameters[ $c_bid ][ 'admin_url' ] ?>post.php?post=<?php echo $c_pid ?>&action=edit" class="button button-small"> Edit</a>
								<?php endif; ?>
								<a href="<?php echo add_query_arg( array( 'zwt_remove_trans_post' => 'true', 'zwt_b_id' => $c_bid ) ); ?>" class="button button-small"> Remove</a>
							</td>
						</tr>
						<?php $c_pid = $c_link = null ?>
					<?php
					endforeach;
				endif;
// end of main language content
				?>

			</tbody>
		</table>
		<br />


		<p class="submit" style="margin:0;padding:0"><input class="button-secondary" id="zwt-update-post" type="submit" value="<?php _e( 'Apply', 'Zanto' ) ?>" /></p><br clear="all" />

		<?php // start secondary language display ?>
	<?php else: ?>
		<br/>
		<?php if ( $translated_flag && !isset( $_REQUEST[ 'change_transln' ] ) ): ?> 
			<b><?php echo format_code_lang( $primary_lang ); ?></b>&nbsp;&nbsp;<select disabled="disabled"> <?php echo $output ?></select>
			<a href="<?php echo $blog_parameters[ $primary_blog_id ][ 'admin_url' ] ?>post.php?post=<?php echo $primary_post_id ?>&action=edit" class="button button-small" >Edit</a> &nbsp; <a href="<?php echo add_query_arg( array( 'change_transln' => 'true' ) ); ?>" class="button button-small" >Change</a>
			<p> &nbsp;</p>

		<?php else: ?>
			<b><?php echo format_code_lang( $primary_lang ); ?></b>&nbsp;&nbsp;<select id="zwt_select_primary" name="zwt_select_primary[<?php echo $primary_lang ?>]" > <?php echo $output ?></select>
			<input class="button"  type="submit" value="<?php _e( 'Update', 'Zanto' ) ?>" />
			<?php if ( !isset( $_REQUEST[ 'change_transln' ] ) ): ?>
				<p>
					<a href="<?php echo add_query_arg( array( 'make_primary' => 'true' ) ); ?>"><?php _e( 'Make this a primary translation post', 'Zanto' ) ?></a>
			<?php else: ?>
					&nbsp; <a href="<?php echo remove_query_arg( array( 'change_transln' ) ); ?>">Cancel</a>
				<p>
					&nbsp;
			<?php endif; ?>
			</p>

		<?php endif; ?>

	<?php endif; ?>

<?php endif; ?>


