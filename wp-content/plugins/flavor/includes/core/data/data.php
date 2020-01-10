<?php

print sql_trace();

die ("don't");

if (! function_exists('data_inactive')) {
// $ignore_list = array("search", "operation", "table_name", "id", "dummy");


function data_delete($table_name, $row_may_ids)
{
	// TODO: adding meta key when needed(?)
	if (is_array($row_may_ids)) {
		foreach ( $row_may_ids as $id )
			if ( ! data_delete( $table_name, $id ) ) return false;
		return true;
	}
	$sql = "delete from $table_name where id = $row_may_ids";
//	print $sql;
	if (! sql_query($sql)) return false;
	return true;
}

// For now use escape_string and not bind. Uncaught Error: Call to undefined method mysqli_stmt::get_result


//($table_name, $field, $prefix, $args);

}

