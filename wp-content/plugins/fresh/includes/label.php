<?php
$logo_url = defined('SITE_URL') ? SITE_URL : '';
if ($logo_url) $logo_i = '<img src="' . $logo_url . '" height="100"><br/>';
else $logo_i = '';

$h = "58mm";
$w = "67mm";
$body_w="210mm";
$body_m="1.4cm 0.8cm";?>
<html>
<head>
    <style>
        body {
            width: <?php print $body_w;?>;
            margin: <?php print $body_m;?>;
        }
        .label{
            width: <?php print $w; ?>;
            height: <?php print $h; ?>; /* plus .125 inches from padding */
            /*padding: .125in .3in 0;*/
            /*margin-right: .125in; !* the gutter *!*/

            float: left;

            text-align: center;
            overflow: hidden;

            /*outline: 1px dotted; !* outline doesn't occupy space like border does *!*/
        }

        .page-break  {
            clear: left;
            display:block;
            page-break-after:always;
        }
    </style>
</head>
<?php

$m = new Mission($mission_id);
$orders = $m->getOrders();
the_custom_logo();
$box_count = 0;

foreach ($orders as $order) {
    $order_id = $order[0];
    $O = new Finance_Order($order_id);
//    print $order_id . " " . $O->box_number() . "<br/>";
//    $client_id = $order[1]; $C = new Fresh_Client($client_id);
    for ($i = 1; $i <= $O->box_number(); $i++) {
        $box_count++;
        ?>
        <div class="label"><table width="100%">
            <tr><td> <?php print $logo_i; ?>
                    </td><td><h1> <?php print $i; ?> </h1></td></tr></table>
               <h3 style="line-height: 0.7"> <?php print $O->receiver(); ?> </h3>
            <?php print $O->getOrderInfo( '_billing_phone' ) ?> <br/>
              <h2> <?php print $O->getAddress() . " " . $O->getCity(); ?> </h2>
        </div>
<?php
	    if ( ( $box_count % 15 ) == 0 )
		    print '<p style="page-break-after: always;">&nbsp;</p>';

}
}
?>

</html>