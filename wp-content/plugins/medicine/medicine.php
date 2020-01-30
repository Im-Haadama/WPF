<?php

error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

if ( ! defined( "ROOT_DIR" ) ) {
	define( 'ROOT_DIR', dirname( __FILE__ ) );
}

require_once( ROOT_DIR . '/wp-config.php' );
require_once( ROOT_DIR . '/im-config.php' );

require_once( ROOT_DIR . "/niver/gui/gem.php" );

require_once( "medicine/medicine_class.php" );
$operation = GetParam( "operation", false, "medicine_main" );

require_once( ROOT_DIR . "/init.php" );

if ( handle_medicine_do( $operation ) !== "not handled" ) {
	return;
}

$themes      = [];
$height      = "80px";
$dark_brown  = "231f20";
$light_green = "98c64a";
$redish      = "6a241a";

$themes["dark"] = array(
	"logo_image" => "http://store.im-haadama.co.il/wp-content/uploads/2019/11/logo-red.png",
	"css"        => "/im-dark.css"
);

$args                 = $themes["dark"];
$args["print_logo"]   = false;
$args["script_files"] = array( "/niver/gui/client_tools.js", "/niver/data/data.js", "/focus/focus.js", "medicine/medicine.js" );
$user_id              = get_user_id();
$projects             = project_pulldown( $user_id );
$logo                 = GuiHyperlink( GuiImage( GetArg( $args, "logo_image", null ), GetArg( $args, "height", 80 ) ), "/focus.php" );
$search               = GuiInput( "search_text", "(search here)", array( "events" => "onfocus=\"search_by_text()\" onkeyup=\"search_by_text()\" onfocusout=\"search_box_reset()\"" ) );
//$greeting = greeting();
$alerts = alerts_pulldown( $user_id );
// $setting = GuiHyperlink("settings", add_to_url("operation", "show_settings"), array ("class" => "light_hyperlink"));
$setting = GuiPulldown( "settings", "settings", array(
	"menu_options" => array(
		array(
			"text" => "Edit organization",
			"link" => AddToUrl( "operation", "edit_organization" )
		)
	)
) );

$teams = team_pulldown( $user_id );
//$circle = '<div id="avatar_circle"
$header_elements         = array(
	$logo,
	$search,
	get_avatar( get_user_id(), 40 ),
	$alerts,
	$projects,
	$teams,
	$setting
);
$args["header_elements"] = $header_elements;
print GemHeader( "header_div", "header_div", $args ); // Opens the body with style definitions and menus.

print '<div id="search_result"></div>';

print "<br/>"; // The space of header.

if ( ! get_user_id( true ) ) {
	return;
}
if ( ! focus_check_user() ) {
	return;
}

if ( $operation ) {
	if ( GetParam( "page", false, null ) ) {
		$args ["page"] = GetParam( "page" );
	}
	handle_medicine_show( $operation, $args );

	return;
}
