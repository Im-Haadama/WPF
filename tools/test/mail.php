<?php
error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

require_once("../catalog/catalog.php");

Catalog::auto_mail();