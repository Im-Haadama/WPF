<?php
require_once( '../r-shop_manager.php' );
require_once( '../header.php' );
require_once( ROOT_DIR . '/niver/gui/inputs.php' );
print header_text( false, false );
?>
    <script>
        /**
         * Created by agla on 07/05/16.
         */

        function changed(field) {
            var subject = field.id;
            document.getElementById("chk_" + subject).checked = true;
        }

        function get_value(element) {
            if (element === null) {
                return 0;
            }
            switch (element.tagName) {
                case "INPUT":

                    if (element.type == "checkbox") {
                        if (element.checked) return 1;
                        return 0;
                        // if (element.checke == "on") return 1;
                        // return 0;
                    }
                    return element.value;

                case "TEXTAREA":
                    // alert (element.value);
                    return element.value;
                case "SELECT":
                    var idx = element.selectedIndex;
                    return element.options[idx].value;
                case "LABEL":
                    return element.textContent;
            }
            return element.nodeValue;
        }

        // Limited use. The internal variable is not scoped correctly.
        // Use only if one call at a time
        function execute_url(url, finish_action) {
            xmlhttp3 = new XMLHttpRequest();
            xmlhttp3.onreadystatechange = function () {
                // Wait to get query result
                if (xmlhttp3.readyState == 4 && xmlhttp3.status == 200)  // Request finished
                {
                    if (finish_action)
                        finish_action(xmlhttp3);
                }
            }
            xmlhttp3.open("GET", url, true);
            xmlhttp3.send();
        }
        function delete_item() {
            var t = document.getElementById("table_supplies");
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
            var request = "supplies.php?operation=delete_items&ids=" + ids.join();
            // alert (request);
            xmlhttp.open("GET", request, true);
            xmlhttp.send();
        }</script></header>
    <body>
<?php
$sql = "SELECT id, status, date, supplier, text, business_id FROM im_supplies WHERE  1 ";

foreach ( $_GET as $key => $value ) {
	$sql .= " and " . $key . " = '" . $value . "'";
}

$sql = $sql . "order by 3  desc";
print "<table dir='rtl' id='table_supplies'>";
print "<tr>";
print gui_cell( "id" );
print gui_cell( "status" );
print gui_cell( "date" );
print gui_cell( "supplier" );
print gui_cell( "business_id" );

print "</tr>";
$seq    = 1;
$result = mysqli_query( $conn, $sql );
while ( $row = mysqli_fetch_row( $result ) ) {
	print_supplies_table( $url, $row[0], true );
}
function print_supplies( $url, $id, $horizontal ) {
	$sql = "select id, status, date, supplier, text, business_id from im_supplies where id = $id";
	$row = sql_query_single( $sql );
	if ( ! $horizontal ) {
		print "<table>";
	}
	if ( $horizontal ) {
		print "<tr>";
	}
	if ( ! $horizontal ) {
		print "<tr>";
	}
	if ( ! $horizontal ) {
		print "<td>";
		print "id";
		print "</td>";
	}
	if ( ! $horizontal or 1 ) {
		print "<td>";
		$value = $row[0];
		print "<a href=\"" . $url . "?id=" . urlencode( $row[0] ) . "\">";
		print $value;
		print "</a>";
		print "</td>";
	}
	if ( ! $horizontal ) {
		print "</tr>";
	}
	if ( ! $horizontal ) {
		print "<tr>";
	}
	if ( ! $horizontal ) {
		print "<td>";
		print "status";
		print "</td>";
	}
	if ( ! $horizontal or 1 ) {
		print "<td>";
		$value = $row[1];
		print "<a href=\"" . $url . "?status=" . urlencode( $row[1] ) . "\">";
		print $value;
		print "</a>";
		print "</td>";
	}
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
	if ( ! $horizontal or 1 ) {
		print "<td>";
		$value = get_week( $row[2] );
		print "<a href=\"" . $url . "?date=" . urlencode( $row[2] ) . "\">";
		print $value;
		print "</a>";
		print "</td>";
	}
	if ( ! $horizontal ) {
		print "</tr>";
	}
	if ( ! $horizontal ) {
		print "<tr>";
	}
	if ( ! $horizontal ) {
		print "<td>";
		print "supplier";
		print "</td>";
	}
	if ( ! $horizontal or 1 ) {
		print "<td>";
		$value = get_supplier_name( $row[3] );
		print "<a href=\"" . $url . "?supplier=" . urlencode( $row[3] ) . "\">";
		print $value;
		print "</a>";
		print "</td>";
	}
	if ( ! $horizontal ) {
		print "</tr>";
	}
	if ( ! $horizontal ) {
		print "<tr>";
	}
	if ( ! $horizontal ) {
		print "<td>";
		print "text";
		print "</td>";
	}
	if ( ! $horizontal or 0 ) {
		print "<td>";
		$value = $row[4];
		print "<a href=\"" . $url . "?text=" . urlencode( $row[4] ) . "\">";
		print $value;
		print "</a>";
		print "</td>";
	}
	if ( ! $horizontal ) {
		print "</tr>";
	}
	if ( ! $horizontal ) {
		print "<tr>";
	}
	if ( ! $horizontal ) {
		print "<td>";
		print "business_id";
		print "</td>";
	}
	if ( ! $horizontal or 1 ) {
		print "<td>";
		$value = $row[5];
		print "<a href=\"" . $url . "?business_id=" . urlencode( $row[5] ) . "\">";
		print $value;
		print "</a>";
		print "</td>";
	}
	if ( ! $horizontal ) {
		print "</tr>";
	}
	if ( $horizontal ) {
		print "</tr>";
	}
}
