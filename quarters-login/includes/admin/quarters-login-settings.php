<?php
/**
 * Quarters Login Settings.
 *
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

if( class_exists( 'Quarters_Login_Settings' ) ){
	return new Quarters_Login_Settings();
}

/**
 * Quarters_Login_Settings Class.
 */
class Quarters_Login_Settings {
	
	/**
	 * Hook in tabs.
	 */
	public function __construct() {
		// Add menus.
		add_action( 'admin_menu', array( $this, 'admin_menu' ), 9 );
		add_action( 'admin_init', array( $this, 'ql_general_settings' ) );
	}

	/**
	 * Add menu items.
	 */
	public function admin_menu(){
		add_menu_page(
			__( 'Quarters Login', 'quarters_login' ),
			__( 'Quarters Login', 'quarters_login' ),
			'manage_options',
			'quarters_login_settings',
			null,
			null
		);

		$ql_settings_page = add_submenu_page( 'quarters_login_settings', __( 'Quarters Login Settings', 'quarters_login' ), __( 'Settings', 'quarters_login' ), 'manage_options', 'ql-settings', array( $this, 'ql_options_fields' ) );

		remove_submenu_page( 'quarters_login_settings', 'quarters_login_settings' );
	}

	/**
	 * Settings Fields
	 */
	public static function ql_options_fields(){
		?>
		<div class="ql-plugin-options-section">
			<?php settings_errors(); ?>
			<div class="ql-plugin-options-wrapper">
				<div class="ql-plugin-options-header">
					<h2>Quarters Login Settings</h2>
				</div>
				<div class="ql-plugin-options-body">
					<form method="post" action="options.php">
						<?php settings_fields( 'ql_settings' ); ?>
						<?php echo do_settings_sections( 'ql_settings_general' ); ?>
						<?php submit_button(); ?>
					</form>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Register Setting and section
	 */
	public function ql_general_settings(){
		if ( false == get_option( 'ql_settings' ) ) {
			add_option( 'ql_settings' );
		}

		add_settings_section(
			'ql_settings_general',
			__return_null(),
			'__return_false',
			'ql_settings_general'
		);

		$settings_fields = $this->ql_setting_fields();

		foreach ( $settings_fields as $field => $field_data ) {

			if ( empty( $field_data['id'] ) ) {
				continue;
			}

			$defaults = wp_parse_args( $field_data, array(
			    'id'            => null,
			    'name'          => '',
			    'desc'          => '',
			    'placeholder'   => null,
			    'allow_blank'   => true,
			    'readonly'      => false,
			) );

			add_settings_field(
				'ql_settings[' . $field_data['id'] . ']',
				'<div class="ql-field-label">'.$field_data['name'].'</div>',
				array( $this, 'ql_' . $field_data['type'] . '_callback' ),
				'ql_settings_general',
				'ql_settings_general',
				$defaults
			);

		}

		register_setting( 'ql_settings', 'ql_settings' );
	}

	/**
	 * Settings Fields array.
	 */
	private function ql_setting_fields(){
		return array(
			'app_environment'	=> array(
				'id'          => 'app_environment',
				'name'        => __( 'Environment', 'quarters_login' ),
				'desc'        => __( 'Check for use Sandbox Environment.', 'quarters_login' ),
				'type'        => 'checkbox',
			),
			'client_id' => array(
				'id'          => 'client_id',
				'name'        => __( 'Client Id', 'quarters_login' ),
				'desc'        => __( 'Enter Client Id here generated from Quarters.', 'quarters_login' ),
				'type'        => 'text',
				'placeholder' => __( 'Client Id', 'quarters_login' ),
			),
			'client_secret' => array(
				'id'          => 'client_secret',
				'name'        => __( 'Client Secret', 'quarters_login' ),
				'desc'        => __( 'Enter Client Secret here generated from Quarters.', 'quarters_login' ),
				'type'        => 'text',
				'placeholder' => __( 'Client Secret', 'quarters_login' ),
			),
			'server_api_token' => array(
				'id'          => 'server_api_token',
				'name'        => __( 'Server Api Token', 'quarters_login' ),
				'desc'        => __( 'Enter Server Api Token here generated from Quarters.', 'quarters_login' ),
				'type'        => 'text',
				'placeholder' => __( 'Server Api Token', 'quarters_login' ),
			),
			'app_url' => array(
				'id'          => 'app_url',
				'name'        => __( 'App Url', 'quarters_login' ),
				'desc'        => __( 'Enter App url you entered into Quarters.( The url must be same. )', 'quarters_login' ),
				'type'        => 'text',
				'placeholder' => __( 'App Url', 'quarters_login' ),
			),
		);
	}

	/**
	 * Get and saved value
	 */
	public function ql_get_option( $key = '', $default = false ) {
		$ql_options = get_option( 'ql_settings' );
		return ! empty( $ql_options[ $key ] ) ? $ql_options[ $key ] : $default;
	}

	/**
	 * Text Field Callback function
	 */
	public function ql_text_callback( $args ) {

		$ql_option = $this->ql_get_option( $args['id'] );

		if ( $ql_option ) {
			$value = $ql_option;
		} else {
			$value = isset( $args['default'] ) ? $args['default'] : '';
		}

		$name = 'name="ql_settings[' . esc_attr( $args['id'] ) . ']"';

		$html     = '<input class="large-text" type="text" id="ql_settings[' . esc_attr( $args['id'] ) . ']" ' . $name . ' value="' . esc_attr( stripslashes( $value ) ) . '" placeholder="' . esc_attr( $args['placeholder'] ) . '"/>';

		$html    .= '<label class="field-description" for="ql_settings[' . esc_attr( $args['id'] ) . ']"> '  . wp_kses_post( $args['desc'] ) . '</label>';

		echo $html;
	}

	public function ql_checkbox_callback( $args ) {
		$el_option = $this->ql_get_option( $args['id'] );

		$name = 'name="ql_settings[' . $args['id'] . ']"';

		$checked  = ! empty( $el_option ) ? checked( 1, $el_option, false ) : '';
		$html     = '<input type="hidden"' . $name . ' value="0" />';
		$html    .= '<input type="checkbox" id="ql_settings[' . $args['id'] . ']"' . $name . ' value="1" ' . $checked . ' class="' . $class . '"/>';
		$html    .= '<label class="field-description" for="ql_settings[' . $args['id'] . ']"> '  . wp_kses_post( $args['desc'] ) . '</label>';

		echo $html;
	}
}

return new Quarters_Login_Settings();

?>