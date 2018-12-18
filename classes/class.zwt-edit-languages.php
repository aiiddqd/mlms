<?php

class ZWT_Edit_Languages {

	var $current_languages;
	var $is_writable = false;
	var $required_fields = array( 'code' => '', 'english_name' => '', 'translations' => 'array', 'default_locale' => '' );
	var $add_validation_failed = false;
	var $active_languages = array( );
	var $custom_languages = array( );
	private $error = '';
	private $message = '';

	function __construct() {

		$this->get_current_languages();

		if ( isset( $_GET[ 'action' ] ) && $_GET[ 'action' ] == 'delete-language' && wp_create_nonce( 'delete-language' . @intval( $_GET[ 'id' ] ) ) == $_GET[ 'zwt_nonce' ] ) {
			$lang_id = @intval( $_GET[ 'id' ] );
			$this->delete_language( $lang_id );
		}



		// Trigger save.
		if ( isset( $_POST[ 'zwt_edit_languages_action' ] ) && $_POST[ 'zwt_edit_languages_action' ] == 'update' ) {
			if ( wp_verify_nonce( $_POST[ '_wpnonce' ], 'zwt_edit_languages' ) ) {
				$this->update();
			}
		}

		add_action( 'admin_footer', array( &$this, 'scripts' ) );

		global $zwt_icon_url;
		?>

		<div class="wrap">

			<div class="icon32" style='background:url("<?php echo $zwt_icon_url; ?>") no-repeat;'><br /></div>
			<h2><?php echo __( 'Edit Languages', 'Zanto' ); ?></h2>

			<br />

			<div id="zwt_edit_languages_info">
				<?php _e( 'For each new language, you need to enter the following information:
<ul>
    <li><b>Code:</b> Language code. Notice locales en_US, en_AU, en_GB all share the same code "en".</li>
    <li><strong>Translations:</strong> The way the language name will be displayed in different languages.</li>
    <li><strong>Locale:</strong> This determines the locale value for this language.</li>
</ul>', 'Zanto' ); ?>

			</div>
			<?php
			if ( $this->error ) {
				echo '	<div class="below-h2 error"><p>' . $this->error . '</p></div>';
			}

			if ( $this->message ) {
				echo '    <div class="below-h2 updated"><p>' . $this->message . '</p></div>';
			}
			?>
			<br />
			<?php $this->edit_table(); ?>
			<div class="zwt_error_text zwt_edit_languages_show" style="display: none; margin:10px;"><p><?php _e( 'Please note: language codes cannot be changed after adding languages. Make sure you enter the correct code.', 'Zanto' ); ?></p></div>

			<?php do_action( 'zwt_menu_footer' ); ?>
		</div>
		<?php
	}

	function edit_table() {
		?>
		<form enctype="multipart/form-data" action="<?php echo admin_url( 'admin.php?page=zwt_manage_locales&edit_langs=1' ) ?>" method="post" id="zwt_edit_languages_form">
			<input type="hidden" name="zwt_edit_languages_action" value="update" />
			<input type="hidden" name="zwt_edit_languages_ignore_add" id="zwt_edit_languages_ignore_add" value="<?php echo ($this->add_validation_failed) ? 'false' : 'true'; ?>" />
			<?php wp_nonce_field( 'zwt_edit_languages' ); ?>
			<table id="zwt_edit_languages_table" class="widefat" cellspacing="0">
				<thead style="background-color:#f5f5f5; border:1px solid #ddd">
					<tr>
						<th><?php _e( 'Language name', 'Zanto' ); ?></th>
						<th><?php _e( 'Locale', 'Zanto' ); ?></th>
						<th><?php _e( 'Code', 'Zanto' ); ?></th>
						<th <?php if ( !$this->add_validation_failed )
			echo 'style="display:none;" '; ?>class="zwt_edit_languages_show"><?php _e( 'Translation (new)', 'Zanto' ); ?></th>
							<?php foreach ( $this->current_languages as $lang ) { ?>
							<th><?php _e( 'Translation', 'Zanto' ); ?> (<?php echo $lang[ 'english_name' ]; ?>)</th>
						<?php } ?>

						<th>&nbsp;</th>
					</tr>
				</thead>
				<tfoot style="background-color:#f5f5f5; border:1px solid #ddd">
					<tr>
						<th><?php _e( 'Language name', 'Zanto' ); ?></th>
						<th><?php _e( 'Locale', 'Zanto' ); ?></th>
						<th><?php _e( 'Code', 'Zanto' ); ?></th>
						<th <?php if ( !$this->add_validation_failed )
					echo 'style="display:none;" '; ?>class="zwt_edit_languages_show"><?php _e( 'Translation (new)', 'Zanto' ); ?></th>
							<?php foreach ( $this->current_languages as $lang ) { ?>
							<th><?php _e( 'Translation', 'Zanto' ); ?> (<?php echo $lang[ 'english_name' ]; ?>)</th>
						<?php } ?>

						<th>&nbsp;</th>
					</tr>
				</tfoot>        
				<tbody>
					<?php
					foreach ( $this->current_languages as $lang ) {
						$this->table_row( $lang );
					}
					if ( $this->add_validation_failed ) {
						$_POST[ 'zwt_edit_languages' ][ 'add' ][ 'id' ] = 'add';
						$new_lang = $_POST[ 'zwt_edit_languages' ][ 'add' ];
					} else {
						$new_lang = array( 'id' => 'add' );
					}
					$this->table_row( $new_lang, true, true );
					?>
				</tbody>
			</table>
			<p class="submit alignleft"><a href="<?php echo admin_url( 'admin.php?page=zwt_manage_locales&scope=front-end' ); ?>">&laquo;&nbsp;<?php _e( 'Back to languages', 'Zanto' ); ?></a></p>

			<p class="submit alignright">
				<input type="button" name="zwt_edit_languages_add_language_button" id="zwt_edit_languages_add_language_button" value="<?php _e( 'Add Language', 'Zanto' ); ?>" class="button-secondary"<?php if ( $this->add_validation_failed ) { ?> style="display:none;"<?php } ?> />&nbsp;<input type="button" name="zwt_edit_languages_cancel_button" id="zwt_edit_languages_cancel_button" value="<?php _e( 'Cancel', 'Zanto' ); ?>" class="button-secondary zwt_edit_languages_show"<?php if ( !$this->add_validation_failed ) { ?> style="display:none;"<?php } ?> />&nbsp;<input disabled type="submit" class="button-primary" value="<?php _e( 'Save', 'Zanto' ); ?>" /></p>
			<br clear="all" />
		</form>

		<p>
			<?php wp_nonce_field( 'reset_languages_nonce', '_zwt_nonce_rl' ); ?>
			<input class="button-primary" type="button" id="zwt_reset_languages" value="<?php _e( 'Reset languages', 'Zanto' ); ?>" />        
			<span class="hidden"><?php _e( 'Zanto will reset all language information to its default values. Any languages that you added or edited will be lost.', 'Zanto' ) ?></span>
		</p>

		<?php
	}

	function table_row( $lang, $echo = true, $add = false ) {
		if ( $lang[ 'id' ] == 'add' ) {
			$lang[ 'english_name' ] = isset( $_POST[ 'zwt_edit_languages' ][ 'add' ][ 'english_name' ] ) ? $_POST[ 'zwt_edit_languages' ][ 'add' ][ 'english_name' ] : '';
			$lang[ 'code' ] = isset( $_POST[ 'zwt_edit_languages' ][ 'add' ][ 'code' ] ) ? $_POST[ 'zwt_edit_languages' ][ 'add' ][ 'code' ] : '';
			$lang[ 'default_locale' ] = isset( $_POST[ 'zwt_edit_languages' ][ 'add' ][ 'default_locale' ] ) ? $_POST[ 'zwt_edit_languages' ][ 'add' ][ 'default_locale' ] : '';
		}
		?>

		<tr style="<?php
		if ( $add && !$this->add_validation_failed )
			echo 'display:none; '; if ( $add )
			echo 'background-color:#fffbcc; ';
		?>"<?php if ( $add )
			echo ' class="zwt_edit_languages_show"'; ?>>

			<td><input type="text" name="zwt_edit_languages[<?php echo $lang[ 'id' ]; ?>][english_name]" value="<?php echo $lang[ 'english_name' ]; ?>"<?php if ( !$add ) { ?> readonly="readonly"<?php } ?> /></td>
			<td><input type="text" name="zwt_edit_languages[<?php echo $lang[ 'id' ]; ?>][default_locale]" value="<?php echo $lang[ 'default_locale' ]; ?>" style="width:60px;" <?php if ( !$add ) { ?> readonly="readonly"<?php } ?> /></td>

			<td><input type="text" name="zwt_edit_languages[<?php echo $lang[ 'id' ]; ?>][code]" value="<?php echo $lang[ 'code' ]; ?>" style="width:30px;" /></td>

			<td <?php if ( !$this->add_validation_failed )
			echo 'style="display:none;" '; ?>class="zwt_edit_languages_show"><input type="text" name="zwt_edit_languages[<?php echo $lang[ 'id' ]; ?>][translations][add]" value="<?php echo isset( $_POST[ 'zwt_edit_languages' ][ $lang[ 'id' ] ][ 'translations' ][ 'add' ] ) ? $_POST[ 'zwt_edit_languages' ][ $lang[ 'id' ] ][ 'translations' ][ 'add' ] : ''; ?>" /></td>
				<?php
				foreach ( $this->current_languages as $translation ) {
					if ( $lang[ 'id' ] == 'add' ) {
						$value = isset( $_POST[ 'zwt_edit_languages' ][ 'add' ][ 'translations' ][ $translation[ 'default_locale' ] ] ) ? $_POST[ 'zwt_edit_languages' ][ 'add' ][ 'translations' ][ $translation[ 'default_locale' ] ] : '';
					} else {
						$value = isset( $lang[ 'translation' ][ $translation[ 'id' ] ] ) ? $lang[ 'translation' ][ $translation[ 'id' ] ] : '';
					}
					?>
				<td><input type="text" name="zwt_edit_languages[<?php echo $lang[ 'id' ]; ?>][translations][<?php echo $translation[ 'default_locale' ]; ?>]" value="<?php echo $value; ?>" /></td>
			<?php } ?>


			<td>
				<?php if ( array_key_exists( $lang[ 'default_locale' ], $this->custom_languages ) && !array_key_exists( $lang[ 'default_locale' ], $this->active_languages ) ): ?>
					<a href="<?php echo admin_url( 'admin.php?page=zwt_manage_locales&edit_langs=1&amp;action=delete-language&amp;id=' .
			$lang[ 'id' ] . '&amp;zwt_nonce=' . wp_create_nonce( 'delete-language' . $lang[ 'id' ] ) ) ?>" title="<?php esc_attr_e( 'Delete', 'Zanto' ) ?>" onclick="if(!confirm('<?php echo esc_js( sprintf( __( 'Are you sure you want to delete this language?%sALL the data associated with this language will be ERASED!', 'Zanto' ), "\n" ) ) ?>')) return false;"><img src="<?php echo GTP_PLUGIN_URL . 'images/delete.gif' ?>" alt="<?php esc_attr_e( 'Delete', 'Zanto' ) ?>" width="12" height="12" /></a>
				   <?php endif; ?>
			</td>

		</tr>
		<?php
	}

	function scripts() {
		?>
		<script type="text/javascript">
			jQuery(document).ready(function(){
				jQuery("#zwt_edit_languages_add_language_button").click(function(){
					jQuery(this).fadeOut('fast',function(){jQuery("#zwt_edit_languages_table tr:last, .zwt_edit_languages_show").show();});
					jQuery('#zwt_edit_languages_ignore_add').val('false');
				});
				jQuery("#zwt_edit_languages_cancel_button").click(function(){
					jQuery(this).fadeOut('fast',function(){
						jQuery("#zwt_edit_languages_add_language_button").show();
						jQuery(".zwt_edit_languages_show").hide();
						jQuery("#zwt_edit_languages_table tr:last input").each(function(){
							jQuery(this).val('');
						});
						jQuery('#zwt_edit_languages_ignore_add').val('true');
						jQuery('#zwt_edit_languages_form :submit').attr('disabled','disabled');
					});
				});
		                				
		                				
				jQuery('#zwt_edit_languages_form :submit').attr('disabled','disabled');
				jQuery('#zwt_edit_languages_form input, #zwt_edit_languages_form select').click(function(){
					jQuery('#zwt_edit_languages_form :submit').removeAttr('disabled');
				});
			});
		</script>
		<?php
	}

	function get_current_languages() {
		global $zwt_site_obj, $wpdb;
		$this->active_languages = $zwt_site_obj->modules[ 'trans_network' ]->get_active_languages();
		$this->custom_languages = $zwt_site_obj->modules[ 'trans_network' ]->get_custom_langs();
		$this->current_languages = array_merge( $this->active_languages, $this->custom_languages );

		foreach ( $this->current_languages as $lang ) {
			foreach ( $this->current_languages as $lang_translation ) {
				$this->current_languages[ $lang[ 'default_locale' ] ][ 'translation' ][ $lang_translation[ 'id' ] ] = $zwt_site_obj->modules[ 'trans_network' ]->get_display_language_name( $lang[ 'default_locale' ], $lang_translation[ 'default_locale' ] );
			}
		}
	}

	function insert_main_table( $code, $english_name, $default_locale, $custom = 0 ) {
		global $wpdb;
		return $wpdb->insert( $wpdb->base_prefix . 'zwt_languages', array(
			'code' => $code,
			'english_name' => $english_name,
			'default_locale' => $default_locale,
			'custom' => $custom
		), array( '%s', '%s', '%s', '%s' )
		);
	}

	function update_main_table( $id, $code, $default_locale ) {
		global $wpdb;
		$wpdb->update( $wpdb->base_prefix . 'zwt_languages', array( 'code' => $code, 'default_locale' => $default_locale ), array( 'ID' => $id ), array( '%s', '%s' ) );
	}

	function insert_translation( $name, $language_code, $display_language_code ) {
		global $wpdb;
		return $wpdb->query( $wpdb->prepare( "INSERT INTO {$wpdb->base_prefix}zwt_languages_translations (name, language_code, display_language_code) VALUES ( %s, %s, %s )", $name, $language_code, $display_language_code ) );
	}

	function update_translation( $name, $language_code, $display_language_code ) {
		global $wpdb;
		$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->base_prefix}zwt_languages_translations SET name=%s WHERE language_code = %s AND display_language_code = %s", $name, $language_code, $display_language_code ) );
	}

	function update() {
		// Basic check.
		if ( !isset( $_POST[ 'zwt_edit_languages' ] ) || !is_array( $_POST[ 'zwt_edit_languages' ] ) ) {
			$this->error( __( 'Please, enter valid data.', 'Zanto' ) );
			return;
		}

		global $zwt_site_obj, $wpdb;

		// First check if add and validate it.
		if ( isset( $_POST[ 'zwt_edit_languages' ][ 'add' ] ) && $_POST[ 'zwt_edit_languages_ignore_add' ] == 'false' ) {
			if ( $this->validate_one( 'add', $_POST[ 'zwt_edit_languages' ][ 'add' ] ) ) {
				$this->insert_one( $this->sanitize( $_POST[ 'zwt_edit_languages' ][ 'add' ] ) );
			}
		}

		foreach ( $_POST[ 'zwt_edit_languages' ] as $id => $data ) {
			// Ignore insert.
			if ( $id == 'add' ) {
				continue;
			}

			// Validate and sanitize data.
			if ( !$this->validate_one( $id, $data ) )
				continue;
			$data = $this->sanitize( $data );

			// Update main table.
			$this->update_main_table( $id, $data[ 'code' ], $data[ 'default_locale' ] );


			// Update translations table.
			foreach ( $data[ 'translations' ] as $translation_code => $translation_value ) {

				// If new (add language) translations are submitted.
				if ( $translation_code == 'add' ) {
					if ( $this->add_validation_failed || $_POST[ 'zwt_edit_languages_ignore_add' ] == 'true' ) {
						continue;
					}
					if ( empty( $translation_value ) ) {
						$translation_value = $data[ 'english_name' ];
					}
					$translation_code = $_POST[ 'zwt_edit_languages' ][ 'add' ][ 'default_locale' ];
				}

				// Check if update.
				if ( $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->base_prefix}zwt_languages_translations WHERE language_code= %s AND display_language_code= %s", $data[ 'default_locale' ], $translation_code ) ) ) {
					$this->update_translation( $translation_value, $data[ 'default_locale' ], $translation_code );
				} else {
					if ( !$this->insert_translation( $translation_value, $data[ 'default_locale' ], $translation_code ) ) {
						$this->error( sprintf( __( 'Error adding translation %s for %s.', 'Zanto' ), $data[ 'default_locale' ], $translation_code ) );
					}
				}
			}
		}
		// Refresh cache.
		foreach ( $zwt_site_obj->modules[ 'trans_network' ]->transnet_blogs as $trans_blog ) {
			switch_to_blog( $trans_blog[ 'blog_id' ] );
			$zwt_site_obj->modules[ 'trans_network' ]->zwt_trans_cache[ 'zwt_lang_name_cache' ]->clear();
			$zwt_site_obj->modules[ 'trans_network' ]->zwt_trans_cache[ 'zwt_locale_cache' ]->clear();
			delete_option( '_zwt_cache' );
			restore_current_blog();
		}


		// Unset ADD fields.
		if ( !$this->add_validation_failed ) {
			unset( $_POST[ 'zwt_edit_languages' ][ 'add' ] );
		}
		// Reser active languages.
		$this->get_current_languages();
	}

	function insert_one( $data ) {
		global $zwt_site_obj, $wpdb;

		// Insert main table.
		if ( !$this->insert_main_table( $data[ 'code' ], $data[ 'english_name' ], $data[ 'default_locale' ], 1 ) ) {
			$this->error( __( 'Adding language failed.', 'Zanto' ) );
			return false;
		}



		// Insert translations.
		$all_languages = $zwt_site_obj->modules[ 'trans_network' ]->get_languages();
		foreach ( $all_languages as $key => $lang ) {

			// If submitted.
			if ( array_key_exists( $lang[ 'default_locale' ], $data[ 'translations' ] ) ) {
				if ( empty( $data[ 'translations' ][ $lang[ 'default_locale' ] ] ) ) {
					$data[ 'translations' ][ $lang[ 'default_locale' ] ] = $data[ 'english_name' ];
				}
				if ( !$this->insert_translation( $data[ 'translations' ][ $lang[ 'default_locale' ] ], $data[ 'default_locale' ], $lang[ 'default_locale' ] ) ) {
					$this->error( sprintf( __( 'Error adding translation %s for %s.', 'Zanto' ), $data[ 'default_locale' ], $lang[ 'default_locale' ] ) );
				}
				continue;
			}

			// Insert dummy translation.
			if ( !$this->insert_translation( $data[ 'english_name' ], $data[ 'default_locale' ], $lang[ 'default_locale' ] ) ) {
				$this->error( sprintf( __( 'Error adding translation %s for %s.', 'Zanto' ), $data[ 'default_locale' ], $lang[ 'default_locale' ] ) );
			}
		}

		// Insert native name.
		if ( !isset( $data[ 'translations' ][ 'add' ] ) || empty( $data[ 'translations' ][ 'add' ] ) ) {
			$data[ 'translations' ][ 'add' ] = $data[ 'english_name' ];
		}
		if ( !$this->insert_translation( $data[ 'translations' ][ 'add' ], $data[ 'default_locale' ], $data[ 'default_locale' ] ) ) {
			$this->error( __( 'Error adding native name.', 'Zanto' ) );
		}
	}

	function validate_one( $id, $data ) {

		global $wpdb;

		// If insert, check if languge code (unique) exists.

		if ( $l_exists = $wpdb->get_var( $wpdb->prepare( "SELECT default_locale FROM {$wpdb->base_prefix}zwt_languages WHERE default_locale=%s", $data[ 'default_locale' ] ) ) && $id == 'add' ) {
			$this->error = __( 'default_locale exists', 'Zanto' );
			$this->add_validation_failed = true;
			return false;

			// Illegal change of locale
		} else if ( $l_exists && $wpdb->get_var( $wpdb->prepare( "SELECT default_locale FROM {$wpdb->base_prefix}zwt_languages WHERE default_locale=%s AND id=%s", $data[ 'default_locale' ], $data[ 'id' ] ) ) != $data[ 'default_locale' ] ) {
			$this->error = __( 'Language default_locale exists', 'Zanto' );
			if ( $id == 'add' )
				$this->add_validation_failed = true;
			return false;
		}

		foreach ( $this->required_fields as $name => $type ) {

			if ( !isset( $_POST[ 'zwt_edit_languages' ][ $id ][ $name ] ) || empty( $_POST[ 'zwt_edit_languages' ][ $id ][ $name ] ) ) {
				if ( $_POST[ 'zwt_edit_languages_ignore_add' ] == 'true' ) {
					return false;
				}
				$this->error( __( 'Please, enter required data Zanto.', 'Zanto' ) );
				if ( $id == 'add' ) {
					$this->add_validation_failed = true;
				}
				return false;
			}
			if ( $type == 'array' && !is_array( $_POST[ 'zwt_edit_languages' ][ $id ][ $name ] ) ) {
				if ( $id == 'add' ) {
					$this->add_validation_failed = true;
				}
				$this->error( __( 'Please, enter valid data.', 'Zanto' ) );
				return false;
			}
		}
		return true;
	}

	function delete_language( $lang_id ) {
		global $wpdb, $zwt_site_obj;
		$lang = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->base_prefix}zwt_languages WHERE id=%d", $lang_id ) );
		if ( $lang ) {
			if ( !array_key_exists( $lang->default_locale, $this->custom_languages ) ) {
				$error = __( "Error: This is a built in language. You can't delete it.", 'Zanto' );
			} else {
				$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->base_prefix}zwt_languages WHERE id=%d", $lang_id ) );
				$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->base_prefix}zwt_languages_translations WHERE language_code=%s", $lang->default_locale ) );

				foreach ( $zwt_site_obj->modules[ 'trans_network' ]->transnet_blogs as $trans_blog ) {
					switch_to_blog( $trans_blog[ 'blog_id' ] );
					$zwt_site_obj->modules[ 'trans_network' ]->zwt_trans_cache[ 'zwt_lang_name_cache' ]->clear();
					$zwt_site_obj->modules[ 'trans_network' ]->zwt_trans_cache[ 'zwt_locale_cache' ]->clear();
					delete_option( '_zwt_cache' );
					restore_current_blog();
				}



				$this->get_current_languages();
				$this->message( sprintf( __( "The language with locale %s was deleted.", 'Zanto' ), '<strong>' . $lang->default_locale . '</strong>' ) );
			}
		} else {
			$error = __( 'Error: Language not found.', 'Zanto' );
		}
		if ( !empty( $error ) ) {
			$this->error( $error );
		}
	}

	function sanitize( $data ) {
		global $wpdb;
		foreach ( $data as $key => $value ) {
			if ( is_array( $value ) ) {
				foreach ( $value as $k => $v ) {
					$data[ $key ][ $k ] = esc_sql( $v );
				}
			}
			$data[ $key ] = esc_sql( $value );
		}
		return $data;
	}

	function error( $str = false ) {
		$this->error .= $str . '<br />';
	}

	function message( $str = false ) {
		$this->message .= $str . '<br />';
	}

}

global $zwt_edit_languages;
$zwt_edit_languages = new ZWT_Edit_Languages;
