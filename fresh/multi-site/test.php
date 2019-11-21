<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 02/02/17
 * Time: 18:17
 */

require_once( 'multi-site.php' );
require_once( 'simple_html_dom.php' );

// print get_site_tools_url(2) . "<br/>";
// print get_site_tools_url(1) . "<br/>";

//$remote_prods = array();
//
//$product_name = "בננה";
//$site_id=2;
//$remote = get_site_tools_url($site_id) . "/catalog/catalog-db-query.php?operation=show";
//$html = file_get_html($remote);
//foreach($html->find('tr') as $row)
//{
//    $id = $row->find('td',0)->plaintext;
//    $name = $row->find('td',1)->plaintext;
//    $price = $row->find('td',2)->plaintext;
//    $supplier = $row->find('td',3)->plaintext;
//
//    $remote_prods[$id] = array($name, $price, $supplier);
//    // print $id . $name . $price . $supplier . "<br/>";
//}
//
//$product_name_prf = $product_name;
//print $product_name_prf;
//$keys = array_keys($remote_prods, $product_name_prf);
//foreach ($keys as $key){
//    // $line .= '<option value="' . $row1[0] . '">' . $row1[1] . '</option>';
//    $line .= '<option value="' . $remote_prods[$key][0] . '">' . $key . '</option>';
//    print $line;
//}

//$url = "http://store.im-haadama.co.il/wordpress/wp-content/uploads/2014/10/seeds.jpg";
//
//if ( $i = strstr( $url, "wordpress" ) ) {
//	print $i . "<br/>";
//	$url = strstr( $url, "wordpress", true ) . substr( $i, 10 );
//	print $url;
////                    $url = substr($url, 10);
//}

print ImMultiSite::GetAll( "multi-site/pp.php" );