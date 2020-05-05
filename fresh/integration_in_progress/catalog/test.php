<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 11/03/17
 * Time: 09:09
 */

require_once( 'catalog.php' );

print header_text( false, true, false );

//
//

$sql = 'select '
       . ' id, post_title '
       . ' from wp_posts '
       . ' where post_type = \'product\'';

$result = SqlQuery( $sql );

while ( $row = SqlFetchRow( $result ) ) {
	$prod_id = $row[0];
	print "prod_id= " . $prod_id;

	$a = alternatives( $prod_id );
	var_dump( $a );

	print "<br/>";
}