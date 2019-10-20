<?php

error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

require_once("../suppliers/gui.php");
require_once("../im_tools.php");
require_once(ROOT_DIR . '/im-config.php');
require_once( ROOT_DIR . "/init.php" );
require_once(ROOT_DIR . '/focus/gui.php');

require_once(ROOT_DIR . '/fresh/catalog/gui.php');

print load_scripts(array("/niver/gui/client_tools.js", "/niver/data/data.js"));
// print gui_select_supplier("supplier", 100051, array("edit"=>true));

$args = [];
$args["worker"] = get_user_id();
print gui_select_product("product", null, $args);

print "<br/>";
print gui_select_project("project", null, $args);

print "<br/>";
print gui_select_worker("worker", null, $args);

print "<br/>";
print gui_select_task("task", null, $args);
