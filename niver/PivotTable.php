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

require_once( ROOT_DIR . '/agla/sql.php' );

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
		$table   = array();
		$results = sql_query( "select " . comma_implode( $this->col, $this->row, $this->data ) .
		                      " from " . $this->table_name .
		                   " Where " . $this->page . "\n" );

		while ( $data = sql_fetch_assoc( $results ) ) {
			$row  = $data[ $this->row ];
			$col  = $data[ $this->row ];
			$data = $data[ $this->row ];
			if ( is_null( $table[ $row ] ) ) {
				$table[ $row ] = array();
			}
			if ( is_null( $table[ $col ] ) ) {
				$table[ $row ][ $col ] = 0;
			}

			$table[ $row ][ $col ] += $data;
		}

		return $table;
	}
}