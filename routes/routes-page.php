<?php

if ( ! defined( "ROOT_DIR" ) ) {
	define( 'ROOT_DIR', dirname( dirname( __FILE__ ) ) ) ;
}

require_once(ROOT_DIR . '/im-config.php');
require_once( ROOT_DIR . "/init.php" );

print load_scripts("routes.js");

print header_text(true, true, true, true);
$id = get_param("id", false, 0);
?>

<?php

require_once( "routes.php" );

$operation = get_param("operation", false, "show_routes");

if ($operation) {
	handle_routes_operation($operation);
	return;
}

?>
