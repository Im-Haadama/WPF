<?php


class WPF_Company {
	private $id;
	private $term;

	public function __construct($id) {
		if (! ($id > 0)) die("invalid company_id: $id");
		$this->id = $id;

		$this->term = get_term_by( 'name', $this->getName(), 'company_taxonomy');

//		var_dump($this->term);
		if (!$this->term){
			$rc = wp_insert_term(self::getName(), 'company_taxonomy');
			$this->term = $rc;
		}
	}

	function getName()
	{
		return sql_query_single_scalar("select name from im_company where id = " . $this->id);
	}
}