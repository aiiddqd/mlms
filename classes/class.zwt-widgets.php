<?php
if ( $_SERVER[ 'SCRIPT_FILENAME' ] == __FILE__ )
	die( 'Access denied.' );

// start widget classes


class Language_Switcher_Widget extends WP_Widget {

	function __construct() {
		$widget_ops = array(
			'classname' => 'zwt_ls_widget_class',
			'description' => __( 'Multilingual language Switcher.', 'Zanto' )
		);
		$this->WP_Widget( 'zwt_multilingual_ls', __( 'Zanto Language Switcher', 'Zanto' ), $widget_ops );
	}

	function form( $instance ) {
		$defaults = array(
			'title' => __( 'Choose Language', 'Zanto' ),
			'lang_switcher_type' => ''
		);
		global $zwt_ls_types;
		if ( !isset( $zwt_ls_types ) && !is_array( $zwt_ls_types ) ) {
			return;
		}


		$instance = wp_parse_args( (array) $instance, $defaults );
		$title = strip_tags( $instance[ 'title' ] );
		$ls_type = $instance[ 'lang_switcher_type' ];
		?>
		<p><label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label> <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" /></p>

		<p>
			<label for="<?php echo $this->get_field_id( 'lang_switcher_type' ); ?>"><?php _e( 'Type:' ); ?></label>
			<select name="<?php echo $this->get_field_name( 'lang_switcher_type' ); ?>" id="<?php echo $this->get_field_id( 'lang_switcher_type' ); ?>" class="widefat">
				<?php foreach ( $zwt_ls_types as $type => $description ): ?>
					<option value="<?php echo $type ?>"<?php selected( $instance[ 'lang_switcher_type' ], $type ); ?>><?php echo $type ?></option>
				<?php endforeach; ?>
			</select>
		</p>
		<?php
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance[ 'title' ] = strip_tags( $new_instance[ 'title' ] );
		$instance[ 'lang_switcher_type' ] = $new_instance[ 'lang_switcher_type' ];
		return $instance;
	}

	function widget( $args, $instance ) {
		extract( $args );
		$ls_type = $instance[ 'lang_switcher_type' ];
		echo $before_widget;
		$title = apply_filters( 'widget_title', $instance[ 'title' ] );
		if ( !empty( $title ) ) {
			echo $before_title . $title . $after_title;
		}

		do_action( 'zwt_lang_switcher', $ls_type );

		echo $after_widget;
	}

}

function zwt_widgets_init() {
	register_widget( 'Language_Switcher_Widget' );
}

add_action( 'widgets_init', 'zwt_widgets_init' );
?>