<?php


require( '../r-shop_manager.php' );
require_once(FRESH_INCLUDES . "/fresh/multi-site/imMulti-site.php");
$m = Core_Db_MultiSite::getInstance();

	print header_text( false );
require_once( "account.php" );
?>
<html dir="rtl" lang="he">
<head>
    <script>

        function zero() {
            xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function () {
                // Wait to get query result
                if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
                {
                    location.reload();
                }
            }
            var request = "account-post.php?operation=zero_near_zero";
            xmlhttp.open("GET", request, true);
            xmlhttp.send();
        }

        function send_month_summary() {
            var collection = document.getElementsByClassName("user_chk");
            var user_ids = new Array();
            for (var i = 0; i < collection.length; i++) {
                if (collection[i].checked) {
                    var user_id = collection[i].id.substr(4);
                    user_ids.push(user_id);
                }
            }
            xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function () {
                // Wait to get query result
                if (xmlhttp.readyState === 4 && xmlhttp.status === 200)  // Request finished
                {
                    var http_text = xmlhttp.responseText.trim();
                    document.getElementById("logging").innerHTML = http_text;
                }
            }
            var request = "account-post.php?operation=send_month_summary&ids=" + user_ids.join();
            xmlhttp.open("GET", request, true);
            xmlhttp.send();

        }
    </script>
</head>
<body>
<?php

$include_zero = isset( $_GET["zero"] );

/**
 * Created by PhpStorm.
 * User: agla
 * Date: 09/08/15
 * Time: 12:39
 */

//require_once("../header.php");


?>
<button id="btn_zero" onclick="zero()">אפס קרובים לאפס</button>
<button id="btn_remind" onclick="send_month_summary()">שלח תזכורת</button>
<div id="logging"></div>
</body>
</html>
