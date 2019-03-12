<?php
/* @wordpress-plugin
 *
 * Plugin Name:       WooCommerce Trade Payment Gateway
 * Plugin URI:        https://github.com/yashkakkar/
 * Description:       The customer can select Trade as the Payment type, choose their bank, and enter the account number. 
 * Version:           1.0.0
 * WC requires at least: 2.6
 * WC tested up to: 3.5
 * Author:            Yash Kakkar
 * Author URI:        https://github.com/yashkakkar/
 * Text Domain:       woocommerce-trade-payment-gateway
 * Domain Path: /languages
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */


	if ( ! defined( 'ABSPATH' ) ) {

		exit; // Exit if accessed directly.
	}

	$active_plugins = apply_filters('active_plugins', get_option('active_plugins'));

	
	if ( wptrade_custom_payment_is_woocommerce_active() ) {

			/**
		     *  Add payment gateway.
		     */
			add_filter('woocommerce_payment_gateways', 'add_trade_payment_gateway');

			function add_trade_payment_gateway( $gateways ){
				$gateways[] = 'WC_Trade_Payment_Gateway';
				return $gateways; 
			}

			/**
		     * Include plugin payment gatway class.
		     */
			add_action('plugins_loaded', 'init_trade_payment_gateway');

			function init_trade_payment_gateway(){
				require 'class-woocommerce-trade-payment-gateway.php';
			}

			/**
		     * Include plugin languages.
		     */
			add_action( 'plugins_loaded', 'trade_payment_load_plugin_textdomain' );

			function trade_payment_load_plugin_textdomain() {
			  load_plugin_textdomain( 'woocommerce-trade-payment-gateway', FALSE, basename( dirname( __FILE__ ) ) . '/languages/' );
			}


			
			
			
		    /**
		     * Output for the order received page.
		     */
		    add_action('woocommerce_checkout_process', 'process_custom_payment');

			function process_custom_payment(){

			    if($_POST['payment_method'] != 'trade_payment')
			        return;

			    if( !isset($_POST[ 'trade_payment-customer_bank_selected' ]) || empty($_POST[ 'trade_payment-customer_bank_selected' ]) )
			        wc_add_notice( __( 'Please add Select your bank', 'trade_payment' ), 'error' );

			    if( !isset($_POST[ 'trade_payment-customer_account_number']) || empty($_POST[ 'trade_payment-customer_account_number']) )
			        wc_add_notice( __( 'Please add Account number', 'trade_payment' ), 'error' );
			}


			/**
			 * Update the order meta with field value
			 */	
			add_action( 'woocommerce_checkout_update_order_meta', 'custom_payment_update_order_meta' );

			function custom_payment_update_order_meta( $order_id ) {

			    if($_POST['payment_method'] != 'trade_payment')
			        return;
				   // echo "<pre>";
				   // var_dump($_POST);
				   // echo "</pre>";
				   // exit();
			    update_post_meta( $order_id, 'trade_payment-customer_bank_selected', $_POST['trade_payment-customer_bank_selected'] );
			    update_post_meta( $order_id, 'trade_payment-customer_account_number', $_POST[ 'trade_payment-customer_account_number'] );
			}

			/**
			 * Display field value on the order edit page
			 */
			add_action( 'woocommerce_admin_order_data_after_shipping_address', 'custom_checkout_field_display_admin_order_meta', 10, 1 );
			
			function custom_checkout_field_display_admin_order_meta($order){

			    $method = get_post_meta( $order->id, '_payment_method', true );
			    if($method != 'trade_payment')
			        return;

			    $bank_name = get_post_meta( $order->id, 'trade_payment-customer_bank_selected', true );
			    $account_number = get_post_meta( $order->id, 'trade_payment-customer_account_number', true );
			    echo "<h3>";
			    echo  __('Trade Payment Info','woocommerce-trade-payment-gateway');
			    echo "</h3>";
			    echo '<p><strong>'.__( 'Bank Name' ).':</strong> ' . $bank_name . '</p>';
			    echo '<p><strong>'.__( 'Account Number').':</strong> ' . $account_number . '</p>';
			}
	}


	/**
	 * @return bool
	 */
	function wptrade_custom_payment_is_woocommerce_active()
	{
		$active_plugins = (array) get_option('active_plugins', array());

		if (is_multisite()) {
			$active_plugins = array_merge($active_plugins, get_site_option('active_sitewide_plugins', array()));
		}

		return in_array('woocommerce/woocommerce.php', $active_plugins) || array_key_exists('woocommerce/woocommerce.php', $active_plugins);
	}