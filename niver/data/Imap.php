<?php

/**
 * Created by PhpStorm.
 * User: agla
 * Date: 24/01/19
 * Time: 13:16
 */

require_once( "ImapMessage.php" );

class Imap {

	private $inbox;
	private $messages;
	private $index;

	function Connect( $host, $user, $password ) {
		if ( strlen( $host ) < 5 ) {
			die ( "host not supplied" );
		}

		if ( strlen( $user ) < 4 ) {
			die ( "user not supplied" );
		}

		if ( strlen( $password ) < 5 ) {
			die ( "password not supplied" );
		}

		$this->inbox = imap_open( $host, $user, $password );

		return ( $this->inbox != null );
	}

	function Read() {
		$this->messages = imap_search( $this->inbox, 'ALL', SE_UID );
		$this->index    = 0;
	}

	function MoveMessage( $i, $folder_name ) {
//		print "i=" . $i . "<br/>";
//		print "f=" . $folder_name . "<br/>";
//		var_dump($this->inbox);

		imap_mail_move( $this->inbox, $i, $folder_name );
//		imap_mail_copy( $this->inbox, $i, $folder_name, CP_MOVE );

		imap_expunge( $this->inbox );
	}

	function ReadMessage( $idx ) {
		return new ImapMessage( $this->inbox, $idx );
	}

	function ReadNext() {
		if ( $this->index == count( $this->messages ) ) {
			return null;
		}

		$msg_no = $this->messages[ $this->index ++ ];
		// print "msg_no= " . $msg_no . "<br/>";

		if ( ! $msg_no ) {
			return null;
		}

		// print "readnext msg_no: " . $msg_no . "<br/>";
		$message = new ImapMessage( $this->inbox, $this->index ); // $i, $header, $subject, $sender, $date);

		return $message;
	}

	function save_html_as_csv( $inbox, $email_number, $supplier_name, $date ) {
		global $attach_folder;

		$message = imap_qprint( imap_fetchbody( $inbox, $email_number, 2 ) );

		$result_file = $supplier_name . "_" . $date . ".csv";

		$f = fopen( $attach_folder . "/" . $result_file, "w" );

		$dom = im_str_get_html( $message );

		foreach ( $dom->find( 'tr' ) as $row ) {
			foreach ( $row->find( 'td' ) as $col ) {
				$cell = htmlspecialchars_decode( $col->plaintext );
				$cell = preg_replace( "/\s|&nbsp;/", ' ', $cell );
				fwrite( $f, $cell . "," );
			}

			fwrite( $f, "\n" );
		}

		fclose( $f );

		return $result_file;
	}

	function read_inbox( $host, $user, $pass ) {
		$count = 0;

		print gui_header( 1, $user );

		if ( ! $inbox ) {
			print "can't open mailbox<br/>";
			// print $host;
			// print $user;
			return;
		}

		$msg_cnt = imap_num_msg( $inbox );

		print $msg_cnt . " messages in the inbox<br/>";

		$emails = imap_search( $inbox, 'ALL', SE_UID );

		print "<table>";
// for($i = 1; $i <= $msg_cnt; $i++) {
		foreach ( $emails as $email ) {
			$count ++;

//		if ($count > 10) {
//			print "debug<br/>";
//			die(3);
//		}
			$i       = imap_msgno( $inbox, $email );
			$header  = imap_headerinfo( $inbox, $i );
			$subject = header_subject( $header );
			$sender  = header_sender_email( $header );
			$date    = header_date( $header );
			$handled = false;

//	 print "Sender: $sender. Subject: $subject<br/>";
			$l = substr( $sender, 0, 4 );
			// print "\n" . $l . " " . $subject . "\n";

			switch ( strtolower( $sender ) ) {
				case "yab02@orange.net.il": // Amir ben yehuda
					if ( strstr( $subject, "רשימה" ) ) {
						handle_pricelist( $subject, $inbox, "amir", $date, $i, true );
						$handled = true;
					}
					break;

				case "batya_l@maabarot.com":
				case "yaakov.aglamaz@gmail.com":
				case "yaakov@im-haadama.co.il":
				case "limor_s@maabarot.com": // Sadot
					if ( strstr( $subject, "מחירון" ) ) {
						$handled = handle_pricelist( $subject, $inbox, "sadot", $date, $i );
						break;
					}
					if ( strstr( $subject, "הזמנה" ) ) {
						$handled = handle_supply( $subject, $inbox, "sadot", $date, $i );
						break;
					}
					break;
				case "office@yevulebar.co.il": // yb
//			print "start yb $subject<br/>";
					if ( strstr( $subject, "מלאי" ) ) {
						$handled = handle_pricelist( $subject, $inbox, "yb", $date, $i );
					}
					break;

				case "info@im-haadama.co.il":
				case "yaakov@im-haadama.co.il":
					if ( strstr( $subject, "מספר" ) and strstr( $subject, "משלוח" ) ) {
						handle_automail( $subject, $inbox, "delivery", $i );
						$handled = true;
					}
					if ( strstr( $subject, "מלקוח" ) ) {
						handle_automail( $subject, $inbox, "הזמנות", $i );
						$handled = true;
					}
					break;
				case "notify@google.com‏": // Doesn't catch. See below
					if ( strstr( $subject, "שורשים" ) ) {
						handle_google( $subject, $inbox, "yosef", $date, $i );
						$handled = true;
					}
					if ( strstr( $subject, "בן יהודה" ) ) {
						handle_google( $subject, $inbox, "amir", $date, $i );
						$handled = true;
					}
					break;
//		case "office@organya.co.il":
//			if (strstr($subject, "מחירון")){
//				$handled = handle_pricelist($subject, $inbox, "organya", $date, $i);
//			}
//			break;

				default:

			}
			if ( $sender == "notify@google.com" ) {
				if ( strstr( $subject, "שורשים" ) ) {
					// handle_google( $subject, $inbox, "yosef", $date, $i );

					// print "<tr><td>" . $subject . "</td>";
					$handled = true;
				}
				if ( strstr( $subject, "בן יהודה" ) ) {
					// print "<tr><td>" . $subject . "</td>";
					// print "amir<br/>";
					handle_google( $subject, $inbox, "amir", $date, $i );
					$handled = true;
				}
			}
			if ( ! $handled ) {
				print "<td>" . $sender . "</td><td>" . $subject . "</td><td>not handled<td/></tr>";
			}
		}
		print "</table>";


// Since we update manually on site 2, auto update just site 1.
//for ($site_id = 1; $site_id <= 1; $site_id++)
//	if ($changed_price[$site_id]) {
//		$html = run_in_server($site_id, "/catalog/catalog-auto-update.php?no_header");
//		print $html;
//	}
		imap_expunge( $inbox );
	}

}