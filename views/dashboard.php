<?php
/**
 * Our custom dashboard page
 */
?>
<div class="wrap about-wrap">

	<h1><?php echo sprintf( __( 'Welcome to Zanto WP Translation %s', 'Zanto' ), GTP_ZANTO_VERSION ); ?></h1>

	<div class="about-text">
		<?php _e( 'Thank you for using Zanto WP Translation! We received very few bug reports in the previous version, we expect this one to even be better!', 'Zanto' ); ?>
	</div>

	<?php
	$db = $db_support = $db_contribute = false;
	if ( isset( $_GET[ 'db_scope' ] ) ) {
		if ( $_GET[ 'db_scope' ] == 'contribute' )
			$db_contribute = true;
		else {
			$db_support = true;
		}
	} else {
		$db = true;
	}
	?>

	<h2 class="nav-tab-wrapper">
		<a href="<?php echo admin_url( 'admin.php?page=zwt_dashboard' ); ?>" class=" <?php echo ($db) ? 'nav-tab nav-tab-active' : 'nav-tab' ?> "><?php _e( 'Welcome', 'Zanto' ); ?></a>
		<a href="<?php echo admin_url( 'admin.php?page=zwt_dashboard&db_scope=contribute' ); ?>" class="<?php echo ($db_contribute) ? 'nav-tab nav-tab-active' : 'nav-tab' ?>"><?php _e( 'Contribute', 'Zanto' ); ?></a>
		<a href="<?php echo admin_url( 'admin.php?page=zwt_dashboard&db_scope=support' ); ?>" class="<?php echo ($db_support) ? 'nav-tab nav-tab-active' : 'nav-tab' ?>"><?php _e( 'Support', 'Zanto' ); ?></a>
	</h2>
	<?php if ( $db ): ?>

		<div class="feature-section col three-col">

			<div>
				<h3><?php _e( 'We need you!', 'Zanto' ); ?></h3>
				<p><?php _e( '<strong>We do our best to make this the perfect free plugin for you!</strong><br/>  You can show your support by giving us a nice review at wordpress.org.', 'Zanto' ) ?></br>

					<a class="button-primary review-zanto" href="" target="_blank" rel="nofollow"><i class="fa fa-heart"></i> <?php _e( 'Review Zanto', 'Zanto' ) ?></a>
				</p>
			</div>

			<div>
				<h3><?php _e( 'Build the community', 'Zanto' ); ?></h3>
				<p><?php _e( 'The more people use Zanto, the better it gets and the more we extend it.', 'Zanto' ) ?>
					<br><?php _e( 'Please take time and indicate that Zanto works on the link below', 'Zanto' ) ?></br>
	                <a class="button-primary review-zanto" title="zanto WordPress Theme" href="http://wordpress.org/support/view/plugin-reviews/zanto" target="_blank"><i class="fa fa-check"></i> <?php _e( 'Show it Works!', 'Zanto' ) ?></a></p>
			</div>

			<div class="last-feature">
				<h3><?php _e( 'Follow us', 'Zanto' ); ?></h3>
				<p class="zanto-follow"><a href="http://twitter.com/zantowp" target="_blank">
						<strong><?php _e( "Know and influence what's coming next!", "Zanto" ) ?></strong><br/>
						<i style="font-size:4em" class="fa fa-twitter"></i></a></p>

			</div><!-- .feature-section -->
		</div>
		<div class="clear"></div>
		<div id="extend" class="changelog">
			<h3 style="text-align:left"><?php _e( "Zanto extentions", "Zanto" ) ?></h3>

			<div class="feature-section images-stagger-right">
				<a class="" title="<?php _e( "Visit the extension's page", "Zanto" ) ?>" href="http://plugins.zanto.org" target="_blank"><i style="float: right; font-size: 19em; display: block; padding-left: 10px; text-align: center; color: rgb(157, 182, 104); padding-top: 0.3em; width: 1.6em;" class="fa fa-puzzle-piece"></i></a>
	            <h4 style="text-align: left"><?php _e( 'Take your multilingual website one step further', "Zanto" ) ?></h4></br>
	            <p style="text-align: left"><?php _e( "The Zanto extensions are an assortment of free and premium plugins developed to extend zanto and other multilingual plugins and tools you need for an international website.  They can be enabled/disabled safely without affecting your existing settings.", "Zanto" ) ?>
	            </p>
	            <p style="text-align: left"><?php _e( "These modules are designed to be simple to use for everyone. They are a good solution to add some creative customizations without needing to dive into the code.", "Zanto" ) ?>
	            </p>
	            <p style="text-align: left"><?php _e( "Zanto's extensions are installed and upgraded from your WordPress admin, like any other WordPress plugins. Well documented and easily extendable with hooks, they come with a dedicated support forum at zanto.org/support", "Zanto" ) ?>
	            </p>
	            <p style="text-align:left">    
	                <a class="button-primary review-zanto" title="<?php _e( "Visit the extension's page", "Zanto" ) ?>" href="http://plugins.zanto.org" target="_blank"><?php _e( "Visit the extension's page", "Zanto" ) ?> &raquo;</a>
	            </p>
			</div>
		</div>


		<div class="feature-section images-stagger-right">

			<table  class="widefat featured-addon">

				<thead><tr>
						<th><?php _e( 'Featured Addon:', 'Zanto' ) ?></th>
						<th>
							<i style="color:orange" class="fa fa-star"></i><i style="color:orange" class="fa fa-star"></i><i style="color:orange" class="fa fa-star"></i><i style="color:orange" class="fa fa-star"></i><i style="color:orange" class="fa fa-star"></i>
							<?php _e( 'Zanto Better Links', 'Zanto' ) ?></th>
					</tr></thead>
				<tbody><tr>
						<td><div style="margin:4em 0 0 0;">

								<a data-price-mode="single" data-variable-price="no" data-download-id="8" data-action="edd_add_to_cart" class="zwt-button" style="font-size: 1.5em;" href="http://plugins.zanto.org/downloads/zanto-better-links/?edd_action=add_to_cart&download_id=187" > <?php _e( '$19.00&nbsp;&ndash;&nbsp;Buy', 'Zanto' ) ?></a>

								<p><a href="http://plugins.zanto.org/downloads"><?php _e( 'See all Addons', 'Zanto' ) ?></a></p>
							</div>
						</td>
						<td>

							<h4><?php _e( 'Features', 'Zanto' ) ?></h4>
							<br/>
							<div class="addon-features" style="font-size:1.3em"><i class="fa fa-link"></i> <a href="http://plugins.zanto.org/downloads/zanto-better-links/"><?php _e( 'Better SEO with  pretty WordPress permalinks in the language switcher urls', 'Zanto' ) ?></a></div>
							<br/>
							<a href="http://plugins.zanto.org/downloads/zanto-better-links/"><?php _e( 'See all features ...', 'Zanto' ) ?></a>

						</td>

					</tr></tbody>
			</table>
		</div>
	<?php elseif ( $db_contribute ): ?>
		<div class="changelog">
			<h3 style="color:gray"><?php _e( 'The fragrance always remains in the hand that gives the rose.', 'Zanto' ); ?></h3>

			<div class="feature-section images-stagger-right">

				<h4><?php _e( 'Translation', 'Zanto' ); ?></h4>
				<p><?php _e( 'Are you fluent in any  language other than English? You could help many by translating Zanto to that Lanaguage. All you have to do is send an email to <a href="mailto:support@zanto.org?Subject=Zanto Plugin Translation" target="_top">support@zanto.org</a> informing us of the language you want to handle and we will mail back with a go ahead.', 'Zanto' ); ?></p>
				<a class="button" href="<?php echo GTP_PLUGIN_URL . 'languages/original_po/zanto.po' ?>"><?php _e( 'Download Zanto.po', 'Zanto' ) ?></a>
				<h4><?php _e( 'Promotion', 'Zanto' ); ?></h4>
				<p><?php _e( 'A kind gesture of rating us on <a href="https://wordpress.org/plugins/zanto/">wordpress.org</a> goes a long way to show appreciation. This creates confidence in others that Zanto works and as our community grows so does the quality of the product. User feedback is pure green energy that fuels our morale to keep developing for you. Go ahead, take some time to let us know what you think :)', 'Zanto' ); ?></p>
				<a href="https://wordpress.org/plugins/zanto/" class="button-primary"><i class="fa fa-wordpress"></i> <?php _e( 'Rate Us on Wordpress.org', 'Zanto' ) ?></a>
				<h4><?php _e( 'Extend', 'Zanto' ); ?></h4>
				<p><?php _e( 'Do you want to extend Zanto WP translation plugin? No problem, you can build your free or premium addon and we will include it in our <a href="plugins.zanto.org">plugins</a>.', 'Zanto' ); ?>
					<br/><?php _e( 'If there are hooks you would like us to add, let us know and we will add them for you.', 'Zanto' ) ?>
				</p>
			</div>
		</div>
	<?php elseif ( $db_support ): ?>
		<div class="changelog">
			<h3 style="color:gray"><?php _e( 'I am glad you asked :)', 'Zanto' ); ?></h3>

			<div class="feature-section images-stagger-right">

				<h4><?php _e( 'Support Forum', 'Zanto' ); ?></h4>
				<p><?php _e( 'For support and feature requests, we have an active <a href="http://zanto.org/support/">Support Forum here</a> where new support threads are regularly checked and serviced. ', 'Zanto' ); ?></p>

				<h4><?php _e( 'Custom Development', 'Zanto' ); ?></h4>
				<p><?php _e( 'Developing for Zanto is a full-time responsibility. As the main software is free, we are dependent on custom development to keep running. If you have Zanto or WordPress related development job, hook us up at <a href="mailto:admin@zanto.org?Subject=New project for Zanto" target="_top">admin@zanto.org</a>. No job is too small!', 'Zanto' ); ?></p>
			</div>
		</div>
	<?php else: ?>

	<?php endif; ?>
</div>
