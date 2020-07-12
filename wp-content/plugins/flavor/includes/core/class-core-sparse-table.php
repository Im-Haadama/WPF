<?php

class Core_Sparse_Table {
	private $data;
	private $empty_line;
	private $add_totals;

	/**
	 * Core_Sparse_Table constructor.
	 */
	public function __construct($header, $add_totals = false) {
		$this->add_totals = $add_totals;
		$this->data["header"] = $header; // E.g: array("name" => "סיכום הזמנות");
		$this->empty_line = array("name" => 'here');
	}

	public function AddColumn($id, $name, $link_format) // optional - to add certain columns first (like baskets).
	{
		if (! $id) {
			print debug_trace();
			die ( "id is missing" );
		}
		$this->empty_line[$id] = '';
		$link = sprintf($link_format, $id);
		$this->data["header"][$id] = Core_Html::GuiHyperlink($name, $link);
	}

	public function AddRow($id, $name, $link_format = null) // Add Row to table. The link is to jump to the item.
	{
		if (! $id) {
			print debug_trace();
			die ( "id is missing" );
		}
		$this->data[$id] = $this->empty_line;
		if ($link_format)
			$link = sprintf($link_format, $id);
		else $link = $name;
//		print __FUNCTION__ . ": $id $name $link<br/>";
		$this->data[$id]["name"] = Core_Html::GuiHyperlink($name, $link);
	}

	public function AddItem($row, $col, $value, $col_name)
	{
		$this->data[$row][$col] = $value;
		if ($this->add_totals){
			if (! isset($this->data["total"][$col]))
				$this->data["total"][$col] = 0;
			$this->data["total"][$col] += $value;
		}
		if (! isset($this->data["header"][$col])) {
			$this->data["header"][ $col] = $col_name;
		}
	}

	public function GetTable()
	{
//		var_dump($this->data);
//		foreach ($this->data as $key => $row) {
//			print "<br/>$key: ";
//
//			foreach ( $row as $vkey => $cell )
//				print "<br/>===[$vkey]$cell, ";
//		}
		$table = [];
		$table["header"] = [];
		if ($this->add_totals)
			$table["total"] = [];
		foreach ($this->data as $row_id => $row){
			$table[$row_id] = array();
			$has_items = false;
			foreach ($this->data["header"] as $col_id => $c) {
				if (isset($this->data[$row_id][$col_id]) and ($this->data[$row_id][$col_id] != '') and ($col_id != 'name')){
					$has_items = true;
				}
				if ($this->add_totals and ((isset($this->data["total"][$row_id]) and ($col_id > 0)))) {
					$table["total"][$col_id] = $this->data["total"][$col_id];
				}
				$table[ $row_id ][ $col_id ] = ( isset( $this->data[ $row_id ][ $col_id ] ) ? $this->data[ $row_id ][ $col_id ] : '' );
			}
			if (($row_id > 0) and ! $has_items) {
				unset ($table[$row_id]);
			}
		}
		if ($this->add_totals)
			$table["total"]["name"] = 'סה"כ';
		$args = array("class" => "widefat", "line_styles" => array('background: #DDD','background: #EEE', 'background: #FFF') );

		if (($this->add_totals and count($table) > 2) or (! $this->add_totals and count($table) >1 ))
			return Core_Html::gui_table_args($table, "table", $args);
		return null;
	}
}