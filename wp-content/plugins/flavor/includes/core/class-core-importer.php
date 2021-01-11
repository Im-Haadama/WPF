<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 18/02/19
 * Time: 18:23
 */
class Core_Importer {
	static private $_instance;

	private function __construct() {
		AddAction("add_conversion_key", array(__CLASS__, "add_conversion_key"));
		self::$_instance = $this;
	}

	/**
	 * @return Core_Importer
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	// conversion look like $conversion["name"] = array("שם", "תיאור")...
	// $conversion["price"] = array("מחיר")...
	// $map created. Looks like: $map["name"] = 1, $map["price"] = 2. etc.
	// The places the header was found in the header.
	// $fields - additional data set externaly.
	static function Import( $file_name, $table_name, $fields = null, &$unmapped = null)
	{
		if (! $file_name) {
			print "No file selected<br/>";
			return false;
		}
		if ( ! file_exists( $file_name ) ) {
			print "file not found<br/>";
			return false;
		}
		$conversion = array();

		if ( ! self::ReadConversion( $table_name, $conversion ) ) {
			throw new Exception( "Conversion table" );
		}

		$file = fopen( $file_name, "r" );
		if ( ! $file ) {
			throw new Exception( "file " . $file_name . " can't be open" );
		}
		$map = array();

		if ( ! self::ReadHeader( $file, $conversion, $map, $table_name, $unmapped ) ) {
			return false; // Failed.
		}

		$count     = 0;
		$dup_count = 0;
		$failed    = 0;

		while ( $line = fgetcsv( $file ) ) {
			switch ( self::ImportLine( $line, $table_name, $map, $fields ) ) {
				case -1:
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

	private static function ReadConversion( $table_name, &$conversion ) {
		if ( ! $table_name ) {
			throw new Exception( __METHOD__ . " no table sended" );
		}

		$sql = "SELECT col, header FROM im_conversion \n" .
		       "WHERE table_name = " . QuoteText( $table_name );

		$result = SqlQuery( $sql );

		if ( ! $result ) {
			// Try naive conversion
			// Todo: later.
		}

		while ( $conv = SqlFetchRow( $result ) ) {
			$col                = $conv[0];
			$row                = $conv[1];
//			print "$row $col<br/>";
			$conversion[ $row ] = $col;
		}

		return true;
	}

	static private function ReadHeader( &$file, $conversion, &$map, $table_name, &$unmapped =null)
	{
		// First line(s) may be empty.
		$done = false;
		$db_prefix = GetTablePrefix();

		$sql    = "describe ${db_prefix}$table_name";
		$result = SqlQuery( $sql );
		while ( $row = SqlFetchRow( $result ) ) {
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
			$found = false;
			if (! strlen($line[$i])) {
				print "Empty column $i header ignored<br/>";
				continue;
			}
			foreach ( $conversion as $k => $m ) {
//				print $k . " " . $line[$i] . "<br/>";
				if ( strlen($m) and (trim($k) == trim($line[ $i ])) ) {
//					print "$k $i<br/>";
					$map[ $m ] = $i;
					$found = true;
					break;
				}
			}
			if (! $found and is_array($unmapped)) {
				array_push( $unmapped, $line[ $i ] );
			}
		}
		if ($unmapped and count($unmapped)) return false;

//		print "map: <br/>";
//		foreach ($map as $k => $m)
//		{
//			print $k . " " . $m . "<br/>";
//		}

		return true;
	}

	private static function ImportLine( $line, $table_name, $map, $fields = null, $check_dup = null )
	{
//		show_errors();
		$db_prefix = GetTablePrefix();
		$insert_fields = array();
		$values        = array();
		if ( $fields ) {
			foreach ( $fields as $key => $field ) {
//				print "$key $field<br/>";
				array_push( $insert_fields, $key );
				array_push( $values, $field );
			}
		}
		foreach ( $map as $k => $m ) {
//			print "$k $m<br/>";
			if ($k == "Don't import") continue;
			if ( $m != - 1 ) {
				// Fix data.
				switch ( substr( SqlType( $table_name, $k ), 0, 3 ) ) {
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
				array_push( $values, EscapeString( $line[ $m ] ) );
			}
		}

		if (0 == count($insert_fields)) return 0; // Noting to insert

		$valid = apply_filters($table_name . '_check_valid', $insert_fields, $values);
		if (! $valid) return -1; // Line exists (duplicate) or data is missing

		$sql = "insert into ${db_prefix}$table_name (" . CommaImplode( $insert_fields ) .
		       ") values (" . CommaImplode( $values, true ) . ")";

//		MyLog($sql);
		if ( ! SqlQuery( $sql ) ) {
			// print "insert failed";
			return 0;
		}

		return 1;
	}

	static function add_conversion_key($post_file)
	{
		$header = GetParam("header");
		$table = GetParam("table", true);
		$header = GetParam("header", true);
		$args = [];

		$i = 0;
		foreach(SqlTableFields($table) as $field) {
			$args["values"][$i]['id'] = $i;
			$args["values"][$i]['name'] = $field;
			$i ++;
		}

		print "Select match field for $header<br>";
		print Core_Html::GuiSelect("table_field", null, $args);

		print Core_Html::GuiButton("btn_save_c", "Add", array("action" => "save_conversion('$post_file', '$header', '$table')"));
		return true;
	}
}