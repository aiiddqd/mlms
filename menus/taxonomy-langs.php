<?php
if ( $_SERVER[ 'SCRIPT_FILENAME' ] == __FILE__ )
	die( 'Access denied.' );
?>
<table class="form-table">
	<tbody>
		<tr class="form-field form-required">
			<?php if ( isset( $_GET[ 'action' ] ) && $_GET[ 'action' ] == 'edit' ): ?>
				<th scope="row" valign="top"><label for="name"><?php _e( 'Translations', 'Zanto' ) ?></label></th>
			<?php else: ?>
		<label><?php _e( 'Translations', 'Zanto' ) ?></label>
	<?php endif; ?>

	<td>
		<?php
		foreach ( $transnet_blogs as $trans_blog ) {
			if ( $trans_blog[ 'blog_id' ] == $blog_id ) {
				continue;
			}
			$c_id = $trans_blog[ 'blog_id' ];
			$b_lang = $c_trans_network->get_display_language_name( $trans_blog[ 'lang_code' ], $locale );
			$blog_drop_down_terms = $terms_drop_down[ $trans_blog[ 'blog_id' ] ];
			?>
			<p>

				<img src="<?php echo GTP_PLUGIN_URL . 'images/flags/' . $trans_blog[ 'lang_code' ] . '.png'; ?>">&nbsp;
				   <?php echo $blog_drop_down_terms; ?>
				   <?php if ( $edit_tax && !$translated_terms[ $trans_blog[ 'blog_id' ] ] ) { ?>
					<a href="<?php echo add_query_arg( array( 'zwt_translate_tax' => $term_id, 'source_b' => $blog_id ), $blog_parameters[ $c_id ][ 'admin_url' ] . 'edit-tags.php?taxonomy=' . esc_html( $taxonomy_name ) . '&post_type=' . $post_type ) ?>" target="_blank"
					   title="<?php printf( __( 'Create %s Translation', 'Zanto' ), $b_lang ); ?>">  <i class="fa fa-plus-square btp-tax-icon"></i> </a>
				   <?php } elseif ( $edit_tax && $translated_terms[ $trans_blog[ 'blog_id' ] ] ) { ?>
					<a href=" <?php echo $blog_parameters[ $c_id ][ 'admin_url' ] . 'edit-tags.php?action=edit&taxonomy=' . esc_html( $taxonomy_name ) . '&tag_ID=' . $translated_terms[ $trans_blog[ 'blog_id' ] ] . '&post_type=' . $post_type ?>" target="_blank"
					   title="<?php printf( __( 'Edit %s Translation', 'Zanto' ), $b_lang ); ?>"> <i class="fa fa-check-square-o btp-tax-icon"></i></a>
	<?php } ?>
			</p>
<?php } ?>
		<p class="description"><?php _e( 'The chosen category should be the translated version of this category.', 'Zanto' ) ?></p>
	</td>
</tr>
</tbody></table>