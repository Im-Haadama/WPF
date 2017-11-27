<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 18/04/17
 * Time: 22:53
 */
require_once( '../r-shop_manager.php' );
require_once( '../header.php' );
?>
<script>
	<?php
	$filename = __DIR__ . "/../client_tools.js";
	$handle = fopen( $filename, "r" );
	$contents = fread( $handle, filesize( $filename ) );
	print $contents;
	?>

    function save_new() {
        var request_url = "supplier_post.php?operation=insert";
        request_url = request_url + "&id=" + get_value(document.getElementById("id"));
        var _supplier_name = encodeURI(get_value(document.getElementById("supplier_name")));
        if (document.getElementById("chk_supplier_name").checked)
            request_url = request_url + "&supplier_name=" + _supplier_name;
        var _supplier_contact_name = encodeURI(get_value(document.getElementById("supplier_contact_name")));
        if (document.getElementById("chk_supplier_contact_name").checked)
            request_url = request_url + "&supplier_contact_name=" + _supplier_contact_name;
        var _supplier_contact_phone = encodeURI(get_value(document.getElementById("supplier_contact_phone")));
        if (document.getElementById("chk_supplier_contact_phone").checked)
            request_url = request_url + "&supplier_contact_phone=" + _supplier_contact_phone;
        var _factor = encodeURI(get_value(document.getElementById("factor")));
        if (document.getElementById("chk_factor").checked)
            request_url = request_url + "&factor=" + _factor;
        var _site_id = encodeURI(get_value(document.getElementById("site_id")));
        if (document.getElementById("chk_site_id").checked)
            request_url = request_url + "&site_id=" + _site_id;
        request = new XMLHttpRequest();
        request.onreadystatechange = function () {
            if (request.readyState == 4 && request.status == 200) {
                window.location = window.location;
            }
        }
        request.open("GET", request_url, true);
        request.send();
    }

</script>
<?php
$sql    = "SELECT id FROM im_suppliers";
$result = mysqli_query( $conn, $sql );
$seq    = 0;
print "<table>";
while ( $row = mysqli_fetch_row( $result ) ) {
	$seq ++;

	print_suppliers( $row[0], true, $seq );
}
print print_entry( 0 );
function delete_suppliers( $id ) {
	$sql = "delete from im_suppliers where id = $id";
	sql_query( $sql );
}

function print_suppliers( $id, $horizontal, $seq ) {
	$sql = "select id, supplier_name, supplier_contact_name, supplier_contact_phone, factor, site_id from im_suppliers where id = $id";
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
		print "supplier_name";
		print "</td>";
	}
	print "<td>";
	print "<a href=\"get_all.php?supplier_name=" . urlencode( $row[1] );
	print "\">";
	print $row[1];
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
		print "supplier_contact_name";
		print "</td>";
	}
	print "<td>";
	print "<a href=\"get_all.php?supplier_contact_name=" . urlencode( $row[2] );
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
		print "supplier_contact_phone";
		print "</td>";
	}
	print "<td>";
	print "<a href=\"get_all.php?supplier_contact_phone=" . urlencode( $row[3] );
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
		print "factor";
		print "</td>";
	}
	print "<td>";
	print "<a href=\"get_all.php?factor=" . urlencode( $row[4] );
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
		print "site_id";
		print "</td>";
	}
	print "<td>";
	print "<a href=\"get_all.php?site_id=" . urlencode( $row[5] );
	print "\">";
	print $row[5];
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

function print_entry( $id ) {
	print "<table>";
	global $conn;
	if ( $id > 0 ) {
		$sql    = "SELECT * FROM im_suppliers WHERE id = " . $id;
		$result = $conn->query( $sql );
		$values = mysqli_fetch_assoc( $result );
	}
	print "<tr>";
// id
	print "<td>";
	print "</td>";
	print "<td>";
	print "מזהה";
	print "</td>";
	print "<td>";
	print "<label id=\"id\">" . $values["id"];
	print "</label>";
	print "</td>";
	print "</tr>";
	print "<tr>";
// supplier_name
	print "<td>";
	print "<input id=\"chk_supplier_name\" type = \"checkbox\">";
	print "</td>";
	print "<td>";
	print "supplier_name";
	print "</td>";
	print "<td>";
	print "<input id=\"supplier_name\"";
	if ( $id > 0 ) {
		print " value=\"" . $values["supplier_name"] . "\"";
	}
	print "onchange=\"changed(this)\">";
	print "</td>";
	print "</tr>";
	print "<tr>";
// supplier_contact_name
	print "<td>";
	print "<input id=\"chk_supplier_contact_name\" type = \"checkbox\">";
	print "</td>";
	print "<td>";
	print "supplier_contact_name";
	print "</td>";
	print "<td>";
	print "<input id=\"supplier_contact_name\"";
	if ( $id > 0 ) {
		print " value=\"" . $values["supplier_contact_name"] . "\"";
	}
	print "onchange=\"changed(this)\">";
	print "</td>";
	print "</tr>";
	print "<tr>";
// supplier_contact_phone
	print "<td>";
	print "<input id=\"chk_supplier_contact_phone\" type = \"checkbox\">";
	print "</td>";
	print "<td>";
	print "supplier_contact_phone";
	print "</td>";
	print "<td>";
	print "<input id=\"supplier_contact_phone\"";
	if ( $id > 0 ) {
		print " value=\"" . $values["supplier_contact_phone"] . "\"";
	}
	print "onchange=\"changed(this)\">";
	print "</td>";
	print "</tr>";
	print "<tr>";
// factor
	print "<td>";
	print "<input id=\"chk_factor\" type = \"checkbox\">";
	print "</td>";
	print "<td>";
	print "factor";
	print "</td>";
	print "<td>";
	print "<input id=\"factor\"";
	if ( $id > 0 ) {
		print " value=\"" . $values["factor"] . "\"";
	}
	print "onchange=\"changed(this)\">";
	print "</td>";
	print "</tr>";
	print "<tr>";
// site_id
	print "<td>";
	print "<input id=\"chk_site_id\" type = \"checkbox\">";
	print "</td>";
	print "<td>";
	print "site_id";
	print "</td>";
	print "<td>";
	print "<input id=\"site_id\"";
	if ( $id > 0 ) {
		print " value=\"" . $values["site_id"] . "\"";
	}
	print "onchange=\"changed(this)\">";
	print "</td>";
	print "</tr>";
	print "</table>";
}

?>
<button id="btn_supplier_save" onclick="save_new()">שמור</button>
