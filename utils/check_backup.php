<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 18/02/18
 * Time: 05:06
 */


require_once( gethostname() . '.php' );

$content = scandir( $backup_dir );

if ( sizeof( $content ) < ( $backup_count + 3 ) ) { // 3 objects always there: ., .., tmp
	print "no enough backups!";
	foreach ( $content as $file ) {
		print $file . "<br/>";
	}
}

foreach ( $content as $file ) {
	if ( $file == "." or $file == ".." or $file == "tmp" ) {
		continue;
	}
	$size = filesize( $backup_dir . "/" . $file );
	if ( $size < $backup_min_size ) {
		print "backup to small! " . $file . ". size is " . $size . "<br/>";
	}
}
