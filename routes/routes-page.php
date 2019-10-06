<?php

if ( ! defined( "ROOT_DIR" ) ) {
	define( 'ROOT_DIR', dirname( dirname( __FILE__ ) ) ) ;
}

require_once(ROOT_DIR . '/im-config.php');
require_once( ROOT_DIR . "/init.php" );

print header_text(true, false, true, true);
$id = get_param("id", false, 0);
?>
<script>
    function update()
    {
	    let mission_id = <?php print $id; ?>;
	    let start = get_value_by_name("start_time");
	    let start_point = get_value_by_name("start_location");
	    let url = "/routes/routes-post.php?operation=update_mission&id=" + mission_id +
		    "&start=" + encodeURI(start) +
		    "&start_point=" + encodeURI(start_point);

	    execute_url(url, update_display);
	    // alert("update" + mission_id + start + start_point);
    }

    function update_display(xmlhttp)
    {
        document.getElementById("mission_text").innerHTML = xmlhttp.response;
    }
</script>
</header>

<?php

require_once( "routes.php" );

$operation = get_param("operation", false, null);

if ($operation) {
	handle_routes_operation($operation);
	return;
}

?>



