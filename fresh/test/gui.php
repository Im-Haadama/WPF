<?php

error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

require_once("../suppliers/gui.php");
require_once("../im_tools.php");
require_once(ROOT_DIR . '/fresh/catalog/gui.php');

print load_scripts(array('/niver/data/data.js', '/niver/gui/client_tools.js'));
// print gui_select_supplier("supplier", 100051, array("edit"=>true));

$args = [];
print gui_select_product("product", null, $args);