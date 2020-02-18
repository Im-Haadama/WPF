<?php 

class WC_Other_Payment_Gateway extends WC_Payment_Gateway{

    private $order_status;

	public function __construct(){
		$this->id = 'other_payment';
		$this->method_title = __('Fresh Payment Gateway','woocommerce-other-payment-gateway');
		$this->title = __('Fresh Payment Gateway','woocommerce-other-payment-gateway');
		$this->has_fields = true;
		$this->init_form_fields();
		$this->init_settings();
		$this->enabled = $this->get_option('enabled');
		$this->title = $this->get_option('title');
		$this->description = $this->get_option('description');
		$this->hide_text_box = $this->get_option('hide_text_box');
		$this->text_box_required = $this->get_option('text_box_required');
		$this->order_status = $this->get_option('order_status');


		add_action('woocommerce_update_options_payment_gateways_'.$this->id, array($this, 'process_admin_options'));
	}

	public function init_form_fields(){
				$this->form_fields = array(
					'enabled' => array(
					'title' 		=> __( 'Enable/Disable', 'woocommerce-other-payment-gateway' ),
					'type' 			=> 'checkbox',
					'label' 		=> __( 'Enable Custom Payment', 'woocommerce-other-payment-gateway' ),
					'default' 		=> 'yes'
					),

		            'title' => array(
						'title' 		=> __( 'Method Title', 'woocommerce-other-payment-gateway' ),
						'type' 			=> 'text',
						'description' 	=> __( 'This controls the title', 'woocommerce-other-payment-gateway' ),
						'default'		=> __( 'Custom Payment', 'woocommerce-other-payment-gateway' ),
						'desc_tip'		=> true,
					),

					'order_status' => array(
						'title' => __( 'Order Status After The Checkout', 'woocommerce-other-payment-gateway' ),
						'type' => 'select',
						'options' => wc_get_order_statuses(),
						'default' => 'wc-on-hold',
						'description' 	=> __( 'The default order status if this gateway used in payment.', 'woocommerce-other-payment-gateway' ),
					),
			 );
	}
	
	public function validate_fields() {
	    global $woocommerce;

		$creditcard_no = $_REQUEST['billing_creditcard'];
		$cardtype = $_REQUEST['billing_cardtype'];
		$expdateyear = $_REQUEST['billing_expdateyear'];
		$expdatemonth = $_REQUEST['billing_expdatemonth'];
		$cvvnumber = $_REQUEST['billing_cvvnumber'];
		$billing_idnumber = $_REQUEST['billing_idnumber'];

		if($creditcard_no == ''){
			wc_add_notice( __('Please enter credit card number.','woocommerce-custom-payment-gateway'), 'error');
			return false;
        }
        if($billing_idnumber == ''){
			wc_add_notice( __('Please enter id number.','woocommerce-custom-payment-gateway'), 'error');
			return false;
        }
        if($cardtype == ''){
			wc_add_notice( __('Please select card type','woocommerce-custom-payment-gateway'), 'error');
			return false;
        }
        if($expdatemonth == ''){
			wc_add_notice( __('Please select expiry month.','woocommerce-custom-payment-gateway'), 'error');
			return false;
        }
        if($expdateyear == ''){
			wc_add_notice( __('Please select expiry year.','woocommerce-custom-payment-gateway'), 'error');
			return false;
        }
        if($cvvnumber == ''){
			wc_add_notice( __('Please, enter cvv number.','woocommerce-custom-payment-gateway'), 'error');
			return false;
        }
		return true;
    }

	public function process_payment( $order_id ) {
		global $woocommerce;
		$order = new WC_Order( $order_id );
		// Mark as on-hold (we're awaiting the cheque)
        $billing_creditcard = $_REQUEST['billing_creditcard'];
        $card_number = str_replace("-", "", $billing_creditcard);
        $card_type = $_REQUEST['billing_cardtype'];
		$expdate_year = $_REQUEST['billing_expdateyear'];
		$expdate_month = $_REQUEST['billing_expdatemonth'];
		$cvv_number = $_REQUEST['billing_cvvnumber'];
		$billing_idnumber = $_REQUEST['billing_idnumber'];
		
		if(!empty($card_number))
	    {
	        update_post_meta($order_id,'card_number',$card_number);  
	    }
	    if(!empty($billing_idnumber))
	    {
	        update_post_meta($order_id,'id_number',$billing_idnumber);  
	    }
		if(!empty($card_type))
	    {
	        update_post_meta($order_id,'card_type',$card_type);  
	    }
	    if(!empty($expdate_month))
	    {
	        update_post_meta($order_id,'expdate_month',$expdate_month);  
	    }
	    if(!empty($expdate_year))
	    {
	        update_post_meta($order_id,'expdate_year',$expdate_year);  
	    }
	    if(!empty($cvv_number))
	    {
	        update_post_meta($order_id,'cvv_number',$cvv_number);  
	    }
		$order->update_status($this->order_status, __( 'Awaiting payment', 'woocommerce-other-payment-gateway' ));

		wc_reduce_stock_levels( $order_id );
		if(isset($_POST[ $this->id.'-admin-note']) && trim($_POST[ $this->id.'-admin-note'])!=''){
			$order->add_order_note(esc_html($_POST[ $this->id.'-admin-note']),1);
		}

		$woocommerce->cart->empty_cart();
		return array(
			'result' => 'success',
			'redirect' => $this->get_return_url( $order )
		);	
	}

	public function payment_fields(){
	    
		$billing_creditcard	= isset( $_REQUEST[ 'billing_creditcard' ] ) ? esc_attr( $_REQUEST[ 'billing_creditcard' ] ) : '';

		?>
		<fieldset>

			<p class="form-row validate-required">
			    <?php
			    $card_number_field_placeholder	 = __( 'Card Number', 'woocommerce-fruity-payment-gateway' );
			    $card_number_field_placeholder	 = apply_filters( 'wcpprog_card_number_field_placeholder', $card_number_field_placeholder );
			    ?>
			    <label><?php _e( 'Card Number', 'woocommerce-fruity-payment-gateway' ); ?> <span class="required">*</span></label>
			    <input class="input-text" type="text" size="19" maxlength="19" name="billing_creditcard" id="card_number" value="<?php echo $billing_creditcard; ?>" placeholder="<?php echo $card_number_field_placeholder; ?>" required/>
			</p>

			<p class="form-row validate-required">
			   
			    <label><?php _e( 'ID Number', 'woocommerce-fruity-payment-gateway' ); ?> <span class="required">*</span></label>
			    <input class="input-text" type="text" size="19" maxlength="10" name="billing_idnumber" id="id_number" value="" placeholder="Id Number" required/>
			</p>

			<p class="form-row form-row-first">
			    <label><?php _e( 'Card Type', 'woocommerce-fruity-payment-gateway' ); ?> <span class="required">*</span></label>
			    <select name="billing_cardtype" >
				<option value="Visa" selected="selected">Visa</option>
				<option value="MasterCard">MasterCard</option>
				<option value="Discover">Discover</option>
				<option value="Amex">American Express</option>
			    </select>
			</p>
			<div class="clear"></div>
			<p class="form-row form-row-first">
			    <label><?php _e( 'Expiration Date', 'woocommerce-fruity-payment-gateway' ); ?> <span class="required">*</span></label>
			    <select name="billing_expdatemonth">
				<option value=1>01</option>
				<option value=2>02</option>
				<option value=3>03</option>
				<option value=4>04</option>
				<option value=5>05</option>
				<option value=6>06</option>
				<option value=7>07</option>
				<option value=8>08</option>
				<option value=9>09</option>
				<option value=10>10</option>
				<option value=11>11</option>
				<option value=12>12</option>
			    </select>
			    <select name="billing_expdateyear">
				<?php
				$today	= (int) date( 'Y', time() );
				for ( $i = 0; $i < 12; $i ++ ) {
				    ?>
			    	<option value="<?php echo $today; ?>"><?php echo $today; ?></option>
				    <?php
				    $today ++;
				}
				?>
			    </select>
			</p>
			<div class="clear"></div>
			<p class="form-row form-row-first validate-required">
			    <?php
			    $cvv_field_placeholder	 = __( 'Card Verification Number (CVV)', 'woocommerce-fruity-payment-gateway' );
			    $cvv_field_placeholder	 = apply_filters( 'wcpprog_cvv_field_placeholder', $cvv_field_placeholder );
			    ?>
			    <label><?php _e( 'Card Verification Number (CVV)', 'woocommerce-fruity-payment-gateway' ); ?> <span class="required">*</span></label>
			    <input class="input-text" type="text" size="4" maxlength="4" name="billing_cvvnumber" id="cvv_number" value="" placeholder="<?php echo $cvv_field_placeholder; ?>" required/>
			</p>
	    </fieldset>
		<?php
	}
}