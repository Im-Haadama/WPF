<?php

class Flavor_System {

	// System to check if we collected all backup files
	function check_backups( $debug = false ) {
		$fail = false;
		if ( $debug ) {
			print "checking backup files<br/>";
		}

		// Check if we have new files from backuped servers.
		// print "0 valid backups"; // No attention needed. All ok.

		$results           = [];
		$results["header"] = array( "hostname", "result" );
		$m = new Core_Db_MultiSite();

		foreach ( $hosts_to_sync as $key => $host_info ) {
			$output   = "";
			$url      = $host_info[2] . "/utils/backup_manager.php?tabula=145db255-79ea-4e9c-a51d-318a86c999bf";
			$get_name = $url . "&op=name&date=" . $date;
			if ( $debug ) {
				print $get_name . "<br/>";
			}
			$file_name = GetContent( $get_name );
			if ( strstr( $file_name, "Fatal" ) or strlen( $file_name ) < 5 ) {
				$results[ $key ] = array( "hostname" => $host_info[1], "result" => "error file name: " . $file_name );
				$fail            = true;
				continue;
			}
			$full_path = IM_BACKUP_FOLDER . '/' . $file_name;

			if ( ! file_exists( $full_path ) ) {
				$fail            = true;
				$output          .= "missing file $full_path";
				$results[ $key ] = array( "hostname" => $host_info[1], "result" => $output );
				// array_push( $results, array( $host_info[1], $file_name, $output ) );
				continue;
			}

			if ( time() - filemtime( $full_path ) > 24 * 3600 ) {
				$fail   = true;
				$output .= "old file: " . date( 'Y-m-d', filemtime( $full_path ) );
			} else {
				$output .= date( 'Y-m-d', filemtime( $full_path ) ) . " ";
			}

			if ( filesize( $full_path ) < 20000 ) {
				$fail   = true;
				$output .= "small file: " . filesize( $full_path );
			} else {
				$output .= "size= " . filesize( $full_path );
			}

			$results[ $key ] = array( "hostname" => $host_info[1], "result" => $output );
		}

// Todo: Delete old ones.
		print ( $fail ? "1 failed" : "0 all ok" );
		if ( $fail ) {
			print "url=" . $url . "<br/>";
		}

		$args = [];
		print Core_Html::gui_table_args( $results, "result_table", $args );

		die( 0 );

	}
}