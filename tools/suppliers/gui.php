<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 18/08/17
 * Time: 05:00
 */

function print_select_supplier( $id, $source ) {
	print "<select id=\"" . $id . "\"";
	if ( $source ) {
		print "onclick=\"change_supplier();\"";
	}
	print ">";

	$sql1 = 'SELECT id, supplier_name, site_id FROM im_suppliers ORDER BY 2';

	// Get line options
	$export1 = mysql_query( $sql1 );
	while ( $row1 = mysql_fetch_row( $export1 ) ) {
		print "<option value = \"" . $row1[0] . "\" ";
		$site_id = $row1[2];
		if ( is_numeric( $site_id ) ) {
			print " data-site-id=\"" . $site_id . "\"";
			print " data-tools-url-id=\"" . get_site_tools_url( $site_id ) . "\"";
		}
		print "> " . $row1[1] . "</option>";
	}

	print "</select>";
}
