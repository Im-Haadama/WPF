<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 26/01/17
 * Time: 16:23
 */

require_once( "im_tools.php" );
require_once( "mail.php" );

send_mail( "mail-test", "yaakov.aglamaz@gmail.com" . ", info@im-haadama.co.il", "test message" );

//require_once("delivery.php");
//$d = new delivery(1632);
//$d->send

