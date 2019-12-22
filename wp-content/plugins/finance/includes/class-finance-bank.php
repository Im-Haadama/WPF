<?php


class Finance_Bank
{
	public function last_load()
	{
		return sql_query_array("select account_id, max(date) from im_bank group by account_id");
	}

	public function  show_status()
	{
		$result = gui_header(2, "last bank load");

		$last = self::last_load();
		if (! $last) $result .= "No transactions yet";
		else $result .= gui_table_args($last);
		return $result;
	}
}