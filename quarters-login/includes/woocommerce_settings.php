<?php
/**
 * Quarters Login Woocommerce Settings.
 *
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

if( class_exists( 'Quarters_Login_Woocommerce_Settings' ) ){
	return new Quarters_Login_Woocommerce_Settings();
}

/**
 * Quarters_Login_Woocommerce_Settings Class.
 */
class Quarters_Login_Woocommerce_Settings {
	
	/**
	 * Hook in tabs.
	 */
	public function __construct() {
		// Add menus.
		add_filter( 'woocommerce_currencies', array( $this, 'add_cw_currency' ) );
		add_filter( 'woocommerce_currency_symbol', array( $this, 'add_cw_currency_symbol' ), 10, 2 );
	}

	public function add_cw_currency( $cw_currency ) {
    	$cw_currency['QUARTERS'] = __( 'QUARTERS CURRECY', 'quarters_login' );
    	return $cw_currency;
	}

	public function add_cw_currency_symbol( $custom_currency_symbol, $custom_currency ) {
    	switch( $custom_currency ) {
        	case 'QUARTERS':
        		$custom_currency_symbol = 'Q';
        		break;
     	}
    	return $custom_currency_symbol;
	}
}

new Quarters_Login_Woocommerce_Settings();
?>