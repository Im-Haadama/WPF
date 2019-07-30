<?php

if ( ! defined( "ROOT_DIR" ) ) {
	define( 'ROOT_DIR', dirname( dirname( dirname( __FILE__ ) ) ) );
}

require_once(ROOT_DIR . "/tools/im_tools.php");

$operation = get_param("operation", true);

// http://fruity.co.il/tools/admin/data.php?table_name=im_business_info&operation=update&id=2560&ref=22
$table_name = get_param("table_name", true);
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
			$sql    = "UPDATE $table_name set ";
			$first  = true;
			$values = array();
			$row_id = intval(get_param("id", true));
			foreach ( $_GET as $key => $value ) {
				//print $key . "<br/>";
				if (in_array($key, $ignore_list))
					continue;
				if ( ! $first ) {
					$sql    .= ", ";
				}
				$sql    .= $key . '=?';
				$values[] = strlen($value) ? $value : "'NULL'";
				$first  = false;
			}
			$sql .= " where id=$row_id";
//			print $sql;
//			print $sql;
			$stmt = $conn->prepare($sql);
			if (! $stmt) {
				die ($conn->error);
			}

			// Bind
			$types = "";
			$values = array();
			foreach ( $_GET as $key => $value ) {
				//print $key . "<br/>";
				if (in_array($key, $ignore_list))
					continue;
				$type = sql_type($table_name, $key);
				switch(substr($type, 0, 3))
				{
					case 'bit':
					case "int":
						$types .= "i";
						$value = strlen($value) ? $value : null;
						break;
					case "var":
						$types .= "s";
						break;
					case "dat":
						$types .= "s";
						break;
					case "dou":
						$types .= "d";
						break;
					default:
						print $type . " not handled";
						die(1);
				}
				array_push($values, $value);
				// print "$type $value<br/>";
			}
//			print "types=$types<br/>";
//			var_dump($values); print "<br/>";

			if (! $stmt->bind_param($types, ...$values))
			{
				die("bind error" . sql_error($sql));
			};

			if (!$stmt->execute()) {
				print "Update failed: (" . $stmt->errno . ") " . $stmt->error . " " . $sql;
				die(2);
			}
			print "done";
			break;

		default:
			print "no operation<br/>";
			die( 2 );
	}
