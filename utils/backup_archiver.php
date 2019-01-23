<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 27/05/18
 * Time: 23:11
 */

$backup_dir = dirname( __FILE__ ) . '/backups';

//print $backup_dir . "<br/>";

$content       = scandir( $backup_dir );
$months_backup = array();
$weeks_backup  = array();

foreach ( $content as $file ) {
	if ( $file == "." or $file == ".." ) {
		continue;
	}
//	print $file;
	if ( substr( $file, strlen( $file ) - 6 ) != "tar.gz" ) {
//		print " skip<br/>";
		continue;
	}
	$full_file  = $backup_dir . '/' . $file;
	$delta_days = ( time() - filemtime( $full_file ) ) / 86400;
	if ( $delta_days > 31 ) {
		// Leave this last file for months prior to this month
		$month = date( "y-m", filemtime( $full_file ) );
		if ( isset( $months_backup[ $month ] ) ) {
			// We have already backup for this month
			if ( filemtime( $full_file ) > filemtime( $months_backup[ $month ] ) ) {
				$remove_file             = $months_backup[ $month ];
				$months_backup[ $month ] = $full_file;
				// unlink the other one
				print "remove " . $remove_file . "<br/>";
				unlink( $remove_file );
			}
		} else {
			$months_backup[ $month ] = $full_file;
		}
//		print $file . " " . $month . "<br/>";
		continue;
	}
	if ( $delta_days > 7 ) {
		// Leave one file for each week prior to this week
		$week = date( "y-W", filemtime( $full_file ) );
		if ( isset( $weeks_backup[ $week ] ) ) {
			// We have already backup for this month
			if ( filemtime( $full_file ) > filemtime( $weeks_backup[ $week ] ) ) {
				$remove_file           = $weeks_backup[ $week ];
				$weeks_backup[ $week ] = $full_file;
				// unlink the other one
				print "remove " . $remove_file . "<br/>";
				unlink( $remove_file );
			}
		} else {
			$weeks_backup[ $week ] = $full_file;
		}
//		print $file . " " . $month . "<br/>";
		continue;
	}
	// Leave all files for this week
//	print $full_file . " " . $delta_days . "<br/>";
	// $mon = date("F", filemtime($full_file));
	// print date ("F d Y H:i:s.", filemtime($full_file)) . "<br/>";

}