<?php

$sites = array("fruity.co.il", "nizat.com", "shufersal.co.il", "super-organi.co.il");

$products = array("עגבניה", "מלפפון", "גזר", "בצל", "מנגו");

require_once( "../../core/data/im_simple_html_dom.php" );

// Loop on all sites
foreach ($sites as $site)
	print $site . "<br/>";
	// Loop on all products
	foreach ($products as $product){
		// Submit a search
		$url = "http://" . $site . "?s=" . $product;
		// $url = "google.com/search?";
		print "getting " . $url . "<br/>";
		$dom = im_file_get_html($url);
		foreach ( $dom->find( 'a' ) as $row ) {
			$text = $row->plaintext;
			if (strstr($text, $product)) {
				print  $row->href . "<br/>";
				// find_price($row->href);
				//var_dump($row);
				// var_dump($row->find("href"));
				// print $text;
			}
			// var_dump($row);
		}
	}