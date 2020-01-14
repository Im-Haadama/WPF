<?php


class WPF_Company {
	private $id;

	public function __construct($id) {
		if (! ($id > 0)) die("invalid company_id: $id");
		$this->id = $id;
	}

	function getName()
	{
		return sql_query_single_scalar("select name from im_company where id = " . $this->id);
	}

}