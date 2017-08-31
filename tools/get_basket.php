<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 09/01/17
 * Time: 12:03
 */

include "im_tools.php";

$id = $_GET["id"];
print get_basket_content( $id );