<?php

/**
 * Created by PhpStorm.
 * User: agla
 * Date: 24/01/19
 * Time: 15:04
 */
class ImapMessage {
	private $i;
	private $header;
	private $subject;
	private $sender;
	private $date;
	private $inbox;

	public function __construct( $inbox, $email ) { //$i, $header, $subject, $sender, $date ) {
		$this->inbox   = $inbox;
		$this->i       = imap_msgno( $inbox, $email );
		$this->header  = imap_headerinfo( $inbox, $this->i );
		$this->subject = $this->header_subject( $this->header );
		$this->sender  = $this->header_sender_email( $this->header );
		$this->date    = $this->header_date( $this->header );
	}

	private function header_subject( $header ) {
		$subject_parts = imap_mime_header_decode( $header->subject );
		$subject       = "";
		foreach ( $subject_parts as $subject_part ) {
			$subject .= $subject_part->text;
		}

		return $subject;
	}

	private function header_sender_email( $header ) {
		$from = $header->from[0];

		return $from->mailbox . "@" . $from->host;
	}

	private function header_date( $header ) {
		$date = date( "j.n.y", strtotime( $header->date ) );

		// print $date;
		return $date;
	}

	/**
	 * @return mixed
	 */
	public function getI() {
		return $this->i;
	}

	/**
	 * @return mixed
	 */
	public function getHeader() {
		return $this->header;
	}

	/**
	 * @return mixed
	 */
	public function getSubject() {
		return $this->subject;
	}

	/**
	 * @return mixed
	 */
	public function getSender() {
		return $this->sender;
	}

	/**
	 * @return mixed
	 */
	public function getDate() {
		return $this->date;
	}

	function SaveAttachment( $folder ) {
		/* get mail structure */
		$structure = imap_fetchstructure( $this->inbox, $this->i );

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
					$attachments[ $i ]['attachment'] = imap_fetchbody( $this->inbox, $this->i, $i + 1 );

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
				$el       = imap_mime_header_decode( $attachment['name'] );
				$filename = "";
				$s        = count( $el );
				for ( $ii = 0; $ii < $s; $ii ++ ) {
					$filename .= $el[ $ii ]->text;
				}
				// Save the file in the sender folder.

				if ( ! file_exists( $folder ) ) {
					die ( "folder " . $folder . " not exists" );
				}

				$result_file = $folder . "/" . $filename;
//				$ext         = pathinfo( $filename, PATHINFO_EXTENSION );
//				$result_file .= '.' . $ext;

				// $result_file1 = $result_file;
				// if ($attachment_count > 0) $result_file1 .= $attachment_count;

				// print $result_file1;
				// In case we run twice a day
				shell_exec( "rm $result_file" );
				// print "creating $result_file";
				if ( $fp = fopen( $result_file, "w+" ) ) {
					fwrite( $fp, $attachment['attachment'] );
					fclose( $fp );

					return $filename;
				} else {
					return null;
				}
			}
		}
	}

}