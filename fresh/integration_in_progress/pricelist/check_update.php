<?php

if ( ! defined( 'TOOLS_DIR' ) ) {
	define( 'TOOLS_DIR', dirname( dirname( __FILE__ ) ) );
}



$suppliers = SqlQueryArrayScalar( "select id from im_suppliers " .
                                  " where machine_update = 1 and active = 1");

$result = "";

foreach ($suppliers as $supplier)
{
	$p = new Fresh_PriceList($supplier);
	// print get_supplier_name($supplier) . " " . $p->GetUpdateDate();
	$diff = abs(strtotime("now") - strtotime($p->GetUpdateDate()));
	$days = $diff / 60 / 60 / 24;

	if ($days > 3) $result .= get_supplier_name($supplier) . " " . $p->GetUpdateDate();

//	$update = sql_query_single_scalar("select )
}

if (strlen($result))
{
	print "0 ".  $result;
} else {
	print "1";
}
