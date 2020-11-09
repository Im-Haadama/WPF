<?php


class Org_Company {
	private $id;

	/**
	 * Org_Company constructor.
	 *
	 * @param $id
	 */
	public function __construct( int $id ) {
		$this->id = $id;
	}

	/**
	 * @return mixed
	 */
	public function getId() {
		return $this->id;
	}

	public function getName() {
		return SqlQuerySingleScalar( "select name from im_company where id = " . $this->id);
	}

	public function getManager(){
        return SqlQuerySingleScalar("select admin from im_company where id = " . $this->id);
    }

	public function getWorkers($include_name = false)
	{
        $users_array = SqlQueryArrayScalar( "select user_id from wp_usermeta where meta_key = 'companies' and meta_value like '%:" . $this->id . ":%'");
	    $manager = $this->getManager();
        if (! in_array($manager, $users_array)) array_push($users_array, $manager);

        if ($include_name) foreach($users_array as $key => $user_id) {
        	$u = new Core_Users($user_id);
//        	print $u->getName() . "<br/>";
//        	var_dump($u->getNa
        	$users_array[$key] = array("id" => $user_id, "name" => $u->getName());
        }
		return $users_array;
	}

    /**
     * return all the teams that users in the company belong to.
     */
    public function getTeams()
    {
    	$db_prefix = GetTablePrefix("working_teams");
        return SqlQueryArrayScalar("select id from ${db_prefix}working_teams where company_id = " . $this->id .
        " and team_name not like '%Personal%'");
    }

	public function AddWorker($w_id)
	{
		$w = new Org_Worker($w_id);
		$w->AddCompany($this->id);
	}

	public function RemoveWorker($w_id)
	{
		$w = new Org_Worker($w_id);
		$w->RemoveCompany($this->id);
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
