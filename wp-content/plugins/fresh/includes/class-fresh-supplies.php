<?php


class Fresh_Supplies {
	static function print_driver_supplies( $mission_id = 0 ) {
		// Self collect supplies
		$data = "";
		$sql  = "SELECT s.id FROM im_supplies s
          JOIN im_suppliers r
          WHERE r.self_collect = 1
          AND s.supplier = r.id
          AND s.status IN (1, 3)" .
		        " AND (s.picked = 0 or isnull(s.picked))";

		// print $sql;

		if ( $mission_id ) {
			$sql .= " AND s.mission_id = " . $mission_id;
		}
		// DEBUG $data .= $sql;

		$supplies = SqlQueryArrayScalar( $sql );

		if ( count( $supplies ) ) {
			foreach ( $supplies as $supply ) {
//			   print "id: " . $supply . "<br/>";
				$data .= print_supply( $supply );
			}
		}

		return $data;
	}

}