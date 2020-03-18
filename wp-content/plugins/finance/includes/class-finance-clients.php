<?php


class Finance_Clients {

	static function admin_page()
	{
		$include_zero = false; // FFu: add control

		$sql = 'select round(sum(ia.transaction_amount),2), ia.client_id, wu.display_name, client_payment_method(ia.client_id), max(date) '
		       . ' from im_client_accounts ia'
		       . ' join wp_users wu'
		       . ' where wu.id=ia.client_id'
		       . ' group by client_id '
		       . ' order by 5 desc';

		$result = sql_query($sql);

		$data = "<table>";
		$data .= "<tr>";
		$data .= Core_Html::gui_cell( "בחר" );

		$data .= "<td>לקוח</td>";
		$data .= "<td>יתרה לתשלום</td>";
		$data .= "<td>הזמנה אחרונה</td>";
		$data .= "</tr>";

		// print ">הצג גם חשבונות מאופסים</a>";

		$data_lines         = array();
		$data_lines_credits = array();

		while ( $row = mysqli_fetch_row( $result ) ) {
			// $line = '';
			$customer_total = $row[0];
			$customer_id    = $row[1];
			$customer_name  = $row[2];

			$line = gui_cell( gui_checkbox( "chk_" . $customer_id, "user_chk" ) );
			$line .= "<td><a href = \"get-customer-account.php?customer_id=" . $customer_id . "\">" . $customer_name . "</a></td>";

			$line           .= "<td>" . $customer_total . "</td>";
			$payment_method = get_payment_method( $customer_id );
			$accountants = payment_get_accountants($payment_method);
			// print $accountants . " " . get_user_id() . "<br/>";
			if (!strstr($accountants, (string) get_user_id())) continue;

			$line           .= "<td>" . $row[4] . "</td>";
			$line           .= "<td>" . get_payment_method_name( $customer_id ) . "</td>";
			if ( $include_zero || $customer_total > 0 ) {
				//array_push( $data_lines, array( - $customer_total, $line ) );
				array_push( $data_lines, array( $payment_method, $line ) );
			} else if ( $customer_total < 0 ) {
				//array_push( $data_lines, array( - $customer_total, $line ) );
				array_push( $data_lines_credits, array( $customer_name, $line ) );
			}
		}

// sort( $data_lines );

		for ( $i = 0; $i < count( $data_lines ); $i ++ ) {
			$line = $data_lines[ $i ][1];
			$data .= "<tr> " . trim( $line ) . "</tr>";
		}

		$data = str_replace( "\r", "", $data );

		print "<center><h1>יתרת לקוחות לתשלום</h1></center>";


		$data .= "</table>";

		print "$data";

	}

	static function install()
	{
		$db_prefix = get_table_prefix();
		if (! table_exists("client_accounts"))
			sql_query("create table ${db_prefix}client_accounts
(
	ID bigint auto_increment
		primary key,
	client_id bigint not null,
	date date not null,
	transaction_amount double not null,
	transaction_method text not null,
	transaction_ref bigint not null
)
charset=utf8;
");
	}
}