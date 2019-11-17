<?php

function handle_supplier_operation($operation)
{
	$allowed_tables = array("im_suppliers");
	$result = "";
	switch ( $operation ) {
		case "update_type":
			$business_id = get_param( "id" );
			$t           = get_param( "type" );

			if (sql_query( "update im_business_info set document_type = " . $t .
			           " where id = " . $business_id )) print "done";
			return;

		case "update":
			$id  = get_param( "id", true );
			$sql = "update im_suppliers set ";
			foreach ( $_GET as $key => $value ) {
				//			print $key . " " . $value . "<br/>";
				if ( $key === "id" or $key === "operation" ) {
					continue;
				}
				$sql .= $key . '=' . quote_text( $value );
			}
			$sql .= " where id = " . $id;
			// print $sql;

			sql_query( $sql );
			print "done";
			return;
		case "save_new":
			$table_name = get_param("table_name", true);
			if (! in_array($table_name, $allowed_tables))
				die ("invalid table operation");
			$result = data_save_new($table_name);
			if ($result > 0) print "done.table=" . $table_name . '&new=' . $result;
			return;

	}
	switch ( $operation ) {
		case "show_supplier":
			$args = [];
			$id = get_param("id", true);
			$result = GemElement("im_suppliers", $id, $args);
			break;
		case "add":
			$args = array();
			$result = GemAddRow( "im_suppliers", "add", $args );
			break;

		default:
			print "$operation ". im_translate("not handled");
	}
	$args = [];
	$args["script_files"] = array("/niver/gui/client_tools.js", "/niver/data/data.js");
	print HeaderText($args);
	print $result;

}