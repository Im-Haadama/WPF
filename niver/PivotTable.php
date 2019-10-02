<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 22/01/19
 * Time: 16:38
 */

namespace Niver;

use mysql_xdevapi\Exception;

if ( ! defined( "ROOT_DIR" ) ) {
	define( 'ROOT_DIR', dirname( dirname( dirname( __FILE__ ) ) ) );
}

require_once( ROOT_DIR . '/niver/data/sql.php' );

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
	 *
	 * @throws \Exception
	 */
	public function __construct( $table_name, $page, $col, $row, $data ) {
		if (! strlen($table_name) > 1) throw new \Exception(__CLASS__ . " no table_name");
		if (! strlen($page) > 1) throw new \Exception(__CLASS__ . " no page");
		if (! strlen($col) > 1) throw new \Exception(__CLASS__ . " no col");
		if (! strlen($row) > 1) throw new \Exception(__CLASS__ . " no row");
		if (! strlen($data) > 1) throw new \Exception(__CLASS__ . " no data");
		$this->table_name = $table_name;
		$this->page       = $page;
		$this->col        = $col;
		$this->row        = $row;
		$this->data       = $data;
//		$this->row = ($s = strpos(strtolower($this->sql_row), "as")) ? trim(substr($this->sql_row, $s + 2)) : $this->row;
//		$this->col = ($s = strpos(strtolower($this->sql_col), "as")) ? trim(substr($this->sql_col, $s + 2)) : $this->col;
//		$this->data = ($s = strpos(strtolower($this->sql_data), "as")) ? trim(substr($this->sql_data, $s + 2)) : $this->data;
	}

	public function Create( $add_url, $row_url = null, $args = null ) {
		// var_dump($row_trans);
		$rows        = array();
		$cols        = array();
		$table       = array();
		$row_trans = GetArg($args, "row_trans", null);
		$order = GetArg($args, "order", null);

		$debug = false;
		if ($debug){
			print "row: " . $this->row ."<br/>";
			print "col: " . $this->col ."<br/>";
			print "data: " . $this->data ."<br/>";
		}
		$table[0][0] = "";
		$sql         = "select " . comma_implode_v( $this->col, $this->row, $this->data ) .
		               " from " . $this->table_name .
		               " Where " . $this->page . "\n" .
		               " $order ";

		// print $sql;

		$results = sql_query( $sql );

		while ( $data = sql_fetch_assoc( $results ) ) {
			$row  = $data[ $this->row ];
			$col  = $data[ $this->col ];
			$cell = $data[ $this->data ];
//			if (! $row || ! $col) {
//				throw new \Exception("bad configuration" . __CLASS__ . ":" . __FUNCTION__);
//			}
//			print "row = $row, col = $col, cell = $cell<br/>";
			if ( ! isset( $table[ $row ] ) ) {
				// Open new row.
				$table[ $row ]    = array();

				// Set the label - include url if provided
				if ( isset( $row_trans[ $this->row ] ) ) {
					$label = $row_trans[ $this->row ]( $row );
				} else {
					$label = $row;
				}

				if ( isset( $row_url ) ) {
					$table[ $row ][0] = gui_hyperlink( $label, sprintf( $row_url, $row ) );
				} else {
					$table[ $row ][0] = $label;
				}

				array_push( $rows, $row );
			}
			if ( ! isset( $table[ $row ][ $col ] ) ) {
// 				print "col: " . $col . "<br/>";
				$table[ $row ][ $col ] = 0;
				$table[0][ $col ]      = "" . $col;
				array_push( $cols, $col );
			}
			if ($row > 0)
				$table[ $row ][ $col ] += floatval($cell);

		}

		foreach ( $rows as $row ) {
			foreach ( $cols as $col ) {
				if ( ! isset ( $table[ $row ][ $col ] ) ) {
					$u                     = sprintf( $add_url, $row, $col );
					$table[ $row ][ $col ] = gui_hyperlink( "0", $u);
				}
			}
			ksort( $table[ $row ] );
		}

		return $table;
	}
}
