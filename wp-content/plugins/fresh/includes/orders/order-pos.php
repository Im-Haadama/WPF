<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 31/05/18
 * Time: 17:16
 */

print header_text( false );
$pos = true;
require( "new-order.php" );

?>

<script type="text/javascript" src="/core/gui/client_tools.js"></script>
<script>
    //    function pay_order()
    //    {
    //        var cash = get_value_by_name("cash");
    //        var credit = get_value_by_name("credit");
    //        var bank = get_value_by_name("bank");
    //        var check = get_value_by_name("check");
    //
    //        if (! (cash + credit + bank + check) > 0){
    //            alert ("ציין את סכום התשלום");
    //            return;
    //        }
    //        var s = add_order();
    //    }

    function do_pay_delivery(del_id, user_id, order_id) {
        var cash = get_value_by_name("cash");
        var credit = get_value_by_name("credit");
        var bank = get_value_by_name("bank");
        var check = get_value_by_name("check");
        var change = Math.round(100 * (cash + credit + bank + check - get_value_by_name("total"))) / 100;

        var request = "pos-post.php?operation=pay_cash&del_id=" + del_id;
        if (cash) request += "&cash=" + cash;
        if (credit) request += "&credit=" + credit;
        if (bank) request += "&bank=" + bank;
        if (credit) request += "&credit=" + credit;
        if (change > 0) request += "&change=" + change;
        request += "&user_id=" + user_id;

        xmlhttp = new XMLHttpRequest();
        xmlhttp.onreadystatechange = function () {
            // Wait to get delivery id.
            if (xmlhttp.readyState === 4 && xmlhttp.status === 200)  // Request finished
            {
                var response = xmlhttp.response;
                add_message(response);

                if (!get_value_by_name("chk_delivery")) {
                    do_close_order(order_id);
                }
            }
        }
        xmlhttp.open("GET", request, true);
        xmlhttp.send();
    }

</script>
<body onload="show_create_order()">

לקוח אוסף
<br/>
<?php

// This button create order, delivery note and invoice.
// print gui_button("pay_order", "pay_order()", "שולם במזומן");

// This button create order, delivery note.
// print gui_button("create_delivery_note", "add_order(2)", "קניה בהקפה");

print gui_table_args( array(
	array( "מזומן", gui_input( "cash", "" ) ),
	array( "אשראי", gui_input( "credit", "" ) ),
	array( "העברה/BIT", gui_input( "bank", "" ) ),
	array( "המחאה", gui_input( "check", "" ) )
) );

?>

</body>

