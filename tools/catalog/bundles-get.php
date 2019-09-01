<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 16/07/15
 * Time: 15:25
 */
require_once( '../r-shop_manager.php' );
require_once( ROOT_DIR . "/niver/gui/inputs.php" );

print header_text( false );
print gui_button( "btn_new", "show_create_new()", "מארז חדש" );

?>

<html dir="rtl">
<header>
    <script type="text/javascript" src="/niver/gui/client_tools.js"></script>
    <script>
        function getPrice() {
            var product_name = get_value(document.getElementById("item_name"));
            var request = "../delivery/delivery-post.php?operation=get_price&name=" + encodeURI(product_name);

            xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function () {
                // Wait to get delivery id.
                if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
                {
                    var price = xmlhttp.response;

                    if (price > 0) {
                        document.getElementById("unit_price").innerHTML = price;
                    }
                }
            }
            xmlhttp.open("GET", request, true);
            xmlhttp.send();
        }

        function calcBundle() {
            let product_id = get_value_by_name("product_name");
            // var product_id = product_name.substr(0, product_name.indexOf(")"));
            var q = get_value(document.getElementById("quantity"));
            var margin = get_value(document.getElementById("margin"));

            var request = "bundles-post.php?operation=calculate&product_id=" + product_id +
                "&quantity=" + q + "&margin=" + margin;

            xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function () {
                // Wait to get delivery id.
                if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
                {
                    var response = xmlhttp.response.split(",");
                    var buy_price = response[0];
                    var price = response[1];
                    var bundle_price = response[2];

                    document.getElementById("buy_price").innerHTML = buy_price;
                    document.getElementById("price").innerHTML = price;
                    document.getElementById("bundle_price").innerHTML = bundle_price;
                    document.getElementById("regular_price").innerHTML = price * q;

                }
            }
            xmlhttp.open("GET", request, true);
            xmlhttp.send();

        }
        var post_url = "bundles-post.php";
        var class_name = "bundle";
        var table_name = class_name + "_list";

        function show_create_new() {
            var new_item = document.getElementById("new_item");
            new_item.style.display = 'block';
            // add_line();
            // document.getElementById("client_select").focus();

        }

        function selected(sel) {
            var pricelist_id = sel.id.substr(4);
            document.getElementById("chk_" + pricelist_id).checked = true;
        }

        function createBundle() {
            var product_id = get_value_by_name("product_name");
            // var product_id = product_name.substr(0, product_name.indexOf(")"));
            var quantity = get_value(document.getElementById("quantity"));
            var margin = get_value(document.getElementById("margin"));

            xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function () {
                // Wait to get query result
                if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
                {
                    updateDisplay();
                }
            }
            var request = post_url + "?operation=add_item&product_id=" + product_id + "&quantity=" + quantity +
                "&margin=" + encodeURI(margin);
            xmlhttp.open("GET", request, true);
            xmlhttp.send();
        }


        function save_items() {
            var table = document.getElementById(table_name);

            var collection = document.getElementsByClassName(class_name);
            var params = new Array();
            for (var i = 0; i < collection.length; i++) {
                if (collection[i].checked) {
                    var bundle_id = table.rows[i + 1].cells[0].firstChild.id.substr(4);
                    var quantity = get_value_by_name("qty_" + bundle_id);
                    var margin = get_value_by_name("mar_" + bundle_id);

                    params.push(bundle_id);
                    params.push(quantity);
                    params.push(encodeURI(margin));
                }
            }
            xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function () {
                // Wait to get query result
                if (xmlhttp.readyState === 4 && xmlhttp.status === 200)  // Request finished
                {
                    updateDisplay();
                }
            }
            var request = post_url + "?operation=update&params=" + params;
            xmlhttp.open("GET", request, true);
            xmlhttp.send();
        }

        function del_items() {
            var table = document.getElementById(table_name);

            var collection = document.getElementsByClassName(class_name);
            var params = new Array();
            for (var i = 0; i < collection.length; i++) {
                if (collection[i].checked) {
                    var id = collection[i].id.substr(4);

                    params.push(id);
                }
            }
            xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function () {
                // Wait to get query result
                if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
                {
                    updateDisplay();
                }
            }
            var request = post_url + "?operation=delete_item" + "&params=" + params;
            xmlhttp.open("GET", request, true);
            xmlhttp.send();
        }

        function updateDisplay() {
            xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function () {
                // Wait to get query result
                if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
                {
                    table = document.getElementById(table_name);
                    table.innerHTML = xmlhttp.response;
                }
            }
            var request = post_url + "?operation=get_bundles";
            xmlhttp.open("GET", request, true);
            xmlhttp.send();
        }

        function add_item() {
            var prod_name = get_value(document.getElementById("item_id"));
            var quantity = get_value(document.getElementById("quantity"));
            var margin = get_value(document.getElementById("margin"));
            var bundle_prod_name = get_value(document.getElementById("bundle_prod_id"));

            xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function () {
                // Wait to get query result
                if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
                {
                    updateDisplay();
                }
            }
            var request = post_url + "?operation=add_item&product_name=" + encodeURI(prod_name) + '&quantity=' + quantity +
                '&margin=' + margin + '&bundle_prod_name=' + encodeURI(bundle_prod_name);
            xmlhttp.open("GET", request, true);
            xmlhttp.send();
        }

    </script>
</header>
<body onload="updateDisplay()">
<button id="btn_save" onclick="save_items()">שמור עדכונים</button>
<button id="btn_delete" onclick="del_items()">מחק פריטים</button>
<?php
print gui_datalist( "items", "im_products", "post_title" );
?>
<table id="bundle_list" border="1"></table>
<!--    <table id="other_product_list">-->
<!--        <div>הוסף מחיר למוצר חדש-->
<!--            פריט-->
<!--            <input id="item_id" list="items">-->
<!--            מזהה מארז-->
<!--            <input id="bundle_prod_id" list="items">-->
<!--            כמות-->
<!--            <input id="quantity">-->
<!--            מרווח-->
<!--            <input id="margin">-->
<!--            <button id="btn_add_bundle" onclick="add_item()">הוסף מארז</button>-->
<!--        </div>-->

<div id="new_item" style="display: none">
	<?php
	print gui_header( 1, "יצירת מארז" );
	print gui_table_args( array(
		array(
			gui_header( 2, "בחר מוצר" ),
			gui_header( 2, "מחיר עלות" ),
			gui_header( 2, "מחיר יחידה" ),
			gui_header( 2, "כמות במארז" ),
			gui_header( 2, "רוווח %/שח" ),
			gui_header( 2, "מחיר מארז" ),
			gui_header( 2, "מחיר רגיל" )
		),
		array(
			gui_input_select_from_datalist( "product_name", "products", "onchange=\"calcBundle()\"" ),
			// '<input id="item_name" list="items" onchange="getPrice()">',
			'<div id="buy_price">',
			'<div id="price">',
			'<input id="quantity" onchange="calcBundle()">',
			'<input id="margin" onchange="calcBundle()">',
			'<div id="bundle_price">',
			'<div id="regular_price">'
		)
	) );
	print gui_button( "btn_create_bundle", "createBundle()", "צור" );
	print gui_datalist( "products", "im_products", "post_title", true );

	?>

        </div>
<div id="logging"></div>

</body>
</html>