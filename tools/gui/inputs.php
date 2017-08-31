<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 15/11/16
 * Time: 00:26
 */

// GUI elements
// cast: function gui_<html code>($params) { return $text; }

function gui_lable( $id, $text ) {
	return "<label id=" . $id . ">" . $text . "</label>";
}

function gui_button( $id, $func, $text ) {
	return "<button id=\"" . $id . "\" onclick=\"" . $func . "\"> " . $text . "</button>";
}

function gui_datalist( $id, $table, $field ) {
	global $conn;

	$data = "<datalist id=\"" . $id . "\">";

	$sql = "select " . $field . " from " . $table;
//    print $sql;
	$result = mysqli_query( $conn, $sql );
	if ( ! $result ) {
		print mysqli_error( $conn );

		return "";
	}
	while ( $row = mysqli_fetch_assoc( $result ) ) {
		$data .= "<option value=\"" . htmlspecialchars( $row[ $field ] ) . "\">";
	}

	$data .= "</datalist>";

	return $data;
}

function gui_checkbox( $id, $class, $value = false, $events = null ) {
	$data = "<input id=\"$id\" class=\"$class\" type=\"checkbox\"";
	if ( $value ) {
		$data .= "checked ";
	}
	if ( strlen( $events ) > 0 ) {
		$data .= $events;
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

function gui_input( $name, $value, $events = null, $id = null ) {
	if ( is_null( $id ) ) {
		$id = $name;
	}
	$data = '<input type="text" name="' . $name . '" id="' . $id . '"';
	if ( strlen( $value ) > 0 ) {
		$data .= "value=\"$value\" ";
	}
	if ( $events ) {
		foreach ( $events as $event ) {
			$data .= $event . ",";
		}
		$data = rtrim( $data, "," );
	}
//    if (strlen($onkeyup) > 0) $data .= ' onkeyup="' . $onkeyup . '">';
	$data .= "</input>";

	return $data;
}

function gui_textarea( $name, $value, $onkeypress, $rows = 2, $cols = 40 ) {
	$data = '<textarea name="' . $name . '" id="' . $name . '"';
	if ( strlen( $onkeypress ) > 0 ) {
		$data .= ' onkeypress="' . $onkeypress;
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

function gui_input_date( $name, $class, $value = null, $events = null ) {
	$data = '<input type="date" name="' . $name . '" ';
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
		$data .= addslashes( $events );
	} // ' onkeypress="' . $onkeypress . '"';
	$data .= '>';

//    print $data;
	return $data;
}

function gui_select_table( $id, $table, $selected = null, $events = null, $more_values = null, $name = null, $where = null ) {
	global $conn;

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
	$sql = "SELECT id, " . $name . " FROM " . $table;
	if ( $where ) {
		$sql .= " " . $where;
	}
// 	print $sql;
	$results = mysqli_query( $conn, $sql );
	while ( $row = $results->fetch_assoc() ) {
		array_push( $values, $row );
	}

	return gui_select( $id, $name, $values, $events, $selected );
}

function gui_select( $id, $name, $values, $events, $selected ) {

	$data = "<select id=\"" . $id . "\"";
	if ( $events ) {
		$data .= $events;
	}

	$data .= ">";

	foreach ( $values as $row ) {
		$data .= "<option value=\"" . $row["id"] . "\"";
		if ( $selected and $selected == $row["id"] ) {
			$data .= " selected";
		}
		// print $selected . " " . $row["$id"] . "<br/>";
		$data .= ">";
		$data .= $row[ $name ] . "</option>";
	}

	$data .= "</select>";

	return $data;

}

function gui_hyperlink( $text, $link ) {
	return "<a href='" . $link . "'>" . $text . "</a>";
}

//function gui_print_table($rows)
//{
//    print "<table>";
//    foreach row
//    print "</table>";
//}

function gui_cell( $cell, $id = null, $show = true ) {
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
	$data .= $cell;
	$data .= "</td>";

	return $data;
}

function gui_row( $cells, $id = null, $show = null, &$sum_fields = null, $col_ids = null ) {
	$data = "<tr>";

	if ( is_array( $cells ) ) {
		$i = 0;
		foreach ( $cells as $cell ) {
			if ( $sum_fields[ $i ] and is_numeric( $sum_fields[ $i ] ) ) {
				$sum_fields[ $i ] += $cell;
			}
			$cell_id = null;
			if ( $col_ids and is_array( $col_ids ) ) {
				$cell_id = $col_ids[ $i ] . "_" . $id;
			} else if ( $id ) {
				$cell_id = $id . "_" . $i;
			}

			$show_cell = true;
			if ( is_array( $show ) ) {
				$show_cell = $show[ $i ];
			}

			$data .= gui_cell( $cell, $cell_id, $show_cell);
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

function gui_table( $rows, $id = null, $header = true, $footer = true, &$sum_fields = null, $style = null, $class = null ) {
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
				$data .= gui_row( $row, $sum_fields );
			}
		}

	} else {
		$data .= "<tr>" . $rows . "</tr>";
	}
	if ( $sum_fields ) {
		$data .= gui_row( $sum_fields );
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