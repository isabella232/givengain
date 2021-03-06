<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
if ( ! function_exists( 'givengain_output' ) ) exit; // Exit if the required function isn't present.

/**
 * Givengain Widget Base Class
 *
 * Base widget class for Givengain.
 *
 * @package WordPress
 * @subpackage Givengain
 * @category Widgets
 * @author Matty
 * @since 1.0.0
 *
 * TABLE OF CONTENTS
 *
 * - __construct()
 * - init()
 * - widget()
 * - update()
 * - form()
 * - generate_field_by_type()
 * - generate_slideshow()
 */
class Givengain_Widget_Base extends WP_Widget {

	/* Variable Declarations */
	protected $defaults = array( 'title' => '' );
	protected $givengain_widget_cssclass;
	protected $givengain_widget_description;
	protected $givengain_widget_idbase;
	protected $givengain_widget_title;
	protected $givengain_endpoint;
	protected $givengain_fields;

	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct () {
		/* Widget variable settings. */
		$this->givengain_widget_cssclass = 'widget_givengain';
		$this->givengain_widget_description = __( 'A GivenGain widget for your site', 'givengain' );
		$this->givengain_widget_idbase = 'widget_givengain';
		$this->givengain_widget_title = __( 'GivenGain', 'givengain' );
		$this->givengain_endpoint = '';
		$this->givengain_fields = $this->get_fields();

		$this->init();
	} // End Constructor

	/**
	 * Initialize the widget.
	 * @since  1.0.0
	 * @return void
	 */
	protected function init () {
		/* Widget settings. */
		$widget_ops = array( 'classname' => $this->givengain_widget_cssclass, 'description' => $this->givengain_widget_description );

		/* Widget control settings. */
		$control_ops = array( 'width' => 250, 'height' => 350, 'id_base' => $this->givengain_widget_idbase );

		/* Create the widget. */
		$this->WP_Widget( $this->givengain_widget_idbase, $this->givengain_widget_title, $widget_ops, $control_ops );
	} // End init()

	/**
	 * widget function.
	 * @since  1.0.0
	 * @access public
	 * @param array $args
	 * @param array $instance
	 * @return void
	 */
	public function widget ( $args, $instance ) {
		$content_args = $instance;
		if ( isset( $content_args['title'] ) ) {
			unset( $content_args['title'] );
		}

		$givengain_output = $this->generate_output( $content_args );

		if ( '' == $givengain_output ) { return; }

		$html = '';

		extract( $args, EXTR_SKIP );

		/* Our variables from the widget settings. */
		$title = apply_filters('widget_title', $instance['title'], $instance, $this->id_base );

		/* Before widget (defined by themes). */
		echo $before_widget;

		/* Display the widget title if one was input (before and after defined by themes). */
		if ( $title ) {
			echo $before_title . esc_html( $title ) . $after_title;
		}

		/* Widget content. */

		// Add actions for plugins/themes to hook onto.
		do_action( $this->givengain_widget_cssclass . '_top', $content_args );

		// Load widget content here.
		$html = '';

		$html .= $givengain_output;

		echo $html;

		// Add actions for plugins/themes to hook onto.
		do_action( $this->givengain_widget_cssclass . '_bottom', $content_args );

		/* After widget (defined by themes). */
		echo $after_widget;

	} // End widget()

	/**
	 * update function.
	 * @access public
	 * @param array $new_instance
	 * @param array $old_instance
	 * @return array $instance
	 */
	public function update ( $new_instance, $old_instance ) {
		$instance = $old_instance;

		/* Strip tags for title and name to remove HTML (important for text inputs). */
		$instance['title'] = esc_html( $new_instance['title'] );

		/* Cater for new fields and preserve old fields if they aren't part of our current fields to be saved. */
		// Save fields for the current type.
		if ( 0 < count( $this->givengain_fields ) ) {
			foreach ( $this->givengain_fields as $i => $j ) {
				switch ( $j['type'] ) {
					case 'checkbox':
						if ( isset( $new_instance[$i] ) && ( $new_instance[$i] == 1 ) ) {
							$instance[$i] = (bool)intval( $new_instance[$i] );
						} else {
							$instance[$i] = false;
						}
					break;

					case 'multicheck':
						$instance[$i] = array_map( 'esc_attr', $new_instance[$i] );
					break;

					case 'text':
					case 'select':
					case 'images':
					case 'range':
						$instance[$i] = esc_attr( $new_instance[$i] );
					break;
				}
			}
		}

		// Allow child themes/plugins to act here.
		$instance = apply_filters( $this->givengain_widget_idbase . '_widget_save', $instance, $new_instance, $this );

		return $instance;
	} // End update()

   /**
    * form function.
    *
    * @since  1.0.0
    * @access public
    * @param array $instance
    * @return void
    */
   public function form ( $instance ) {
		/* Set up some default widget settings. */
		/* Make sure all keys are added here, even with empty string values. */
		$defaults = (array)$this->defaults;

		// Allow child themes/plugins to filter here.
		$defaults = apply_filters( $this->givengain_widget_idbase . '_widget_defaults', $defaults, $this );

		$instance = wp_parse_args( $instance, $defaults );
?>
		<!-- Widget Title: Text Input -->
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title (optional):', 'givengain' ); ?></label>
			<input type="text" name="<?php echo $this->get_field_name( 'title' ); ?>"  value="<?php echo $instance['title']; ?>" class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" />
		</p>
		<?php
			if ( 0 < count( $this->givengain_fields ) ) {
				foreach ( $this->givengain_fields as $k => $v ) {
		?>
		<p>
			<?php
				$field_label = '<label for="' . esc_attr( $k ) . '">' . $v['name'] . '</label>' . "\n";
				if ( 'checkbox' != $v['type'] ) { echo $field_label; } // Display the label first if the field isn't a checkbox.
				$this->generate_field_by_type( $v['type'], $v['args'], $instance );
				if ( 'checkbox' == $v['type'] ) { echo $field_label; } // Display the label last if the field is a checkbox.
			?>
		</p>
		<?php
				}
			}
		?>
<?php
		// Allow child themes/plugins to act here.
		do_action( $this->givengain_widget_idbase . '_widget_settings', $instance, $this );

	} // End form()

	/**
	 * Generate a field from the settings API based on a provided field type.
	 * @since  1.0.0
	 * @param  string $type The type of field to generate.
	 * @param  array $args Arguments to be passed to the field.
	 * @param  array $instance The current widget's instance.
	 * @return void
	 */
	protected function generate_field_by_type ( $type, $args, $instance ) {
		if ( is_array( $args ) && isset( $args['key'] ) ) {
			$html = '';
			switch ( $type ) {
				// Select fields.
				case 'select':
				case 'images':
				case 'range':
					$html = '<select name="' . esc_attr( $this->get_field_name( $args['key'] ) ) . '" id="' . esc_attr( $this->get_field_id( $args['key'] ) ) . '" class="widefat">' . "\n";
					foreach ( $args['data']['options'] as $k => $v ) {

						$html .= '<option value="' . esc_attr( $k ) . '"' . selected( $k, $instance[$args['key']], false ) . '>' . $v . '</option>' . "\n";
					}
					$html .= '</select>' . "\n";

					echo $html;
				break;

				// Multiple checkboxes.
				case 'multicheck':
				if ( isset( $args['data']['options'] ) && ( count( (array)$args['data']['options'] ) > 0 ) ) {
					$html = '<div class="multicheck-container">' . "\n";
					foreach ( $args['data']['options'] as $k => $v ) {
						$checked = '';
						if ( in_array( $k, (array)$instance[$args['key']] ) ) { $checked = ' checked="checked"'; }
						$html .= '<input type="checkbox" name="' . esc_attr( $this->get_field_name( $args['key'] ) ) . '[]" class="multicheck multicheck-' . esc_attr( $args['key'] ) . '" value="' . esc_attr( $k ) . '"' . $checked . ' /> ' . $v . '<br />' . "\n";
					}
					$html .= '</div>' . "\n";
					echo $html;
				}

				break;

				// Single checkbox.
				case 'checkbox':
				if ( isset( $args['key'] ) && $args['key'] != '' ) {
					$html .= '<input type="checkbox" name="' . esc_attr( $this->get_field_name( $args['key'] ) ) . '" class="checkbox checkbox-' . esc_attr( $args['key'] ) . '" value="1"' . checked( '1', $instance[$args['key']], false ) . ' /> ' . "\n";
					echo $html;
				}

				break;

				// Text input.
				case 'text':
				if ( isset( $args['key'] ) && $args['key'] != '' ) {
					$html .= '<input type="text" name="' . esc_attr( $this->get_field_name( $args['key'] ) ) . '" class="input-text input-text-' . esc_attr( $args['key'] ) . ' widefat" value="' . esc_attr( $instance[$args['key']] ) . '" /> ' . "\n";
					echo $html;
				}

				break;
			}
		}
	} // End generate_field_by_type()

	/**
	 * Generate the HTML for this widget.
	 * @since  1.0.0
	 * @return string The generated HTML.
	 */
	protected function generate_output ( $instance ) {
		$instance['echo'] = false;
		$html = givengain_output( $this->givengain_endpoint, $instance );
		return $html;
	} // End generate_output()

	/**
	 * Return an array of field data.
	 * @since  1.0.0
	 * @return array Field data for the fields pertaining to this widget.
	 */
	protected function get_fields () {
		// Override this in the extended class.
		return array();
	} // End get_fields()
} // End Class
?>