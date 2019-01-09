<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 07/11/17
 * Time: 20:27
 */

if ( ! defined( 'TOOLS_DIR' ) ) {
	define( 'TOOLS_DIR', dirname( dirname( __FILE__ ) ) );
}

// require_once( TOOLS_DIR . "/r-shop_manager.php" );
require_once( TOOLS_DIR . "/multi-site/imMulti-site.php" );
require_once( TOOLS_DIR . "/maps/config.php" );

$addresses    = array();

//function print_path( $ul ) {
//	print "cost: " . evaluate_path( 1, $ul, 1 ) . "<br/>";
//	$i = 1;
//	foreach ( $ul as $u ) {
//
//		print $i . ")" . map_get_order_address( $u ) . " ";
//		print "<br/>";
//		$i ++;
//	}
//}

function evaluate_path( $start, $elements, $end ) {
//	if ( $end < 1 ) {
//		print "end is " . $end . "<br/>";
//	}
	$cost = get_distance( $start, $elements[0] );
	$size = sizeof( $elements );
//	print "size: " . $size . "<br/>";
	for ( $i = 1; $i < $size; $i ++ ) {
//		print "i = " . $i . " e[i-1] = " . $elements[$i-1] . " e[i] = " . $elements[$i] . "<br/>";
		$cost += get_distance( $elements[ $i - 1 ], $elements[ $i ] );
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

function find_route_1( $node, $rest, &$path, $print = false, $end ) {
	if ( $print ) {
		$url = "http://gebweb.net/optimap/index.php?loc0=" . $node;
		for ( $i = 0; $i < count( $rest ); $i ++ ) {
//		print $rest[$i] . " " . get_user_address($rest[$i]) . "<br/>";n
			$url .= "&loc" . ( $i + 1 ) . "=" . $rest[ $i ];
		}
		print gui_hyperlink( "Optimap", $url );
		print "<br/>";
	}

	// print "find route 1. node = " . $node . " rest = " . comma_implode($path) . "<br/>";
	if ( count( $rest ) == 1 ) {
		array_push( $path, $rest[0] );

		return;
	}
	find_route( $node, $rest, $path );

	$best_cost = evaluate_path( $node, $path, $end );
	$switched  = true;
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
function find_route( $node, $rest, &$path ) {
	if ( sizeof( $rest ) == 1 ) {
		array_push( $path, $rest[0] );

		return;
	}

	$min     = - 1;
	$min_seq = 0;
	for ( $i = 0; $i < sizeof( $rest ); $i ++ ) {
		// print $rest[$i]  . " ";
		$d = get_distance( $node, $rest[ $i ] );
		if ( ( $node == $rest[ $i ] ) or ( $min == - 1 ) or ( $d < $min ) ) {
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

	find_route( $next, $new_rest, $path );
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
	global $conn;
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
	if ( ! $r ) {
		// One is invalid
		if ( do_get_distance( $address_a, $address_a ) ) {
			print "כתובת לא תקינה  " . $address_a;
		}
		if ( do_get_distance( $address_b, $address_b ) ) {
			print "כתובת לא תקינה " . $address_b;
		}
	}
	$distance = $r[0];
	$duration = $r[1];
	// print get_client_address($order_a) . " " . get_client_address($order_b) . " " . $d . "<br/>";
	if ( $distance > 0 ) {
		$sql1 = "insert into im_distance (address_a, address_b, distance, duration) VALUES 
				('" . mysqli_real_escape_string( $conn, $address_a ) . "', '" .
		        mysqli_real_escape_string( $conn, $address_b ) . "', $distance, $duration)";
		sql_query( $sql1 );
		if ( mysqli_affected_rows( $conn ) < 1 ) {
			print "fail: " . $sql1 . "<br/>";
		}

		return $distance;
	}

	return - 1;
}

function get_distance_duration( $address_a, $address_b ) {
	global $conn;
	$sql = "SELECT duration FROM im_distance WHERE address_a = '" . mysqli_real_escape_string( $conn, $address_a ) .
	       "' AND address_b = '" . mysqli_real_escape_string( $conn, $address_b ) . "'";

	return sql_query_single_scalar( $sql );

}

function do_get_distance( $a, $b ) {
	// $start = new DateTime();
	if ( $a == $b ) {
		return 0;
	}
	if ( is_null( $a ) or strlen( $a ) < 1 ) {
		$debug = debug_backtrace();
		for ( $i = 2; $i < 8 && $i < count( $debug ); $i ++ ) {
			print "called from " . $debug[ $i ]["function"] . ":" . $debug[ $i ]["line"] . "<br/>";
		}

		print "a is null";
		var_dump( $b );
		print " ";
		var_dump( $a );

		// print "b is " . $b . "<br/>";
		return 0;
	}

	if ( is_null( $b ) or strlen( $b ) < 1 ) {
		$debug = debug_backtrace();
		for ( $i = 2; $i < 6 && $i < count( $debug ); $i ++ ) {
			print "called from " . $debug[ $i ]["function"] . ":" . $debug[ $i ]["line"] . "<br/>";
		}
		print "b is null";
		var_dump( $b );
		print " ";
		var_dump( $a );
		print "<br/>";

		return 0;
	}

	global $key;
//	debug_time1("google start");
	$s = "https://maps.googleapis.com/maps/api/directions/json?origin=" . urlencode( $a ) . "&destination=" .
	     urlencode( $b ) . "&key=" . $key . "&language=iw";

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