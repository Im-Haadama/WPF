<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 15/05/19
 * Time: 09:29
 */

require_once( '../r-shop_manager.php' );

$operation = get_param("operation", true);

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


}

