<?php

if ( ! defined( "ROOT_DIR" ) ) {
	define( 'ROOT_DIR', dirname( dirname( dirname( __FILE__ ) ) ) );
}

require_once(ROOT_DIR . "/tools/im_tools.php");

$operation = get_param("operation", true);
$table_name = "im_business_info";

// http://fruity.co.il/tools/admin/data.php?table_name=im_business_info&operation=update&id=2560&ref=22
// $table_name = get_param("table_name", true);
error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

// TODO: Check permission to table

$ignore_list = array("operation", "table_name", "id");

if ( $operation )
	switch ( $operation ) {
		case "new":
//        print "adding...";
			$sql    = "INSERT INTO $table_name (";
			$values = "values (";
			$first  = true;
			foreach ( $_GET as $key => $value ) {
				//print $key . "<br/>";
				if (in_array($key, $ignore_list))
					continue;
				if ( ! $first ) {
					$sql    .= ", ";
					$values .= ", ";
				}
				$sql    .= $key;
				$values .= "\"" . $value . "\"";
				$first  = false;
			}
			$sql    .= ") ";
			$values .= ") ";
			$sql    .= $values;

			// print $sql;
			// TODO: use prepare https://stackoverflow.com/questions/60174/how-can-i-prevent-sql-injection-in-php

			if ( ! $conn->query( $sql ) ) {
				print "Sql: " . $sql . "<br>" . mysqli_error( $conn );
			}
			print "done";
			break;

		case "update":
			// for debug:
			$sql    = "UPDATE $table_name set ";
			$first  = true;
			$values = array();
			$types = "";
			$row_id = intval(get_param("id", true));
			foreach ( $_GET as $key => $value ) {
				//print $key . "<br/>";
				if (in_array($key, $ignore_list))
					continue;
				if ( ! $first ) {
					$sql    .= ", ";
				}
				$sql    .= $key . '=?';
				$values[] = $value;
				$types .= 's';
				$first  = false;
			}
			$sql .= " where id=$row_id";
//			print $sql;
			$stmt = $conn->prepare($sql);
			if (! $stmt) {
				die ($conn->error);
			}
			$stmt->bind_param($types, ...$values);
			if (!$stmt->execute()) {
				print "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
				die(2);
			}
			print "done";
			break;

		default:
			print "no operation<br/>";
			die( 2 );
	}
