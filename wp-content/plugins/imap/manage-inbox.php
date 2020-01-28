<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 16/06/17
 * Time: 10:42
 */
define( 'STORE_DIR', dirname( dirname( __FILE__ ) ) );

require_once( STORE_DIR . '/core/data/im_simple_html_dom.php' );
$filename = STORE_DIR . '/imap/mail-config.php';

if ( ! file_exists( $filename ) ) {
	print "config file " . $filename . " not found<br/>";
	die ( 1 );
}
require_once( $filename );

if (! defined('IM_ATTACHMENTS')) {
	print "define IM_ATTACHMENTS in config file";
	die (1);
}

// print __FILE__;
require_once( STORE_DIR . '/fresh-totals/gui/inputs.php' );
require_once( STORE_DIR . '/core/fund.php' );

$debug = GetParam( "debug" );
if ( $debug ) {
	print "Debug mode<br/>";
}

$changed_price = array();
$site_tools    = array(
	"", // 0
	"http://store.im-haadama.co.il/tools", // 1
	"http://super-organi.co.il/fresh/", // 2
	"", // 3
	"http://fruity.co.il/tools" // 4
);
$attach_folder = IM_ATTACHMENTS; // ROOT_DIR . "/attachments";

// header_text
$text = '<html>';
$text .= '<head>';
$text .= '<meta http-equiv="content-type" content="text/html; charset=utf-8">';
$text .= '</head>';

print $text;

for ( $i = 0; $i < count( $hosts ); $i ++ ) {
//	print $i . "<br/>";
	read_inbox( $hosts[ $i ], $users[ $i ], $passes[ $i ], $debug );
}

return;

function read_inbox( $host, $user, $pass, $debug ) {

	$count = 0;

	print gui_header( 1, $user );
	$inbox = imap_open( $host, $user, $pass );

	if ( ! $inbox ) {
		print "can't open mailbox<br/>";
//		 print $host . "<br/>";
//		 print $user . "<br/>";
//		 print $pass . "<br/>";
		return false;
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
				//    print "start yb $subject<br/>";
				if ( strstr( $subject, "מלאי" ) ) {
					$handled = handle_pricelist( $subject, $inbox, "yb", $date, $i, null, $debug );
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

function run_in_server( $site_id, $relative_url ) {
	global $site_tools;
	global $attach_folder;

	// print $relative_url;

	$full_url = $site_tools[ $site_id ] . $relative_url;
	$html     = im_file_get_html( $full_url );
	$log_file = $attach_folder . "/" . date( 'j.n.y' ) . ".log";
	shell_exec( "mv $log_file $log_file" . time() );

	$file = fopen( $log_file, "w" );
	fwrite( $file, $html );
	fclose( $file );

	return $html;
}

function handle_supply_in_server( $site_id, $supplier, $file ) {
	$relative_url = "/supplies/create-supply.php?supplier_name=" . $supplier;
	$relative_url .= "&file=" . rtrim( $file );

	$html = run_in_server( $site_id, $relative_url );
	foreach ( preg_split( "/\<br(\s*)?\/?\>/i", $html ) as $line ) {
		if ( strstr( $line, "נוצרה" ) ) {
			return $line;
		}
	}

	return $html . " " . $relative_url;
}

function handle_in_server( $site_id, $supplier, $file = null, $debug = false ) {
	$relative_url = "/pricelist/update-pricelist.php?supplier_name=" . $supplier;
	if ( $file ) {
		$relative_url .= "&file=" . rtrim( basename($file) );
	}
	if ( $debug ) {
		$relative_url .= "&debug=1";
	}

	if ( $debug )
		print $relative_url . "<br/>";

	$html = run_in_server( $site_id, $relative_url );
	foreach ( preg_split( "/\<br(\s*)?\/?\>/i", $html ) as $line ) {
		if ( strstr( $line, "נקראו" ) ) {
			return $line;
		}
	}

	return $html . " " . $relative_url;
}

function handle_google( $subject, $inbox, $supplier, $date, $i ) {
	$result = handle_in_server( 4, $supplier );
	if ( strstr( $result, "נקראו" ) ) {
		$success = "עודכן";
	} else {
		$success = "נכשל";
	}

	$line = "<tr>";
	$line .= "<td>" . $subject . "</td>";
	$line .= "<td>" . $supplier . "</td>";
	$line .= "<td>" . $supplier . "</td>";
	$line .= "<td/>" . $success . "</td>";
	$line .= "</tr>";

	imap_mail_move( $inbox, $i, $supplier );
	print $line;
}

function handle_automail( $subject, $inbox, $folder, $i ) {
	$line = "<tr>";
	$line .= "<td>" . $subject . "</td>";
	$line .= "<td>" . $folder . "</td>";
	$line .= "<td/>" . "</td>";
	$line .= "</tr>";
	// Todo: verify that no replay was made
	imap_mail_copy( $inbox, $i, $folder, CP_MOVE );

	print $line;
}

function handle_supply( $subject, $inbox, $supplier, $date, $i, $text = false ) {
	global $changed_price;

//	if (0) {
	$line = "<tr>";
	$line .= "<td>" . $subject . "</td>";
	$line .= "<td>" . $supplier . "</td>";
	if ( $text ) {
		// Save mail text. For amir.
		$file = save_html_as_csv( $inbox, $i, $supplier, $date );
	} else {
		print "saving attachment<br/>";
		if ( $file = save_attachment( $inbox, $i, $supplier, $date ) ) {
			$command = "/home/agla/store/utils/" . $supplier . "2csv.sh $file "; // 2>1 | tail -1
			print "running: " . $command . "<br/>";
			$file = shell_exec( $command );
			// $csv_file =shell_exec("echo lalala");
			// print "csv_file: " . $csv_file ."<br/>";
			// Now run update in store
		} else {
			print "Failed. No file. <br/>";

			return "no file";
		}
	}
	// $result[2] = handle_in_server(2, $supplier, $file);
	// if (strstr($result[2], "נקראו")) $changed_price[2] = true;
	// print "Result2: " . $result[2]. "<br/>";

//	$result[1] = handle_in_server(1, $supplier, $file);
//	if (strstr($result[1], "נקראו")) $changed_price[1] = true;
	$result = handle_supply_in_server( 4, $supplier, $file );
	print $result;
	if ( strstr( $result, "נקראו" ) ) {
		$changed_price[3] = true;
	}
	// print "Result1: " . $result[1]. "<br/>";

	$line .= "<td/>" . $result . "</td>";
	$line .= "</tr>";

	print $line;
//	}
	if ( strstr( $result, "נקראו" ) ) {
		// print $line;
		imap_mail_move( $inbox, $i, $supplier );

		// print "MOVE: " . $move_result . "<br/>";
		return true;
	}

	return false;
}

function handle_pricelist( $subject, $inbox, $supplier, $date, $i, $text = false, $debug = false ) {
	// print "hjd=" . $debug . "<br>";
	$debug = true;
	global $changed_price;

//	if (0) {
	$line   = "<tr>";
	$line   .= "<td>" . $subject . "</td>";
	$line   .= "<td>" . $supplier . "</td>";
	$result = array();
	if ( $text ) {
		// Save mail text. For amir.
		$file = save_html_as_csv( $inbox, $i, $supplier, $date );
	} else {
		if ( $debug ) {
			print "saving attachment<br/>";
		}
		if ( $file = save_attachment( $inbox, $i, $supplier, $date, $debug ) ) {
			if ( $debug )
				print "saved " . $file . "<br/>";

			$command = "/home/agla/store/utils/" . $supplier . "2csv.sh $file "; // 2>1 | tail -1
			if ( $debug )
				print "running: " . $command . "<br/>";
			$file = shell_exec( $command );
			// $csv_file =shell_exec("echo lalala");
			// print "csv_file: " . $csv_file ."<br/>";
			// Now run update in store
		} else {
			print "Failed. No file. <br/>";

			return;
		}
	}
	// $result[2] = handle_in_server(2, $supplier, $file);
	// if (strstr($result[2], "נקראו")) $changed_price[2] = true;
	// print "Result2: " . $result[2]. "<br/>";

	//	$result[1] = handle_in_server(1, $supplier, $file);
	//	if (strstr($result[1], "נקראו")) $changed_price[1] = true;
	if (! file_exists($file)){
		print $file . " not found. failed ";
//		return false;
	}
	if ($debug){
		print "handling in server ";
	}
	$result[3] = handle_in_server( 4, $supplier, $file, $debug );
	if ( strstr( $result[3], "נקראו" ) ) {
		$changed_price[3] = true;
	}
	if ($debug) print $result[3];

	// print "Result1: " . $result[1]. "<br/>";

	$line .= "<td/>" . $result[3] . " " . $result[3] . "</td>";
	$line .= "</tr>";

	print $line;
//	}
	if ( strstr( $result[3], "נקראו" ) ) {
		// print $line;
		imap_mail_move( $inbox, $i, $supplier );

		// print "MOVE: " . $move_result . "<br/>";
		return true;
	}

	return false;
}

function header_sender_email( $header ) {
	$from = $header->from[0];

	// var_dump($from);
	return $from->mailbox . "@" . $from->host;
}

function header_date( $header ) {
	$date = date( "j.n.y", strtotime( $header->date ) );

	// print $date;
	return $date;
}

function header_subject( $header ) {
	$subject_parts = imap_mime_header_decode( $header->subject );
	$subject       = "";
	foreach ( $subject_parts as $subject_part ) {
		$subject .= $subject_part->text;
	}

	return $subject;
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

function save_attachment( $inbox, $email_number, $supplier_name, $date, $debug = null ) {
	global $attach_folder;

//	$overview = imap_fetch_overview( $inbox, $email_number, 0 );

//	$message = imap_fetchbody( $inbox, $email_number, 2 );

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
		if ( $attachment['is_attachment'] == 1 ) {
			$el          = imap_mime_header_decode( $attachment['name'] );
			$result_file = $attach_folder . "/" . $supplier_name . "_" . $date;
			$filename    = "";
			$s           = count( $el );
			for ( $ii = 0; $ii < $s; $ii ++ ) {
				$filename .= $el[ $ii ]->text;
			}
			$ext         = pathinfo( $filename, PATHINFO_EXTENSION );
			$result_file .= '.' . $ext;

			if ( $debug )
				print "result = " . $result_file . "<br/>";

			// $result_file1 = $result_file;
			// if ($attachment_count > 0) $result_file1 .= $attachment_count;

			// print $result_file1;
			// In case we run twice a day
			shell_exec( "rm $result_file" );
			if ( $fp = fopen( $result_file, "w+" ) ) {
				fwrite( $fp, $attachment['attachment'] );
				fclose( $fp );

				return $result_file;
			} else {
				return null;
			}
		}
	}
}
