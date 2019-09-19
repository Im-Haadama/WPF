<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 15/05/19
 * Time: 09:29
 */

require_once( '../r-shop_manager.php' );
require_once("suppliers.php");

$operation = get_param("operation", true);

if ($operation)
	handle_supplier_operation($operation);
