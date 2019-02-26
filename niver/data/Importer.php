<?php

/**
 * Created by PhpStorm.
 * User: agla
 * Date: 18/02/19
 * Time: 18:23
 */
class Importer {
	// conversion look like $conversion["name"] = array("שם", "תיאור")...
	// $conversion["price"] = array("מחיר")...
	// $map created. Looks like: $map["name"] = 1, $map["price"] = 2. etc.
	// The places the header was found in the header.
	// $fields - additional data set externaly.
	function Import( $file_name, $table_name, $fields = null, $check_dup = null ) {
		$conversion = array();

		if ( ! $this->ReadConversion( $table_name, $conversion ) ) {
			throw new Exception( "Conversion table" );
		}

		if ( ! file_exists( $file_name ) ) {
			throw new Exception( "file " . $file_name . " doesn't exists" );
		}
		$file = fopen( $file_name, "r" );
		if ( ! $file ) {
			throw new Exception( "file " . $file_name . " can't be open" );
		}
		$map = array();

		if ( ! $this->ReadHeader( $file, $conversion, $map, $table_name ) ) {
			return false; // Failed.
		}

		$count     = 0;
		$dup_count = 0;
		$failed    = 0;

		while ( $line = fgetcsv( $file ) ) {

			switch ( $this->ImportLine( $line, $table_name, $map, $fields, $check_dup ) ) {
				case - 1:
					$dup_count ++;
					break;
				case 0:
					$failed ++;
					break;
				case 1:
					$count ++;
					break;
			}
		}
		if ( $count == 0 and ( $failed ) ) {
			throw new Exception( "Can't import" );
		}

		// Import data.
		return array( $count, $dup_count, $failed);
	}

	// Returns:
	// 1 - row inserted.
	// 0 - failed.
	// -1 - row exists (check_dup)

	private function ReadConversion( $table_name, &$conversion ) {
		global $conn;

		if ( ! $conn ) {
			throw new Exception( "not connected" );
		}

		if ( ! $table_name ) {
			throw new Exception( __METHOD__ . " no table sended" );
		}

		$sql = "SELECT col, header FROM nv_conversion \n" .
		       "WHERE table_name = " . quote_text( $table_name );

		$result = sql_query( $sql );

		if ( ! $result ) {
			// Try naive conversion
			// Todo: later.
		}

		while ( $conv = sql_fetch_row( $result ) ) {
			$col                = $conv[0];
			$row                = $conv[1];
			$conversion[ $col ] = $row;
		}

		return true;
	}

	private function ReadHeader( &$file, $conversion, &$map, $table_name ) {
		// First line(s) may be empty.
		$done = false;

		$sql    = "describe $table_name";
		$result = sql_query( $sql );
		while ( $row = sql_fetch_row( $result ) ) {
			$map[ $row[0] ] = - 1; // Init - not found;
		}

		$line = null;
		do {
			$line = fgetcsv( $file );
			if ( count( $line ) > 1 ) {
				$done = true;
			}
		} while ( ! $done );

//		print "conv:<br/>";
//		foreach ($conversion as $k => $m)
//		{
//			print $k . " " . $m . "<br/>";
//		}

		for ( $i = 0; $i < count( $line ); $i ++ ) {
			foreach ( $conversion as $k => $m )
				if ( $m == $line[ $i ] )
					$map[ $k ] = $i;
		}

//		print "map: <br/>";
//		foreach ($map as $k => $m)
//		{
//			print $k . " " . $m . "<br/>";
//		}

		return true;
	}

	private function ImportLine( $line, $table_name, $map, $fields = null, $check_dup = null ) {
		$insert_fields = array();
		$values        = array();
		if ( $fields ) {
			foreach ( $fields as $key => $field ) {
				array_push( $insert_fields, $key );
				array_push( $values, $field );
			}
		}
		foreach ( $map as $k => $m ) {
			if ( $m != - 1 ) {
				// Fix data.
				switch ( substr( sql_type( $table_name, $k ), 0, 3 ) ) {
					case 'flo':
						if ( $line[ $m ] == '' ) {
							$line[ $m ] = 0;
						}
						break;

					case 'int':
						if ( $line[ $m ] == '' ) {
							$line[ $m ] = 0;
						}
						break;

					case 'dat':
						// Todo: figure out date format and parse it. For now assume dd/mm/yy
						$date = $line[ $m ];
						$ye   = substr( $date, 6, 2 );
						$mo   = substr( $date, 3, 2 );
						$da   = substr( $date, 0, 2 );
						$date = $mo . '/' . $da . '/' . $ye;
						$date = date( 'Y-m-d', strtotime( $date ) );
						// print $date . '<br/>';
						$line[ $m ] = $date;
						break;
				}

				array_push( $insert_fields, $k );
				array_push( $values, escape_string( $line[ $m ] ) );
			}
		}
		if ( $check_dup ) {
			// print "checking dup ";
			if ( $check_dup( $insert_fields, $values ) ) {
				// print "dup";
				return - 1; // Already exists.
			}
		}

		$sql = "insert into $table_name (" . comma_implode( $insert_fields ) .
		       ") values (" . comma_implode( $values, true ) . ")";


		if ( ! sql_query( $sql ) ) {
			// print "insert failed";
			return 0;
		}

		return 1;
	}
}