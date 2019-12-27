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

function evaluate_path( $start, $elements, $end ) {
//	if ( $end < 1 ) {
//		print "end is " . $end . "<br/>";
//	}
	// $cost = get_distance( $start, $elements[0] );
	$cost = get_distance_duration( $start, $elements[0] );
	$size = sizeof( $elements );
//	print "size: " . $size . "<br/>";
	for ( $i = 1; $i < $size; $i ++ ) {
//		print "i = " . $i . " e[i-1] = " . $elements[$i-1] . " e[i] = " . $elements[$i] . "<br/>";
		$dis = get_distance( $elements[ $i - 1 ], $elements[ $i ] );
		if ($dis > -1)
			$cost += $dis;
	}
//	print "end = " . $end . "<br/>";
	$cost += get_distance( $elements[ $size - 1 ], $end );

	return $cost;
}


function swap( &$a, &$b ) {
	$x = $a;
	$a = $b;
	$b = $x;
}

function find_route_1( $node, $rest, &$path, $print, $end, $prerequisite )
{

	if (! $rest or ! is_array($rest))
	{
		die("invalid points");
	}
	// print "find route 1. node = " . $node . " rest = " . comma_implode($path) . "<br/>";
	if ( count( $rest ) == 1 ) {
		array_push( $path, $rest[0] );

		return;
	}
	find_route( $node, $rest, $path, $prerequisite );

	$best_cost = evaluate_path( $node, $path, $end );

	if ($print) {
		print "first guess route<br/>";
//		print "cost: " . $best_cost . "<br/>";
		print_path( $path );
	}

	// Continue as long as switching adjacent nodes makes the route shorter
	// Disable for now, because the preq is not implemented here.
	$switched  = false;
	while ( $switched ) {
		$switched = false;
		for ( $switch_node = 1; $switch_node < count( $path ) - 1; $switch_node ++ ) {
//			print "node: " . $switch_node . " " . get_user_address($path[$switch_node]) . "<br/>";
			// print $switch_node . "<br/>";
			$alternate_path = $path;
			swap( $alternate_path[ $switch_node ], $alternate_path[ $switch_node + 1 ] );
//			print "alternate:";
//			print_path($alternate_path);
			$temp_cost = evaluate_path( $node, $alternate_path, $end );
			if ( $temp_cost < $best_cost ) {
				if ( $print ) {
					print "Best: " . $temp_cost . " " . $switch_node . " " . $path[ $switch_node ] . " " .
					      $path[ $switch_node + 1 ] . "<br/>";
				}
				$switched = true;
				swap( $path[ $switch_node ], $path[ $switch_node + 1 ] );
//				print "after switch:<br/>";
//				print_path($path);
				$best_cost = $temp_cost;
			}
		}
	}
}

// Go to next closest node first
function find_route( $node, $rest, &$path, $prerequisite = null ) {
	if ( sizeof( $rest ) == 1 ) {
		array_push( $path, $rest[0] );

		return;
	}

	$min     = - 1;
	$min_seq = 0;
	for ( $i = 0; $i < sizeof( $rest ); $i ++ ) {
		// print $rest[$i]  . " ";
		$d = get_distance( $node, $rest[ $i ] );
		if ( ( $min == - 1 ) or ( $d < $min ) ) { // ( $node == $rest[ $i ] ) or
			// If we didn't visit previous location for collecting, skip.
			// var_dump($path); print "<br/>";
			if ($prerequisite and isset($prerequisite[$rest[ $i ]]) and strlen ($prerequisite[$rest[$i]])){
				// print "<br/>checking preq for " . $rest[ $i ] . " ";
				if (! in_array($prerequisite[$rest[ $i ]], $path)) {
					print "X" . $prerequisite[$rest[ $i ]] . "X not yet. skipping<br/>";

					continue;
				}
			}
			$min     = $d;
			$min_seq = $i;
		}
	}

	$next = $rest[ $min_seq ];
	array_push( $path, $next );
	$new_rest = array();
	for ( $i = 0; $i < sizeof( $rest ); $i ++ ) {
		if ( $i <> $min_seq ) {
			array_push( $new_rest, $rest[ $i ] );
		}
	}

	find_route( $next, $new_rest, $path, $prerequisite );
}

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

function get_distance( $address_a, $address_b ) {
	if ( 0 ) {
		print "a: X" . $address_a . "X<br/>";
		print "b: X" . $address_b . "X<br/>";
	}
	if ( rtrim( $address_a ) == rtrim( $address_b ) ) {
		return 0;
	}
	$sql = "SELECT distance FROM im_distance WHERE address_a = '" . escape_string( $address_a ) . "' AND address_b = '" .
	       escape_string( $address_b ) . "'";
	// print $sql . " ";
	$ds  = sql_query_single_scalar( $sql );
	// print $ds . "<br/>";

	if ( $ds > 0 ) {
		return $ds;
	}
	$r = do_get_distance( $address_a, $address_b );
	if ( $r  == -1) {
		// One is invalid
		return -1;
	}
	$distance = $r[0];
	$duration = $r[1];
	// print get_client_address($order_a) . " " . get_client_address($order_b) . " " . $d . "<br/>";
	if ( $distance > 0 ) {
		$sql1 = "insert into im_distance (address_a, address_b, distance, duration) VALUES 
				('" . escape_string( $address_a ) . "', '" .
		        escape_string(  $address_b ) . "', $distance, $duration)";
		sql_query( $sql1 );
		if ( sql_affected_rows( ) < 1 ) {
			print "fail: " . $sql1 . "<br/>";
		}

		return $distance;
	}

	return - 1;
}

function get_distance_duration( $address_a, $address_b ) {
	$sql = "SELECT duration FROM im_distance WHERE address_a = '" . escape_string( $address_a ) .
	       "' AND address_b = '" . escape_string( $address_b ) . "'";

	return sql_query_single_scalar( $sql );

}

function do_get_distance( $a, $b ) {
	// $start = new DateTime();
	if ( $a == $b ) {
		return 0;
	}
	if ( is_null( $a ) or strlen( $a ) < 1 ) return -1;

	if ( is_null( $b ) or strlen( $b ) < 1 ) return -1;

//	debug_time1("google start");
	$s = "https://maps.googleapis.com/maps/api/directions/json?origin=" . urlencode( $a ) . "&destination=" .
	     urlencode( $b ) . "&key=" . MAPS_KEY . "&language=iw";

	// print $s;
	$result = file_get_contents( $s );
//	debug_time1("google end");

	$j = json_decode( $result );

	if ( ! $j or ! isset( $j->routes[0] ) ) {
		print "Can't find distance between '" . $a . "' and '" . $b . "'<br/>";

		return null;
	}

	$v = $j->routes[0]->legs[0]->distance->value;
	$t = $j->routes[0]->legs[0]->duration->value;

//	$end = new DateTime();
//
//	$delta = $start->diff($end)->format("%s");
//	// var_dump($delta); print "<br/>"; // ->format("%s");
//	// print "diff: " . $sec . "<br/>";
//	if ($delta > 0) {
//		print "בדוק כתובות" . $a . " " . $b . "<br/>";
//	}
	if ( $v > 0 ) {
		return array( $v, $t );
	}

	print "can't find distance between " . $a . " " . $b . "<br/>";

	return null;
}

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
//		print gui_hyperlink( "Optimap", $url );
//		print gui_hyperlink("Maps", $url2);
//		print "<br/>";
//	}