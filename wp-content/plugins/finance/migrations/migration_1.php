<?php

return function() : bool {
	$db_prefix = GetTablePrefix();
	$sql = "alter table ${db_prefix}suppliers add supplier_description varchar(200)";
	return (SqlQuery($sql) != null);
};
