<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 20/02/17
 * Time: 17:34
 */

require_once( "mail-config.php" );

function read_mail( $host, $user, $pass, $search_strings ) {
	$attach_folder = "/home/agla/store/imap/attachment";

	$inbox = imap_open( $host, $user, $pass );
	if ( ! $inbox ) {
		print "Cannot open inbox\n";
		die( 1 );
	}

//    print "searching " . $search_string . "...<br/>";

	foreach ( $search_strings as $search_string ) {
		print "searching " . $search_string . "\n";
		$emails = imap_search( $inbox, $search_string );
		if ( $emails ) {
			print "found\n";
			break;
		}
	}

	if ( $emails ) {
		$count = 1;

		/* put the newest emails on top */
		rsort( $emails );

		/* for every email... */
		foreach ( $emails as $email_number ) {
//            print $email_number . "<br/>";
			/* get information specific to this email */
			$overview = imap_fetch_overview( $inbox, $email_number, 0 );

			$message = imap_fetchbody( $inbox, $email_number, 2 );

			/* get mail structure */
			$structure = imap_fetchstructure( $inbox, $email_number );

			$attachments = array();

			/* if any attachments found... */
			if ( isset( $structure->parts ) && count( $structure->parts ) ) {
				for ( $i = 0; $i < count( $structure->parts ); $i ++ ) {
					$attachments[ $i ] = array(
						'is_attachment' => false,
						'filename'      => '',
						'name'          => '',
						'attachment'    => ''
					);

					if ( $structure->parts[ $i ]->ifdparameters ) {
						foreach ( $structure->parts[ $i ]->dparameters as $object ) {
							if ( strtolower( $object->attribute ) == 'filename' ) {
								$attachments[ $i ]['is_attachment'] = true;
								$attachments[ $i ]['filename']      = $object->value;
							}
						}
					}

					if ( $structure->parts[ $i ]->ifparameters ) {
						foreach ( $structure->parts[ $i ]->parameters as $object ) {
							if ( strtolower( $object->attribute ) == 'name' ) {
								$attachments[ $i ]['is_attachment'] = true;
								$attachments[ $i ]['name']          = $object->value;
//                                print "\nyy:" . $object->value . "\n";
							}
						}
					}

					if ( $attachments[ $i ]['is_attachment'] ) {
						$attachments[ $i ]['attachment'] = imap_fetchbody( $inbox, $email_number, $i + 1 );

						/* 3 = BASE64 encoding */
						if ( $structure->parts[ $i ]->encoding == 3 ) {
							$attachments[ $i ]['attachment'] = base64_decode( $attachments[ $i ]['attachment'] );
						} /* 4 = QUOTED-PRINTABLE encoding */
						elseif ( $structure->parts[ $i ]->encoding == 4 ) {
							$attachments[ $i ]['attachment'] = quoted_printable_decode( $attachments[ $i ]['attachment'] );
						}
					}
				}
			}

			/* iterate through each attachment and save it */
			foreach ( $attachments as $attachment ) {
				$attachment_count = 0;
				if ( $attachment['is_attachment'] == 1 ) {
					$el          = imap_mime_header_decode( $attachment['name'] );
					$result_file = $attach_folder . "/" . date( 'Y-m-j' );
					$filename    = "";
					$s           = count( $el );
					for ( $ii = 0; $ii < $s; $ii ++ ) {
						$filename .= $el[ $ii ]->text;
					}
					$ext         = pathinfo( $filename, PATHINFO_EXTENSION );
					$result_file .= '.' . $ext;


					// $filename = $attachment['name'];
//                    if(empty($filename)) $filename = $attachment['filename'];
//
//                    if(empty($filename)) $filename = time() . ".dat";
//                    $folder = "attachment";
//                    if(!is_dir($folder))
//                    {
//                        mkdir($folder);
//                    }
//                    $save_filename = $folder ."/" . $resul;
//                    print "writing " . $save_filename . "<br/>";

					// $result_file1 = $result_file;
					// if ($attachment_count > 0) $result_file1 .= $attachment_count;

					// print $result_file1;
					$fp = fopen( $result_file, "w+" );
					fwrite( $fp, $attachment['attachment'] );
					fclose( $fp );

					return $result_file;
				}
			}
		}
	} else {
		print "nothing found\n";
		die ( 1 );
	}
}
//if( $emails ) {
//    foreach( $emails as $email_number ) {
//        $overview = imap_fetch_overview($inbox,$email_number,0);
//        $message = imap_fetchbody($inbox,$email_number,2);
//        $structure = imap_fetchstructure($inbox,$email_number);
//        // var_dump($overview);
//        // print $email_uid . "<br/>";
////        imap_mail_move($inbox, $email_uid, 'processed', CP_UID);
//    }
//}