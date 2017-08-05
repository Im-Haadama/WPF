<?php
require_once( '../tools.php' );
require_once( '../header.php' );
?>
<html dir="rtl">
<header>
    <script>
        function change_supplier() {

        }

        function get_value(element) {
            if (element === null) {
                return 0;
            }
            if (element.tagName == "SELECT") {
                var idx = element.selectedIndex;
                return element.options[idx].value;
            }

            if (element.tagName == "INPUT") {
                return element.value;
            }
            if (element.tagName == "A") {
                return element.innerHTML;
            }

            return element.nodeValue;
        }
        function delete_item() {
            var t = document.getElementById("table_business");
            var ids = new Array();
            var i;

            // Skip the header and the summary lines
            for (i = 1; i < t.rows.length - 1; i++) {
                ids.push(get_value(t.rows[i].cells[1].firstChild));
            }

            xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function () {
                // Wait to get query result
                if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
                {
                    window.location = window.location;
                }
            }
            var request = "business.php?operation=delete_items&ids=" + ids.join();
            // alert (request);
            xmlhttp.open("GET", request, true);
            xmlhttp.send();
        }
        function add_item() {
            var part_id = get_value(document.getElementById("supplier_id"));
            var date = get_value(document.getElementById("date"));
            var amount = -get_value(document.getElementById("amount"));
            var ref = get_value(document.getElementById("ref"));

            xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function () {
                // Wait to get query result
                if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
                {
                    window.location = window.location;
                }
            }
            var request = "business.php?operation=add_item&part_id=" + part_id + '&date=' + date +
                '&amount=' + amount + '&ref=' + ref;
            xmlhttp.open("GET", request, true);
            xmlhttp.send();
        }

    </script>
</header>
<body>
<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 14/10/16
 * Time: 05:59
 */

// Build the query
$sql   = "SELECT id, date, amount, delivery_fee FROM im_business_info WHERE ";
$query = " is_active = 1 ";
foreach ( $_GET as $key => $value ) {
	$query .= " and " . $key . " = '" . $value . "'";
}
$sql .= $query;
$sql .= ' order by 3 desc';

print "<table dir='rtl' id='table_business'>";
print "<tr>";
print_col( "#" );
print_col( "מזהה" );
print_col( "לקוח/ספק" );
print_col( "תאריך" );
print_col( "שבוע" );
print_col( "סכום" );
print_col( "תעודת משלוח" );
print_col( "דמי משלוח" );
print "</tr>";

//$sql = "SELECT id" .
//    " FROM im_business_info ";

$seq                = 1;
$total_amount       = 0;
$total_delivery_fee = 0;
$export = mysql_query( $sql ) or die ( "Sql error : " . mysql_error() . $sql );
while ( $row = mysql_fetch_row( $export ) ) {
	$total_amount       += $row[2];
	$total_delivery_fee += $row[3];
	print_business( $row[0], true, $seq );
	$seq ++;
}
print "<tr>";
print_col( "" );
print_col( "" );
print_col( "" );
print_col( "" );
print_col( "" );
print_col( $total_amount );
print_col( "" );
print_col( "$total_delivery_fee" );
print "</tr>";
print "</table>";

function print_col( $hdr ) {
	print "<td>" . $hdr . "</td>";
}

function get_name( $id ) {
	$sql = "SELECT display_name FROM wp_users WHERE id = " . $id .
	       " UNION SELECT supplier_name FROM im_suppliers WHERE id = " . $id;

	$export = mysql_query( $sql ) or die ( "Sql error : " . mysql_error() . $sql );
	$row = mysql_fetch_row( $export );

	return $row[0];
}

// From here created by coder.old.php
function delete_business( $id ) {
	$sql = "delete from im_business_info where id = $id";
	$export = mysql_query( $sql ) or die ( "Sql error : " . mysql_error() . $sql );
}

function print_business( $id, $horizontal, $seq ) {
	$sql = "select id, part_id, date, week, amount, ref, delivery_fee from im_business_info where id = $id";
	$export = mysql_query( $sql ) or die ( "Sql error : " . mysql_error() . $sql );
	$row = mysql_fetch_row( $export );
	if ( ! $horizontal ) {
		print "<table>";
	}
	if ( $horizontal ) {
		print "<tr><td>$seq</td>";
	}
	if ( ! $horizontal ) {
		print "<tr>";
	}
	if ( ! $horizontal ) {
		print "<td>";
		print "id";
		print "</td>";
	}
	print "<td>";
	print "<a href=\"get_all.php?id=" . urlencode( $row[0] );
	print "\">";
	print $row[0];
	print "</a>";
	print "</td>";
	if ( ! $horizontal ) {
		print "</tr>";
	}
	if ( ! $horizontal ) {
		print "<tr>";
	}
	if ( ! $horizontal ) {
		print "<td>";
		print "part_id";
		print "</td>";
	}
	print "<td>";
	print "<a href=\"get_all.php?part_id=" . urlencode( $row[1] );
	print "\">";
	print get_name( $row[1] );
	print "</a>";
	print "</td>";
	if ( ! $horizontal ) {
		print "</tr>";
	}
	if ( ! $horizontal ) {
		print "<tr>";
	}
	if ( ! $horizontal ) {
		print "<td>";
		print "date";
		print "</td>";
	}
	print "<td>";
	print "<a href=\"get_all.php?date=" . urlencode( $row[2] );
	print "\">";
	print $row[2];
	print "</a>";
	print "</td>";
	if ( ! $horizontal ) {
		print "</tr>";
	}
	if ( ! $horizontal ) {
		print "<tr>";
	}
	if ( ! $horizontal ) {
		print "<td>";
		print "week";
		print "</td>";
	}
	print "<td>";
	print "<a href=\"get_all.php?week=" . urlencode( $row[3] );
	print "\">";
	print $row[3];
	print "</a>";
	print "</td>";
	if ( ! $horizontal ) {
		print "</tr>";
	}
	if ( ! $horizontal ) {
		print "<tr>";
	}
	if ( ! $horizontal ) {
		print "<td>";
		print "amount";
		print "</td>";
	}
	print "<td>";
	print "<a href=\"get_all.php?amount=" . urlencode( $row[4] );
	print "\">";
	print $row[4];
	print "</a>";
	print "</td>";
	if ( ! $horizontal ) {
		print "</tr>";
	}
	if ( ! $horizontal ) {
		print "<tr>";
	}
	if ( ! $horizontal ) {
		print "<td>";
		print "ref";
		print "</td>";
	}
	print "<td>";
	print "<a href=\"../delivery/get-delivery.php?id=" . $row[5];
	print "\">";
	print $row[5];
	print "</a>";
	print "</td>";
	if ( ! $horizontal ) {
		print "</tr>";
	}
	if ( ! $horizontal ) {
		print "<tr>";
	}
	if ( ! $horizontal ) {
		print "<td>";
		print "delivery_fee";
		print "</td>";
	}
	print "<td>";
	print "<a href=\"get_all.php?delivery_fee=" . urlencode( $row[6] );
	print "\">";
	print $row[6];
	print "</a>";
	print "</td>";
	if ( ! $horizontal ) {
		print "</tr>";
	}
	if ( $horizontal ) {
		print "</tr>";
	}
}

print "</table>";

?>
<br>
<h1>הוסף תעודת משלוח</h1>

<br/>
<table>
    <tr>
        <td>ספק:</td>
        <td><? print_select_supplier( "supplier_id", true ); ?></td>
    </tr>
    <tr>
        <td>תאריך:</td>
        <td><input type="date" id="date" value="<?php echo date( 'Y-m-d' ); ?>"></td>
    </tr>
    <tr>
        <td>סכום:</td>
        <td><input type="text" id="amount"></td>
    </tr>
    <tr>
        <td>סימוכין:</td>
        <td><input type="text" id="ref"></td>
    </tr>
</table>
<button id="btn_add_business" onclick="add_item()">+</button>

<h2>מחק שורות שמוצגות</h2>
<button id="btn_delete_business" onclick="delete_item()">-</button>


</body>
</html>
