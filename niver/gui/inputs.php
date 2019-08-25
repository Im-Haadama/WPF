<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 15/11/16
 * Time: 00:26
 */
/*
 * GUI - HTML
 *-=-=-=-=-=-
 * GuiRowContent - header+row data -> gui (html)
 * GuiTableContent - gui (html) with id. Adds actions
 *
 *
 */

// GUI elements
// cast: function gui_<html code>($params) { return $text; }

/**
 * Create html <label>
 * @param $id
 * label id
 * @param $text
 * content of the label
 *
 * @return string
 */
function gui_label( $id, $text ) {
	return "<label id=" . $id . ">" . $text . "</label>";
}

/**
 * create html button
 * @param $id
 * @param $func
 * @param $text
 * @param bool $disabled
 *
 * @return string
 */
function gui_button( $id, $func, $text, $disabled = false ) {
	$btn =  "<button id=\"" . $id . "\" onclick=\"" . $func . "\"";
	if ($disabled) $btn .= " disabled";
	$btn .= "> " . $text . "</button>";
	
	return $btn;
}

/**
 * @param $id
 * @param $class
 * @param bool $value
 * @param null $events
 *
 * @return string
 */
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


// $key, $data, $args
function GuiInput($id, $data = null, $args = null)
{
	$name = GetArg($args, "name", $id);
	$events = GetArg($args, "events", null);
	$class = GetArg($args, "class", null);
	$size = GetArg($args, "size", null);
	return gui_input($name, $data, $events, $id, $class, $size);
}

/**
 * @param $name
 * @param $value
 * @param null $events
 * @param null $id
 * @param null $class
 * @param null $size
 * @deprecated use GuiInput
 *
 * @return string
 */

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
				if (strstr($event, '"'))
				{
					$event .= "TDOD: replace \" to '";
				}

				$data .= $event . " ";
			}
		} else {
			if (strstr($events, '"'))
			{
				$events .= "TDOD: replace \" to '";
			}

			$data .= $events;
		}
		$data = rtrim( $data, "," );
	}
//    if (strlen($onkeyup) > 0) $data .= ' onkeyup="' . $onkeyup . '">';
	$data .= ">";

	return $data;
}

/**
 * @param $name
 * @param $value
 * @param null $events
 * @param int $rows
 * @param int $cols
 *
 * @return string
 */
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

/**
 * @param $id
 * @param $table
 * @param $field
 * @param bool $include_id
 *
 * @return string
 */
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

/**
 * @param $name
 * @param $class
 * @param $value
 * @param $events
 *
 * @return string
 */
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

/**
 * @param $id
 * @param $class
 * @param null $value
 * @param null $events
 *
 * @return string
 */
function gui_input_date( $id, $class, $value = null, $events = null ) {
	$data = '<input type="date" id="' . $id . '" ';
	if ( is_null( $value ) ) {
		$value = date( 'Y-m-d' );
	}
	if (is_array($value)){
		var_dump($value);
		$debug = debug_backtrace();
		print __FILE__ . " " . __LINE__ . "<br/>";
		for ( $i = 0; $i < 6 && $i < count( $debug ); $i ++ ) {
			print "called from " . $debug[ $i ]["function"] . ":" . $debug[ $i ]["line"] . "<br/>";
		}
		die("invalid date");
	}
	if ( $value and strlen( $value ) > 0 ) {
		$date = date( "Y-m-d", strtotime( $value ) );
		$data .= "value=\"$date\" ";
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

/**
 * @param $id
 * @param $class
 * @param null $value
 * @param null $events
 *
 * @return string
 */
function gui_input_time( $id, $class, $value = null, $events = null ) {
	$data = '<input type="time" id="' . $id . '" ';
	if ( is_null( $value ) ) {
		$value = date( 'h:m' );
	}
	if ( strlen( $value ) > 0 ) {
		$time = date( "h:m", strtotime( $value ) );
		$data .= "value=\"$time\" ";
	}
	if ( strlen( $class ) > 0 ) {
		$data .= "class=\"$class\" ";
	}
	if ( $events and strlen( $events ) > 0 ) {
		$data .= $events;
	}
	$data .= '>';

	return $data;
}

/**
 * @param $text
 * @param $link
 * @param null $target
 *
 * @return string
 */
function gui_hyperlink( $text, $link, $target = null ) {
	$data = "<a href='" . $link . "'";
	if ( $target ) {
		$data .= 'target="' . $target . '"';
	}
	$data .= ">" . $text . "</a>";

	return $data;
}

/**
 * @param $level
 * @param $text
 * @param bool $center
 *
 * @return string
 */
function gui_header( $level, $text, $center = false ) {
	$data = "<h" . $level;
	if ( $center ) {
		$data .= ' style="text-align:center"';
	}
	$data .= ">" . $text . "</h" . $level . ">";

	return $data;
}

/**
 * @param $text
 *
 * @return string
 */
function gui_list( $text ) {
	return "<li>" . $text . "</li>";
}

/**
 * @param $text
 *
 * @return string
 */
function gui_bold( $text ) {
	return "<B>" . $text . "</B>";
}

/**
 * @param $url
 * @param int $x
 * @param int $y
 *
 * @return string
 */
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

/**
 * @param $id
 * @param null $text
 * @param bool $center
 *
 * @return string
 */
function gui_div( $id, $text = null, $center = false ) {
	$data = '<div id="' . $id . '"';
	if ( $center ) {
		$data .= ' style="text-align:center" ';
	}

	$data .= '>';
	if ( $text ) {
		$data .= $text;
	}
	$data .= "</div>";

	return $data;
}

/**
 * print string with <br/> at the end.
 * @param null $text
 */
function printbr( $text = null ) {
	if ( $text ) {
		print $text;
	}
	print "<br/>";
}


// TABLE functions

/**
 * Create html table cell - <td>
 * Hide contents if $show is true.
 * @param $cell
 * @param null $id
 * @param bool $show
 *
 * @return string
 */
function gui_cell( $cell, $id = null, $show = true) {
	$data = "<td";
	if ( $id ) {
		$data .= " id=\"" . $id . "\"";
	}
	if ( ! $show ) {
		$data .= " style=\"display:none;\"";
	}
	$data .= ">";

	$data .= $cell;
	$data .= "</td>";

	return $data;
}

/**
 * Convert array of cells to html table starting with <tr>.
 * Adds checkbox in the start, if needed.
 *
 * @param $cells
 * @param null $row_id
 * @param null $show
 * @param null $acc_fields
 * @param null $col_ids
 * @param null $style
 * @param bool $add_checkbox
 * @param bool $checkbox_class
 * @param null $checkbox_events
 *
 * @return string
 */
function gui_row( $cells, $row_id = null, $show = null, &$acc_fields = null, $col_ids = null, $style = null, $add_checkbox = false, $checkbox_class = false,
	$checkbox_events = null) {

	$data = "<tr ";

	if ( $style ) {
		$data .= $style;
	}

	$data .= ">";

	if ($add_checkbox and is_array($cells))
	{
		array_unshift($cells, gui_checkbox("chk_" . $row_id, $checkbox_class, false, $checkbox_events));
	}

	if ( is_array( $cells ) ) {
		$i = 0;
		foreach ( $cells as $cell ) {
			if ( isset( $acc_fields[ $i ] ) and is_array( $acc = $acc_fields[ $i ] ) ) {
				if ( function_exists( $acc[1] ) ) {
					$acc[1]( $acc_fields[ $i ][0], $cell );
				} else {
					print $acc[1] . " is not a function<br/>";
				}
			}
			$cell_id = null;
			if ( $col_ids and is_array( $col_ids ) ) {
				if ( isset( $col_ids[ $i ] ) ) {
					$cell_id = $col_ids[ $i ] . "_" . $row_id;
				} else {
					$cell_id = "undef" . "_" . $row_id;
				}
			} else if ( $row_id ) {
				$cell_id = $row_id . "_" . $i;
			}

			$show_cell = true;
			if ( is_array( $show ) and isset( $show[ $i ] ) ) {
				$show_cell = $show[ $i ];
//				print $i . " " . $show_cell . "<br/>";
			}
			$data .= gui_cell( $cell, $cell_id, $show_cell );
			$i ++;
		}
	} else {
		$data .= $cells;
	}
	$data .= "</tr>";

	return $data;
}

/**
 * @deprecated use gui_table_args
 */
function gui_table(
	$rows, $id = null, $header = true, $footer = true, &$sum_fields = null, $style = null, $class = null, $show_fields = null,
	$links = null, $col_ids = null, $first_id = false, $actions = null
) {

//	var_dump($id);
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
//	print "id=" . $id . '<br/>';
	if ( is_array( $rows ) ) {
		$first_row = true;
		$row_id = null;
		foreach ( $rows as $row ) {
			if ( ! is_null( $row ) ) {
				if ( $first_id ) {
					$row_id = array_shift( $row );
				}
				if ($actions and ! $first_row){
					foreach ($actions as $action) {
						if (is_array($action))
						{
							$text = $action[0];
							$action = sprintf( $action[1], $row_id );
							array_push($row, gui_hyperlink($text, $action));

						} else {
							$h = sprintf( $action, $row_id );
							array_push( $row, $h );
						}
					}

				}
				$first_row = false;

//				print "id= " . $id ."<br/>";
				$data .= gui_row( $row, $row_id, $show_fields, $sum_fields, $col_ids, null );
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

/**
 * Create html table with data supplied in two dimensional array. Can sum the content in the rows and
 * cols.
 * @param $rows
 * @param null $id
 * @param null $args
 *
 * @return string
 */
function gui_table_args($rows, $id = null, $args = null)
{
	$data = "";

	$debug = GetArg($args, "debug", false);
	$class = GetArg($args, "class", null);
	$add_checkbox = GetArg($args, "add_checkbox", false);
	$checkbox_class = GetArg($args, "checkbox_class", null);
	$checkbox_events = GetArg($args, "checkbox_events", null);
	$header = true;
	$footer = true;
	$sum_fields = GetArg($args, "sum_fields", null);
	$style = null;
	$col_ids = GetArg($args, "col_ids", null);
	$show_cols = GetArg($args, "show_cols", null);
	$id_col = GetArg($args, "id_col", "id");
//	print "id_col=" . $id_col . "<br/>";
	$transpose = GetArg($args, "transpose", false);
	$first_row_to_prepare = GetArg($args, "first_row_to_prepare", 1);

	for ($i = $first_row_to_prepare; $i < count($rows); $i++) {
		if ( isset( $rows[$i][ $id_col ] ) ) {
			$row_id = $rows[$i][ $id_col ];
			// print "rid=" .$row_id ."<br/>";
		} else {
			$row_id = null;
//			 print "no rid<br/>";
		}

//		print "id_col=" . $id_col . " " . $rows[$i][$id_col] . "<br/>";
		$rows[ $i ] = PrepareRow( $rows[ $i ], $args, $row_id );
	}

	if ($transpose){
		$rows = array_map(null, ...$rows);
	}

	if ( $style ) {
		print "<style>" . $style . "</style>";
	} else {
		if ($debug) print "no style";
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
			// $row_id = ! is_null($id_col) ? strip_tags($row[$id_col]) : null;
			if (isset($row[$id_col]))
				$row_id = $row[$id_col];
			else $row_id = "row_id not supplied $id_col";

			if ( ! is_null( $row ) ) {
				$data .= gui_row( $row, $row_id, $show_cols, $sum_fields, $col_ids, null, $add_checkbox, $checkbox_class, $checkbox_events );
			}
		}
	} else {
		$data .= "<tr>" . $rows . "</tr>";
	}

	if ( $footer ) {
		$data .= "</table>";
	}

	return $data;

}

// SELECTORS

/**
 * @param $id
 * @param $datalist
 * @param null $events
 *
 * @return string
 */
function gui_input_select_from_datalist( $id, $datalist, $events = null, $value = null ) {
	$data = "<input id='$id' list='$datalist' ";
	if ($value)
		$data .= 'value="' . $value . '"';
	if ( $events ) {
		$data .= $events;
	}
	$data .= ">";

	return $data;
}

/**
 * @param $id
 * @param $table
 * @param $args
 *
 * @return string
 */
function GuiSelectTable($id, $table, $args)
{
	$selected = GetArg($args, "selected", null);
	$events = GetArg($args, "events", null);
	$more_values = GetArg($args, "more_values", null);
	$name = GetArg($args, "name", "name");
	$where = GetArg($args, "where", null);
	$include_id = GetArg($args, "include_id", false);
	$datalist = GetArg($args, "datalist", false);
	$order_by = GetArg($args, "order_by", null);
	$id_key = GetArg($args, "id_key", null);
	$class = GetArg($args, "class", null);
	$length_limit = GetArg($args, "length_limit", 30);

	if ( ! $id_key ) {
		$id_key = "id";
	}

	$values = array();

	if ( $more_values ) {
		foreach ( $more_values as $value ) {
			array_push( $values, substr($value, $length_limit) );
		}
	}

	$sql = "SELECT distinct " . $id_key . ", " . sprintf($name, $id_key);
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

	$results = sql_query( $sql );
	if ( $results ) {
		while ( $row = $results->fetch_assoc() ) {
			array_push( $values, $row );
		}
	} else {
		return "no results " . $sql;
	}

	if ( $datalist ) {
		return gui_select_datalist( $id, $table . "_values", $name, $values, $events, $selected, $include_id, $id_key, $class );
	} else {
		return gui_select( $id, $name, $values, $events, $selected, $id_key, $class );
	}
}

/**
 * @param $id
 * @param $datalist_id
 * @param $name
 * @param $values
 * @param $events
 * @param null $selected
 * @param bool $include_id
 * @param null $id_key
 * @param null $class
 *
 * @return string
 */
function gui_select_datalist( $id, $datalist_id, $name, $values, $events, $selected = null, $include_id = true, $id_key = null, $class = null ) {
//	print "include_id= " . $include_id . "<br/>";
//	print "selected = " . $selected . "<br/>";

	if ( ! $id_key )
		$id_key = "id";

	$data = "";
	static $shown_datalist = array();

	if (! $shown_datalist[$datalist_id]) {
		$data .= "<datalist id=\"" . $datalist_id . "\">";
		$selected_value = $selected;

		if (! is_array($values))
		{
			print "bad list: ";
			var_dump($values);
			return null;
		}

		foreach ( $values as $row ) {
			$value = "";
			if (! isset($row[$id_key]))
			{
				die ("check args! $id_key not found in row" . __FILE__ . " " . __LINE__);
			}
			$value .= $row[ $name ];
			if ( $include_id ) {
				$value = $row[ $id_key ] . ")" . $value;
			}
			$data  .= "<option value=\"" . $value . "\" ";
			foreach ( $row as $key => $data_value ) {
				if (  $key != $name ) {
					$data .= "data-" . $key . '="' . $data_value . '" ';
				}
			}
			$data  .= ">";
		}

		$data .= "</datalist>";

		$shown_datalist[$datalist_id]++;

		if ($id == "datalist") return $data; // Just print the datalist.
	}

	if ($selected)
		foreach ( $values as $row ) {
			if ($row[$id_key] == $selected){
				$selected_value = $row[$name];
			}
	}

	$data .= "<input id=\"" . $id . "\" ";
	if ( $selected ) {
		$data .= "value = '" . htmlentities($selected_value, ENT_QUOTES) . "' ";
	}
	$data .= "list=\"" . $datalist_id. "\" ";

	if ( $events ) {
		$data .= $events;
	}

	$data .= ">";

	return $data;
}

/**
 * @param $id
 * @param $values
 * @param $events
 * @param $selected
 *
 * @return string
 */
function gui_simple_select( $id, $values, $events, $selected ) {
	$data = "<select id=\"" . $id . "\" ";
	if ( $events ) {
		$data .= $events;
	}

	$data .= ">";

	foreach ( $values as $key => $row ) {
		$data .= "<option value=\"" . $row . "\"";
//		print $selected . " " . $row . "<br/>";
		if ( $selected and ($selected == $key) ) {
			$data .= " selected ";
		}
		// print $selected . " " . $row["$id"] . "<br/>";
		$data .= ">";
		$data .= $row . "</option>";
	}

	$data .= "</select>";

	return $data;
}

/**
 * @param $id
 * @param $name
 * @param $values
 * @param $events
 * @param $selected
 * @param string $id_key
 * @param null $class
 *
 * @return string
 */
function gui_select( $id, $name, $values, $events, $selected, $id_key = "id", $class = null ) {
	$data = "<select id=\"" . $id . "\" ";

	if ( $class ) {
		$data .= ' class = "' . $class . '" ';
	}

	if ( $events ) {
		$data .= $events;
	}

	$data .= ">";

	foreach ( $values as $row ) {
//		var_dump($row); print "<br/>";
		if (! isset($row[$id_key]))
		{
//			var_dump($row);
//			print '<br/>';
//			die ($id_key . ' offset not set ' . $id);
		}
		$data .= "<option value=\"" . $row[ $id_key ] . "\"";
//		print "key = $row[$id_key] <br/>";
		if ( $selected and $selected == $row[ $id_key ] ) {
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
		if ( $name ) {
			$data .= $row[ $name ] . "</option>";
		}
	}

	$data .= "</select>";

	return $data;
}

//if ( $sum_fields ) {
//	$array = array();
//	foreach ( $sum_fields as $value ) {
//		if ( is_array( $value ) ) {
//			array_push( $array, $value[0] );
//		} else {
//			array_push( $array, $value );
//		}
//	}
//	$data .= gui_row( $array );
//}


/**
 * @deprecated use GuiSelectTable
 * @param $id
 * @param $table
 * @param null $selected
 * @param null $events
 * @param null $more_values
 * @param null $name
 * @param null $where
 * @param bool $include_id
 * @param bool $datalist
 * @param null $order_by
 * @param null $id_key
 *
 * @return string
 */

function gui_select_table(
	$id, $table, $selected = null, $events = null, $more_values = null, $name = null, $where = null,
	$include_id = false, $datalist = false, $order_by = null, $id_key = null
) {
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

	$results = sql_query( $sql );
	if ( $results ) {
		while ( $row = $results->fetch_assoc() ) {
			array_push( $values, $row );
		}
	} else {
		return "no results " . $sql;
	}

	if ( $datalist ) {
		return gui_select_datalist( $id, $table, $name, $values, $events, $selected, $include_id, $id_key );
	} else {
		return gui_select( $id, $name, $values, $events, $selected, $id_key );
	}
}
