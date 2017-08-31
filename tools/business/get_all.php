<?php
require_once( '../im_tools.php' );
require_once( '../header.php' );
require_once( "../gui/inputs.php" );
require_once( "../delivery/delivery.php" );
require_once( "../suppliers/gui.php" );
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
if ( isset( $_GET["week"] ) ) {
	$week = $_GET["week"];
} else {
	$week = date( "Y-m-d", strtotime( "last sunday" ) );
}

if ( date( 'Y-m-d' ) > date( 'Y-m-d', strtotime( $week . "+1 week" ) ) ) {
	print gui_hyperlink( "שבוע הבא", "get_all.php?week=" . date( 'Y-m-d', strtotime( $week . " +1 week" ) ) ) . " ";
}

print gui_hyperlink( "שבוע קודם", "get_all.php?week=" . date( 'Y-m-d', strtotime( $week . " -1 week" ) ) );

// Build the query
// $sql   = "SELECT id, date, amount, delivery_fee FROM im_business_info WHERE ";
$sql = "SELECT id, part_id, date, week, amount, ref +0, delivery_fee FROM im_business_info WHERE ";

$query   = " is_active = 1 ";
$new_url = "get_all.php?";
foreach ( $_GET as $key => $value ) {
	if ( $key <> "sort" ) {
		$query   .= " and " . $key . " = '" . $value . "'";
		$new_url .= $key . "=" . $value . "&";
	}
}
$new_url = rtrim( $new_url, "&" );

// print "new: " . $new_url . "<br/>";
if ( isset( $_GET["sort"] ) ) {
	$query .= " order by " . $_GET["sort"];
}

$sql .= $query;

// print $sql;

print "<table dir='rtl' id='table_business'>";
print "<tr>";
print_col( "#" );
$key = 1;
print_col( "מזהה", $key ++ );
print_col( "לקוח/ספק", $key ++ );
print_col( "תאריך", $key ++ );
print_col( "שבוע", $key ++ );
print_col( "סכום", $key ++ );
print_col( "תעודת משלוח", $key ++ );
print_col( "דמי משלוח", $key ++ );
print "</tr>";

//$sql = "SELECT id" .
//    " FROM im_business_info ";

$seq                = 1;
$total_amount       = 0;
$total_delivery_fee = 0;

// print $sql;
$result = sql_query( $sql );
//while ( $row = mysqli_fetch_row( $result ) ) {
while ( $row = mysqli_fetch_assoc( $result ) ) {
	$total_amount       += $row["amount"];
	$total_delivery_fee += $row["delivery_fee"];
	print_business( $row["id"], true, $seq );
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

function print_col( $hdr, $key = null ) {
	global $new_url;
	if ( $key ) {
		$url = $new_url . "&sort=" . $key;
		print "<td>" . gui_hyperlink( $hdr, $url ) . "</td>";
	} else {
		print "<td>" . $hdr . "</td>";
	}
}

function get_name( $id ) {
	$sql = "SELECT display_name FROM wp_users WHERE id = " . $id .
	       " UNION SELECT supplier_name FROM im_suppliers WHERE id = " . $id;

	return sql_query_single_scalar( $sql );
}

// From here created by coder.old.php
function delete_business( $id ) {
	$sql = "delete from im_business_info where id = $id";
	sql_query( $sql );
}

function print_business( $id, $horizontal, $seq ) {
	$sql = "select id, part_id, date, week, amount, ref, delivery_fee from im_business_info where id = $id";
	$row = sql_query_single( $sql );
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

<h2>מחק שורות שמוצגות</h2>

<tr>
    <button id="btn_delete_business" onclick="delete_item()">-</button>
</tr>

<br>
<h1 align="center">הוסף תעודת משלוח</h1>

<br/>
<Table align="center">
    <tr>
        <td>
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
    <tr>
        <td>
            <button id="btn_add_business" onclick="add_item()">הוספת תעודת ספק</button>
        </td>
    </tr>

</table>
        </td>
        <td>
			<?php

			print delivery::GuiCreateNewNoOrder();
			?>
        </td>
    </tr>

</Table>


</body>
</html>
