<?php

return function() : bool {
	$db_prefix = GetTablePrefix();
	$sql = "alter table ${db_prefix}suppliers add is_active bool";
	return (SqlQuery($sql) != null);
};
