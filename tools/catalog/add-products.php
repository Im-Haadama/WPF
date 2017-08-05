<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 21/07/17
 * Time: 15:11
 */
require_once( '../tools.php' );
require_once( '../multi-site/multi-site.php' );
print header_text( true );


?>
<script charset="utf-8">

    function add_products() {
        document.getElementById("add_products").disabled = true;

        // Pass information to server for processing.
        var sel = document.getElementById("supplier_id");
        var idx = sel.selectedIndex;
//        if (idx == 0) {
//            alert "יש לבחור"
//        }
        var supplier_id = sel.options[idx].value;

        var request = "add-products-post.php?operation=create_products&supplier_id=" + supplier_id;

        // Check if remote
        var sel_remote_supplier = document.getElementById("remote_supplier");
        if (sel_remote_supplier) {
            var remote_supplier = sel_remote_supplier.options[sel_remote_supplier.selectedIndex].value;
            request = request + "&remote_supplier=" + remote_supplier;

            var sel_remote_category_id = document.getElementById("remote_category_id");
            request = request + "&remote_category_name=" + encodeURI(sel_remote_category_id.options[sel_remote_category_id.selectedIndex].innerHTML);
        }

        var sel_local_category_id = document.getElementById("local_category_id");
        if (sel_local_category_id.selectedIndex == 0) {
            alert("יש לבחור קטגוריה");
            document.getElementById("add_products").disabled = false;
            return;
        }

        request = request + "&local_category_name=" + encodeURI(sel_local_category_id.options[sel_local_category_id.selectedIndex].innerHTML);

        request += "&Params=";
        for (var i = 0; i < 20; i++) {
            var name = document.getElementById("name" + i).value;
            var price = document.getElementById("pric" + i).value;

            if (price > 0)
                request = request + encodeURI(name) + "," + price + ",";
        }

        // remove last comma
        request = request.substr(0, request.length - 1);
        logging.innerHTML = "מעבד. נא להמתין";

        xmlhttp = new XMLHttpRequest();
        xmlhttp.onreadystatechange = function () {
            // Wait to get delivery id.
            if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
            {
                logging.innerHTML = xmlhttp.response;
                document.getElementById("add_products").disabled = false;
                // logging.innerHTML += request;
            }
        }
        xmlhttp.open("GET", request, true);
        xmlhttp.send();

        // Display results.

    }
    function change_supplier() {
        var remote_suppliers = ["", "", '<?php print MultiSite::Execute( "multi-site/get-suppliers-categs.php", 2 ); ?>'];
//        var site_names = ["", "", "<?php print MultiSite::GetSiteName( 2 );?>"];

        var sel = document.getElementById("supplier_id");
        var selected = sel.options[sel.selectedIndex];
        supplier_id = selected.value;
        var site_id = selected.getAttribute("data-site-id");
        var tools = selected.getAttribute("data-tools-url-id");

        if (site_id && !(site_id == <? print MultiSite::LocalSiteID(); ?>)) {
            remote_pricelist.innerHTML = "הוספה לרשימה " + /* site_names[site_id] +*/
                ": " + remote_suppliers[site_id];
        } else {
            remote_pricelist.innerHTML = "";
        }
    }

    function check(field) {
        var line = parseInt(field.name.substr(4));
        switch (field.name.substr(0, 4)) {
            case "name":
                if (field.value.length < 3) alert("שם מוצר צריך להכיל לפחות 3 תווים");
                else {
                    document.getElementById("pric" + line).focus();
                }
                break;
            case "pric":
                if (!(parseInt(field.value) > 1)) alert("מחיר צריך להיות מספר עשרוני, גדול מ-1");
                else {
                    var next_row = document.getElementById("name" + (line + 1));
                    if (next_row) next_row.focus();
                }
                break;
        }
    }
</script>
<?php
print gui_header( 1, "הוספת פריטים" );
print "ספק/אתר" . " ";
print_select_supplier( "supplier_id", true );

?>
<div id="remote_pricelist"></div>
הוספה לקטגוריה:
<?php
print_category_select( "local_category_id", true );

$table_content = array();
array_push( $table_content, array( "תאור", "מחיר" ) );
for ( $i = 0; $i < 20; $i ++ ) {
	array_push( $table_content, array(
		gui_input( "name" . $i, "", array( 'onchange="check(this)"' ) ),
		gui_input( "pric" . $i, "", array( 'onchange="check(this)"' ) )
	) );
}

print gui_table( $table_content, "new_products" );

print gui_button( "add_products", "add_products()", "הוסף פריטים" );

?>

<div id="logging"></div>