<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 07/11/17
 * Time: 20:27
 */

// require_once( TOOLS_DIR . "/r-shop_manager.php" );
require_once( FRESH_INCLUDES . "/multi-site/imMulti-site.php" );

$addresses    = array();

function print_path( $ul ) {
	$start = 'גרניט 23 כפר-יונה';
	print "cost: " . evaluate_path( $start, $ul, $start ) . "<br/>";
	$i = 1;
	foreach ( $ul as $u ) {

		print $i . ")" .  $u . " ";
		print "<br/>";
		$i ++;
	}
}



function swap( &$a, &$b ) {
	$x = $a;
	$a = $b;
	$b = $x;
}


// Go to next closest node first

//function map_get_order_address( $order_id )
//{
//	global $store_address;
//	global $addresses;
//	if ( ! is_numeric( $order_id ) ) {
//		print $order_id . " is not a number ";
//
//		return $store_address;
//	}
//	if ( $order_id == 1 ) {
//		return $store_address;
//	}
//
//	$address = $addresses[ $order_id ];
//	if ( ! $address ) {
////		print "order id = " . $order_id;
//		$address                = order_get_address( $order_id );
//		if ( ! $address ) {
//			print "לא נמצאה כתובת  " . $order_id;
//			$addresses[ $order_id ] = $store_address;
//		}
//		// print $order_id . " " . $address . "<br/>";
//		$addresses[ $order_id ] = $address;
//	}
//
//	return $address;
//}



//$order_id = $row[0];
//$client_id = get_customer_id_by_order_id();
//$g->addedge()
//
//$g->addedge("a", "b", 4);
//$g->addedge("a", "d", 1);

//	if ( $print ) {
//		$url = "http://gebweb.net/optimap/index.php?loc0=" . $node;
//		$url2 = "https://www.google.com/maps/dir/";
//
//		for ( $i = 0; $i < count( $rest ); $i ++ ) {
////		print $rest[$i] . " " . get_user_address($rest[$i]) . "<br/>";n
//			$url .= "&loc" . ( $i + 1 ) . "=" . $rest[ $i ];
//			$url2 .= "/" . $rest[$i];
//		}
//		print Core_Html::GuiHyperlink( "Optimap", $url );
//		print Core_Html::GuiHyperlink("Maps", $url2);
//		print "<br/>";
//	}
