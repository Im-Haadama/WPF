<?php

class Core_Data
{
	protected static $_instance = null;

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Core_Data constructor.
	 */
	public function __construct() {
	}

	static function handle_operation( $operation )
	{
		switch ($operation){
			case "data_auto_list":
				$prefix = get_param("prefix", true);

				$lists = array("products" => array("table" => "im_products", "field_name" =>'post_title', "include_id" => 0, "id_field" => "ID"),
				"tasks" => array("table"=>"im_tasklist", "field_name" => "task_description", "include_id" => 1, "id_field" => "id", "query" => " status = 0"),
				"users" => array("table" => "wp_users", "field_name" => "display_name", "id_field" => "ID"));
				$list = get_param("list", true);
				if (! isset($lists[$list])) die ("Error: unknown list " . $list);
				$table_name = $lists[$list]["table"];
				$field = $lists[$list]["field_name"];

				$args = $lists[$list];
				$args["datalist"] = $list . "_list";

				print auto_list($table_name, $field, $prefix, $args);
				return true;
			case "data_update":
				$table_name = get_param("table_name", true);
				return self::update_data($table_name);

			case "data_save_new":
				$table_name = get_param("table_name");
				return self::SaveNew($table_name);

		}
	}

	static function SaveNew($table_name)
	{
		$ignore_list = ["dummy", "operation", "table_name"];
		$sql    = "INSERT INTO $table_name (";
		$values = "values (";
		$first  = true;
		$sql_values = array();
		foreach ( $_GET as $key => $value ) {
			if (in_array($key, $ignore_list))
				continue;
			if ( ! $first ) {
				$sql    .= ", ";
				$values .= ", ";
			}
			$sql    .= $key;
			$values .= "?"; // "\"" . $value . "\"";
			$first  = false;

			$sql_values[$key] = $value;
		}
		$sql    .= ") ";
		$values .= ") ";
		$sql    .= $values;

		$stmt = sql_prepare($sql);
		sql_bind($table_name, $stmt, $sql_values);
		if (!$stmt -> execute())
			sql_error($sql);

		return sql_insert_id();
	}

	static function update_data($table_name)
	{
		// TODO: adding meta key when needed(?)
		global $meta_table_info;

		$row_id = intval(get_param("id", true));

		// Prepare sql statements: primary and meta tables;
		$values = self::data_parse_get($table_name, array("search", "operation", "table_name", "id", "dummy"));

		foreach ($values as $tbl => $changed_values)
		{
			foreach ($changed_values as $changed_field => $changed_pair){
//				print $changed_field . " " . $changed_pair[0] . "<br/>";
				$changed_value = $changed_pair[0];
				$is_meta = $changed_pair[1];
				if (sql_type($table_name, $changed_field) == 'date' and strstr($changed_value, "0001")) {
					$sql = "update $table_name set $changed_field = null where id = " . $row_id;
					// print $sql;
					if ($row_id) sql_query($sql);
					continue;
				}
				if ($is_meta){
					if (! isset($meta_table_info)) return false;
					$sql = "update $tbl set " . $meta_table_info[$tbl]['value'] . "=?" .
					       " where " . $meta_table_info[$tbl]['key'] . "=? " .
					       " and " . $meta_table_info[$tbl]['id'] . "=?";
				}
				else
					$sql = "update $table_name set $changed_field =? where id =?";

//				 print $sql;
				$stmt = sql_prepare($sql);
				if (! $stmt) return false;
				if ($is_meta){
					if (! sql_bind($tbl, $stmt,
						array($meta_table_info[$tbl]['value'] => $changed_value,
						      $meta_table_info[$tbl]['key'] => $changed_field,
						      $meta_table_info[$tbl]['id'] => $row_id))) return false;
				} else {
					if ( ! sql_bind($table_name, $stmt,
						array(
							$changed_field => $changed_value,
							sql_table_id($table_name)  => $row_id
						) ) ) {
						return false;
					}
				}
				if (!$stmt->execute()) {
					print "Update failed: (" . $stmt->errno . ") " . $stmt->error . " " . $sql;
					die(2);
				}
			}
		}
		return true;
	}

	static function data_parse_get($table_name, $ignore_list) {
		$debug = false; // (1== get_user_id());
		$values =array();
		foreach ( $_GET as $key => $value ) {
			$value = stripcslashes($value);
			if ( in_array( $key, $ignore_list ) ) {
				continue;
			}
			$tbl   = $table_name;
			$field = $key;
			$meta  = false;
			if ( $st = strpos( $key, "/" ) ) {
				$tbl   = substr( $key, 0, $st );
				$field = substr( $key, $st + 1 );
				$meta  = true;
			}
			if ( ! isset( $values[ $tbl ] ) ) {
				$values[ $tbl ]  = array();
			}

			if ($debug) print "parse: $key $value<br/>";
			$values[ $tbl ][ $field ] = array( $value, $meta );
		}
		return $values;
	}


}