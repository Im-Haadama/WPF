<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 24/02/17
 * Time: 09:13
 */
require_once( '../tools.php' );
require_once( 'pricelist.php' );


// TODO: incremental doesn't handle deletion.
// TODO: for now deleting will be done in full sync (once a day).
function pricelist_remote_site_process( $supplier_id, &$results, $inc = false ) {
	$debug = false;

	$PL = new PriceList( $supplier_id );

	if ( $inc ) {
		print gui_header( 1, "עדכון חלקי" );
		print gui_list( " רק הפריטים שהוספו או עודכן מחיר יעודכנו" );
		print gui_list( "פריטים שנמחקו לא יורדו" );
	} else {
		$PL->ChangeStatus( 2 );
	}

	// $results = array();

	$sql     = "SELECT site_id FROM im_suppliers WHERE id=" . $supplier_id;
	$site_id = sql_query_single_scalar( $sql );

	if ( ! ( $site_id > 0 ) ) {
		sql_error( $sql );
		die( 1 );
	}

	$remote = get_site_tools_url( $site_id ) . "/catalog/get-as-pricelist.php";
	if ( $inc ) {
		$remote .= "?incremental&site_id=" . MultiSite::LocalSiteID();
	}

	print gui_header( 2, "מבקש נתונים" );
	flush();
	$html = file_get_html( $remote );
	print gui_header( 2, "התקבלו נתונים" );
	flush();
	$item_count = 0;
	$var_count  = 0;

	$lines = $html->find( 'tr' );

	for ( $i = 0; $i < count( $lines ); $i ++ ) {
		if ( $i % 1000 == 0 ) {
			print $i;
			flush();
		}
		$row     = $lines[ $i ];
		$type    = $row->find( 'td', 0 )->plaintext; // Prod or Var
		$prod_id = $row->find( 'td', 1 )->plaintext;
		// print "prod id " . $prod_id;
		$name = $row->find( 'td', 2 )->plaintext;
		// $date = $row->find('td', 3)->plaintext;
		$price   = $row->find( 'td', 4 )->plaintext;
		$var_num = $row->find( 'td', 5 )->plaintext;
		if ( $debug ) {
			print "<p dir=ltr>" . $name;
		}

		if ( $var_num == 0 ) { // Regular product
			if ( $debug ) {
				print " regular ";
			}
			$result = handle_line( $price, $name, $prod_id, $PL, false, $id, 0 );
			switch ( $result ) {
				case UpdateResult::UsageError:
				case UpdateResult::SQLError:
					print "Unexpected error 1";
					die( 1 );
				default:
					$results[ $result ][] = array( $name, $price, $id );
			}
			$item_count ++;
		} else { // With variations
			if ( $debug ) {
				print " with variations ";
			}
			$parent_id = 0;
			// Insert or update the parent. Get back the parent id
			$result = handle_line( $price, $name, $prod_id, $PL, true, $parent_id, 0 );
			if ( $parent_id == 0 ) {
				print "Didn't get parent id for product " . $name . "<br/>";
				die ( 2 );
			}
			switch ( $result ) {
				case UpdateResult::UsageError:
				case UpdateResult::SQLError:
					print "Unexpected error 2";
					die( 1 );
				default:
					$results[ $result ][] = array( $name, $price, $parent_id );
			}
			for ( $j = 1; $j <= $var_num; $j ++ ) {
				$row = $lines[ $i + $j ];
				// print $row;
				$var_id = $row->find( 'td', 1 )->plaintext;
				if ( $debug ) {
					print " handling var " . $var_id;
				}

				//print "prod id " . $prod_id;
				$name = $row->find( 'td', 2 )->plaintext;
				// $date = $row->find('td', 3)->plaintext;
				$price = $row->find( 'td', 4 )->plaintext;

				if ( $price > 0 ) {
					$result = handle_line( $price, $name, $var_id, $PL, false, $var_id, $parent_id );
					switch ( $result ) {
						case UpdateResult::UsageError:
						case UpdateResult::SQLError:
							print "Unexpected error 2";
							die( 1 );
						default:
							$results[ $result ][] = array( $name, $price, $var_id );
					}
					$var_count ++;
				} else {
					print "אין מחיר לוריאציה " . $name . "<br/>";
				}
			}
			// Move to next product
			$i += $var_num;
		}
		if ( $debug ) {
			print "done </p>";
		}
	}

	echo $item_count . " מוצרים נקראו!" . "<br/>";
	echo $var_count . " וריאציות נקראו!" . "<br/>";
	if ( ! $inc and ( $item_count > 4 ) ) {
		$results[ UpdateResult::DeletePrice ] = $PL->RemoveLines( 2 );
//        var_dump($results[UpdateResult::DeletePrice]);
	}
	if ( ! $inc and ( $item_count <= 4 ) ) {
		print "לא התקבלו מספיק שורות!" . "<br/>";
	}

	print_items( "פריטים חדשים", $results[ UpdateResult::NewPrice ] );
	print_items( "פריטים התייקרו", $results[ UpdateResult::UpPrice ] );
	print_items( "פריטים הוזלו", $results[ UpdateResult::DownPrice ] );
	print_items( "פריטים יצאו", $results[ UpdateResult::DeletePrice ] );
}

function pricelist_process_name( $filename, $supplier_name, $add ) {
	global $conn;

	$sql = "select id from im_suppliers where supplier_name = '$supplier_name' or eng_name = '$supplier_name'";
	// print $sql;
	$sid = sql_query_single_scalar( $sql );
	if ( $sid > 0 ) {
		print "supplier id = " . $sid . "<br/>";

		pricelist_process( $filename, $sid, $add );
	} else {
		print "Cannot find supplier $supplier_name<br/>";
		die( 1 );
	}
}

function pricelist_process( $filename, $supplier_id, $add ) {
	$debug = true;

	// The file is on server. Lets read it
	$file = fopen( $filename, "r" );
	if ( ! $file ) {
		return false;
	}
//    print "reading header...<br/>";

	$item_code_idx       = Array();
	$name_idx            = Array();
	$price_idx           = Array();
	$detail_idx          = Array();
	$parse_header_result = false;
	$inventory_idx       = 0;
	for ( $i = 0; ! $parse_header_result and ( $i < 4 ); $i ++ ) {
		$parse_header_result = parse_header( $file, $item_code_idx, $name_idx, $price_idx, $inventory_idx, $detail_idx );
	}

	if ( ! $parse_header_result ) {
		print "Missing headers:<br/>";
		die( " name " . count( $name_idx ) . " price " . count( $price_idx ) );
	}

	$line_number = 0;

	my_log( "supplier_id = " . $supplier_id );

	$PL = new PriceList( $supplier_id );

	if ( ! $add ) {
		$PL->ChangeStatus( 2 );
	} // Change all supplier's lines to status 2 = old

	$results = array();

	$item_count = 0;
	while ( ( $data = fgetcsv( $file ) ) !== false ) {
		if ( $data ) {
			for ( $col = 0; $col < count( $price_idx ); $col ++ ) {
				$item_code = 10;
				$price     = trim( $data[ $price_idx[ $col ] ], '₪ ' );
				$name      = $data[ $name_idx[ $col ] ];
				$detail    = $data[ $detail_idx[ $col ] ];
				$detail    = rtrim( $detail, "!" );
				if ( $detail == "מוגבל מאוד" or $detail == "מוגבל מאד" ) {
					continue;
				}
				// print $name . " " . $detail . "<br/>";
				if ( in_array( $detail, array( "חדש", "מוגבל" ) ) ) {
					$detail = "";
				}
				// if ($debug) print $name . "<br/>";
				if ( $price > 0 and $inventory_idx and ! is_numeric( $data[ $inventory_idx ] ) and $data[ $inventory_idx ] != "יש" ) {
					print $data[ $name_idx[ $col ] ] . " חסר" . "<br/>";
					continue;
				}
				if ( $item_code_idx[ $col ] != - 1 ) {
					$item_code = $data[ $item_code_idx[ $col ] ];
				}
				if ( $price > 0 ) {
					$result = handle_line( $price, $name . " " . $detail, $item_code, $PL, false, $id );
					switch ( $result ) {
						case UpdateResult::UsageError:
						case UpdateResult::SQLError:
							print "Unexpected error 3";
							print $price . " " . $name . " " . $item_code . "<br/>";
							die( 1 );
						default:
							$results[ $result ][] = array( $name, $price, $id );
					}
					$item_count ++;
				}
			}
			$line_number ++;
		}
	}
	echo $item_count . " פריטים נקראו!" . "<br/>";
	if ( ! $add and ( $item_count > 4 ) ) {
		$results[ UpdateResult::DeletePrice ] = $PL->RemoveLines( 2 );
//        var_dump($results[UpdateResult::DeletePrice]);
	}
	if ( $item_count <= 4 ) {
		print "לא התקבלו מספיק שורות!" . "<br/>";
	}

	print_items( "פריטים חדשים", $results[ UpdateResult::NewPrice ] );
	print_items( "פריטים התייקרו", $results[ UpdateResult::UpPrice ] );
	print_items( "פריטים הוזלו", $results[ UpdateResult::DownPrice ] );
	print_items( "פריטים יצאו", $results[ UpdateResult::DeletePrice ] );
	print_items( "פריטים מוסתרים", $results[ UpdateResult::NotUsed ] );

	return true;
}

function parse_header( $file, &$item_code_idx, &$name_idx, &$price_idx, &$inventory_idx, &$detail_idx ) {
	$header = fgetcsv( $file );

//    print "header size: " . count($header) ."<br/>";

	for ( $i = 0; $i < count( $header ); $i ++ ) {
		$key = trim( $header[ $i ] );
		// print "key=" . $key . " ";

		// print $key . " " . strlen($key) . " " . $i . "<br/>";
		switch ( $key ) {
			case 'פריט':
			case 'קוד פריט':
				array_push( $item_code_idx, $i );
				break;
			case 'הירק':
			case 'סוג קמח':
			case 'שם פריט':
			case 'שם':
			case 'תאור פריט':
			case 'תיאור פריט':
			case 'תיאור':
			case 'מוצר':
			case 'שם המוצר':
				array_push( $name_idx, $i );
				break;
			case 'פירוט המוצר':
				array_push( $detail_idx, $i );
				break;
			case 'מחיר':
			case 'מחיר נטו':
			case 'מחיר לק"ג':
			case 'סיטונאות':
				array_push( $price_idx, $i );
				break;
			case 'מלאי (ביחידות)':
				$inventory_idx = $i;
				break;
		}
	}

	//if ($name_idx == -1 or $price_idx == -1) die("item code " . $item_code_idx . "name " . $name_idx  . "price " . $price_idx );
	if ( count( $name_idx ) == 0 or count( $price_idx ) == 0 ) {
		print "name_idx count: " . count( $name_idx ) . "<br/>";
		print "price_idx count: " . count( $price_idx ) . "<br/>";

		return false;
		// print "Missing headers:<br/>";
		// die(" name " . count($name_idx)  . " price " . count($price_idx));
	}

	return true;
}

function print_items( $title, $list ) {
	if ( count( $list ) > 0 ) {
		print $title . ": <br/>";

		foreach ( $list as $item ) {
			print $item[0] . ", " . $item [1] . "<br/>";
		}
	} else {
		print "אין " . $title . "<br/>";
	}
}

// Variation are saved with reference to parent.
function handle_line( $price, $name, $item_code, $PL, $has_variations, &$id, $parent_id = null ) {
	$name = pricelist_strip_product_name( $name );
	$id   = 0;
	// Check if we have a price.
	// Product with variations doesn't need a price
	if ( $price > 0 or $has_variations ) {
		return $PL->AddOrUpdate( $price, $name, $item_code, $id, $parent_id );
	}
//    else
//        print "price: " . $price . "<br/>";

	return UpdateResult::UsageError;
}

