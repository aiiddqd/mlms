<?php
if ( !current_user_can( 'manage_options' ) )
	wp_die( __( 'You do not have sufficient permissions to manage this page.' ) );
?>
<div class="wrap">
    <div class="icon32" style='background:url("<?php echo $zwt_icon_url; ?>") no-repeat;'><br /></div>

	<?php if ( 'incomplete' == $settings[ 'setup_status' ][ 'setup_wizard' ] ) { ?>
		<h2><?php _e( 'Zanto Translation Network Setup', 'Zanto' ); ?></h2>
		<?php } /* elseif(!(isset($_GET['stg_scope']) && $_GET['stg_scope']=='debug')) {
	  ?>
	  <h2><?php _e('Zanto Settings for this blog', 'Zanto'); ?></h2>
	  <p><?php _e('This is the Zanto Basic settings Page. Most settings here other than the "Primary Translation Language" Settings will only affect this blog.', 'Zanto') ?></p>
	  <p><?php _e('CSS put in the custom css field will be included in the header css of your webpage.', 'Zanto') ?></p>
	  <br/>
	  <?php } */ ?>


	<?php if ( 'incomplete' == $settings[ 'setup_status' ][ 'setup_wizard' ] && (!$zwt_first_install_flag) ) { /* setup wizard */ ?>
		<?php
		if ( 'two' == $settings[ 'setup_status' ][ 'setup_interface' ] ) {
			$sw_width = 45;
		} else {
			$sw_width = 99;
		}
		?>
		<div id="zwt_wizard_wrap">
			<h3><?php _e( 'Complete Installation to set up Zanto for this site', 'Zanto' ) ?></h3>
			<div id="zwt_wizard">
				<div class="zwt_wizard_step"><?php _e( '1. Add this blog to a Translation Network', 'Zanto' ) ?></div>
				<div class="zwt_wizard_step"><?php _e( '2. Finish Configuration', 'Zanto' ) ?></div>
			</div>
			<br clear="all" />
			<div id="zwt_wizard_progress"><div id="zwt_wizard_progress_bar" style="width:<?php echo $sw_width ?>%">&nbsp;
				</div></div>
		</div>
		<br />
	<?php } elseif ( 'incomplete' == $settings[ 'setup_status' ][ 'setup_wizard' ] ) { ?>
		<div id="zwt_wizard_wrap">
			<h3><?php _e( 'First time Zanto Installation', 'Zanto' ) ?></h3>
			<div id="zwt_wizard">
				<div class="zwt_wizard_step"><?php _e( '1. Complete this form to create a new multi blog Translation Network', 'Zanto' ) ?></div>
			</div>
			<br clear="all" />
			<div id="zwt_wizard_progress"><div id="zwt_wizard_progress_bar" >&nbsp;
				</div></div>
		</div>
	<?php }
	/* setup wizard */ ?>


	<?php if ( 'incomplete' == $settings[ 'setup_status' ][ 'setup_wizard' ] ) { ?>

		<?php /* Interface 1 */ ?>
		<?php if ( 'one' == $settings[ 'setup_status' ][ 'setup_interface' ] ) { ?>
			<form id="zwt_trans_network_setting1" method="post" action="<?php echo esc_url( $_SERVER[ 'REQUEST_URI' ] ) ?>">
				<?php wp_nonce_field( 'zwt_translation_setting_nonce_1', 'zwt_translation_interface_1' ); ?>
				<table class="widefat">
					<thead>
						<tr>
							<th><?php _e( 'Slect Blogs to associate', 'Zanto' ) ?></th>
							<th><?php _e( 'Assign Blog Language', 'Zanto' ) ?></th>
							<th><?php _e( 'Access Blog', 'Zanto' ) ?></th>
						</tr>
					</thead>
					<tbody>

						<?php foreach ( $user_blog_ids as $userblog_id ):
							$blog_detail_obj = get_blog_details( $userblog_id ); ?>
							<tr id="blog<?php echo $userblog_id ?>">
								<td class="zwt-blog-title">
									<label>
										<input class="zwt-select-site" id='check-select-blog<?php echo $userblog_id ?>' name='gtr_select_site_<?php echo $userblog_id ?>' type='checkbox'/>
										<strong><?php echo $blog_detail_obj->blogname ?></strong>
									</label>
								</td>
								<td>
									<select name="language_of_blog_<?php echo $userblog_id ?>" id="select-blog<?php echo $userblog_id ?>" class="zwt-select-lang">
										<option value=""><?php _e( '- Select -', 'Zanto' ) ?></option>
										<?php foreach ( $langs_array as $lang ): ?>
											<option value="<?php echo $lang[ 'default_locale' ] ?>"><?php echo $lang[ 'english_name' ] ?></option>
										<?php endforeach; ?>
									</select>
								</td>
								<td> 
									[<a href='<?php echo $blog_detail_obj->siteurl ?>' target="_blank"> Visit Blog</a>  ]
								</td>
							</tr>
						<?php endforeach; ?>

				</table>
				<p class="zwt-wizard-button">
					<?php if ( !$zwt_first_install_flag ) { ?>
						<input class="button-primary"  name="interface_1_back" value="<?php echo __( 'Back', 'Zanto' ) ?>" type="submit" />
					<?php } ?>
					<input class="button-primary zwt-wiz-submit"  name="interface_1_finish" value="<?php echo __( 'Finish', 'Zanto' ) ?>" type="submit" />
				</p>
			</form>
		<?php }
//end interface 1 ?>


		<?php /* Interface 2 */ ?>
		<?php if ( ('two' == $settings[ 'setup_status' ][ 'setup_interface' ] ) ) { ?>
			<form id="zwt_trans_network_setting2" method="post" action="<?php echo esc_url( $_SERVER[ 'REQUEST_URI' ] ) ?>" >
				<?php wp_nonce_field( 'zwt_translation_setting_nonce_2', 'zwt_translation_interface_2' ); ?>
				<table class="widefat">
					<thead>
						<tr>
							<th><?php esc_html_e( GTP_NAME ); ?> Settings</th>
						</tr>
					</thead>
					<tr>
						<td>
							<div>
								<p class="pad-10px">
									<label>
										<input  type="radio" name="zwt-setup-interface" value="1" checked="checked" />
										&nbsp;&nbsp; <?php _e( 'Make a new translation network for this blog', 'Zanto' ) ?>
									</label>

									<label style="margin-left:40px">
										<input  type="radio" name="zwt-setup-interface" value="2"/>
										&nbsp;&nbsp; <?php _e( 'Add this Blog to an existing translation network', 'Zanto' ) ?>
									</label>
								</p>
							</div>
						</td>
					</tr>
					</tbody>
				</table>
				<p class="zwt-wizard-button"><input type="submit" name="interface_2_next" id="submit" class="button-primary" value="<?php esc_attr_e( 'Next' ); ?>"  /></p>

			</form>


		<?php } //end interface 2  ?>

		<?php /* Interface 3 */ ?>
		<?php if ( 'three' == $settings[ 'setup_status' ][ 'setup_interface' ] ) { ?>
			<form form id="zwt_trans_network_setting3" method="post" action="<?php echo esc_url( $_SERVER[ 'REQUEST_URI' ] ) ?>">
				<?php wp_nonce_field( 'zwt_translation_setting_nonce_3', 'zwt_translation_interface_3' ); ?>
				<table class="widefat">
					<thead>
						<tr>
							<th><?php esc_html_e( GTP_NAME ); ?><?php _e( 'Settings', 'Zanto' ) ?></th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td>


								<p><?php _e( 'Choose Translation Network for this Blog', 'Zanto' ) ?></p>
								<table style="width: 80%">
									<tr><td>
											<div class="select-Network">
												<select name="trans_network_id" id="trans_network_name">
		<?php foreach ( $trans_blog as $trans_id => $details ): ?>  
														<option value="<?php echo $trans_id ?>"><?php echo 'Translation Network ' . $trans_id ?></option>
													<?php endforeach; ?>
												</select>
											</div>
										</td><th>
									<div id="translation_Networks">

		<?php foreach ( $trans_blog as $trans_id => $details ): ?>
											<div id="translation-network-<?php echo $trans_id; ?>" class="zwt-trans-networks alternate">
												<strong><a href="#"><?php echo __( 'Blogs in Translation Network ', 'Zanto' ) . $trans_id ?></a></strong>
												<ol>
			<?php foreach ( $details as $index => $blog_detail ): ?>
														<?php $blog_detail_obj = get_blog_details( $blog_detail[ 'blog_id' ] ) ?>
														<li><?php echo format_code_lang( $blog_detail[ 'lang_code' ] ) . ': &nbsp; ' . $blog_detail_obj->siteurl; ?></li>

			<?php endforeach; ?>
												</ol>
											</div>
		<?php endforeach; ?>

									</div>
							</th></tr>
				</table>

				<div class="select_site_language">
					<p><?php _e( 'Choose the Language of this Blog', 'Zanto' ) ?></p>
					<select name="language_of_blog" id="new_blog_lang" class="zwt-select-lang2">
		<?php foreach ( $langs_array as $lang ): ?>
							<option <?php if ( $blog_current_lang == $lang[ 'default_locale' ] ) : ?>selected="selected"<?php endif; ?> 
																								 value="<?php echo $lang[ 'default_locale' ] ?>"><?php echo $lang[ 'english_name' ] ?></option>
		<?php endforeach; ?>
					</select>
				</div>

				</td>
				</tr>
				</table>
				<p class="zwt-wizard-button">
					<input class="button-primary" name="interface_3_back" value="<?php echo __( 'Back', 'Zanto' ) ?>" type="submit" />
					<input class="button-primary zwt-wiz-submit" name="interface_3_finish" value="<?php echo __( 'Finish', 'Zanto' ) ?>" type="submit" />

				</p>

			</form>

	<?php }
//end interface 3  ?>

	<?php } //setup complete ?>

	<?php /* Interface 4 */ ?>

	<?php
	if ( 'four' == $settings[ 'setup_status' ][ 'setup_interface' ] ) {

//set active tabs
		$scope_stgs = $scope_ls = false;
		if ( isset( $_GET[ 'stg_scope' ] ) ) {
			switch ( $_GET[ 'stg_scope' ] ) {
				case 'lang_swchr':
					$scope_ls = true;
					break;
				default:
					$scope_stgs = true;
					break;
			}
		} else {
			$scope_stgs = true;
		}
		?>

		<h2 class="nav-tab-wrapper">
			<a href="<?php echo admin_url( 'admin.php?page=zwt_settings' ); ?>" class=" <?php echo ($scope_stgs) ? 'nav-tab nav-tab-active' : 'nav-tab' ?> "><?php _e( 'Zanto Blog Settings', 'Zanto' ) ?></a>
			<a href="<?php echo admin_url( 'admin.php?page=zwt_settings&stg_scope=lang_swchr' ); ?>" class="<?php echo ($scope_ls) ? 'nav-tab nav-tab-active' : 'nav-tab' ?>"><?php _e( 'Language Switcher Settings', 'Zanto' ) ?></a>
		</h2>
		<div class="menu-edit">

			<div  style="padding: 10px;">
	<?php if ( !isset( $_GET[ 'stg_scope' ] ) ) { ?>
					<form form id="zwt_trans_network_setting4" method="post" action="<?php echo esc_url( $_SERVER[ 'REQUEST_URI' ] ) ?>">
					<?php wp_nonce_field( 'zwt_translation_setting_nonce_4', 'zwt_translation_interface_4' ); ?>
						<input type="hidden" name="ztm_active" id="ztm_active" value="<?php echo ($tm_active) ? 1 : 0; ?>">
						<table class="form-table">
		<?php do_action( 'zwt_menu_main_start' ); ?>
							<tr valign="top">
								<th width="33%" scope="row"><?php _e( 'Primary Translation Language', 'Zanto' ) ?></th>
								<td>
									<select name="primary_trans_lang_blog" id="primary-lang">
										<option value=''> - Select - </option>
		<?php foreach ( $blog_trans_network as $index => $blog_details ): ?>
											<option value="<?php echo $blog_details[ 'blog_id' ] ?>" <?php selected( $c_primary_blog_lang, $blog_details[ 'blog_id' ] ); ?>><?php echo format_code_lang( $blog_details[ 'lang_code' ] ), ' (', $blog_details[ 'lang_code' ], ')' ?></option>
										<?php endforeach; ?>
									</select>
									<p class="description"><?php _e( 'This is the Language of the blog from which all other languages will will be translated from.', 'Zanto' ) ?></p>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php _e( 'Language URL Format' ) ?></th>
								<td>
									<fieldset><legend class="screen-reader-text"><span><?php _e( 'Language URL Format' ) ?></span></legend>
										<label title="<?php _e( 'Dont change', 'Zanto' ) ?>">

											<input  type="radio" name="zwt_url_format" value="0" <?php checked( $settings[ 'translation_settings' ][ 'lang_url_format' ], '0' ); ?> />
											<span><?php _e( 'Don\'t add anything to the URL', 'Zanto' ) ?><span>
													</label>
													<br/>
													<label title="<?php _e( 'Add language to direcories url', 'Zanto' ) ?>">
														<input  type="radio" name="zwt_url_format"  <?php echo($rewrite_on) ? '' : 'disabled="true"' ?> value="1" <?php checked( $settings[ 'translation_settings' ][ 'lang_url_format' ], '1' ); ?> />
														<span><?php echo sprintf( __( 'Add language to Directories URL e.g %s/%s/', 'Zanto' ), zwt_home_url(), $c_blog_lang_code ); ?>
													</label>
													<br>
													<label title =<?php _e( 'Add language parameter to url', 'Zanto' ) ?> >
														<input  type="radio" name="zwt_url_format" <?php echo($rewrite_on) ? 'disabled="true"' : '' ?> value="2" <?php checked( $settings[ 'translation_settings' ][ 'lang_url_format' ], '2' ); ?> />
														<span><?php echo sprintf( __( 'Add language Parameter to URL e.g %s/?lang=%s', 'Zanto' ), zwt_home_url(), $c_blog_lang_code ) ?></span>

													</label>
													<p><a href=" http://zanto.org/tutorial/language-url-formats/"><?php _e( 'Documentation Language URL format', 'Zanto' ) ?></a>.</p>


													</fieldset>
													</td>
													</tr>
													<tr>
														<th scope="row"><?php _e( 'Multilingual SEO Options', 'Zanto' ) ?></th>
														<td>
															<fieldset><legend class="screen-reader-text"><span><?php _e( 'Multilingual SEO Options', 'Zanto' ) ?></span></legend>
																<label title="<?php _e( 'Add alternative languages in the HEAD section.', 'Zanto' ) ?>">
																	<input  type="checkbox" name="zwt_seo_headlangs" <?php checked( $add_langs2head, 1 ) ?> value="1"  />
																	<span><?php _e( 'Add alternative languages in the HEAD section.(This is applied to all blogs)', 'Zanto' ) ?> </span>
																</label>

																<p><a href="http://zanto.org/?p=292"><?php _e( 'Documentation on  alternative languages in the HEAD section', 'Zanto' ) ?></a>.</p>
														</td>
													</tr>
													<tr>
														<th scope="row"><?php _e( 'User network  Acess Options', 'Zanto' ) ?></th>
														<td>
															<fieldset><legend class="screen-reader-text"><span><?php _e( 'User network Options', 'Zanto' ) ?></span></legend>
																<label title="<?php _e( 'Auto add users to network', 'Zanto' ) ?>">
																	<input  type="radio" name="zwt_auto_user" value="1" <?php checked( $settings[ 'blog_setup' ][ 'auto_add_subscribers' ], '1' ) ?> />
																	<span><?php _e( 'Auto add users of other blogs in this translation network when they visit', 'Zanto' ) ?> </span>
																</label>
																<br/>
																<label title="<?php _e( 'Don\'t auto add users to blogs they are not registered', 'Zanto' ) ?>">
																	<input  type="radio" name="zwt_auto_user" value="0" <?php checked( $settings[ 'blog_setup' ][ 'auto_add_subscribers' ], '0' ) ?> />
																	<span><?php _e( 'Don\'t auto add users of other blogs in this translation network when they visit', 'Zanto' ) ?> </span>
																</label>
																<p><a href="http://zanto.org/?p=58"><?php _e( 'Documentation on auto adding users option', 'Zanto' ) ?></a>.</p>
														</td>
													</tr>

													<tr>
														<th scope="row"><?php _e( 'Site visibility options', 'Zanto' ) ?></th>
														<td>
															<fieldset><legend class="screen-reader-text"><span><?php _e( 'Site visibility options', 'Zanto' ) ?></span></legend>
																<label title="<?php _e( 'Include site in Translation Network language swichers', 'Zanto' ) ?>">
																	<input  type="radio" name="zwt_site_visibility" value="1" <?php checked( $settings[ 'blog_setup' ][ 'site_visibility' ], '1' ) ?> />
																	<span><?php _e( 'Include site in Translation Network language swichers', 'Zanto' ) ?> </span>
																</label>
																<br/>
																<label title="<?php _e( 'Remove site from Translation Network language swichers', 'Zanto' ) ?>">
																	<input  type="radio" name="zwt_site_visibility" value="0" <?php checked( $settings[ 'blog_setup' ][ 'site_visibility' ], '0' ) ?> />
																	<span><?php _e( 'Remove site from Translation Network language swichers', 'Zanto' ) ?> </span>
																</label>
																<p><a href="http://zanto.org/?p=60"><?php _e( 'Documentation on site visibility options', 'Zanto' ) ?></a>.</p>
														</td>
													</tr>

													<tr>
														<th scope="row"><?php _e( 'Browser Language Redirect', 'Zanto' ) ?></th>
														<td>
															<fieldset><legend class="screen-reader-text"><span><?php _e( 'Browser Language Redirect', 'Zanto' ) ?></span></legend>
																<label title="<?php _e( 'Disable browser Language Re-direct', 'Zanto' ) ?>">
																	<input  type="radio" name="zwt_browser_lang_redct" value="0" <?php checked( $settings[ 'blog_setup' ][ 'browser_lang_redirect' ], '0' ) ?> />
																	<span><?php _e( 'Disable browser Language Re-direct', 'Zanto' ) ?> </span>
																</label>
																<br/>
																<label title="<?php _e( 'Re-direct visitors to browser language if translation exists', 'Zanto' ) ?>">
																	<input  type="radio" name="zwt_browser_lang_redct" value="1" <?php checked( $settings[ 'blog_setup' ][ 'browser_lang_redirect' ], '1' ) ?> />
																	<span><?php _e( 'Re-direct visitors to browser language if translation exists', 'Zanto' ) ?> </span>
																</label>
																<br/>
																<label title="<?php _e( 'Always redirect visitors based on browser language', 'Zanto' ) ?>">
																	<input  type="radio" name="zwt_browser_lang_redct" value="2"  <?php checked( $settings[ 'blog_setup' ][ 'browser_lang_redirect' ], '2' ) ?> />
																	<span><?php _e( 'Always redirect visitors based on browser language (redirect to home page if translations are missing)', 'Zanto' ) ?> </span>
																</label>
																<br/><br/>
																<label title="<?php _e( 'Remember visitor\'s language preference for', 'Zanto' ) ?>">
																	<span><?php _e( 'Remember visitor\'s language preference for', 'Zanto' ) ?> </span>
																	<input  type="text" name="zwt_browser_lang_redct_time" value=" <?php echo $settings[ 'blog_setup' ][ 'browser_lr_time' ] ?>" size="2" />
																	<span><?php _e( 'Hours', 'Zanto' ) ?> </span>
																</label>
																<p><a href="http://zanto.org/?p=61"><?php _e( 'Documentation on browser language redirect', 'Zanto' ) ?></a>.</p>
														</td>
													</tr>
		<?php do_action( 'zwt_menu_main_end' ); ?>
													</table>
													<input type="submit" name="interface_4_save" id="submit4" class="button button-primary" value="Save Changes">
													</form>
	<?php } ?>
												<?php if ( isset( $_GET[ 'stg_scope' ] ) && $_GET[ 'stg_scope' ] == 'lang_swchr' ) { ?>
													<form form id="zwt_trans_network_setting4" method="post" action="<?php echo esc_url( $_SERVER[ 'REQUEST_URI' ] ) ?>">
													<?php wp_nonce_field( 'zwt_translation_setting_nonce_4', 'zwt_translation_interface_4' ); ?>


														<table class="form-table">
		<?php do_action( 'zwt_menu_ls_start' ); ?>
															<tr>
																<th scope="row"><?php _e( 'Languages Order', 'Zanto' ) ?></th>
																<td>
																	<div class="lang-sort">

																		<p class="description"><?php _e( 'Drag and drop to order languages', 'Zanto' ) ?></p>
																		<p>
																		<ul id="sortable">
		<?php foreach ( $blog_trans_network as $index => $blog_details ): ?>
																				<li class="button" id="<?php echo $blog_details[ 'blog_id' ] ?>">

			<?php echo $c_trans_net->get_display_language_name( $blog_details[ 'lang_code' ], get_locale() )/* format_code_lang($blog_details['lang_code']) */ ?>

																				</li>

		<?php endforeach; ?>
																		</ul>
																		</p>
																	</div>
																</td>
															</tr>

															<tr>
																<th scope="row"><?php _e( 'Content without translation', 'Zanto' ) ?></th>
																<td>
																	<fieldset><legend class="screen-reader-text"><span><?php _e( 'Content without translation', 'Zanto' ) ?></span></legend>
																		<label title="<?php _e( 'Skip language', 'Zanto' ) ?>">
																			<input  type="radio" name="zwt_no_translation" value="1" <?php checked( $settings[ 'lang_switcher' ][ 'skip_missing_trans' ], 1 ) ?> />
																			<span><?php _e( 'Skip language', 'Zanto' ) ?> </span>
																		</label>
																		<br/>
																		<label title="<?php _e( 'Link to the home page of missing translation', 'Zanto' ) ?>">
																			<input  type="radio" name="zwt_no_translation" value="0"   <?php checked( $settings[ 'lang_switcher' ][ 'skip_missing_trans' ], 0 ) ?> />
																			<span><?php _e( 'Link to the home page of missing translation', 'Zanto' ) ?> </span>
																		</label>
																</td>
															</tr>
															<tr>
																<th scope="row"><?php _e( 'Front Page Settings', 'Zanto' ) ?></th>
																<td>
																	<fieldset><legend class="screen-reader-text"><span><?php _e( 'Front Page Settings', 'Zanto' ) ?></span></legend>
																		<label title="<?php _e( 'Link front page to translation front pages', 'Zanto' ) ?>">
																			<input  type="radio" name="zwt_front_page_trans" value="0" <?php checked( $settings[ 'lang_switcher' ][ 'front_page_trans' ], 0 ) ?> />
																			<span><?php _e( 'Link front page to translation front pages', 'Zanto' ) ?> </span>
																		</label>
																		<br/>
																		<label title="<?php _e( 'Link front page static page to the translated page', 'Zanto' ) ?>">
																			<input  type="radio" name="zwt_front_page_trans" value="1"   <?php checked( $settings[ 'lang_switcher' ][ 'front_page_trans' ], 1 ) ?> />
																			<span><?php _e( 'Link front page static page to the translated page', 'Zanto' ) ?> </span>
																		</label>
																</td>
															</tr>

															<tr>
																<th scope="row"><?php _e( 'Show post translation Links', 'Zanto' ) ?></th>
																<td>
																	<fieldset><legend class="screen-reader-text"><span><?php _e( 'Show post translation Links', 'Zanto' ) ?></span></legend>
																		<label title="<?php _e( 'Yes', 'Zanto' ) ?>">
																			<input  type="checkbox" name="zwt_post_trans_links"  <?php checked( $settings[ 'lang_switcher' ][ 'alt_lang_availability' ], 1 ) ?> />
																			<span><?php _e( 'Yes', 'Zanto' ) ?> </span>
																		</label>
																		<p>
																			<label title="<?php _e( 'Position', 'Zanto' ) ?>">
																				<span><?php _e( 'Position', 'Zanto' ) ?> </span>
																				<br/>
																				<select   name="zwt_post_link_pos"  />
																				<option value="below" <?php selected( $settings[ 'lang_switcher' ][ 'post_tl_position' ], 'below' ) ?> >Below Post</option>
																				<option value="above" <?php selected( $settings[ 'lang_switcher' ][ 'post_tl_position' ], 'above' ) ?> >Above Post</option>
																				</select>
																			</label>
																		</p><p>
																			<label title="<?php _e( 'Translation Links Style', 'Zanto' ) ?>">
																				<span><?php _e( 'Translation Links Style', 'Zanto' ) ?> </span>
																				<br/>
																				<select   name="zwt_post_link_style"  />
																				<option value="0" <?php selected( $settings[ 'lang_switcher' ][ 'post_tl_style' ], '0' ) ?> ><?php _e( 'Default', 'Zanto' ) ?></option>
																				<option value="1" <?php selected( $settings[ 'lang_switcher' ][ 'post_tl_style' ], '1' ) ?> ><?php _e( 'Plain', 'Zanto' ) ?></option>
																				</select>
																			</label>
																		</p>
																		<p><?php _e( 'Text for alternative languages for posts', 'Zanto' ) ?></p>
																		<input type="text" name="zwt_post_availabitlity_text" size="50" value="<?php echo $settings[ 'lang_switcher' ][ 'post_availability_text' ] ?>"/>
																</td>
															</tr>

															<tr>
																<th scope="row"><?php _e( 'What to include in the language switcher', 'Zanto' ) ?></th>
																<td>
																	<fieldset><legend class="screen-reader-text"><span><?php _e( 'What to include in the Footer language switcher', 'Zanto' ) ?></span></legend>
																		<label title="<?php _e( 'Flag', 'Zanto' ) ?>">
																			<input  type="checkbox" name="zwt_ls_elements[flag]" <?php checked( $settings[ 'lang_switcher' ][ 'elements' ][ 'flag' ], 1 ) ?> />
																			<span><?php _e( 'Flag', 'Zanto' ) ?> </span>
																		</label>
																		<br/>
																		<label title="<?php _e( 'Native Name', 'Zanto' ) ?>">
																			<input  type="checkbox" name="zwt_ls_elements[native_name]" <?php checked( $settings[ 'lang_switcher' ][ 'elements' ][ 'native_name' ], 1 ) ?> />
																			<span><?php _e( 'Native Name', 'Zanto' ) ?> </span>
																		</label>
																		<br/>
																		<label title="<?php _e( 'Translated Name', 'Zanto' ) ?>">
																			<input  type="checkbox" name="zwt_ls_elements[translated_name]" <?php checked( $settings[ 'lang_switcher' ][ 'elements' ][ 'translated_name' ], 1 ) ?> />
																			<span><?php _e( 'Translated Name', 'Zanto' ) ?> </span>
																		</label>
																		<p><a href="http://zanto.org/?p=62"><?php _e( 'Documentation on language switcher modification', 'Zanto' ) ?></a>.</p>
																</td>
															</tr>

															<tr>
																<th scope="row"><?php _e( 'Language Switcher Appearance', 'Zanto' ) ?></th>
																<td>

		<?php
		$lang_switcher_themes = zwt_get_ls_themes();
		?>																			
																	<p>
																		<label title="<?php _e( 'Language Switcher Theme', 'Zanto' ) ?>">
																			<span><?php _e( 'Language Switcher Theme', 'Zanto' ) ?> </span>
																			<br/>
																			<select name="zwt_ls_theme" id="li_theme">
		<?php
		$active_ls = 0;
		foreach ( (array) $lang_switcher_themes as $index => $ls_files ) {

			if ( $ls_files[ 'uri' ] == $settings[ 'lang_switcher' ][ 'zwt_ls_theme' ] ) {
				$active_ls = $index;
			}
			?>
																					<option value="<?php echo esc_attr( $ls_files[ 'uri' ] ) ?>" <?php selected( $settings[ 'lang_switcher' ][ 'zwt_ls_theme' ], $ls_files[ 'uri' ], true ) ?> > <?php echo esc_html( $ls_files[ 'Name' ] ) ?></option>
																				<?php } ?>

																			</select>
																		</label>
		<?php $active_switcher = $lang_switcher_themes[ $active_ls ]; ?>
																	<p><?php printf( __( 'You can add a  language switcher from the current Language Swicher Theme any where on your site theme by inserting any of these PHP codes in the theme: %s or use the language switcher widget', 'Zanto' ), '<a title="" href="#TB_inline?width=600&height=300&inlineId=zwt_theme_codes" class="thickbox">See Codes</a>' ); ?>.</p>
																	<p><a href="http://zanto.org/?p=62"><?php _e( 'Documentation on how to create your own language switcher theme', 'Zanto' ) ?></a>.</p>

		<?php add_thickbox(); ?>
																	<div id="zwt_theme_codes" style="display:none;">
																	<?php global $zwt_ls_types ?>
																		<table class="zwt-ls-table">
																		<?php
																		foreach ( $active_switcher as $ls_item => $lang_detail ):
																			if ( empty( $lang_detail ) )
																				continue;
																			if ( $ls_item == 'uri' )
																				continue;
																			?>
																				<tr>
																					<th><?php echo $ls_item ?></th>
																					<td><?php echo $lang_detail ?></td>       
																				</tr>

		<?php endforeach; ?>
																		</table>
																		<br/>
																		<table class="widefat zwt-ls-codes">
		<?php if ( is_array( $zwt_ls_types ) && !empty( $zwt_ls_types ) ):
			foreach ( $zwt_ls_types as $type => $description ): ?>
																					<tr>
																						<th><?php echo $description ?></th><td><code class="php">&lt;?php do_action('zwt_lang_switcher', '<?php echo $type ?>'); ?&gt;</code></td>
																					</tr>

			<?php endforeach;
		else: ?>
																				<tr>
																					<th><?php _e( 'Custom Language Switcher codes', 'Zanto' ); ?></td>
																					<td><code class="php">&lt;?php do_action('zwt_lang_switcher'); ?&gt;</code></td>
																				</tr>
		<?php endif; ?>

																		</table>
																	</div>
																	</p>

																	<strong><?php _e( 'custom css', 'Zanto' ) ?> </strong><br/>
																	<textarea id="zwt_custom_css"  name="zwt_additional_css" rows="4" cols="80"><?php
		if ( !empty( $settings[ 'lang_switcher' ][ 'zwt_ls_custom_css' ] ) )
			echo $settings[ 'lang_switcher' ][ 'zwt_ls_custom_css' ];
		?></textarea>
																</td>
															</tr>

															<tr>
																<th scope="row"><?php _e( 'Footer Language Switcher Option', 'Zanto' ) ?></th>
																<td>
																	<fieldset><legend class="screen-reader-text"><span><?php _e( 'Footer Language Switcher Option', 'Zanto' ) ?></span></legend>
																		<label title="<?php _e( 'Show footer language switcher', 'Zanto' ) ?>">
																			<input  type="checkbox" name="zwt_footer_ls" <?php checked( $settings[ 'lang_switcher' ][ 'show_footer_selector' ], 1 ) ?>/>
																			<span><?php _e( 'Show footer language switcher', 'Zanto' ) ?> </span>
																		</label>
																		<br/><br/>



																</td>
															</tr>
		<?php do_action( 'zwt_menu_ls_end' ); ?>
														</table>
														<input type="hidden"  name="zwt_lang_order" id="zwt_lang_order" >
														<input type="submit" name="interface_4_save" id="submit4" class="button button-primary" value="Save Changes">
													</form>
	<?php } ?> 
												</div>
												<div id="nav-menu-footer">
													&nbsp;     
												</div>
												</div>
<?php } //end interface 4    ?>
											<?php do_action( 'zwt_menu_footer' ); ?>                                           

                                            </div> <!-- .wrap -->