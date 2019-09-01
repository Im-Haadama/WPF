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

require_once( ROOT_DIR . '/niver/data/sql.php' );
require_once( ROOT_DIR . '/tools/im_tools.php' );

class PivotTable {
	private $table_name;
	private $page;
	private $col, $sql_col;
	private $row, $sql_row;
	private $data, $sql_data;

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
		$this->sql_col        = $col;
		$this->sql_row        = $row;
		$this->sql_data       = $data;
		$this->row = ($s = strpos(strtolower($this->sql_row), "as")) ? trim(substr($this->sql_row, $s + 2)) : $this->row;
		$this->col = ($s = strpos(strtolower($this->sql_col), "as")) ? trim(substr($this->sql_col, $s + 2)) : $this->col;
		$this->data = ($s = strpos(strtolower($this->sql_data), "as")) ? trim(substr($this->sql_data, $s + 2)) : $this->data;
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
		$sql         = "select " . comma_implode_v( $this->sql_col, $this->sql_row, $this->sql_data ) .
		               " from " . $this->table_name .
		               " Where " . $this->page . "\n" .
		               " $order ";

		$results = sql_query( $sql );

		while ( $data = sql_fetch_assoc( $results ) ) {
			$row  = $data[ $this->row ];
			$col  = $data[ $this->col ];
			$cell = $data[ $this->data ];
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

			$table[ $row ][ $col ] += $cell;
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
