<?php
require_once( FRESH_INCLUDES . "/core/data/translate.php" );
?>
	<script>
        function success_message(xmlhttp)
        {
            if (xmlhttp.response.substr(0, 3) === "done")
                alert("<?php print im_translate("Success"); ?>");
            else
                alert (xmlhttp.response);
        }
	</script>
<?php

