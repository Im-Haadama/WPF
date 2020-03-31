<?php

/**
 * Created by PhpStorm.
 * User: agla
 * Date: 18/02/19
 * Time: 18:23
 */
class Core_Importer {
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

//		foreach($map as $k => $i)
//			print "$k $i <br/>";

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
		if ( ! $table_name ) {
			throw new Exception( __METHOD__ . " no table sended" );
		}

		$sql = "SELECT col, header FROM nv_conversion \n" .
		       "WHERE table_name = " . QuoteText( $table_name );

		$result = sql_query( $sql );

		if ( ! $result ) {
			// Try naive conversion
			// Todo: later.
		}

		while ( $conv = sql_fetch_row( $result ) ) {
			$col                = $conv[0];
			$row                = $conv[1];
//			print "$row $col<br/>";
			$conversion[ $row ] = $col;
		}

		return true;
	}

	private function ReadHeader( &$file, $conversion, &$map, $table_name ) {
		// First line(s) may be empty.
		$done = false;
		$db_prefix = get_table_prefix();

		$sql    = "describe ${db_prefix}$table_name";
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
//			print $line[$i];
			$found = false;
			foreach ( $conversion as $k => $m ) {
//				print $k . " " . $line[$i] . "<br/>";
				if ( trim($k) == trim($line[ $i ]) ) {
//					print "$k $i<br/>";
					$map[ $m ] = $i;
					$found = true;
					break;
				}
			}
			if (! $found) print "Didn't find conversion to $line[$i] <br/>";
		}

//		print "map: <br/>";
//		foreach ($map as $k => $m)
//		{
//			print $k . " " . $m . "<br/>";
//		}

		return true;
	}

	private function ImportLine( $line, $table_name, $map, $fields = null, $check_dup = null )
	{
		$db_prefix = get_table_prefix();
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
					case "int":
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

			if ( call_user_func($check_dup, $insert_fields, $values ) ) {
				return - 1; // Already exists.
			}
		}

		$sql = "insert into ${db_prefix}$table_name (" . CommaImplode( $insert_fields ) .
		       ") values (" . CommaImplode( $values, true ) . ")";


		if ( ! sql_query( $sql ) ) {
			// print "insert failed";
			return 0;
		}

		return 1;
	}
}