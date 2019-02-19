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
	function Import( $file_name, $table_name ) {
		$conversion = array();

		if ( ! $this->ReadConversion( $table_name, $conversion ) ) {
			throw new Exception( "Conversion table" );
		}

		$file = fopen( $file_name, "r" );
		if ( ! $file ) {
			throw new Exception( "file " . $file . " can't be open" );
		}
		$map = array();

		if ( ! $this->ReadHeader( $file, $conversion, $map ) ) {
			return false; // Failed.
		}

		var_dump( $map );

		// Import data.
		return true;
	}

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

	private function ReadHeader( &$file, $conversion, &$map ) {
		// First line(s) may be empty.
		$done = false;

		$line = null;
		do {
			$line = fgetcsv( $file );
			if ( count( $line ) > 1 ) {
				$done = true;
			}
		} while ( ! $done );

		for ( $i = 0; $i < count( $line ); $i ++ ) {
			$key = $line[ $i ];
			for ( $j = 0; $j < count( $conversion ); $j ++ ) {
				if ( in_array( $key, $conversion[ $j ] ) ) {
					$map[ $conversion[ $j ] ] = $i;
					break;
				}
			}
		}

		return true;
	}
}