<?php

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
	static function gui_label( $id, $text, $hidden = false ) {
		$result = "<label id=" . $id . " ";
		if ( $hidden ) {
			$result .= 'style="display: none"';
		}
		$result .= ">" . $text . "</label>";

		return $result;
	}

	/**
	 * create html button
	 *
	 * @param $id
	 * @param $text
	 * @param $args
	 *
	 * @return string
	 */
	static function GuiButton($id, $text, $args)
	{
		$result = "<button id=\"$id\"";
		if ($class = GetArg($args, "class", null)) $result .= " class=\"$class\"";
		if ($events = GetArg($args, "events", null)) $result .= " $events ";
		if ($action = GetArg($args, "action", null)) $result .= " onclick=\"$action\" ";
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
//	static function gui_checkbox( $id, $class, $value = false, $events = null ) {
		$class = GetArG($args, "class", "class");
		$events = GetArG($args, "events", null);
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

	/**
	 * @param $id
	 * @param null $value
	 * @param null $args
	 *
	 * @return string
	 */
	static function GuiInput( $id, $value = null, $args = null ) {
		//	print __FUNCTION__ . "<br/>";
		$name   = GetArg( $args, "name", $id );
		$events = GetArg( $args, "events", null );
		$class  = GetArg( $args, "class", null );
		$size   = GetArg( $args, "size", null );

		if ( is_null( $id ) ) {
			$id = $name;
		}
		$data = '<input type="text" name="' . $name . '" id="' . $id . '" ';
		if ( is_array( $value ) ) {
			debug_var( $value );
		}
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
		$data = '<textarea name="' . $name . '" id="' . $name . '"';
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
	static function GuiDatalist( $id, $values, $id_field, $field_name, $include_id = false ) {
		$debug = 1;

		$data = "<datalist id=\"" . $id . "\">";

		foreach ( $values as $row ) {
			// var_dump($row); print "<br/>";
			$id_text = "";
			if ( $include_id ) {
				if ( ! isset( $row[ $id_field ] ) and $debug ) {
					print __FUNCTION__ . ": " . $id_field . " is missing. ";
					var_dump( $row );
					print "<br/>";
					print sql_trace();
					die( 1 );
				}
				$id_text = $row[ $id_field ] . ")";
			}
			$data .= "<option value=\"" . $id_text . htmlspecialchars( $row[ $field_name ] ) . "\"";
			foreach ( $row as $key => $data_value ) {
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

	static function gui_datalist( $id, $table, $field, $include_id = false ) {
		$args               = [];
		$args["include_id"] = $include_id;
		$args["field"]      = $field;

		return TableDatalist( $id, $table, $args );
	}

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
	 *
	 * @return string
	 */
	static function gui_input_date( $id, $class, $value = null, $events = null ) {
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

		$data .= ">" . __( $text ) . "</a>";

		return $data;
	}

	/**
	 * @param $level
	 * @param $text
	 * @param bool $center
	 *
	 * @param bool $inline
	 *
	 * @return string
	 */
	static function gui_header( $level, $text, $center = false, $inline = false ) {
//		debug_var($text);
		$data = "";
		// if ($inline) $data .= "<style>h1 {display: inline;}</style>";
		$data  .= "<h" . $level . " ";
		$style = "";
		if ( $inline ) {
			$style .= 'display:inline; ';
		}
		if ( $center ) {
			$style .= 'text-align:center; ';
		}
		if ( strlen( $style ) ) {
			$data .= 'style="' . $style . '"';
		}
		$data .= ">" . __( $text ) . "</h" . $level . ">";

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

		if ( $style ) {
			$data .= " " . $style;
		}

		$data .= ">";

		if ( $add_checkbox and is_array( $cells ) ) {
			array_unshift( $cells, gui_checkbox( "chk_" . $row_id, $checkbox_class, false, $checkbox_events ) );
		}

		if ( is_array( $cells ) ) {
			$i = 0;
			foreach ( $cells as $cell ) {
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
				$data .= Core_Html::gui_cell( $cell, $cell_id, $show_cell );
				$i ++;
			}
		} else {
			$data .= "<td>" . $cells . "</td>";
		}
		$data .= "</tr>";

		return $data;
	}

	/**
	 * @deprecated use gui_table_args
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
					$data .= gui_row( $row, $row_id, $show_fields, $acc_fields, $col_ids, null );
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
	 *
	 * @param $input_rows
	 * @param null $id
	 * @param null $args
	 *
	 * @return string
	 * @throws Exception
	 */
	static function gui_table_args( $input_rows, $id = null, $args = null )
	{
		$debug       = GetArg( $args, "debug", false );
		$width       = GetArg( $args, "width", null );
		$bordercolor = GetArg( $args, "bordercolor", null );

		$align_table_cells = GetArg( $args, "align_table_cells", null );

		// add_checkbox should be used on multiple rows view.
		$add_checkbox = GetArg( $args, "add_checkbox", false );
		// debug_var($add_checkbox . " " . $id);
		$checkbox_class  = GetArg( $args, "checkbox_class", null );
		$checkbox_events = GetArg( $args, "checkbox_events", null );
		$prepare         = GetArg( $args, "prepare", true );

		// Table start and end
		$header = true;
		$footer = true;

		// Style and class.
		$style = GetArg( $args, "style", null );
		$class = GetArg( $args, "class", null );

		$show_cols = GetArg( $args, "show_cols", null );

		if ( isset( $args["edit_cols"] ) ) {
			$args["edit_cols"]["id"] = false;
		}

		$transpose = GetArg( $args, "transpose", false );

		if ( ! $input_rows ) {
			if ( $debug ) {
				print "no input rows";
			}

			return null;
		}

		$rows = array();

		foreach ( $input_rows as $key => $input_row ) {
			if ( ! $prepare || in_array( $key, array( "checkbox", "header", "mandatory", "sums" ) ) ) {
				if ( isset( $input_row['checkbox'] ) ) {
					$input_row['checkbox'] = '';
				}
				$rows[ $key ] = $input_row;
			} else {
				$rows[ $key ] = Core_Data::PrepareRow( $input_row, $args, $key );
			}
		}

		if ( $header ) {
			$data = "<table";
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
			$data .= " border=\"1\"";
			$data .= ">";
		}
		$action_line = null;

		if ( $style ) {
			$data .= "<style>" . $style . "</style>";
		}
		if ( is_array( $rows ) and $transpose ) {
			$rows = array_transpose( $rows );
		}

		foreach ( $rows as $line_id => $line ) {
			if ( $line_id == "header" ) {
				$data .= "<tr>";
				if ( is_array( $line ) ) {
					foreach ( $line as $col_id => $cell ) {
						$data .= "<td";
						if ( isset( $args["col_width"][ $col_id ] ) ) {
							$data .= " width=" . $args["col_width"][ $col_id ];
						}
						$data .= ">";
						$data .= $cell;
						$data .= "</td>";
					}
				} else {
					$data .= "<td>$line</td>";
				}
				$data .= "</tr>";
				continue;
			}
			// print "line$line_id<br/>";
			$data .= "<tr>";

			if ( is_array( $line ) ) {
				$add_checkbox_line = $add_checkbox;
				foreach ( $line as $cell_id => $cell ) {
					//				print $line_id . " " . $cell_id ." " . $args["col_width"][$cell_id] . "<br/>";
					$field  = ( $transpose ? $line_id : $cell_id ); // print "field: $field ";
					$row_id = ( $transpose ? $cell_id : $line_id ); // print "row: $row_id<br/>";
					if ( $add_checkbox_line and $row_id != "acc" ) {
						$data              .= "<td>" . gui_checkbox( "chk_" . $row_id, $checkbox_class, 0,
								( $row_id === "header" ) ? $e = 'onchange="select_all_toggle(this, \'' . $checkbox_class . '\')"' : $checkbox_events );
						$add_checkbox_line = false;
					}
					// print "show: "; var_dump($args["show_cols"]); print "<br/>";
					$show = ( ( ( ( ! $show_cols ) or isset( $show_cols[ $field ] ) ) // Positive
					            and ! ( isset( $args["hide_cols"] ) and isset( $args["hide_cols"][ $field ] ) ) )  // Negative
					          and ( ! isset( $args["hide_rows"][ $row_id ] ) ) );

					// print "f=$field r=$row_id $show is=" . isset($args["hide_cols"][$row_id]) . "<br/>";
					// print "$line_id $cell_id<br/>";
					$data .= self::gui_cell( $cell, $field . "_" . $row_id, $show,
						isset( $align_table_cells[ $line_id ][ $cell_id ] ) ? $align_table_cells[ $line_id ][ $cell_id ] : null );
				}
//				 $data .= "<td>" . $cell . "</td>";
			} else {
				$data .= "<td>" . $line . "</td>";
			}
			$data .= "</tr>\n";
		}

		// $data .= gui_row($acc_fields);
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
		if ( ! $args ) {
			$args = [];
		}
		// var_dump($args);
		// //	$selected = GetArg($args, "selected", null);
		$events         = GetArg( $args, "events", "" ) . ' onkeyup="update_list(\'' . $list_name . '\', this)"';
		$args["events"] = $events;
		$value          = GetArg( $args, "selected", null );

//		print "VVV=$value<br/>";

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

	/**
	 * @param $args
	 * @param $table
	 * @param $values
	 */
	static function DatalistCreate( $args, $table, &$values ) {
		$query  = GetArg( $args, "query", null );
		$id_key = GetArg( $args, "id_key", "id" );

		$order_by = GetArg( $args, "order_by", null );
		$name     = GetArg( $args, "name", "name" );

		$sql = "SELECT distinct " . $id_key . ", " . sprintf( $name, $id_key );
		if ( $order_by ) {
			$sql .= ", " . $order_by;
		}
		$sql .= " FROM " . $table;
		if ( $query ) {
			$sql .= " where " . $query;
		}

		if ( $order_by ) {
			$sql .= " order by $order_by ";
		}

		$results = sql_query( $sql );

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

		//	print __FUNCTION__ . "<br/>";
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
			return GuiInput( $id, null, $args );
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
				$data .= gui_select_datalist( $id, $table, $table . "_values", $name, $values, $events, null, $include_id, $id_key, $class );
			}

			return $data;
		} else {
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
	 * @param $value
	 * @param $args
	 *
	 * @return string
	 */

	static function GuiSimpleSelect( $id, $value, $args ) {
		$values = GetArg( $args, "values", array( "Send values thru args" ) );
		$events = GetArg( $args, "events", null );
		$edit   = GetArg( $args, "edit", true );
		if ( ! $edit ) {
			return __( $values[ $value ] );
		}

		return gui_simple_select( $id, $values, $events, $value );
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
		$data .= "id=\"" . $id . "\" ";

		if ( $class ) {
			$data .= ' class = "' . $class . '" ';
		}

		if ( is_array( $events ) ) {
			sql_error( "bad events in $id" );
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
					//			var_dump($row);
					//			print '<br/>';
					//			die ($id_key . ' offset not set ' . $id);
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
			return gui_select_datalist( $id, $table, $table, $name, $values, $events, $selected, $include_id, $id_key );
		} else {
			return gui_select( $id, $name, $values, $events, $selected, $id_key );
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
		$events = GetArg( $args, "events", null );
//		print "type=$type<br/>";
		switch ( substr( $type, 0, 3 ) ) {
			case 'dat':
				$value = Core_Html::gui_input_date( $input_name, null, $data, $events );
				break;
			case 'var':
				$length = 10;
				$r      = array();
				if ( preg_match_all( '/\(([0-9]*)\)/', $type, $r ) ) {
					$length = $r[1][0];
				}
				if ( $length > 100 ) {
					$value = Core_Html::gui_textarea( $input_name, $data, $events );
				} else {
					$value = self::GuiInput( $input_name, $data, $args ); // gui_input( $input_name, $data, $field_events, $row_id );
				}
				break;
			case 'fun':  // function
				return $data;
			default:
				// $field_events = sprintf( $events, $row_id, $key );
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
			$f      = strtok( $selected, ":" );
			$result = DayName( $f );
			while ( $z = strtok( ":" ) ) {
				$result .= ", " . DayName( $z );
			}

			return $result;
		}
		$days = [];
		for ( $i = 0; $i < 7; $i ++ ) {
			$days[ $i ] = array( "id" => $i, "day_name" => DayName( $i ) );
		}

		$args["values"]   = $days;
		$events           = GetArg( $args, "events", null );
		$args["multiple"] = true;

		return gui_select( $id, "day_name", $days, $events, $selected, "id", "class", true );
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
		} // die ("no options for " . __FUNCTION__ . " " . $id);
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

	/**
	 * @param $tabs
	 *
	 * @return string
	 */
	static function GuiTabs($tabs)
	{
		$result = '<div class="tab">';

		$args = [];
		$args["class"] = "tablinks";
		$contents = "";
		$div_args = array("class" => "tabcontent");
		$shown_tab = GetArg($args, "show_tab", 0);
		$tab_index = 0;
		foreach ($tabs as $tab)
		{
			if (! is_array($tab) or count($tab) < 3){
				return "Tab elements should be indexed array with 3 elements: name, display_name, and content";
			}
			$name = $tab[0];
			$display_name = $tab[1];
//			$result .= 'style="display: none"';

			$div_args["style"] = (($tab_index == $shown_tab) ? 'display: block': "");
			$contents .= Core_Html::GuiDiv($name, Core_Html::gui_header(2, $name) . $tab[2], $div_args);

			$args["events"] = "onclick=\"selectTab(event, '$name', 'tabcontent')\"";
			$result .= Core_Html::GuiButton("btn_tab_$name", $display_name, $args);
			$tab_index++;
		}
		$result .= "</div>";

		$result .= $contents;
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
		$id_key = GetArg($args, "id_key", "id");
		$fields = GetArg($args, "fields", null);
		$table_id = GetArg($args, "table_id", $table_name);

		if (! isset($args["skip_id"]))	$args["skip_id"] = true;

		if (GetArg($args, "headers", null) and isset($args["headers"][0])) $args["headers"] = array_assoc($args["headers"]);

		$edit = GetArg($args, "edit", false);
		if ($edit) {
			$args["v_checkbox"] = 1;
			if (! isset($args["transpose"])) $args["transpose"] = 1;
			$args["events"] = "onchange=changed_field(%s)";
		}
		if ($row_id) { // Show specific record
			$sql = "select " . ($fields ? CommaImplode($fields) : "*") . " from $table_name where " . $id_key . " = " . $row_id;
			$args["row_id"] = $row_id;
		} else { // Create new one.
			if ($fields) {
				$sql = "show columns from $table_name where field in ( " . CommaImplode($fields, true) . ")";
			}
			else
				$sql = "describe $table_name";
		}
		if (! defined('NOT_NULL_FLAG')) define ('NOT_NULL_FLAG', 1);
		if ($args /* and ! isset($args["sql_fields"]) */) {
			$result = sql_query("select * from $table_name");
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
		if (! $sql)	{
			$fields = GetArg($args, "fields", '*');
			$where = GetArg($args, "where", null);
			if (is_array($fields)) $fields = CommaImplode($fields);
			$sql = "select $fields from $table_id";
			if ($where) $sql .= " where $where";
		}

		// Fetch the data from DB or create the new row
		$rows_data = Core_Data::TableData( $sql, $args);

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
			$html = Core_Html::gui_table_args( $rows_data, $table_id, $args );
			return $html;
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
		$include_id = GetArg($args, "include_id", false);
		$sql = GetArg($args, "sql", "select " . $field . ($include_id ? ", id" : "") .	 " from " . $table);
		if (!strstr($sql, "where")) $sql .= " where " . GetArg ($args, "query", "1");
		$id_field = GetArg($args, "id_field", "id");
		$values = [];

		// print "id_field: $id_field<br/>";

		$result = sql_query( $sql );
		// print $sql . "<br/>";
		while ( $row = sql_fetch_assoc($result ) ) {
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
		if (! isset($args["hide_cols"])) $args["hide_cols"] = [];
		$row = self::GuiRowContent($table_name, null, $args);
		return $row;
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
		require_once("wp-content/plugins/flavor/includes/core/data/im_simple_html_dom.php");

		$dom   = im_str_get_html( $text );
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
	function HeaderText( $args = null ) {
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
		$text .= '<title>';
		if ( defined( $business_info ) ) {
			$text .= $business_info;
		}
		$text .= '</title>';

		// print "loading..."; var_dump($script_files); print "<br/>";
		$text .= load_scripts( $script_files );
		if ( isset( $style_file ) ) {
			$text .= load_style( $style_file );
		}

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

		if ( function_exists('gui_type') and gui_type() == "html" ) {
			return $text;
		}

		return strip_tags( $text );
	}

}

if (!function_exists('gui_checkbox'))
{

	function gui_checkbox( $id, $class, $value = false, $events = null ) {
		return Core_Html::GuiCheckbox($id, $value, array("events" => $events, "class" => $class));
	}
}