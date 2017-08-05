<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 19/05/17
 * Time: 13:05
 */

require_once( '../pricelist/pricelist.php' );

$PL   = new PriceList( 100005 );
$a    = $PL->GetUpdateDate();
$b    = date( 'Y-m-d' );
$diff = date_diff( date_create( $a ), date_create( $b ) );
print $diff->format( '%d' );

