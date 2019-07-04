<?php

error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

define("ROOT_DIR", dirname(dirname(__FILE__)));

require_once("im_tools.php");
print header_text(true, true, false);
require_once( 'multi-site/imMulti-site.php' );

$i = new ImMultiSite();

print system("uname -a") . "<br/>";

foreach ($i->getAllServers() as $host)
{
	print gui_header(1, $host) . "<br/>";
	print nl2br(system("ping -c 2 " . $host ));
	print system("traceroute " . $host );
	print "<br/>";
}


