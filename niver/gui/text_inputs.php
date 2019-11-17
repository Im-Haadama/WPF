<?php

function gui_header($level, $text) { return $text . PHP_EOL; }

function gui_type() { return "text"; }
// function gui_table($array)

function gui_table_args($input_rows, $id = null, $args = null)
{
	// for now without prepare and such.
	foreach ($input_rows as $row) {
		if (is_array($row))
			foreach ($row as $item)
				print $item . "\t";
			else print $row . "\t";
		print PHP_EOL;
	}
}