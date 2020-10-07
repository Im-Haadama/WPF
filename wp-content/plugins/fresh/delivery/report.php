<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 08/06/17
 * Time: 18:02
 */
require_once( "../r-shop_manager.php" );
require_once( ROOT_DIR . "/niver/gui/sql_table.php" );
require_once( ROOT_DIR . "/fresh/supplies/Supply.php" );

print header_text();

if ( isset( $_GET["week"] ) ) {
	$week = $_GET["week"];
	$args = "";
	foreach ( $_GET as $k => $v ) {
		if ( $k != 'week' ) {
			$args .= "&" . $k . '=' . $v;
		}
	}
	printCore_Html::GuiHyperlink( "שבוע קודם", "report.php?week=" . date( 'Y-m-d', strtotime( $week . " -1 week" ) ) . $args );

	printCore_Html::GuiHyperlink( "שבוע עוקב", "report.php?week=" . date( 'Y-m-d', strtotime( $week . " +1 week" ) ) . $args );
}
if ( isset( $week ) and isset( $_GET["prod_id"] ) ) {
	print_prod_report( $_GET["prod_id"], $week );

	return;
}

if ( isset( $_GET["prod_id"] ) ) {
	print_prod_report( $_GET["prod_id"], null, isset( $_GET["user_id"] ) ? $_GET["user_id"] : null );

	return;
}


if ( isset( $_GET["customer_id"] ) ) {
	client_report( $_GET["customer_id"] );

	return;
}

if ( isset( $_GET["supplier_id"] ) ) {
	$week = date( "Y-m-d", strtotime( "last sunday" ) );
	if ( isset( $_GET["week"] ) ) {
		$week = $_GET["week"];
	}
	supplier_report( $_GET["supplier_id"], $week );

	return;
}

// print "a";
if ( isset( $_GET["week"] ) ) {
//	print "w";
	$sort = 4;
	if ( isset( $_GET["sort"] ) ) {
		$sort = $_GET["sort"];
		if ( strpos( $sort, "d" ) ) {
			//print "d<br/>";
			$sort = substr( $sort, 0, strlen( $sort ) - 1 ) . " desc";
			//print "sort: " . $sort . "<br/>";
		}
	}
	print_weekly_report( $_GET["week"], $sort );

	return;
}

function week_deliveries( $week ) {
	return SqlQueryArrayScalar( "SELECT id FROM im_delivery WHERE first_day_of_week(date) = '" . $week . "'");
}

if ( isset( $_GET["project"] ) ) {
	print_project_report( $_GET["project"], 1 );
	die( 0 );
}

function supplier_report( $supplier_id, $week ) {
	printCore_Html::GuiHeader( 1, "נתוני מכירה לספק " . get_supplier_name( $supplier_id ) );
	$end   = $week;
	$start = date( 'Y-m-d', strtotime( $week . ' -1 week' ) );
	printCore_Html::GuiHeader( 2, "בין התאריכים " . $start . " - " . $end );
	$sum = array();

	print gui_table( supplier_report_data( $supplier_id, $start, $end ), null, true, true, $sum, null, null,
		array( null, null, "report.php?week=" . $week . "&prod_id=%s" ) );

}

function client_report( $customer_id, $last = 5 ) {
	$sql = "select id from im_delivery where order_user(order_id) = " . $customer_id .
	       " order by 1 desc limit " . $last;

	$rows = SqlQueryArrayScalar( $sql );
	if ( ! $rows ) {
		print "no results";

		return;
	}
	foreach ( $rows as $delivery_id ) {
		printCore_Html::GuiHeader( 1, $delivery_id );
		$sql = "select product_name from im_delivery_lines where delivery_id = " . $delivery_id;

		$items = SqlQueryArrayScalar( $sql );
		foreach ( $items as $item ) {
			print $item . "<br/>";
		}
	}
}

// print_weekly_report( date( "Y-m-d", strtotime( "last sunday" ) ) );
function print_prod_report( $prod_id, $week = null, $user_id = null ) {
	if ( $week ) {
		printCore_Html::GuiHeader( 1, "מציג תוצאות לשבוע המתחיל ביום " . $week );
	}
	printCore_Html::GuiHeader( 2, "מוצר " . get_product_name( $prod_id ) );
	if ( $user_id ) {
		printCore_Html::GuiHeader( 2, "לקוח " . get_customer_name( $user_id ) );
	}

	$sql      = "SELECT delivery_id, product_name, round(quantity, 1), order_id, date, prod_id";
	$order_by = array();
	if ( ! $week ) {
		$sql .= ", date";
	}
	$prod_ids = Bundle::GetBundles( $prod_id );
	array_push( $prod_ids, $prod_id );

	$sql .= " FROM im_delivery_lines dl JOIN im_delivery d " .
	        " WHERE dl.delivery_id = d.id AND prod_id in (" . comma_implode( $prod_ids ) . ") AND delivery_id IN (SELECT id FROM im_delivery";

	$query = null;
	if ( $week ) {
		AddQuery( $query, "first_day_of_week(date) = '" . $week . "'" );
	} else {
		array_push( $order_by, "5 desc" );
	}

	if ( $user_id ) {
		AddQuery( $query, "order_user(order_id) = " . $user_id );
	}

	if ( $query ) {
		$sql .= " " . $query;
	}
	$sql .= ")";

//	var_dump($order_by);
	if ( count( $order_by ) )
		$sql .= "order by " . comma_implode( $order_by );

	// print $sql;

	// print $sql;
	$result = SqlQuery( $sql );

	$lines = array();
	while ( $row = mysqli_fetch_row( $result ) ) {
		$order_id = $row[3];
		$o        = new Order( $order_id );
		$q        = $row[2];
		$prod_id  = $row[5];
		if ( is_bundle( $prod_id ) ) {
			$b = Bundle::CreateFromBundleProd( $prod_id );
			$q = $q * $b->GetQuantity();
		}
		$line     = array(
			Core_Html::GuiHyperlink( $row[0], "get-delivery.php?id=" . $row[0] ),
			$q,
			get_customer_name( $o->getCustomerId() )
		);
		if ( ! $week ) {
			array_push( $line, $row[4] );
		}
		array_push( $lines, $line );
	}

	$sum = array();
	print gui_table( $lines, null, true, null, $sum, null, null, true );
}

