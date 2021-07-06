<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 16/06/17
 * Time: 14:45
 */
require_once( 'mail-config.php' );
require_once( 'simple_html_dom.php' );

// print phpversion();
//$url="http://store.im-haadama.co.il/fresh/pricelist/update-sadot.php?file=sadot_15.6.17.csv";
//$html = file_get_html($url);
//print $html;

$inbox = imap_open( $host, $user, $pass );

$header = imap_headerinfo( $inbox, 1 );



//
//
// imap_mail_copy($inbox, 1, 'amir');
