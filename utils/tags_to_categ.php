<?php

require_once("../wp-config.php");

// Organic fruits
$cat = new Fresh_Category(19);
// 155, 172
//$cat = new Fresh_Category(172);

//
// $cat->merge_with_category_with_tag()
if (! $cat->addTag("אורגני"))
	print "failed " . $cat->getName() . "<br/>";

//$cat = new Fresh_Category(307);
//if (! $cat->merge_with_category_with_tag("פירות", 'no_pest'))
//	print "failed " . $cat->getName() . "<br/>";