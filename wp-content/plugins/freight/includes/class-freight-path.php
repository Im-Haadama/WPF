<?php


class Freight_Path {
	private $id;
	private $description;
	private $zones;

	/**
	 * Freight_Path constructor.
	 *
	 * @param $id
	 */
	public function __construct( $id ) {
		$db_prefix = get_table_prefix();

		$this->id = $id;
		$row = sql_query_single_assoc("select * from ${db_prefix}paths where id = $id");
		$this->description = $row['description'];
		$this->zones = $row['zones'];
	}

	/**
	 * @return mixed
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * @return mixed|string
	 */
	public function getDescription() {
		return $this->description;
	}

	/**
	 * @return mixed|string
	 */
	public function getZones() {
		return explode(':', $this->zones);
	}

	public function setDays($days)
	{
		sql_query("update im_paths set days = '" . implode(":", $days) . "'" .
						" where id = " . $this->id );
	}

}