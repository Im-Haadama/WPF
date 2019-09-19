<?php

function handle_supplier_operation($operation)
{
	switch ( $operation ) {
		case "update_type":
			$business_id = get_param("id");
			$t = get_param("type");

			sql_query("update im_business_info set document_type = " .$t .
			          " where id = " . $business_id);
			break;

		case "update":
			$id = get_param("id", true);
			$sql = "update im_suppliers set ";
			foreach ($_GET as $key => $value)
			{
	//			print $key . " " . $value . "<br/>";
				if ($key === "id" or $key === "operation") continue;
				$sql .= $key . '=' . quote_text($value);
			}
			$sql .= " where id = " . $id;
			// print $sql;

			sql_query($sql);
			print "done";
			break;

		case "add":
			$args = array();
			print GemAddRow("im_suppliers", "add", $args);
			break;

		default:
			print "$operation ". im_translate("not handled");
	}

}