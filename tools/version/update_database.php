<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 23/01/19
 * Time: 11:36
 */

if ( ! defined( "ROOT_DIR" ) ) {
	define( 'ROOT_DIR', dirname( dirname( dirname( __FILE__ ) ) ) );
}

require_once( ROOT_DIR . '/tools/im_tools_light.php' );

$version = get_param( "version" );

switch ( $version ) {
	case "17":
		version17();
		break;
}
die ( 1 );

function version17() {
	sql_query( "CREATE FUNCTION task_status(task_id INT)
  RETURNS TEXT CHARSET 'utf8'
  BEGIN
    declare _status int;
    select status into _status from im_tasklist
    where id = task_id;

    return _status;
  END;
" );
}
// Version 1.6
//sql_query( "ALTER TABLE im_business_info
//ADD net_total DOUBLE;
//" );
//
//sql_query( "ALTER TABLE im_delivery
//ADD draft_reason VARCHAR(50);
//" );

// Version 1.7
//sql_query( "ALTER TABLE im_suppliers
//drop invoice_email;
//" );

sql_query( "ALTER TABLE im_supplies
ADD picked BIT  
" );

sql_query( "ALTER TABLE im_suppliers
ADD invoice_email VARCHAR(50)  
  CHARACTER SET utf8
  COLLATE utf8_general_ci;
" );

sql_query( "ALTER TABLE im_business_info
ADD invoice_file VARCHAR(200)  
  CHARACTER SET utf8
  COLLATE utf8_general_ci

" );

sql_query( "ALTER TABLE im_business_info
   add `occasional_supplier` varchar(50) CHARACTER SET utf8 DEFAULT NULL;

" );

sql_query( "ALTER TABLE im_business_info
ADD invoice INTEGER(10);  
" );

sql_query( "ALTER TABLE im_business_info
ADD document_type INT(2) DEFAULT '1' NOT NULL;
" );

sql_query( "ALTER TABLE im_business_info
MODIFY week DATE;
" );

sql_query( "ALTER TABLE im_business_info
MODIFY delivery_fee FLOAT;
" );

sql_query( "ALTER TABLE im_suppliers
ADD auto_order_day INT(11),
add invoice_email VARCHAR(50);
" );

sql_query( "ALTER TABLE im_suppliers
ADD auto_order_day INT(11),
ADD invoice_email VARCHAR(50);
" );


print "done";

