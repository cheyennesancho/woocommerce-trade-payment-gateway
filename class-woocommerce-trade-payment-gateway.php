<?php 


if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class WC_Trade_Payment_Gateway file.
 *
 * 
 */
class WC_Trade_Payment_Gateway extends WC_Payment_Gateway{


	public function __construct(){
		$this->id = 'trade_payment';
		$this->method_title = __('Trade Payment','woocommerce-trade-payment-gateway');
		$this->title = __('Trade Payment','woocommerce-trade-payment-gateway');
		$this->has_fields = true;
		$this->init_form_fields();
		$this->init_settings();
		$this->enabled = $this->get_option('enabled');
		$this->title = $this->get_option('title');
		$this->description = $this->get_option('description');
		$this->hide_text_box = $this->get_option('hide_text_box');

		$this->select_list_of_banks = get_option(
			'trade_select_list_of_banks)',
			array(
				array(
					'bank_name'      => $this->get_option( 'bank_name' ),
				),
			)
		);

		$this->customer_trade_account_number = $this->get_option('customer_trade_account_number');
		

		add_action('woocommerce_update_options_payment_gateways_'.$this->id, array($this, 
			'process_admin_options'));

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'save_bank_lists' ) );
		
		add_action( 'woocommerce_thankyou_trade_payment', array( $this, 'thankyou_page' ) );

		add_action( 'woocommerce_view_order', array( $this, 'account_view_order' ) );
	}

	/**
	 * Generate account details html.
	 *
	 * @return string
	 */
	public function generate_select_list_of_banks_html() {

		ob_start();
		$select_list_of_banks = get_option( 'trade_select_list_of_banks');
		?>
		<tr valign="top">
			<th scope="row" class="titledesc"><?php esc_html_e( 'List of Exchanges:', 'woocommerce-trade-payment-gateway' ); ?></th>
			<td class="forminp" id="trade_accounts">
				<div class="wc_input_table_wrapper">
					<table class="widefat wc_input_table sortable" cellspacing="0">
						<thead>
							<tr>
								<th class="sort">&nbsp;</th>
								<th><?php esc_html_e( 'Bank Name', 'woocommerce-trade-payment-gateway' ); ?></th>
							</tr>
						</thead>
						<tbody class="accounts">
							<?php

							//var_dump($select_list_of_banks);
							$i = -1;
							if ( $select_list_of_banks ) {
								foreach ( $select_list_of_banks as $bank ) {
									$i++;
									
									echo '<tr class="account">
										<td class="sort"></td>
										
										<td><input type="text" value="' . esc_attr( wp_unslash( $bank['bank_name'] ) ) . '" name="trade_bank_name[' . esc_attr( $i ) . ']" /></td>
									</tr>';
								}
							}
							?>
						</tbody>
						<tfoot>
							<tr>
								<th colspan="7"><a href="#" class="add button"><?php esc_html_e( '+ Add Bank', 'woocommerce-trade-payment-gateway' ); ?></a> <a href="#" class="remove_rows button"><?php esc_html_e( 'Remove selected bank(s)', 'woocommerce-trade-payment-gateway' ); ?></a></th>
							</tr>
						</tfoot>
					</table>
				</div>
				<script type="text/javascript">
					jQuery(function() {
						jQuery('#trade_accounts').on( 'click', 'a.add', function(){

							var size = jQuery('#trade_accounts').find('tbody .account').length;

							jQuery('<tr class="account">\
									<td class="sort"></td>\
									<td><input type="text" name="trade_bank_name[' + size + ']" /></td>\
								</tr>').appendTo('#trade_accounts table tbody');

							return false;
						});
					});
				</script>
			</td>
		</tr>
		<?php
		return ob_get_clean();

	}

	/**
	 * Save list of bank names table.
	 */
	public function save_bank_lists() {

		$accounts = array();

		// phpcs:disable WordPress.CSRF.NonceVerification.NoNonceVerification -- Nonce verification already handled in WC_Admin_Settings::save()
		if ( isset( $_POST['trade_bank_name'] ) ) {

			$bank_names      = wc_clean( wp_unslash( $_POST['trade_bank_name'] ) );

			foreach ( $bank_names as $i => $name ) {
				if ( ! isset( $bank_names[ $i ] ) ) {
					continue;
				}

				$accounts[] = array(
					'bank_name'      => $bank_names[ $i ],
				);
			}
		}
		// phpcs:enable

		update_option( 'trade_select_list_of_banks', $accounts );
	}


	/**
	 * Get bank details and place into a list format.
	 *
	 * @param int $order_id Order ID.
	 */
	private function customer_bank_details( $order_id ) {

		$method = get_post_meta( $order_id, '_payment_method', true );
	    if($method != 'trade_payment')
	        return;
	    $bank_name = get_post_meta( $order_id, 'trade_payment-customer_bank_selected', true );
	    $account_number = get_post_meta( $order_id, 'trade_payment-customer_account_number', true );
	    echo '<ul class="woocommerce-order-overview woocommerce-thankyou-order-details order_details">';
	    echo '<li class="woocommerce-order-overview__payment-method method">'.__( 'Bank Name' ).': <strong>' . $bank_name . '</strong></li>';
	    echo '<li class="woocommerce-order-overview__payment-method method">'.__( 'Account Number').': <strong>' . $account_number . '</strong></li>';
	    echo '</ul>';

	}

	/**
	 * Initlize form fields.
	 */
	public function init_form_fields(){
				$this->form_fields = array(
					'enabled' => array(
					'title' 		=> __( 'Enable/Disable', 'woocommerce-trade-payment-gateway' ),
					'type' 			=> 'checkbox',
					'label' 		=> __( 'Enable Trade Payment', 'woocommerce-trade-payment-gateway' ),
					'default' 		=> 'yes'
					),
					'title' => array(
						'title' 		=> __( 'Method Title', 'woocommerce-trade-payment-gateway' ),
						'type' 			=> 'text',
						'description' 	=> __( 'This controls the title', 'woocommerce-trade-payment-gateway' ),
						'default'		=> __( 'Trade Payment', 'woocommerce-trade-payment-gateway' ),
						'desc_tip'		=> true,
					),
					'description' => array(
						'title' => __( 'Customer Message', 'woocommerce-trade-payment-gateway' ),
						'type' => 'textarea',
						'css' => 'width:500px;',
						'default' => 'None of the other payment options are suitable for you? please drop us a note about your favourable payment option and we will contact you as soon as possible.',
						'description' 	=> __( 'The message which you want it to appear to the customer in the checkout page.', 'woocommerce-trade-payment-gateway' ),
					),
					'select_list_of_banks' => array(
						'type' => 'select_list_of_banks',
					),
					/*'hide_text_box' => array(
						'title' 		=> __( 'Hide The Payment Field', 'woocommerce-trade-payment-gateway' ),
						'type' 			=> 'checkbox',
						'label' 		=> __( 'Hide', 'woocommerce-trade-payment-gateway' ),
						'default' 		=> 'no',
						'description' 	=> __( 'If you do not need to show the text box for customers at all, enable this option.', 'woocommerce-trade-payment-gateway' ),
					),*/

			 );
	}
	/**
	 * Admin Panel Options
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function admin_options() {
		?>
		<h3><?php _e( 'Trade Payment Settings', 'woocommerce-trade-payment-gateway' ); ?></h3>
			<div id="poststuff">
				<div id="post-body" class="metabox-holder columns-2">
					<div id="post-body-content">
						<table class="form-table">
							<?php $this->generate_settings_html();?>
						</table><!--/.form-table-->


					</div>
					<div id="postbox-container-1" class="postbox-container">
                        <div id="side-sortables" class="meta-box-sortables ui-sortable"> 
                           

                        </div>
                    </div>
                </div>
			</div>
				<div class="clear"></div>
				<style type="text/css">
				.wptrade_button{
					background-color:#4CAF50 !important;
					border-color:#4CAF50 !important;
					color:#ffffff !important;
					width:100%;
					padding:5px !important;
					text-align:center;
					height:35px !important;
					font-size:12pt !important;
				}
				</style>
				<?php
	}
	public function process_payment( $order_id ) {
		global $woocommerce;
		$order = new WC_Order( $order_id );
		// Mark as on-hold (we're awaiting the cheque)
		$order->update_status('on-hold', __( 'Awaiting payment', 'woocommerce-trade-payment-gateway' ));
		// Reduce stock levels
		wc_reduce_stock_levels( $order_id );
		if(isset($_POST[ $this->id.'-admin-note']) && trim($_POST[ $this->id.'-admin-note'])!=''){
			$order->add_order_note(esc_html($_POST[ $this->id.'-admin-note']),1);
		}
		// Remove cart
		$woocommerce->cart->empty_cart();
		// Return thankyou redirect
		return array(
			'result' => 'success',
			'redirect' => $this->get_return_url( $order )
		);	
	}

	public function payment_fields(){

			$select_list_of_banks = get_option( 'trade_select_list_of_banks');

	    ?>

		<fieldset>
			<p class="form-row form-row-wide">
				<label for="<?php echo $this->id; ?>-customer_bank_selected"><?php echo __( 'Select Bank', 'woocommerce-trade-payment-gateway' ); ?> <span class="required">*</span></label>
				<select id="<?php echo $this->id; ?>-customer_bank_selected" data-placeholder="Select bank" class="select2" type="select" name="<?php echo $this->id; ?>-customer_bank_selected" width="100%">
					<option class=""></option>
					<?php
						if ( $select_list_of_banks ) {
						//var_dump($select_list_of_banks);
						foreach ( $select_list_of_banks as $bank ) {			

							echo '<option type="text" value="' . esc_attr( wp_unslash( $bank['bank_name'] ) ) . '"> '. esc_attr( wp_unslash( $bank['bank_name'] ) ) . '</option>';
					
							}
						}

					?>
				</select>
				<style type="text/css">
					.select2-container {width: 100%!important;}
				</style>
				<script type="text/javascript">
				jQuery(document).ready(function($){

					var data=[];
					function format(item) { return item.tag; }
					 
					$("#<?php echo $this->id; ?>-customer_bank_selected").select2({
						placeholder: "Select bank",
					    data:{ results: data, text: 'tag' },
					    formatSelection: format,
					    formatResult: format
					});
				});
				</script>
			</p>
			
			<p class="form-row form-row-wide">
				<label for="<?php echo $this->id; ?>-customer_account_number">Give Account number <span class="required">*</span></label>
				<input id="<?php echo $this->id; ?>-customer_account_number" class="input-text" type="text" required name="<?php echo $this->id; ?>-customer_account_number"/>
			</p>
			<div class="clear"></div>
			<p class="form-row form-row-wide">
				<label for="<?php echo $this->id; ?>-admin-note"><?php echo ($this->description); ?></label>
			</p>						
			<div class="clear"></div>
		</fieldset>
		<?php

	}


	/**
	 * Output for the order received page.
	 *
	 * @param int $order_id Order ID.
	 */
	public function thankyou_page( $order_id ) {

		/*if ( $this->description ) {
			echo wp_kses_post( wpautop( wptexturize( wp_kses_post( $this->description ) ) ) );
		}*/
		$this->customer_bank_details( $order_id );

	}

	/**
	 * Add content to the WC emails.
	 *
	 * @param WC_Order $order Order object.
	 * @param bool     $sent_to_admin Sent to admin.
	 * @param bool     $plain_text Email format: plain text or HTML.
	 */
	public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {

		if ( ! $sent_to_admin && 'trade_payment' === $order->get_payment_method() && $order->has_status( 'on-hold' ) ) {
			if ( $this->description ) {
				echo wp_kses_post( wpautop( wptexturize( $this->description ) ) . PHP_EOL );
			}
			$this->customer_bank_details( $order->get_id() );
		}

	}

	/**
	 * Output for the account order view page.
	 *
	 * @param int $order_id Order ID.
	 */
	public function account_view_order( $order_id ) {

		/*if ( $this->description ) {
			echo wp_kses_post( wpautop( wptexturize( wp_kses_post( $this->description ) ) ) );
		}*/
		$this->customer_bank_details( $order_id );

	}

}

