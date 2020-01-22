<?php


class WPF_Company {
	private $id;

	public function __construct($id) {
		if (! ($id > 0)) die("invalid company_id: $id");
		$this->id = $id;

//		$this->term = get_term_by( 'name', $this->getName(), 'company_taxonomy');
//
//		if (!$this->term){
//			$rc = wp_insert_term(self::getName(), 'company_taxonomy');
//			$this->term = $rc;
//		}
	}

	function Manager()
	{
		return sql_query_single_scalar(" select admin from im_company where id = " . $this->id);
	}

	function getName()
	{
		return sql_query_single_scalar("select name from im_company where id = " . $this->id);
	}

	function GetWorkers()
	{
		$array = sql_query_array_scalar("select user_id from wp_usermeta " .
		                              " where meta_key = 'companies' and meta_value like '%:" . $this->id . ":%'");

		if ($array) return $array;

		return self::Manager();
	}
}