./tools/orders/order-form.php                                                                       0000664 0001750 0001750 00000015240 13414640420 015032  0                                                                                                    ustar   agla                            agla                                                                                                                                                                                                                   <?php

error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

if ( ! defined( "TOOLS_DIR" ) ) {
	define( 'TOOLS_DIR', dirname( dirname( __FILE__ ) ) );
}
require_once( TOOLS_DIR . "/im_tools.php" );
require_once( ROOT_DIR . "/agla/gui/inputs.php" );
require_once( TOOLS_DIR . "/multi-site/imMulti-site.php" );
require_once( TOOLS_DIR . "/options.php" );
require_once( TOOLS_DIR . "/pricing.php" );
require_once( TOOLS_DIR . "/orders/form.php" );
require_once( TOOLS_DIR . "/orders/orders-common.php" );
// print header_text();

$text  = isset( $_GET["text"] );
$fresh = isset( $_GET["fresh"] );

// print "text = " . $text;

function show_category( $term_name, $sale = false, $text = false ) {
	$the_term = get_term_by( 'name', $term_name, 'product_cat' );
	if ( $the_term ) {
		print show_category_by_id( $the_term->term_id, $sale, $text );
	}
}

function get_form_tables() {
	$key         = "form_categs";
	$categs_info = info_get( $key );
	if ( ! $categs_info ) {
		info_update( $key, "" );
		$categs_info = "";
	}
	$categs = explode( ",", $categs_info );
	$result = "";
	foreach ( $categs as $categ ) {
		$result .= "\"table_" . $categ . "\", ";
	}

	return rtrim( $result, ", " );
}

?>
<script type="text/javascript" src="/agla/client_tools.js"></script>

<script>

    var tables = [<?php print get_form_tables(); ?>];

    function add_order() {
        var params = new Array();
        var prod_id;
        for (var i = 0; i < tables.length; i++) {
            var table = document.getElementById(tables[i]);
            for (var j = 1; j < table.rows.length; j++) {
                var line = get_value_by_name(table.rows[j].cells[5].firstElementChild.id);
                if (parseFloat(line) > 0) {
                    prod_id = table.rows[j].cells[4].firstElementChild.id.substr(4);
                    params.push(prod_id);
                    params.push(get_value_by_name(table.rows[j].cells[4].firstElementChild.id))
                }
            }
        }
        var url = "<?php print ImMultiSite::LocalSiteTools();?>/orders/order-form-post.php?operation=create_order" +
            "&params=" + params;

        var phone = get_value_by_name("phone");
        var name = get_value_by_name("name");
        var email = get_value_by_name("email");

        if (phone.length > 2) url += "&phone=" + encodeURI(phone);
        if (name.length > 2) url += "&name=" + encodeURI(name);
		<?php if ( isset( $group ) ) {
		print "url += \"&group=\" + encodeURI('" . $group . "');";
	} ?>
		<?php if ( isset( $user ) ) {
		print "url += \"&user=\" + encodeURI('" . $user . "');";
	}
		?>
        if (email.length > 4) url += "&email=" + encodeURI(email);
        window.location.href = url;
    }
    function update() {
        var total = update_total();
        var email = get_value_by_name("email");
        var email_valid = email.length > 5;
        var min = <?php if ( isset( $min ) ) {
			print $min;
		} else {
			print 80;
		} ?>;
        var dis = !((total > min) && email_valid);
        document.getElementById("btn_add_order_1").disabled = dis;
        document.getElementById("btn_add_order_2").disabled = dis;
    }
    function update_total() {
        var total = 0;

        for (var i = 0; i < tables.length; i++)
            total += table_total(tables[i]);

        document.getElementById("total").innerHTML = Math.round(total * 100) / 100;

        return total;

    }
    function table_total(table_name) {
        // alert(table_name);
        var table = document.getElementById(table_name);
        var total = 0;

        for (var i = 1; i < table.rows.length; i++) {
            var line = get_value_by_name(table.rows[i].cells[5].firstElementChild.id);
            if (parseFloat(line) > 0)
                total += parseFloat(line);
        }

        return total;
    }
    function calc_line(elm) {
        var id = elm.id;
        var prod_id = id.substr(4);
        var q = get_value_by_name("qua_" + prod_id);
        var p;
        if (q >= 8) {
            p = get_value_by_name("vpr_" + prod_id);
        } else {
            p = get_value_by_name("prc_" + prod_id);
        }
        document.getElementById("tot_" + prod_id).innerHTML = String(Math.round(q * p * 100) / 100);
        var next_row = elm.parentElement.parentElement.nextElementSibling;
        if (next_row) {
            var input = next_row.childNodes[4].children[0];
            if (input) {
                input.focus();
                input.select();
            }
        }
        update();
    }
</script>


<?php
// print gui_header(1, "פרוטי אקספרס");

if ( $text ) {
	print header_text( true );
	print "מוצרים זמינים השבוע</br>";
} else {
	print "כתובת מייל של המזמין:";
	print gui_input( "email", "", array( "onchange=update()" ) );
	print "<br/>";
	print gui_table( array( 'סה"כ הזמנה:', gui_label( "total", "0" ) ) );

	print gui_button( "btn_add_order_1", "add_order(0)", "הוסף הזמנה" );
}
?>
<?php

print "<br/>";

//print "לפניכם מארזי כמות במחירים מוזלים. פירות ממשק נהרי, יש להזמין עד יום שישי, ההספקה בשבוע העוקב (איסוף או משלוח). בננות סוטו - להזמין עד יום ראשון בערב.";

$categs = info_get( "form_categs" );
if ( $categs ) {
	foreach ( explode( ",", $categs ) as $categ ) {
		print show_category_by_id( $categ, false, $text );
	}
} else {
	print show_category_all( false, $text, $fresh, true );
}
//show_category( "מארזי כמות מוזלים", true, $text );

print "<br/>";

//show_category( "פירות אורגניים", false, $text );
//show_category( "פירות לא אורגניים", false, $text );
//show_category( "ירקות אורגניים", false, $text );
//show_category( "עלים אורגניים", false, $text );
//show_category( "פטריות אורגניות", false, $text );
//show_category( "זרעי מאכל אורגניים", false, $text );
//show_category( "פירות יבשים אורגניים", false, $text );
//show_category( "צמחי מרפא אורגניים", false, $text );
//show_category( "נבטים אורגניים", false, $text);

if ( ! $text ) {
	print gui_button( "btn_add_order_2", "add_order(0)", "הוסף הזמנה" );
}

?>
<script>
    document.getElementById("btn_add_order_1").disabled = true;
    document.getElementById("btn_add_order_2").disabled = true;

</script>                                                                                                                                                                                                                                                                                                                                                                ./tools/maps/build-path.php                                                                         0000664 0001750 0001750 00000016745 13414637544 014501  0                                                                                                    ustar   agla                            agla                                                                                                                                                                                                                   <?php
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

$addresses = array();

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
	$ds = sql_query_single_scalar( $sql );
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
//$g->addedge("a", "d", 1);                           ./tools/panel/menu.php                                                                              0000664 0001750 0001750 00000010511 13414640420 013517  0                                                                                                    ustar   agla                            agla                                                                                                                                                                                                                   <?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 18/05/17
 * Time: 20:39
 */
require_once( '../r-shop_manager.php' );
require_once( '../header.php' );
require_once( "../gui/inputs.php" );
require_once( "../gui/sql_table.php" );
require_once( "../orders/orders-common.php" );
require_once( '../pricelist/pricelist.php' );
require_once( '../multi-site/imMulti-site.php' );

print header_text( true );
$table    = array();
$table[0] = array();

// Orders
$sql = "SELECT count(*) AS count, post_status AS status
    FROM wp_posts
      WHERE post_status LIKE 'wc%'
      AND post_status NOT IN ('wc-cancelled', 'wc-completed')
    GROUP BY 2";

$links    = [];
$links[1] = "../../wp-admin/edit.php?post_status=%s&post_type=shop_order";

$col = 0;

$table[0][ $col ] = gui_header( 1, "הזמנות" );
$table[1][ $col ] = table_content( $sql, true, true, $links );
$table[2][ $col ] = gui_hyperlink( "צור הזננות למנויים", "../weekly/create-subs.php" );
$table[3][ $col ] = "";
$table[4][ $col ] = "";

// Supplies
$i = 0;
$col ++;
$table[ $i ++ ][ $col ] = gui_header( 1, "אספקות" );
$table[ $i ++ ][ $col ] = gui_header( 2, "פריטים להזמין" );
$table[ $i ++ ][ $col ] = calculate_total_products();
$table[ $i ++ ][ $col ] = gui_link( "תכנון הספקה", "../orders/get-total-orders.php", "doc_frame" );
$sql                    = "SELECT count(*) AS 'כמות', 
CASE
  WHEN status = 1 THEN \"חדש\"
  WHEN status = 3 THEN \"נשלח\"
END AS 'מצב' FROM im_supplies WHERE status < 5 GROUP BY 2";
$links                  = [];
$links[0]               = "../supplies/supplies-get.php?status=%s";
$table[ $i ++ ][ $col ] = table_content( $sql, true, true, $links );

// Catalog
$i = 0;
$col ++;
$table[ $i ++ ][ $col ] = gui_header( 1, "קטלוג" );
$count                  = count_unmapped();
if ( $count > 0 ) {
	$table[ $i ++ ][ $col ] = gui_header( 2, "מיפויים" );
	$table[ $i ++ ][ $col ] = gui_hyperlink( $count . " פריטים לא ממופים ", "../catalog/catalog-map.php" );
}
$first = true;
foreach ( sql_query_single( "SELECT id FROM im_suppliers WHERE machine_update = TRUE " ) as $supplier_id ) {
	$PL       = new PriceList( $supplier_id );
	$a        = $PL->GetUpdateDate();
	$b        = date( 'Y-m-d' );
	$diff     = date_diff( date_create( $a ), date_create( $b ) );
	$day_diff = $diff->format( '%d' );

	if ( $day_diff > 3 ) {
		if ( $first ) {
			$table[ $i ++ ][ $col ] = gui_header( 2, "מחירונים לא מעודכנים" );
			$first                  = false;
		}
		// print $i . " "  . $col . "<br/>";
		$table[ $i ++ ][ $col ] = get_supplier_name( $supplier_id ) . " " . $a;
	}
}

$table[ $i ++ ][ $col ] = gui_hyperlink( "עדכן קטלוג", "../catalog/catalog-auto-update.php" );
$table[ $i ++ ][ $col ] = gui_hyperlink( "עדכן מכולת", "../pricelist/update-makolet.php" );
$table[ $i ++ ][ $col ] = gui_hyperlink( "הוספת פריטים", "../catalog/add-products.php" );

$i                         = 0;
$table[ $i ++ ][ ++ $col ] = gui_header( 1, "משלוחים" );

if ( ImMultiSite::LocalSiteID() == 2 ) {
	$table[ $i ++ ][ $col ] = gui_header( 2, "מכולת" );
	$table[ $i ++ ][ $col ] = gui_hyperlink( "מורשת", "../delivery/legacy.php" );
}

$i                         = 0;
$table[ $i ++ ][ ++ $col ] = gui_header( 1, "מלאי" );
$table[ $i ++ ][ $col ]    = gui_hyperlink( "איפוס", "../weekly/start.php" );

$i                         = 0;
$table[ $i ++ ][ ++ $col ] = gui_header( 1, "לקוחות" );
$table[ $i ++ ][ $col ]    = gui_hyperlink( "צור לקוח", "../account/add-account.php" );

for ( $i = 0; $i < sizeof( $table ); $i ++ ) {
	for ( $j = 0; $j < sizeof( $table[0] ); $j ++ ) {
		if ( is_null( $table[ $i ][ $j ] ) ) {
			$table[ $i ][ $j ] = " ";
		}
		// print $i . " " . $j . " " . $table[$i][$j] . "<br/>";
	}
	ksort( $table[ $i ] );
	// print htmlspecialchars(var_dump($table[$i]));
}

print gui_table( $table );


function count_unmapped() {
	global $conn;
	$sql    = "SELECT id FROM im_supplier_price_list";
	$result = mysqli_query( $conn, $sql );
	$count  = 0;

	while ( $row = mysqli_fetch_row( $result ) ) // mysql_fetch_row($export))
	{
		$pricelist_id = $row[0];

		$pricelist = PriceList::Get( $pricelist_id );

		$prod_id = Catalog::GetProdID( $pricelist_id )[0];
		if ( ( $prod_id == - 1 ) or ( $prod_id > 0 ) ) {
			continue;
		}
		$count ++;
	}

	return $count;
}


?>
                                                                                                                                                                                       ./tools/pricelist/pricelist.php                                                                     0000664 0001750 0001750 00000054376 13414637511 015500  0                                                                                                    ustar   agla                            agla                                                                                                                                                                                                                   <?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 06/12/15
 * Time: 10:16
 */
// require_once( '../im-tools.php' );
// error_reporting( E_ALL );
// ini_set( 'display_errors', 'on' );

if ( ! defined( "TOOLS_DIR" ) ) {
	define( 'TOOLS_DIR', dirname( dirname( __FILE__ ) ) );
}

require_once( TOOLS_DIR . '/catalog/catalog.php' );
require_once( ROOT_DIR . '/agla/gui/inputs.php' );
require_once( TOOLS_DIR . '/multi-site/imMulti-site.php' );
require_once( TOOLS_DIR . '/wp/Product.php' );
require_once( TOOLS_DIR . "/orders/orders-common.php" );
require_once( TOOLS_DIR . "/orders/Order.php" );

class PricelistItem {
	private $id;
	private $product_name;
	private $supplier_id;
	private $date;
	private $price;
	private $supplier_product_code;
	private $sale_price;
	private $category;
	private $picture_path;

	function __construct( $pricelist_id ) {
		$sql = " SELECT product_name, supplier_id, date, price, supplier_product_code, sale_price, category, picture_path FROM im_supplier_price_list " .
		       " WHERE id = " . $pricelist_id;

		$result = sql_query_single_assoc( $sql );
		if ( $result == null ) {
			print "pricelist item $pricelist_id not found<br/>";
			die( 1 );
		}
		$this->id                    = $pricelist_id;
		$this->product_name          = $result["product_name"];
		$this->supplier_id           = $result["supplier_id"];
		$this->date                  = $result["date"];
		$this->price                 = $result["price"];
		$this->supplier_product_code = $result["supplier_product_code"];
		$this->sale_price            = $result["sale_price"];
		$this->category              = $result["category"];
		$this->picture_path          = $result["picture_path"];
	}

	/**
	 * @return mixed
	 */
	public function getId() {
		return $this->id;
	}


	/**
	 * @return mixed
	 */
	public function getProductName() {
		return $this->product_name;
	}

	/**
	 * @return mixed
	 */
	public function getSupplierId() {
		return $this->supplier_id;
	}

	/**
	 * @return mixed
	 */
	public function getDate() {
		return $this->date;
	}

	/**
	 * @return mixed
	 */
	public function getPrice() {
		return $this->price;
	}

	/**
	 * @return mixed
	 */
	public function getSupplierProductCode() {
		return $this->supplier_product_code;
	}

	/**
	 * @return mixed
	 */
	public function getSalePrice() {
		return $this->sale_price;
	}

	/**
	 * @return mixed
	 */
	public function getCategory() {
		return $this->category;
	}

	/**
	 * @return mixed
	 */
	public function getPicturePath() {
		return $this->picture_path;
	}

	public function getSellPrice() {
		return calculate_price( $this->price, $this->supplier_id, $this->sale_price );
	}

	public function getSupplierName() {
		return get_supplier_name( $this->supplier_id );
	}
}


class UpdateResult {
	const UsageError = 0;
	const UpPrice = 1;
	const NoChangPrice = 2;
	const DownPrice = 3;
	const ExitPrice = 4;
	const NewPrice = 5;
	const SQLError = 6;
	const DeletePrice = 7;
	const NotUsed = 8;
}

class PriceList {
	private $SupplierID = 0;

	public function __construct( $id ) {
		$this->SupplierID = $id;
	}

	static function DeleteMapping( $pricelist_id ) {
		// Get product id
		$prod_ids    = Catalog::GetProdID( $pricelist_id );
		$supplier_id = sql_query_single_scalar( "SELECT supplier_id FROM im_supplier_price_list WHERE ID = " . $pricelist_id );
//		print "supplier id: " . $supplier_id . "<br/>";

		foreach ( $prod_ids as $prod_id ) {
//			print "prod id: " . $prod_id . "<br/>";
			$sql = "DELETE FROM im_supplier_mapping WHERE product_id = " . $prod_id . " AND supplier_id = " . $supplier_id;
//			print $sql . "<br/>";
			sql_query( $sql );

			$line = "";
			Catalog::UpdateProduct( $prod_id, $line );
//			print header_text(false, true, false);
//			print $line;

		}

		return;
	}

	function Refresh() {
		$priceslist_items = sql_query_array_scalar( "SELECT id FROM im_supplier_price_list WHERE supplier_id = " . $this->SupplierID );
		foreach ( $priceslist_items as $pricelist_id ) {
			$prod_ids = Catalog::GetProdID( $pricelist_id );
			foreach ( $prod_ids as $prod_id ) {
				print "update " . $prod_id . get_product_name( $prod_id ) . "<br>";

				Catalog::UpdateProduct( $prod_id, $line );
			}
		}
	}

	function SiteId() {
		return sql_query_single_scalar( "SELECT site_id FROM im_suppliers WHERE id =" . $this->SupplierID );
	}

	function PrintCSV() {
		global $conn;

		$sql = 'SELECT product_name, price, supplier_product_code' .
		       ' FROM im_supplier_price_list pl '
		       . ' where supplier_id = ' . $this->SupplierID;

		$result = mysqli_query( $conn, $sql );

		print "שם, מחיר, קוד\n";

		while ( $row = mysqli_fetch_row( $result ) ) {
			print $row[0] . ", " . $row[1] . ", " . $row[2] . "\n";
		}
	}

	function PrintHTML( $ordered_only = false, $need_supply_only = false ) {
		// print "nso=" . $need_supply_only . "oo=" . $ordered_only . "<br/>";
		Order::CalculateNeeded( $needed_products );

		$catalog = new Catalog();

		$sql = 'SELECT product_name, price, date, pl.id, supplier_product_code, s.factor ' .
		       ' FROM im_supplier_price_list pl ' .
		       ' Join im_suppliers s '
		       . ' where supplier_id = ' . $this->SupplierID
		       . ' and s.id = pl.supplier_id '
		       . ' order by 1';

		$result = sql_query( $sql );

		$table_rows = array(
			array(
				gui_checkbox( "select_all", "", false,
					'onclick="select_all_toggle(\'select_all\', \'product_checkbox\')"' ),
				"קוד פריט",
				"שם פריט",
				"תאריך שינוי",
				"מחיר קנייה",
				"מחיר מחושב",
				"קטגוריות",
				"שם מוצר",
				"מחיר מכירה",
				"מחיר מבצע",
				"מזהה",
				"מנוהל מלאי",
				"יתרה במלאי",
				"כמות בהזמנות פתוחות",
				"מחירים נוספים"
			)
		);

		if ( ! $ordered_only and ! $need_supply_only ) {
			array_unshift( $table_rows[0], gui_label( "delete_row", "מחק פריט" ) );
		}

		$show_fields = array( true, true, true, true, true, true, true, true, true, true, true, true );
		// Add new item fields
		while ( $row = mysqli_fetch_row( $result ) ) {
			$pl_id     = $row[3];
			$link_data = $catalog->GetProdID( $pl_id );
			$prod_id   = "";
			$map_id    = "";
			if ( $link_data ) {
				$prod_id = $link_data[0];
				$p       = new Product( $prod_id );
				$map_id  = null;

				if ( isset( $link_data[1] ) ) {
					$map_id = $link_data[1];
				}
				// print $prod_id . " " . $map_id . "<br/>";
				if ( $ordered_only and ! ( ( $needed_products[ $prod_id ][0] or $needed_products[ $prod_id ][1] ) ) ) {
					continue;
				}
				if ( $need_supply_only and ( $needed_products[ $prod_id ][0] <= $p->getStock() ) ) {
					continue;
				}
			} else {
				// print "nso=" . $need_supply_only . "<br/>";
				if ( $need_supply_only or $ordered_only ) {
					continue;
				}
			}
			$line = $this->Line( $row[0], $row[1], $row[2], $pl_id, $row[4], $row[5], $prod_id, true, $map_id, $needed_products );
			if ( ! $ordered_only and ! $need_supply_only ) {
				array_unshift( $line, gui_button( "del_" . $pl_id, "del_line(" . $pl_id . ")", "מחק" ) );
			}

			array_push( $table_rows, $line );
			// $data .= $line;
		}

		$sum  = null;
		$data = gui_table( $table_rows, "pricelist", true, true, $sum, null, null, $show_fields );
		//( $rows, $id = null, $header = true, $footer = true, &$sum_fields = null, $style = null, $class = null, $show_fields = null,
		// $links = null)
		// $data .= "<tr>";
		$data .= "<td>" . gui_button( "add", "add_item()", "הוסף" ) . "</td>";
		$data .= gui_cell( gui_input( "product_code", "" ) );
		$data .= gui_cell( gui_input( "product_name", "" ) );
		$data .= gui_cell( "" );

		$data .= gui_cell( gui_input( "price", "" ) );
		$data .= "</tr>";

		print $data;
	}

	private function Line( $product_name, $price, $date, $pl_id, $supplier_product_code, $factor, $linked_prod_id, $editable = true, $map_id, $needed = null ) {
		$calc_price = round( $price * ( 100 + $factor ) / 100, 1 );

		$line = array();
		array_push( $line, gui_checkbox( "chk" . $pl_id, "product_checkbox" ) );
		//$line .= "<td><input id=\"chk" . $pl_id . "\" class=\"product_checkbox\" type=\"checkbox\"></td>";
		array_push( $line, $supplier_product_code );
		// $line .= "<td>" . $supplier_product_code . "</td>";
		array_push( $line, $product_name );
		//$line .= "<td>" . $product_name . "</td>";
		array_push( $line, $date );
		// $line .= "<td>" . $date . "</td>";

		//$line .= "<td>";
		if ( $editable ) {
			array_push( $line, gui_input( "prc_" . $pl_id, $price, array( 'onchange="changed(this)"' ), "prc_" . $pl_id, "", 7 ) );
		} else {
			array_push( $line, $price );
		}
		// $line .= "</td>";
		// $line .= '<td><input type="text" value="' . $price . '"</td>';
		array_push( $line, $calc_price );
		// $line .= '<td>' . $calc_price . '</td>';
		$category = sql_query_single_scalar( "SELECT category FROM im_supplier_price_list WHERE id = " . $pl_id );
		array_push( $line, $category );
		if ( $linked_prod_id > 0 ) {
			$p = new Product( $linked_prod_id );
			array_push( $line, get_product_name( $linked_prod_id ) );
			array_push( $line, get_price( $linked_prod_id ) );
			array_push( $line, get_sale_price( $linked_prod_id ) );
			array_push( $line, $linked_prod_id );
			$stockManaged = $p->getStockManaged();
			array_push( $line, gui_checkbox( "chm_" . $linked_prod_id, "stock", $stockManaged, "onchange=\"change_managed(this)\")" ) );
			array_push( $line, gui_label( "stk_" . $linked_prod_id, gui_hyperlink( $p->getStock(), "../orders/get-orders-per-item.php?prod_id=" . $linked_prod_id ) ) );
			$n = orders_per_item( $linked_prod_id, 1, true, true, true );
//			if (isset($needed[$linked_prod_id][0]))
//				$n .= $needed[$linked_prod_id][0];
//			if (isset($needed[$linked_prod_id][1]))
//				$n .= $needed[$linked_prod_id][1] . "יח";

			array_push( $line, gui_label( "ord_" . $linked_prod_id, $n ) );
			array_push( $line, product_other_suppliers( $linked_prod_id, $this->SupplierID ) );

			// $line .= '<td>' . get_product_name( $linked_prod_id ) . '</td>';
			// $line .= '<td>' . get_price( $linked_prod_id ) . '</td>';
			//		$line .= '<td>' . get_sale_price( $linked_prod_id ) . '</td>';
		} else {
			if ( $linked_prod_id == - 1 ) {
				array_push( $line, "לא למכירה", "", "" );
				// $line .= "<td>לא למכירה</td><td></td><td></td>";
			} else {
				//var_dump(Catalog::GetProdOptions($product_name));die (1);
				array_push( $line, gui_select( "prd" . $pl_id, "post_title", Catalog::GetProdOptions( $product_name ), "onchange=selected(this)", "" ) );
				// array_push( $line, "", "", "" );
				// $line .= "<td></td><td></td><td></td>";
			}
		}
		// $line     .= gui_cell( $category);
//		if ( $linked_prod_id > 0 ) {
//			array_push( $line, gui_cell( $linked_prod_id ) );
//			// $line         .= gui_cell( $linked_prod_id );
//		//		$line         .= gui_cell( $map_id );
//			array_push( $line, gui_cell( $map_id ) );
//			if ( $needed ) {
//		//			var_dump($needed);
//		//			die(1);
//
//			}
//
//		}
		// get_product_name()
		// $line .= "</tr>";

		return $line;
	}

	function GetUpdateDate() {
		$sql = 'SELECT max(date) FROM im_supplier_price_list'
		       . ' WHERE supplier_id = ' . $this->SupplierID;

		$result = sql_query( $sql );

		$row = mysqli_fetch_row( $result );

		return $row[0];
	}

	function GetFactor() {
		$sql = 'SELECT factor FROM im_suppliers'
		       . ' WHERE id = ' . $this->SupplierID;

		$result = sql_query( $sql );

		$row = mysqli_fetch_row( $result );

		return $row[0];
	}

	function AddOrUpdate(
		$regular_price, $sale_price, $product_name, $code = 10, $category, &$id, $parent_id = null,
		$picture_path = null
	) {
		$debug = true;
//		print "start";
//		print "AddOrUpdate: " . $product_name . " " . $regular_price . "<br/>";
		my_log( __METHOD__, __FILE__ );
		if ( mb_strlen( $product_name ) > 40 ) {
			$product_name = mb_substr( $product_name, 0, 40 );
		}
		if ( ! is_numeric( $sale_price ) ) {
			$sale_price = 0;
		}
		if ( ! is_numeric( $regular_price ) ) {
			print "Bad price: " . $regular_price;

			return UpdateResult::UsageError;
		}
		// print "Add: " . $product_name . ", " . $regular_price . " " . $category . "<br/>";
		global $conn;

		// Change if line exits.
		$sql = "select id, price " .
		       " from im_supplier_price_list " .
		       " where supplier_id = " . $this->SupplierID .
		       " and product_name = '" . $product_name . "'";

		$date = date( 'y/m/d' );

		$id     = 0;
		$result = mysqli_query( $conn, $sql );

		if ( $result ) {
			$row = mysqli_fetch_row( $result );
			$id  = $row[0];
		}
		if ( $id == - 1 ) { // Hide
			return UpdateResult::NotUsed;
		}
		if ( $id > 0 ) {
			if ( $debug ) {
				print "Exists, update.. ";
			}
			$old_price = $this->Get( $id )["price"];
			$sql       = "update im_supplier_price_list " .
			             " set line_status = 1, price = " . $regular_price . ", sale_price = " . $sale_price .
			             ", date = '" . $date . "' ";

			if ( isset( $category ) ) {
				$sql .= ", category = '" . $category . "'";
			}

			if ( isset( $picture_path ) ) {
				$sql .= ", picture_path = '" . mysqli_real_escape_string( $conn, $picture_path ) . "'";
			} else {
				$sql .= ", picture_path = null";
			}
			$sql .= " where product_name = '" . $product_name . "' and supplier_id = " . $this->SupplierID;

			//  print "<br/>"  . $product_name . "<br/>";
			// print "<p dir='ltr'>"  . $sql . "</p>";

			$result = mysqli_query( $conn, $sql );
			if ( ! $result ) {
				sql_error( $sql );

				return UpdateResult::SQLError;
			}

			if ( $regular_price > $old_price ) {
				$rc = UpdateResult::UpPrice;
			} else if ( $regular_price < $old_price ) {
				$rc = UpdateResult::DownPrice;
			} else {
				$rc = UpdateResult::NoChangPrice;
			}

		} else {
			if ( $code == "" ) {
				$code = "10";
			}

			$sql = "INSERT INTO im_supplier_price_list (product_name, supplier_id, "
			       . "date, price, sale_price, supplier_product_code, line_status";

			$values = "VALUES ('" . addslashes( $product_name ) . "', " . $this->SupplierID .
			          ", " . "'" . $date . "', " . $regular_price . ", " . $sale_price . ", " . $code . ", 1";

			if ( $parent_id > 0 ) {
				// Variation
				$sql    .= ", variation, parent_id";
				$values .= ", 1, " . $parent_id;
			} else {
				// Product
				$sql    .= ", variation";
				$values .= ", 0";
			}

			if ( isset( $category ) ) {
				$sql    .= ", category ";
				$values .= ", '" . $category . "'";
			}
			if ( isset( $picture_path ) ) {
				$sql    .= ", picture_path ";
				$values .= ", '" . mysqli_real_escape_string( $conn, $picture_path ) . "'";
			}
			// Complete the sql statement
			$sql .= ") " . $values . ")";

			// print "<p dir=ltr>" . $sql . "</p>";

			$result = mysqli_query( $conn, $sql );
			if ( ! $result ) {
				sql_error( $sql );

				return UpdateResult::SQLError;
			}
			// Output
			$id = mysqli_insert_id( $conn );
			$rc = UpdateResult::NewPrice;
		}
		// Update linked products
		$this->Update( $id, $regular_price, $sale_price );
		if ( $debug ) {
			print "<br/>";
		}

		return $rc;
	}

	// Also called when mapping is deleted

	static function Get( $pricelist_id ) {
		// my_log("Pricelist::Get" . $pricelist_id);
		$sql = " SELECT product_name, supplier_id, date, price, supplier_product_code, sale_price, category, picture_path FROM im_supplier_price_list " .
		       " WHERE id = " . $pricelist_id;

		$result = sql_query_single_assoc( $sql );

		return $result;
	}

	// Return code: 0 - usage error: error. 1:
	// ID: output the pricelist id

	function Update( $id, $price, $sale_price = 0 ) {
		global $conn;
		my_log( __METHOD__, "update line $id, price $price, sale price $sale_price" );
		$sql = "UPDATE im_supplier_price_list SET price = " . $price .
		       ", sale_price = " . $sale_price .
		       ", date = '" . date( 'y/m/d' ) . "' " .
		       " WHERE id = " . $id;
		mysqli_query( $conn, $sql );

		$this->UpdateCatalog( $id );

//        $this->ExecuteRemotes("pricelist/pricelist-post.php?operation=update_in_slave&price=" . $price .           "&line_id=" . $line_id);

		//        $date = date('y/m/d');
//        my_log(__FILE__, __METHOD__);
//        my_log(__FILE__, "supplier: " . $this->SupplierID . ", price = " . $price . ", product name = " . $product_name_code);
//        if (! is_numeric($this->SupplierID)) {
//            die("bad supplier id: " . $this->SupplierID);
//        }
//        if (! is_numeric($price)) {
//            die ("Bad price: " . $price );
//        }
//        $link = $GLOBALS["glink"];
//        if (is_numeric($product_name_code) and $product_name_code != 10) {
//            $sql = "update im_supplier_price_list set date = '" . $date . "', price = " . $price
//                . " where supplier_product_code = '" . $product_name_code . "' and supplier_id = " . $this->SupplierID;
//        } else {
//            $sql = "update im_supplier_price_list set date = '" . $date . "', price = " . $price
//                . " where product_name = '" . addslashes($product_name_code) . "' and supplier_id = " . $this->SupplierID;
//        }
//
//        my_log($sql, "catalog_update_price");
//        if (! $export)
//            die ('Invalid query: ' . mysql_error());
		return;
	}

	static function UpdateCatalog( $pricelist_id ) {
		$debug    = true;
		$prod_ids = Catalog::GetProdID( $pricelist_id );
		$line     = "";
		if ( $debug ) {
			print "update";
		}
		if ( $prod_ids ) {
			foreach ( $prod_ids as $prod_id ) {
				if ( $debug ) {
					print $prod_id . " ";
				}
				my_log( __METHOD__, "update product $prod_id" );
				Catalog::UpdateProduct( $prod_id, $line );
				my_log( $line );
			}
		}
	}

	function GetByName( $product_name ) {
		$product_name = pricelist_strip_product_name( $product_name );

		$sql = "SELECT price FROM im_supplier_price_list "
		       . " WHERE product_name = '" . addslashes( $product_name ) . "' AND supplier_id = " . $this->SupplierID;

		return sql_query_single_scalar( $sql );
	}

//    function DraftRemoved()
//    {
//        global $conn;
//
//        $sql = "select id from ihstore.im_supplier_price_list where " .
//            " supplier_id = " . $this->SupplierID . " and line_status = 2";
//        $result = mysqli_result($sql);
//
//        if (!$result){
//            handle_sql_error($sql, $conn);
//        }
////        $table_name = "temp_supplier_" . $this->SupplierID;
////
////        $sql = "SELECT a.id " .
////            " FROM " . $table_name . " a " .
////            " LEFT JOIN im_supplier_price_list b " .
////            " ON a.product_name = b.product_name AND b.supplier_id = " . $this->SupplierID .
////            " WHERE b.id IS NULL ";
////
//////        print $sql . "<br/>";
////        $result = mysqli_query($conn, $sql);
////        if (! $result) die ($sql . mysqli_error($conn));
////
////        while ($row = mysqli_fetch_row($result))
////        {
////            print $row[0];
////        }
//    }

	function ChangeStatus( $status ) {
		// Act local
		global $conn;

		$sql = "UPDATE im_supplier_price_list SET line_status = " . $status . " WHERE supplier_id = " . $this->SupplierID;

//        print $sql;

		$result = mysqli_query( $conn, $sql );

		if ( ! $result ) {
			handle_sql_error( $sql );
		}

		// Act remote
//        $this->ExecuteRemotes("pricelist/pricelist-post.php?operation=change_status" . "&status=" . $status);
	}

//    function ExecuteRemotes($url)
//    {
//        // print "ExecuteRemotes: " . $url . "<br/>";
//        // my_log("Execute " . $url);
//        global $conn;
//
//        $sql = "select remote_site_id, remote_supplier_id from im_multisite_pricelist " .
//            " where supplier_id = " . $this->SupplierID;
//
//        my_log($sql);
//
//        $result = mysqli_query($conn, $sql);
//
//        if ($result)
//        while ($row = mysqli_fetch_row($result)) {
//            $url .= "&supplier_id=" . $row[1];
//            my_log($url, $row[0]);
//            MultiSite::Execute($url, $row[0]);
//        }
//    }

	function RemoveLines( $status ) {
		$removed = Array();

		// print "Removing previous items...<br/>";
		global $conn;
		$sql = "select id, price, product_name " .
		       " from im_supplier_price_list " .
		       " where line_status = " . $status .
		       " and supplier_id = " . $this->SupplierID;

		print $sql;

		$result = mysqli_query( $conn, $sql );

		if ( ! $result ) {
			handle_sql_error( $sql );
		}

		while ( $row = mysqli_fetch_row( $result ) ) {
			$id = $row[0];
			print "removing " . $id . "<br/>";
			my_log( "Remove " . $id );
			$this->Delete( $id );
			$removed[] = array( $row[2], $row[1] );
			// var_dump($ids);
		}
//        $this->ExecuteRemotes("pricelist/pricelist-post.php?operation=delete_price&params=" . implode(",", $ids));

//        print "Done<br/>";
		// var_dump($removed);
		return $removed;
	}

	function Delete( $pricelist_id ) {
		global $conn;
		my_log( __METHOD__ . $pricelist_id );

		// Check if this product linked.
		$prod_info = catalog::GetProdID( $pricelist_id );
		if ( $prod_info ) {
			$prod_id = $prod_info[0];
		} else {
			$prod_id = 0;
		}
		my_log( "Delete $pricelist_id $prod_id" );

		my_log( "Delete. id = " . $pricelist_id );
		my_log( "catalog_delete_price", "pricelist-post.php" );
		$sql = "DELETE FROM im_supplier_price_list  "
		       . " WHERE id = " . $pricelist_id;

		mysqli_query( $conn, $sql );

		// The mapping stays - in case supplier gets it back.

		// If no other option for this product - make it draft
		if ( $prod_id > 0 ) {
			$line = "";
			Catalog::UpdateProduct( $prod_id, $line );
		}
	}
}

function pricelist_get_price( $prod_id ) {
	// my_log("prod_id = " . $prod_id);
	if ( ! ( $prod_id > 0 ) ) {
		print "missing prod_id " . $prod_id . "<br/>";
		die ( 1 );
	}
	$supplier_id = get_supplier_id( get_postmeta_field( $prod_id, "supplier_name" ) );

	$sql = 'SELECT price FROM im_supplier_price_list WHERE supplier_id = \'' . $supplier_id . '\'' .
	       ' AND product_name IN (SELECT supplier_product_name FROM im_supplier_mapping WHERE product_id = ' . $prod_id . ')';

	return sql_query_single_scalar( $sql );
}


function pricelist_strip_product_name( $name ) {
	// trim sadot product name starting with * or **
	$name = str_replace( array( '.', ',', '*', '\'' ), '', $name );
	$name = str_replace( array( ')', '(', '-' ), ' ', $name );

	return $name;
}

function product_other_suppliers( $prod_id, $supplier_id ) {
	$result       = "";
	$alternatives = alternatives( $prod_id );
	foreach ( $alternatives as $alter ) {
		$a_supplier_id = $alter->getSupplierId();
		if ( $a_supplier_id != $supplier_id ) {
			$result .= get_supplier_name( $a_supplier_id ) . " " . $alter->getPrice() . ", ";
		}
	}

	return rtrim( $result, ", " );
}

?>                                                                                                                                                                                                                                                                  ./tools/pricelist/pricelist-get.php                                                                 0000664 0001750 0001750 00000044654 13415044647 016256  0                                                                                                    ustar   agla                            agla                                                                                                                                                                                                                   <?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 16/07/15
 * Time: 15:25
 */
//error_reporting( E_ALL );
//ini_set( 'display_errors', 'on' );

require_once( '../r-shop_manager.php' );
require_once( ROOT_DIR . '/agla/gui/inputs.php' );
require_once( "../suppliers/gui.php" );
require_once( "../multi-site/imMulti-site.php" );
require_once( "../suppliers/Supplier.php" );

function set_supplier_id() {
	if ( ! isset( $_GET["supplier_id"] ) ) {
		print 'var sel = document.getElementById("supplier_id");
                var selected = sel.options[sel.selectedIndex];
                supplier_id = selected.value;
                var site_id = selected.getAttribute("data-site-id");
                var tools = selected.getAttribute("data-tools-url-id");
                ';
	} else {
		$id      = $_GET["supplier_id"];
		$supp    = new Supplier( $id );
		$site_id = $supp->getSiteId();
		print 'var supplier_id = ' . $id . ';';
		if ( $site_id > 0 ) {
			print 'var site_id = ' . $site_id . ';';
			print 'var tools = \'' . ImMultiSite::SiteTools( $site_id ) . "';";
		} else {
			print 'var site_id = "";';
			print 'var tools = "";';
		}
	}

}

?>
<html dir="rtl" lang="he">
<header>
    <meta charset="UTF-8">
    <script type="text/javascript" src="/agla/client_tools.js"></script>
	<?php
	$map_table = "price_list";
	require_once( "../catalog/mapping.php" );
	?>
    <script>
        var supplier_id;

        function selected(sel) {
            var pricelist_id = sel.id.substr(3);
            document.getElementById("chk" + pricelist_id).checked = true;
        }

        function create_supply() {
			<?php
			set_supplier_id();
			?>

            var table = document.getElementById('pricelist');

            var collection = document.getElementsByClassName("product_checkbox");
            var params = new Array();

            for (var i = 0; i < collection.length; i++) {
                if (collection[i].checked) {
                    var prod_id = table.rows[i + 1].cells[11].innerText;
                    var stock = parseFloat(get_value_by_name("stk_" + prod_id));
                    if (isNaN(stock)) stock = 0;
                    var ordered = 1;
                    var ordered_text = get_value_by_name("ord_" + prod_id);
                    if (ordered_text.length > 2) {
                        ordered = parseFloat(ordered_text.substr(0, ordered_text.indexOf(":")));
                        if (isNaN(ordered)) ordered = 1;
                    }

                    // if (stock > ordered) continue;
                    // var code = get_value(table.rows[i+1].cells[1].firstChild);
                    // var name_code = get_value(table.rows[i+1].cells[2].firstChild);
                    // var new_price = get_value_by_name("prc_" + line_id);
                    // var sel = document.getElementById("supplier_id");
                    // var supplier_id = sel.options[sel.selectedIndex].value;

                    // if (code > 0 && code != 10) name_code = code;

                    var to_order = ordered - stock;
                    if (to_order < 3) to_order = 3;
                    params.push(prod_id);
                    params.push(to_order);
                    params.push(0); // units
                }
            }
            xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function () {
                // Wait to get query result
                if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
                {
                    add_message(xmlhttp.response);
                    // change_supplier();
                }
            }
            if (!params.length) {
                alert("יש לבחור חסרים במלאי כדי ליצור הספקה");
                return;
            }
            var request = "../supplies/supplies-post.php?operation=create_supply&supplier_id=" + supplier_id + "&create_info=" + params;
            xmlhttp.open("GET", request, true);
            xmlhttp.send();

        }

        function updatePrices() {
            var sel = document.getElementById("supplier_id");
            supplier_id = sel.options[sel.selectedIndex].value;

            xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function () {
                // Wait to get query result
                if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
                {
                    change_supplier();
                }
            }
            var request = "pricelist-post.php?operation=refresh_prices&supplier_id=" + supplier_id;
            xmlhttp.open("GET", request, true);
            xmlhttp.send();
        }

        function change_managed(field) {
            var subject = field.id.substr(4);
            var is_managed = get_value_by_name("chm_" + subject);

            xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function () {
                // Wait to get query result
                if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
                {

                }
            }
            var request = "pricelist-post.php?operation=managed&is_managed=" + is_managed + "&prod_id=" + subject;
            xmlhttp.open("GET", request, true);
            xmlhttp.send();
        }
        function inActiveList() {
            var sel = document.getElementById("supplier_id");
            supplier_id = sel.options[sel.selectedIndex].value;

            xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function () {
                // Wait to get query result
                if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
                {
                    change_supplier();
                }
            }
            var request = "pricelist-post.php?operation=inactive&supplier_id=" + supplier_id;
            xmlhttp.open("GET", request, true);
            xmlhttp.send();
        }

        function changed(field) {
            var subject = field.name.substr(4);
            document.getElementById("chk" + subject).checked = true;
        }

        function savePrices() {
			<?php
			set_supplier_id();
			?>

            var table = document.getElementById('price_list');
//            var sel = document.getElementById("supplier_id");
//            supplier_id = sel.options[sel.selectedIndex].value;

            var collection = document.getElementsByClassName("product_checkbox");
            var params = new Array();
            for (var i = 0; i < collection.length; i++) {
                if (collection[i].checked) {
                    var line_id = collection[i].id.substr(3);
                    // var code = get_value(table.rows[i+1].cells[1].firstChild);
                    // var name_code = get_value(table.rows[i+1].cells[2].firstChild);
                    var new_price = get_value_by_name("prc_" + line_id);
                    // var sel = document.getElementById("supplier_id");
                    // var supplier_id = sel.options[sel.selectedIndex].value;

                    // if (code > 0 && code != 10) name_code = code;

                    params.push(line_id, new_price);
                }
            }
            xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function () {
                // Wait to get query result
                if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
                {
                    // Test - don't reload page on each change.
                    alert("שינויים נשמרו. אפשר להמשיך לעדכן");
                    // change_supplier();
                }
            }
            var request = "pricelist-post.php?operation=update_price&supplier_id=" + supplier_id + "&params=" + params;
            xmlhttp.open("GET", request, true);
            xmlhttp.send();
        }

        function del_line(pricelist_id) {
            var btn = document.getElementById("del_" + pricelist_id);
            btn.parentElement.parentElement.style.display = 'none';
            execute_url("pricelist-post.php?operation=delete_price&params=" + pricelist_id);
        }
        function delPrices() {
            // var table = document.getElementById('price_list');
            // var sel = document.getElementById("supplier_id");
            // var id = sel.options[sel.selectedIndex].value;

            var collection = document.getElementsByClassName("product_checkbox");
            var params = new Array();
            for (var i = 0; i < collection.length; i++) {
                if (collection[i].checked) {
                    var id = collection[i].id.substr(3);
//                var name = get_value(table.rows[i+1].cells[1].firstChild);
//                var sel = document.getElementById("supplier_id");
//                var supplier_id = sel.options[sel.selectedIndex].value;

                    params.push(id);
                    //        alert(id);
                }
            }
            execute_url("pricelist-post.php?operation=delete_price&params=" + params, change_supplier);
        }

        function delMap() {
            var collection = document.getElementsByClassName("product_checkbox");
            var params = new Array();
            for (var i = 0; i < collection.length; i++) {
                if (collection[i].checked) {

                    var pricelist_id = collection[i].id.substr(3);

                    params.push(pricelist_id);
                    //        alert(map_id);
                }
            }
            var URL = "pricelist-post.php?operation=delete_map&params=" + params;
            execute_url(URL, change_supplier);
        }

        function donPrices() {
            var collection = document.getElementsByClassName("product_checkbox");
            var params = new Array();
            for (var i = 0; i < collection.length; i++) {
                if (collection[i].checked) {
                    var id = collection[i].id.substr(3);

                    params.push(id);
                }
            }
            xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function () {
                // Wait to get query result
                if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
                {
                    change_supplier();
                }
            }
            var sel = document.getElementById("supplier_id");
            supplier_id = sel.options[sel.selectedIndex].value;
            var request = "pricelist-post.php?operation=dont_price&params=" + params;
            // alert(request);
            xmlhttp.open("GET", request, true);
            xmlhttp.send();
        }

        function change_supplier() {
			<?php
			set_supplier_id();
			?>
            var upcsv = document.getElementById("upcsv");

            if (site_id > 0) {
                document.getElementById("btn_save").style.visibility = "hidden";
                document.getElementById("btn_delete").style.visibility = "hidden";
                upcsv.style.visibility = "hidden";
                document.getElementById("addcsv").style.visibility = "hidden";
            } else {
                document.getElementById("btn_save").style.visibility = "visible";
                document.getElementById("btn_delete").style.visibility = "visible";
                upcsv.style.visibility = "visible";
                upcsv.action = "pricelist-upload-supplier-prices.php?supplier_id=" + supplier_id;
                document.getElementById("addcsv").style.visibility = "visible";
            }

            xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function () {
                // Wait to get query result
                if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
                {
                    table = document.getElementById("price_list");
                    table.innerHTML = xmlhttp.response;
                }
            }
            xmlhttp.onloadend = function () {
                if (xmlhttp.status == 404 || xmlhttp.status == 500)
                    change_supplier();
            }
            var request = "pricelist-post.php?operation=get_priceslist&supplier_id=" + supplier_id;
//            var o = get_value_by_name("chk_ordered");
//            alert (o);
            if (get_value_by_name("chk_ordered")) request += "&ordered";
            if (get_value_by_name("chk_need_supply")) request += "&need_supply";

            xmlhttp.open("GET", request, true);
            xmlhttp.send();

            // Get last update date
            xmlhttp_date = new XMLHttpRequest();
            xmlhttp_date.onreadystatechange = function () {
                // Wait to get query result
                if (xmlhttp_date.readyState == 4 && xmlhttp_date.status == 200)  // Request finished
                {
                    label = document.getElementById("last_update");
                    label.innerHTML = xmlhttp_date.response;
                }
            }
            request = "pricelist-post.php?operation=header&supplier_id=" + supplier_id;
            xmlhttp_date.open("GET", request, true);
            xmlhttp_date.send();

            // Change the action of upload button according to selected supplier

            var fa = document.getElementById("addcsv");
            fa.action = "pricelist-upload-supplier-prices.php?add&supplier_id=" + supplier_id;

            var fd = document.getElementById("downcsv");
            fd.href = "pricelist-post.php?operation=get_csv&supplier_id=" + supplier_id;
            // alert(fd.action);

            // Disable buttons if pricelist is slave of other site
//        xmlhttp_slave = new XMLHttpRequest();
//        xmlhttp_slave.onreadystatechange = function()
//        {
//            // Wait to get query result
//            if (xmlhttp_slave.readyState==4 && xmlhttp_slave.status==200)  // Request finished
//            {
//                if (xmlhttp_slave.response.substr(0, 5) == "slave") {
////                    document.getElementById("div_add").style.visibility = 'hidden';
//                    document.getElementById("div_change").style.visibility = 'hidden';
//                    document.getElementById("is_slave").innerHTML = '<b>' + 'שים לב! מנוהל מרחוק' + '</b>';
//                    document.getElementById("upcsv").style.visibility = 'hidden';
//
//                } else {
////                    document.getElementById("div_add").style.visibility = 'visible';
//                    document.getElementById("div_change").style.visibility = 'visible';
//                    document.getElementById("is_slave").innerHTML = '';
//                    document.getElementById("upcsv").style.visibility = 'visible';
//                }
//            }
//        }
//        request = "pricelist-post.php?operation=is_slave&supplier_id=" + supplier_id;
//        xmlhttp_slave.open("GET", request, true);
//        xmlhttp_slave.send();
        }

        function add_item() {
			<?php
			set_supplier_id();
			?>

            savePrices();
            var code = get_value(document.getElementById("product_code"));
            var name = get_value(document.getElementById("product_name"));
            var price = get_value(document.getElementById("price"));
            xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function () {
                // Wait to get query result
                if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
                {
                    // For now after add, reload.
                    change_supplier();
                }
            }
            var request = "pricelist-post.php?operation=add_price&product_name=" + name + '&price=' + price +
                '&supplier_id=' + supplier_id;
            if (code.length > 0) request += "&code=" + code;
            xmlhttp.open("GET", request, true);
            xmlhttp.send();
        }

        function refresh() {
            change_supplier();
        }
    </script>
</header>

<style>
    h1 {
        text-align: center;
    }
</style>

<body onload="change_supplier()">
<h1>
    מחירון ספק

	<?php
	if ( ! isset ( $_GET["supplier_id"] ) ) {
		print_select_supplier( "supplier_id", true );
	} else {
		print get_supplier_name( $_GET["supplier_id"] );
	}
	?>
</h1>
<label id="last_update"></label>

<div id="div_change">
    <button id="btn_save" onclick="savePrices()">שמור עדכונים</button>
    <button id="btn_delete" onclick="delPrices()">מחק פריטים</button>
    <button id="btn_delete_map" onclick="delMap()">מחק מיפוי</button>
    <button id="btn_dontsell" onclick="donPrices()">לא למכירה</button>
	<?php
	$user = wp_get_current_user();
	if ( $user->ID == "1" ) {
		print '<button id="btn_delete_list" onclick="inActiveList()">הקפא ספק</button>';
	}
	print '<button id="btn_update_list" onclick="updatePrices()">עדכן מחירים</button>';

	?>
    <button id="btn_map" onclick="map_products()">שמור מיפוי</button>
    <button id="btn_create_supply" onclick="create_supply()">צור הספקה</button>

    <label id="log"></label>
</div>
<label id="is_slave"></label>
<br/>
</div>

<form name="upload_csv" id="upcsv" method="post" enctype="multipart/form-data">
    החלף רשימה של הספק:
    <input type="file" name="fileToUpload" id="fileToUpload">
    <input type="submit" value="החלף" name="submit">

    <input type="hidden" name="post_type" value="product"/>
</form>

<form name="add_csv" id="addcsv" method="post" enctype="multipart/form-data">
    הוסף לרשימה של הספק:
    <input type="file" name="fileToUpload" id="fileToUpload1">
    <input type="submit" value="הוסף" name="submit">

    <input type="hidden" name="post_type" value="product"/>
</form>

<?php

print gui_checkbox( "chk_ordered", "", "", "onchange=change_supplier()" );
print "הצג רק מוזמנים<br/>";

print gui_checkbox( "chk_need_supply", "", "", "onchange=change_supplier()" );
print "הצג רק פריטים להזמין<br/>";

//print gui_button("download", "download_csv()","הורד"); ?>

<!--<form id="downcsv" method="get" action="download_csv.php">-->
<!--    <button type="submit">הורד</button>-->
<!--    <input type='hidden' name='supplier_id'/>-->
<!--</form>-->
<a id="downcsv" href="path_to_file" download="pricelist.csv">הורד CSV</a>
<div id="price_list"></div>
<!--            <button id="btn_load_prices" onclick="load_file()">טען רשימה</button>-->

</div>

</body>
</html>                                                                                    ./tools/pricelist/pricelist-post.php                                                                0000664 0001750 0001750 00000015762 13415045015 016450  0                                                                                                    ustar   agla                            agla                                                                                                                                                                                                                   <?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 16/07/15
 * Time: 16:00
 */

require_once( '../r-shop_manager.php' );
require_once( 'pricelist.php' );
require_once( '../catalog/catalog.php' );
require_once( '../multi-site/imMulti-site.php' );

?>
<?php
// To map item from price list to our database the shop manager select item from the price list
// and product_id. The triplet: product_id, supplier_id and product_code are sent as saved
// in im_supplier_products

$operation = $_GET["operation"];
///my_log("Operation: " . $operation, __FILE__);

$supplier_id = $_GET["supplier_id"];
$pl          = new PriceList( $supplier_id );
$cat         = new Catalog();

$pricelist_id = 0;

$debug = false;
if ( $debug ) {
	print $operation;
}
switch ( $operation ) {

	case "get_priceslist":
		$pl->PrintHTML( isset( $_GET["ordered"] ) ? 1 : 0, isset( $_GET["need_supply"] ) ? 1 : 0 );
		break;

	case "get_csv":
		$pl->PrintCSV();
		break;

	case "refresh_prices":
		print "XXXX";
		print header_text( false, true, false );
		$pl->Refresh();
		break;

	case "update_price":
		$params = explode( ',', $_GET["params"] );
		for ( $pos = 0; $pos < count( $params ); $pos += 2 ) {
			$line_id = $params[ $pos ];
//            $supplier_id = $params[$pos + 2];
			$price = $params[ $pos + 1 ];
//                        $product_name_code = $params[$pos];
//            my_log("supplier_id " . $supplier_id, "pricelist-post.php");
			my_log( "price " . $price, "pricelist-post.php" );
//            my_log("product_name " . $product_name_code, "pricelist-post.php");
//			$regular_price, $sale_price = 0, $product_name = null, $code = 10, $category = null, &$id, $parent_id = null,
//		$picture_path = null

			// Todo - display and update sale price
			$pl->Update( $line_id, $price, 0 );
		}
		break;

	case "managed":
		if ( ! isset ( $_GET["prod_id"] ) ) {
			die( "send prod_id" );
		};
		if ( ! isset ( $_GET["is_managed"] ) ) {
			die( "send is_managed" );
		};
		$prod_id    = $_GET["prod_id"];
		$is_managed = $_GET["is_managed"] == "1";
		$P          = new Product( $prod_id );
//		print "fresh? " . $P->isFresh() . "<br/>";
		$P->setStockManaged( $is_managed, $P->isFresh() ? "yes" : "no" );
		break;

	case "inactive":
		print "Remove lines from list->draft items<br/>";
		$pl->RemoveLines( 1 );
		print "Remove the list<br/>";
		$sql = "UPDATE im_suppliers SET active = 0 WHERE id = " . $supplier_id;
		sql_query( $sql );
		break;

	case "delete_price":
		print "start delete";
		$params = explode( ',', $_GET["params"] );
		for ( $pos = 0; $pos < count( $params ); $pos ++ ) {
			$price_id = $params[ $pos ];

//            $sql = 'SELECT product_name, price FROM im_supplier_price_list'
//                . ' where id = ' . $price_id;
//
//            $export = mysql_query($sql) or die ("Sql error : " . mysql_error());
//
//            my_log("delete price " . $price_id . " product " . $row[0] . "price " . $row[1]);
			$pl->Delete( $price_id );
		}
		print "done delete";
		break;

	case "delete_map":
		print "start delete<br/>";
		$params = explode( ',', $_GET["params"] );
		for ( $pos = 0; $pos < count( $params ); $pos ++ ) {
			$pricelist_id = $params[ $pos ];
			// print $map_id . "<br/>";
			PriceList::DeleteMapping( $pricelist_id );
		}
		print "done delete";
		break;

	case "dont_price":
		my_log( "start dont sell" );
		$params = explode( ',', $_GET["params"] );
		for ( $pos = 0; $pos < count( $params ); $pos ++ ) {
			$price_id = $params[ $pos ];

			my_log( "hiding " . $price_id );

			$cat->HideProduct( $price_id );
		}
		my_log( print "done dont sell" );
		break;
	case "is_slave":
		$id  = $_GET["supplier_id"];
		$sql = "SELECT master FROM im_multisite_pricelist WHERE supplier_id = " . $id;
		// print $sql;
		$r = sql_query_single( $sql );
//        print $r[0];
		if ( $r[0] == '0' ) {
			print "slave";
		} else {
			print "master";
		}
		break;

	case "header":
		print gui_table( array(
			gui_row( array( gui_cell( "תאריך עדכון אחרון " ), gui_cell( " מרווח מכירה " ) ) ),
			gui_row( array( gui_cell( $pl->GetUpdateDate() ), gui_cell( $pl->GetFactor() ) ) )
		) );

		break;

	case "add_price":
		$product_name = $_GET["product_name"];
		$price        = $_GET["price"];
		$code         = 10;
		if ( isset( $_GET["code"] ) ) {
			$code = $_GET["code"];
		}

//        my_log("supplier_id " . $supplier_id, "pricelist-post.php");
//        my_log("price " . $price, "pricelist-post.php");
//        my_log("product_name " . $product_name, "pricelist-post.php");
//        my_log("date " . date('Y-m-d'), "pricelist-post.php");
//        print "Adding " . $product_name . " " . " price: " . $price . "<br/>";
		$pl->AddOrUpdate( trim( $price ), '', $product_name, $code, "", $pricelist_id, 0 );
// function AddOrUpdate( $regular_price, $sale_price, $product_name, $code = 10, $category, &$id, $parent_id = null ) {

		break;

	case "add_prices":
		// print "Params: " . $_GET["Params"] . "<br/>";
		$params = explode( ',', $_GET["Params"] );
		// var_dump($params);
		for ( $pos = 0; $pos < count( $params ); $pos += 2 ) {
			$product_name = $params[ $pos + 0 ];
			$price        = $params[ $pos + 1 ];
			//  print "Adding " . $product_name . " " . " price: " . $price . "<br/>";

			$pl->AddOrUpdate( $price, '', $product_name, 10, "", $pricelist_id, 0 );
			print $pricelist_id . "<br/>";
		}
		break;


//    case "add_in_slave":
//        print "add_in_slave";
//        $product_name = $_GET["product_name"];
//        $price = $_GET["price"];
//        $line_id = $_GET["line_id"];
////        my_log("supplier_id " . $supplier_id, "pricelist-post.php");
////        my_log("price " . $price, "pricelist-post.php");
////        my_log("product_name " . $product_name, "pricelist-post.php");
////        my_log("date " . date('Y-m-d'), "pricelist-post.php");
//        $pl->AddInSlave(trim($price), $product_name, $line_id);
//        break;

	case "update_in_slave":
		$product_name = $_GET["product_name"];
		$price        = $_GET["price"];
		$line_id      = $_GET["line_id"];
		my_log( "update in slave" . $product_name . " " . $price . " " . $line_id );
//        my_log("supplier_id " . $supplier_id, "pricelist-post.php");
//        my_log("price " . $price, "pricelist-post.php");
//        my_log("product_name " . $product_name, "pricelist-post.php");
//        my_log("date " . date('Y-m-d'), "pricelist-post.php");
		$pl->UpdateInSlave( trim( $price ), $line_id );
		break;

	case "map":
		$map_triplets = $_GET["map_triplets"];
		$ids          = explode( ',', $map_triplets );
		map_products( $ids );
		break;

	case 'change_status':
		$status      = $_GET["status"];
		$supplier_id = $_GET["supplier_id"];
		$PL          = new PriceList( $supplier_id );
		$PL->ChangeStatus( $status );
		break;

	case 'remove_status':
		$status      = $_GET["status"];
		$supplier_id = $_GET["supplier_id"];
		$PL          = new PriceList( $supplier_id );
		$PL->RemoveLines( $status );
		break;

	case 'get_prod':
		$pricelist_id = $_GET["pricelist"];
		$prod_link_id = Catalog::GetProdID( $pricelist_id, true );

		$prod_id = $prod_link_id[0];
		print $prod_id;
		break;

}

?>
              ./tools/delivery/close-deliveries.php                                                               0000664 0001750 0001750 00000000544 13414640420 016542  0                                                                                                    ustar   agla                            agla                                                                                                                                                                                                                   <?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 13/03/17
 * Time: 23:19
 */

require_once( '../r-shop_manager.php' );
require_once( '../multi-site/imMulti-site.php' );

$sql = "UPDATE wp_posts 
SET post_status = 'wc-completed' 
WHERE post_status='wc-awaiting-shipment'";

print mysqli_query( $conn, $sql ) . ImMultiSite::LocalSiteName() . "<br/>";
                                                                                                                                                            ./
tools / delivery / create - delivery - script . php                                                         0000664 0001750 0001750 00000053471 13415033747 017712  0                                                                                                    ustar   agla                            agla                                                                                                                                                                                                                   <?php
//error_reporting( E_ALL );
//ini_set( 'display_errors', 'on' );

require_once( "../im_tools.php" );
require_once( "../multi-site/imMulti-site.php" );
require_once( ROOT_DIR . '/agla/gui/inputs.php' );
require_once( 'delivery-common.php' );
require_once( "../../wp-includes/pluggable.php" );
require_once( "../account/account.php" );

$id       = isset( $_GET["id"] ) ? $_GET["id"] : null;
$order_id = $_GET["order_id"];
$edit     = false;
if ( $id > 0 ) {
	$edit     = true;
	$order_id = get_order_id( $id );
}

print gui_datalist( "items", "im_products", "post_title" );
?>

<script>
    const product_name_id = <?php print DeliveryFields::product_name; ?>;
    const q_quantity_ordered_id = <?php print DeliveryFields::order_q; ?>;
    const q_units = <?php print DeliveryFields::order_q_units; ?>;
    const q_supply_id = <?php print DeliveryFields::delivery_q; ?>;
    const has_vat_id = <?php print DeliveryFields::has_vat; ?>;
    const line_vat_id = <?php print DeliveryFields::line_vat; ?>;
    const price_id = <?php print DeliveryFields::price; ?>;
    const line_total_id = <?php print DeliveryFields::delivery_line; ?>;
    const term_id = <?php print DeliveryFields::term; ?>;
    const q_refund_id = <?php print DeliveryFields::refund_q ?>;
    const refund_total_id = <?php print DeliveryFields::refund_line; ?>;

    function getPrice(my_row) {
        var product_name = get_value(document.getElementById("nam_" + my_row));
        var request = "delivery-post.php?operation=get_price_vat&name=" + encodeURI(product_name);

		<?php
		if ( $type = get_client_type( order_get_customer_id( $order_id ) ) ) {
			print 'request = request + "&type=" + \'' . $type . '\';';
		}
		?>

        xmlhttp = new XMLHttpRequest();
        xmlhttp.onreadystatechange = function () {
            // Wait to get delivery id.
            if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
            {
                var response = xmlhttp.response.split(",");
                var price = response[0];
                var vat = response[1] > 0;

                if (price > 0) {
                    document.getElementById("prc_" + my_row).value = price;
                    document.getElementById("deq_" + my_row).focus();
                }

                document.getElementById("hvt_" + my_row).checked = vat;
            }
        }
        xmlhttp.open("GET", request, true);
        xmlhttp.send();
    }

    function moveNextRow(my_row) {
        if (event.which == 13) {
            var current = document.getElementsByName("quantity" + (my_row));
            current[0].value = Math.round(current[0].value * 10) / 10;
            var objs = document.getElementsByName("quantity" + (my_row + 1));
            calcDelivery();
            if (objs[0]) {
                objs[0].focus();
            } else {
                var del = document.getElementById("delivery");
                if (del) del.focus();
            }
        }
    }

    function addLine() {
        var table = document.getElementById('del_table');
//        var hidden = [];
//        for (var i = 0; i < table.rows.length; i++) {
//            for (var j = 0; j < table.rows[0].cells.length; j++)
//                table.rows[i].cells[j].style.display = 'visible';
////             hidden[i] = table.rows[0].cells[i].style.display;
//
//        }
//        return;

        var lines = table.rows.length;
        var line_id = lines - 4;
        var row = table.insertRow(lines - 4);
//        row.insertCell(0).style.visibility = false;              // 0 - select
        row.insertCell(-1).innerHTML = "<input id=\"nam_" + line_id + "\" type=\"text\" list=\"items\" onchange=\"getPrice(" + line_id + ")\">";   // 1 - product name
        row.insertCell(-1).innerHTML = "0";                       // 2 - quantity ordered
        row.insertCell(-1).innerHTML = "";                        // 3 - unit ordered
        // row.insertCell(-1).innerHTML = ""; // order total
        row.insertCell(-1).innerHTML = "<input id=\"deq_" + line_id + "\" type=\"text\" onchange='calcDelivery()'>";   // 4 - supplied
        row.insertCell(-1).innerHTML = "<input id=\"prc_" + line_id + "\" type=\"text\">";   // 5 - price
        row.insertCell(-1).innerHTML = "<label id=\"lpr_" + line_id + "\" type=\"text\">";   // line price
        row.insertCell(-1).innerHTML = "<input id=\"hvt_" + line_id + "\"  type = \"checkbox\" checked>"; // 6 - has vat
        row.insertCell(-1).id = "lvt_" + line_id;                       // 7 - line vat
        row.insertCell(-1).id = "del_" + line_id;   // 8 - total_line

        calcDelivery();
//        row.insertCell(9).style.visibility = false;              // 9 - categ
//        row.insertCell(10).style.visibility = false;              // 10 - refund q
//        row.insertCell(11).style.visibility = false;              // 11 - refund total
    }

    function addDelivery(draft) {
        calcDelivery();
        if (draft)
            execute_url("delivery-post.php?operation=check_delivery&order_id=" + <?php print $order_id; ?>, doAddDraft);
        else
            execute_url("delivery-post.php?operation=check_delivery&order_id=" + <?php print $order_id; ?>, doAdd);
    }

    function doAddDraft(xmlhttp) {
		<?php
		if ( isset( $id ) ) { // Was new when open
			print 'if (xmlhttp.response != ' . $id . ') { alert ("תעודה נשמרה במקום אחר. יש לסגור ולפתוח מחדש"); return; }';
		} else { // Was before open
			print 'if (xmlhttp.response != "none") { alert ("תעודה נשמרה במקום אחר. יש לסגור ולפתוח מחדש"); return; }';
		}
		?>
        do_add(1);
    }

    function doAdd(xmlhttp) {
		<?php
		if ( isset( $id ) ) { // Was new when open
			print 'if (xmlhttp.response != ' . $id . ') { alert ("תעודה נשמרה במקום אחר. יש לסגור ולפתוח מחדש"); return; }';
		} else { // Was before open
			print 'if (xmlhttp.response != "none") { alert ("תעודה נשמרה במקום אחר. יש לסגור ולפתוח מחדש"); return; }';
		}
		?>
        do_add(0);
    }

    function do_add(draft) {
        document.getElementById('btn_add').disabled = true;
		<?php if ( isset( $order_id ) ) print "var order_id = " . $order_id . ";" ?>

        var table = document.getElementById('del_table');
        var lines = table.rows.length;
        var total = table.rows[table.rows.length - 1].cells[line_total_id].firstChild.nodeValue;
        var total_vat = table.rows[table.rows.length - 2].cells[line_total_id].firstChild.nodeValue;
        var logging = document.getElementById('logging');
        var line_number = 0;
        var is_edit = false;

		<?php if ( $edit ) {
		print "is_edit = true;";
	} ?>

        // Enter delivery note to db.
        var request = "create-delivery-post.php?operation=add_header&order_id=" + order_id
            + "&total=" + total
            + "&vat=" + total_vat;

		<?php if ( $edit ) {
		print "request = request + \"&edit&delivery_id=" . $id . "\"";
	} ?>

        var delivery_id = 0;
        var saved_lines = 0;
        var fee = 0;
        var i;

        // Check number of lines in the delivery
        fee = get_value(document.getElementById("del_del"));
        for (i = 1; i < lines - 3; i++) {
            var prfx = table.rows[i].cells[0].id.substr(4);
            if (prfx === "")
                prfx = table.rows[i].cells[0].firstElementChild.id.substr(4);

            var quantity = get_value(document.getElementById("deq_" + prfx));
            if (quantity > 0 || quantity < 0) saved_lines++;
            var prod_name = get_value(document.getElementById("nam_" + prfx));
            if (prod_name === "דמי משלוח") fee = get_value(document.getElementById("del_" + prfx))


            // var product = get_value(table.rows[i].cells[product_name_id].firstChild);
            // if (product == "דמי משלוח") fee = get_value(table.rows[i].cells[line_total_id].firstChild);
        }
        request = request + "&lines=" + saved_lines;
        request = request + "&fee=" + fee;
        if (draft) request += "&draft";

        // Call the server to save the delivery
        server_header = new XMLHttpRequest();
        server_header.onreadystatechange = function () {
            // Wait to get delivery id.
            // 2) Save the lines.
            if (server_header.readyState == 4 && server_header.status == 200)  // Request finished
            {
                delivery_id = server_header.responseText.trim();
                logging.value += "תעודת משלוח מס " + delivery_id + "נשמרת " + "..";

                server_lines = new XMLHttpRequest();

                var line_request = "create-delivery-post.php?operation=add_lines&delivery_id=" + delivery_id;
                if (is_edit) line_request = line_request + "&edit";
                var line_args = new Array();

                // logging.value += response_text;
                // Enter delivery lines to db.
                for (i = 1; i < lines - 3; i++) {
                    var prfx = table.rows[i].cells[0].id.substr(4);
                    if (prfx === "")
                        prfx = table.rows[i].cells[0].firstElementChild.id.substr(4);
                    var prod_id;
                    var prod_name;
                    //if (parseInt(prfx) > 0) { // Regular line
                    prod_id = get_value(document.getElementById("pid_" + prfx));
                    prod_name = get_value(document.getElementById("nam_" + prfx));
//                    } else {
//                        // Special line:
//                        if (prfx === "dis") {
//                            prod_id = 0;
//                            prod_name = "הנחת כמות";
//                        }
//                    }
                    if (!(prod_id > 0)) prod_id = 0; // New or unknown
                    if (prod_name.length > 1) prod_name = prod_name.replace(/['"()%,]/g, "").substr(0, 20);

                    var quantity = get_value(document.getElementById("deq_" + prfx));
                    if (quantity === "") quantity = 0;

                    var quantity_ordered = get_value(document.getElementById("orq_" + prfx));
                    if (quantity_ordered === "") quantity_ordered = 0;

                    var unit_ordered = get_value(document.getElementById("oru_" + prfx));
                    if (unit_ordered.length < 1) unit_ordered = 0;

                    var price = get_value(document.getElementById("prc_" + prfx));
                    var vat = get_value(document.getElementById("lvt_" + prfx));
                    // var prod_name = get_value(table.rows[i].cells[product_name_id].firstChild);
                    var line_total = get_value(document.getElementById("del_" + prfx));
//                    if (table.rows[i].cells[0].children.length === 1) { // delivery lines or new line
//                        prod_id = 0;
//                        prod_name = get_value(table.rows[i].cells[0]);
//                        quantity = get_value(table.rows[i].cells[3]);
//                        quantity_ordered = 0;
//                        price = get_value(table.rows[i].cells[4]);
//                        vat = get_value(table.rows[i].cells[6]);
//                        line_total = get_value(table.rows[i].cells[7]);
//                    } else {
//                        line_total = get_value(table.rows[i].cells[line_total_id].firstChild);
//                    }

                    if (prod_id > 0 || line_total > 0 || line_total < 0) {
                        if (prod_id > 0)
                            push(line_args, prod_id);
                        else
                            push(line_args, encodeURIComponent(prod_name));
                        push(line_args, quantity);
                        push(line_args, quantity_ordered);
                        push(line_args, unit_ordered);
                        push(line_args, vat);
                        push(line_args, price);
                        push(line_args, line_total);
                    }
                }
                server_lines = new XMLHttpRequest();
                server_lines.onreadystatechange = function () {
                    if (server_lines.readyState === 4 && server_lines.status === 200) {  // Request finished
                        logging.value += "הסתיים.\n";
//                    3) Send the delivery notes to the client
                        // Now call the server, to send the delivery. It waits few seconds for the save lines to finish
                        if (!draft) {
                            var xmlhttp_send = new XMLHttpRequest();
                            var request = "send-delivery.php?del_id=" + delivery_id;
							<?php if ( $edit ) {
							print 'request = request + "&edit";     ';
						} ?>
                            logging.value += "תעודה נשלחת ללקוח";
							<?php
							$d = new delivery( $id );
							//                                if (strstr($d->getPrintDeliveryOption(), "P")) {
							//                                 //   print 'logging.style.display="false";';
							//                                    print 'location.replace("get-delivery.php?id=' . $id . '&print"); return;';
							//	                               // print 'logging.style.display="true";';
							//                                }

							?>
                            xmlhttp_send.open("GET", request);
                            xmlhttp_send.send();
                        }
                        location.replace(document.referrer);
                    }

                }

                line_request = line_request + "&lines=" + line_args.join();
                server_lines.open("GET", line_request, true);
                server_lines.send();
            }
        }

//	1) Send the header.
        server_header.open("GET", request, true);
        server_header.send();
    }

    function push(array, item) {
        if (item === null) {
            alert(null);
            item = '';
        }

        array.push(item);
    }

    function printDeliveryNotes() {
        document.getElementById('btn_calc').style.visibility = "hidden";
        document.getElementById('btn_print').style.visibility = "hidden";
        // Get the html
        var txt = document.documentElement.innerHTML;

        // Download the html
        var a = document.getElementById("a");
        var file = new Blob(txt, 'text/html');
        a.href = URL.createObjectURL(file);
        a.download = 're.html';

//	download(txt, 'myfilename.html', 'text/html')
//	window.open('data:text/html;charset=utf-8,<html dir="rtl" lang="he">' + txt + '</html>');

        document.getElementById('btn_calc').style.visibility = "visible";
        document.getElementById('btn_print').style.visibility = "visible";

    }

    function calcDelivery() {
        var table = document.getElementById('del_table');
        var total = 0;
        var total_vat = 0;
        var lines = table.rows.length;
        var quantity_discount = 0;
        var due_vat = 0;
        var delivery_fee = 0;

        for (var i = 1; i < lines; i++)  // Skip the header. Skip last lines: total, vat, total-vat, discount
        {
            if (table.rows[i].cells[0].id.substr(4, 3) == "bsk" || get_value(table.rows[i].cells[1]) == "הנחת סל") {
                // Sum upper lines and compare to basket price
                var j = i - 1;
                var sum = 0;
                // while (table.rows[j].cells[product_name_id].innerHTML.substr(0, 3) == "===") {
                while (get_value(document.getElementById("nam_" + j)).substr(0, 3) == "===") {
                    // alert(table.rows[j].cells[product_name_id].innerHTML);
                    sum = sum + Number(document.getElementById("del_" + j).innerHTML);
                    j = j - 1;
                }
                var basket_total = Number(get_value(document.getElementById("orq_" + j))) * Number(get_value(document.getElementById("prc_" + j)));
                if (sum > basket_total) {
                    diff = Math.round(100 * (basket_total - sum), 2) / 100;
                    table.rows[i].cells[q_supply_id].innerHTML = 1;
                    table.rows[i].cells[line_total_id].innerHTML = diff;
                    table.rows[i].cells[price_id].innerHTML = diff;
                    table.rows[i].cells[line_vat_id].innerHTML = 0;
                    total += diff;
                } else {
                    table.rows[i].cells[q_supply_id].innerHTML = '';
                    table.rows[i].cells[line_total_id].innerHTML = '';
                    table.rows[i].cells[price_id].innerHTML = '';
                }

                continue;
            }
            if (table.rows[i].cells[product_name_id].innerHTML == "סה\"כ חייבי מע\"מ") break;
            if (table.rows[i].cells[product_name_id].innerHTML == "" ||
                table.rows[i].cells[product_name_id].innerHTML == "הנחת כמות" ||
                table.rows[i].cells[product_name_id].innerHTML == "הנחת עובד") continue; // Reserved line for discount

            var q = 0;
            var p = 0;
            var line_total = 0;
            var vat_percent = <?php global $global_vat; print $global_vat; ?>;
            var line_vat = 0;
            var has_vat = true;
            var prfx = table.rows[i].cells[0].id.substr(4);
            if (prfx === "")
                prfx = table.rows[i].cells[0].firstElementChild.id.substr(4);

            p = get_value(document.getElementById("prc_" + prfx));
            if (prfx === "del") {
                q = 1;
            } else {
                q = get_value(document.getElementById("deq_" + prfx));
                if (eval(q) !== q) {
                    if (eval(q) > 0) {
                        q = eval(q);
                        document.getElementById("deq_" + prfx).value = q;
                    }
                }
            }
            has_vat = get_value_by_name("hvt_" + prfx);
            if (has_vat) line_vat = Math.round(100 * p * q / (100 + vat_percent) * vat_percent) / 100;
            line_total = Math.round(p * q * 100) / 100;
            document.getElementById("del_" + prfx).innerHTML = line_total.toString();
            document.getElementById("lvt_" + prfx).innerHTML = line_vat.toString();

            if (line_vat) due_vat += line_total;
            total_vat += line_vat;
            total += line_total;

            // Old q>=8 discount. Now in cart.
//            if (<?php
			//		        $customer_id = order_get_customer_id( $order_id );
			//
			//		        $result = 0;
			//		        if ( MultiSite::LocalSiteID() == 1 ) {
			//			        if ( customer_type( $customer_id ) == 0 ) {
			//				        $result = "(q >= 8) && (table.rows[i].cells.length > 7)";
			//			        }
			//		        }
			//		        print $result;
			//		        ?>
//            )
//            {
//                var line_term_id = table.rows[i].cells[term_id].innerHTML;
//                // alert (line_term_id);
//                var terms = line_term_id.split(",");
//                var fresh = false;
//                for (var x = 0; x < terms.length; x++) {
//                    if ([<?php //print_fresh_category()?>//].indexOf(parseInt(terms[x])) > -1) {
//                        fresh = true;
//                    }
//                }
//                if (fresh) quantity_discount += line_total;
//            }
        }

        var employee_discount = false;
		<?php
		$customer_id = order_get_customer_id( $order_id );
		$wp_user = get_user_by( 'id', $customer_id );
		$roles = $wp_user->roles;
		if ( $roles and customer_type( $customer_id ) == 0 // Not owner or siton
		                and count( array_intersect( array( "staff" ), $roles ) )
		) {
			print "employee_discount = true;";
		}

		?>
        // Show discount line or hide
        var line = table.rows.length - 4;
        var discount = 0;
        if (employee_discount) {
            var discount_gross = Math.round(total, 0); /// todo: get delivery_fee
            discount = -Math.round(discount_gross * 10) / 100;
            table.rows[line].cells[product_name_id].innerHTML = (discount_gross > 0) ? "הנחת עובד" : "";
            table.rows[line].cells[q_supply_id].innerHTML = (discount_gross > 0) ? -0.1 : "";
            table.rows[line].cells[price_id].innerHTML = discount_gross;
            table.rows[line].cells[line_vat_id].innerHTML = 0; // For now just for fresh. No VAT. (quantity_discount > 0) ? quantity_discount : "";

        } else {
            quantity_discount = Math.round(quantity_discount);
            table.rows[line].cells[product_name_id].innerHTML = (quantity_discount > 0) ? "הנחת כמות" : "";
            table.rows[line].cells[q_supply_id].innerHTML = (quantity_discount > 0) ? -0.15 : "";
            table.rows[line].cells[price_id].innerHTML = (quantity_discount > 0) ? quantity_discount : "";
            table.rows[line].cells[line_vat_id].innerHTML = 0; // For now just for fresh. No VAT. (quantity_discount > 0) ? quantity_discount : "";
            discount = -Math.round(quantity_discount * 15) / 100;
        }
        total = total + discount;
        table.rows[line].cells[line_total_id].innerHTML = (discount < 0) ? discount : "";

        // Update totals
        total = Math.round(100 * total, 2) / 100;
        due_vat = Math.round(100 * due_vat, 2) / 100;
//    round_total = Math.round(total);
//    table.rows[table.rows.length - 4].cells[line_total_id].firstChild.nodeValue = Math.round((round_total-total) *100)/100;
        // Due VAT
        document.getElementById("del_due").innerHTML = due_vat;
        // VAT
        document.getElementById("del_vat").innerHTML = Math.round(total_vat * 100) / 100;
        // Total
        document.getElementById("del_tot").innerHTML = total;
    }
</script>
                                                                                                                                                                                                       ./tools/delivery/delivery-common.php                                                                0000664 0001750 0001750 00000016752 13415042366 016433  0                                                                                                    ustar   agla                            agla                                                                                                                                                                                                                   <?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 05/08/17
 * Time: 12:38
 */
// require_once( "../multi-site/imMulti-site.php" );
//require_once ("../supplies/supplies.php");

require_once( ROOT_DIR . "/tools/tasklist/Tasklist.php" );

class DeliveryFields {
	const
		/// User interface
		line_select = 0,
		/// Product info
		product_name = 1,
		product_id = 2,
		term = 3,
		// Order info
		order_q = 4, // Only display
		order_q_units = 5,
		delivery_q = 6,
		price = 7,
		order_line = 8,
		// Delivery info
		has_vat = 9,
		line_vat = 10,
		delivery_line = 11,
		// Refund info
		refund_q = 12,
		refund_line = 13,
		buy_price = 14,
		line_margin = 15,
		max_fields = 16;
}

$delivery_fields_names = array(
	"chk", // 0
	"nam", // 1
	"pid", // 2
	"ter", // 3
	"orq", // 4
	"oru", // 5
	"deq", // 6
	"prc", // 7
	"orl", // 8
	"hvt", // 9
	"lvt", // 10
	"del", // 11
	"req", // 12
	"ret",  // 13
	"buy", //14
	"mar", // 15
);

$header_fields = array(
	"בחר",
	"פריט",
	"ID",
	"קטגוריה",
	"כמות הוזמן",
	"יחידות הוזמנו",
	"כמות סופק",
	"מחיר",
	"סה\"כ להזמנה",
	"חייב מע\"מ",
	"מע\"מ",
	"סה\"כ",
	"כמות לזיכוי",
	"סה\"כ זיכוי",
	"מחיר עלות",
	"סה\"כ מרווח שורה"
);

class ImDocumentType {
	const order = 1,
		delivery = 2,
		refund = 3;
}

class ImDocumentOperation {
	const
		collect = 0, // From order to delivery, before collection
		create = 1, // From order to delivery. Expand basket
		show = 2,     // Load from db
		edit = 3;     // Load and edit

}

function print_fresh_category() {
	$list = "";

	$option = sql_query_single_scalar( "SELECT option_value FROM wp_options WHERE option_name = 'im_discount_categories'" );
	if ( ! $option ) {
		return;
	}

	$fresh_categ = explode( ",", $option );
	foreach ( $fresh_categ as $categ ) {
		$list .= $categ . ",";
		foreach ( get_term_children( $categ, "product_cat" ) as $child_term_id ) {
			$list .= $child_term_id . ", ";
		}
	}
	print rtrim( $list, ", " );
}


function print_deliveries( $query, $selectable = false ) {
	// print "q= " . $query . "<br/>";
	$data = "";
	$sql  = 'SELECT posts.id, order_is_group(posts.id), order_user(posts.id) '
	        . ' FROM `wp_posts` posts'
	        . ' WHERE ' . $query;

	$sql .= ' order by 1';

	$orders    = sql_query( $sql );
	$prev_user = - 1;
	while ( $order = sql_fetch_row( $orders ) ) {
		$order_id   = $order[0];
		$is_group   = $order[1];
		$order_user = $order[2];
		if ( ! $is_group ) {
			$data .= print_order( $order_id, $selectable );
			continue;
		} else {
			if ( $order_user != $prev_user ) {
				$data      .= print_order( $order_id, $selectable );
				$prev_user = $order_user;
			}
		}
	}

	return $data;
}

function print_order( $order_id, $selectable = false ) {
	$site_tools = ImMultiSite::LocalSiteTools();

	$fields = array();

	if ( $selectable ) {
		array_push( $fields, gui_checkbox( "chk" . $order_id, "deliveries", true ) );
	}

	array_push( $fields, ImMultiSite::LocalSiteName() );

	$client_id     = order_get_customer_id( $order_id );
	$ref           = "<a href=\"" . $site_tools . "/orders/get-order.php?order_id=" . $order_id . "\">" . $order_id . "</a>";
	$address       = order_get_address( $order_id );
	$receiver_name = get_meta_field( $order_id, '_shipping_first_name' ) . " " .
	                 get_meta_field( $order_id, '_shipping_last_name' );
	$shipping2     = get_meta_field( $order_id, '_shipping_address_2', true );

	array_push( $fields, $ref );

	array_push( $fields, $client_id );

	array_push( $fields, $receiver_name );

	array_push( $fields, "<a href='waze://?q=$address'>$address</a>" );

	array_push( $fields, $shipping2 );

	array_push( $fields, get_user_meta( $client_id, 'billing_phone', true ) );
	$payment_method = get_payment_method_name( $client_id );
	if ( $payment_method <> "מזומן" and $payment_method <> "המחאה" ) {
		$payment_method = "";
	}
	array_push( $fields, $payment_method );

	array_push( $fields, order_get_mission_id( $order_id ) );

	array_push( $fields, ImMultiSite::LocalSiteID() );
	// array_push($fields, get_delivery_id($order_id));


	$line = "<tr> " . delivery_table_line( 1, $fields ) . "</tr>";

	// get_field($order_id, '_shipping_city');

	return $line;
}

function delivery_table_line( $ref, $fields, $edit = false ) {
	//"onclick=\"close_orders()\""
	$row_text = "";
	if ( $edit ) {
		$row_text = gui_cell( gui_checkbox( "chk_" . $ref, "", "", null ) );
	}

	foreach ( $fields as $field ) // display customer name
	{
		$row_text .= gui_cell( $field );
	}

	return $row_text;
}

function delivery_table_header( $edit = false ) {
	$data = "";
	$data .= "<table><tr>";
	$data .= "<td><h3>אתר</h3></td>";
	$data .= "<td><h3>מספר </br>/הזמנה<br/>אספקה</h3></td>";
	$data .= "<td><h3>מספר </br>לקוח</h3></td>";
//	$data .= "<td><h3>שם המזמין</h3></td>";
	$data .= "<td><h3>שם המקבל</h3></td>";
	$data .= "<td><h3>כתובת</h3></td>";
	$data .= "<td><h3>כתובת-2</h3></td>";
	$data .= "<td><h3>טלפון</h3></td>";
	// $data .= "<td><h3></h3></td>";
	$data .= "<td><h3>מזומן/המחאה</h3></td>";
	$data .= "<td><h3>משימה</h3></td>";
	$data .= "<td><h3>אתר</h3></td>";
	$data .= "<td><h3>מספר משלוח</h3></td>";

	// $data .= "<td><h3>מיקום</h3></td>";
	return $data;
}

function print_task( $id ) {
	$fields = array();
	array_push( $fields, "משימות" );

	$ref = gui_hyperlink( $id, "../tasklist/c-get-tasklist.php?id=" . $id );

	array_push( $fields, $ref );

	$T = new Tasklist( $id );

	array_push( $fields, "" ); // client number
	array_push( $fields, $T->getLocationName() ); // name
	array_push( $fields, $T->getLocationAddress() ); // address
	array_push( $fields, $T->getTaskDescription() ); // address 2
	array_push( $fields, "" ); // phone
	array_push( $fields, "" ); // payment
	array_push( $fields, $T->getMissionId() ); // payment
	array_push( $fields, ImMultiSite::LocalSiteID() );

	$line = gui_row( $fields );

	print $line;

}

function print_supply( $id ) {
//	$site_tools = MultiSite::LocalSiteTools();

	$fields = array();
	array_push( $fields, "supplies" );

	$address = "";

	$supplier_id = supply_get_supplier_id( $id );
	$ref         = gui_hyperlink( $id, "../supplies/supply-get.php?id=" . $id );
	$address     = sql_query_single_scalar( "SELECT address FROM im_suppliers WHERE id = " . $supplier_id );
//	$receiver_name = get_meta_field( $order_id, '_shipping_first_name' ) . " " .
//	                 get_meta_field( $order_id, '_shipping_last_name' );
//	$shipping2     = get_meta_field( $order_id, '_shipping_address_2', true );
//	$mission_id    = order_get_mission_id( $order_id );
//	$ref           = $order_id;
//
	array_push( $fields, $ref );
//
	array_push( $fields, $supplier_id );
//
	array_push( $fields, "<b>איסוף</b> " . get_supplier_name( $supplier_id ) );
//
	array_push( $fields, "<a href='waze://?q=$address'>$address</a>" );
//
	array_push( $fields, "" );
//
	array_push( $fields, sql_query_single_scalar( "SELECT supplier_contact_phone FROM im_suppliers WHERE id = " . $supplier_id ) );
//	$payment_method = get_payment_method_name( $client_id );
//	if ( $payment_method <> "מזומן" and $payment_method <> "המחאה" ) {
//		$payment_method = "";
//	}
	array_push( $fields, "" );
//
	array_push( $fields, sql_query_single_scalar( "SELECT mission_id FROM im_supplies WHERE id = " . $id ) );
//
	array_push( $fields, imMultiSite::LocalSiteID() );
	// array_push($fields, get_delivery_id($order_id));


	$line = "<tr> " . delivery_table_line( 1, $fields ) . "</tr>";

	// get_field($order_id, '_shipping_city');

	print $line;

}

                      ./
tools / delivery / get - driver . php                                                                     0000664 0001750 0001750 00000020557 13414640420 015362  0                                                                                                    ustar   agla                            agla                                                                                                                                                                                                                   <?php
error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

require_once( '../r-shop_manager.php' );
// require_once( '../../wp-content/plugins/woocommerce-delivery-notes/woocommerce-delivery-notes.php' );
require_once( '../multi-site/imMulti-site.php' );
require_once( '../account/account.php' );
require_once( "../supplies/supplies.php" );

$header     = isset( $_GET["header"] );
$mission_id = get_param( "mission_id" );

//if (isset($_GET["week"])) $week = $_GET["week"];
//$footer = $_GET["footer"];
//$header = ( MultiSite::LocalSiteID() == 1 );
// print "Start " . MultiSite::LocalSiteName();
// print $header;
if ( $header ) {
	print header_text( false );
	print '<style type="text/css" media="print">
    .page
    {
        -webkit-transform: rotate(-90deg);
        -moz-transform:rotate(-90deg);
        filter:progid:DXImageTransform.Microsoft.BasicImage(rotation=3);
    }
</style>';
	print "<header style='text-align: center'><center><h1>מסלולים ליום " . date( 'd/m/Y' );
	print "</h1></header>";
	print delivery_table_header();
}
$print = 1; //$_GET["print"];

if ( $mission_id ) {
	$table = print_deliveries( "mission_id = " . $mission_id );
} else {
	$table = print_deliveries( //"post_excerpt like '%משלוח המכולת%' " .
		" `post_status` in ('wc-awaiting-shipment', 'wc-processing')" );
}

print $table;
print_driver_supplies( $mission_id );

print_driver_tasks( $mission_id );

//else print_archive_deliveries($week);
//
//print_legacy();

die( 0 );

function sort_key( $zone_order, $long_lat ) {
	// $x = sprintf("%.02f", 40 - $long_lat[0]);
	// $y = sprintf("%.02f", $long_lat[1]);

//     $coor = 100 * (40 -$long_lat[0]) + $long_lat[1];

	$sort_index = $zone_order . " " . $long_lat[0] . " " . $long_lat[1];

	return $sort_index;
}

// print "loop end<br>";


function get_zone_order( $zone_id ) {
	if ( ! is_numeric( $zone_id ) ) {
		print __METHOD__ . " got " . $zone_id . "<br/>";
		die( 1 );
	}
//    print "zone=" . $zone . "<br/>";
//	$shipping = ;
//	// print "shipping: " . $shipping[0] . "<br/>";
//	if (is_string($shipping)){
//		$zone = strtok(substr($shipping, strpos($shipping,"flat_rate") + 10), "\"");
//		// print "order zone=" . $zone . "<br/>";
//	}
//	// mot found shipping method from order.
//	// Take default from client
//	if (zone_get_name($zone) == 'N/A') $zone = get_user_meta($client_id, 'shipping_zone', true);
//	if (! is_numeric($zone)) {
//	    $zone = "00";
//		// print "default zone=" . $zone . "<br/>";
//		return $client_id;
//	}
//
	$sql = "SELECT zone_delivery_order FROM wp_woocommerce_shipping_zones WHERE zone_id = " . $zone_id;

//	// print $sql . "<br/>";
	return sprintf( "%02d", sql_query_single_scalar( $sql ) );
}

function print_legacy() {
	global $conn;

	$site_tools = ImMultiSite::LocalSiteTools();

	$sql = "SELECT id, client_id, mission_id FROM im_delivery_legacy " .
	       " WHERE status = 1";

	$result     = mysqli_query( $conn, $sql );
	$data_lines = array();

	if ( $result ) {
		while ( $row = mysqli_fetch_assoc( $result ) ) {
			$fields = array();

			array_push( $fields, "המכולת" );

			array_push( $fields, "" ); // order number

			$client_id = $row['client_id'];
			// print "client_id = " .$client_id . "<br/>";
			$ref = $row['id'];

			array_push( $fields, $client_id );
			$user_info = get_userdata( $client_id );

			// display customer name
			$name = $user_info->first_name . " " . $user_info->last_name;

			array_push( $fields, gui_hyperlink( $name, $site_tools . "../../wp-admin/user-edit.php?user_id=" . $client_id ) );

			$address = "";
			foreach ( array( 'shipping_address_1', 'shipping_city' ) as $field ) {
				$address .= get_user_meta( $client_id, $field )[0] . " ";
			}

			array_push( $fields, "<a href='waze://?q=$address'>$address</a>" );

			foreach ( array( 'shipping_address_2', 'billing_phone' ) as $field ) {
				$field_value = get_user_meta( $client_id, $field, true );
				// print $ref . " " . $field_value . "<br/>";
				array_push( $fields, $field_value );
			}

			array_push( $fields, "" ); // Billing method

			$postcode = get_user_meta( $client_id, 'shipping_postcode', true );
//            print $postcode . "<br/>";

			$zone_id = get_zone_from_postcode( $postcode );

			array_push( $fields, $row['mission_id'] );

			// $client_id = $ref;
			$long_lat = get_long_lat( $client_id, $address );
			// var_dump( $long_lat); print "<br/>";


			if ( ! $zone_id ) {
				$zone_id = intval( get_user_meta( $client_id, 'shipping_zone', true ) );
			}
			/// print $zone_id . "<br/>";

			array_push( $fields, zone_get_name( $zone_id ) );

			$zone_order = get_zone_order( $zone_id );

//	    $x = sprintf("%.02f", 40 - $long_lat[0]);
//	    $y = sprintf("%.02f", $long_lat[1]);

			$sort_index = sort_key( $zone_order, $long_lat );
			// $sort_index = $day . "/" . $zone_order . ":" . $x . "-" . $long_lat[1];
			array_push( $fields, $sort_index );

			$line = "<tr> " . delivery_table_line( $ref, $fields ) . "</tr>";

			array_push( $data_lines, array( $sort_index, $line, $ref ) );
		}
	}
	sort( $data_lines );

	$data = "";
	for ( $i = 0; $i < count( $data_lines ); $i ++ ) {
		$line = $data_lines[ $i ][1];
		$data .= trim( $line );

		//	$print_url_id = $data_lines[$i][2] . "-" . $print_url_id;
	}
	print $data;
}

//print "done legacy<br/>";

function get_payment_name( $method ) {
	switch ( $method ) {
		case "bacs":
			return "העברה";
		case "cheque":
			return "כ. אשראי";
		case "cod":
			return "מזומן";
	}

	return $method;
}

function get_delivery_driver( $order_id ) {
	// print "get_delivery_driver " . $order_id . "<br/>";
	$city = get_meta_field( $order_id, '_shipping_city' );

	$sql = 'SELECT path  FROM im_paths WHERE city = "' . $city . '"';
	// print $sql . "<br>";
	$result = sql_query( $sql );
	$row    = mysqli_fetch_row( $result );

	$sql = 'SELECT driver FROM im_path_info WHERE id = "' . $row[0] . '"';
	// print $sql . "<br>";
	$result = sql_query( $sql );
	$row    = mysqli_fetch_row( $result );

	// print "done<br>";

	return $row[0];
}

function get_order_order( $order_id ) {
	print "get_order_order";
	$city = get_meta_field( $order_id, '_shipping_city' );

	$sql = 'SELECT city_order, path FROM im_paths WHERE city = "' . $city . '"';
	// print    $sql . "<br>";
	$result = sql_query( $sql );
	$row    = mysqli_fetch_row( $result );
//	print $row[0] + "<br>";
	$city_order = $row[0];
	$path       = $row[1];
	print "done<br/>";

	return 100 * $path + $city_order;
}

function get_long_lat( $client_id, $address ) {
	$long_lat = get_user_meta( $client_id, "long_lat", true );
	// print "llt=" . $long_lat[0]. ":" . $long_lat[1] . "<br/>";

	if ( ! $long_lat or $long_lat[0] == "" ) {
		$long_lat = do_get_long_lat( $address );
		update_user_meta( $client_id, "long_lat", $long_lat );
	}

	return $long_lat;
}


function do_get_long_lat( $address ) {
	// print $address . "<br/>";
	$dashed_address = str_replace( " ", "-", $address );

	//print $dashed_address . "<br/>";
	$url = "http://maps.google.com/maps/api/geocode/json?address=" . $dashed_address . "&sensor=false&region=Israel";

	$ch = curl_init();
	curl_setopt( $ch, CURLOPT_URL, $url );
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
	curl_setopt( $ch, CURLOPT_PROXYPORT, 3128 );
	curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 0 );
	curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0 );
	$response = curl_exec( $ch );
	// print $response . " " . $dashed_address . "<br/>";
	curl_close( $ch );
	$response_a = json_decode( $response );
	$lat        = $response_a->results[0]->geometry->location->lat;
	$long       = $response_a->results[0]->geometry->location->lng;

	return array( $lat, $long );
}

function print_driver_supplies( $mission_id = 0 ) {
	// Self collect supplies
	$sql = "SELECT s.id FROM im_supplies s
          JOIN im_suppliers r
          WHERE r.self_collect = 1
          AND s.supplier = r.id
          AND s.status IN (1, 3)" .
	       " AND s.picked = 0";

	// print $sql;


	if ( $mission_id ) {
		$sql .= " AND s.mission_id = " . $mission_id;
	}

	$supplies = sql_query_array_scalar( $sql );
	foreach ( $supplies as $supply ) {
//	    print "id: " . $supply . "<br/>";
		print_supply( $supply );
	}
}

function print_driver_tasks( $mission_id = 0 ) {
	if ( ! table_exists( 'im_tasklist' ) ) {
		return;
	}

	// Self collect supplies
	$sql = "SELECT t.id FROM im_tasklist t " .
	       "WHERE (status < 2)";

	if ( $mission_id ) {
		$sql .= " and t.mission_id = " . $mission_id;
	}

	$tasks = sql_query_array_scalar( $sql );
	foreach ( $tasks as $task ) {
		print_task( $task );
	}
}

?>
</html>
                                                                                                                                                 ./tools/delivery/get-driver-multi.php                                                               0000664 0001750 0001750 00000021653 13415043266 016516  0                                                                                                    ustar   agla                            agla                                                                                                                                                                                                                   
<html>
<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 08/03/17
 * Time: 22:41
 */
error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

require_once( "../im_tools.php" );
// TODO: require_once( '../r-shop_manager.php' );
require_once( '../multi-site/imMulti-site.php' );
require_once( '../maps/build-path.php' );
require_once( '../missions/Mission.php' );

$debug = false;
// $addresses = array();

if ( isset( $_GET["debug"] ) ) {
	$debug = true;
}

if ( isset( $_GET["week"] ) ) {
	$week = $_GET["week"];
} else {
	$week = date( "Y-m-d", strtotime( "last sunday" ) );
}

if ( date( 'Y-m-d' ) > date( 'Y-m-d', strtotime( $week . "+1 week" ) ) ) {
	print gui_hyperlink( "שבוע הבא", "get-driver-multi.php?week=" . date( 'Y-m-d', strtotime( $week . " +1 week" ) ) ) . " ";
}

//print gui_hyperlink( "שבוע קודם", "get-driver-multi.php?week=" . date( 'Y-m-d', strtotime( $week . " -1 week" ) ) );

print header_text( false, true, true );

?>
<style>
    @media print {
        h1 {
            page-break-before: always;
        }
    }
</style>
<script>
    function delivered(site, id, type) {
        var url = "delivery-post.php?site_id=" + site + "&type=" + type +
            "&id=" + id + "&operation=delivered";

        xmlhttp = new XMLHttpRequest();
        xmlhttp.onreadystatechange = function () {
            // Wait to get query result
            if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
            {
                // alert (xmlhttp.response);
                if (xmlhttp.response == "delivered") {
                    var row = document.getElementById("chk_" + id).parentElement.parentElement;
                    var table = row.parentElement.parentElement;
                    table.deleteRow(row.rowIndex);
                } else {
                    alert("failed: " + xmlhttp.response);
                }
                // window.location = window.location;
            }
        }

        xmlhttp.open("GET", url, true);
        xmlhttp.send();
    }
</script>
<?php

function get_text( $row, $index ) {
	$cell = $row->find( 'td', $index );
	if ( $cell ) {
		return $cell->plaintext;
	}

	return "";
}

$data_lines = array();

$header = null;
// print "week = " . $week . "<br/>";
$m      = new ImMultiSite();
$output = $m->GetAll( "delivery/get-driver.php?week=" . $week, $debug );

$dom = im_str_get_html( $output );

foreach ( $dom->find( 'tr' ) as $row ) {
	if ( ! $header ) {
		for ( $i = 0; $i < 7; $i ++ ) {
			if ( $i != 2 ) {
				$header .= $row->find( 'td', $i );
			}
		}
		$header .= gui_cell( gui_header( 3, "מספר ארגזים, קירור" ) );
		$header .= gui_cell( gui_header( 3, "נמסר" ) );
		$header .= gui_cell( gui_header( 3, "ק\"מ ליעד" ) );
		$header .= gui_cell( gui_header( 3, "דקות" ) );
		$header .= gui_cell( gui_header( 3, "דקות מצטבר" ) );
		continue;
	}
	// $key_fields = $row->find( 'td', 11 )->plaintext;
	$site = $row->find( 'td', 0 )->plaintext;
	if ( $site == 'אתר' ) {
		continue;
	}
	$order_id               = $row->find( 'td', 1 )->plaintext;
	$user_id                = $row->find( 'td', 2 )->plaintext;
	$name                   = $row->find( 'td', 3 )->plaintext;
	$addresses[ $order_id ] = $row->find( 'td', 4 )->plaintext;
	$site_id                = $row->find( 'td', 9 )->plaintext;
	$delivery_id            = get_text( $row, 10 );

	// print "name = " . $name . " key= "  . $key . "<br/>";
	$mission_id = $row->find( 'td', 8 )->plaintext;
	$line_data  = "<tr>";
	for ( $i = 0; $i < 7; $i ++ ) {
		if ( $i <> 2 ) {
			$line_data .= $row->find( 'td', $i );
		}
	}
	$line_data .= gui_cell( "" ); // #box
	$type      = "orders";
	if ( $site == "supplies" ) {
		$type = "supplies";
	}

	if ( $site == "משימות" ) {
		$type = "tasklist";
	}
	if ( ! is_numeric( $site_id ) ) {
		die ( $site_id . " not number" . $site_id . " order_id = " . $order_id . " name = " . $name . " <br/>" );
	}
	$line_data .= gui_cell( gui_checkbox( "chk_" . $order_id, "", "",
		'onchange="delivered(' . $site_id . "," . $order_id . ', \'' . $type . '\')"' ) ); // #delivered
	$line_data .= gui_cell( $site_id );

	$line_data .= "</tr>";
	if ( ! isset( $data_lines[ $mission_id ] ) ) {
		$data_lines[ $mission_id ] = array();
		/// print "new: " . $mission_id . "<br/>";
	}
	array_push( $data_lines[ $mission_id ], array( $addresses[ $order_id ], $line_data ) );
	// var_dump($line_data); print "<br/>";
}

foreach ( $data_lines as $mission_id => $data_line ) {
//    $mission_id = 152;
//    $data_line = $data_lines[152];1
//    if (1){
	if ( ! ( $mission_id > 0 ) ) {
		// print "mission 0 skipped<br/>";
		continue;
	}
//        die ("no mission id");

	$mission = Mission::getMission( $mission_id );

	print gui_header( 1, get_mission_name( $mission_id ) . "($mission_id)" );

	if ( $debug ) {
		print_time( "start handle mission " . $mission_id, true );
	}

	// Collect the stop points
	$path              = array();
	$stop_points       = array();
	$lines_per_station = array();
	for ( $i = 0; $i < count( $data_lines[ $mission_id ] ); $i ++ ) {
		$stop_point = $data_lines[ $mission_id ][ $i ][0];
		$dom        = im_str_get_html( $data_lines[ $mission_id ][ $i ][1] );
		$row        = $dom->find( 'tr' );
		$site       = get_text( $row[0], 0 );
		$site_id    = get_text( $row[0], 8 );
		$order_id   = get_text( $row[0], 1 );
		$customer   = get_text( $row[0], 2 );
		$pickup     = ImMultiSite::getPickupAddress( $site_id );
		if ( $site != "משימות" and $pickup != $mission->getStartAddress() ) {
//		    print "site: " . $site . "<br/>";
//		    print "add stop " . MultiSite::getPickupAddress($site_id) . "<br/>";
			add_stop_point( $pickup );
			add_line_per_station( $mission->getStartAddress(), $pickup, gui_row( array(
				$site,
				$order_id,
				"<b>איסוף </b>" . $customer,
				$pickup,
				"",
				"",
				"",
				"",
				""
			) ) );

		}
		// print "stop point: " . $stop_point . "<br/>";

		add_stop_point( $stop_point );
//		array_push( $stop_points, $stop_point );
		add_line_per_station( $mission->getStartAddress(), $stop_point, $data_lines[ $mission_id ][ $i ][1] );
	}
//	foreach ($stop_points as $p) print $p . " ";
	if ( $debug ) {
		print_time( "start path ", true );
	}
	// var_dump($mission);
	find_route_1( $mission->getStartAddress(), $stop_points, $path, true, $mission->getEndAddress() );
	if ( $debug ) {
		print_time( "end path " . $mission_id, true );
	}

//	var_dump($path);
	if ( $debug ) {
		print $path[0] . "<br/>";// . " " .get_distance(1, $path[0]) . "<br/>";
		for ( $i = 1; $i < count( $path ); $i ++ ) {
			// print $path[$i] . " " . $addresses[$path[$i]]. "<br/>";
			print $path[ $i ] . "<br/>"; // get_distance($path[$i], $path[$i-1]) . "<br/>";
		}
	}

	// print "mission_id: " . var_dump($data_lines[$mission_id]) . "<br/>";
	print "<table>";
	$data = $header;

	$data .= gui_list( "באחריות הנהג להעמיס את הרכב ולסמן את מספר האריזות והאם יש קירור." );
	$data .= gui_list( "יש לוודא שכל המשלוחים הועמסו." );
	$data .= gui_list( "בעת קבלת כסף או המחאה יש לשלוח מיידית הודעה ליעקב, עם הסכום ושם הלקוח." );
	$data .= gui_list( "במידה והלקוח לא פותח את הדלת, יש ליידע את הלקוח שהמשלוח בדלת (טלפון או הודעה)." );

	$prev           = $mission->getStartAddress();
	$total_distance = 0;
	$total_duration = 0;
	for ( $i = 0; $i < count( $path ); $i ++ ) {
		foreach ( $lines_per_station[ $path[ $i ] ] as $line ) {
			$distance       = round( get_distance( $prev, $path[ $i ] ) / 1000, 1 );
			$total_distance += $distance;
			$duration       = round( get_distance_duration( $prev, $path[ $i ] ) / 60, 0 );
			$total_duration += $duration + 5;
			$data           .= substr( $line, 0, strpos( $line, "</tr>" ) ) . gui_cell( $distance . "km" ) .
			                   gui_cell( $duration . "ד'" ) . gui_cell( $total_duration . "ד'" ) . "</td>";
		}
		$prev = $path[ $i ];
	}
	$total_distance += get_distance( $path[ count( $path ) - 1 ], $mission->getEndAddress() ) / 1000;

//	foreach ($path as $id => $stop_point){
//		print $id ."<br/>";
//	for ( $i = 0; $i < count( $data_lines[ $mission_id ] ); $i ++ ) {
//		$line = $data_line[ $i ][1];
//		$data .= trim( $line );
//	}

	print $data;

	print "</table>";

	print "סך הכל ק\"מ " . $total_distance . "<br/>";
	if ( $debug ) {
		print_time( "end handle mission " . $mission_id, true );
	}

}

function add_stop_point( $point ) {
	global $stop_points;

	if ( ! in_array( $point, $stop_points ) ) {
		array_push( $stop_points, $point );
	}
}

function add_line_per_station( $start_address, $stop_point, $line ) {
	global $lines_per_station;

	if ( ! isset( $lines_per_station[ $stop_point ] ) ) {
		$lines_per_station[ $stop_point ] = array();
	}
	if ( get_distance( $start_address, $stop_point ) ) {
		array_push( $lines_per_station[ $stop_point ], $line );
	} else {
		print "לא מזהה את הכתובת של הזמנה " . $line . "<br/>";
	}
}

function mb_ord( $c ) {
	return ord( substr( $c, 1, 1 ) );
}

                                                                                     ./
tools / delivery / create - delivery . php                                                                0000664 0001750 0001750 00000006542 13415033167 016401  0                                                                                                    ustar   agla                            agla                                                                                                                                                                                                                   <?php
error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

// include_once( "../tools_wp_login.php" );
// include_once( "../orders/orders-common.php" );
include_once( "delivery.php" );
// include_once( "../multi-site/imMulti-site.php" );


print header_text( false );

/// If id is set -> edit. get order_id from id.
/// Otherwise order_id should be set.
?>
<script type="text/javascript" src="/agla/client_tools.js"></script>
<?php

$script_file = ImMultiSite::LocalSiteTools() . "/delivery/create-delivery-script.php?i=1";

$edit = false;
if ( isset( $_GET["id"] ) ) {
	$id          = $_GET["id"];
	$script_file .= "&id=" . $id;
	if ( $id > 0 ) {
		$edit     = true;
		$order_id = get_order_id( $id );
	}
} else {
	if ( isset( $_GET["order_id"] ) ) {
		$order_id = $_GET["order_id"];
	} else {
		print "nothing to work with<br/>";
		die ( 1 );
	}
}

$script_file .= "&order_id=" . $order_id;

$contents = file_get_contents( $script_file );
print $contents;
?>
</head>
<body>
<?php

// display form for creating invoice. If id already exist, open for edit
$id = null;

if ( isset( $_GET["id"] ) ) {
	$id = $_GET["id"];
}
if ( isset( $_GET["refund"] ) ) {
	$refund = true;
}

my_log( __FILE__, "order=" . $order_id . " id = " . $id );

if ( $id > 0 ) {
	print "<form name=\"delivery\" action= \"\">";
	print gui_header( 2, "עריכת תעודת משלוח מספר  " . $id );
	print gui_header( 3, "הזמנה מספר " . get_order_id( $id ) );
	// print order_info_table(get_order_id($id));

	$d = new Delivery( $id );
	$d->PrintDeliveries( ImDocumentType::delivery, ImDocumentOperation::edit );

	//$d = new delivery( $id );
	print "</form>";

} else {
	$client_id = order_get_customer_id( $order_id );
	print "<form name=\"delivery\" action= \"\">";
	// print gui_header( 2, "יצירת תעודת משלוח להזמנה מספר " . $order_id, true );


	if ( sql_query_single_scalar( "select order_is_group(" . $order_id . ")" ) == 1 ) {
//		 print "הזמנה קבוצתית";
		$sql       = 'SELECT posts.id as id '
		             . ' FROM `wp_posts` posts'
		             . " WHERE post_status LIKE '%wc-processing%'  "
		             . " and order_user(id) = " . $client_id;
		$order_ids = sql_query_array_scalar( $sql );
		if ( count( $order_ids ) == 0 ) {
			print "אין הזמנות ללקוח הזמנות במצב טיפול<br/>";
			die ( 1 );
		}
		print " הזמנות " . comma_implode( $order_ids );
		print order_info_data( $order_ids, false, "יצירת תעודת משלוח ל" );
		$d = delivery::CreateFromOrder( $order_ids );
	} else {
		print order_info_data( $order_id, false, "יצירת תעודת משלוח ל" );
		$d = delivery::CreateFromOrder( $order_id );

	}
	// var_dump($orders);

	$d->PrintDeliveries( ImDocumentType::delivery, true );
	print "</form>";
}

?>

<button id="btn_calc" onclick="calcDelivery()">חשב תעודה</button>
<?php
$show_save_draft = false;
if ( ! $edit ) { // New
	$show_save_draft = true;
} else {
	// Still draft
	$d               = delivery::CreateFromOrder( $order_id );
	$show_save_draft = $d->isDraft();
}

if ( $show_save_draft ) {
	print gui_button( "btn_save_draft", "addDelivery(1)", "שמור טיוטא" );
}

?>
<button id="btn_add" onclick="addDelivery(0)">אשר תעודה</button>
<button id="btn_addline" onclick="addLine()">הוסף שורה</button>
<textarea id="logging" rows="2" cols="50"></textarea>

</body>
</html>                                                                                                                                                              ./tools/delivery/delivery.php                                                                       0000664 0001750 0001750 00000077652 13414640507 015153  0                                                                                                    ustar   agla                            agla                                                                                                                                                                                                                   <?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 25/02/16
 * Time: 19:29
 */
// require_once( "../r-shop_manager.php" );
if ( ! defined( 'TOOLS_DIR' ) ) {
	define( 'TOOLS_DIR', dirname( dirname( __FILE__ ) ) );
}

require_once( TOOLS_DIR . "/pricing.php" );
require_once( TOOLS_DIR . "/account/account.php" );
include_once( TOOLS_DIR . "/orders/orders-common.php" );
require_once( ROOT_DIR . '/agla/gui/inputs.php' );
include_once( TOOLS_DIR . "/multi-site/imMulti-site.php" );
require_once( TOOLS_DIR . "/mail.php" );
require_once( TOOLS_DIR . "/account/gui.php" );
require_once( TOOLS_DIR . "/delivery/delivery-common.php" );
require_once( TOOLS_DIR . "/inventory/inventory.php" );
require_once( TOOLS_DIR . "/business/business.php" );
require_once( ROOT_DIR . "/tools/catalog/Basket.php" );
$debug = false;

class delivery {
	private $ID = 0;
	private $d_OrderID = 0;
	private $order_total = 0;
	private $order_vat_total = 0;
	private $order_due_vat = 0;
	private $line_number = 0;
	private $del_price = 0;
	private $delivery_total = 0;
	private $delivery_due_vat = 0;
	private $delivery_total_vat = 0;
	private $margin_total = 0;
	private $user_id = 0;

	public function __construct( $id ) {
		$this->ID = $id;
	}

	public static function CreateDeliveryFromOrder( $order_id, $q ) {
		// $q = 1: take from order.
		// $q = 2: inventory
		$prods       = array();
		$order       = new WC_Order( $order_id );
		$order_items = $order->get_items();
		$total       = 0;
		$vat         = 0;
		$lines       = 0;
		foreach ( $order_items as $product ) {
			$lines ++;
			// $p = $product['price'];
			// push_array($prods, array($product['qty']));
			// $total += $p * $q;
			// var_dump($product);
			$prod                 = array();
			$prod['product_name'] = $product["name"];
			switch ( $q ) {
				case 1:
					$prod['quantity'] = $product["quantity"];
					break;
				case 2:
					$prod['quantity'] = inventory::GetQuantity( $product['product_id'] );
					break;
			}
			$prod['quantity_ordered'] = 0;
			$prod['vat']              = 0;
			$quantity                 = $product["quantity"];

			if ( $q != 0 ) {
				$prod['price'] = $quantity ? ( $product['total'] / $quantity ) : 0;
			}
			$prod['line_price'] = $product['total'];
			$total              += $product['total'];
			$prod['prod_id']    = $product['product_id'];

			// var_dump($prod);
			array_push( $prods, $prod );
		}

		$delivery_id = delivery::CreateDeliveryHeader( $order_id, $total, $vat, $lines, false, 0, 0, false );

		// print " מספר " . $delivery_id;

		foreach ( $prods as $prod ) {
			delivery::AddDeliveryLine( $prod['product_name'], $delivery_id, $prod['quantity'], $prod['quantity_ordered'], 0,
				$prod['vat'], $prod['price'], $prod['line_price'], $prod['prod_id'] );
		}

		// print " נוצרה <br/>";

//	$order = new WC_Order( $order_id );
//	$order->update_status( 'wc-completed' );

		global $track_email;
		$delivery = new delivery( $delivery_id );
		$delivery->send_mail( $track_email, false );

		return $delivery_id;
	}

	public static function CreateDeliveryHeader( $order_id, $total, $vat, $lines, $edit, $fee, $delivery_id = null, $_draft = false ) {
		global $conn;

		$draft = $_draft ? 1 : 0;

		if ( $edit ) {
			$sql = "UPDATE im_delivery SET vat = " . $vat . ", " .
			       " total = " . $total . ", " .
			       " dlines = " . $lines . ", " .
			       " draft = " . $draft . ", " .
			       " fee = " . $fee .
			       " WHERE order_id = " . $order_id;
			sql_query( $sql );
		} else {
			$sql = "INSERT INTO im_delivery (date, order_id, vat, total, dlines, fee, draft) "
			       . "VALUES ( CURRENT_TIMESTAMP, "
			       . $order_id . ", "
			       . $vat . ', '
			       . $total . ', '
			       . $lines . ', '
			       . $fee . ', '
			       . $draft . ')';
			sql_query( $sql );
			$delivery_id = mysqli_insert_id( $conn );
		}

		if ( ! ( $delivery_id > 0 ) ) {
			die ( "Error!" );
		}
		$client_id = order_get_customer_id( $order_id );

		if ( $edit ) {
			account_update_transaction( $total, $delivery_id, $client_id );
			business_update_transaction( $delivery_id, $total, $fee );
		} else { // New!
			$date = date( "Y-m-d" );

			account_add_transaction( $client_id, $date, $total, $delivery_id, "משלוח" );
			business_add_transaction( $client_id, $date, $total, $fee, $delivery_id, 3 );
		}
		$order = new WC_Order( $order_id );
		if ( ! $order->update_status( 'wc-awaiting-shipment' ) ) {
			printbr( "can't update order status" );
		}

		// Return the new delivery id!

		return $delivery_id;
	}

	public static function AddDeliveryLine( $product_name, $delivery_id, $quantity, $quantity_ordered, $unit_ordered, $vat, $price, $line_price, $prod_id ) {

		if ( ! ( $delivery_id > 0 ) ) {
			print "must send positive delivery id. Got " . $delivery_id . "<br/>";
			die ( 1 );
		}
		$product_name = preg_replace( '/[\'"%()]/', "", $product_name );
		// print "name: " . $product_name . "<br/>";

		$sql = "INSERT INTO im_delivery_lines (delivery_id, product_name, quantity, quantity_ordered, unit_ordered, vat, price, line_price, prod_id) VALUES ("
		       . $delivery_id . ", "
		       . "'" . urldecode( $product_name ) . "', "
		       . $quantity . ", "
		       . $quantity_ordered . ", "
		       . $unit_ordered . ", "
		       . $vat . ", "
		       . $price . ', '
		       . round( $line_price, 2 ) . ', '
		       . $prod_id . ' )';

// print $sql . "<br/>";

		my_log( $sql, "db-add-delivery-line.php" );

		sql_query( $sql );
	}

	function send_mail( $more_email = null, $edit = false ) {
		global $business_name;
		global $bank_info;
		global $support_email;
		global $mail_sender;

		$order_id = get_order_id( $this->ID );
		if ( ! ( $order_id > 0 ) ) {
			die ( "can't get order id from delivery " . $this->ID );
		}
		// print "oid= " . $order_id . "<br/>";
		$client_id = order_get_customer_id( $this->OrderId() );
		if ( ! ( $client_id > 0 ) ) {
			die ( "can't get client id from order " . $this->OrderId() );
		}

		my_log( __FILE__, "client_id = " . $client_id );

		$sql = "SELECT dlines FROM im_delivery WHERE id = " . $this->ID;

		$dlines = sql_query_single_scalar( $sql );

		my_log( __FILE__, "dlines = " . $dlines );

		$del_user = order_info( $order_id, '_billing_first_name' );
		$message  = header_text( true, true, true );

		$message .= "<body>";
		$message .= "שלום " . $del_user . "!
<br><br>
המשלוח שלך ארוז ויוצא לדרך!";

		$message .= "<Br> להלן פרטי המשלוח";

		$message .= $this->delivery_text( ImDocumentType::delivery, ImDocumentOperation::show );
		// file_get_contents("http://store.im-haadama.co.il/tools/delivery/get-delivery.php?id=" . $del_id . "&send=1");

		$message .= "<br> היתרה המעודכנת במערכת " . client_balance( $client_id );

		$message .= "<br /> לפרטים אודות מצב החשבון והמשלוח האחרון הכנס " .
		            gui_hyperlink( "מצב חשבון", get_site_url() . '/balance' ) .
		            "
 <br/>
 העברות בנקאיות מתעדכנות בחשבונכם אצלנו עד עשרה ימים לאחר התשלום.
<li>
למשלמים בהעברה בנקאית - פרטי החשבון: " . $bank_info . ". 
</li>
<li>המחאה לפקודת " . $business_name . ".
</li>
<li>
במידה ושילמתם כבר, המכתב נשלח לצורך פירוט עלות המשלוח בלבד ואין צורך לשלם שוב.
</li>

נשמח מאוד לשמוע מה דעתכם! <br/>
 לשאלות בנוגע למשלוח מוזמנים ליצור איתנו קשר במייל " . $support_email . "
</body>
</html>";

		$user_info = get_userdata( $client_id );
		my_log( $user_info->user_email );
		$to = $user_info->user_email;
		// print "To: " . $to . "<br/>";
		if ( $more_email ) {
			$to = $to . ", " . $more_email;
		}
		// print "From: " . $support_email . "<br/>";
		// print "To: " . $to . "<br/>";
		// print "Message:<br/>";
		// print $message . "<br/>";
		$subject = "משלוח מספר" . $this->ID . " בוצע";
		if ( $edit ) {
			$subject = "משלוח מספר " . $this->ID . " - תיקון";
		}
		send_mail( $subject, $to, $message );
		// print "mail sent to " . $to . "<br/>";
	}

	public function OrderId() {
		if ( ! ( $this->d_OrderID > 0 ) ) {
			$sql = "SELECT order_id FROM im_delivery WHERE id = " . $this->ID;

			$this->d_OrderID = sql_query_single_scalar( $sql );
		}

		return $this->d_OrderID;
	}

	function delivery_text( $document_type, $operation = ImDocumentOperation::show, $margin = false ) {
		global $delivery_fields_names;
		global $header_fields;
		global $debug;
		if ( false ) {
			print "Document type " . $document_type . "<br/>";
			print "operation: " . $operation . "<br/>";
		}
		global $global_vat;

		$expand_basket = false;

		if ( $operation == ImDocumentOperation::create or $operation == ImDocumentOperation::collect ) {
			$expand_basket = true;
		}

		// All fields:
		$show_fields = array();
		for ( $i = 0; $i < DeliveryFields::max_fields; $i ++ ) {
			$show_fields[ $i ] = false;
		}

		$show_fields[ DeliveryFields::product_name ]  = true;
		$show_fields[ DeliveryFields::order_q ]       = true;
		$show_fields[ DeliveryFields::order_q_units ] = true;
		$show_fields[ DeliveryFields::price ]         = true;

		$empty_array = array();
		for ( $i = 0; $i < DeliveryFields::max_fields; $i ++ ) {
			$empty_array[ $i ] = "";
		}

		switch ( $document_type ) {
			case ImDocumentType::order:
				$header_fields[ DeliveryFields::delivery_line ] = "סה\"כ למשלוח";
				if ( $operation == ImDocumentOperation::edit ) {
					$header_fields[ DeliveryFields::line_select ] = gui_checkbox( "chk", "line_chk", false );
					$show_fields[ DeliveryFields::line_select ]   = true;
				}
				$show_fields[ DeliveryFields::order_line ] = true;
				if ( $margin ) {
					$show_fields[ DeliveryFields::buy_price ]   = true;
					$show_fields[ DeliveryFields::line_margin ] = true;
				}
				break;
			case ImDocumentType::delivery:
				$show_fields[ DeliveryFields::delivery_q ] = true;
				if ( $operation != ImDocumentOperation::collect ) {
					$show_fields[ DeliveryFields::has_vat ]       = true;
					$show_fields[ DeliveryFields::line_vat ]      = true;
					$show_fields[ DeliveryFields::delivery_line ] = true;
				}
				if ( $operation == ImDocumentOperation::create or $operation == ImDocumentOperation::collect ) {
					$show_fields[ DeliveryFields::order_line ] = true;
				}
				if ( $margin ) {
					$show_fields[ DeliveryFields::buy_price ]   = true;
					$show_fields[ DeliveryFields::line_margin ] = true;
				}
				break;
			case ImDocumentType::refund:
				$refund                                     = true;
				$show_fields[ DeliveryFields::refund_q ]    = true;
				$show_fields[ DeliveryFields::refund_line ] = true;
				break;
			default:
				print "Document type " . $document_type . " not handled " . __FILE__ . " " . __LINE__ . "<br/>";
				die( 1 );
		}

		$data = "";

//		$client_id   = $this->GetCustomerID();
//		print "cid=" . $client_id . "<br/>";
//		$client_type = customer_type( $client_id );
//		 print $client_type . "XX<br/>";

//		if ( $client_type > 0 ) {
//			$data .= "תעריף " . sql_query_single_scalar( "SELECT type FROM im_client_types WHERE id = " . $client_type );
//		}

		$delivery_loaded = false;
		$volume_line     = false;

		$data .= "<style> " .
		         "table.prods { border-collapse: collapse; } " .
		         " table.prods, td.prods, th.prods { border: 1px solid black; } " .
		         " </style>";

		// Orig: $data .= "<table class=\"prods\" id=\"del_table\" border=\"1\">";
		$data .= "<table style='border-collapse: collapse'  id=\"del_table\">";

		// Print header
		$sum   = null;
		$style = 'style="border: 2px solid #dddddd; text-align: right; padding: 8px;"';
		$data  .= gui_row( $header_fields, "header", $show_fields, $sum, null, $style );

		if ( $this->ID > 0 ) { // load delivery
			$delivery_loaded = true;
			$sql             = 'select id, product_name, round(quantity, 1), quantity_ordered, vat, price, line_price, prod_id ' .
			                   'from im_delivery_lines ' .
			                   'where delivery_id=' . $this->ID . " order by 1";

			$result = sql_query( $sql );

			if ( ! $result ) {
				print $sql;
				die ( "select error" );
			}

			while ( $row = mysqli_fetch_assoc( $result ) ) {
				if ( $row["product_name"] == "הנחת כמות" ) {
					$volume_line = true;
				}
				$data .= $this->delivery_line( $show_fields, ImDocumentType::delivery, $row["id"], 0, $operation, $margin, $style );
			}
		} else {
			// For group orders - first we get the needed products and then accomulate the quantities.
			$sql = 'select distinct woim.meta_value,  order_line_get_variation(woi.order_item_id) '
			       . ' from wp_woocommerce_order_items woi join wp_woocommerce_order_itemmeta woim'
			       . ' where ' . $this->OrderQuery()
			       . ' and woi.order_item_id = woim.order_item_id and woim.`meta_key` = \'_product_id\'';

			$prods_result = sql_query( $sql );
			while ( $row = sql_fetch_row( $prods_result ) ) {
				$prod_id = $row[0];
				$var_id  = $row[1];

				$items_sql      = 'select woim.order_item_id'
				                  . ' from wp_woocommerce_order_items woi join wp_woocommerce_order_itemmeta woim'
				                  . ' where ' . $this->OrderQuery()
				                  . ' and woi.order_item_id = woim.order_item_id and woim.`meta_key` = \'_product_id\''
				                  . ' and woim.meta_value = ' . $prod_id
				                  . ' and order_line_get_variation(woi.order_item_id) = ' . $var_id
				                  . ' order by 1';
				$order_item_ids = sql_query_array_scalar( $items_sql );

				// $data .= $this->delivery_line($show_fields, $prod_id, $quantity_ordered, "", $quantity_ordered, $price, $has_vat, $prod_id, $refund, $unit );
				$data .= $this->delivery_line( $show_fields, $document_type, $order_item_ids, 0, $operation, $margin, $style, $var_id );
				// print "ex " . $expand_basket . " is " . is_basket($prod_id) . "<br/>";

				if ( $expand_basket && is_basket( $prod_id ) ) {
					$quantity_ordered = get_order_itemmeta( $order_item_ids, '_qty' ); //, $client_type, $operation, $data );

					$this->expand_basket( $prod_id, $quantity_ordered, 0, $show_fields, $document_type,
						$order_item_ids, 0, $operation, $data );
				}
			}

			// Get and display order delivery price
			$sql2 = 'SELECT meta_value FROM `wp_woocommerce_order_itemmeta` WHERE order_item_id IN ( '
			        . 'SELECT order_item_id FROM wp_woocommerce_order_items WHERE ' . $this->OrderQuery()
			        . ' AND order_item_type = \'shipping\' )  AND meta_key = \'cost\'; ';

			$del_price = sql_query_single_scalar( $sql2 );
			if ( ! is_numeric( $del_price ) ) {
				$del_price = 0;
			}
		}

		if ( ! $delivery_loaded ) {
			$this->order_total   += $del_price;
			$this->order_due_vat += $del_price;

			$del_vat = round( $del_price / ( 100 + $global_vat ) * $global_vat, 2 );

			$delivery_line                                  = $empty_array;
			$delivery_line[ DeliveryFields::product_name ]  = "דמי משלוח";
			$delivery_line[ DeliveryFields::delivery_q ]    = 1;
			$delivery_line[ DeliveryFields::price ]         = $operation ? gui_input( "delivery", $del_price > 0 ? $del_price : "", "" ) : $del_price;
			$delivery_line[ DeliveryFields::has_vat ]       = gui_checkbox( "hvt_del", "vat", true );
			$delivery_line[ DeliveryFields::line_vat ]      = $del_vat;
			$delivery_line[ DeliveryFields::delivery_line ] = $del_price;
			$delivery_line[ DeliveryFields::order_line ]    = $del_price;

			$sums = null;
			global $delivery_fields_names;
			$data                  .= gui_row( $delivery_line, "del", $show_fields, $sums, $delivery_fields_names );
			$this->order_vat_total += $del_vat;
			// Spare line for volume discount
		}

		if ( $operation != ImDocumentOperation::collect ) {

			if ( ! $volume_line ) {
				$delivery_line = $empty_array;
				$data          .= gui_row( $delivery_line, "dis", $show_fields, $sums, $delivery_fields_names );
			}
			// Summary
			// Due VAT
			$summary_line                                  = $empty_array;
			$summary_line[ DeliveryFields::product_name ]  = 'סה"כ חייב במע"מ';
			$summary_line[ DeliveryFields::delivery_line ] = $this->delivery_due_vat;
			$summary_line[ DeliveryFields::order_line ]    = $this->order_due_vat;
			$data                                          .= gui_row( $summary_line, "due", $show_fields, $sum, $delivery_fields_names, $style );

			// Total VAT
			$summary_line                                  = $empty_array;
			$summary_line[ DeliveryFields::product_name ]  = 'מע"מ 17%';
			$summary_line[ DeliveryFields::delivery_line ] = $this->delivery_total_vat;
			$summary_line[ DeliveryFields::order_line ]    = $this->order_vat_total;
			$data                                          .= gui_row( $summary_line, "vat", $show_fields, $sum, $delivery_fields_names, $style );

			// Total
			$summary_line                                  = $empty_array;
			$summary_line[ DeliveryFields::product_name ]  = "סה\"כ לתשלום";
			$summary_line[ DeliveryFields::delivery_line ] = $this->delivery_total;
			$summary_line[ DeliveryFields::order_line ]    = $this->order_total;
			$summary_line[ DeliveryFields::line_margin ]   = $this->margin_total;
			$data                                          .= gui_row( $summary_line, "tot", $show_fields, $sum, $delivery_fields_names, $style );
		}

		$data = str_replace( "\r", "", $data );

		$data .= "</table>";

		$data .= "מספר שורות  " . $this->line_number . "<br/>";

		return "$data";
	}

	public function delivery_line( $show_fields, $document_type, $line_ids, $client_type, $operation, $margin = false, $style = null, $var_id = 0 ) {
		global $delivery_fields_names;

		global $global_vat;

		$line_color = null;

		$line = array();
		for ( $i = 0; $i <= DeliveryFields::max_fields; $i ++ ) {
			$line[ $i ] = "";
		}

		if ( is_array( $line_ids ) ) {
			$line_id = $line_ids[0];
		} else {
			$line_id = $line_ids;
		}

		$line[ DeliveryFields::line_select ] = gui_checkbox( "chk" . $line_id, "line_chk", false );

		$unit_ordered       = null;
		$quantity_delivered = 0;
		//////////////////////////////////////////
		// Fetch fields from the order/delivery //
		//////////////////////////////////////////
		$unit_q          = "";
		$load_from_order = false;
		switch ( $document_type ) {
			case ImDocumentType::order:
				$load_from_order = true;
				break;

			case ImDocumentType::delivery:
				if ( $operation == ImDocumentOperation::create or $operation == ImDocumentOperation::collect ) {
					$load_from_order = true;
				} else {
					$load_from_order = false;
				}
				// TODO: check price
				break;
		}
		$has_vat = null;

		$P = null;

		if ( $load_from_order ) {
			// print "lid=". $line_id . "<br/>";
			$sql                                = "SELECT order_item_name FROM wp_woocommerce_order_items WHERE order_item_id = " . $line_id;
			$prod_name                          = sql_query_single_scalar( $sql );
			$quantity_ordered                   = get_order_itemmeta( $line_ids, '_qty' );
			$unit_ordered                       = get_order_itemmeta( $line_id, 'unit' );
			$order_line_total                   = round( get_order_itemmeta( $line_ids, '_line_total' ), 1 );
			$this->order_total                  += $order_line_total;
			$line[ DeliveryFields::order_line ] = $order_line_total;
			$prod_id                            = get_order_itemmeta( $line_id, '_product_id' );
			$P                                  = new Product( $prod_id );
			// $line_price       = get_order_itemmeta( $line_id, '_line_total' );

			// Todo: handle prices
			switch ( $client_type ) {
				case 0:
					if ( $quantity_ordered ) {
						$price = round( $order_line_total / $quantity_ordered, 1 );
					} else {
						$price = get_price( $prod_id );
					}
					break;
				case 1:
					$price = siton_price( $prod_id );
					break;
				case 2:
					$price = get_buy_price( $prod_id );
					break;
				default:
					$price = round( 1.3 * get_buy_price( $prod_id ), 1 );
			}

			if ( $unit_ordered ) {
				$quantity_ordered = "";
				$unit_array       = explode( ",", $unit_ordered );
				$unit_q           = $unit_array[1];
				// print "unit: " ; var_dump($unit) ; print "<br/>";
			}
		} else {
			$sql = "SELECT prod_id, product_name, quantity_ordered, unit_ordered, round(quantity, 1), price, line_price, vat FROM im_delivery_lines WHERE id = " . $line_id;

			$row = sql_query_single( $sql );
			if ( ! $row ) {
				sql_error( $sql );
				die ( 2 );
			}

			$prod_id          = $row[0];
			$P                = new Product( $prod_id );
			$prod_name        = $row[1];
			$quantity_ordered = $row[2];
			$unit_q           = $row[3];
//			if ( $unit_q > 0 and $quantity_ordered == 0 ) {
//				$quantity_ordered = "";
//			}
			$quantity_delivered = $row[4];
			$price              = $row[5];
			$delivery_line      = $row[6];
			$has_vat            = $row[7];

			if ( $quantity_delivered < ( 0.8 * $quantity_ordered ) or ( $unit_q > 0 and $quantity_delivered == 0 ) ) {
				$line_color = "yellow";
			}
		}

		// in Order price is total/q. in delivery get from db.
		// $price            = $this->item_price( $client_type, $prod_id, $order_line_total, $quantity_ordered );

		// Display item name. product_name
		$line[ DeliveryFields::product_name ] = $prod_name;
		$line[ DeliveryFields::product_id ]   = $prod_id;
		// $value .= "<td id='" . $prod_id . "'>" . $prod_name . '</td>'; // 1- name

		// q_quantity_ordered
		$line[ DeliveryFields::order_q ]       = $quantity_ordered;
		$line[ DeliveryFields::order_q_units ] = $unit_q;
		// $value .= "<td>" . $quantity_ordered . "</td>";                             // 2- ordered

		if ( is_null( $has_vat ) ) {
			if ( $P->getVatPercent() == 0 ) {
				$has_vat = false;
			} else {
				$has_vat = true;
			}
		}

		// price
		if ( $operation == ImDocumentOperation::create and $document_type == ImDocumentType::delivery ) {
			$line[ DeliveryFields::price ] = gui_input( "", $price );
		} else {
			$line[ DeliveryFields::price ] = $price;
		}

		// has_vat
		$line[ DeliveryFields::has_vat ] = gui_checkbox( "hvt_" . $prod_id, "has_vat", $has_vat > 0 ); // 6 - has vat

		// q_supply
		switch ( $document_type ) {
			case ImDocumentType::order:
				// TODO: get supplied q
				// $line[DeliveryFields::delivery_q] = $quantity_delivered;
				// $value .= gui_cell( $quantity_delivered, "", $show_fields[ DeliveryFields::delivery_q ] ); // 4-supplied
				// $value .= gui_cell( "הוזמן", $debug );
				break;

			case ImDocumentType::delivery:
				// $line[DeliveryFields::order_line] = $order_line_total;
				switch ( $operation ) {
					case ImDocumentOperation::edit:
						$line[ DeliveryFields::delivery_q ] = gui_input( "quantity" . $this->line_number,
							( $quantity_delivered > 0 ) ? $quantity_delivered : "",
							array( 'onkeypress="moveNextRow(' . $this->line_number . ')"' ) );
						break;
					case ImDocumentOperation::collect:
						break;
					case ImDocumentOperation::show:
						$line[ DeliveryFields::delivery_q ] = $quantity_delivered;
						break;
					default:
				}
				if ( isset( $delivery_line ) ) {
					$line[ DeliveryFields::delivery_line ] = $delivery_line;
					$this->delivery_total                  += $delivery_line;
				}
				if ( $has_vat and isset( $delivery_line ) ) {
					$line[ DeliveryFields::line_vat ] = round( $delivery_line / ( 100 + $global_vat ) * $global_vat, 2 );
					// round($delivery_line / (100 + $global_vat));

					$this->delivery_due_vat   += $delivery_line;
					$this->delivery_total_vat += $line[ DeliveryFields::line_vat ];
				} else {
					$line[ DeliveryFields::line_vat ] = "";
				}

				break;
			case ImDocumentType::refund;
				$line[ DeliveryFields::delivery_q ] = $quantity_delivered;
				// $value .= gui_cell( $quantity_delivered );                                              // 4- Supplied
				break;
		}

		if ( ! is_numeric( $price ) ) {
			$price = 0;
		}

		// terms
		// Check if this product eligible for quantity discount.
		$terms      = get_the_terms( $prod_id, 'product_cat' );
		$terms_cell = "";
		if ( $terms ) {
			foreach ( $terms as $term ) {
				$terms_cell .= $term->term_id . ",";
			}
			$terms_cell = rtrim( $terms_cell, "," );
		}
		$line[ DeliveryFields::term ] = $terms_cell;
		//$value .= gui_cell( $terms_cell, "terms" . $this->line_number, false );                    // 9 - terms

		// Handle refund
		if ( $document_type == ImDocumentType::refund ) {
			$line[ DeliveryFields::refund_q ] = gui_cell( gui_input( "refund_" . $this->line_number, 0 ) );             // 10 - refund q
			// $value .= gui_cell( "0" );                                                              // 11 - refund amount
		}

		if ( $margin ) {
			$q                                   = ( $operation == ImDocumentType::delivery ) ? $quantity_delivered : $quantity_ordered;
			$line[ DeliveryFields::buy_price ]   = get_buy_price( $prod_id );
			$line[ DeliveryFields::line_margin ] = ( $price - get_buy_price( $prod_id ) ) * $q;
			$this->margin_total                  += $line[ DeliveryFields::line_margin ];
		}

		$this->line_number = $this->line_number + 1;
		$sums              = null;
		if ( $line_color ) {
			$style .= 'bgcolor="' . $line_color . '"';
		}

		return gui_row( $line, $this->line_number, $show_fields, $sums, $delivery_fields_names, $style );
	}

	function OrderQuery() {
		if ( is_array( $this->d_OrderID ) ) {
			return "order_id in (" . comma_implode( $this->d_OrderID ) . ")";
		} else {
			return "order_id = " . $this->d_OrderID;
		}
	}

	function expand_basket( $basket_id, $quantity_ordered, $level, $show_fields, $document_type, $line_id, $client_type, $edit, &$data ) {
		global $conn, $delivery_fields_names;
		$sql2 = 'SELECT DISTINCT product_id, quantity FROM im_baskets WHERE basket_id = ' . $basket_id;

		$result2 = mysqli_query( $conn, $sql2 );
		while ( $row2 = mysqli_fetch_assoc( $result2 ) ) {
			$prod_id = $row2["product_id"];
			// print $prod_id . "<br/>";
			$P        = new Product( $prod_id );
			$quantity = $row2["quantity"];
			if ( is_basket( $prod_id ) ) {
//				$this->expand_basket( $prod_id, $client_type, $quantity_ordered * $quantity, $data, $level + 1 );
				$this->expand_basket( $prod_id, $quantity_ordered * $quantity, $level + 1, $show_fields, $document_type, $line_id, $client_type, $edit, $data );

			} else {
				// $price = $this->item_price( $client_type, $prod_id, 0, 0 );
				//                        print "prod_id = " . $prod_id . "price = " . $price . "<br/>";
				//				$data         .= $this->delivery_line( $prod_id, $quantity_ordered * $quantity, "", 0,
//					$price, round( $price * get_vat_percent( $prod_id ), 2 ), $prod_id, false, "" );

				$line = array();
				for ( $i = 0; $i <= DeliveryFields::max_fields; $i ++ ) {
					$line[ $i ] = "";
				}

				$line[ DeliveryFields::product_name ] = "===> " . get_product_name( $prod_id );
				$line[ DeliveryFields::price ]        = get_price( $prod_id, $client_type );
				$has_vat                              = true;

//				if ($P-> == 149) var_dump($P);
				if ( ! $P->getVatPercent() ) { // get_vat_percent( $prod_id ) == 0 ) {
//					print "has vat false<br/>";
					$has_vat = false;
				}
				$line[ DeliveryFields::product_id ] = $prod_id;
				$line[ DeliveryFields::has_vat ]    = gui_checkbox( "hvt_" . $prod_id, "has_vat", $has_vat > 0 );
				$line[ DeliveryFields::order_q ]    = $quantity_ordered;
				$line[ DeliveryFields::delivery_q ] = gui_input( "quantity" . $this->line_number, "",
					array( 'onkeypress="moveNextRow(' . $this->line_number . ')"' ) );
				// $line[ DeliveryFields::line_vat]

				$this->line_number = $this->line_number + 1;
				$data              .= gui_row( $line, $this->line_number, $show_fields, $sums, $delivery_fields_names );

				// $data .=- $this->delivery_line( $show_fields, $document_type, $line_id, $client_type, $edit );
			}
		}
		if ( $level == 0 ) {
			$line = array();
			for ( $i = 0; $i <= DeliveryFields::max_fields; $i ++ ) {
				$line[ $i ] = "";
			}
			$line[ DeliveryFields::product_name ] = gui_label( "ba", "הנחת סל" );
			// $line[DeliveryFields::has_vat] = gui_checkbox("", )
			$sums              = null;
			$data              .= gui_row( $line, "bsk" . $this->line_number, $show_fields, $sums, $delivery_fields_names );
			$this->line_number = $this->line_number + 1;


//			$data .= "<tr><td id='bsk_dis". $this->line_number .
//			         "'>הנחת סל</td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>";
		}
	}

	public static function GuiCreateNewNoOrder() {
		$data = gui_table( array(
			array( "לקוח:", gui_select_client() ),
			array( "תאריך", gui_input_date( "delivery_date", "" ) ),
			array( gui_button( "btn_add_delivery", "", "הוסף תעודת משלוח" ) )
		) );

		return $data;
	}

	public static function CreateFromOrder( $order_id ) {

		$id = get_delivery_id( $order_id );

		$instance = new self( $id );

		$instance->SetOrderId( $order_id );

		return $instance;
	}

	private function SetOrderID( $order_id ) {
		$this->d_OrderID = $order_id;
	}

	/**
	 * @return int
	 */
	public function getID() {
		return $this->ID;
	}

	public function isDraft() {
		if ( $this->ID ) {
			return sql_query_single_scalar( "SELECT draft FROM im_delivery WHERE ID = " . $this->ID );
		} else {
			die ( __METHOD__ . " no ID" );
		}
	}

	public function DeliveryDate() {
		global $conn;

		$sql = "SELECT date FROM im_delivery WHERE id = " . $this->ID;

		$result = $conn->query( $sql );

		if ( ! $result ) {
			print $sql;
			die ( "select error" );
		}

		$row = mysqli_fetch_assoc( $result );

		return $row["date"];
	}

	public function Delete() {
		// change the order back to processing
		$order_id = $this->OrderId();
		if ( ! $order_id ) {
			die ( "no order id: Delete" );
		}

		$sql = "UPDATE wp_posts SET post_status = 'wc-processing' WHERE id = " . $order_id;

		sql_query( $sql );

		// Remove from client account
		$sql = 'DELETE FROM im_client_accounts WHERE transaction_ref = ' . $this->ID;

		sql_query( $sql );

		// Remove the header
		$sql = 'DELETE FROM im_delivery WHERE id = ' . $this->ID;

		sql_query( $sql );

		// Remove the lines
		$sql = 'DELETE FROM im_delivery_lines WHERE delivery_id = ' . $this->ID;

		sql_query( $sql );

	}

	public function DeleteLines() {
		// TODO:
		$sql = 'DELETE FROM im_delivery_lines WHERE delivery_id = ' . $this->ID;

		sql_query( $sql );
	}

	public function Price() {
		// $sql = 'SELECT round(transaction_amount, 2) FROM im_client_accounts WHERE transaction_ref = ' . $this->ID;
		$sql = 'SELECT round(total, 2) FROM im_delivery WHERE id = ' . $this->ID;

		// my_log($sql);

		return sql_query_single_scalar( $sql );
	}

	public function getPrintDeliveryOption() {
		$user_id = $this->getUserId();

		$option = get_user_meta( $user_id, "print_delivery_note" );

		if ( $option == null ) {
			$option = 'MP';
		}

		return $option;
	}


//	public function delivery_line_group($show_fields, $document_type, $line_ids, $client_type, $operation, $margin = false, $style = null)
//	{
//
//	}

	// Used for:
	// Creating new delivery.
	// - Prices are taken from order for regular clients, discount for siton and buy prices for owner
	// display delivery
	// - Prices are taken from the database - delivery

	// public function delivery_line($show_fields, $prod_id, $quantity_ordered, $unit_ordered, $quantity_delivered, $price, $has_vat, $document_type, $edit)
	// Delivery or Order line.
	// If Document is delivery, line_id is delivery line id.
	// If Document is order, line_id is order line id.

	/**
	 * @return int
	 */
	public function getUserId() {
		if ( ! $this->user_id ) {
			$this->user_id = sql_query_single_scalar( "SELECT client_from_delivery(id) FROM im_delivery WHERE id = " . $this->user_id );
		}

		return $this->user_id;
	}

	function PrintDeliveries( $document_type, $operation, $margin = false ) {
		print $this->delivery_text( $document_type, $operation, $margin );
	}

	// function expand_basket( $basket_id, $client_type, $quantity_ordered, &$data, $level ) {
	// Called when creating a delivery from an order.
	// After the basket line is shown, we print here the basket lines and basket discount line.

	public function DeliveryFee() {
		$sql = 'SELECT fee FROM im_delivery WHERE id = ' . $this->ID;

		// print $sql;
		// my_log($sql);

		return sql_query_single_scalar( $sql );
	}

	private function GetCustomerID() {
		if ( is_array( $this->d_OrderID ) ) {
			return order_get_customer_id( $this->d_OrderID[0] );
		}

		return order_get_customer_id( $this->d_OrderID );
	}
}

                                                                                      ./
tools / delivery / missing . php                                                                        0000664 0001750 0001750 00000002213 13415044227 014754  0                                                                                                    ustar   agla                            agla                                                                                                                                                                                                                   <?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 09/10/18
 * Time: 19:32
 */


require_once( "../im_tools.php" );
require_once( "../orders/Order.php" );
require_once( ROOT_DIR . '/agla/gui/inputs.php' );

$none = true;

print header_text( true, true, true );

$sql = 'SELECT posts.id, order_is_group(posts.id), order_user(posts.id) '
       . ' FROM `wp_posts` posts'
       . ' WHERE `post_status` in (\'wc-awaiting-shipment\')';

$sql .= ' order by 1';

$orders = sql_query( $sql );

while ( $order = sql_fetch_row( $orders ) ) {
	$order_id   = $order[0];
	$is_group   = $order[1];
	$order_user = $order[2];

	$order       = new Order( $order_id );
	$delivery_id = $order->getDeliveryId();
	$m           = $order->Missing();
	if ( strlen( $m ) ) {
		$link = gui_hyperlink( $order_id, "../orders/get-order.php?order_id=" . $order_id );
		if ( $delivery_id ) {
			$link = gui_hyperlink( "ת.מ " . $delivery_id, "create-delivery.php?id=" . $delivery_id );
		}

		print gui_header( 1, $order->CustomerName() . " " . $link );
		print $m;
		$none = false;
	}
}

if ( $none ) {
	print "הידד, אין חוסרים בהזמנות שממתינות למשלוח";
}

                                                                                                                                                                                                                                                                                                                                                                                     ./tools / multi - site / sync - zones . php                                                                   0000664 0001750 0001750 00000010143 13414640420 015661  0                                                                                                    ustar   agla                            agla                                                                                                                                                                                                                   <?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 02/08/17
 * Time: 20:58
 */

require_once( '../r-multisite.php' );
require_once( ROOT_DIR . '/agla/gui/sql_table.php' );
require_once( "../multi-site/imMulti-site.php" );

$operation = $_GET["operation"];

// print header_text(false);
switch ( $operation ) {
	case "get":
		print header_text( false );
		print table_content( "SELECT zone_id, zone_name, zone_order, min_order, zone_delivery_order, delivery_days FROM wp_woocommerce_shipping_zones" );
		break;

	case "update":
		if ( ! isset( $_GET["source"] ) ) {
			print "must define source <br/>";
			die ( 1 );
		}
		$source = $_GET["source"];
		$html   = ImMultiSite::sExecute( "multi-site/sync-zones.php?operation=get", $source );

		print $html;
		update_zone_table( $html );
		break;

	case "get_shipping":
		print header_text( false );
		print table_content( "SELECT zone_id, instance_id, method_id, method_order, is_enabled FROM wp_woocommerce_shipping_zone_methods" );
		break;

}

function update_zone_table( $table ) {
	print header_text( false, true, false );
	global $conn;

	// var_dump($conn);
	$dom = im_str_get_html( $table );
	$row = $dom->find( 'tr' );
	print $row->plaintext;

	$headers = array();

	$fields = array();
	$first  = true;
	$keys   = array();

	$row_key = - 1;
	foreach ( $dom->find( 'tr' ) as $row ) {
		// First line - headers.
		if ( $first ) {
			foreach ( $row->children() as $key ) {
				array_push( $headers, $key->plaintext );
			}
			// unset($headers[0]);
			$field_list = comma_implode( $headers );
			print "headers: " . $field_list . "<br/>";
			$first = false;
			continue;
		}
		$first_key     = true;
		$update_fields = "";
		$i             = 0;
		$insert        = false;

		foreach ( $row->children() as $value ) {
			$fields[ $i ] = $value->plaintext;

			// First key: id
			if ( $first_key ) {
				$row_key          = intval( $fields[0] );
				$keys[ $row_key ] = 1;
				$insert_values    = "";

				print "<br/>handle " . $row_key . " ";

				$sql = "SELECT COUNT(*) FROM wp_woocommerce_shipping_zones WHERE zone_id=" . $row_key;

				if ( sql_query_single_scalar( $sql ) < 1 ) {
					print " insert ";
					$insert = true;
				} else {
					print " update ";
				}
				$first_key = false;
				$i ++;
				continue;
			}
			if ( $insert ) {
				$insert_values .= quote_text( $fields[ $i ] ) . ", ";
			} else { // Update
				$update_fields .= $headers[ $i ] . "=" . quote_text( $fields[ $i ] ) . ", ";
			}
			$i ++;
		}

		if ( $insert ) {
			$sql = "INSERT INTO wp_woocommerce_shipping_zones (" . $field_list . ") VALUES ( " . $row_key . ", " . rtrim( $insert_values, ", " ) . ")";
			// print $sql . "<br/>";
			sql_query( $sql );
		} else {
			$sql = "UPDATE wp_woocommerce_shipping_zones SET " . rtrim( $update_fields, ", " ) .
			       " WHERE zone_id = " . $row_key;
			// print $sql . "<br/>";
			sql_query( $sql );
		}
	}
	// Delete not recieved keys.
	$min        = sql_query_single_scalar( "SELECT min(zone_id) FROM wp_woocommerce_shipping_zones" );
	$max        = sql_query_single_scalar( "SELECT max(zone_id) FROM wp_woocommerce_shipping_zones" );
	$ids        = sql_query_array_scalar( "SELECT zone_id FROM wp_woocommerce_shipping_zones" );
	$for_delete = "";

	for ( $i = $min; $i <= $max; $i ++ ) {
		if ( ! $keys[ $i ] and in_array( $i, $ids ) ) {
			$for_delete .= $i . ", ";
		}
	}
	if ( strlen( $for_delete ) ) {
		$sql = "DELETE FROM wp_woocommerce_shipping_zones WHERE zone_id IN (" . rtrim( $for_delete, ", " ) . ")";
		print $sql;

		sql_query( $sql );
	}
}

function get_decorated_diff( $old, $new ) {
	$from_start = strspn( $old ^ $new, "\0" );
	$from_end   = strspn( strrev( $old ) ^ strrev( $new ), "\0" );

	$old_end = strlen( $old ) - $from_end;
	$new_end = strlen( $new ) - $from_end;

	$start    = substr( $new, 0, $from_start );
	$end      = substr( $new, $new_end );
	$new_diff = substr( $new, $from_start, $new_end - $from_start );
	$old_diff = substr( $old, $from_start, $old_end - $from_start );

	$new = "$start<ins style='background-color:#ccffcc'>$new_diff</ins>$end";
	$old = "$start<del style='background-color:#ffcccc'>$old_diff</del>$end";

	return array( "old" => $old, "new" => $new );
}                                                                                                                                                                                                                                                                                                                                                                                                                             ./tools / multi - site / sync - from - master . php                                                             0000664 0001750 0001750 00000000515 13414633315 016766  0                                                                                                    ustar   agla                            agla                                                                                                                                                                                                                   <?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 20/04/18
 * Time: 15:55
 */

error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

if ( ! defined( 'TOOLS_DIR' ) ) {
	define( 'TOOLS_DIR', dirname( dirname( __FILE__ ) ) );
}
require_once( TOOLS_DIR . "/im_tools.php" );

require_once( 'sync.php' );

sync_from_master();
                                                                                                                                                                                   ./tools / multi - site / sync - data . php                                                                    0000664 0001750 0001750 00000004063 13414640420 015440  0                                                                                                    ustar   agla                            agla                                                                                                                                                                                                                   <?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 02/08/17
 * Time: 20:58
 */

require_once( '../r-multisite.php' );
require_once( ROOT_DIR . '/agla/gui/sql_table.php' );
require_once( "../multi-site/imMulti-site.php" );

$operation = $_GET["operation"];
if ( ! isset( $_GET["table"] ) ) {
	print "bad usage";
	die( 1 );
}
$table = $_GET["table"];
switch ( $table ) {
	case "wp_woocommerce_shipping_zones":
		$key = "zone_id";
		break;
	case "im_missions":
		$key = "id";
		break;
	case "wp_woocommerce_shipping_zone_locations":
//		print "zone_locations<br/>";
		$key = "location_id";
		break;
	case "wp_woocommerce_shipping_zone_methods":
		$key = "instance_id";
		break;
	case "im_baskets":
		$key = "id";
		break;
	case 'im_mission_methods':
		$key = "id";
		break;
	case 'wp_options':
		$key = "options_name";
		if ( ! isset( $_GET["query"] ) ) {
			print "wp_options must be used with query<br/>";
			die( 1 );
		}
		break;

	default:
		print "bad usage";
		die( 2 );
}

// print header_text(false);
switch ( $operation ) {
	case "get":
		print header_text( false );
		$sql = "SELECT * FROM $table";
		if ( isset ( $_GET["query"] ) ) {
			$sql .= " where " . stripcslashes( $_GET["query"] );
		}
		print table_content( $sql );
		break;

	case "update":
		if ( ! isset( $_GET["source"] ) ) {
			print "must define source <br/>";
			die ( 1 );
		}
		$source = $_GET["source"];

		ImMultiSite::UpdateFromRemote( $table, $key, $source );
}

function get_decorated_diff( $old, $new ) {
	$from_start = strspn( $old ^ $new, "\0" );
	$from_end   = strspn( strrev( $old ) ^ strrev( $new ), "\0" );

	$old_end = strlen( $old ) - $from_end;
	$new_end = strlen( $new ) - $from_end;

	$start    = substr( $new, 0, $from_start );
	$end      = substr( $new, $new_end );
	$new_diff = substr( $new, $from_start, $new_end - $from_start );
	$old_diff = substr( $old, $from_start, $old_end - $from_start );

	$new = "$start<ins style='background-color:#ccffcc'>$new_diff</ins>$end";
	$old = "$start<del style='background-color:#ffcccc'>$old_diff</del>$end";

	return array( "old" => $old, "new" => $new );
}                                                                                                                                                                                                                                                                                                                                                                                                                                                                             ./tools / multi - site / sync . php                                                                         0000664 0001750 0001750 00000001756 13414634256 014551  0                                                                                                    ustar   agla                            agla                                                                                                                                                                                                                   <?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 07/01/19
 * Time: 12:54
 */
require_once( ROOT_DIR . "/agla/gui/inputs.php" );
require_once( TOOLS_DIR . "/multi-site/imMulti-site.php" );

function sync_from_master() {
	print header_text( false, true, true );

	$i = new ImMultiSite();
	print gui_header( 1, "מסנכרן מיקומים" );
	$i->UpdateFromRemote( "wp_woocommerce_shipping_zone_locations", "location_id" );

	print gui_header( 1, "מסנכרן שיטות משלוח" );
	$i->UpdateFromRemote( "wp_woocommerce_shipping_zone_methods", "instance_id" );

	print gui_header( 1, "מסנכרן איזורי משלוח" );
	$i->UpdateFromRemote( "wp_woocommerce_shipping_zones", "zone_id" );

	print gui_header( 1, "מסנכרן משימות" );
	$i->UpdateFromRemote( "im_missions", "id" );

	print gui_header( 1, "מסנרכן שיטות משלוח" );
	$i->UpdateFromRemote( "wp_options", "option_name", 0, "option_name like 'woocommerce_flat_rate_%_settings'", array( 'option_id' ) );
}                  ./tools / catalog / add - products . php                                                                    0000664 0001750 0001750 00000011700 13414640420 015460  0                                                                                                    ustar   agla                            agla                                                                                                                                                                                                                   <?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 21/07/17
 * Time: 15:11
 */
require_once( '../r-shop_manager.php' );
require_once( '../multi-site/imMulti-site.php' );
require_once( "../suppliers/gui.php" );

print header_text( true );


?>
<script charset="utf-8">

    function add_products() {
        document.getElementById("add_products").disabled = true;

        // Pass information to server for processing.
        var sel = document.getElementById("supplier_id");
        var idx = sel.selectedIndex;
//        if (idx == 0) {
//            alert "יש לבחור"
//        }
        var supplier_id = sel.options[idx].value;

        var request = "add-products-post.php?operation=create_products&supplier_id=" + supplier_id;

        // Check if remote
        var sel_remote_supplier = document.getElementById("remote_supplier");
        if (sel_remote_supplier) {
            var remote_supplier = sel_remote_supplier.options[sel_remote_supplier.selectedIndex].value;
            request = request + "&remote_supplier=" + remote_supplier;

            var sel_remote_category_id = document.getElementById("remote_category_id");
            request = request + "&remote_category_name=" + encodeURI(sel_remote_category_id.options[sel_remote_category_id.selectedIndex].innerHTML);
        }

        var sel_local_category_id = document.getElementById("local_category_id");
        if (sel_local_category_id.selectedIndex == 0) {
            alert("יש לבחור קטגוריה");
            document.getElementById("add_products").disabled = false;
            return;
        }

        request = request + "&local_category_name=" + encodeURI(sel_local_category_id.options[sel_local_category_id.selectedIndex].innerHTML);

        request += "&Params=";
        for (var i = 0; i < 20; i++) {
            var name = document.getElementById("name" + i).value;
            var price = document.getElementById("pric" + i).value;

            if (price > 0)
                request = request + encodeURI(name) + "," + price + ",";
        }

        // remove last comma
        request = request.substr(0, request.length - 1);
        logging.innerHTML = "מעבד. נא להמתין";

        xmlhttp = new XMLHttpRequest();
        xmlhttp.onreadystatechange = function () {
            // Wait to get delivery id.
            if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
            {
                logging.innerHTML = xmlhttp.response;
                document.getElementById("add_products").disabled = false;
                // logging.innerHTML += request;
            }
        }
        xmlhttp.open("GET", request, true);
        xmlhttp.send();

        // Display results.

    }
    function change_supplier() {
        var remote_suppliers = ["", "", '<?php print ImMultiSite::sExecute( "multi-site/get-suppliers-categs.php", 2 ); ?>'];
//        var site_names = ["", "", "<?php print ImMultiSite::GetSiteName( 2 );?>"];

        var sel = document.getElementById("supplier_id");
        var selected = sel.options[sel.selectedIndex];
        supplier_id = selected.value;
        var site_id = selected.getAttribute("data-site-id");
        var tools = selected.getAttribute("data-tools-url-id");

        if (site_id && !(site_id == <? print ImMultiSite::LocalSiteID(); ?>)) {
            remote_pricelist.innerHTML = "הוספה לרשימה " + /* site_names[site_id] +*/
                ": " + remote_suppliers[site_id];
        } else {
            remote_pricelist.innerHTML = "";
        }
    }

    function check(field) {
        var line = parseInt(field.name.substr(4));
        switch (field.name.substr(0, 4)) {
            case "name":
                if (field.value.length < 3) alert("שם מוצר צריך להכיל לפחות 3 תווים");
                else {
                    document.getElementById("pric" + line).focus();
                }
                break;
            case "pric":
                if (!(parseInt(field.value) > 1)) alert("מחיר צריך להיות מספר עשרוני, גדול מ-1");
                else {
                    var next_row = document.getElementById("name" + (line + 1));
                    if (next_row) next_row.focus();
                }
                break;
        }
    }
</script>
<?php
print gui_header( 1, "הוספת פריטים" );
print "ספק/אתר" . " ";
print_select_supplier( "supplier_id", true );

?>
<div id="remote_pricelist"></div>
הוספה לקטגוריה:
<?php
print_category_select( "local_category_id", true );

$table_content = array();
array_push( $table_content, array( "תאור", "מחיר" ) );
for ( $i = 0; $i < 20; $i ++ ) {
	array_push( $table_content, array(
		gui_input( "name" . $i, "", array( 'onchange="check(this)"' ) ),
		gui_input( "pric" . $i, "", array( 'onchange="check(this)"' ) )
	) );
}

print gui_table( $table_content, "new_products" );

print gui_button( "add_products", "add_products()", "הוסף פריטים" );

?>

<div id="logging"></div>                                                                ./tools/catalog/catalog-map-post.php                                                                0000664 0001750 0001750 00000023045 13415044517 016252  0                                                                                                    ustar   agla                            agla                                                                                                                                                                                                                   <?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 03/07/15
 * Time: 11:53
 */
require_once( '../r-shop_manager.php' );
require_once( 'catalog.php' );
require_once( '../pricelist/pricelist.php' );
require_once( '../multi-site/imMulti-site.php' );
require_once( "../wp/terms.php" );

// print header_text();

// To map item from price list to our database the shop manager select item from the price list
// and product_id. The triplet: product_id, supplier_id and product_code are sent as saved
// in im_supplier_products

$operation = $_GET["operation"];
// print $operation . "<br/>";

switch ( $operation ) {
	case "get_unmapped":
		my_log( "get_unmapped" );
		search_unmapped_products();
		break;

	case "create_term":
		my_log( $operation );
		$category_name = $_GET["category_name"];
		terms_create( $category_name );
		break;

	case "get_unmapped_terms":
		my_log( "get_unmapped_terms" );
		search_unmapped_terms();
		break;

	case 'get_invalid_mapped':
		search_invalid_mapping();
		break;

	case "remove_map":
		$id_to_remove = $_GET["id_to_remove"];
		$ids          = explode( ',', $id_to_remove );
		remove_map( $ids );
		break;

	case "hide":
		my_log( "hide" );
		$id_ = $_GET["ids"];
		$ids = explode( ',', $id_ );
		hide_product( $ids );
		break;

	case "map":
		$ids = $_GET["ids"];
		$ids = explode( ',', $ids );
		map_products( $ids );
		break;

	case "create_products":
		$category_name = $_GET["category_name"];
		my_log( "category: " . $category_name );
		$map_triplets = $_GET["create_info"];
		$ids          = explode( ',', $map_triplets );
		//var_dump($ids);
		Catalog::CreateProducts( $category_name, $ids );
		break;
}

function map_products( $ids ) {
	print "start mapping<br/>";

//    my_log(__METHOD__, __FILE__);
	for ( $pos = 0; $pos < count( $ids ); $pos += 3 ) {
		$site_id      = $ids[ $pos ];
		$product_id   = $ids[ $pos + 1 ];
		$pricelist_id = $ids[ $pos + 2 ];
//        my_log("product_id = " . $product_id . ", supplier_id=" . $supplier_id . ", product_name=" . $product_name);
		print "adding " . $site_id . " " . $product_id . " " . $pricelist_id . "<br/>";
		Catalog::AddMapping( $product_id, $pricelist_id, $site_id );
	}
}

function remove_map( $ids ) {
	$catalog = new Catalog();

	for ( $pos = 0; $pos < count( $ids ); $pos ++ ) {
		$map_id = $ids[ $pos ];
		$catalog->DeleteMapping( $map_id );
	}
}

// Hide this items.
function hide_product( $ids ) {
	my_log( "start hide" );
//    print "hide";
	$catalog = new Catalog();

	for ( $pos = 0; $pos < count( $ids ); $pos ++ ) {
		$pricelist_id = $ids[ $pos ];
		$catalog->HideProduct( $pricelist_id );
	}
//    print "done";

//    for ($pos = 0; $pos < count($ids); $pos ++) {
//        $prod_id = $ids[$pos];
//        my_log("hide prod " . $prod_id);
//        $catalog->HideProduct($prod_id);
//    }
}

function is_mapped( $code ) {
	$sql = 'SELECT id FROM `im_supplier_mapping` WHERE supplier_product_code = ' . $code .
	       ' AND supplier_product_code != 10';

	$id = sql_query_single_scalar( $sql );

	if ( $id > 0 ) {
		// print $sql;
		return true;
	}

	return false;
}

// Write the result to screen. Client will insert to result_table
//function search_unmapped_products() {
//	search_unmapped_local();
//	//  search_unmapped_remote();
//}

function search_unmapped_remote() {
	global $conn;

	$data = "";

	$sql = "SELECT site_id, id FROM im_suppliers WHERE site_id > 0";

	$result = mysqli_query( $conn, $sql );

	if ( ! $result ) {
		sql_error( $sql );

		return "No result";
	}

	while ( $row = mysqli_fetch_row( $result ) ) {
		$site_id     = $row[0];
		$supplier_id = $row[1];
		print $site_id . " " . $supplier_id . "<br/>";

		// print $site_id;

		$remote = get_site_tools_url( $site_id ) . "/catalog/get-as-pricelist.php";
		$html   = im_file_get_html( $remote );
		foreach ( $html->find( 'tr' ) as $row ) {
			$prod_id = $row->find( 'td', 1 )->plaintext;
			//print "prod id " . $prod_id;
			$name          = $row->find( 'td', 2 )->plaintext;
			$date          = $row->find( 'td', 3 )->plaintext;
			$price         = $row->find( 'td', 4 )->plaintext;
			$local_prod_id = multisite_map_get_remote( $prod_id, $site_id );

			if ( ! ( $local_prod_id > 0 ) and $local_prod_id != - 1 ) {
				$data .= print_unmapped( $prod_id, $prod_id, $name, $supplier_id, $site_id );
			}
		}
	}
	print $data;
}

function search_unmapped_products() {
	global $conn;

	$sql    = "SELECT id, supplier_id, product_name " .
	          " FROM im_supplier_price_list ORDER BY 2, 3";
	$result = mysqli_query( $conn, $sql );

	$data = "<tr>";
	$data .= "<td>בחר</td>";
	$data .= "<td>קוד</td>";
	$data .= "<td>שם מוצר</td>";
	$data .= "<td>מזהה ספק</td>";
	$data .= "<td>מוצר שלנו </td>";
	$data .= gui_cell( "קטגוריה" );
	$data .= gui_cell( "תמונה" );
	$data .= "</tr>";

	while ( $row = mysqli_fetch_row( $result ) ) {
		$pricelist_id = $row[0];

		$pricelist = PriceList::Get( $pricelist_id );

		$prod_link_id = Catalog::GetProdID( $pricelist_id, true );

		if ( $prod_link_id ) {
			$prod_id = $prod_link_id[0];
			if ( ( ( $prod_id == - 1 ) or ( $prod_id > 0 ) ) and get_post_status( $prod_id ) != 'trash' ) {
				continue;
			}
		}
		$data .= print_unmapped( $pricelist_id, $pricelist["supplier_product_code"], $pricelist["product_name"],
			$pricelist["supplier_id"] );
	}
	print $data;
}

function search_unmapped_terms() {
	global $conn;

	// print header_text();
	$all_terms      = array();
	$all_terms_flat = array();
	$sql            = "SELECT id, supplier_id, product_name " .
	                  " FROM im_supplier_price_list ORDER BY 2, 3";
	$result         = mysqli_query( $conn, $sql );

	while ( $row = mysqli_fetch_row( $result ) ) {
		$pricelist_id = $row[0];

		$prod_link_id = Catalog::GetProdID( $pricelist_id, true );

		$prod_id = $prod_link_id[0];
		if ( ( ( $prod_id == - 1 ) or ( $prod_id > 0 ) ) and get_post_status( $prod_id ) != 'trash' ) {
			continue;
		}

		$item      = PriceList::Get( $pricelist_id );
		$prod_term = $item["category"];
		$terms     = explode( ",", $prod_term );
		foreach ( $terms as $term ) {
			// print "term: " . $term . "<br/>";
			if ( ! in_array( $term, $all_terms_flat ) ) {
				array_push( $all_terms_flat, $term );
				array_push( $all_terms, array( 'term' => $term, 'id' => terms_get_id( $term ) ) );
			}
		}
	}
	// var_dump($all_terms);

	// print gui_select_datalist("term", "name", $all_terms, "");
	print gui_select( "create_term", "term", $all_terms, "", "" );
}


function print_unmapped( $pricelist_id, $supplier_product_code, $product_name, $supplier_id, $site_id = 0 ) {
	$match = false;

//	if (substr($striped_prod, 0, 8) == "סברס") print "Y" . $striped_prod . "Y<br/>";

	$striped_prod = $product_name;
	foreach ( array( "אורגני", "יחידה", "טרי" ) as $word_to_remove ) {
		$striped_prod = str_replace( $word_to_remove, "", $striped_prod );
//		print $word_to_remove . " " . $striped_option . "<br/>";
	}

	$striped_prod = trim( $striped_prod );

	$prod_options = Catalog::GetProdOptions( $product_name );

	$options = "";

	foreach ( $prod_options as $row1 ) {

		// Get line options
//         print $row1[1] . " " . $product_name . "<br/>";
		// var_dump($row1); print "<br/>";
		$striped_option = $row1["post_title"];
		$striped_option = str_replace( "-", " ", $striped_option );
		$striped_option = trim( $striped_option, " " );
//        if (substr($striped_option, 0, 8) == "סברס") print "X" . $striped_option . "X<br/>";
		$options .= '<option value="' . $row1["id"] . '" ';
		if ( ! strcmp( $striped_option, $striped_prod ) ) {
			$options .= 'selected';
//			$match   = true;
		}
		$options .= '>' . $row1["post_title"] . '</option>';
	}

	$line = "<tr>";
	$line .= "<td>" . gui_checkbox( "chk" . $pricelist_id, "product_checkbox", $match ) . "</td>";
	$line .= "<td>" . $supplier_product_code . "</td>";
	$line .= "<td>" . $product_name . "</td>";
	$line .= "<td>" . $supplier_id . "</td>";
	$line .= "<td><select onchange='selected(this)' id='$pricelist_id'>";

	$line .= $options;

	$line .= '</select></td>';
	$line .= "<td>" . get_supplier_name( $supplier_id ) . "</td>";
	if ( $site_id > 0 ) {
		$line .= "<td style=\"display:none;\">" . $site_id . "</td>";
	}

	$item = PriceList::Get( $pricelist_id );
	$line .= gui_cell( $item["category"] );
	$url  = $item["picture_path"];
	$line .= gui_cell( basename( $url ) );
	$line .= "</tr>";

	return $line;
}

// Write the result to screen. Client will insert to result_table
function search_invalid_mapping() {
	// Purpose: read supplier items and map them to our database.
	// First get all unmapped items
//    $sql = 'SELECT id, product_id, supplier_id, supplier_product_name FROM `im_supplier_mapping` WHERE (supplier_id, supplier_product_name) not in' .
//        ' (select supplier_id, product_name from im_supplier_price_list)' ;
	$sql = 'SELECT id, supplier_id, supplier_product_name, supplier_product_code
            FROM im_supplier_mapping';


	$result = sql_query( $sql );

	$data = "<tr>";
	$data .= "<td>בחר</td>";
	$data .= "<td>מזהה מיפוי</td>";
	$data .= "<td>מזהה מוצר</td>";
	$data .= "<td>מזהה ספק</td>";
	$data .= "<td>שם מוצר</td>";
	$data .= "</tr>";

	while ( $row = mysqli_fetch_row( $result ) ) {
		$line_id      = $row[0];
		$product_id   = $row[1];
		$supplier_id  = $row[2];
		$product_name = $row[3];

		$line = "<tr>";
		$line .= "<td><input id=\"chk" . $product_id . "\" class=\"invalid_map_checkbox\" type=\"checkbox\"></td>";
		$line .= "<td>" . $line_id . "</td>";
		$line .= "<td>" . $product_id . "</td>";
		$line .= "<td>" . get_supplier_name( $supplier_id ) . "</td>";
		$line .= "<td>" . $product_name . "</td>";

		$line .= "</tr>";
		$data .= $line;
	}
	print $data;
}

?>

                                                                                                                                                                                                                                                                                                                                                                                                                                                                                           ./tools/catalog/catalog-map.php                                                                     0000664 0001750 0001750 00000021502 13415044524 015261  0                                                                                                    ustar   agla                            agla                                                                                                                                                                                                                   <?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 03/07/15
 * Time: 11:53
 */
require_once( '../r-shop_manager.php' );
require_once( 'catalog.php' );
require_once( '../gui.php' );

// To map item from price list to our database the shop manager select item from the price list
// and product_id. The triplet: product_id, supplier_id and product_code are sent as saved
// in im_supplier_products

?>
<html dir="rtl" lang="he">
<header>
    <meta http-equiv="content-type" content="text/html; charset=utf-8">
    <script type="text/javascript" src="/agla/client_tools.js"></script>
	<?php
	require_once( "../catalog/mapping.php" );
	?>

    <script>
        function create_term() {
            var t = document.getElementById("create_term");
            var category_name = t.options[t.selectedIndex].text;

            xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function () {
                // Wait to get query result
                if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
                {
                    refresh();
                }
            }
            var request = "catalog-map-post.php?operation=create_term&category_name=" + encodeURI(category_name);
            xmlhttp.open("GET", request, true);
            xmlhttp.send();
        }
        function create_products() {
            var sel = document.getElementById("product_cat").selectedIndex;
            var category_name = document.getElementById("product_cat").options[sel].innerHTML;
            var collection = document.getElementsByClassName("product_checkbox");
            var table = document.getElementById("map_table");
            var map_ids = new Array();
            for (var i = 0; i < collection.length; i++) {
                if (collection[i].checked) {
                    var product_name = encodeURIComponent(get_value(table.rows[i + 1].cells[2].firstChild.data));
                    var supplier_code = get_value(table.rows[i + 1].cells[3].firstChild);
                    var pricelist_id = collection[i].id.substr(3);
                    var supplier_product_code = get_value(table.rows[i + 1].cells[1].firstChild);
                    map_ids.push(product_name);
                    map_ids.push(supplier_code.data);
                    map_ids.push(pricelist_id);
                    map_ids.push(supplier_product_code.data);
                    // Send every 10 products
                    if (map_ids.length > 40) {
                        xmlhttp = new XMLHttpRequest();
                        var request = "catalog-map-post.php?operation=create_products&category_name=" + encodeURI(category_name) +
                            "&create_info=" + map_ids.join();
                        xmlhttp.open("GET", request, true);
                        xmlhttp.send();
                        map_ids.length = 0;
                    }
                }
            }
            xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function () {
                // Wait to get query result
                if (xmlhttp.readyState === 4 && xmlhttp.status === 200)  // Request finished
                {
                    refresh();
                }
            }
            request = "catalog-map-post.php?operation=create_products&category_name=" + encodeURI(category_name) +
                "&create_info=" + map_ids.join();
            xmlhttp.open("GET", request, true);
            xmlhttp.send();
        }

        function select_term_products() {
            var t = document.getElementById("create_term");
            var term = t.options[t.selectedIndex].text;
            var table = document.getElementById("map_table");
            var collection = document.getElementsByClassName("product_checkbox");
            var map_ids = new Array();
            for (var i = 0; i < table.rows.length - 1; i++) {
                var terms = get_value(table.rows[i + 1].cells[6].firstChild).data;
                if (terms && terms.indexOf(term) >= 0)
                    collection[i].checked = true;
            }
        }


        function remove_map_products() {
            var collection = document.getElementsByClassName("invalid_map_checkbox");
            var table = document.getElementById("invalid_map_table");
            var map_ids = new Array();
            for (var i = 0; i < collection.length; i++) {
                if (collection[i].checked) {
                    var map_id = get_value(table.rows[i + 1].cells[1].firstChild);
                    map_ids.push(map_id);
                }
            }
            xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function () {
                // Wait to get query result
                if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
                {
                    refresh();
                }
            }
            var request = "catalog-map-post.php?operation=remove_map&id_to_remove=" + map_ids.join();
            xmlhttp.open("GET", request, true);
            xmlhttp.send();
        }

        function refresh() {
            // Needed mapping
            xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function () {
                // Wait to get query result
                if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
                {
                    table = document.getElementById("map_table");
                    table.innerHTML = xmlhttp.response;
                }
            }
            var request = "catalog-map-post.php?operation=get_unmapped";

            xmlhttp.open("GET", request, true);
            xmlhttp.send();

            xmlhttp1 = new XMLHttpRequest();
            xmlhttp1.onreadystatechange = function () {
                // Wait to get query result
                if (xmlhttp1.readyState == 4 && xmlhttp1.status == 200)  // Request finished
                {
                    var terms = document.getElementById("create_term");
                    terms.innerHTML = xmlhttp1.response;
                }
            }
            xmlhttp1.onloadend = function () {
                if (xmlhttp1.status === 404 || xmlhttp1.status === 500)
                    refresh();
            }

            var request1 = "catalog-map-post.php?operation=get_unmapped_terms";

            xmlhttp1.open("GET", request1, true);
            xmlhttp1.send();

            // Unneeded mapping
//
//        xmlhttp1 = new XMLHttpRequest();
//        xmlhttp1.onreadystatechange = function()
//        {
//            // Wait to get query result
//            if (xmlhttp1.readyState==4 && xmlhttp1.status==200)  // Request finished
//            {
//                table = document.getElementById("invalid_map_table");
//                table.innerHTML = xmlhttp1.response;
//            }
//        }
//        var request1 = "catalog-map-post.php?operation=get_invalid_mapped";
//        xmlhttp1.open("GET", request1, true);
//        xmlhttp1.send();

        }

        function select_all_toggle() {
            var is_on = document.getElementById("select_all").checked;
            var collection = document.getElementsByClassName("product_checkbox");
            for (var i = 0; i < collection.length; i++) {
                collection[i].checked = is_on;
            }
        }

        function select_detailed() {
            var is_on = document.getElementById("select_details").checked;
            var collection = document.getElementsByClassName("product_checkbox");
            for (var i = 0; i < collection.length; i++) {
                collection[i].checked = is_on;
            }
        }

        function selected(sel) {
            var pricelist_id = sel.id;
            document.getElementById("chk" + pricelist_id).checked = true;
        }

    </script>
</header>
<body onload="refresh()">
<input id="select_all" type="checkbox" onclick="select_all_toggle()">בחר הכל</button>
<input id="select_details" type="checkbox" onclick="select_detailed()">בחר מפורטים</button>

<button id="btn_hide" onclick="map_hide()">הסתר</button>
<button id="btn_map" onclick="map_products()">שמור מיפוי</button>
<button id="btn_create" onclick="create_products()">צור מוצרים</button>
<datalist id="unmapped_terms"></datalist>

<?php
print_category_select( "product_cat" );

print gui_header( 1, "יצירת מוצרים" );

print gui_select( "create_term", "", array(), "", "" );
// print gui_select_datalist("term", "t", array(), "");

print gui_button( "btn_select_term_items", "select_term_products()", "בחר" );

print gui_button( "btn_create_term", "create_term()", "צור קטגוריה" );

print gui_header( 1, "פריטים לא ממופים" );

?>


<table id="map_table">
</table>

<!--<button id="btn_remove_map" onclick="remove_map_products()">הסר מיפוי</button>-->

<table id="invalid_map_table">
</table>

</body>

</html>                                                                                                                                                                                              ./tools/catalog/copy-product-info.php                                                               0000664 0001750 0001750 00000000333 13414640420 016450  0                                                                                                    ustar   agla                            agla                                                                                                                                                                                                                   <?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 06/03/18
 * Time: 05:16
 */

require_once( "../r-shop_manager.php" );
require_once( "../multi-site/imMulti-site.php" );
print header_text( false, true, false );

                                                                                                                                                                                                                                                                                                     ./
tools / auto / run . php                                                                                0000664 0001750 0001750 00000012714 13415036057 013245  0                                                                                                    ustar   agla                            agla                                                                                                                                                                                                                   <?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 27/08/18
 * Time: 04:37
 */

 error_reporting( E_ALL );
 ini_set( 'display_errors', 'on' );

if ( ! defined( "TOOLS_DIR" ) ) {
	define( 'TOOLS_DIR', dirname( dirname( __FILE__ ) ) );
}

return; // Not used
require_once( TOOLS_DIR . "/im_tools.php" );
require_once( TOOLS_DIR . "/options.php" );
require_once( TOOLS_DIR . "/delivery/missions.php" );
// require_once( TOOLS_DIR . "/multi-site/imMulti-site.php" );
require_once( TOOLS_DIR . "/supplies/supplies.php" );
require_once( TOOLS_DIR . "/pricelist/pricelist-process.php" );

// DEBUG = 1. output on screen
$debug = 1;
//$gap_key = "run_gap";
//$run_gap = info_get( $gap_key );
//if ( $debug ) {
//	$run_gap = 5;
//} // debug mode
//$date_format = "h:m:s";
//
//if ( ! $run_gap ) {
//	// Set default
//	$run_gap = 600;
//	print "setting default run_gap to " . $run_gap . "<br/>";
//	info_update( "run_gap", $run_gap );
//}
//
ob_start();
$this_run_time = time();
print "run started " . date( $date_format ) . "\n";

// Check last run
$key      = "weekly_run";
$last_run = info_get( $key );

print "last_run: " . $last_run . "\n";
print "this_run_time: " . $this_run_time . "\n";
if ( $this_run_time - $last_run < $run_gap and $debug == 0 ) {
	print "no need to run\n";
	close_file( $debug );

	return;
}
// print $run . " " . $create_time . "<br/>";

auto_mail();

// TODO: Check permission
//if ( ImMultiSite::isMaster() ) {
//	duplicate_week();
//} else {
//	//require_once( TOOLS_DIR . "/delivery/sync-from-master.php" );
//}

update_remotes();

//if ( MultiSite::LocalSiteID() == 4 ) {
//	print "im haadama not proceesed<br/>";
//	// $results = "";
//	// print $results;
//}

auto_supply();

info_update( $key, $this_run_time );
close_file( $debug );

function close_file( $debug ) {

	global $date_format;
	print "run ended " . date( $date_format ) . "\n";

	$log = ob_get_clean();
//	print "log: " . $log . "<br/>";

	$file_name = ROOT_DIR . "/logs/run-" . date( 'd' ) . ".txt";
	// print "results saved to " . $file_name . "<br/>";
	$file = fopen( $file_name, "a" );
	fwrite( $file, $log );

	if ( $debug == 1 ) // debug
	{
		print nl2br( $log );
	}
}

function auto_mail() {
	require_once( TOOLS_DIR . "/orders/form.php" );
	require_once( TOOLS_DIR . "/mail.php" );

	global $business_name;
	global $support_email;

	$sql = "SELECT user_id FROM wp_usermeta WHERE meta_key = 'auto_mail'";

	$auto_list = sql_query_array_scalar( $sql );

	print "Auto mail<br/>";

	foreach ( $auto_list as $client_id ) {
		print get_customer_name( $client_id ) . "<br/>";
		$last = get_user_meta( $client_id, "last_email", true );
		if ( $last == date( 'Y-m-d' ) ) {
			print "already sent";
			continue;
		}
		$setting = get_user_meta( $client_id, 'auto_mail', true );
		$day     = strtok( $setting, ":" );
		$categ   = strtok( ":" );
		print "day: " . $day . "<br/>";
		print "categ: " . $categ . "<br/>";

		if ( $day == date( 'w' ) ) {
			$subject = "מוצרי השבוע ב-" . $business_name;
			$mail    = "שלום " . get_customer_name( $client_id ) .
			           " להלן רשימת מוצרי פרוטי ";

			do {
				if ( $categ == 0 ) {
					$mail = show_category_all( false, true );
					break;
				}
				if ( $categ == "f" ) {
					$mail = show_category_all( false, true, true );
					break;
				}
				foreach ( explode( ",", $categ ) as $categ ) {
					$mail .= show_category_by_id( $categ, false, true );
				}
			} while ( 0 );
			$user_info = get_userdata( $client_id );
			$to        = $user_info->user_email . ", " . $support_email;

			$rc = send_mail( $subject, $to, $mail );
			print "subject: " . $subject . "<br/>";
			print "mail: " . $mail . "<br/>";
			print "to: " . $to . "<br/>";
			print "rc: " . $rc . "<br/>";

			update_user_meta( $client_id, "last_email", date( 'Y-m-d' ) );
		}
	}

	// Todo: remove this
}
function auto_supply() {
//	print "auto supply<br/>";
	$sql = "SELECT id FROM im_suppliers WHERE  auto_order_day = " . date( "w" );

	// print $sql;
	$suppliers = sql_query_array_scalar( $sql );

	foreach ( $suppliers as $supplier_id ) {
		print "create auto order for " . get_supplier_name( $supplier_id ) . "\n";

		// $s = new Supply($supplier_id);
		$last_order = sql_query_single_scalar( "select max(date) from im_supplies where supplier = " . $supplier_id );

		print "last: " . $last_order . "\n";
		$sold         = supplier_report_data( $supplier_id, $last_order, date( 'y-m-d' ) );
		$supply_lines = array();
		$total        = 0;
		foreach ( $sold as $k => $product ) {
			$prod_id  = $sold[ $k ][0];
			$quantity = $sold[ $k ][1];
			$price    = get_buy_price( $prod_id, $supplier_id );
			if ( $quantity > 0 ) {
				print get_product_name( $prod_id ) . " " . $quantity . "\n";
				array_push( $supply_lines, array( $prod_id, $quantity ) );
				$total += $quantity * $price;
			}
		}
		if ( $total > sql_query_single_scalar( "select min_order from im_suppliers where id = " . $supplier_id ) ) {
			$supply = Supply::CreateSupply( $supplier_id );
			foreach ( $supply_lines as $line ) {
				$supply->AddLine( $line[0], $line[1], get_buy_price( $line[0] ) );
			}
			$supply->Send();
		} else {
			print "not enough for an order\n";
		}
//		var_dump($sold);
	}
}

function update_remotes() {
	// Update remote pricelist.
	$sql = "select id, site_id from im_suppliers where site_id is not null";

	$suppliers = sql_query( $sql );

	while ( $row = sql_fetch_row( $suppliers ) ) {
		$supplier_id = $row[0];
		pricelist_remote_site_process( $supplier_id, $results, false );

		// print $row[0] . " " . $row[1] . "<br/>";
	}
}                                                    ./tools / auto / create . php                                                                             0000664 0001750 0001750 00000000621 13414640420 013670  0                                                                                                    ustar   agla                            agla                                                                                                                                                                                                                   <?php

require_once( '../r-shop_manager.php' );
require_once( '../multi-site/imMulti-site.php' );
require_once( '../orders/orders-common.php' );
require_once( '../supplies/supplies.php' );
require_once( '../pricelist/pricelist.php' );
require_once( ROOT_DIR . '/agla/gui/inputs.php' );
require_once( "../delivery/missions.php" );
require_once( "start.php" );

print header_text();


create_missions();                                                                                                               ./tools / auto / start . php                                                                              0000664 0001750 0001750 00000006765 13414640420 013601  0                                                                                                    ustar   agla                            agla                                                                                                                                                                                                                   <?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 23/01/17
 * Time: 18:11
 */
error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

require_once( '../r-shop_manager.php' );
require_once( '../multi-site/imMulti-site.php' );
require_once( '../orders/orders-common.php' );
require_once( '../supplies/supplies.php' );
require_once( '../pricelist/pricelist.php' );
require_once( ROOT_DIR . '/agla/gui/inputs.php' );
require_once( "../delivery/missions.php" );
print header_text();

print "aa";
if ( isset( $_GET["operation"] ) ) {
	$operation = $_GET["operation"];
	print "op = " . $operation . "<br/>";
	switch ( $operation ) {
		case "create_inventory_view":
			do_create_inventory_views();
//			print gui_header( 1, "מאפס מלאי" );
//			reset_inventory();
//				print gui_header( 2, "מאתחל רשימה של אמיר" );
//				$PL = new PriceList( 100004 );
//				$PL->RemoveLines( 1 );
//				$PL->RemoveLines( 2 );
//			print gui_header( 2, "יוצר הזמנות למנויים" );
//			orders_create_subs();
//			print gui_header( 1, "משימות" );
//			if ( MultiSite::IsMaster() ) {
//				print "יוצר חדשות<br/>";
//				create_missions();
//				print MultiSite::RunAll( "multi-site/sync-data.php?table=im_missions&operation=update&source=" . MultiSite::LocalSiteID() );
//			} else {
//				print "מעתיק ממסטר - עדיין לא פעיל<br/>";
//			}
			break;
	}
} else {
	$open = display_active_supplies( array( 1 ) );
	if ( $open ) {
		print gui_header( 2, "אספקות לטיפול" );
		print $open;
	}
	$got = display_active_supplies( array( 3 ) );
	if ( $got ) {
		print gui_header( 2, "אספקות בדרך" );
		print $got;
	}
//	print "<br/><B>" . "יש לסגור הספקות לפני איפוס שבועי!" . "</B><br/>";
//	print "<br/><B>" . "איפוס שבועי מוחק את הרשימה של אמיר בן יהודה!" . "</B><br/>";
	print gui_hyperlink( "האם ברצונך לאפס את המלאי?", "start.php?operation=reset_inventory" );
}

function create_missions() {
	$this_week = date( "Y-m-d", strtotime( "last sunday" ) );
	$sql       = "SELECT id FROM im_missions WHERE FIRST_DAY_OF_WEEK(date) = '" . $this_week . "' order by 1";
//	print $sql;

	$result = sql_query( $sql );
	while ( $row = sql_fetch_row( $result ) ) {
		$mission_id = $row[0];
		print "משכפל את משימה " . $mission_id . "<br/>";

		duplicate_mission( $mission_id );
	}
}


function do_create_inventory_views() {

	$sql = "create or replace view i_in as " .
	       " select product_id, sum(quantity) as q_in " .
	       " from im_supplies_lines l " .
	       " join im_supplies s " .
	       //	       " where supply_id > " . $last_supply . " and l.status < 8 " .
	       " where l.status < 8 " .
	       " and s.status < 9 " .
	       " and s.id = l.supply_id " .
	       " group by 1";

	print $sql . "<br/>";
	sql_query( $sql );

	$sql = "create or replace view i_out as " .
	       " select prod_id, round(sum(dl.quantity),1) as q_out " .
	       " from im_delivery_lines dl" .
	       //	       " where dl.delivery_id > " . $last_delivery .
	       " group by 1 ORDER BY  1";

	sql_query( $sql );
	print $sql . "<br/>";

//	$sql = "cerate or replace VIEW `i_total` AS " .
//	  "AS select `i_out`.`prod_id` AS `prod_id`,`wp_posts`.`post_title` AS `product_name`, " .
//	       "(`i_in`.`q_in` - `i_out`.`q_out`) AS `q` from ((`i_out` join `i_in`) join `wp_posts`) " .
//	       " where ((`i_in`.`product_id` = `i_out`.`prod_id`) and (`wp_posts`.`ID` = `i_in`.`product_id`))";
//	  sql_query($sql);
}


           ./tools / auto / daily . php                                                                              0000664 0001750 0001750 00000000211 13414630074 013526  0                                                                                                    ustar   agla                            agla                                                                                                                                                                                                                   <?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 07/01/19
 * Time: 12:46
 */

require_once( "../delivery/sync-from-master.php" );                                                                                                                                                                                                                                                                                                                                                                                       ./tools / auto / test . php                                                                               0000664 0001750 0001750 00000000315 13414640420 013404  0                                                                                                    ustar   agla                            agla                                                                                                                                                                                                                   <?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 13/03/17
 * Time: 23:18
 */

require_once( '../multi-site/imMulti-site.php' );

// MultiSite::RunAll("delivery/close-deliveries.php");
//create_ord
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                   