<?php
require_once( '../r-shop_manager.php' );
?>

<!DOCTYPE html>
<html>
<body>

<form name="upload_csv" action="pricelist-upload-supplier-prices.php?supplier_id=5" method="post"
      enctype="multipart/form-data">
    בחר קובץ CSV של הספק:
    <select onclick="change_supplier(this);" name="supplier_id">

		<?php

		$sql1 = 'SELECT id, supplier_name FROM im_suppliers';

		// Get line options
		$export1 = mysql_query( $sql1 );
		while ( $row1 = mysql_fetch_row( $export1 ) ) {
			print "<option value = \"" . $row1[0] . "\" > " . $row1[1] . "</option>";
		}

		?>
    </select>
    <input type="file" name="fileToUpload" id="fileToUpload">
    <input type="submit" value="טען מחירון" name="submit">
</form>

<!--<button id="btn_diff" onclick="diff2last()">השווה בין שני מחירונים אחרונים של האורגני</button>-->
<script>
    function change_supplier(id) {
        document.upload_csv.action = "pricelist-upload-supplier-prices.php?supplier_id=" + id.value;
    }

    function diff2last() {

        // Enter delivery note to db.
        var request = "diff-2-last.php?supplier_id=2";

        var xmlhttp = new XMLHttpRequest();
        xmlhttp.onreadystatechange = function () {
            // Wait to get delivery id.
            if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
            {
                result_text = xmlhttp.responseText;
            }
        }

        xmlhttp.open("GET", request, true);
        xmlhttp.send();
    }
</script>
</body>
</html>