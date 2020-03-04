<?php

require_once( '../r-shop_manager.php' );
require_once( "../catalog/bundles.php" );

$b = Fresh_Bundle::CreateFromDb(18);

$b->CreateOrUpdate();


//print $b->CalculatePrice() . "<br/>";
//
//$p = new Product(6430);
//print $p->getPrice();


