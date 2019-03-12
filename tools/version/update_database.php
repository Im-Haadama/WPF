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
	case "16":
		version16();
		break;
	case "17":
		version17();
		break;
	case "tasklist":
		create_tasklist();
		break;
	default:
		die( "no valid option selected" );

}
print "done";
die ( 0 );

function create_tasklist() {
	sql_query( "CREATE TABLE `im_tasklist` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `date` DATETIME DEFAULT NULL,
  `task_description` VARCHAR(100) CHARACTER SET utf8 DEFAULT NULL,
  `task_template` INT(11) DEFAULT NULL,
  `status` INT(11) DEFAULT '0',
  `started` DATETIME DEFAULT NULL,
  `ended` DATETIME DEFAULT NULL,
  `project_id` INT(11) DEFAULT NULL,
  `mission_id` INT(11) NOT NULL DEFAULT '0',
  `location_name` VARCHAR(50) CHARACTER SET utf8 DEFAULT NULL,
  `location_address` VARCHAR(50) CHARACTER SET utf8 DEFAULT NULL,
  `priority` INT(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=latin1" );

	sql_query( "ALTER TABLE im_tasklist
	MODIFY `location_name` VARCHAR(50) CHARACTER SET utf8 DEFAULT NULL;
	" );

}


function version17() {
	sql_query( "drop view im_products_draft" );

	sql_query( "create view im_products_draft as 
	SELECT
    `wp_posts`.`ID`                    AS `ID`,
    `wp_posts`.`post_author`           AS `post_author`,
    `wp_posts`.`post_date`             AS `post_date`,
    `wp_posts`.`post_date_gmt`         AS `post_date_gmt`,
    `wp_posts`.`post_content`          AS `post_content`,
    `wp_posts`.`post_title`            AS `post_title`,
    `wp_posts`.`post_excerpt`          AS `post_excerpt`,
    `wp_posts`.`post_status`           AS `post_status`,
    `wp_posts`.`comment_status`        AS `comment_status`,
    `wp_posts`.`ping_status`           AS `ping_status`,
    `wp_posts`.`post_password`         AS `post_password`,
    `wp_posts`.`post_name`             AS `post_name`,
    `wp_posts`.`to_ping`               AS `to_ping`,
    `wp_posts`.`pinged`                AS `pinged`,
    `wp_posts`.`post_modified`         AS `post_modified`,
    `wp_posts`.`post_modified_gmt`     AS `post_modified_gmt`,
    `wp_posts`.`post_content_filtered` AS `post_content_filtered`,
    `wp_posts`.`post_parent`           AS `post_parent`,
    `wp_posts`.`guid`                  AS `guid`,
    `wp_posts`.`menu_order`            AS `menu_order`,
    `wp_posts`.`post_type`             AS `post_type`,
    `wp_posts`.`post_mime_type`        AS `post_mime_type`,
    `wp_posts`.`comment_count`         AS `comment_count`
  FROM `wp_posts`
  WHERE ((`wp_posts`.`post_type` = 'product') AND 
  (`wp_posts`.`post_status` = 'draft'));

" );

	sql_query( "CREATE FUNCTION task_status(task_id INT)
  RETURNS TEXT CHARSET 'utf8'
  BEGIN
    declare _status int;
    select status into _status from im_tasklist
    where id = task_id;

    return _status;
  END;
" );

	sql_query( "create function client_displayname (user_id int) returns text 
BEGIN
    declare _user_id int;
    declare _display varchar(50) CHARSET utf8;
    select display_name into _display from wp_users
    where id = user_id;

    return _display;
  END;

" );
}
// Version 1.6
function version16() {

	sql_query( "ALTER TABLE im_business_info
ADD net_amount DOUBLE;
" );
}
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

