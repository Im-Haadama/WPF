<?php

//print "z=" . date('z', strtotime('2020-10-05'));
if (class_exists("WC_Payment_Gateway")) {

	class E_Fresh_Payment_Gateway extends WC_Payment_Gateway
    {
		private $order_status;
		private $error_message;

		public function __construct() {
			$this->id           = 'other_payment';
			$this->method_title = __( 'E-fresh Payment Gateway', 'e-fresh-payment-gateway' );
			$this->title        = __( 'E-fresh Payment Gateway', 'e-fresh' );
			$this->has_fields   = true;
			$this->init_form_fields();
			$this->init_settings();
			$this->pay_on_checkout   = $this->get_option( 'pay_on_checkout' );
			$this->title             = $this->get_option( 'title' );
			$this->description       = $this->get_option( 'description' );
			$this->hide_text_box     = $this->get_option( 'hide_text_box' );
			$this->text_box_required = $this->get_option( 'text_box_required' );
			$this->order_status      = $this->get_option( 'order_status' );

			$this->paying = null;

			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array(
				$this,
				'process_admin_options'
			) );
		}

		public function init_form_fields() {
			$this->form_fields = array(
				'pay_on_checkout' => array(
					'title'   => __( 'Pay on checkout', 'e-fresh-pay-on-checkout' ),
					'type'    => 'checkbox', // woocommerce-other-payment-gateway
					'label'   => __( 'If checked on ordered will be paid while checkout', 'e-fresh-pay-on-order' ),
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
				wc_add_notice( __( 'Please enter credit card number.', 'finance' ), 'error' );

				return false;
			}
			if ( isset( $billing_idnumber ) && $billing_idnumber == '' ) {
				wc_add_notice( __( 'Please enter id number.', 'finance' ), 'error' );

				return false;
			}
			if ( isset( $cardtype ) && $cardtype == '0' ) {
				wc_add_notice( __( 'Please select card type', 'finance' ), 'error' );

				return false;
			}
			if ( isset( $expdatemonth ) && $expdatemonth == '0' ) {
				wc_add_notice( __( 'Please select expiry month.', 'finance' ), 'error' );

				return false;
			}
			if ( isset( $expdateyear ) && $expdateyear == '0' ) {
				wc_add_notice( __( 'Please select expiry year.', 'finance' ), 'error' );

				return false;
			}

			return true;
		}

		public function test()
		{
		    $d = new Finance_Delivery(16631);
		    $d->Delete();
			$_REQUEST["billing_creditcard"] = "4580000000000000";
			$_REQUEST["billing_expdatemonth"] = "12";
			$_REQUEST["billing_expdateyear"] = "2021";
			$_REQUEST["billing_idnumber"] = "014480286";
			$_REQUEST['save_as_token'] = 1;
			self::process_payment(16631);

			print "Error: " . $this->error_message . "<br/>";
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
                    print __("Previous card expired", "e-fresh");
                }

				$id_number =  $wpdb->get_var( "SELECT id_number FROM im_payment_info WHERE user_id = $current_user_id" );
				$valid_id = ((! class_exists('Israel_Shop')) or Israel_Shop::ValidID($id_number));

//                update_usermeta($current_user_id, 'credit_token', 'invoice4u00');
				$token = get_user_meta($current_user_id, 'credit_token', true);
//				MyLog("token: $token");
//				MyLog(strstr($card_number, 'XX'));

                FinanceLog("vid= " . $valid_id . " " . $valid_year . " " . $valid_month . " " . $card_number);
				$valid_credit_info = ($valid_id and
				     (($valid_year > $this_year) or (($valid_year == $this_year) and ($valid_month >= $this_month))) and
                     (strlen($token) >= 10 or ! strstr($card_number, 'XX')));
				FinanceLog("vci=" . $valid_credit_info);
			}

			global $l10n;

//			if (get_user_id() == 1) var_dump($l10n['finance']);

			if ( $valid_credit_info ) { ?>
                <fieldset>
                    <p class="form-row validate-required">
						<?php
						$card_number_field_placeholder = __( '  ', 'finance' );
						?>
                        <label><?php print __( 'Card Number', 'finance' ) . " " .
                                           substr($card_number, -4); ?></label>
                    </p>
                </fieldset>
			<?php } else {
				?>
                <fieldset>
                    <p class="form-row validate-required" style="display: inline-block;">
						<?php
						$card_number_field_placeholder = __( 'Card Number', 'finance' );
						$card_number_field_placeholder = apply_filters( 'wcpprog_card_number_field_placeholder', $card_number_field_placeholder );
						?>
                        <label><?php _e( 'Card Number', 'finance' ); ?> <span
                                    class="required">*</span></label>
                        <input class="input-text" type="text" size="19" maxlength="19" name="billing_creditcard"
                               id="card_number" value="<?php echo $billing_creditcard; ?>"
                               placeholder="<?php echo $card_number_field_placeholder; ?>" required/>
                    </p>

                    <p class="form-row validate-required" style="display: inline-block;">

                        <label><?php _e( 'ID Number', 'finance' ); ?> <span class="required">*</span></label>
                        <input class="input-text" type="text" size="19" maxlength="10" name="billing_idnumber"
                               id="id_number" value="" placeholder="Id Number" required/>
                    </p>

                    <p class="form-row" style="display: inline-block;">
                        <label><?php _e( 'Card Type', 'finance' ); ?> <span class="required">*</span></label>
                        <select name="billing_cardtype" required>
                            <option value="0"><?php _e("Select Card", "finance"); ?></option>
                            <option value="Visa">Visa</option>
                            <option value="MasterCard">MasterCard</option>
                            <option value="Discover">Discover</option>
                            <option value="Amex">American Express</option>
                        </select>
                    </p>
                    <p class="form-row" style="display: inline-block;">
                        <label><?php _e( 'Expiration Date', 'finance' ); ?>
                            <span class="required" style="display: inline-block;">*</label>
                        <select name="billing_expdatemonth" required style="display: inline-block;">
                            <option value=0><?php _e("Select Month", "finance"); ?></option>
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
                        <select name="billing_expdateyear" required style="display: inline-block;">
                            <option value=0><?php _e("Select Year", "finance"); ?></option>
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
<!--                    <p class="form-row" style="display: inline-block;">-->
<!--                        <label>--><?php //_e( 'Save token to next orders', 'finance' ); ?><!--</label>-->
<!--                        <input name="save_as_token" type="checkbox">-->
<!--                    </p>-->
                        <?php
                        woocommerce_form_field( 'save_as_token', array( // CSS ID
	                        'type'          => 'checkbox',
	                        'class'         => array('form-row mycheckbox'), // CSS Class
	                        'label_class'   => array('woocommerce-form__label woocommerce-form__label-for-checkbox checkbox'),
	                        'input_class'   => array('woocommerce-form__input woocommerce-form__input-checkbox input-checkbox'),
//	                        'required'      => true, // Mandatory or Optional
	                        'label'         => 'Save token for next orders', // Label and Link
                        ));
                        ?>
                </fieldset>
			<?php }
		}

		public function process_payment($order_id)
        {
            $bl = new Finance_Business_Logic();
            $args = [];
            $args['pay_on_checkout'] = $this->get_option('pay_on_checkout');
            $passed = $bl->process_payment($order_id, $args);
            $this->error_message = $bl->getErrorMessage();

	        if ($passed) {
		        $order = new Finance_Order($order_id);

		        $order->update_status( $this->order_status, ETranslate("Paid with E-fresh payment gateway " . date('Y-m-d')));

		        wc_reduce_stock_levels( $order_id );
		        if ( isset( $_POST[ $this->id . '-admin-note' ] ) && trim( $_POST[ $this->id . '-admin-note' ] ) != '' ) {
			        $order->add_order_note( esc_html( $_POST[ $this->id . '-admin-note' ] ), 1 );
		        }
		        global $woocommerce;
		        $woocommerce->cart->empty_cart();

		        return array(
			        'result'   => 'success',
                    'redirect' => $this->get_return_url( $order->getWCOrder() )
		        );
	        } else  {
		        FinanceLog("payment failed");

		        wc_add_notice( $this->error_message, 'error' );

		        return array("result"=>'fail');
	        }
        }

//	/**
//	 * @param $customer_id
//	 * @param int $amount
//	 * pay customer balance or the given amount.
//	 *
//	 * @param int $payment_number
//	 *
//	 * @return bool
//	 */
//	function pay_user_credit_wrap($customer_id, $amount = 0, $payment_number = 1)
//	{
//		FinanceLog(__FUNCTION__ . ": pay for $customer_id");
//		// $delivery_ids = sql_query_array_scalar("select id from im_delivery where payment_receipt is null and draft is false");
//		$sql = 'select
//		id,
//		date,
//		round(transaction_amount, 2) as transaction_amount,
//		client_balance(client_id, date) as balance,
//	    transaction_method,
//	    transaction_ref,
//		order_from_delivery(transaction_ref) as order_id,
//		delivery_receipt(transaction_ref) as receipt,
//		id
//		from im_client_accounts
//		where client_id = ' . $customer_id . '
//		and delivery_receipt(transaction_ref) is null
//		and transaction_method = "משלוח"
//		order by date asc
//		';
//
//		// If amount not specified, try to pay the balance.
//		$user = new Finance_Client($customer_id);
//
//		if ($amount == 0)
//			$amount = $user->balance();
//
//		$rows = SqlQueryArray($sql);
//		$current_total = 0;
//
//		$paying_transactions = [];
//		foreach ($rows as $row) {
//			$trans_amount = $row[2];
//			if (($trans_amount + $current_total) < ($amount + 15)) {
//				array_push($paying_transactions, $row[0]);
//				$current_total += $trans_amount;
//			}
//		}
//
//		$change = $amount - $current_total;
//
//		$credit_data = SqlQuerySingleAssoc( "select * from im_payment_info where user_id = " . $user->getUserId());
//		if (! $credit_data) {
//			FinanceLog("no credit info found");
//			return false;
//		}
//
//		return $this->pay_user_credit($user, $credit_info, $paying_transactions, $amount, $change, $payment_number);
//	}

	static function getCustomerStatus(Finance_Client $C, $string = true)
	{
		if ($string)
			$rc = (SqlQuerySingleScalar( "select count(*) from im_payment_info where card_number not like '%X%' and email = " . QuoteText($C->get_customer_email())) > 0 ? 'C' : '') .
			      (SqlQuerySingleScalar( "select count(*) from im_payment_info where card_number like '%X%' and email = " . QuoteText($C->get_customer_email())) > 0 ? 'X' : '');
		else
			$rc = (SqlQuerySingleScalar( "select count(*) from im_payment_info where card_number not like '%X%' and email = " . QuoteText($C->get_customer_email())) > 0 ? 1 : 0) +
			      (SqlQuerySingleScalar( "select count(*) from im_payment_info where card_number like '%X%' and email = " . QuoteText($C->get_customer_email())) > 0 ? 2 : 0);

		return $rc;

	}

	static function RemoveRawInfo($row_id)
	{
		global $wpdb;
		FinanceLog(__FUNCTION__ . ": $row_id");
		FinanceLog($row_id, __FUNCTION__);
		$table_name = "im_payment_info";
		$card_four_digit   = $wpdb->get_var("SELECT card_four_digit FROM $table_name WHERE id = ".$row_id." ");
		$dig4 = Finance_Payments::setCreditCard($card_four_digit);
		SqlQuery("UPDATE $table_name SET card_number =  '".$dig4."' WHERE id = ".$row_id." ");
		return true;
	}
}
}