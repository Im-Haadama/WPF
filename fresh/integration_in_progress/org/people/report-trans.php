<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 06/02/18
 * Time: 11:16
 */

require_once( "../r-multisite.php" );
require_once( "people.php" );
$week    = $_GET["week"];
$project = GetParam( "project" );

// print $week;

// print $week;
$s = array();
print print_transactions( 0, 0, 0, $week, $project, $s, true );
