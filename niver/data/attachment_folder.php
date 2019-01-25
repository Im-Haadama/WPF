<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 25/01/19
 * Time: 07:49
 */

function inbox_files( $hostname, $mail_user, $password, $attach_folder, $folder_url ) {
	$m = new Imap();

	if ( ! $m->Connect( $hostname, $mail_user, $password ) ) {
		die( "can't connect to mail" );
	}

	$m->Read();

	$rows = array( array( "נושא", "שולח", "הורדה" ) );

	if ( ! file_exists( $attach_folder ) ) {
		if ( ! mkdir( $attach_folder ) ) {
			die ( "can't create " . $attach_folder );
		}
	}

	while ( $message = $m->ReadNext() ) {
		// Check if message is invoice
		if ( strstr( $message->getSubject(), "חשבונית" ) ) {
			$target = $attach_folder . '/' . $message->getSender();
			$url    = $folder_url . '/' . $message->getSender();

			if ( ! file_exists( $target ) ) {
				mkdir( $target );
			}

			// Check if there is attachment
			$file = $message->SaveAttachment( $target );

			$text = "";
			if ( ! ( strlen( $file ) > 3 ) ) {
				$text = "אין מסמך";
			} else {
				$text = gui_hyperlink( basename( $file ), $url . '/' . $file );

			}
			$row = array(
				$message->getSubject(),
				$message->getSender(),
				$text
			);
			// gui_hyperlink("הורד קבצים", "admin-post.php?operation=download&id=" . $message->getI()));

			array_push( $rows, $row );
			// print $message->getSubject() . " " . $message->getSender();
		}
	}

	return $rows;
}
