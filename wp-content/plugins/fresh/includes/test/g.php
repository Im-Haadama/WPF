<?php




// require_once ("../../focus/gui.php");
if ( ! defined( 'FRESH_INCLUDES' ) ) {
	define( 'FRESH_INCLUDES', dirname(dirname( dirname( __FILE__ ) ) ));
}

require_once(FRESH_INCLUDES . '/im-config.php');

require_once( FRESH_INCLUDES . "/init.php" );

init();

print load_scripts( '/core/gui/client_tools.js');
print header_text();
require_once( FRESH_INCLUDES . '/core/gui/inputs.php' );

//$args = [];
//$args["edit"] = true;
//print gui_select_days("day", null, $args);

$args = [];
$args["options"] = array("a", "bc","d");
// print GuiPulldown("test", "test", $args);
?>

<div class="dropdown">
	<button onclick="myFunction()" class="dropbtn">Dropdown</button>
	<div id="myDropdown" class="dropdown-content">
		<a href="#">Link 1</a>
		<a href="#">Link 2</a>
		<a href="#">Link 3</a>
	</div>
</div>

<script>
    function myFunction() {
        document.getElementById("myDropdown").classList.toggle("show");
    }

    // Close the dropdown menu if the user clicks outside of it
    window.onclick = function(event) {
        if (!event.target.matches('.dropbtn')) {
            var dropdowns = document.getElementsByClassName("dropdown-content");
            var i;
            for (i = 0; i < dropdowns.length; i++) {
                var openDropdown = dropdowns[i];
                if (openDropdown.classList.contains('show')) {
                    openDropdown.classList.remove('show');
                }
            }
        }
    }
</script>