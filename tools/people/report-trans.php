<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 06/02/18
 * Time: 11:16
 */

require_once( "../r-multisite.php" );
require_once( "people.php" );
$week = $_GET["week"];

// print $week;
print print_transactions( 'owner', 0, 0, 0, $week );
