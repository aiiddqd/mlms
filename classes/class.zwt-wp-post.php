<?php

if ( $_SERVER[ 'SCRIPT_FILENAME' ] == __FILE__ )
	die( 'Access denied.' );

if ( !class_exists( 'ZWT_WP_POST' ) ) {

	/**
	 * Handles post type operations 
	 * @package ZWT_Base
	 * @author Mucunguzi Ayebare Brooks
	 */
	class ZWT_WP_POST {

		function __construct() {
			$this->registerHookCallbacks();
		}

		public function registerHookCallbacks() {

			add_action( 'wp_trash_post', array( $this, 'delete_post' ) );
			add_action( 'before_delete_post', array( $this, 'delete_post' ) );
			add_action( 'save_post', array( $this, 'save_trans_metabox' ), 1, 9);
			add_action( 'add_meta_boxes', array( $this, 'add_translation_box' ) );
			add_action( 'current_screen', array( $this, 'screen_fns' ) );
			add_action( 'media_buttons', array( $this, 'zwt_copy_from_original' ), 11 );
		}

		/**
		 * Initializes variables
		 */
		public function init() {
			if ( did_action( 'init' ) !== 1 )
				return;
		}

		public function screen_fns( $current_screen ) {
			if ( did_action( 'current_screen' ) !== 1 )
				return;
			$post_type = $current_screen->post_type;
			add_filter( "manage_{$post_type}_posts_columns", array( $this, 'edit_post_th' ) );
			add_action( "manage_{$post_type}_posts_custom_column", array( $this, 'edit_post_td' ), 10, 2 );
			return;
		}

		public function add_translation_box() {
			global $zwt_site_obj, $wpdb;
			$c_trans_network = $zwt_site_obj->modules[ 'trans_network' ];
			if ( did_action( 'add_meta_boxes' ) !== 1 )
				return;
			$post_types = get_post_types( array( 'public' => true ) );
			foreach ( $post_types as $post_type ) {
				/* if ( 'page' == $post_type || 'post' == $post_type )
				  continue;
				  if($pagenow!='post-new.php') */
				if ( !isset( $_REQUEST[ 'source_b' ] ) || !isset( $_REQUEST[ 'zwt_translate' ] ) )
					add_meta_box( ZWT_Base::PREFIX . 'choose_translation', __( 'Zanto Translate', 'Zanto' ), __CLASS__ . '::meta_box_callback', $post_type, 'normal', 'high' );
			}
		}

		public static function meta_box_callback( $post, $box ) {
			global $blog_id, $site_id, $wpdb, $zwt_site_obj, $current_screen;
			$c_trans_network = $zwt_site_obj->modules[ 'trans_network' ];
			wp_nonce_field( plugin_basename( __FILE__ ), 'zwt_savepost_nonce' );
			$post_type_string = '?post_type=' . $current_screen->post_type;

			if ( isset( $_REQUEST[ 'zwt_remove_trans_post' ] ) ) {
				$c_post_network = get_post_meta( $post->ID, ZWT_Base::PREFIX . 'post_network', true );
				zwt_detach_post( $_REQUEST[ 'zwt_b_id' ], $c_post_network );
			}
			switch ( $box[ 'id' ] ) {
				case ZWT_Base::PREFIX . 'choose_translation':
					$transnet_blogs = $c_trans_network->transnet_blogs;
					$transnet_id = $c_trans_network->transnet_id;
					$post_network = get_post_meta( $post->ID, ZWT_Base::PREFIX . 'post_network', true );
					$primary_blog_id = $c_trans_network->primary_lang_blog;

					foreach ( $transnet_blogs as $index => $blog_transn ) {
						if ( $blog_transn[ 'blog_id' ] == $primary_blog_id )
							$primary_lang = $blog_transn[ 'lang_code' ];
					}

					$translated_flag = false; // this will be used to verify if the post is translated
					$primary_post_exists = false;

					if ( $post_network && is_array( $post_network ) ) {// separate translated from untranslated blog post for display
						foreach ( $post_network as $p_index => $p_trans_net ) {
							$b_deleted = true; //monitor deleted blogs
							foreach ( $transnet_blogs as $index => $trans_blog ) {
								if ( $trans_blog[ 'blog_id' ] == $p_trans_net[ 'blog_id' ] ) {
									$tld_lang_blog[ $trans_blog[ 'blog_id' ] ] = $trans_blog[ 'lang_code' ];
									unset( $transnet_blogs[ $index ] );
									$b_deleted = false;
								}
								if ( $p_trans_net[ 'blog_id' ] == $primary_blog_id )
									$primary_post_exists = true;
								if ( isset( $p_trans_net[ 'post_id' ] ) && $p_trans_net[ 'post_id' ] == $post->ID )
									$translated_flag = true;
							}
							if ( $b_deleted ) {// blog was deleted
								unset( $post_network[ $p_index ] );
								zwt_broadcast_post_network( $post_network );
							}
						}
					}

					$blog_parameters = get_metadata( 'site', $site_id, 'zwt_' . $transnet_id . '_site_cache', true );

					if ( $primary_blog_id != $blog_id && ($primary_post_exists || !$translated_flag) ) {// code for non primary language metabox display
						if ( $post_network && is_array( $post_network ) ) {

							foreach ( $post_network as $index => $nw_posts ) {
								if ( $nw_posts[ 'blog_id' ] == $primary_blog_id )
									$primary_post_id = $nw_posts[ 'post_id' ];
							}
						}
						if ( $translated_flag && !isset( $_REQUEST[ 'change_transln' ] ) ) {
							$pb_prefix = $wpdb->get_blog_prefix( $primary_blog_id );
							$output_title = $wpdb->get_var( $wpdb->prepare( "SELECT post_title FROM  {$pb_prefix}posts WHERE ID= %d", $primary_post_id ) );

							$output = '<option>' . $output_title . '</option>';
						} else {


							$args = array(
								'post_type' => $post->post_type,
								'orderby' => 'title',
								'order' => 'ASC',
								'posts_per_page' => -1,
								'c_blog' => $c_trans_network->primary_lang_blog
							);

							$output = zwt_get_post_select_options( $args );
						}
					}
					$view = 'zwt-meta-options.php';
					break;
			}

			$view = dirname( __DIR__ ) . '/views/' . $view;
			if ( is_file( $view ) )
				require( $view );
			else
				throw new Exception( __METHOD__ . " error: " . $view . " doesn't exist." );
		}

		function zwt_copy_from_original() {

			global $wpdb, $post, $zwt_site_obj, $blog_id;
			$c_trans_network = $zwt_site_obj->modules[ 'trans_network' ];

			if ( isset( $_GET[ 'source_b' ] ) && isset( $_GET[ 'zwt_translate' ] ) ) {

				$disabled = '';

				$source_blog = $_GET[ 'source_b' ];
				$source_post_id = $_GET[ 'zwt_translate' ];

				$transnet_blogs = $c_trans_network->transnet_blogs;
				foreach ( $transnet_blogs as $trans_blog )
					if ( $trans_blog[ 'blog_id' ] == $source_blog ) {
						$source_details = $trans_blog;
					}

				$source_lang_name = $c_trans_network->get_display_language_name( $source_details[ 'lang_code' ], get_locale() );
				$show = true;

				if ( trim( $post->post_content ) ) {
					$disabled = ' disabled="disabled"';
				}

				if ( $show ) {
					wp_nonce_field( 'copy_from_original_nonce', '_zwt_nonce_cfo_' . $source_post_id );
					$copy_button_v = '<a id="zwt_cfo" class="button" onclick="zwt_copy_from_original(\'' . esc_js( $source_blog ) . '\', \'' . esc_js( $source_post_id ) . '\');"' . $disabled . '>
					<span><i class="fa fa-paste"></i> ' . sprintf( __( 'Copy content from %s', 'Zanto' ), $source_lang_name ) . '</span>
					</a>';
					echo apply_filters( 'button_cfo_visible', $copy_button_v, $source_post_id, $source_blog );
				}
			} else {
				global $hook_suffix;

				if ( $hook_suffix == 'post-new.php' && $blog_id !== $c_trans_network->primary_lang_blog ) {
					$source_lang_name = $c_trans_network->get_display_language_name( zwt_get_blog_lang( $c_trans_network->primary_lang_blog ) );
					$copy_button_h = '<a id="zwt_cfo" class="button zwt_cfp" style="display:none">
					<span><i class="fa fa-paste"></i> ' . sprintf( __( 'Copy content from %s', 'Zanto' ), $source_lang_name ) . ' </span>
					</a><input type="hidden" id="zwtprimaryblog" name="zwtprimaryblog" value="' . $c_trans_network->primary_lang_blog . '" />';
					echo apply_filters( 'button_cfo_hidden', $copy_button_h, $c_trans_network->primary_lang_blog );
				}
			}
		}

		//@todo verify metabox values
		public function save_trans_metabox( $post_id ) {
			if ( did_action( 'save_post' ) !== 1 )
				return;
			global $wpdb, $blog_id, $zwt_site_obj;
			$c_trans_network = $zwt_site_obj->modules[ 'trans_network' ];
			if ( did_action( 'save_post' ) !== 1 )
				return;

			if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ) {
				return;
			}
			if ( $parent_id = wp_is_post_revision( $post_id ) ) {
				$post_id = $parent_id;
			}

			$old_pnetwork = get_post_meta( $post_id, ZWT_Base::PREFIX . 'post_network', true );
			$transnet_blogs = $c_trans_network->transnet_blogs;
			/* zwt_select_primary is the translation post select tag name for secondary language blogs */
			if ( isset( $_POST[ 'zwt_select_primary' ] ) && is_array( $_POST[ 'zwt_select_primary' ] ) ) {
				if ( !isset( $_POST[ 'zwt_savepost_nonce' ] ) || !wp_verify_nonce( $_POST[ 'zwt_savepost_nonce' ], plugin_basename( __FILE__ ) ) ) {
					return;
				}
				$secondary_elements = array_filter( $_POST[ 'zwt_select_primary' ], 'strlen' );
				if ( empty( $secondary_elements ) )
					return;

				if ( is_array( $old_pnetwork ) ) {
					/* translation existed, remove post from all pnetworks its attached to */
					zwt_detach_post( $blog_id, $old_pnetwork );
				}
				foreach ( $transnet_blogs as $trans_blog ) {
					foreach ( $_POST[ 'zwt_select_primary' ] as $lang => $p_id ) {
						if ( $lang == $trans_blog[ 'lang_code' ] )
							$prim_blog_details = $trans_blog; //see reference comment below
						break;
					}
				}
				/* $prim_blog_details is the array cotaining the blog_id and lang_code of the primary language blog */
				switch_to_blog( $prim_blog_details[ 'blog_id' ] );
				/* check if new primary post has its own post trans network */
				$prim_post_network = get_post_meta( $p_id, ZWT_Base::PREFIX . 'post_network', true );
				restore_current_blog();
				if ( is_array( $prim_post_network ) ) {
					/* add post to existing network */
					$new_pvalue = array( 'blog_id' => $blog_id, 'post_id' => $post_id );
					if ( !in_array( $new_pvalue, $prim_post_network ) ) {
						$prim_post_network[ ] = $new_pvalue;
						zwt_broadcast_post_network( $prim_post_network );
					}
				} else {
					/* create new network and add it to the posts */
					foreach ( $transnet_blogs as $index => $trans_blog ) {
						foreach ( $_POST[ 'zwt_select_primary' ] as $lang => $p_id ) {
							if ( $lang == $trans_blog[ 'lang_code' ] ) {
								$post_transnetwork[ ] = array( 'blog_id' => $trans_blog[ 'blog_id' ], 'post_id' => $p_id );
							}
						}
					}
					$post_transnetwork[ ] = array( 'blog_id' => $blog_id, 'post_id' => $post_id );
					zwt_broadcast_post_network( $post_transnetwork );
				}
			} else {
				/* zwt_select_secondary is the selection tag name on the primary blog post page for selecting translations from secondary blogs */
				if ( isset( $_POST[ 'zwt_select_secondary' ] ) && is_array( $_POST[ 'zwt_select_secondary' ] ) ) {
					if ( !isset( $_POST[ 'zwt_savepost_nonce' ] ) || !wp_verify_nonce( $_POST[ 'zwt_savepost_nonce' ], plugin_basename( __FILE__ ) ) ) {
						return;
					}
					foreach ( $transnet_blogs as $index => $trans_blog )
						foreach ( $_POST[ 'zwt_select_secondary' ] as $lang => $p_id ) {
							if ( $p_id == "-1" )
								continue;
							if ( $lang == $trans_blog[ 'lang_code' ] ) {
								$post_transnetwork[ ] = array( 'blog_id' => $trans_blog[ 'blog_id' ], 'post_id' => $p_id );
							}
						}


					/* process external links in $_POST */
				} if ( isset( $_POST[ 'transln_method_text' ] ) && is_array( $_POST[ 'transln_method_text' ] ) ) {
					$text_elements = array_filter( $_POST[ 'transln_method_text' ], 'strlen' );
					if ( !empty( $text_elements ) ) {

						foreach ( $transnet_blogs as $index => $trans_blog )
							foreach ( $text_elements as $lang => $t_link ) {
								if ( $lang == $trans_blog[ 'lang_code' ] ) {
									$post_transnetwork[ ] = array( 'blog_id' => $trans_blog[ 'blog_id' ], 't_link' => $t_link );
								}
							}
					}
				}
				if ( isset( $post_transnetwork ) && is_array( $post_transnetwork ) ) {
					if ( is_array( $old_pnetwork ) ) {
						$post_transnetwork = array_merge( $post_transnetwork, $old_pnetwork );
					} else {
						/* add current blog to the metadata since no post translatin network exists */
						$post_transnetwork[ ] = array( 'blog_id' => $blog_id, 'post_id' => $post_id );
					}
					zwt_broadcast_post_network( $post_transnetwork );
				}
			}

			if ( !empty( $_SERVER[ 'HTTP_REFERER' ] ) ) {
				$query = array( );
				$parts = parse_url( $_SERVER[ 'HTTP_REFERER' ] );
				if ( isset( $parts[ 'query' ] ) ) {
					parse_str( strval( $parts[ 'query' ] ), $query );
				}
				$source_post_id = isset( $query[ 'zwt_translate' ] ) ? intval( $query[ 'zwt_translate' ] ) : null;
				$source_blog = isset( $query[ 'source_b' ] ) ? intval( $query[ 'source_b' ] ) : null;
			}
			if ( isset( $source_post_id ) && isset( $source_blog ) ) {
				switch_to_blog( $source_blog );
				$source_post_network = get_post_meta( $source_post_id, ZWT_Base::PREFIX . 'post_network', true ); //get post trans network of source post
				restore_current_blog();
				if ( is_array( $source_post_network ) ) { // add post to existing network
					$new_pvalue = array( 'blog_id' => $blog_id, 'post_id' => $post_id );
					if ( !in_array( $new_pvalue, $source_post_network ) ) {
						$source_post_network[ ] = $new_pvalue;
						zwt_broadcast_post_network( $source_post_network );
					}
				} else { // create new post network and add to it the new post and source post
					$post_transnetwork = array( );
					$post_transnetwork[ ] = array( 'blog_id' => $source_blog, 'post_id' => $source_post_id );
					$post_transnetwork[ ] = array( 'blog_id' => $blog_id, 'post_id' => $post_id );
					zwt_broadcast_post_network( $post_transnetwork );
				}
			}
		}

		public function edit_post_th( $columns ) {
			global $zwt_site_obj, $blog_id;
			$transnet_blogs = $zwt_site_obj->modules[ 'trans_network' ]->transnet_blogs;
			$primary_blog = $zwt_site_obj->modules[ 'trans_network' ]->primary_lang_blog;
			$flags = array( );
			if ( $blog_id == $primary_blog ) {
				foreach ( $transnet_blogs as $trans_blog ) {
					if ( $trans_blog[ 'blog_id' ] == $blog_id )
						continue;
					$flags[ ] = zwt_get_flag( $trans_blog[ 'lang_code' ] );
					$columns[ 'zwt_col' ] = implode( '&nbsp;', $flags );
				}
			}else {
				$primary_lang = zwt_get_blog_lang( $primary_blog );
				$columns[ 'zwt_col' ] = zwt_get_flag( $primary_lang );
			}
			return $columns;
		}

		public function edit_post_td( $column_name, $item_id ) {

			if ( 'zwt_col' == $column_name ) {

				global $site_id, $zwt_site_obj, $blog_id, $current_screen;
				$locale = get_locale();
				$trans_obj = $zwt_site_obj->modules[ 'trans_network' ];
				$transnet_blogs = $trans_obj->transnet_blogs;
				$primary_blog = $trans_obj->primary_lang_blog;
				$blog_parameters = get_metadata( 'site', $site_id, 'zwt_' . $trans_obj->transnet_id . '_site_cache', true );
				$post_type_string = '?post_type=' . $current_screen->post_type;

				$post_network = get_post_meta( $item_id, ZWT_Base::PREFIX . 'post_network', true );

				if ( $post_network && is_array( $post_network ) ) {// separate translated from untranslated blog post for display
					if ( $blog_id !== $primary_blog ) {
						$primary_lang = zwt_get_blog_lang( $primary_blog );
						$translated_flag = false;
						foreach ( $post_network as $p_trans_net ) {
							if ( $primary_blog == $p_trans_net[ 'blog_id' ] ) {

								echo '<a href="',
								(isset( $p_trans_net[ 'post_id' ] )) ? $blog_parameters[ $primary_blog ][ 'admin_url' ] . 'post.php?post=' . $p_trans_net[ 'post_id' ] . '&action=edit' : $p_trans_net[ 't_link' ],
								'" target="_blank" title ="' . sprintf( __( 'Edit the %s translation', 'Zanto' ), $trans_obj->get_display_language_name( $primary_lang, $locale ) ) . '"><i class="fa fa-check-square-o btp-post-icon"></i></a>&nbsp';
								$translated_flag = true;
								break;
							}
						}
						if ( !$translated_flag ) {
							echo '<a href="' . add_query_arg( array( 'zwt_translate' => $item_id, 'source_b' => $blog_id ), $blog_parameters[ $primary_blog ][ 'admin_url' ] . 'post-new.php' . $post_type_string ) . '" target="_blank" title ="' . sprintf( __( 'Add %s translation', 'Zanto' ), $trans_obj->get_display_language_name( $primary_lang ) ) . '"><i class="fa fa-plus btp-post-icon"></i></a>&nbsp;';
						}
					} else {
						foreach ( $transnet_blogs as $trans_blog ) {
							if ( $trans_blog[ 'blog_id' ] == $blog_id ) {
								continue;
							}

							$translated_flag = false;
							foreach ( $post_network as $p_trans_net ) {
								if ( $trans_blog[ 'blog_id' ] == $p_trans_net[ 'blog_id' ] ) {

									echo '<a href="',
									(isset( $p_trans_net[ 'post_id' ] )) ? $blog_parameters[ $trans_blog[ 'blog_id' ] ][ 'admin_url' ] . 'post.php?post=' . $p_trans_net[ 'post_id' ] . '&action=edit' : $p_trans_net[ 't_link' ],
									'" target="_blank" title ="' . sprintf( __( 'Edit the %s translation', 'Zanto' ), $trans_obj->get_display_language_name( $trans_blog[ 'lang_code' ], $locale ) ) . '"><i class="fa fa-check-square-o btp-post-icon"></i></a>&nbsp';
									$translated_flag = true;
									break;
								}
							}
							if ( !$translated_flag ) {
								echo '<a href="' . add_query_arg( array( 'zwt_translate' => $item_id, 'source_b' => $blog_id ), $blog_parameters[ $trans_blog[ 'blog_id' ] ][ 'admin_url' ] . 'post-new.php' . $post_type_string ) . '" target="_blank" title ="' . sprintf( __( 'Add %s translation', 'Zanto' ), $trans_obj->get_display_language_name( $trans_blog[ 'lang_code' ] ) ) . '"><i class="fa fa-plus btp-post-icon"></i></a>&nbsp;';
							}
						}
					}
				} else {//no translation exists
					if ( $blog_id !== $primary_blog ) {
						$primary_lang = zwt_get_blog_lang( $primary_blog );
						echo '<a href="' . add_query_arg( array( 'zwt_translate' => $item_id, 'source_b' => $blog_id ), $blog_parameters[ $primary_blog ][ 'admin_url' ] . 'post-new.php' . $post_type_string ) . '" target="_blank" title ="' . sprintf( __( 'Add %s translation', 'Zanto' ), $trans_obj->get_display_language_name( $primary_lang, $locale ) ) . '"><i class="fa fa-plus btp-post-icon"></i></a>&nbsp;';
					} else {
						foreach ( $transnet_blogs as $trans_blog ) {
							if ( $trans_blog[ 'blog_id' ] == $blog_id ) {
								continue;
							}
							if ( defined( 'ZTM_VERSION' ) ) {
								$translation_meta = get_post_meta( $item_id, '_translation_meta_', true );
								if ( isset( $translation_meta[ $trans_blog[ 'blog_id' ] ][ 'status' ] ) && $translation_meta[ $trans_blog[ 'blog_id' ] ][ 'status' ] !== 'translated' ) {
									echo '<a href="#" title ="' . __( 'In Translation', 'Zanto' ) . '"><i class="fa fa-lock btp-post-icon"></i></a>&nbsp;';
									continue;
								}
							}
							echo '<a href="' . add_query_arg( array( 'zwt_translate' => $item_id, 'source_b' => $blog_id ), $blog_parameters[ $trans_blog[ 'blog_id' ] ][ 'admin_url' ] . 'post-new.php' . $post_type_string ) . '" target="_blank" title ="' . sprintf( __( 'Add %s translation', 'Zanto' ), $trans_obj->get_display_language_name( $trans_blog[ 'lang_code' ], $locale ) ) . '"><i class="fa fa-plus btp-post-icon"></i></a>&nbsp;';
						}
					}
				}
			}
		}

		public function delete_post( $post_id ) {
			global $blog_id;
			$c_post_network = get_post_meta( $post_id, ZWT_Base::PREFIX . 'post_network', true ); //get post trans network of post to delete

			if ( is_array( $c_post_network ) ) {
				zwt_detach_post( $blog_id, $c_post_network );
			}
		}

		/**
		 * Prepares site to use the plugin during activation
		 * @param bool $networkWide
		 */
		public function activate() {
			
		}

		/**
		 * Rolls back activation procedures when de-activating the plugin
		 */
		public function deactivate() {
			
		}

		/**
		 * Executes the logic of upgrading from specific older versions of the plugin to the current version
		 * @param string $dbVersion
		 */
		public function upgrade( $dbVersion = 0 ) {
			/* all general upgrade procedures are implemented in the ZWT_Translation_Network class upgrade function */
		}

		/**
		 * Checks that the object is in a correct state
		 * @param string $property An individual property to check, or 'all' to check all of them
		 * @return bool
		 */
		protected function isValid( $property = 'all' ) {
			return true;
		}

	}

}
?>