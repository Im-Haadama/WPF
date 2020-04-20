<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 24/02/17
 * Time: 09:13
 */
if ( ! defined( "TOOLS_DIR" ) ) {
	define( 'TOOLS_DIR', dirname( dirname( __FILE__ ) ) );
}

//require_once( TOOLS_DIR . '/r-shop_manager.php' );
require_once( TOOLS_DIR . '/data/header.php' );

require_once( 'pricelist.php' );

// TODO: incremental doesn't handle deletion.
// TODO: for now deleting will be done in full sync (once a day).
function pricelist_remote_site_process( $supplier_id, &$results, $inc = false ) {
	$debug_product = 938;

	$debug = false;

	$PL = new Fresh_PriceList( $supplier_id );

	if ( $inc ) {
		print Core_Html::gui_header( 1, "עדכון חלקי" );
		print gui_list( " רק הפריטים שהוספו או עודכן מחיר יעודכנו" );
		print gui_list( "פריטים שנמחקו לא יורדו" );
	} else {
		print Core_Html::gui_header( 1, "משנה פריטים למצב המתנה" );
		$PL->ChangeStatus( 2 );
	}

	// $results = array();

	$sql     = "SELECT site_id FROM im_suppliers WHERE id=" . $supplier_id;
	// print $sql;
	$site_id = sql_query_single_scalar( $sql );

	print $site_id;
	if ( ! ( $site_id > 0 ) ) {
		sql_error( $sql );
		die( 1 );
	}

	$remote = "/catalog/get-as-pricelist.php";
	if ( $inc ) {
		$remote .= "?incremental&site_id=" . Core_Db_MultiSite::LocalSiteID();
	}

	print Core_Html::gui_header( 2, "מבקש נתונים" );
	flush();
	print $remote . "<br/>";
	$html = Core_Db_MultiSite::sExecute( $remote, $site_id );
//	print $html;
//	die(1);
	if ( strlen( $html ) < 500 ) {
		print "no data<br/>";
		print "remote: " . $remote . "<br/>";
		print $html . strlen( $html );
		die ( 1 );
	}

	print Core_Html::gui_header( 2, "התקבלו נתונים" );
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
		$price      = $row->find( 'td', 4 )->plaintext;
		$sale_price = $row->find( 'td', 5 )->plaintext;
		$var_num    = $row->find( 'td', 6 )->plaintext;
		$picture    = $row->find( 'td', 7 )->plaintext;
		$terms      = $row->find( 'td', 8 )->plaintext;

		if ( isset( $debug_product ) and $prod_id == $debug_product ) {
			print "DEBUG: " . $type . " " . $prod_id . " " . $name . " " . $price . " " . $sale_price . " " . $var_num . "<br/>";
		}
		if ( $debug ) {
			print "<p dir=ltr>" . $name;
		}

		if ( $var_num == 0 ) { // Regular product
			if ( $debug ) {
				print " regular ";
			}
			$result = handle_line( $price, $sale_price, $name, $prod_id, $terms, $PL, $var_num > 0, $id, 0, $picture );
			switch ( $result ) {
				case UpdateResult::UsageError:
				case UpdateResult::SQLError:
					print "Didn't update " . $name . "<br/>";
					break;

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
			$result = handle_line( $price, $sale_price, $name, $prod_id, $terms, $PL, true, $parent_id, 0 );
			if ( $parent_id == 0 ) {
				print "Didn't get parent id for product " . $name . "<br/>";
				continue;
			}
			switch ( $result ) {
				case UpdateResult::UsageError:
				case UpdateResult::SQLError:
					print "Unexpected error 2";
					die( 1 );
				default:
					$results[ $result ][] = array( $name, $price, $parent_id );
			}
			if ($prod_id == 934 || $prod_id == 3452) print "$prod_id var_number: $var_num<br/>";
			for ( $j = 1; $j <= $var_num; $j ++ ) {
				if ( ! isset($lines[ $i + $j ])) {
					print "reading variations failed $prod_id $name<br/>";
					continue;
				}
				$row = $lines[ $i + $j ];

				// print $row;
				$var_id = $row->find( 'td', 1 )->plaintext;
				if ( $debug ) {
					print " handling var " . $var_id;
				}

				//print "prod id " . $prod_id;
				$name = $row->find( 'td', 2 )->plaintext;
				// $date = $row->find('td', 3)->plaintext;
				$price      = $row->find( 'td', 4 )->plaintext;
				$sale_price = $row->find( 'td', 5 )->plaintext;

				if ( $price > 0 ) {
					$result = handle_line( $price, $sale_price, $name, $var_id, "", $PL, false, $var_id, $parent_id );
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
	} else {
		print Core_Html::gui_header( 1, "מוריד פריטים במצב המתנה" );
		$PL->RemoveLines( 2 );
	}

	if ( isset( $results[ UpdateResult::NewPrice ] ) ) {
		print_items( "פריטים חדשים", $results[ UpdateResult::NewPrice ] );
	}
	if ( isset( $results[ UpdateResult::UpPrice ] ) ) {
		print_items( "פריטים התייקרו", $results[ UpdateResult::UpPrice ] );
	}
	if ( isset( $results[ UpdateResult::DownPrice ] ) ) {
		print_items( "פריטים הוזלו", $results[ UpdateResult::DownPrice ] );
	}
	if ( isset( $results[ UpdateResult::DeletePrice ] ) ) {
		print_items( "פריטים יצאו", $results[ UpdateResult::DeletePrice ] );
	}
}

function pricelist_process_name( $filename, $supplier_name, $add, $debug = false ) {
	$sql = "select id from im_suppliers where supplier_name = '$supplier_name' or eng_name = '$supplier_name'";
	// print $sql;
	$sid = sql_query_single_scalar( $sql );
	if ( $sid > 0 ) {
		print "supplier id = " . $sid . "<br/>";

		try {
			pricelist_process( $filename, $sid, $add, null, $debug );
		} catch ( Exception $e ) {
			print $e->getMessage();
			die ( 1 );
		}
	} else {
		print "Cannot find supplier $supplier_name<br/>";
		die( 1 );
	}
}

function pricelist_process( $filename, $supplier_id, $add, $picture_prefix = null, $debug = false ) {
	// Read the file
	$file = file( $filename );
	if ( ! $file ) {
		throw new Exception( "Can't handle file " . $filename );
	}
	do_pricelist_process( $file, $supplier_id, $add, $picture_prefix, $debug );
}

function do_pricelist_process( $file, $supplier_id, $add, $picture_prefix = null, $debug = false ) {
	$item_code_idx       = Array();
	$name_idx            = Array();
	$price_idx           = Array();
	$sale_idx            = Array();
	$detail_idx          = Array();
	$category_idx        = Array();
	$filter_idx          = Array();
	$quantity_idx        = Array(); // Used for delivery notes.
	$is_active_idx       = null;
	$picture_path_idx    = null;
	$parse_header_result = false;
	$inventory_idx       = 0;
	$line_number         = 0;
	for ( $i = 0; ! $parse_header_result and ( $i < 4 ); $i ++ ) {
		$header_line = $file[ $line_number ];
		$line_number ++;

//		$header_line, &$item_code_idx, &$name_idx, &$price_idx, &$sale_idx, &$inventory_idx, &$detail_idx,
//	&$category_idx, $is_active_idx, &$filter_idx, &$picture_idx, &$quantity_idx

		$parse_header_result = parse_header( $header_line, $item_code_idx, $name_idx, $price_idx, $sale_idx, $inventory_idx, $detail_idx,
			$category_idx, $is_active_idx, $filter_idx, $picture_path_idx, $quantity_idx );
	}
	if ( $debug ) {
		print "ln=" . $line_number . "<br/>";
	}

	// print "pp = $picture_path_idx<br/>";
	if ( ! $parse_header_result ) {
		print "Missing headers:<br/>";
		die( " name " . count( $name_idx ) . " price " . count( $price_idx ) );
	}

	$line_number = 0;

	MyLog( "supplier_id = " . $supplier_id );

	$PL = new Fresh_PriceList( $supplier_id );

	if ( ! $add ) {
		$PL->ChangeStatus( 2 );
	} // Change all supplier's lines to status 2 = old

	$results = array();

	$item_count = 0;
	$category   = null;
	print "<table>";
	for ( ; $line_number < count( $file ); $line_number ++ ) {
		$data = str_getcsv( $file[ $line_number ] );
		if ( $debug ) {
			var_dump( $data );
			print "<br/>";
		}

		if ( $data ) {
			if ( isset( $is_active_idx ) and ( $data[ $is_active_idx ] == 1 ) ) {
				// print $data[ $name_idx[ 0 ] ] . " not active. Skipped<br/>";
				continue;
			}
			for ( $col = 0; $col < count( $price_idx ); $col ++ ) {
				$pic_path  = null;
				$item_code = 10;
				$price     = trim( $data[ $price_idx[ $col ] ], '₪ ' );
				if ( $sale_idx ) {
					$sale_price = $data[ $sale_idx[ $col ] ];
				}
				$name = $data[ $name_idx[ $col ] ];
				if ( $debug ) {
					print "<br/>" . $name . " ";
				}
				$detail     = "";
				$sale_price = 0;
				if ( isset( $detail_idx[ $col ] ) ) {
					$detail = $data[ $detail_idx[ $col ] ];
					$detail = rtrim( $detail, "!" );
				}
				if ( isset( $category_idx[ $col ] ) ) {
					$category = $data[ $category_idx[ $col ] ];
					// print $category . "<br/>";
				}
				if ( $detail == "מוגבל מאוד" or $detail == "מוגבל מאד" ) {
					if ( $debug )
						print "מוגבל";
					continue;
				}
				// print $name . " " . $detail . "<br/>";
				if ( in_array( $detail, array( "חדש", "מוגבל" ) ) ) {
					$detail = "";
				}
				// if ($debug) print $name . "<br/>";
				if ( $price > 0 and $inventory_idx and ! is_numeric( $data[ $inventory_idx ] ) and $data[ $inventory_idx ] != "יש" ) {
					print $data[ $name_idx[ $col ] ] . " חסר" . "<br/>";
					if ( $debug )
						print "חסר";
					continue;
				}
				if ( isset( $item_code_idx[ $col ] ) ) {
//					print "code: " . $data[ $item_code_idx[ $col ] ];
					$item_code = $data[ $item_code_idx[ $col ] ];
				}
				if ( $picture_path_idx ) {
					$pp = $data[ $picture_path_idx ];
					if ( strlen( $pp ) > 3 ) {
						$pic_path = $picture_prefix . '/' . $pp;
					} else {
						$pic_path = "";
					}
				}

				if ( $price > 0 ) {
					$result = handle_line( $price, $sale_price, $name . " " . $detail, $item_code, $category, $PL,
						false, $id, $category, $pic_path);
					switch ( $result ) {
						case UpdateResult::UsageError:
						case UpdateResult::SQLError:
							print "Unexpected error 3";
							print $price . " " . $name . " " . $item_code . "<br/>";
							break;
						default:
							$results[ $result ][] = array( $name, $price, $id );
					}
					$item_count ++;
				}
			}
		}
	}
	print "</table>";
	echo $item_count . " פריטים נקראו!" . "<br/>";
	if ( ! $add and ( $item_count > 4 ) ) {
		$results[ UpdateResult::DeletePrice ] = $PL->RemoveLines( 2 );
//        var_dump($results[UpdateResult::DeletePrice]);
	}
	if ( $item_count <= 4 ) {
		print "לא התקבלו מספיק שורות!" . "<br/>";
	}

	$status_string[ UpdateResult::NewPrice ]    = "פריטים חדשים";
	$status_string[ UpdateResult::UpPrice ]     = "פריטים התייקרו";
	$status_string[ UpdateResult::DownPrice ]   = "פריטים הוזלו";
	$status_string[ UpdateResult::DeletePrice ] = "פריטים יצאו";

//	print_items( "פריטים התייקרו", $results[ UpdateResult::UpPrice ] );
//	print_items( "פריטים הוזלו", $results[ UpdateResult::DownPrice ] );
//	print_items( "פריטים יצאו", $results[ UpdateResult::DeletePrice ] );
//	print_items( "פריטים מוסתרים", $results[ ] );

	foreach (
		array(
			UpdateResult::NewPrice,
			UpdateResult::UpPrice,
			UpdateResult::DownPrice,
			UpdateResult::DeletePrice,
			UpdateResult::NotUsed
		) as
		$status
	) {
		if ( isset( $results[ $status ] ) ) {
			print_items( $status_string[ $status ], $results[ $status ] );
		}
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
function handle_line(
	$price, $sale_price, $name, $item_code, $category, $PL, $has_variations, &$id, $parent_id = null,
	$pic_path = null) {
	$name = pricelist_strip_product_name( $name );

	// print $name . " " . $price;
	$id   = 0;
	// Check if we have a price.
	// Product with variations doesn't need a price
	if ( $price > 0 or $has_variations ) {
		$rc = $PL->AddOrUpdate( $price, $sale_price, $name, $item_code, $category, $id, $parent_id, $pic_path );

		// print gui_row( array( $name, $price, $rc ) );

		return $rc;
	}
//    else
//        print "price: " . $price . "<br/>";

	return UpdateResult::UsageError;
}

