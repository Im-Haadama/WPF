<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 18/04/17
 * Time: 23:56
 */
require_once( '../tools_wp_login.php' );

$operation = $_GET["operation"];

if ( $operation )
	switch ( $operation ) {
		case "insert":
//        print "adding...";
			$sql    = "INSERT INTO im_suppliers (";
			$values = "values (";
			$first  = true;
			foreach ( $_GET as $key => $value ) {
				//print $key . "<br/>";
				if ( $key == "operation" ) {
					continue;
				}
				if ( $key == "id" ) {
					continue;
				}
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

			print $sql;
			if ( ! $conn->query( $sql ) ) {
				print "Sql: " . $sql . "<br>" . mysqli_error( $conn );
			}
			break;


		default:
			print "now operation<br/>";
			die( 2 );
	}
