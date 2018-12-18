<div class="updated">
	<p><?php echo GTP_NAME, __( 'Requirements Alert: Please meet requirements listed below to use Zanto.', 'Zanto' ) ?></p>
	<ul class="ul-disc">
		<?php
		$zwt_unfullfilled_requirments = zwt_requirements_missing();
		foreach ( $zwt_unfullfilled_requirments as $req => $status ) {
			if ( 0 == $status ) {
				if ( 'Multisite' == $req ) {
					?>
					<li><?php _e( '<strong>Wordpress Multisite</strong> has not been activated for your installation. to use the Zanto Wordpress Translation Plugin, you 
			  need to activate the Multisite mode for Wordpress. You can learn how to do it from
			  <strong> <a href="http://codex.wordpress.org/Create_A_Network"> here</a>', 'Zanto' ) ?></strong>
					</li>
					<?php
				}
				if ( 'zwt_PHP_VERSION' == $req ) {
					?>
					<li><strong>PHP</strong><?php echo sprintf( __( '<em>(You\'re running PHP version %s): You need atleast version 5.3 to run Zanto</em>', 'Zanto' ), phpversion() ) ?></li>
					<?php
				}
				if ( 'zwt_WP_VERSION' == $req ) {
					?>
					<li><?php _e( 'You are running an old version of WordPress please upgrade your WordPress Installation to use Zanto', 'Zanto' ) ?></li>
					<?php
				}
			}
		}
		?>
	</ul>
</div>