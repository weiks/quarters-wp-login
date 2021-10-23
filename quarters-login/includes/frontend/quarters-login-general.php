<?php
/**
 * Quarters Login Settings.
 *
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

if( class_exists( 'Quarters_Login_General' ) ){
	return new Quarters_Login_General();
}

class Quarters_Login_General{

	/**
	 * Ql Settings
	 */
	public $ql_settings = array();

	public $environment = 'live';

	public $live_url = 'https://www.poq.gg/';

	public $live_api_url = 'https://www.poq.gg/api/';

	public $sandbox_url = 'https://sandbox.poq.gg/';

	public $sandbox_api_url = 'https://api.sandbox.pocketfulofquarters.com/';

	/**
	 * Hook in tabs.
	 */
	public function __construct() {

		if( empty( $this->ql_settings ) ){
			$this->get_ql_settings();
		}

		$this->environment = ( isset( $this->ql_settings['app_environment'] ) && $this->ql_settings['app_environment'] ) ? 'sandbox' : 'live';

		add_action( 'wp_enqueue_scripts', array( $this, 'quartets_login_scripts' ) );
		add_action( 'woocommerce_login_form_end', array( $this, 'quartets_login_button' ) );

		//Quarters Redirect page shortcode
		add_shortcode( 'ql_setup_account', array( $this, 'ql_setup_account_shortcode' ) );

		//Quarters Login AJAX Request
		add_action( 'wp_ajax_nopriv_ql_get_access_token', array( $this, 'ql_get_access_token' ) );
		add_action( 'wp_ajax_nopriv_ql_get_userdata', array( $this, 'ql_get_userdata' ) );
	}

	public function quartets_login_button(){
		$login_btn_url = $this->get_auth_url();
		if( $login_btn_url ){
			?>
			<p>Login with Quarters</p>
			<div class="ql-login-button">
				<a href="<?php echo $login_btn_url; ?>" class="ql-button button"><img src="<?php echo QUARTERS_LOGIN_URL . 'assets/images/quarters-logo.png'; ?>"> Login with Quarters</a>
			</div>
			<?php
		}
	}

	private function get_ql_settings(){
		$this->ql_settings = get_option( 'ql_settings' );
		$this->set_environment();
	}

	public function quartets_login_scripts(){

		wp_enqueue_style( 'quartets_login_style', QUARTERS_LOGIN_URL . 'assets/css/quarters-style.css', null, QUARTERS_LOGIN_VERSION, 'all' );

		wp_enqueue_script( 'jquery' );
		//wp_enqueue_script( 'quartets_login_js', 'https://raw.githubusercontent.com/weiks/quarters-js/master/lib/Quarters.min.js', array('jquery'), QUARTERS_LOGIN_VERSION );

		wp_register_script( 'quartets_general_js', QUARTERS_LOGIN_URL . 'assets/js/quarters-general.js', array('jquery'), QUARTERS_LOGIN_VERSION );

		if( empty( $this->ql_settings ) ){
			$this->get_ql_settings();
		}

		wp_localize_script( 'quartets_general_js', 'ql_data', array( 'ajax_url' => admin_url( 'admin-ajax.php' ), 'ql_settings' => $this->ql_settings, 'my_account_url' => get_permalink( get_option( 'woocommerce_myaccount_page_id' ) ) ) );

		wp_enqueue_script( 'quartets_general_js' );
	}

	private function set_environment(){
		$this->environment = ( isset( $this->ql_settings['app_environment'] ) && $this->ql_settings['app_environment'] ) ? 'sandbox' : 'live';
	}

	private function environment_url(){
		if( empty( $this->ql_settings ) ){
			$this->get_ql_settings();
		}

		return ( $this->environment == 'live' ) ? $this->live_url : $this->sandbox_url;
	}

	private function api_environment_url(){
		if( empty( $this->ql_settings ) ){
			$this->get_ql_settings();
		}

		return ( $this->environment == 'live' ) ? $this->live_api_url : $this->sandbox_api_url;
	}

	private function get_ql_redirect_page(){
		if( empty( $this->ql_settings ) ){
			$this->get_ql_settings();
		}

		if( !isset( $this->ql_settings ) && $this->ql_settings['app_url'] == '' ){
			return false;
		}

		return $this->ql_settings['app_url'];
	}

	private function get_ql_available_scopes(){
		$scope = array(
			'email',
			'identity',
			'wallet',
			'transactions',
			'events'
		);
		
		return implode( " ", $scope );
	}

	private function authorize_data(){
		if( empty( $this->ql_settings ) ){
			$this->get_ql_settings();
		}

		if( !isset( $this->ql_settings ) && $this->ql_settings['client_id'] == '' ){
			return false;
		}

		$redirect_page = $this->get_ql_redirect_page();
		/*$redirect_page = get_permalink();*/

		$scope = $this->get_ql_available_scopes();

		if( ! $redirect_page || ! $scope ){
			return false;
		}

		return 'response_type=code&client_id=' . $this->ql_settings['client_id'] . '&redirect_uri=' . $redirect_page . '&scope=' . $scope;

	}

	private function get_auth_url(){
		$authorize_data = $this->authorize_data();
		if( ! $authorize_data ){
			return false;
		}

		return $this->environment_url() . 'oauth2/authorize?' . $authorize_data;
	}

	public function ql_setup_account_shortcode(){
		wp_nonce_field( 'ql_intermediate_page_nonce', 'ql_intermediate_page_nonce' );
	}


	public function ql_get_access_token(){
		if( empty( $this->ql_settings ) ){
			$this->get_ql_settings();
		}
		$send_data = array();
		$flag = false;
		
		$data = array(
			'client_id' => $this->ql_settings['client_id'],
			'client_secret' => $this->ql_settings['client_secret'],
			'grant_type' => 'authorization_code',
			'code' => $_POST['code'],
			'redirect_uri' => $this->ql_settings['app_url']
		);

		//$json_data = json_encode( $data );

		$curl_headers = array( "Content-Type: application/x-www-form-urlencoded" );
		$url = $this->api_environment_url() . 'oauth2/token';

		$curl = curl_init( $url );
		curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $curl, CURLOPT_HTTPHEADER, $curl_headers );
		curl_setopt( $curl, CURLOPT_POST, true );
		curl_setopt( $curl, CURLOPT_POSTFIELDS, http_build_query( $data ) );
		curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, false );
		curl_setopt( $curl, CURLOPT_SSL_VERIFYHOST, 0 );

		$response = curl_exec( $curl );
		$status = curl_getinfo( $curl, CURLINFO_HTTP_CODE );

		$response_data = json_decode( $response );

		if ( $status != 200 ) {
		    $flag = false;
		    $send_data['message'] = $response_data->message;
		    wc_add_notice( apply_filters( 'login_errors', 'There is something wrong.' ), 'error' );
			//do_action( 'woocommerce_login_failed' );
		} else {
			$flag = true;
			$send_data['refresh_token'] = $response_data->refresh_token;
			$send_data['access_token'] = $response_data->access_token;
			$send_data['expires_in'] = $response_data->expires_in;
		}

		curl_close( $curl );
		wp_send_json( array( 'flag' => $flag, 'data' => $send_data ) );

	}

	public function ql_get_userdata(){
		$flag = false;
		$data = array();

		$url = $this->api_environment_url() . 'v1/users/me';
		$curl = curl_init( $url );
		$curl_headers = array( 'Authorization: Bearer '.$_POST['authorization'] );
		curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $curl, CURLOPT_HTTPHEADER, $curl_headers );
		curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, false );
		curl_setopt( $curl, CURLOPT_SSL_VERIFYHOST, 0 );
		$response = curl_exec( $curl );
		$status = curl_getinfo( $curl, CURLINFO_HTTP_CODE );

		$response_data = json_decode( $response );
		
		$my_account_url = get_permalink( get_option( 'woocommerce_myaccount_page_id' ) );
		if( !$my_account_url ){
			$my_account_url = home_url();
		}

		if ( $status == 200 ) {
			if( email_exists( $response_data->email ) ){
				$user = get_user_by( 'email', $response_data->email );
				wp_set_current_user( $user->ID );
				wp_set_auth_cookie( $user->ID );
				update_user_meta( $user->ID, 'quarters_access-token', $_POST['authorization'] );
				update_user_meta( $user->ID, 'quarters_refresh-token', $_POST['reauthorization'] );
				
				$flag = true;
				$data['message'] = 'Login Successfully.';
			} else {
				//create new user
				$user_email = $response_data->email;
				$quarters_id = $response->id;
				$quarters_user_id = $response->userId;
				$user_pass = wp_generate_password();
				$new_customer = wp_insert_user(
					array(
						'user_login'		=> $user_email,
						'user_email'		=> $user_email,
						'user_pass'	 		=> $user_pass,
						'user_registered'	=> date( 'Y-m-d H:i:s' ),
						'role'				=> apply_filters( 'ql_new_user_role', 'customer' ),
					)
				);

				if( $new_customer && !is_wp_error( $new_customer ) ) {
					$flag = true;
					$data['message'] = $response_data;
					update_user_meta( $new_customer, 'quarters_id', $quarters_id );
					update_user_meta( $new_customer, 'quarters_user_id', $quarters_user_id );
					update_user_meta( $new_customer, 'quarters_access-token', $_POST['authorization'] );
					update_user_meta( $new_customer, 'quarters_refresh-token', $_POST['reauthorization'] );
					wp_set_current_user( $new_customer );
					wp_set_auth_cookie( $new_customer );
				} else {
					$flag = true;
					$data['message'] = 'User account not created.';
					wc_add_notice( apply_filters( 'login_errors', 'There is something wrong.' ), 'error' );
				}
			}
		} else {
			$flag = false;
			$data['message'] = $response_data;
			wc_add_notice( apply_filters( 'login_errors', 'There is something wrong.' ), 'error' );
		}

		wp_send_json( array( 'flag' => $flag, 'redirection' => $my_account_url ) );
	}

}

new Quarters_Login_General();
