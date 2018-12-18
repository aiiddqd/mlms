<?php

if ( $_SERVER[ 'SCRIPT_FILENAME' ] == __FILE__ )
	die( 'Access denied.' );

if ( !class_exists( 'ZWT_Tax' ) ) {

	/**
	 * Handles taxonomy type operations 
	 * @package ZWT_Base
	 * @author Zanto Translate
	 */
	class ZWT_Tax {

		protected $tax_meta;

		function __construct() {
			$this->tax_meta = get_option( 'zwt_taxonomy_meta' );
			$this->registerHookCallbacks();
		}

		public function registerHookCallbacks() {
			add_action( 'current_screen', array( $this, 'screen_fns' ) );
			add_action( 'edit_term', array( $this, 'add_term' ), 99, 3 );
			add_action( 'create_term', array( $this, 'add_term' ), 1, 3 );
			add_action( 'delete_term', array( $this, 'delete_term' ), 10, 3 );
		}

		/**
		 * Initializes variables
		 * @mvc Controller
		 * @author Zanto Translate
		 */
		public function init() {
			if ( did_action( 'init' ) !== 1 )
				return;
		}

		function screen_fns( $current_screen ) {
			if ( did_action( 'current_screen' ) !== 1 ) {
				return;
			}
			$taxonomy = $current_screen->taxonomy;
			if ( isset( $_GET[ 'from_lang' ] ) ) {

				add_action( 'admin_notices', array( $this, '_tax_adding' ) );
			}

			if ( !empty( $taxonomy ) ) {
				// if (!in_array($taxonomy, donttranslate)) {  //check if it should not be translated from here
				add_action( $taxonomy . '_add_form_fields', array( $this, 'edit_term_form' ) );
				add_action( $taxonomy . '_edit_form', array( $this, 'edit_term_form' ) );

				add_filter( 'manage_edit-' . $taxonomy . '_columns', array( $this, 'edit_tax_th' ) );
				add_action( 'manage_' . $taxonomy . '_custom_column', array( $this, 'edit_tax_td' ), 10, 3 );
			}
		}

		function edit_term_form( $taxonomy ) { //adds taxonomy fields to edit taxonomy term and add new term pages
			global $wpdb, $current_screen, $blog_id, $site_id, $zwt_site_obj;
			$locale = get_locale();
			$tax_meta = $this->tax_meta;
			$term_id = isset( $taxonomy->term_id ) ? $taxonomy->term_id : false;
			$edit_tax = false;

			if ( isset( $_GET[ 'action' ] ) ) {
				$edit_tax = true;
			}

			$taxonomy_name = $current_screen->taxonomy;
			$post_type = $current_screen->post_type;

			$source_blog = isset( $_GET[ 'source_b' ] ) ? strip_tags( $_GET[ 'source_b' ] ) : false;
			$c_trans_network = $zwt_site_obj->modules[ 'trans_network' ];
			wp_nonce_field( plugin_basename( __FILE__ ), 'zwt_savetax_nonce' );
			$transnet_blogs = $c_trans_network->transnet_blogs;
			$transnet_id = $c_trans_network->transnet_id;
			$args = array(
				'hide_empty' => 0,
				'show_option_none' => 'Non Selected',
				'selected' => 0,
				'hierarchical' => 1,
				'taxonomy' => $taxonomy_name,
				'hide_if_empty' => false,
				'echo' => false,
				'class' => 'zwt_input_class'
			);
			$translated_terms = array( );
			if ( $source_blog ) {
				if ( isset( $_GET[ 'zwt_translate_tax' ] ) ) {
					$source_term_id = $_GET[ 'zwt_translate_tax' ];
					$source_tax_meta = get_blog_option( $source_blog, 'zwt_taxonomy_meta' );
					if ( isset( $source_tax_meta[ $taxonomy_name ][ $source_term_id ] ) )
						$tax_selected = $source_tax_meta[ $taxonomy_name ][ $source_term_id ];
				}
			}
			foreach ( $transnet_blogs as $trans_blog ) {
				if ( $trans_blog[ 'blog_id' ] == $blog_id ) {
					continue;
				}

				if ( isset( $tax_meta[ $taxonomy_name ][ $term_id ][ $trans_blog[ 'blog_id' ] ] ) ) {
					$args[ 'selected' ] = $tax_meta[ $taxonomy_name ][ $term_id ][ $trans_blog[ 'blog_id' ] ];
					$translated_terms[ $trans_blog[ 'blog_id' ] ] = $args[ 'selected' ]; // coresponding translated term id in blog with id $trans_blog['blog_id']
				} else {
					$args[ 'selected' ] = 0;
					$translated_terms[ $trans_blog[ 'blog_id' ] ] = false;
				}


				$args[ 'name' ] = 'zwt_trans_taxonomies[' . $trans_blog[ 'blog_id' ] . ']';
				$args[ 'id' ] = 'zwt_bterms_drop' . $trans_blog[ 'blog_id' ];

				switch_to_blog( $trans_blog[ 'blog_id' ] );
				if ( $source_blog && isset( $tax_selected[ $trans_blog[ 'blog_id' ] ] ) ) {
					$args[ 'selected' ] = $tax_selected[ $trans_blog[ 'blog_id' ] ];
				} elseif ( $source_blog && $source_blog == $trans_blog[ 'blog_id' ] ) {
					$args[ 'selected' ] = $source_term_id;
				}


				$args['orderby'] = 'NAME';
				$tax_terms = wp_dropdown_categories( $args );
				$terms_drop_down[ $trans_blog[ 'blog_id' ] ] = $tax_terms;
				restore_current_blog();
			}


			/* if ($post_network && is_array($post_network)) {
			  foreach ($transnet_blogs as $index => $b_trans_net) {
			  foreach ($post_network as $p_trans_net) {
			  // do taxonomy meta suff here
			  }
			  }
			  } */

			$blog_parameters = get_metadata( 'site', $site_id, 'zwt_' . $transnet_id . '_site_cache', true );


			require( dirname( __DIR__ ) . '/menus/taxonomy-langs.php');
		}

		function edit_tax_th( $columns ) {
			global $zwt_site_obj, $blog_id;
			$transnet_blogs = $zwt_site_obj->modules[ 'trans_network' ]->transnet_blogs;
			$flags = array( );
			foreach ( $transnet_blogs as $trans_blog ) {
				if ( $trans_blog[ 'blog_id' ] == $blog_id )
					continue;
				$flags[ ] = zwt_get_flag( $trans_blog[ 'lang_code' ] );
			}
			$columns[ 'zwt_col' ] = implode( '&nbsp;', $flags );
			return $columns;
		}

		function edit_tax_td( $deprecated, $column_name, $term_id ) {

			if ( 'zwt_col' == $column_name ) {

				global $site_id, $zwt_site_obj, $blog_id, $current_screen;
				$locale = get_locale();
				$trans_obj = $zwt_site_obj->modules[ 'trans_network' ];
				$transnet_blogs = $trans_obj->transnet_blogs;
				$blog_parameters = get_metadata( 'site', $site_id, 'zwt_' . $trans_obj->transnet_id . '_site_cache', true );
				$taxonomy_name = $current_screen->taxonomy;
				$post_type = $current_screen->post_type;
				//$post_type_string = '?post_type=' . $current_screen->taxonomy;
				$tax_meta = $this->tax_meta;


				if ( isset( $tax_meta[ $taxonomy_name ][ $term_id ] ) ) {// separate translated from untranslated blog terms 
					foreach ( $transnet_blogs as $trans_blog ) {
						if ( $trans_blog[ 'blog_id' ] == $blog_id )
							continue;
						$b_lang = $trans_obj->get_display_language_name( $trans_blog[ 'lang_code' ], $locale );
						if ( isset( $tax_meta[ $taxonomy_name ][ $term_id ][ $trans_blog[ 'blog_id' ] ] ) ) {
							$blog_term = $tax_meta[ $taxonomy_name ][ $term_id ][ $trans_blog[ 'blog_id' ] ];
							echo '<a href="',
							$blog_parameters[ $trans_blog[ 'blog_id' ] ][ 'admin_url' ] . 'edit-tags.php?action=edit&taxonomy=' . $taxonomy_name . '&tag_ID=' . $blog_term . '&post_type=' . $post_type,
							'" target="_blank" title ="' . sprintf( __( 'Edit the %s translation', 'Zanto' ), $b_lang ) . '"><i class="fa fa-check-square-o btp-tax-icon"></i></a>&nbsp';
						} else {
							echo '<a href="' . add_query_arg( array( 'zwt_translate_tax' => $term_id, 'source_b' => $blog_id ), $blog_parameters[ $trans_blog[ 'blog_id' ] ][ 'admin_url' ] . 'edit-tags.php?taxonomy=' . $taxonomy_name . '&post_type=' . $post_type ) . '" target="_blank" title ="' . sprintf( __( 'Add %s translation', 'Zanto' ), $b_lang ) . '"><i class="fa fa-plus btp-tax-icon"></i></a>&nbsp;';
						}
					}
				} else {//no translation exists
					foreach ( $transnet_blogs as $trans_blog ) {
						if ( $trans_blog[ 'blog_id' ] == $blog_id )
							continue;
						echo '<a href="' . add_query_arg( array( 'zwt_translate_tax' => $term_id, 'source_b' => $blog_id ), $blog_parameters[ $trans_blog[ 'blog_id' ] ][ 'admin_url' ] . 'edit-tags.php?taxonomy=' . $taxonomy_name . '&post_type=' . $post_type ) . '" target="_blank" title ="' . sprintf( __( 'Add %s translation', 'Zanto' ), $trans_obj->get_display_language_name( $trans_blog[ 'lang_code' ], $locale ) ) . '"><i class="fa fa-plus btp-tax-icon"></i></a>&nbsp;';
					}
				}
			}
		}

		/* save taxonomy translations */

		function add_term( $term_id, $tt_id, $taxonomy ) {
			if ( did_action( 'edit_term' ) !== 1 && did_action( 'create_term' ) !== 1 ) {
				return;
			}
			if ( !isset( $_POST[ 'zwt_trans_taxonomies' ] ) ) {
				return;
			}
			global $zwt_site_obj, $blog_id;
			$transnet_blogs = $zwt_site_obj->modules[ 'trans_network' ]->transnet_blogs;
			$tax_meta = $this->tax_meta;

			if ( !isset( $tax_meta[ $taxonomy ][ $term_id ] ) || !is_array( $tax_meta[ $taxonomy ][ $term_id ] ) )
				$tax_meta[ $taxonomy ][ $term_id ] = array( );

			foreach ( $transnet_blogs as $trans_blog ) {
				if ( $trans_blog[ 'blog_id' ] == $blog_id ) {
					continue;
				}
				if ( isset( $_POST[ 'zwt_trans_taxonomies' ][ $trans_blog[ 'blog_id' ] ] )
				&& !empty( $_POST[ 'zwt_trans_taxonomies' ][ $trans_blog[ 'blog_id' ] ] )
				&& -1 != $_POST[ 'zwt_trans_taxonomies' ][ $trans_blog[ 'blog_id' ] ] ) {
					$val = intval( $_POST[ 'zwt_trans_taxonomies' ][ $trans_blog[ 'blog_id' ] ] );
					$tax_meta[ $taxonomy ][ $term_id ][ $trans_blog[ 'blog_id' ] ] = $val;
					$old_blog_id = $blog_id;
					switch_to_blog( $trans_blog[ 'blog_id' ] ); // switch to $val term source blog and update it with the term being translated
					$val_blog_tax_meta = get_option( 'zwt_taxonomy_meta' );
					$val_blog_tax_meta[ $taxonomy ][ $val ][ $old_blog_id ] = $term_id; //@todo verify existence of $taxonomy before comencing
					update_option( 'zwt_taxonomy_meta', $val_blog_tax_meta, false);
					restore_current_blog();
				} else {
					if ( isset( $tax_meta[ $taxonomy ][ $term_id ][ $trans_blog[ 'blog_id' ] ] ) )
						unset( $tax_meta[ $taxonomy ][ $term_id ][ $trans_blog[ 'blog_id' ] ] );
				}
			}
			if ( empty( $tax_meta[ $taxonomy ][ $term_id ] ) ) { //avoid empty arrays due to initialising an empty array
				unset( $tax_meta[ $taxonomy ][ $term_id ] );
			}
			update_option( 'zwt_taxonomy_meta', $tax_meta, false );

			if ( defined( 'W3TC_DIR' ) && class_exists( 'W3_ObjectCache' ) ) {
				require_once W3TC_DIR . '/lib/W3/ObjectCache.php';
				$w3_objectcache = & W3_ObjectCache::instance();

				$w3_objectcache->flush();
			}
		}

		public function delete_term( $term, $tt_id, $taxonomy ) {
			if ( did_action( 'delete_term' ) !== 1 ) {
				return;
			}
			global $zwt_site_obj, $blog_id;
			$tax_meta = $this->tax_meta;
			$c_trans_network = $zwt_site_obj->modules[ 'trans_network' ];
			$transnet_blogs = $c_trans_network->transnet_blogs;
			if ( isset( $tax_meta[ $taxonomy ][ $term ] ) ) {
				unset( $tax_meta[ $taxonomy ][ $term ] );
				update_option( 'zwt_taxonomy_meta', $tax_meta, false );
			}
			foreach ( $transnet_blogs as $trans_blog ) {
				if ( $trans_blog[ 'blog_id' ] == $blog_id ) {
					continue;
				}
				$b_taxmeta = get_blog_option( $trans_blog[ 'blog_id' ], 'zwt_taxonomy_meta' );
				$update_flag = false;
				if ( isset( $b_taxmeta[ $taxonomy ] ) )
					foreach ( $b_taxmeta[ $taxonomy ] as $index => $tax_array ) {
						if ( isset( $tax_array[ $blog_id ] ) && $tax_array[ $blog_id ] == $term ) {
							unset( $b_taxmeta[ $taxonomy ][ $index ][ $blog_id ] );
							$update_flag = true;
						}
					}
				if ( $update_flag ) {
					update_blog_option( $trans_blog[ 'blog_id' ], 'zwt_taxonomy_meta', $b_taxmeta );
				}
			}
		}

		/**
		 * Prepares site to use the plugin during activation
		 * @mvc Controller
		 * @author Zanto Translate
		 * @param bool $networkWide
		 */
		public function activate() {
			
		}

		/**
		 * Rolls back activation procedures when de-activating the plugin
		 * @mvc Controller
		 * @author Zanto Translate
		 */
		public function deactivate() {
			
		}

		/**
		 * Executes the logic of upgrading from specific older versions of the plugin to the current version
		 * @mvc Model
		 * @author Zanto Translate
		 * @param string $dbVersion
		 */
		public function upgrade( $dbVersion = 0 ) {
			/* all general upgrade procedures are implemented in the ZWT_Translation_Network class upgrade function */
		}

		/**
		 * Checks that the object is in a correct state
		 * @mvc Model
		 * @author Zanto Translate
		 * @param string $property An individual property to check, or 'all' to check all of them
		 * @return bool
		 */
		protected function isValid( $property = 'all' ) {
			return true;
		}

	}

	// end ZWT_Tax
}
?>