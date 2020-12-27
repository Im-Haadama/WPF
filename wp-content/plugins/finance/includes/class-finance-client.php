<?php


class Finance_Client {
	private $user_id;
	private $user;

	/**
	 * Finance_Client constructor.
	 *
	 * @param $user_id
	 */
	public function __construct( $user_id ) {
		$this->user_id = $user_id;
	}

	function add_transaction( $date, $amount, $ref, $type ) {
		$sql = "INSERT INTO im_client_accounts (client_id, date, transaction_amount, transaction_method, transaction_ref) "
		       . "VALUES (" . $this->user_id . ", \"" . $date . "\", " . $amount . ", \"" . $type . "\", " . $ref . ")";

		FinanceLog( __FUNCTION__ . ':'. $sql );
		return SqlQuery( $sql );
	}

	function update_transaction( $total, $delivery_id) {
		$sql = "UPDATE im_client_accounts SET transaction_amount = " . $total .
		       " WHERE transaction_ref = " . $delivery_id . " and client_id = " . $this->user_id;

		MyLog( $sql, "account_update_transaction" );
		SqlQuery( $sql );
	}

	function get_payment_method( ) {
		$m = get_user_meta( $this->user_id, "payment_method", true );
		if ( $m ) {
			return $m;
		}

		$p = SqlQuerySingleScalar( "SELECT id FROM im_payments WHERE `default_method` = 1" );
		if ( $p ) {
			return $p;
		} else {
			return "לא נבחר אמצעי ברירת מחדל";
		}
	}

	function get_customer_email()
	{
		return self::get_user()->user_email;
	}

	private function get_user()
	{
		if (! $this->user) {
			$this->user = get_user_by('id', $this->user_id);
		}
		if (! $this->user) return null;
		return $this->user;
	}

	/**
	 * @return int
	 */
	public function getUserId(): int {
		return $this->user_id;
	}

	function getInvoiceUserId($create = false)
	{
		$invoice = Finance_Invoice4u::getInstance();
		if (! $invoice) return null;

//	    // Try the cache
		$id = get_user_meta( $this->user_id, 'invoice_id', 1 );
		if ($id) return $id;

		if ($u = self::getInvoiceUser($create)) {
			$id = $u->ID;

			// Save cache
			update_user_meta( $this->user_id, 'invoice_id', $id );
		}
		return $id;
	}

	function getInvoiceUser($create = true)
	{
		// Invoice is alive?
		$invoice = Finance_Invoice4u::getInstance();
		if (! $invoice) return null;

		$email = $this->get_customer_email();
		$name = $this->getName();


//	    // Try the cache
		$id = get_user_meta( $this->user_id, 'invoice_id', 1 );
		FinanceLog("get by id $id");
		if ($id) {
			$c =  $invoice->GetCustomerByID($id, $name, $email);
			if ($c) return $c;
		}

		// Thoose should be redudant as GetCustomerByID tries also with name and email.
		// Try to get by email.
		FinanceLog("get by email $email");
		$c = $invoice->GetCustomerByEmail($email);
		if (is_array($c)) foreach ($c as $customer) {
			if ($customer->Email == $email) return $customer;
		}
		if ($c) return $c;

		// Try to get by name (is unique in invoice4u);
		FinanceLog("Get by name $name");
		$c = $invoice->GetCustomerByName($name);
		if ($c) return $c;

		FinanceLog("Not found");
		if (! $create) return null;
		FinanceLog("Creating user " . $this->getName() . " " . $this->get_customer_email() . " ". $this->get_phone_number());

		// Create the user.
		if ($id = $invoice->CreateUser($this->getName(), $this->get_customer_email(), $this->get_phone_number())){
			update_user_meta( $this->user_id, 'invoice_id', $id );
			return $invoice->GetCustomerByEmail($this->get_customer_email());
		}
		return null;
	}

	function createInvoiceUser()
	{
		IF ($I = Finance_Invoice4u::getInstance())
			return $I->CreateUser($this->getName(), $this->get_customer_email(), $this->get_phone_number());
		return null;
	}

	function getName()
	{
//		var_dump(self::get_user());
		// TODO: after fixing the error with invoice4u, remove this:
		self::set_default_display_name();
		if ($user = self::get_user())
			return $user->display_name;
		return "no user " . $this->getUserId();
	}

	function set_default_display_name( ) {
		$user = get_user_by( "id", $this->user_id );
//		MyLog(__FUNCTION__, $this->user_id);

		if (! $user) return "user $this->user_id not found";
		$name = $user->user_firstname . " " . $user->user_lastname;;
		// print $this->user_id . " " . $name;
		if ( strlen( $name ) < 3 ) {
			$name = get_user_meta( $this->user_id, 'billing_first_name', true ) . " " .
			        get_user_meta( $this->user_id, 'billing_last_name', true );
			// print "user meta name " . $name;

		}
		$args = array(
			'ID'           => $this->user_id,
			'display_name' => $name,
			'nickname'     => $name
		);

		// print "<br/>";
		if ( strlen( $name ) > 3 ) {
			wp_update_user( $args );
		}
	}

	function get_phone_number()
	{
		return get_post_meta( self::get_last_order( ), '_billing_phone', true );
	}

	function get_last_order( ) {
		return SqlQuerySingleScalar( " SELECT max(meta.post_id) " .
		                             " FROM `wp_posts` posts, wp_postmeta meta" .
		                             " where meta.meta_key = '_customer_user'" .
		                             " and meta.meta_value = " . $this->user_id .
		                             " and meta.post_id = posts.ID");
	}

	public function balance() {
		//    MyLog(__FUNCTION__, $this->user_id);
		$sql = 'select sum(transaction_amount) '
		       . ' from im_client_accounts '
		       . ' where client_id = ' . $this->user_id;

		return round( SqlQuerySingleScalar( $sql ), 2 );
	}

}