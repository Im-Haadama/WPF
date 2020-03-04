<?php




// Tabula:
//// TODO: require_once( '../r-shop_manager.php' );
require_once( "../../core/data/im_simple_html_dom.php" );

print im_file_get_html("http://fruity.co.il/fresh/about.php");
print im_file_get_html("http://store.im-haadama.co.il/fresh/about.php");
print im_file_get_html("http://super-organi.co.il/fresh/about.php");
