<div class="<?php echo self::PREFIX; ?>message <?php esc_attr_e( $class ); ?>">
	<?php foreach( $mesage_array as $message) : ?>
			<p><?php echo $message; ?></p>
	<?php endforeach; ?>
</div>