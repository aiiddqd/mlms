<?php
if ( $_SERVER[ 'SCRIPT_FILENAME' ] == __FILE__ )
	die( 'Access denied.' );
/**
 * My Sites dashboard.
 *
 * @package WordPress
 * @subpackage Multisite
 * @since 3.0.0
 */
global $zwt_icon_url;
$title = __( 'Translation Networks' );
$parent_file = 'index.php';

get_current_screen()->add_help_tab( array(
	'id' => 'overview',
	'title' => __( 'Overview' ),
	'content' =>
	'<p>' . __( 'This screen shows an individual user all of their sites in this network, and also allows that user to set a primary site. He or she can use the links under each site to visit either the frontend or the dashboard for that site.' ) . '</p>' .
	'<p>' . __( 'Up until WordPress version 3.0, what is now called a Multisite Network had to be installed separately as WordPress MU (multi-user).' ) . '</p>'
) );

get_current_screen()->set_help_sidebar(
 '<p><strong>' . __( 'For more information:' ) . '</strong></p>' .
 '<p>' . __( '<a href="http://codex.wordpress.org/Dashboard_My_Sites_Screen" target="_blank">Documentation on My Sites</a>' ) . '</p>' .
 '<p>' . __( '<a href="http://wordpress.org/support/" target="_blank">Support Forums</a>' ) . '</p>'
);
?>
<div class="wrap">
	<div class="icon32" style='background:url("<?php echo $zwt_icon_url; ?>") no-repeat;'><br /></div>
	<h2><?php echo esc_html( $title ); ?></h2>

	<p><?php _e( 'A translation network is a group of sites or blogs that are translations of each other. These share the same Translation Network ID', 'Zanto' ) ?></p>
	<p><?php _e( 'This page shows which blogs are in which network as shown by the Translation Network ID. It also shows the blogs that are not in any network.', 'Zanto' ) ?></p>
	<p><?php _e( 'You can add a blog that is not attached to a network yet from here.', 'Zanto' ) ?>

		<?php
		if ( empty( $user_blog_ids ) ) :
			echo '<p>';
			_e( 'You must be a member of at least one site to use this page.' );
			echo '</p>';
		else :
			?>
		<form id="myblogs" action="" method="post">

			<br clear="all" />
			<script type="text/javascript">
				var zwt_pluginUrl = '<?php echo GTP_PLUGIN_URL; ?>'
			</script>
			<table class="widefat fixed right-border" >
				<thead>
					<tr>
						<th><?php _e( 'Blog Name', 'Zanto' ) ?></th>
						<th><?php _e( 'Blog Language', 'Zanto' ) ?></th>
						<th><?php _e( 'Translation Network ID', 'Zanto' ) ?></th>
						<th><?php _e( 'Translation Network Admin', 'Zanto' ) ?></th>
						<th><?php _e( 'Action', 'Zanto' ) ?></th>
					</tr>
				</thead>


				<?php
				$c = 'not';
				?>

				<?php foreach ( $trans_blog as $user_blog ): ?>
					<?php
					$c = $c == 'alternate' ? ' not' : 'alternate';
					$s = 'border-right: 1px solid #ccc;';
					$blog_info = get_blog_details( $user_blog[ 'blog_id' ] );
					$trans_admin = get_trans_network_admin( $user_blog[ 'trans_id' ] );
					?>
					<tr class='<?php echo $c ?>'>	

						<td >
							<strong><?php echo $blog_info->blogname; ?></strong>
							<p><a href="<?php echo esc_url( get_home_url( $blog_info->blog_id ) ); ?>"> <?php _e( 'Visit' ); ?> </a> | <a href="<?php echo esc_url( get_admin_url( $blog_info->blog_id ) ); ?>"> <?php _e( 'Dashboard' ); ?> </a></p>
						</td>
						<td><?php echo format_code_lang( $user_blog[ 'lang_code' ] ), ' (', $user_blog[ 'lang_code' ], ')'; ?></td>
						<td><?php echo $user_blog[ 'trans_id' ]; ?> </td>
						<td><?php echo get_trans_network_admin( $user_blog[ 'trans_id' ] )->display_name; ?> </td>
						<td> <a href="" title="<?php _e( 'Remove &nbsp;' . $blog_info->blogname, 'Gama' ) ?>" id='remove_trans_blog<?php echo $blog_info->blog_id; ?>' class='button remove_from_network'><?php _e( 'Remove', 'Zanto' ) ?></a>
						</td>

					</tr>

				<?php endforeach; ?>
				<?php // Zanto is not installed on these blogs  ?>

				<?php foreach ( $no_trans_blog as $user_blog ): ?>
					<?php
					$c = $c == 'alternate' ? '' : 'alternate';
					$blog_info = get_blog_details( $user_blog );
					$c_id = $blog_info->blog_id;
					?>
					<tr class='<?php echo $c ?> zwt_add_blog'>			
						<td valign='top' >
							<strong><?php echo $blog_info->blogname; ?></strong>
							<p><a href='<?php echo esc_url( get_home_url( $c_id ) ); ?>'> <?php _e( 'Visit' ); ?> </a> | <a href='<?php echo esc_url( get_admin_url( $c_id ) ); ?>'> <?php _e( 'Dashboard' ); ?> </a></p>
						</td>
						<td valign='top' >
							<div id='1zwt_elements<?php echo $c_id ?>' class='zwt_show_dash'>-</div>
						</td>
						<td valign='top'>
							<div id='2zwt_elements<?php echo $c_id ?>' class='zwt_show_dash'>-</div>
						</td>
						<td valign='top'>
							<div id='3zwt_elements<?php echo $c_id ?>' class='zwt_show_dash'>-</div>
						</td>
						<td valign='top'>
							<div class='zwt_show_elements<?php echo $c_id ?>'>
								<a href="#" class='button secondary add_to_network' id='update_trans_blog<?php echo $c_id ?>'><?php _e( 'Add to Translation Network', 'Zanto' ) ?></a>
							</div>
						</td>

					</tr>

				<?php endforeach; ?>

			</table>
			<?php // the below fields are only visible when javascript is disabled to practice non obstractive methods  ?>
			<div id= 'zwt_remove_elements'>
				</br>

				<label> Add this Blog
					<select name="hidden_blog_ids" id="hidden_blog_ids" class="hidden_ids">
	                    <option value=""><?php _e( '- Select -', 'Zanto' ) ?></option>
						<?php foreach ( $no_trans_blog as $hidden_blog ): ?>
							<?php $h_blog_info = get_blog_details( $hidden_blog ); ?>
							<option value="<?php echo $h_blog_info->blog_id ?>"><?php echo $h_blog_info->blogname ?></option>

						<?php endforeach; ?>
	                </select>
				</label>
				<br/>	</br>
				<label> Translation Networks

					<select name="blog_trans_ids" id="zwt_trans_id" class="zwt-update-transid">
	                    <option value=""><?php _e( '- Select -', 'Zanto' ) ?></option>

						<?php foreach ( $unique_trans_ids as $u_transid ): ?>

							<option value="<?php echo $u_transid ?>"><?php echo $u_transid ?></option>

						<?php endforeach; ?>
	                </select>
				</label>
				<br/><br/>
				<label> <?php _e( 'Languages', 'Zanto' ) ?>
	                <select name="language_of_blog" id="update_lang_blog" class="zwt-update-lang">
	                    <option value=""><?php _e( '- Select -', 'Zanto' ) ?></option>
						<?php foreach ( $langs_array as $lang ): ?>
							<option value="<?php echo $lang[ 'default_locale' ] ?>"><?php echo $lang[ 'english_name' ] ?></option>
						<?php endforeach; ?>
	                </select>
				</label>

			</div>
			<input type="hidden" id="zwt_get_blogid" name="zwt_blog_id" value=" " />
			<?php wp_nonce_field( 'zwt_update_transnetwork_nonce', 'zwt_updatetrans_nonce' ); ?>

			<p>
	            <input class="button-primary"  name="zwt_add_blog_trans" value="<?php echo __( 'Save Changes', 'Zanto' ) ?>" type="submit" />
			</p>
		</form>
	<?php
	endif;
	do_action( 'zwt_menu_footer' );
	?>

</div>