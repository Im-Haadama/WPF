<?php


class Org_Company {
	private $id;

	/**
	 * Org_Company constructor.
	 *
	 * @param $id
	 */
	public function __construct( $id ) {
		$this->id = $id;
	}

	/**
	 * @return mixed
	 */
	public function getId() {
		return $this->id;
	}

	public function getName() {
		return sql_query_single_scalar("select name from im_company where id = " .$this->id);
	}

	public function getWorkers()
	{
		return sql_query_array_scalar("select user_id from wp_usermeta where meta_key = 'companies' and meta_value like '%:" . $this->id . ":%'");

	}

}

//class WPF_Company {
//
//	function Manager() {
//		return sql_query_single_scalar( " select admin from im_company where id = " . $this->id );
//	}
//
//	function getName() {
//		return sql_query_single_scalar( "select name from im_company where id = " . $this->id );
//	}
//
//	function GetWorkers() {
//		$array = sql_query_array_scalar( "select user_id from wp_usermeta " .
//		                                 " where meta_key = 'companies' and meta_value like '%:" . $this->id . ":%'" );
//
//		if ( $array ) {
//			return $array;
//		}
//
//		return self::Manager();
//	}
//}