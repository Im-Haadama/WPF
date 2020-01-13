<?php

function handle_supplier_operation($operation, $args = [])
{
	$allowed_tables = array("im_suppliers", "im_business_info");
	$result = "";
	if (substr($operation, 0, 4) == "show") {
		if (substr($operation, 5,3) == "add") {
			$table_name = substr($operation, 9);
			print GemAddRow($table_name, "Add", $args);
			return;
		}
	}

	switch ( $operation ) {
		case "update_type":
			$business_id = GetParam( "id" );
			$t           = GetParam( "type" );

			if (sql_query( "update im_business_info set document_type = " . $t .
			           " where id = " . $business_id )) print "done";
			return;

		case "update":
			$id  = GetParam( "id", true );
			$sql = "update im_suppliers set ";
			foreach ( $_GET as $key => $value ) {
				//			print $key . " " . $value . "<br/>";
				if ( $key === "id" or $key === "operation" ) {
					continue;
				}
				$sql .= $key . '=' . QuoteText( $value );
			}
			$sql .= " where id = " . $id;
			// print $sql;

			sql_query( $sql );
			print "done";
			return;
		case "save_new":
			$table_name = GetParam("table_name", true);
			if (! in_array($table_name, $allowed_tables))
				die (__FUNCTION__ . ": invalid table operation");
			$result = data_save_new($table_name);
			if ($result > 0) print "done.table=" . $table_name . '&new=' . $result;
			return;

		case "add_invoice":
			$_GET["part_id"] = $_GET["supplier_id"];
			unset ($_GET["supplier_id"]);
			data_save_new('im_business_info');
			return;

	}
	switch ( $operation ) {
		case "show_supplier":
			$id = GetParam("id", true);
			$result = GemElement("im_suppliers", $id, $args);
			break;
		case "add":
			$args = array();
			$result = GemAddRow( "im_suppliers", "add", $args );
			break;

		case "show_balance":

			break;

		default:
			print "$operation ". im_translate("not handled");
	}
	$args = [];
//	$args["script_files"] = array("/core/gui/client_tools.js", "/core/data/data.js");
//	print HeaderText($args);
	print $result;

}