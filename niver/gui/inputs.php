<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 15/11/16
 * Time: 00:26
 */

// GUI elements
// cast: function gui_<html code>($params) { return $text; }

function gui_label( $id, $text ) {
	return "<label id=" . $id . ">" . $text . "</label>";
}

function printbr( $text = null ) {
	if ( $text ) {
		print $text;
	}
	print "<br/>";
}

function gui_button( $id, $func, $text ) {
	return "<button id=\"" . $id . "\" onclick=\"" . $func . "\"> " . $text . "</button>";
}

function gui_datalist( $id, $table, $field, $include_id = false ) {
	global $conn;

	$data = "<datalist id=\"" . $id . "\">";

	$sql = "select " . $field;
	if ( $include_id ) {
		$sql .= ", id";
	}
	$sql .= " from " . $table;

	$result = mysqli_query( $conn, $sql );
	if ( ! $result ) {
		print mysqli_error( $conn );

		return "";
	}
	while ( $row = mysqli_fetch_assoc( $result ) ) {
		$id_text = "";
		if ( $include_id ) {
			$id_text = $row["ID"] . ")";
		}
		$data .= "<option value=\"" . $id_text . htmlspecialchars( $row[ $field ] ) . "\">";
	}

	$data .= "</datalist>";

	return $data;
}

function gui_checkbox( $id, $class, $value = false, $events = null ) {
	$data = "<input id=\"$id\" class=\"$class\" type=\"checkbox\" ";
	if ( $value ) {
		$data .= "checked ";
	}
	if ( $events ) {
		if ( is_array( $events ) ) {
			$data .= implode( ",", $events );
		} else {
			$data .= $events;
		}
	}

	$data .= ">";

	return $data;
}

//function gui_input($name, $value, $onkeyup, $id = null)
//{
//	if (is_null($id)) $id = $name;
//	$data = '<input type="text" name="' . $name . '" id="' . $id . '"';
//	if (strlen($value) > 0) $data .= "value=\"$value\" ";
//	if (strlen($onkeyup) > 0) $data .= ' onkeyup="' . $onkeyup . '">';
//	$data .= "</input>";
//	return $data;
//}

function gui_input( $name, $value, $events = null, $id = null, $class = null, $size = null ) {
	if ( is_null( $id ) ) {
		$id = $name;
	}
	$data = '<input type="text" name="' . $name . '" id="' . $id . '"';
	if ( strlen( $value ) > 0 ) {
		$data .= "value=\"$value\" ";
	}
	if ( $size ) {
		$data .= ' size="' . $size . '" ';
	}
	if ( $class ) {
		$data .= ' class = "' . $class . '" ';
	}
	if ( $events ) {
		if ( is_array( $events ) ) {
			foreach ( $events as $event ) {
				$data .= $event . " ";
			}
		} else {
			$data .= $events;
		}
		$data = rtrim( $data, "," );
	}
//    if (strlen($onkeyup) > 0) $data .= ' onkeyup="' . $onkeyup . '">';
	$data .= ">";

	return $data;
}

function gui_textarea( $name, $value, $events = null, $rows = 0, $cols = 0 ) {
	$data = '<textarea name="' . $name . '" id="' . $name . '"';
	if ( strlen( $events ) > 0 ) {
		$data .= $events;
	}
	if ( $rows == 0 ) {
		$rows = min( 10, substr_count( $value, "\n" ) + 2 );
	}
	$data .= "cols=" . $cols . " rows=" . $rows;
	$data .= '">';
	if ( strlen( $value ) > 0 ) {
		$data .= $value;
	}
	$data .= "</textarea>";

	return $data;
}

function gui_input_month( $name, $class, $value, $events ) {
	$data = '<input type="month" name="' . $name . '" ';
	if ( strlen( $value ) > 0 ) {
		$data .= "value=\"$value\" ";
	}
	if ( strlen( $class ) > 0 ) {
		$data .= "class=\"$class\" ";
	}
	if ( strlen( $events ) > 0 ) {
		$data .= addslashes( $events );
	} // ' onkeypress="' . $onkeypress . '"';
	$data .= '>';

//    print $data;
	return $data;
}

function gui_input_date( $id, $class, $value = null, $events = null ) {
	$data = '<input type="date" id="' . $id . '" ';
	if ( is_null( $value ) ) {
		$value = date( 'Y-m-d' );
	}
	if ( strlen( $value ) > 0 ) {
		$data .= "value=\"$value\" ";
	}
	if ( strlen( $class ) > 0 ) {
		$data .= "class=\"$class\" ";
	}
	if ( $events and strlen( $events ) > 0 ) {
		// $data .= addslashes( $events );
		$data .= $events;
	} // ' onkeypress="' . $onkeypress . '"';
	$data .= '>';

//    print $data;
	return $data;
}

function gui_input_select_from_datalist( $id, $datalist, $events = null ) {
	$data = "<input id='$id' list='$datalist' ";
	if ( $events ) {
		$data .= $events;
	}
	$data .= ">";

	return $data;
}

function gui_select_table(
	$id, $table, $selected = null, $events = null, $more_values = null, $name = null, $where = null,
	$include_id = false, $datalist = false, $order_by = null, $id_key = null
) {
	global $conn;
	if ( ! $id_key ) {
		$id_key = "id";
	}

	$values = array();

	if ( $more_values ) {
		foreach ( $more_values as $value ) {
			array_push( $values, $value );
//			$data .= "<option value=\"" . $value[0] . "\"";
//			if ( $selected and $selected == $value ) {
//				$data .= " selected";
//			}
//			// print $selected . " " . $row["$id"] . "<br/>";
//			$data .= ">";
//			$data .= $value[1] . "</option>";
		}
	}
	if ( $name == null ) {
		$name = "name";
	}

	$sql = "SELECT distinct " . $id_key . ", " . $name;
	if ( $order_by ) {
		$sql .= ", " . $order_by;
	}
	$sql .= " FROM " . $table;
	if ( $where ) {
		$sql .= " " . $where;
	}
	// print $sql;
	if ( $order_by ) {
		$sql .= " order by 3 ";
	}

	// print $sql;

	$results = mysqli_query( $conn, $sql );
	if ( $results ) {
		while ( $row = $results->fetch_assoc() ) {
			array_push( $values, $row );
		}
	} else {
		return "no results " . $sql;
	}

	if ( $datalist ) {
		return gui_select_datalist( $id, $name, $values, $events, $selected, $include_id, $id_key );
	} else {
		return gui_select( $id, $name, $values, $events, $selected );
	}
}


function gui_select_datalist( $id, $name, $values, $events, $selected = null, $include_id = true, $id_key = null ) {
	if ( ! $id_key )
		$id_key = "id";

	$data = "<datalist id=\"" . $id . "_items\">";

	foreach ( $values as $row ) {
		$value = "";
		if ( $include_id ) {
			$value .= $row[ $id_key ] . ")";
		}
		$value .= $row[ $name ];
		$data  .= "<option value=\"" . $value . "\"";
		$x     = "";
		foreach ( $row as $key => $data_value ) {
			if ( $key != $id and $key != $name ) {
				$data .= "data-" . $key . '="' . $data_value . '" ';
			}
		}
		// print $x . "<br/>";
		$data  .= ">";
		// $data .= $row[ $name ] . "</option>";
	}

	$data .= "</datalist>";

//	if ( $selected and $selected == $row["id"] ) {
//		$data .= " selected";
//	}
	$data .= "<input id=\"" . $id . "\" ";
	if ( $selected ) {
		$data .= "value = '" . $selected . "' ";
	}
	$data .= "list=\"" . $id . "_items\" ";

	if ( $events ) {
		$data .= $events;
	}

	$data .= ">";

	return $data;

}

function gui_select( $id, $name, $values, $events, $selected ) {
	$data = "<select id=\"" . $id . "\" ";
	if ( $events ) {
		$data .= $events;
	}

	$data .= ">";

	foreach ( $values as $row ) {
		$data .= "<option value=\"" . $row["id"] . "\"";
		if ( $selected and $selected == $row["id"] ) {
			$data .= " selected ";
		}
		if ( is_array( $row ) ) {
			foreach ( $row as $k => $f ) {
				if ( substr( $k, 0, 4 ) == "data" ) {
					$data .= $k . "=" . '"' . $f . '"';
				}
			}
		}
		// print $selected . " " . $row["$id"] . "<br/>";
		$data .= ">";
		$data .= $row[ $name ] . "</option>";
	}

	$data .= "</select>";

	return $data;

}

function gui_hyperlink( $text, $link, $target = null ) {
	$data = "<a href='" . $link . "'";
	if ( $target ) {
		$data .= 'target="' . $target . '"';
	}
	$data .= ">" . $text . "</a>";

	return $data;
}

//function gui_print_table($rows)
//{
//    print "<table>";
//    foreach row
//    print "</table>";
//}

function gui_cell( $cell, $id = null, $show = true, $link = null ) {
	$data = "<td";
	if ( $id ) {
		$data .= " id=\"" . $id . "\"";
	}
	if ( ! $show ) {
		$data .= " style=\"display:none;\"";
	}
	$data .= ">";

//	if (is_numeric($cell))
//		$data .= number_format($cell, 2);
//	else
	if ( $link ) {
		$url  = sprintf( $link, $cell );
		$data .= gui_hyperlink( $cell, urldecode( $url ) );
	} else {
		$data .= $cell;
	}
	$data .= "</td>";

	return $data;
}

function gui_row( $cells, $id = null, $show = null, &$acc_fields = null, $col_ids = null, $style = null, $links = null ) {
	$data = "<tr ";

	if ( $style ) {
		$data .= $style;
	}

	$data .= ">";

	if ( is_array( $cells ) ) {
		$i = 0;
		foreach ( $cells as $cell ) {
			if ( isset( $acc_fields[ $i ] ) and is_array( $acc = $acc_fields[ $i ] ) ) {
				// var_dump($acc);
				if ( function_exists( $acc[1] ) ) {
					$acc[1]( $acc_fields[ $i ][0], $cell );
				} else {
					print $acc[1] . " is not a function<br/>";
				}
//				print "Y" . ($sum_fields[$i] === true) . "Y";
//				if (($sum_fields[$i] === true) and ($cell > 0)) { print "XX"; $sum_fields[$i] = 0; };
				// $sum_fields[ $i ] += $cell;
			}
			$cell_id = null;
			if ( $col_ids and is_array( $col_ids ) ) {
				if ( isset( $col_ids[ $i ] ) ) {
					$cell_id = $col_ids[ $i ] . "_" . $id;
				} else {
					$cell_id = "undef" . "_" . $id;
				}
			} else if ( $id ) {
				$cell_id = $id . "_" . $i;
			}

			$show_cell = true;
			if ( is_array( $show ) and isset( $show[ $i ] ) ) {
				$show_cell = $show[ $i ];
			}
			//	print $i . " " . $cell . " " . $show_cell . "<br/>";
			$data .= gui_cell( $cell, $cell_id, $show_cell, $links[ $i ] );
			$i ++;
		}
	} else {
		$data .= $cells;
	}
	$data .= "</tr>";

	return $data;
}

function gui_header( $level, $text, $center = false ) {
	$data = "<h" . $level;
	if ( $center ) {
		$data .= ' style="text-align:center"';
	}
	$data .= ">" . $text . "</h" . $level . ">";

	return $data;
}

function gui_list( $text ) {
	return "<li>" . $text . "</li>";
}

function gui_link( $text, $url, $target ) {
	return '<a href="' . $url . '" target="' . $target . '">' . $text . "</a>";
}

function gui_bold( $text ) {
	return "<B>" . $text . "</B>";
}

function gui_table(
	$rows, $id = null, $header = true, $footer = true, &$sum_fields = null, $style = null, $class = null, $show_fields = null,
	$links = null, $col_ids = null, $first_id = false
) {

//	var_dump($links);
	$data = "";

	if ( $style ) {
		print "<style>" . $style . "</style>";
	}
	if ( $header ) {
		$data = "<table";
		if ( $class ) {
			$data .= " class=\"" . $class . "\"";
		}
		if ( ! is_null( $id ) ) {
			$data .= ' id="' . $id . '"';
		}
		$data .= " border=\"1\"";
		$data .= ">";
	}
	if ( is_array( $rows ) ) {
		foreach ( $rows as $row ) {
			if ( ! is_null( $row ) ) {
				if ( $first_id ) {
					$id = array_shift( $row );
				}
				$first_row = false;

				$data .= gui_row( $row, $id, $show_fields, $sum_fields, $col_ids, null, $links );
//				function gui_row( $cells, $id = null, $show = null, &$sum_fields = null, $col_ids = null ) {

			}
		}
	} else {
		$data .= "<tr>" . $rows . "</tr>";
	}
	if ( $sum_fields ) {
		$array = array();
		foreach ( $sum_fields as $value ) {
			if ( is_array( $value ) ) {
				array_push( $array, $value[0] );
			} else {
				array_push( $array, $value );
			}
		}
		$data .= gui_row( $array );
	}

	if ( $footer ) {
		$data .= "</table>";
	}

	return $data;
}

function gui_image( $url, $x = 0, $y = 0 ) {
	$val = "<img src=\"" . $url . "\"";
	if ( $x > 0 || $y > 0 ) {
		$val .= "style=\"";
	}
	if ( $x > 0 ) {
		$val .= "width:" . $x . "px; ";
	}
	if ( $y > 0 ) {
		$val .= "height:" . $y . "px;";
	}
	if ( $x > 0 || $y > 0 ) {
		$val .= "\"";
	}
	$val .= ">";

	return $val;
}

function gui_div( $id, $text = null ) {
	$data = '<div id="' . $id . '">';
	if ( $text ) {
		$data .= $text;
	}
	$data .= "</div>";

	return $data;
}