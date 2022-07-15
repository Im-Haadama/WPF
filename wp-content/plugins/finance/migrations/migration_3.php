<?php

return function() : bool {
	$db_prefix = GetTablePrefix();
	SqlQuery("drop function if exists client_payment_method");
	return SqlQuery("create function client_payment_method(_user_id int)
returns text charset utf8
DETERMINISTIC
BEGIN
declare _method_id int;
declare _name VARCHAR(50) CHARSET 'utf8';
select meta_value into _method_id from wp_usermeta where user_id = _user_id and meta_key = 'payment_method';
select name into _name from im_payments where id = _method_id;

return _name;
END;

") != null;

};
