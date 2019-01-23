<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 22/01/19
 * Time: 16:38
 */

namespace Niver;

if ( ! defined( "ROOT_DIR" ) ) {
	define( 'ROOT_DIR', dirname( dirname( dirname( __FILE__ ) ) ) );
}

require_once( ROOT_DIR . '/niver/sql.php' );

class PivotTable {
	private $table_name;
	private $page;
	private $col;
	private $row;
	private $data;

	/**
	 * PivotTable constructor.
	 *
	 * @param $table_name
	 * @param $page
	 * @param $col
	 * @param $row
	 * @param $data
	 */
	public function __construct( $table_name, $page, $col, $row, $data ) {
		$this->table_name = $table_name;
		$this->page       = $page;
		$this->col        = $col;
		$this->row        = $row;
		$this->data       = $data;
	}

	public function Create() {
		$rows        = array();
		$cols        = array();
		$table       = array();
		$table[0][0] = "";
		$sql         = "select " . comma_implode_v( $this->col, $this->row, $this->data ) .
		               " from " . $this->table_name .
		               " Where " . $this->page . "\n" .
		               " order by 1 ";

		// print $sql . "<br/>";
		$results = sql_query( $sql );

		while ( $data = sql_fetch_assoc( $results ) ) {
			$row  = $data[ $this->row ];
			$col  = $data[ $this->col ];
			$cell = $data[ $this->data ];
//			print "row=$row col=$col cell=$cell<br/>";
			if ( ! isset( $table[ $row ] ) ) {
				$table[ $row ]    = array();
				$table[ $row ][0] = "" . $row;
				//			print "row: " . $row . "<br/>";
				array_push( $rows, $row );
			}
			if ( ! isset( $table[ $row ][ $col ] ) ) {
// 				print "col: " . $col . "<br/>";
				$table[ $row ][ $col ] = 0;
				$table[0][ $col ]      = "" . $col;
				array_push( $cols, $col );
			}

			$table[ $row ][ $col ] += $cell;
		}

		// Transform from key array to index array
//		$grid = array();
//		foreach ($rows as $row)
//			foreach ($cols as $col) {
//				$grid[0] = $cols;
//			}
		foreach ( $rows as $row ) {
			// print "row: " . $row . "<br/>";
			foreach ( $cols as $col ) {
				//	print "col: " . $col;
				if ( ! isset ( $table[ $row ][ $col ] ) ) {
					$table[ $row ][ $col ] = 0;
				}
			}
			ksort( $table[ $row ] );
//			$key = array_shift($table[$row]);
//			ksort ($table[$row]);
//			array_unshift($table[$row], $key);
		}

		// ksort( $table );
		return $table;
	}
}
