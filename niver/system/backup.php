<?php

function delete_backup($backup_dir)
{
	$content = scandir( $backup_dir, SCANDIR_SORT_DESCENDING );
	foreach ($content as $c)
	{
		$full_path = $backup_dir . '/' . $c;
		if (! is_file($full_path)) continue;
		$date = filemtime($full_path);
		$year = date('y');
		$month = date('m');
		$week = date('W');
		if (date('m-j', $date) == '01-01') continue; // Yearly backup
		if (date('y-j', $date) == $year . '-1') continue; // Monthly backup, this year.
		if (date('m-w', $date) == $month . '-0') continue; // Weekly backup, this month.
		if (date('W', $date) == $week) continue; // Daily backup, this week.
		print 'deleting ' . $full_path . PHP_EOL;
		unlink($full_path);
	}
}

function readfile_chunked( $filename, $retbytes = true )
{
	$chunksize = 1 * ( 1024 * 1024 ); // how many bytes per chunk
	$cnt       = 0;
	// $handle = fopen($filename, 'rb');
	$handle = fopen( $filename, 'rb' );
	if ( $handle === false ) {
		return false;
	}
	while ( ! feof( $handle ) ) {
		$buffer = fread( $handle, $chunksize );
		echo $buffer;
		if ( $retbytes ) {
			$cnt += strlen( $buffer );
		}
	}
	$status = fclose( $handle );
	if ( $retbytes && $status ) {
		return $cnt; // return num. bytes delivered like readfile() does.
	}

	return $status;
}
