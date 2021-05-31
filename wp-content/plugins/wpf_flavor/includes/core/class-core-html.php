<?php

define ('TABLE_START', "
<table>");
define ('TABLE_END', "
</table>");
define ('ROW_START', "
<tr>");
define ('ROW_END', "
</tr>");
define ('CELL_START', "
<td>");
define ('CELL_END', "
</td>");

class Core_Html {
	/**
	 * Create html <label>
	 *
	 * @param $id
	 * label id
	 * @param $text
	 * content of the label
	 *
	 * @param bool $hidden
	 *
	 * @return string
	 */
	static function gui_label( $id, $text, $args = null ) {
		$hidden = GetArg($args, "hidden", false);
		$events = GetArg($args, "events", false);
		$result = "<label id=" . $id . " ";
		if ( $hidden ) {
			$result .= 'style="display: none"';
		}
		if ($events)
			$result .= $events;
		$result .= ">" . $text . "</label>";

		return $result;
	}

	/**
	 * create html button
	 *
	 * @param $id
	 * @param $text
	 * @param $args_or_action
	 *
	 * @return string
	 */
	static function GuiButton($id, $text, $args_or_action)
	{
		$result = "<button id=\"$id\"";
		if (is_array($args_or_action)) {
			if ( $style = GetArg( $args_or_action, "style", null ) ) $result .= " style=\"$style\"";
			if ( $class = GetArg( $args_or_action, "class", null ) ) $result .= " class=\"$class\"";
			if ( $events = GetArg( $args_or_action, "events", null ) ) $result .= " $events ";
            if ($tooltip = GetArg($args_or_action, 'tooltip', null)) $result .= "title=\"$tooltip\"";
		}
        //$result .= " title=\"hello\"";
		if (is_string($args_or_action)) $result .= " onclick=\"$args_or_action\" ";
			else if ($action = GetArg($args_or_action, "action", null)) $result .= " onclick=\"$action\" ";
		$result .= ">$text";
		$result .= "</button>";

		return $result;
	}


	/**
	 * @param $id
	 * @param $class
	 * @param bool $value
	 * @param null $events
	 *
	 * @return string
	 */
	static function GuiCheckbox($id, $value = false, $args = array())
	{
		$events = GetArg($args, "events", null);
		$class = GetArg($args, "checkbox_class", "checkbox_default");
//		if ($class == "checkbox_default") {
//			print debug_trace(10);
//			die ("1");
//		}
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

		if (false === GetArg($args, "edit", null))
			$data .= " disabled='disabled' ";
		$data .= ">";

		return $data;
	}

	static function ActiveCheckbox($id, $value, $args = [])
	{
		$result = ETranslate($value ? "Active" : "Not active") . " ";

		$post_file = GetArg($args, "post_file", "post not supplied" . __FUNCTION__);
		if (! isset($args["events"])) $args["events"] = 'onchange="data_set_active(\'' . $post_file . '\', \'' . $id .'\')"';
		$result .= self::GuiCheckbox($id, $value, $args);
		return $result;
	}

	// $key, $data, $args

	/**
	 * @param $id
	 * @param null $value
	 * @param null $args
	 *
	 * @return string
	 */
	static function GuiInput( $id, $value = null, $args = null ) {
		$name   = GetArg( $args, "name", $id );

		if (is_array($name))
		{
			var_dump($value);
			return __FUNCTION__ . " got array";
		}
		$events = GetArg( $args, "events", null );
		$class  = GetArg( $args, "class", null );
		$size   = GetArg( $args, "size", null );
//		print "id=$id size=$size<br/>";
		$style = GetArg($args, "style", null);
//		print "style=$style";

		if ( is_null( $id ) ) {
			$id = $name;
		}
		$data = '<input type="text" name="' . $name . '" id="' . $id . '" ';
		if ( is_array( $value ) ) {
			DebugVar( $value );
		}
		if ( strlen( $value ) > 0 ) {
			$data .= "value=\"$value\" ";
		}
		if ($style) $data .= ' style="' . $style . '"';
		if ( $size ) {
			$data .= ' size="' . $size . '" ';
		}
		if ( $class ) {
			$data .= ' class = "' . $class . '" ';
		}
		if ( $events ) {
			if ( ! is_array( $events ) ) {
				$events = array( $events );
			}
			foreach ( $events as $event ) {
				$data .= $event . " ";
			}
			$data = rtrim( $data, "," );
		}
		$data .= ">";

		return $data;
	}

	/**
	 * @param $id
	 * @param null $value
	 * @param null $args
	 *
	 * @return string
	 */
	static function GuiButtonOrHyperlink( $id, $value = null, $args = null ) // Value is irrelevant but here to keep the structure: id, value, args.
	{
		$action = GetArg( $args, "action", null );
		$text   = GetArg( $args, "text", null );
		if ( $s = strpos( $action, ';' ) ) { // We have javascript command.
			$server_action = substr( $action, 0, $s );
			$client_action = substr( $action, $s + 1 );

			return Core_Html::GuiButton( $id, $text, array("action" => "execute_url('" . $server_action . "', $client_action)"));
		} else {
			return Core_Html::GuiHyperlink( $text, $action );
		}
	}

	/**
	 * @param $name
	 * @param $value
	 * @param null $events
	 * @param null $id
	 * @param null $class
	 * @param null $size
	 *
	 * @return string
	 * @deprecated use GuiInput
	 *
	 */

	static function gui_input( $name, $value, $events = null, $id = null, $class = null, $size = null ) {
		if ( is_null( $id ) ) {
			$id = $name;
		}
		$data = '<input type="text" name="' . $name . '" id="' . $id . '" ';
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
			if ( ! is_array( $events ) ) {
				$events = array( $events );
			}
			foreach ( $events as $event ) {
				$data .= $event . " ";
			}
			$data = rtrim( $data, "," );
		}
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
	static function gui_textarea( $name, $value, $events = null, $rows = 0, $cols = 0 ) {
		$data = '<textarea name="' . $name . '" id="' . $name . '" ';
		if ( strlen( $events ) > 0 ) {
			$data .= $events;
		}
		if ( $rows == 0 ) {
			$rows = min( 10, substr_count( $value, "\n" ) + 2 );
		}
		$data .= " cols=" . $cols . " rows=" . $rows;
		$data .= '>';
		if ( strlen( $value ) > 0 ) {
			// Replace <br/> to \n
			$data .= $value;
		}
		$data .= "</textarea>";

		return $data;
	}

	/**
	 * @param $value
	 *
	 * @return string|string[]
	 */
	static function remove_br( $value ) {
		$to_replace = array( "<br/>", "<br>" );
		foreach ( $to_replace as $rep ) {
			$value = str_replace( $rep, '\n', $value );
		}

		return $value;
	}

	/**
	 * @param $id
	 * @param $values
	 * @param $id_field
	 * @param $field_name
	 * @param bool $include_id
	 *
	 * @return string
	 */
	static function GuiDatalist( $id, $values, $id_field, $field_name, $include_id = false )
	{
		if (is_array($field_name)) {
			var_dump($field_name);
			die("field_name should be string");
		}
		$data = "<datalist id=\"" . $id . "\">";

		foreach ( $values as $row ) {
			// var_dump($row); print "<br/>";
			$id_text = "";
			if ( $include_id ) {
				if ( ! isset( $row[ $id_field ] )  ) {
					print __FUNCTION__ . ": " . $id_field . " is missing. ";
					var_dump( $row );
					print "<br/>";
					print debug_trace();
					die( 1 );
				}
				$id_text = $row[ $id_field ] . ")";
			}
			$value = "$field_name not set";
			if (isset($row[$field_name])) $value =
				htmlspecialchars( $row[ $field_name ] );
			$data .= "<option value=\"" . $id_text . $value . "\"";

			foreach ( $row as $key => $data_value ) {
				if ($key == $id_field) $key = "id";
				if ( $key != $field_name ) {
					$data .= " data-" . $key . '="' . $data_value . '" ';
				}
			}

			$data .= "\">";
		}

		$data .= "</datalist>";

		return $data;
	}

	/**
	 * @param $id
	 * @param $table
	 * @param $field
	 * @param bool $include_id
	 *
	 * @return string
	 * @deprecated. Use GuiDatalist.
	 *
	 */

//	static function gui_datalist( $id, $table, $field, $include_id = false ) {
//		$args               = [];
//		$args["include_id"] = $include_id;
//		$args["field"]      = $field;
//
//		return self::TableDatalist( $id, $table, $args );
//	}

	/**
	 * @param $name
	 * @param $class
	 * @param $value
	 * @param $events
	 *
	 * @return string
	 */
	static function gui_input_month( $name, $class, $value, $events ) {
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
	 * @deprecated user GuiInputDate
	 *
	 * @return string
	 */
	static function gui_input_date( $id, $class, $value = null, $events = null ) {
		return self::GuiInputDate($id, $value, array("class"=>$class, "events"=>$events));
	}

	static function GuiInputDate($id, $value, $args)
	{
		$class = GetArg($args, "class", null);
		$events = GetArg($args, "events", null);

		$data = '<input type="date" id="' . $id . '" ';
		// 09/09/2019. It's ok to show null - not selected value. E.g - task date.
		//	if ( is_null( $value ) ) {
		//		$value = date( 'Y-m-d' );
		//	}
		if ( is_array( $value ) ) {
			var_dump( $value );
			$debug = debug_backtrace();
			print __FILE__ . " " . __LINE__ . "<br/>";
			for ( $i = 0; $i < 6 && $i < count( $debug ); $i ++ ) {
				print "called from " . $debug[ $i ]["function"] . ":" . $debug[ $i ]["line"] . "<br/>";
			}
			die( "invalid date" );
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

	static function GuiShowDynamicDateTime( $id, $value = null, $args = null ) {
//		var_dump(SqlQuerySingleAssoc("select * from im_tasklist where id = 6282")); die (1);

		$delta = time() - strtotime($value);
//		print "del=$delta $value<br/>";
		if ($delta < 10) $value = ETranslate("Seconds ago");
		else if ($delta < 3600) $value = round($delta/60) . " " . ETranslate("minutes ago");
		else if ($delta < 86400) $value = get_date_from_gmt(  $value,  'G:i' );
		else if ($delta < (86400 * 7)) $value = get_date_from_gmt(  $value,  'l G:i' );
		else if ($delta < (86400 * 365)) $value = get_date_from_gmt(  $value,  'd-m' );

		return self::GuiLabel($id, $value, $args);
	}

	/**
	 * @param $id
	 * @param $class
	 * @param null $value
	 * @param null $events
	 *
	 * @return string
	 */
	static function gui_input_time( $id, $class, $value = null, $events = null ) {
		$data = '<input type="time" id="' . $id . '" ';
		if ( is_null( $value ) ) {
			$value = "8:00";
		}
		if ( strlen( $value ) > 0 ) {
			$time = date( 'H:i', strtotime( $value ) );
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
	 * @param null $args
	 *
	 * @return string
	 */
	static function GuiHyperlink( $text, $link, $args = null ) {
		$data = "<a href='" . $link . "'";
		if ( $target = GetArg( $args, "target", null ) ) {
			$data .= ' target="' . $target . '"';
		}
		// if ( $color = GetArg($args, "hyperlink_color", null)) $data .= ' style="color:' . $color . '";';
		if ( $class = GetArg( $args, "class", null ) ) {
			$data .= " class = " . QuoteText( $class );
		}
		$data .= ">" . (is_numeric($text) ? $text : __( $text )) . "</a>";

		return $data;
	}

	static function GuiHeader($level, $text, $args = null)
	{
		$inline = GetArg($args, "inline", false);
		$center = GetArg($args, "center", false);
		$class = GetArg($args, "class", null);
		$close = GetArg($args, "close", true);
		if (is_array($text)) {
			var_dump($text);
			return "got array";
		}
		$data = "";
		$data  .= "<h" . $level . " ";
		if ($class) {
			$data .= "class=\"$class\"";
		} else {
			$style = "";
			if ( $inline ) $style .= 'display:inline; ';
			if ( $center ) $style .= 'text-align:center; ';
			if ( strlen( $style ) ) $data .= 'style="' . $style . '"';
		}
		$data .= ">" . ETranslate( $text );
		if ($close) $data .= "</h" . $level . ">";

		return $data;
	}

	/**
	 * @param $text
	 *
	 * @return string
	 */
	static function gui_list( $text ) {
		return "<li>" . $text . "</li>";
	}

	/**
	 * @param $text
	 *
	 * @return string
	 */
	static function gui_bold( $text ) {
		return "<B>" . $text . "</B>";
	}

	/**
	 * @param $url
	 * @param int $x
	 * @param int $y
	 *
	 * @return string
	 */
	static function gui_image( $url, $x = 0, $y = 0 ) {
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
	 * @param null $tool_tip
	 *
	 * @return string
	 */
	static function gui_div( $id, $text = null, $center = false, $tool_tip = null ) {
		$data = '<div ';
		if ( $tool_tip ) {
			$data .= 'class="tooltip" ';
		}
		$data .= 'id="' . $id . '"';
		if ( $center ) {
			$data .= ' style="text-align:center" ';
		}

		$data .= '>';
		if ( $text ) {
			$data .= $text;
		}
		if ( $tool_tip ) {
			$data .= '<span class="tooltiptext">' . $tool_tip . '</span>';
		}
		$data .= "</div>";

		return $data;
	}

	/**
	 * @param $logo_url
	 * @param int $height
	 *
	 * @return string
	 */
	static function GuiImage( $logo_url, $height = 0 ) {
		return '<img src=' . QuoteText( $logo_url ) . '  style="height: ' . $height . 'px; width: auto;">';
	}

	/**
	 * @param $id
	 * @param null $text
	 * @param null $args
	 *
	 * @return string
	 */
	static function GuiDiv( $id, $text = null, $args = null ) {
		$data = "";

		$data .= '<div ';

		if ( $style = GetArg( $args, "style", null ) ) {
			$data .= "style=\"" . $style . "\"";
		}

		if ( $class = GetArg( $args, "class", null ) ) {
			$data .= 'class="' . $class . '" ';
		}
		$data .= 'id="' . $id . '"';
		if ( GetArg( $args, "center", false ) ) {
			$data .= ' style="text-align:center" ';
		}

		$data .= '>';
		if ( $text ) {
			if (! is_string($text)) {
				print debug_trace(6);
				die ('$text is not string');
			}
			$data .= $text;
		}
		if ( $tool_tip = GetArg( $args, "tool_tip", false ) ) {
			$data .= '<span class="tooltiptext">' . $tool_tip . '</span>';
		}
		$data .= "</div>";

		return $data;
	}


	/**
	 * print string with <br/> at the end.
	 *
	 * @param null $text
	 */
	static function printbr( $text = null ) {
		if ( $text ) {
			print $text;
		}
		print "<br/>";
	}

	// TABLE functions

	/**
	 * Create html table cell - <td>
	 * Hide contents if $show is true.
	 *
	 * @param $cell
	 * @param null $id
	 * @param bool $show
	 *
	 * @param null $align
	 *
	 * @return string
	 */
	static function GuiCell($cell, $args = null)
	{
		$cell = str_replace( '\n', '<br/>', $cell );

		$data = "
		<td";
		$id = GetArg($args, "id", null);
		if ($id) $data .= " id=\"" . $id . "\"";

		$show = GetArg($args, "show", true);
		if ( ! $show ) $data .= " style=\"display:none;\"";

		$align = GetArg($args, "align", false);
		if ( $align ) $data .= ' style="text-align: ' . $align . '";';

		$width = GetArg($args, "col_width", null);
		if ($width) $data .= ' style="width:' . $args["col_width"] . '"'; // Seems not workling
		$data .= ">";

		if ( function_exists( '__' ) ) {
			if (! is_string($cell)) $cell = StringVar($cell);
			$data .= __( $cell );
		} else if ( is_array( $cell ) ) {
			$data .= CommaImplode( $cell );
		} else {
			$data .= $cell;
		}
		$data .= "</td>";

		return $data;
	}
	static function gui_cell( $cell, $id = null, $show = true, $align = null ) {
		// Preformating...
		// a) replace \n with <br/>
		// b) make url from strings start with http:// or https://

		$cell = str_replace( '\n', '<br/>', $cell );

		$data = "<td";
		if ( $id ) {
			$data .= " id=\"" . $id . "\"";
		}
		if ( ! $show ) {
			$data .= " style=\"display:none;\"";
		}
		if ( $align ) {
			$data .= ' style="text-align: ' . $align . '";';
		}
		$data .= ">";

		if ( function_exists( '__' ) ) {
			$data .= __( $cell );
		} else if ( is_array( $cell ) ) {
			$data .= CommaImplode( $cell );
		} else {
			$data .= $cell;
		}
		$data .= "</td>";

		return $data;
	}

	// Replace http:// with hyperlink
	//	if (! strstr($cell, "<a"))
	//		$cell = preg_replace(
	//			"~[[:alpha:]]+://[^<>[:space:]]+[[:alnum:]/]~",
	//			"<a href=\"\\0\">\\0</a>",
	//			$cell);

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
	static function gui_row(
		$cells, $row_id = null, $show = null, &$acc_fields = null, $col_ids = null, $style = null, $add_checkbox = false, $checkbox_class = false,
		$checkbox_events = null
	) {
		$data = "<tr";

		if (0) { // for debug
			$data .= ">";
			if ( is_array( $cells ) ) {
				foreach ( $cells as $key => $cell ) {
					print $key . ")" . $col_ids[ $key ] . " " . $cell . "<br/>";

					$data .= "<td>$cell</td>";
				}
			} else {
				$data .= "<td>$cells</td>";
			}
			$data .= ROW_END;

			return $data;
		}

		if ( $style ) {
			$data .= " " . $style;
		}

		$data .= ">";

		if ( $add_checkbox and is_array( $cells ) ) {
			array_unshift( $cells, gui_checkbox( "chk_" . $row_id, $checkbox_class, false, $checkbox_events ) );
		}

		if ( is_array( $cells ) ) {
			$i = 0;
			foreach ( $cells as $col_id => $cell ) {
				// Accumulate
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
				$data .= Core_Html::gui_cell( $cell, $cell_id, $show_cell ) . "\n";
				$i ++;
			}
		} else {
			$data .= "<td>" . $cells . "</td>";
		}
		$data .= "
</tr>";

		return $data;
	}

	/**
	 * @deprecated use Core_Html::gui_table_args
	 */
	static function gui_table(
		$rows, $id = null, $header = true, $footer = true, &$acc_fields = null, $style = null, $class = null, $show_fields = null,
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
			$row_id    = null;
			foreach ( $rows as $row ) {
				if ( ! is_null( $row ) ) {
					if ( $first_id ) {
						$row_id = array_shift( $row );
					}
					if ( $actions and ! $first_row ) {
						foreach ( $actions as $action ) {
							if ( is_array( $action ) ) {
								$text   = $action[0];
								$action = sprintf( $action[1], $row_id );
								array_push( $row, Core_Html::GuiHyperlink( $text, $action ) );

							} else {
								$h = sprintf( $action, $row_id );
								array_push( $row, $h );
							}
						}

					}
					$first_row = false;

					//				print "id= " . $id ."<br/>";
					$data .= Core_Html::gui_row( $row, $row_id, $show_fields, $acc_fields, $col_ids, null );
				}
			}
		} else {
			$data .= "<tr>" . $rows . "</tr>";
		}
		if ( $acc_fields ) {
			$array = array();
			foreach ( $acc_fields as $value ) {
				if ( is_array( $value ) ) {
					array_push( $array, $value[0] );
				} else {
					array_push( $array, $value );
				}
			}
			$data .= Core_Html::gui_row( $array );
		}

		if ( $footer ) {
			$data .= "</table>";
		}

		return $data;
	}

	/**
	 * Create html table with data supplied in two dimensional array. Can sum the content in the rows and
	 * cols.
	 *
	 * @param $input_rows
	 * @param null $id
	 * @param null $args
	 *
	 * @return string
	 * @throws Exception
	 */
	static function gui_table_args( $input_rows, $id = null, &$args = null )
	{
		$width       = GetArg( $args, "width", null );
		$bordercolor = GetArg( $args, "bordercolor", null );

		$align_table_cells = GetArg( $args, "align_table_cells", null );

		// add_checkbox should be used on multiple rows view.
		$add_checkbox = GetArg( $args, "add_checkbox", false );
		// debug_var($add_checkbox . " " . $id);
		$checkbox_class  = GetArg( $args, "checkbox_class", null );
		// print "cc= $checkbox_class id=$id<br/>";
		$checkbox_events = GetArg( $args, "checkbox_events", null );
		$prepare         = GetArg( $args, "prepare", true );
		$reverse = GetArg($args, "reverse", false);

		// Table start and end
		$footer = true;

		// Style and class.
		$style = GetArg( $args, "style", null );
		$class = GetArg( $args, "class", null );

		$show_cols = GetArg( $args, "show_cols", null );

		if ( isset( $args["edit_cols"] ) ) $args["edit_cols"]["id"] = false;

		$transpose = GetArg( $args, "transpose", false );

		if ( ! $input_rows ) return null;

		if (! is_array($input_rows)) {
			print "input row is not array";
			print debug_trace(5);
			die (1);
		}

		$rows = array();

		if ($reverse){
			$header = null;
			if (isset($input_rows["header"])) {
				$header = $input_rows["header"];
				unset( $input_rows["header"] );
			}
			$input_rows = array_reverse($input_rows, true);
			if ($header) array_unshift($input_rows, $header);
		}
		foreach ( $input_rows as $key => $input_row ) {
			if ( ! $prepare || in_array( $key, array( "checkbox", "header", "mandatory", "sums" ) ) ) {
				if ( isset( $input_row['checkbox'] ) ) $input_row['checkbox'] = '';
				$rows[ $key ] = $input_row;
			} else {
				$rows[ $key ] = Core_Data::PrepareRow( $input_row, $args, isset($args["duplicate_of"]) ? 0 : $key );
				// If prepare return null - remove it from table.
				if (! $rows[$key]) {
					unset( $rows[ $key ] );
					if (isset($args['count'])) $args['count'] --;
				}
			}
		}
		if ($accumulation_row = GetArg($args, "accumulation_row", null)) {
			$rows["sums"] = array();
			foreach ($accumulation_row as $key => $cell)
				if (is_array($cell)) $rows["sums"][$key] = $cell[0];
				else $rows["sums"][$key] = $cell;
		}

		$data = "
		<table";
		if ( $bordercolor ) {
			$data .= " bordercolor=" . $bordercolor;
		}
		if ( $class ) {
			$data .= " class=\"" . $class . "\"";
		}
		if ( $width ) {
			$data .= " width=\"" . $width . "\"";
		}
		if ( ! is_null( $id ) ) {
			if ( ! is_string( $id ) ) {
				return "bad table id";
			}
			$data .= ' id="' . $id . '"';
		}
		if ( $style ) $data .= "style=\"$style\"";
		$border = GetArg($args, "border", 1);
		if ($border) $data .= " border=\"$border\"";
		$data .= ">";

		$action_line = null;

		if ( is_array( $rows ) and $transpose ) {
			$rows = Core_Fund::array_transpose( $rows );
		}

		$line_index = 0;
		foreach ( $rows as $line_id => $line ) {
			if ( $line_id == "header" ) {
				$data .= "
<tr>";
				if ( is_array( $line ) ) {
					foreach ( $line as $col_id => $cell ) {
//						if (isset($args["skip_header_field"][$col_id])) continue;
						$data .= "<td";
						if ( isset( $args["col_width"][ $col_id ] ) ) {
							$data .= ' style="overflow: auto; width:' . $args["col_width"][ $col_id ] . '" ';
//							$data .= " width=" . $args["col_width"][ $col_id ];
						}
						$data .= ">";
						$data .= ETranslate($cell);
						$data .= "</td>";
					}
				} else {
					$data .= "
<td>$line</td>";
				}
				$data .= "</tr>";
				continue;
			}
			// print "line$line_id<br/>";
			$row_style = self::get_style($args, $line_index++);

			$data .= "
<tr $row_style>";


			if ( is_array( $line ) ) {
				$add_checkbox_line = $add_checkbox;
				foreach ( $line as $cell_id => $cell ) {
					//				print $line_id . " " . $cell_id ." " . $args["col_width"][$cell_id] . "<br/>";
					$field  = ( $transpose ? $line_id : $cell_id ); // print "field: $field ";
					$row_id = ( $transpose ? $cell_id : $line_id ); // print "row: $row_id<br/>";
					if ( $add_checkbox_line and $row_id != "acc" ) {
//						$data              .= "<td>" . gui_checkbox( "chk_" . $row_id, $checkbox_class, 0,
//								( $row_id === "header" ) ? $e = 'onchange="select_all_toggle(this, \'' . $checkbox_class . '\')"' : $checkbox_events );
						$data .= "<td>" . Core_Html::GuiCheckbox("chk_$row_id", 0, array("checkbox_class" => $checkbox_class, "events"=>$checkbox_events)) . "</td>";
						$add_checkbox_line = false;

					}
					// print "show: "; var_dump($args["show_cols"]); print "<br/>";
					$show = ( ( ( ( ! $show_cols ) or isset( $show_cols[ $field ] ) ) // Positive
					            and ! ( isset( $args["hide_cols"] ) and isset( $args["hide_cols"][ $field ] ) ) )  // Negative
					          and ( ! isset( $args["hide_rows"][ $row_id ] ) ) );

					// print "f=$field r=$row_id $show is=" . isset($args["hide_cols"][$row_id]) . "<br/>";
					// print "$line_id $cell_id<br/>";
					$cell_args = [];
					$cell_args["id"] = $field . "_" . $row_id;
					$cell_args["show"] = $show;
					$cell_args["align"] = isset( $align_table_cells[ $line_id ][ $cell_id ] ) ? $align_table_cells[ $line_id ][ $cell_id ] : null;
					if (isset($args["col_width"][$cell_id])) $cell_args["col_width"] = $args["col_width"][$cell_id];
//					var_dump($cell_args); print "<br/>";
					$data .= self::GuiCell($cell, $cell_args);
//					$data .= self::gui_cell( $cell, $field . "_" . $row_id, $show,
//						isset( $align_table_cells[ $line_id ][ $cell_id ] ) ? $align_table_cells[ $line_id ][ $cell_id ] : null );
				}
//				 $data .= "<td>" . $cell . "</td>";
			} else {
				$data .= "
<td>" . $line . "</td>";
			}
			$data .= "
</tr>";
		}

		// $data .= gui_row($acc_fields);
		if ( $footer ) {
			$data .= "
</table>";
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
	static function GuiInputDatalist( $id, $datalist, $events = null, $value = null ) {
		$data = "<input id='$id' list='$datalist' ";
		if ( $value ) {
			$data .= ' value="' . $value . '"';
		}
		if ( $events ) {
			$data .= $events;
		}
		$data .= ">";

		return $data;
	}

	/**
	 * @param $id
	 * @param $list_name
	 * @param $args
	 *
	 * @return string
	 */
	static function GuiAutoList( $id, $list_name, $args ) {
		$post_file = GetArg($args, "post_file", null);
		if ( ! $post_file ) {
			return __FUNCTION__ . ": Must send post_file";
		}
		// var_dump($args);
		// //	$selected = GetArg($args, "selected", null);
		$events         = GetArg( $args, "events", "" ) . ' onkeyup="update_list(\'' . $post_file . '\', \'' . $list_name . '\', this)"';
		$args["events"] = $events;
		$value          = GetArg( $args, "selected", null );

		$datalist_id = $id . "_list";

		$data = "<input id=\"" . $id . "\" ";
		if ( $value ) {
			$data .= "value = '" . htmlentities( $value, ENT_QUOTES ) . "' ";
		}
		$data .= "list=\"" . $datalist_id . "\" ";

		if ( $events ) {
			$data .= $events;
		}

		$data .= ">";
		$data .= '<datalist id="' . $datalist_id . '"></datalist>';

		return $data;
	}

	static function get_style($args, $line_id)
	{
		if (! $args) return "";
		if (!isset($args["line_styles"])) return "";

		return "style = \"" . $args["line_styles"][$line_id % count($args["line_styles"])] . "\"";
	}
	/**
	 * @param $args
	 * @param $table
	 * @param $values
	 */
	static function DatalistCreate( $args, $table, &$values )
	{
		$prefix = GetTablePrefix();
		$query  = GetArg( $args, "query", null );
		$id_key = GetArg( $args, "id_key", "id" );

		$order_by = GetArg( $args, "order_by", null );
		$name     = GetArg( $args, "name", "name" );

		$sql = "SELECT distinct " . $id_key . ", " . sprintf( $name, $id_key );
		if ( $order_by ) {
			$sql .= ", " . $order_by;
		}
		$sql .= " FROM " . $prefix . $table;
		if ( $query ) {
			$sql .= " where " . $query;
		}

		if ( $order_by ) {
			$sql .= " order by $order_by ";
		}

		$results = SqlQuery( $sql );

		if ( $results ) {
			while ( $row = $results->fetch_assoc() ) {
				array_push( $values, $row );
			}
		}
		array_unshift( $values, array( $id_key => 0, $name => __( "select" ) ) );
	}

	/**
	 * @param $id
	 * @param $table
	 * @param $args
	 *
	 * @return string
	 */
	static function GuiSelectTable( $id, $table, $args ) {

		if (! $table)
			die (__FUNCTION__ . ": table is missing");

		$selected             = GetArg( $args, "selected", null );
		$events               = GetArg( $args, "events", null );
		$more_values          = GetArg( $args, "more_values", null );
		$name                 = GetArg( $args, "name", "name" );
		$include_id           = GetArg( $args, "include_id", false );
		$datalist             = GetArg( $args, "datalist", false );
		$id_key               = GetArg( $args, "id_key", "id" );
		$class                = GetArg( $args, "class", null );
		$length_limit         = GetArg( $args, "length_limit", 30 );
		$multiply_choice      = GetArg( $args, "multiply_choice", false );
		$autocomplete_letters = GetArg( $args, "autocomplete_letters", null );

		$values = array();

		if ( $autocomplete_letters ) {
			// print "auto";
			return Core_Html::GuiInput( $id, null, $args );
		}

		if ( $datalist ) {
			$data = "";
			$seq  = 0;
			if ( $multiply_choice ) {
				foreach ( explode( ",", $selected ) as $select_value ) {
					$data .= gui_select_datalist( $id . '.' . $seq, $table, $table . "_values", $name, $values, $events, $select_value, $include_id, $id_key, $class );
					$seq ++;
				}
				// New one
			} else {
				$data .= Core_Html::gui_select_datalist( $id, $table, $table . "_values", $name, $values, $events, null, $include_id, $id_key, $class );
			}

			return $data;
		} else {
			if ($more_values) foreach ($more_values as $value)
				if (! in_array($value, $values))
					array_push($values, $value);

			self::DatalistCreate( $args, $table, $values );

			return self::gui_select( $id, $name, $values, $events, $selected, $id_key, $class );
			// gui_select( $id, $name, $values, $events, $selected, $id_key = "id", $class = null, $multiple = false )
		}
	}

	/**
	 * @param $id
	 * @param $table
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
	static function gui_select_datalist( $id, $table, $datalist_id, $name, $values, $events, $selected = null, $include_id = true, $id_key = null, $class = null ) {
		if ( ! $id_key ) {
			$id_key = "id";
		}

		$data = "";
		static $shown_datalist = array();

		$args          = [];
		$args["field"] = $name;
		if ( ! isset( $shown_datalist[ $datalist_id ] ) ) {
			$shown_datalist[ $datalist_id ] = 0;
			$data                           = Core_Html::TableDatalist( $datalist_id, $table, $args );
			$shown_datalist[ $datalist_id ] ++;

			if ( $id == "datalist" ) {
				return $data;
			} // Just print the datalist.
		}

		$selected_value = $selected ? $selected : "select";
		if ( $selected ) {
			foreach ( $values as $row ) {
				if ( $row[ $id_key ] == $selected ) {
					$selected_value = ( $include_id ? $row[ $id_key ] : "" ) . ")" . $row[ $name ];
				}
			}
		}

		$data .= "<input id=\"" . $id . "\" ";
		if ( $selected ) {
			$data .= "value = '" . htmlentities( $selected_value, ENT_QUOTES ) . "' ";
		}
		$data .= "list=\"" . $datalist_id . "\" ";

		if ( $events ) {
			$data .= $events;
		}

		$data .= ">";

		return $data;
	}

	/**
	 * @param $id
	 * @param $selected
	 * @param $args
	 *
	 * @return string
	 */

	static function GuiSimpleSelect( $id, $selected, $args ) {
		$values = GetArg( $args, "values", array( "Send values thru args" ) );
		$events = GetArg( $args, "events", null );
		$edit   = GetArg( $args, "edit", true );
		if ( ! $edit ) {
			if (isset($values[$selected])) return __( $values[ $selected ] );
			return __($values[sizeof($values)]);
		}
		return self::gui_simple_select( $id, $values, $events, $selected );
	}

	/**
	 * @param $id
	 * @param $values
	 * @param $events
	 * @param null $selected_key
	 * @param null $selected_value
	 *
	 * @return string
	 */
	static function gui_simple_select( $id, $values, $events, $selected_key = null, $selected_value = null ) {
		$data = "<select id=\"" . $id . "\" ";
		if ( $events ) {
			$data .= $events;
		}
		$data .= ">";

		foreach ( $values as $key => $row ) {
			$data .= "<option value=\"" . $row . "\"";
			//		print $selected . " " . $row . "<br/>";
			if ( ( $selected_key and ( $selected_key == $key ) ) or ( $selected_value and ( $selected_value == $row ) ) ) {
				$data .= " selected";
			}
			// print $selected . " " . $row["$id"] . "<br/>";
			$data .= ">";
			$data .= $row . "</option>";
		}

		$data .= "</select>";

		return $data;
	}

	static function GuiSelect($id, $selected, $args)
	{
		$values = GetArg($args, "values", null);
//		var_dump($values);
		$id_key = GetArg($args, "id_key", "id");
		$name = GetArg($args, "name", "name");
//
//		if (! GetArg($args, "edit", true)){
//			if (! is_array($selected)) $selected = array($selected);
//			$result = "";
//			foreach ($selected as $value)
//				$result .= $values[$value][$name]. ", ";
//			return rtrim($result, ", ");
//		}
		$data = "<select ";
		$size = GetArg($args, "size", null);
		if ($size) $data .= " size=\"$size\" ";
		if ( $multiple = GetArg($args, "multiple", false) ) {
			$data .= "multiple ";
		}
		$data .= "id=\"" . $id . "\" ";

		if ( $class = GetArg($args, "class", null) ) {
			$data .= ' class = "' . $class . '" ';
		}

		$events = GetArg($args, "events", null);
		if ( is_array( $events ) ) {
			SqlError( "bad events in $id" );
			var_dump( $events );
			die( 1 );
		}

		if ( $events ) $data .= $events;

		$data .= ">";

		if ( $values ) {
			foreach ( $values as $row ) {
				$data .= "<option value=\"" . $row[ $id_key ] . "\"";
				if ( $selected and ( ( $selected == $row[ $id_key ] or ( $multiple and strstr( ':' . $selected . ':', ':' . $row[ $id_key ] . ':' ) ) ) ) ) {
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
	 * @param bool $multiple
	 *
	 * @return string
	 */
	static function gui_select( $id, $name, $values, $events, $selected, $id_key = "id", $class = null, $multiple = false ) {
		$data = "<select ";
		if ( $multiple ) {
			$data .= "multiple ";
		}
		$data .= "id=\"" . $id . "\" name=\"$id\" ";

		if ( $class ) {
			$data .= ' class = "' . $class . '" ';
		}

		if ( is_array( $events ) ) {
			SqlError( "bad events in $id" );
			var_dump( $events );
			die( 1 );
		}

		if ( $events ) {
			$data .= $events;
		}

		$data .= ">";

		if ( $values ) {
			foreach ( $values as $row ) {
				if ( ! isset( $row[ $id_key ] ) ) {
//								var_dump($row);
//								print '<br/>';
//								die ($id_key . ' offset not set ' . $id);
					MyLog(__FUNCTION__, $id_key . ' offset not set ' . $id . " " . StringVar($row));
					continue;
				}
				$data .= "<option value=\"" . $row[ $id_key ] . "\"";
				if ( $selected and ( ( $selected == $row[ $id_key ] or ( $multiple and strstr( ':' . $selected . ':', ':' . $row[ $id_key ] . ':' ) ) ) ) ) {
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
//					var_dump($row); print "<br/>";
					$data .= $row[ $name ] . "</option>";
				}
			}
		}

		$data .= "</select>";

		return $data;
	}

	/**
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
	 * @deprecated use GuiSelectTable
	 */

	static function gui_select_table(
		$id, $table, $selected = null, $events = null, $more_values = null, $name = null, $where = null,
		$include_id = false, $datalist = false, $order_by = null, $id_key = null
	) {
		if ( ! $id_key ) {
			$id_key = "id";
		}

		if (! $table)
			die (__FUNCTION__ . " table is missing");
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
		// debug_var($sql);

		if ( $order_by ) {
			$sql .= " order by 3 ";
		}

		$results = SqlQuery( $sql );
		if ( $results ) {
			while ( $row = $results->fetch_assoc() ) {
				array_push( $values, $row );
			}
		} else {
			return "no results " . $sql;
		}

		if ( $datalist ) {
			return gui_select_datalist( $id, $table, $table, $name, $values, $events, $selected, $include_id, $id_key );
		} else {
			$args = array("values"=>$values,
				"events"=> $events,
				"id_key" => $id_key,
				"name"=> $name
				);
			return self::GuiSelect( $id, $selected, $args );
		}
	}

	/**
	 * @param $input_name
	 * @param null $type
	 * @param null $args
	 * @param null $data
	 *
	 * @return string|null
	 */
	static function gui_input_by_type( $input_name, $type = null, $args = null, $data = null ) {
		if (strstr($data, "<input")) return $data;
		$events = GetArg( $args, "events", null );

		switch ( substr( $type, 0, 3 ) ) {
			case 'tin':
				if ($type == 'tinyint(1)' or $type=="tiny")
					$value = Core_Html::GuiCheckbox( $input_name, $data, array("events"=>$events) );
				else
					$value = Core_Html::GuiInput( $input_name, $data, $args ); //gui_input( $input_name, $data, $field_events, $row_id );
				break;
			case 'dat':
				$value = Core_Html::GuiInputDate( $input_name,  $data, array("events"=>$events ));
				break;
			case 'var':
			case 'med':
			case 'lon':
				$length = 10;
				$r      = array();
				if ( preg_match_all( '/\(([0-9]*)\)/', $type, $r )) {
					$length = $r[1][0];
				}
				if ( $length > 100 or substr($type, 0, 3) == 'med') {
					$prepare_data = preg_replace('#<br\s*/?>#i', "\n", $data);
					$value = Core_Html::gui_textarea( $input_name, $prepare_data, $events );
				} else {
//					print "$input_name $length<br/>";
//					$args["size"] = $length;
					$value = self::GuiInput( $input_name, $data, $args ); // gui_input( $input_name, $data, $field_events, $row_id );
//					unset($args["size"]);
				}
				break;
			case 'fun':  // function
				return $data;
			case 'flo':
//					print "$input_name $length<br/>";
					$args["size"] = 4;
				$value = self::GuiInput( $input_name, $data, $args ); // gui_input( $input_name, $data, $field_events, $row_id );
					unset($args["size"]);
					break;
			default:
				// $field_events = sprintf( $events, $row_id, $key );
//				print "in=" . $input_name . "size=".$args['size'] ."<br/>";
				$value = Core_Html::GuiInput( $input_name, $data, $args ); //gui_input( $input_name, $data, $field_events, $row_id );
				break;
		}

		return $value;
	}

	/**
	 * @param $id
	 * @param $selected
	 * @param $args
	 *
	 * @return mixed|string
	 */
	static function gui_select_days( $id, $selected, $args ) {
		$edit = GetArg( $args, "edit", false );

		if ( ! $edit ) {
			$result = "";
			$days = explode(":", $selected);
			foreach ($days as $day)
				$result .= DayName($day) . ", ";

			return rtrim($result, ", ");
		}
		$days = [];
		for ( $i = 0; $i < 7; $i ++ ) {
			$days[ $i ] = array( "id" => $i, "day_name" => DayName( $i ) );
		}

		$args["values"]   = $days;
		$events           = GetArg( $args, "events", null );
		$args["multiple"] = true;

		return Core_Html::gui_select( $id, "day_name", $days, $events, $selected, "id", "class", true );
	}

	/**
	 * @param $id
	 * @param $text
	 * @param $args
	 *
	 * @return string|null
	 */
	static function GuiPulldown( $id, $text, $args ) {
		$result  = "";
		$result  .= '
	<div class="dropdown">
	<button onclick="show_menu(\'' . $id . '\')" class="dropbtn">' . __( $text ) . '</button>

	<div id="' . $id . '" class="dropdown-content">';
		$options = GetArg( $args, "menu_options", null );
		if ( ! $options ) {
			return null;
		}
		foreach ( $options as $option ) {
			$link   = $option['link'];
			$text   = $option['text'];
			$result .= '<a href="' . $link . "\">" . $text . '</a>';
		}
		$result .= '</div></div>';

		return $result;
	}

	/**
	 * @return string
	 */
	static function gui_type() {
		return "html";
	}

	/**
	 * @param $id
	 * @param $text
	 * @param null $args
	 *
	 * @return string
	 */
	static function GuiLabel($id, $text, $args= null)
	{
		$result = "<label id=" . $id . " ";
		if ( GetArg($args, "hidden", false) ) {
			$result .= 'style="display: none"';
		}
		$result .= ">" . $text . "</label>";

		return $result;
	}

	/**
	 * @param $branch
	 * @param $args
	 *
	 * @return string
	 */
	static private function GuiTreeBranch($branch, $args)
	{
		$class = GetArg($args, "class", "caret");
		$nested_class = GetArg($args, "nested_class", "nested");
		$result = "<li><span class='$class'>". $branch['title'] . "</span>";
		$result .= '<ul class="' . $nested_class . '">';
		if (isset($branch['childs']))
			foreach ($branch['childs'] as $child){
				if (is_array($child))
					$result .= self::GuiTreeBranch($child, $args);
				else
					$result .= "<li>" . $child . "</li>";
			}
		$result .= "</ul>";

		return $result;
	}

	/**
	 * @param $tree
	 * @param $id
	 *
	 * @return string
	 */
	static function GuiTree($tree, $id)
	{
		$result = "<ul id = $id>";
		foreach ($tree as $branch)
			$result .= self::GuiTreeBranch($branch, $args = array());

		$result .= "</ul>";

		return $result;
	}

	static function NavTabs($links, $args = null)
	{
		$nav_class = GetArg($args, "nav_tab_wrapper", "nav-tab-wrapper");
		$result = '<nav class=' . $nav_class . '"tab">';

		$args = [];
		$args["class"] = GetArg($args, "nav_tab", "nav-tab");

		foreach($links as $key => $link)
		{
			if (! is_array($link) or count($link) < 2){
				return "Tab elements should be seq array with 2 elements: [0]name, [1]link";
			}
			$result .= Core_Html::GuiHyperlink($link[0], $link[1], $args);
		}
		$result .= "</nav>";

		return $result;
	}

	/**
	 * @param $id sring: id of the tab group.
	 * @param $tabs array.
	 *
	 * @param null $args
	 *
	 * @return string
	 */
	static function GuiTabs(string $id, array $tabs, $args = null)
	{
		$debug = false; // ($id == "company_settings");
		$result = '<div class="tab">';

		$contents = "";
		$class = GetArg($args, "class", "tab");

		$div_args = $args;
		$div_class = "div_$class";
		$div_args["class"] = $div_class;

		$button_args = $args;
		$btn_class = "btn_$class";
		$button_args["class"] = $btn_class;
		$url = GetArg($args, "url", GetUrl(0));

		$selected_tab = GetArg($args, "st_$id", null);
		$all_loaded = GetArg($args, "tabs_load_all", false);
		if ($all_loaded) $selected_tab = null;
		if (! $all_loaded and (null == $selected_tab)){
			print "st_$id not set<br/>";
			die(1);
		}


		if ($debug) print "al=$all_loaded";

		if ($tabs) foreach ($tabs as $key => $tab)
		{
			if (! is_array($tab) or count($tab) < 3){
				var_dump($tab);
				return "Tab elements should be seq array with 3 elements: [0]name, [1]display_name, and [2]content. Count is " . count($tab);
			}
			$name = strtok($tab[0], '&'); // print "name=$name show=$selected_tab $all_loaded<br/>";
			$display_name = $tab[1];

			// To show the block?
			// Default no.
			$div_args["style"] = "display: none";
			// Unless first of all loaded
			if ($all_loaded and ($key == 0)) $div_args['style'] = 'display:block';
			// Or is selected.
			if ($selected_tab == $name) $div_args['style'] = 'display:block';

			if ($all_loaded or ($selected_tab == $name)){
				$contents .= Core_Html::GuiDiv($name, $tab[2], $div_args);
				if ($debug) print $contents;
				$selected_tab = null;
			}

			if ($all_loaded)
				$button_args["events"] = "onclick=\"selectTab(event, '$name', '$div_class', '$btn_class')\"";
			else
				$button_args["events"] = "onclick=\"window.location.href = '" . AddParamToUrl($url, "st_$id", $tab[0]) . "'\"";

			$result .= Core_Html::GuiButton("btn_tab_$name", $display_name, $button_args);
		}
		$result .= "</div>";
		$result .= "<hr>";

		$result .= $contents;
		if ($debug) print $contents;
		return $result;
	}
	/**
	 * Get record from the database and display in html table.
	 * This function defines the args for TableContent
	 *
	 * @param $table_name
	 * @param $row_id
	 * @param $args
	 *
	 * @return string|null
	 * @throws Exception
	 */
	static function GuiRowContent($table_name, $row_id, $args)
	{
		$db_prefix = GetTablePrefix();
		if (! $table_name) return null;
		$id_key = GetArg($args, "id_key", "id");
		$fields = GetArg($args, "fields", null);
		$table_id = GetArg($args, "table_id", $table_name);

		if (! isset($args["skip_id"]))	$args["skip_id"] = true;

		if (GetArg($args, "headers", null) and isset($args["headers"][0])) $args["headers"] = array_assoc($args["headers"]);

		$edit = GetArg($args, "edit", false);
		if ($edit) {
			// $args["v_checkbox"] = 1; 09/11/2020 Add v_checkbox where needed
			if (! isset($args["transpose"])) $args["transpose"] = 1;
			$args["events"] = 'onchange="changed_field(%s)"';
		}
		if ($row_id) { // Show specific record
			$sql = "select " . ($fields ? CommaImplode($fields) : "*") . " from ${db_prefix}$table_name where " . $id_key . " = " . $row_id;
			$args["row_id"] = $row_id;
		} else { // Create new one.
			if ($fields) {
				$sql = "show columns from ${db_prefix}$table_name where field in (" . CommaImplode($fields, true) . ")";
			}
			else
				$sql = "describe ${db_prefix}$table_name";
		}
		if (! defined('NOT_NULL_FLAG')) define ('NOT_NULL_FLAG', 1);
		if ($args /* and ! isset($args["sql_fields"]) */) {
			$result = SqlQuery("select * from ${db_prefix}$table_name");
			if (! $result) return null;
			$args["sql_fields"] = mysqli_fetch_fields( $result );
			if (! isset($args["mandatory_fields"])){
				$args["mandatory_fields"] = [];
				foreach ($args["sql_fields"] as $field){
					if ($field->flags & NOT_NULL_FLAG)
						$args["mandatory_fields"][$field->name] = 1;
				}
			}
		}
		return self::GuiTableContent($table_id, $sql, $args);
	}

	/**
	 * Execute SQL. If data return, return html table with the data. Otherwise return null.
	 *
	 * @param $table_id
	 * @param $sql
	 * @param $args
	 * @param null $sum_links
	 *
	 * @return string|null
	 * @throws Exception
	 */

	static function GuiTableContent($table_id, $sql, &$args = null)
	{
		$db_prefix = GetTablePrefix($table_id);
		if (! $sql)	{
			$fields = GetArg($args, "fields", '*');
			$where = GetArg($args, "where", null);
			if (is_array($fields)) $fields = CommaImplode($fields);
			$sql = "select $fields from ${db_prefix}$table_id";
			if ($where) $sql .= " where $where";
			if ($order = GetArg($args, "order_by", null)) $sql .= " " . $order;
		}

		// Fetch the data from DB or create the new row
		$rows_data = Core_Data::TableData( $sql, $args);

//var_dump($rows_data);
//		print "<table border='1'>";
//		foreach($rows_data as $key => $row) {
//			print "<b>" . $key ."</b><br/>";
//			print "<tr>";
//			foreach ( $row as $cell_id => $cell ) {
//				print $cell_id . "<br/>";
//				print "<td>$cell</td>";
//			}
//			print "</tr>";
//		}
//		print "</table>";
		if (! $rows_data)
			return null;

		$id_field = GetArg($args, "id_field", "id");
		if (isset($args["edit_cols"]))
			$args["edit_cols"][$id_field] = 0;

		if (! isset($args["form_table"])) $args["form_table"] = $table_id;

		$row_count = count( $rows_data);
		if (isset($args["count"])) $args["count"] += $row_count;

		// Convert to table if data returned.
		if ( $row_count >= 1 ) {
			if (GetArg($args, "duplicate_of", false)) $table_id .= "_new";
			return Core_Html::gui_table_args( $rows_data, $args["form_table"], $args );
		}

		return null;
	}

	/**
	 * @param $string
	 *
	 * @return mixed
	 */

// This function collects values from the table. If sql is not specified - all values are read and sent to doGuiDatalist.
	/**
	 * @param $id
	 * @param $table
	 * @param null $args
	 *
	 * @return string
	 */
	static function TableDatalist( $id, $table, $args = null)
	{
		$field = GetArg($args, "field", "field");
		$include_id = GetArg($args, "include_id", true);
		$id_field = GetArg($args, "id_field", "id");
		$sort = GetArg($args, "sort", null);

		$sql = GetArg($args, "sql", "select " . $field . ($include_id ? ", $id_field" : "") .	 " from " . $table);
		if (! strstr($sql, "sort") and $sort) $sql .= " sort by $sort ";
		if (!strstr($sql, "where")) $sql .= " where " . GetArg ($args, "query", "1");
		$values = [];

		// print "id_field: $id_field<br/>";

		$result = SqlQuery( $sql );
		while ( $row = SqlFetchAssoc($result ) ) {
			$row = apply_filters("datalist_alter", $row);
//		var_dump($row); print "<br/>";
			// print "key = " . $row[$id_field];
			array_push($values, $row);
			// $values[$row[$id_field]] = $row;
			// $row["ID"]] = $row[$field];
		}

		return self::GuiDatalist($id, $values, $id_field,  $field, $include_id);
	}

	/**
	 * @param $table_name
	 * @param $args
	 *
	 * @return string|null
	 * @throws Exception
	 */
	static function NewRow($table_name, $args)
	{
		$args["edit"] = true;
		$args["table_name"] = $table_name;
		$args['events'] = 'onchange="changed_field(\'%s\')"';
		$args["add_field_suffix"] = false;
		$args["new_row"] = true; // Selectors can use that to offer creating of new row. E.g, new project.
		$args["table_id"] = $table_name . "_new";
		$args["v_checkbox"] = true;
		if (! isset($args["hide_cols"])) $args["hide_cols"] = [];
		return self::GuiRowContent($table_name, null, $args);
	}

	/**
	 * @return string
	 */
	static function Br() {
		return '<br/>';
	}

	/**
	 * @param $text
	 *
	 * @return array
	 */
	static function html2array( $text )
	{
		require_once( ABSPATH . 'vendor/simple_html_dom.php' );

		$dom = \Dom\str_get_html( $text );
		$array = array();

		foreach ( $dom->find( 'tr' ) as $row ) {
			$new_row = array();
			foreach ( $row->find( 'td' ) as $cell ) {
				array_push( $new_row, $cell->plaintext );
			}
			array_push( $array, $new_row );
		}

		return $array;
	}
	static function HeaderText( $args = null ) {
		global $business_info;
		global $logo_url;
		$style_file = GetArg( $args, "css", null );

		$rtl          = GetArg( $args, "rtl", ( function_exists( "is_rtl" ) ? is_rtl() : false ) );
		$print_logo   = GetArg( $args, "print_logo", true );
		$script_files = GetArg( $args, "script_files", false );
		$close_header = GetArg( $args, "close_header", true );
		$greeting     = GetArg( $args, "greeting", false );

		$text = "";

		$text .= '<html';
		if ( $rtl ) {
			$text .= ' dir="rtl"';
		}
		$text .= '>';
		$text .= '<head>';
		$text .= '<meta http-equiv="content-type" content="text/html; charset=utf-8">';
		if (GetArg($args, "viewport"))
		{
			$text .= '<meta name="viewport" content="initial-scale=1.0, minimum-scale=1.0, user-scalable=no" />';
		}
		$text .= '<title>';
		if ( defined( $business_info ) ) {
			$text .= $business_info;
		}
		$text .= '</title>';

		// print "loading..."; var_dump($script_files); print "<br/>";
//		$text .= load_scripts( $script_files );
//		if ( isset( $style_file ) ) {
//			$text .= load_style( $style_file );
//		}

		if ( $close_header ) {
			$text .= '</head>';
		}

		$table = array();
		$row   = array();
		if ( $greeting ) {
			$row [] = greeting( $args );
		}
		if ( $print_logo and $logo_url ) {
			$row []                    = '<img src=' . $logo_url . '  style="height: 100px; width: auto;">'; // Todo: use GuiImage
			$table []                  = $row;
			$args["align_table_cells"] = array( array( null, "left" ) );
			$text                      .= Core_Html::gui_table_args( $table, "header", $args );
		}

		return $text;
	}

	static function PageLinks($args)
	{
		$row_count = GetArg($args, "row_count", null);
		$page_number = GetArg($args, "page_number", 1);
		$rows_per_page = GetArg($args, "rows_per_page", 10);

//		var_dump($args);
//		print "rpp=$rows_per_page rc=$row_count<br/>";
		if ($rows_per_page and ($row_count > $rows_per_page)) {
			$total_page_number = ceil($row_count / $rows_per_page);
//			print "tpn=$total_page_number<br/>";

			$result = "<div style='text-align: center; direction: ltr'>";
			if ($page_number > 1) {
				$result .= self::GuiHyperlink( "<<", AddToUrl( "page_number", 1 ) ) . " ";
				$result .= self::GuiHyperlink( "<", AddToUrl( "page_number", $page_number - 1 ) ) . " ";
			}
			for ($i = max(1, $page_number - 3); $i <= min ($total_page_number, $page_number + 3); $i++) {
				$text = $i;
				if ($i == $page_number) $text = "<b>$i</b>";
				$result .= self::GuiHyperlink( $text, AddToUrl( "page_number", $i ) ) . " ";
			}

			if ($page_number < $total_page_number) {
				$result .= self::GuiHyperlink( ">", AddToUrl( "page_number", $page_number + 1 ) ) . " ";
				$result .= self::GuiHyperlink( ">>", AddToUrl( "page_number", $total_page_number ) ) . " ";
			}

			$result .= "</div>";

			return $result;
		}
		return "";
	}

	static function load_scripts( $script_file = false ) {
		$text = "";
		if ( $script_file ) {
			// print "Debug: " . $script_file . '<br/>';
			// var_dump($script_file);
			do {
				if ( $script_file === true ) {
					$text .= '<script type="text/javascript" src="/core/gui/client_tools.js"></script>';
					break;
				}
				if ( is_string( $script_file ) ) {
					$text .= '<script type="text/javascript" src="' . $script_file . '"></script>';
					break;
				}
				if ( is_array( $script_file ) ) {
					foreach ( $script_file as $file ) {
						if ( strstr( $file, 'php' ) ) {
							$text .= GetContent( $file );
						} else {
							$text .= '<script type="text/javascript" src="' . $file . '"></script>';
						}
					}
					break;
				}
				print $script_file . " not added<br/>";
			} while ( 0 );
		}
		return $text;
	}

	static function is_active($id, $value, $args)
	{
		if ($value) return self::GuiLabel($id, ETranslate("Active"), $args);
		return self::GuiLabel($id, ETranslate("Not active"), $args);
	}
}
function gui_checkbox( $id, $class, $value = false, $events = null ) {
	return Core_Html::GuiCheckbox($id, $value, array("events" => $events, "checkbox_class" => $class));
}
