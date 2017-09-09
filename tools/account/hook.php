<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 10/09/16
 * Time: 20:27
 */

require_once( 'account.php' );

// set_default_display_name(190);
//set_post_meta_field(96,'_client_type', 'owner');
// print customer_type( 91 );
for ( $i = 73; $i < 74; $i ++ ) {
	print $i . "<br/>";
	im_set_default_display_name( $i );
}