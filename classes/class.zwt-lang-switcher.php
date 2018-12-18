<?php
if ( $_SERVER[ 'SCRIPT_FILENAME' ] == __FILE__ ) {
	die( 'Access denied.' );
}

if ( !class_exists( 'ZWT_Lang_Switcher' ) ) {

	/**
	 * Definition of the language switcher calss
	 * @package ZWT_Base
	 * @author Zanto Translate
	 */
	class ZWT_Lang_Switcher {

		private $wp_query;
		protected $ls_settings;
        protected $current_user_id;

		/**
		 * Constructor
		 * @mvc Controller
		 * @author Zanto Translate
		 */
		function __construct() {
			global $zwt_site_obj;
			if ( !isset( $zwt_site_obj->modules[ 'settings' ] ) )
				return;
			$this->get_settings();
			$this->registerHookCallbacks();
			if ( !isset( $_POST[ 'zwt_ls_theme' ] ) ) {//don't load when doing language switcher updates
				$this->get_ls_theme();
			}
		}

		/**
		 * Register callbacks for actions and filters
		 * @mvc Controller
		 * @author Zanto Translate
		 */
		public function registerHookCallbacks() {

			add_action( 'plugins_loaded', array( $this, 'init' ), 1 );
			if ( is_admin() ) {
				add_action( 'admin_init', array( $this, 'process_admin_swicher' ) );
				add_action( 'admin_bar_menu', __CLASS__ . '::adminLanguageSwitcher', 999 );
				add_action( 'zwt_language_switcher', array( $this, 'zwt_language_switcher_temp' ), 1 );
			} else {

				if ( $this->ls_settings[ 'show_footer_selector' ] ) {
					add_action( 'wp_footer', array( $this, 'language_selector_footer' ), 19 );
				}
				if ( !empty( $this->ls_settings[ 'alt_lang_availability' ] ) ) {
					add_filter( 'the_content', array( $this, 'post_availability' ), 100 );
				}
			}
			if ( !is_admin() ) {
				add_action( 'wp_head', array( $this, 'custom_language_switcher_style' ), 20 );
				add_action( 'wp_head', array( $this, 'add_header_lang_links' ) );
			}
		}

		/**
		 * Initializes variables
		 * @mvc Controller
		 * @author Zanto Translate
		 */
		public function init() {
			$this->current_user_id = get_current_user_id();
			if ( is_admin() ) {
				add_filter( 'locale', array( $this, 'user_admin_locale' ) );
			}
		}

		public function get_settings( $update=false ) {
			if ( !$update ) {
				global $zwt_site_obj;
				$this->ls_settings = $zwt_site_obj->modules[ 'settings' ]->settings[ 'lang_switcher' ];
			} else {
				$settings = ZWT_Settings::getSettings();
				$this->ls_settings = $settings[ 'lang_switcher' ];
			}
		}

		public function custom_language_switcher_style() {
			if ( isset( $this->ls_settings[ 'zwt_ls_custom_css' ] ) && !empty( $this->ls_settings[ 'zwt_ls_custom_css' ] ) ) {
				echo "\r\n<style type=\"text/css\">";
				echo $this->ls_settings[ 'zwt_ls_custom_css' ];
				echo "\r\n</style>";
			}
		}

		/**
		 * Adds alternative languages to the <head> tag
		 * @since 0.3.0
		 * @return void
		 */
		function add_header_lang_links() {
			global $zwt_site_obj, $wp;
			$trans_network = $zwt_site_obj->modules[ 'trans_network' ];

			// $add_langs2head = zwt_network_vars( $trans_network->transnet_id, 'get', 'add_seo_headlangs' );
            // Custom filter (because zanto is lame)
			$add_langs2head = apply_filters(
                'zwt_add_lang_to_head',
                zwt_network_vars( $trans_network->transnet_id, 'get', 'add_seo_headlangs')
            );

			if ( $add_langs2head ) {
				$links = '';
				$languages = $this->get_current_ls( array( 'skip_missing' => 1 ) );
				if ( !empty( $languages ) )
					foreach ( $languages as $lang_code => $lang ) {
						$lang_locale = str_replace( "_", "-", $lang_code );
						$lang_locale = $lang_locale === 'uk' ? 'uk-UA' : $lang_locale;
						$links.= '<link rel="alternate" hreflang="' . $lang_locale . '" href="' . $lang[ 'url' ] . '">';
					}
				$links .= '<link rel="alternate" hreflang="x-default" href="' . home_url(add_query_arg([], $wp->request)) . '">';

                // Custom filter (because zanto is lame)
                $links = apply_filters('zwt_header_lang_links', $links, $languages);
				echo $links;
			}
		}

		public function get_ls_theme() {
			global $show_flag, $show_native_name, $show_translated_name;

			$show_flag = $this->ls_settings[ 'elements' ][ 'flag' ];
			$show_native_name = $this->ls_settings[ 'elements' ][ 'native_name' ];
			$show_translated_name = $this->ls_settings[ 'elements' ][ 'translated_name' ];

			if ( $this->ls_settings[ 'zwt_ls_theme' ] !== 0 ) {
				$theme_path = WP_CONTENT_DIR . $this->ls_settings[ 'zwt_ls_theme' ];

				if ( file_exists( $theme_path ) && is_file( $theme_path ) ) {
					include_once($theme_path);
				} else {
					include_once( dirname( __DIR__ ) . '/views/lang-switcher/lang_switcher.zwt.php' );
				}
			} else {
				include_once( dirname( __DIR__ ) . '/views/lang-switcher/lang_switcher.zwt.php' );
			}

			return;
		}

		//$args['skip_missing']
		public function get_current_ls( $args=array( ) ) {
			global $wpdb, $post, $cat, $tag_id, $blog_id, $zwt_site_obj, $wp_query, $site_id;

			$defaults = array( 'skip_missing' => 0 );
			$args = wp_parse_args( $args, $defaults );

			$trans_network = $zwt_site_obj->modules[ 'trans_network' ];
			$transnet_blogs = $trans_network->transnet_blogs;
			$ls_exclude_list = get_metadata( 'site', $site_id, 'zwt_' . $trans_network->transnet_id . '_exclude', true );
			$translated_urls = null;

			if ( is_front_page() ) {
				if ( $this->ls_settings[ 'front_page_trans' ] && !$wp_query->is_home && !empty( $wp_query->posts ) ) {
					//get meta of post  $this->wp_query->post->ID
					$p_translations = get_post_meta( $wp_query->post->ID, ZWT_Base::PREFIX . 'post_network', true );
					//do blog update checks here

					if ( !empty( $p_translations ) )
						foreach ( $p_translations as $p_trans ) {
							if ( isset( $p_trans[ 'post_id' ] ) )
								$translated_urls[ $p_trans[ 'blog_id' ] ] = zwt_get_trans_url( 'post', $p_trans[ 'blog_id' ], $p_trans[ 'post_id' ] );
							else
								$translated_urls[ $p_trans[ 'blog_id' ] ] = $p_trans[ 't_link' ];
						}
				}
			}
			
			elseif($wp_query->is_home || $wp_query->is_posts_page){
				$p_translations = get_post_meta( $wp_query->queried_object_id, ZWT_Base::PREFIX . 'post_network', true );

				if ( !empty( $p_translations ) ) {
					foreach ( $p_translations as $p_trans ) {
						if ( isset( $p_trans[ 'post_id' ] ) )
							$translated_urls[ $p_trans[ 'blog_id' ] ] = zwt_get_trans_url( 'post', $p_trans[ 'blog_id' ], $p_trans[ 'post_id' ] );
						else
							$translated_urls[ $p_trans[ 'blog_id' ] ] = $p_trans[ 't_link' ];
					}
				}				
			}

			elseif ( is_singular() && !empty( $wp_query->posts ) ) {
				//get meta of post  $this->wp_query->post->ID
				$p_translations = get_post_meta( $wp_query->post->ID, ZWT_Base::PREFIX . 'post_network', true );
				//do blog update checks here

				if ( !empty( $p_translations ) ) {
					foreach ( $p_translations as $p_trans ) {
						if ( isset( $p_trans[ 'post_id' ] ) ) {
							$translated_urls[ $p_trans[ 'blog_id' ] ] = zwt_get_trans_url( 'post', $p_trans[ 'blog_id' ], $p_trans[ 'post_id' ] );
                            if (empty($translated_urls[ $p_trans[ 'blog_id' ] ])) {
                                unset($translated_urls[$p_trans['blog_id']]);
                            }
                        }
						else
							$translated_urls[ $p_trans[ 'blog_id' ] ] = $p_trans[ 't_link' ];
					}
                    unset($index);
				}
			}
			//$tax_meta[$taxonomy][$term_id][$trans_blog['blog_id']]
			elseif ( is_category() && !empty( $wp_query->posts ) ) {
				$tax_meta = get_option( 'zwt_taxonomy_meta' );
				$tax_id = $wp_query->get_queried_object_id();
				foreach ( $transnet_blogs as $trans_blog ) {
					if ( $blog_id == $trans_blog[ 'blog_id' ] ) {
						$translated_urls[ $trans_blog[ 'blog_id' ] ] = zwt_get_trans_url( 'category', $blog_id, $tax_id );
					} elseif ( isset( $tax_meta[ 'category' ][ $cat ][ $trans_blog[ 'blog_id' ] ] ) )
						$translated_urls[ $trans_blog[ 'blog_id' ] ] = zwt_get_trans_url( 'category', $trans_blog[ 'blog_id' ], $tax_meta[ 'category' ][ $cat ][ $trans_blog[ 'blog_id' ] ] );
				}
			} elseif ( is_tag() && !empty( $wp_query->posts ) ) {
				$tax_meta = get_option( 'zwt_taxonomy_meta' );
				$tax_id = $wp_query->get_queried_object_id();
				foreach ( $transnet_blogs as $trans_blog ) {
					if ( $blog_id == $trans_blog[ 'blog_id' ] ) {
						$translated_urls[ $trans_blog[ 'blog_id' ] ] = zwt_get_trans_url( 'post_tag', $blog_id, $tax_id );
					} elseif ( isset( $tax_meta[ 'post_tag' ][ $tag_id ][ $trans_blog[ 'blog_id' ] ] ) ) {
						$translated_urls[ $trans_blog[ 'blog_id' ] ] = zwt_get_trans_url( 'post_tag', $trans_blog[ 'blog_id' ], $tax_meta[ 'post_tag' ][ $tag_id ][ $trans_blog[ 'blog_id' ] ] );
					}
				}
			} elseif ( is_tax() ) {
				$tax_meta = get_option( 'zwt_taxonomy_meta' );
				$tax_id = $wp_query->get_queried_object_id();
				$taxonomy_name = get_query_var( 'taxonomy' );
				foreach ( $transnet_blogs as $trans_blog ) {
					if ( $blog_id == $trans_blog[ 'blog_id' ] ) {
						$translated_urls[ $trans_blog[ 'blog_id' ] ] = zwt_get_trans_url( $taxonomy_name, $blog_id, $tax_id);
					} elseif ( isset( $tax_meta[ $taxonomy_name ][ $tax_id ][ $trans_blog[ 'blog_id' ] ] ) ) {
						$translated_urls[ $trans_blog[ 'blog_id' ] ] = zwt_get_trans_url( $taxonomy_name, $trans_blog[ 'blog_id' ], $tax_meta[ $taxonomy_name ][ $tax_id ][ $trans_blog[ 'blog_id' ] ] );
					}
				}
			}
			$translated_urls = apply_filters( 'zwt_translated_urls', $translated_urls, $args );

			$id = 1;
			$zwt_ls_array = array( );
			$current_language = get_locale();
			foreach ( $transnet_blogs as $trans_blog ) {
				if ( isset( $ls_exclude_list[ $trans_blog[ 'blog_id' ] ] ) ) {
					continue;
				}
				if ( isset( $translated_urls[ $trans_blog[ 'blog_id' ] ] ) ) {// translated url exists
					$url = $translated_urls[ $trans_blog[ 'blog_id' ] ];
					if ( zwt_get_global_data( 'lang_url_format', $trans_blog[ 'blog_id' ] ) == 2 ) {
						$url = $trans_network->add_url_lang( $url, $trans_blog[ 'blog_id' ], 2 );
					}
					$url = apply_filters( 'zwt_url_exist', $url, $translated_urls[ $trans_blog[ 'blog_id' ] ], $trans_blog );
				} else {//translated url doesn't exist
					if ( (0 == $this->ls_settings[ 'skip_missing_trans' ] && 0 == $args[ 'skip_missing' ]) || is_front_page() ) { //link to home page
						$url = zwt_get_global_data( 'site_url', $trans_blog[ 'blog_id' ] );
						$lang_url_format = zwt_get_global_data( 'lang_url_format', $trans_blog[ 'blog_id' ] );
						if ( $lang_url_format == 2 ) {
							$url = $trans_network->add_url_lang( $url, $trans_blog[ 'blog_id' ], 2 );
						} elseif ( $lang_url_format == 1 ) {
							$url = $trans_network->add_url_lang( $url, $trans_blog[ 'blog_id' ], 1 );
						}
					} else {
						$url = apply_filters( 'zwt_no_url', null, $trans_blog ); // use this filter to do what you want with skip language option
						if ( is_null( $url ) )
							continue;
					}
				}
				($blog_id == $trans_blog[ 'blog_id' ]) ? $active = 1 : $active = 0;
				$native_name = $trans_network->get_display_language_name( $trans_blog[ 'lang_code' ] );
				if ( !$native_name )
					$native_name = $trans_network->get_english_name( $trans_blog[ 'lang_code' ] );
				$translated_name = $trans_network->get_display_language_name( $trans_blog[ 'lang_code' ], $current_language );
				if ( !$translated_name )
					$translated_name = $trans_network->get_english_name( $trans_blog[ 'lang_code' ] );
				$language_code = $trans_network->get_lang_code( $trans_blog[ 'lang_code' ], true );
				$country_flag_url = zwt_get_site_flags( $trans_blog[ 'lang_code' ] );

				$zwt_ls_array[ $trans_blog[ 'lang_code' ] ] = array( 'id' => $id, 'active' => $active, 'encode_url' => 0, 'native_name' => $native_name,
					'language_code' => $language_code, 'translated_name' => $translated_name, 'url' => $url, 'country_flag_url' => $country_flag_url );
				$id++;
			}
			unset( $id );
			return apply_filters( 'zwt_ls_array', $zwt_ls_array, $args );
		}

		function post_availability( $content ) {
			global $post_id, $blog_id, $zwt_site_obj, $wp_query;
			$trans_network = $zwt_site_obj->modules[ 'trans_network' ];
			$transnet_blogs = $trans_network->transnet_blogs;

			$out = '';
			if ( is_singular() ) {
				//get meta of post  $this->wp_query->post->ID
				$p_translations = get_post_meta( $wp_query->post->ID, ZWT_Base::PREFIX . 'post_network', true );

				if ( !empty( $p_translations ) ) {
					$other_langs = array( );
					foreach ( $transnet_blogs as $trans_blog ) {
						if ( $trans_blog[ 'blog_id' ] == $blog_id ) {
							continue;
						}
						foreach ( $p_translations as $p_trans ) {
							if ( $p_trans[ 'blog_id' ] == $trans_blog[ 'blog_id' ] ) {
								if ( isset( $p_trans[ 'post_id' ] ) )
									$other_langs[ ] = '<a href="' . zwt_get_trans_url( 'post', $p_trans[ 'blog_id' ], $p_trans[ 'post_id' ] ) . '">' . $trans_network->get_display_language_name( $trans_blog[ 'lang_code' ] ) . '</a>';
								else
									$other_langs[ ] = '<a href="' . $p_trans[ 't_link' ] . '">' . $trans_network->get_display_language_name( $trans_blog[ 'lang_code' ] ) . '</a>';
							}
						}
					}
				}

				if ( empty( $other_langs ) ) {
					return $content;
				}
				$alt_lang_style = $this->ls_settings[ 'post_tl_style' ];
				$out .= join( ', ', $other_langs );
				$out = '<p class="' . ((!$alt_lang_style) ? 'zwt_lang_guess' : 'zwt_lang_guess_plain') . '">' . sprintf( $this->ls_settings[ 'post_availability_text' ], $out ) . '</p>';
				$out = apply_filters( 'zwt_post_alternative_languages', $out );

				if ( $this->ls_settings[ 'post_tl_position' ] == 'above' ) {
					$content = $out . $content;
				} else {
					$content = $content . $out;
				}
			}
			return $content;
		}

		function language_selector_footer() {
			do_action( 'zwt_footer_lang_switcher' );
			if ( !apply_filters( 'zwt_do_footer_lang_switcher', true ) ) {
				return;
			}
			global $show_flag, $show_native_name, $show_translated_name;

			$languages = zwt_get_languages( 'skip_missing=0' );
			if ( !empty( $languages ) ) {
				// This is used in display of the footer Language Switcher
				?>
				<div id="lang_sel_footer">
					<?php foreach ( $languages as $lang ) { ?>
						<?php $lang_native = ($show_native_name) ? $lang[ 'native_name' ] : false; ?>
						<?php $lang_translated = ($show_translated_name) ? $lang[ 'translated_name' ] : false; ?>
						<?php $flag = ($show_flag) ? $lang[ 'country_flag_url' ] : '' ?>
						<?php $href = apply_filters( 'zwt_filter_link', $lang[ 'url' ], $lang ) ?>
						<?php $class = ($lang[ 'active' ]) ? 'lang_sel_sel' : 'lang_sel'; ?>

						<a rel="alternate" class ="<?php echo $class ?>" hreflang="<?php echo $lang[ 'language_code' ] ?>" href="<?php echo $href ?>">
							<span>
							<img src="<?php echo $flag; ?>"/>
							<?php echo zwt_disp_language( $lang_native, $lang_translated ); ?>
							</span>
						</a>

					<?php } ?>

				</div>
				<?php
			}
		}

		function zwt_language_switcher_temp() {
			$this->zwt_language_switcher();
		}

		/**
		 * Adds pages to the Admin Panel menu
		 * @mvc Controller
		 * @author Zanto Translate
		 */
		public static function adminLanguageSwitcher( $wp_admin_bar ) {
			if ( did_action( 'admin_bar_menu' ) !== 1 ) {
				return;
			}
			$user_id = get_current_user_id();
			$user_locale = get_user_meta( $user_id, 'zwt_adminlang_lang', true );
			if ( "" == $user_locale ) {
				$user_locale = get_locale();
			}

			if ( $user_locale == 'en_US' ) {
				// American English
				$lang_name = __( 'American English' );
				$flag = true;
			} else if ( $user_locale == 'en_GB' ) {
				// British English
				$lang_name = __( 'British English' );
				$flag = true;
			} else {
				$lang_name = format_code_lang( $user_locale );
			}

			$title = '<img src="' . GTP_PLUGIN_URL . 'images/flags/' . $user_locale . '.png" class="admin-flag-icon" /><span class="ab-label">' . $lang_name . '</span>';

			$initial_args = array( 'id' => 'zwt_admin_lang_switcher',
				'title' => $title,
				'parent' => 'top-secondary',
				'href' => wp_nonce_url( add_query_arg( 'lang', $user_locale ), 'zwt_changeLang-' . $user_locale . '_' . $user_id ),
				'meta' => array( 'class' => 'admin-lang-switcher' )
			);



			$wp_admin_bar->add_node( $initial_args );


			$languages = get_available_languages();
			$flag = false;
			//string strstr(string str, string occurrence [, bool before_needle])
			//echo add_query_arg('lang', 'bar' );
			if ( !empty( $languages ) ) {

				foreach ( $languages as $val ) {
					$parent = 'zwt_admin_lang_switcher';
					$code_lang = basename( $val, '.mo' );
					if ( $code_lang == $user_locale ) {
						continue;
					}

					if ( $code_lang == 'en_US' ) {
						// American English
						$lang_name = __( 'American English' );
						$flag = true;
					} else if ( $code_lang == 'en_GB' ) {
						// British English
						$lang_name = __( 'British English' );
						$flag = true;
					} else {
						$lang_name = format_code_lang( $code_lang );
					}

					$title = '<img src="' . GTP_PLUGIN_URL . 'images/flags/' . $code_lang . '.png" class="admin-flag-icon" /><span class="ab-label">' . $lang_name . '</span>';

					$args = array( 'id' => 'zwt_admin_lang_' . $code_lang,
						'title' => $title,
						'parent' => 'zwt_admin_lang_switcher',
						'href' => wp_nonce_url( add_query_arg( 'set_lang', $code_lang ), 'zwt_changeLang-' . $code_lang . '_' . $user_id ),
						'meta' => array( 'class' => 'admin-lang-switcher' )
					);

					$wp_admin_bar->add_node( $args );
				}
			}
			if ( !$flag && $user_locale !== 'en_US' ) {
				$title = '<img src="' . GTP_PLUGIN_URL . 'images/flags/en_US.png" class="admin-flag-icon" /><span class="ab-label">English</span>';

				$args = array( 'id' => 'zwt_admin_lang_en_US',
					'title' => $title,
					'parent' => 'zwt_admin_lang_switcher',
					'href' => wp_nonce_url( add_query_arg( 'set_lang', 'en_US' ), 'zwt_changeLang-en_US_' . $user_id ),
					'meta' => array( 'class' => 'admin-lang-switcher' )
				);

				$wp_admin_bar->add_node( $args );
			}

			$ua = false === strpos( $_SERVER[ 'HTTP_USER_AGENT' ], 'Chrome' ) ? '&nbsp;&nbsp;&nbsp;&nbsp;' : '';

			$wp_admin_bar->add_node( array(
				'parent' => 'zwt_admin_lang_switcher',
				'id' => 'WP_LANG_lang_child_more_langs',
				'title' => '<i class="fa fa-flag"></i>&nbsp;' . __( 'Add admin language', 'Zanto' ),
				'href' => admin_url( 'admin.php?page=zwt_manage_locales&more_langs=1' ),
				'meta' => array(
					'title' => __( 'Add admin language', 'wordpress-language' )
				)
			) );

			$wp_admin_bar->add_node( array(
				'parent' => 'zwt_admin_lang_switcher',
				'id' => 'WP_LANG_lang_options',
				'title' => '<i class="fa fa-gear"></i>&nbsp;' . __( 'Language settings', 'Zanto' ),
				'href' => admin_url( 'admin.php?page=zwt_manage_locales' ),
				'meta' => array(
					'title' => __( 'Language options', 'wordpress-language' )
				)
			) );
		}

		function process_admin_swicher() {
			if ( did_action( 'admin_init' ) !== 1 || (defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
				return;
			}


			if ( isset( $_REQUEST[ 'set_lang' ] ) ) {
				$user_id = get_current_user_id();
				$lang_code = $_REQUEST[ 'set_lang' ];
				check_admin_referer( 'zwt_changeLang-' . $lang_code . '_' . $user_id );
				update_user_meta( $user_id, 'zwt_adminlang_lang', $lang_code );
				wp_redirect( remove_query_arg( array( 'set_lang' ) ) );
				add_notice( __( 'Your personal admin Language setting has been changed and saved', 'Zanto' ) );
				exit;
			}
		}

		function user_admin_locale( $locale ) {
			$new_locale = get_user_meta($this->current_user_id, 'zwt_adminlang_lang', true);
			if ( "" != $new_locale ) {
				$locale = $new_locale;
			}
			return $locale;
		}

	}

	// end ZWT_Lang_Switcher class
}
?>