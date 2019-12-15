<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 25/01/19
 * Time: 07:49
 */
require_once( FRESH_INCLUDES . '/niver/data/Imap.php' );

function inbox_files( $hostname, $mail_user, $password, $attach_folder, $folder_url, $subjects ) {
	$m = new Imap();

	if ( ! $m->Connect( $hostname, $mail_user, $password ) ) {
		die( "can't connect to mail" );
	}

	if ( ! $m->Read() ) {
		print "Nothing found";

		return null;
	};

	$rows = array( array( "נושא", "שולח", "הורדה" ) );

	if ( ! file_exists( $attach_folder ) ) {
		if ( ! mkdir( $attach_folder ) ) {
			die ( "can't create " . $attach_folder );
		}
	}

	while ( $message = $m->ReadNext() ) {
//		 print "sub= " . $message->getSubject() . "<br/>";
		// Check if message is invoice
		$found = false;
		foreach ( $subjects as $subject ) {
//			print $subject;
			if ( strstr( $message->getSubject(), $subject ) ) {
				$found = true;
				break;
			}
		}


		if ( $found ) {
//			$target = $message->getAttachment
			$target = $attach_folder . '/' . $message->getSender();
			$url    = $folder_url . '/' . $message->getSender();

			if ( ! file_exists( $target ) ) {
				mkdir( $target );
			}

			// Check if there is attachment
			$file = $message->SaveAttachment( $target );
			//	print "file=" . $file ."<br/>";

			if ( ! ( strlen( $file ) > 3 ) ) {
				$text = "אין מסמך";
			} else {
				$text = gui_hyperlink( substr( basename( $file ), 0, 20 ), $url . '/' . $file, "right" );
			}
			$row = array(
				substr( $message->getSubject(), 0, 20 ),
				$message->getSender(),
				$text,
				$message->getDate(),
				$message->getIndex()
			);
			// gui_hyperlink("הורד קבצים", "admin-post.php?operation=download&id=" . $message->getI()));

			array_push( $rows, $row );
			// print $message->getSubject() . " " . $message->getSender();
		}
	}

	return $rows;
}
