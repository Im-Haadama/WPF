<?php

/**
 * Created by PhpStorm.
 * User: agla
 * Date: 24/01/19
 * Time: 15:04
 */
class ImapMessage {
	private $index;
	private $header;
	private $subject;
	private $sender;
	private $date;
	private $inbox;
	private $attachments;

//	private $message_no;

	public function __construct( $inbox, $index ) { //$i, $header, $subject, $sender, $date ) {
		// print "email: " . $email;
		// print "msg_no: " . $index . "<br/>";
		$this->inbox = $inbox;
		$this->index = $index;
		// $this->message_no = imap_msgno($inbox, $index);
		$this->header      = imap_headerinfo( $inbox, $index );
		$this->subject     = $this->header_subject( $this->header );
		$this->sender      = $this->header_sender_email( $this->header );
		$this->date        = $this->header_date( $this->header );
		$this->attachments = null; // Read only if needed
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
		$date = date( 'Y-m-d', strtotime( $header->date ) );

		// print $date;
		return $date;
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
	 * @return int
	 */
//	public function getMessageNo(): int {
//		return $this->message_no;
//	}

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

	function GetAttachmentName( $index = 1 ) {
		$this->read_attachments();
		// var_dump($this->attachments);
		$attachment = $this->attachments[ $index ];
		$el         = imap_mime_header_decode( $attachment['name'] );
		// var_dump($el);
		$filename = "";
		$s        = count( $el );
		for ( $ii = 0; $ii < $s; $ii ++ ) {
			$filename .= $el[ $ii ]->text;
		}

		return $filename;
	}

	private function read_attachments() {
		if ( $this->attachments ) {
			return;
		}
		$structure = imap_fetchstructure( $this->inbox, $this->getIndex() );

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
					$attachments[ $i ]['attachment'] = imap_fetchbody( $this->inbox, $this->getIndex(), $i + 1 );

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
		$this->attachments = $attachments;
	}

	/**
	 * @return mixed
	 */
	public function getIndex() {
		return $this->index;
	}

	function SaveAttachment( $folder ) {
		/* get mail structure */
		$this->read_attachments();

		// var_dump($this->attachments);

		/* iterate through each attachment and save it */
		foreach ( $this->attachments as $attachment ) {
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