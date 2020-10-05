<?php

//print "z=" . date('z', strtotime('2020-10-05'));
if (class_exists("WC_Payment_Gateway")) {

	class WC_Other_Payment_Gateway extends WC_Payment_Gateway {

		private $order_status;

		public function __construct() {
			$this->id           = 'other_payment';
			$this->method_title = __( 'E-fresh Payment Gateway', 'woocommerce-other-payment-gateway' );
			$this->title        = __( 'E-fresh Payment Gateway', 'woocommerce-other-payment-gateway' );
			$this->has_fields   = true;
			$this->init_form_fields();
			$this->init_settings();
			$this->enabled           = $this->get_option( 'enabled' );
			$this->title             = $this->get_option( 'title' );
			$this->description       = $this->get_option( 'description' );
			$this->hide_text_box     = $this->get_option( 'hide_text_box' );
			$this->text_box_required = $this->get_option( 'text_box_required' );
			$this->order_status      = $this->get_option( 'order_status' );


			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array(
				$this,
				'process_admin_options'
			) );
		}

		public function init_form_fields() {
			$this->form_fields = array(
				'enabled' => array(
					'title'   => __( 'Enable/Disable', 'woocommerce-other-payment-gateway' ),
					'type'    => 'checkbox',
					'label'   => __( 'Enable Custom Payment', 'woocommerce-other-payment-gateway' ),
					'default' => 'yes'
				),

				'title' => array(
					'title'       => __( 'Method Title', 'woocommerce-other-payment-gateway' ),
					'type'        => 'text',
					'description' => __( 'This controls the title', 'woocommerce-other-payment-gateway' ),
					'default'     => __( 'Custom Payment', 'woocommerce-other-payment-gateway' ),
					'desc_tip'    => true,
				),

				'order_status' => array(
					'title'       => __( 'Order Status After The Checkout', 'woocommerce-other-payment-gateway' ),
					'type'        => 'select',
					'options'     => wc_get_order_statuses(),
					'default'     => 'wc-on-hold',
					'description' => __( 'The default order status if this gateway used in payment.', 'woocommerce-other-payment-gateway' ),
				),
			);
		}

		public function validate_fields() {
			global $woocommerce;

			$creditcard_no    = $_REQUEST['billing_creditcard'];
			$cardtype         = $_REQUEST['billing_cardtype'];
			$expdateyear      = $_REQUEST['billing_expdateyear'];
			$expdatemonth     = $_REQUEST['billing_expdatemonth'];
			$billing_idnumber = $_REQUEST['billing_idnumber'];

			if ( isset( $creditcard_no ) && $creditcard_no == '' ) {
				wc_add_notice( __( 'Please enter credit card number.', 'woocommerce-custom-payment-gateway' ), 'error' );

				return false;
			}
			if ( isset( $billing_idnumber ) && $billing_idnumber == '' ) {
				wc_add_notice( __( 'Please enter id number.', 'woocommerce-custom-payment-gateway' ), 'error' );

				return false;
			}
			if ( isset( $cardtype ) && $cardtype == '0' ) {
				wc_add_notice( __( 'Please select card type', 'woocommerce-custom-payment-gateway' ), 'error' );

				return false;
			}
			if ( isset( $expdatemonth ) && $expdatemonth == '0' ) {
				wc_add_notice( __( 'Please select expiry month.', 'woocommerce-custom-payment-gateway' ), 'error' );

				return false;
			}
			if ( isset( $expdateyear ) && $expdateyear == '0' ) {
				wc_add_notice( __( 'Please select expiry year.', 'woocommerce-custom-payment-gateway' ), 'error' );

				return false;
			}

			return true;
		}

		public function process_payment( $order_id ) {
			global $woocommerce;
			$order              = new WC_Order( $order_id );
			$billing_creditcard = $_REQUEST['billing_creditcard'];
			$card_number        = str_replace( "-", "", $billing_creditcard );
			$card_type          = $_REQUEST['billing_cardtype'];
			$expdate_year       = $_REQUEST['billing_expdateyear'];
			$expdate_month      = $_REQUEST['billing_expdatemonth'];
			$billing_idnumber   = $_REQUEST['billing_idnumber'];

			if ( isset( $card_number ) && ! empty( $card_number ) ) {
				update_post_meta( $order_id, 'card_number', $card_number );
			}
			if ( isset( $billing_idnumber ) && ! empty( $billing_idnumber ) ) {
				update_post_meta( $order_id, 'id_number', $billing_idnumber );
			}
			if ( isset( $card_type ) && ! empty( $card_type ) ) {
				update_post_meta( $order_id, 'card_type', $card_type );
			}
			if ( isset( $expdate_month ) && ! empty( $expdate_month ) ) {
				update_post_meta( $order_id, 'expdate_month', $expdate_month );
			}
			if ( isset( $expdate_year ) && ! empty( $expdate_year ) ) {
				update_post_meta( $order_id, 'expdate_year', $expdate_year );
			}
			$order->update_status( $this->order_status, __( 'Awaiting payment', 'woocommerce-other-payment-gateway' ) );

			wc_reduce_stock_levels( $order_id );
			if ( isset( $_POST[ $this->id . '-admin-note' ] ) && trim( $_POST[ $this->id . '-admin-note' ] ) != '' ) {
				$order->add_order_note( esc_html( $_POST[ $this->id . '-admin-note' ] ), 1 );
			}

			$woocommerce->cart->empty_cart();

			return array(
				'result'   => 'success',
				'redirect' => $this->get_return_url( $order )
			);
		}

		public function payment_fields() {
			global $wpdb;
			$billing_creditcard = isset( $_REQUEST['billing_creditcard'] ) ? esc_attr( $_REQUEST['billing_creditcard'] ) : '';
			$valid_credit_info = false;
			if ( is_user_logged_in() ) {
				$current_user_id = wp_get_current_user()->ID;
				$card_number      = $wpdb->get_var( "SELECT card_number FROM im_payment_info WHERE user_id = $current_user_id" );

				$valid_month     = (int) $wpdb->get_var( "SELECT exp_date_month FROM im_payment_info WHERE user_id = $current_user_id" );
				$valid_year      = (int) $wpdb->get_var( "SELECT exp_date_year FROM im_payment_info WHERE user_id = $current_user_id" );

				$this_month = (int) date('m');
				$this_year = (int) date('Y');

                if (! (($valid_year > $this_year) or (($valid_year == $this_year) and ($valid_month >= $this_month)))){
                    print __("Previous card expired");
                }

				$id_number =  $wpdb->get_var( "SELECT id_number FROM im_payment_info WHERE user_id = $current_user_id" );
				//$valid_id = (! class_exists('Israel_Shop')) or
                $valid_id = Israel_Shop::ValidID($id_number);

				$token = get_usermeta($current_user_id, 'credit_token');
//				MyLog("token: $token");
//				MyLog(strstr($card_number, 'XX'));

                MyLog("vid= " . $valid_id . " " . $valid_year . " " . $valid_month . " " . $card_number);
				$valid_credit_info = ($valid_id and
				     (($valid_year > $this_year) or (($valid_year == $this_year) and ($valid_month >= $this_month))) and
                     (strlen($token) >= 10 or ! strstr($card_number, 'XX')));
				MyLog("vci=" . $valid_credit_info);
			}

			global $l10n;

//			if (get_user_id() == 1) var_dump($l10n['finance']);

			if ( $valid_credit_info ) { ?>
                <fieldset>
                    <p class="form-row validate-required">
						<?php
						$card_number_field_placeholder = __( 'Card Number ', 'woocommerce-fruity-payment-gateway' );
						?>
                        <label><?php print __( 'Card Number', 'finance' ) . " " .
                                           substr($card_number, -4); ?></label>
                    </p>
                </fieldset>
			<?php } else {
				?>
                <fieldset>

                    <p class="form-row validate-required">
						<?php
						$card_number_field_placeholder = __( 'Card Number', 'woocommerce-fruity-payment-gateway' );
						$card_number_field_placeholder = apply_filters( 'wcpprog_card_number_field_placeholder', $card_number_field_placeholder );
						?>
                        <label><?php _e( 'Card Number', 'woocommerce-fruity-payment-gateway' ); ?> <span
                                    class="required">*</span></label>
                        <input class="input-text" type="text" size="19" maxlength="19" name="billing_creditcard"
                               id="card_number" value="<?php echo $billing_creditcard; ?>"
                               placeholder="<?php echo $card_number_field_placeholder; ?>" required/>
                    </p>

                    <p class="form-row validate-required">

                        <label><?php _e( 'ID Number', 'woocommerce-fruity-payment-gateway' ); ?> <span class="required">*</span></label>
                        <input class="input-text" type="text" size="19" maxlength="10" name="billing_idnumber"
                               id="id_number" value="" placeholder="Id Number" required/>
                    </p>

                    <p class="form-row form-row-first">
                        <label><?php _e( 'Card Type', 'woocommerce-fruity-payment-gateway' ); ?> <span class="required">*</span></label>
                        <select name="billing_cardtype" required>
                            <option value="0">Select Card</option>
                            <option value="Visa">Visa</option>
                            <option value="MasterCard">MasterCard</option>
                            <option value="Discover">Discover</option>
                            <option value="Amex">American Express</option>
                        </select>
                    </p>
                    <div class="clear"></div>
                    <p class="form-row form-row-first">
                        <label><?php _e( 'Expiration Date', 'woocommerce-fruity-payment-gateway' ); ?> <span
                                    class="required">*</span></label>
                        <select name="billing_expdatemonth" required>
                            <option value=0>Select Month</option>
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
                        <select name="billing_expdateyear" required>
                            <option value=0>Select Year</option>
							<?php
							$today = (int) date( 'Y', time() );
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
                </fieldset>
			<?php }
		}
	}
}
//delete_option('woocommerce_checkout_privacy_policy_text');