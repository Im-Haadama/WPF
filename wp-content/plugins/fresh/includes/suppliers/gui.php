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

	$sql = 'SELECT id, supplier_name, site_id FROM im_suppliers WHERE active = 1 ORDER BY 2';

	// Get line options
	$found  = false;
	$result = sql_query( $sql );
	while ( $row1 = mysqli_fetch_row( $result ) ) {
		$found = true;
		print "<option value = \"" . $row1[0] . "\" ";
		$site_id = $row1[2];
		if ( is_numeric( $site_id ) ) {
			print " data-site-id=\"" . $site_id . "\"";
			print " data-tools-url-id=\"" . get_site_tools_url( $site_id ) . "\"";
		}
		print "> " . $row1[1] . "</option>";
	}
	if ( ! $found ) {
		print "יש להוסיף ספקים פעילים<br/>";
	}

	print "</select>";
}

// function gui_select_supplier( $id = "supplier_select", $value = null, $events = null ) {

function gui_select_supplier( $id = "supplier_select", $value = null, $args = null )
{
	$events = null;
	$events = GetArg($args, "events", null);
	$class = GetArg($args, "class", null);
	$edit = GetArg($args, "edit", true);

	if (! $edit){
		if ($value) return get_supplier_name($value);
		return "supplier not selected";
	}

	return gui_select_table( $id, "im_suppliers", $value, $events, "", "supplier_name",
		" where active = 1", true, false, "supplier_name", $class );
//		$sql_where );
}

// Selector ($id, $value, $args)
