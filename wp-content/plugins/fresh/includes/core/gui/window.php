<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 08/01/19
 * Time: 20:08
 */

function redirect_back() {
	if ( isset( $_SERVER["HTTP_REFERER"] ) ) {
		header( "Location: " . $_SERVER["HTTP_REFERER"] );
	}
}
