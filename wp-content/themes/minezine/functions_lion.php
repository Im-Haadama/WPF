<?php
add_action('wp_enqueue_scripts', 'add_lion_scripts');

function add_lion_scripts()
{
	if ( isset($_GET["wpam_id"] )) {
		$code = $_GET["wpam_id"];
		print '<script> 
document.addEventListener("DOMContentLoaded", copy_affiliate, false);

function copy_affiliate()
{
    document.getElementsByName("affiliate")[0].value = ' . $code . ';
}
</script>';
	}
}
?>