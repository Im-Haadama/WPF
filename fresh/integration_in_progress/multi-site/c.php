<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 28/01/17
 * Time: 21:43
 */
$file = fopen( "http://store.im-haadama.co.il/fresh/multi-site/pp.php", "r" );
if ( ! $file ) {
	echo "<p>Unable to open remote file.\n";
	exit;
}
while ( ! feof( $file ) ) {
	$line = fgets( $file, 1024 );
	print $line;
}
fclose( $file );

