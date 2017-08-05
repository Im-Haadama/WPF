<?php
require '../tools_wp_login.php';

$order_id         = $_GET["delivery_id"];
$total            = $_GET["total"];
$vat              = $_GET["vat"];
$product_name     = $_GET["product_name"];
$quantity         = $_GET["quantity"];
$quantity_ordered = $_GET["quantity_ordered"];
$vat              = $_GET["vat"];
$price            = $_GET["price"];
$line_price       = $_GET["line_price"];
$prod_id          = $_GET["prod_id"];
my_log( "q = " . $quantity, "db-add-delivery.php" );

