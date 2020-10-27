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

	public function getWorkers()
	{
        $users_array = SqlQueryArrayScalar( "select user_id from wp_usermeta where meta_key = 'companies' and meta_value like '%:" . $this->id . ":%'");
	    $manager = $this->getManager();
        if (! in_array($manager, $users_array)) array_push($users_array, $manager);
		return $users_array;
	}

    /**
     * return all the teams that users in the company belong to.
     */
    public function getTeams()
    {
        $teams_array = array();
        $workers = self::getWorkers();
        foreach ($workers as $worker){
            $worker_teams = CommaArrayExplode(get_usermeta($worker, 'teams'));
            foreach ($worker_teams as $worker_team){
                $team_id = new Org_Team($worker_team);
                $team_name = $team_id->getName();
                $team = array( "id" =>$worker_team, "team_name" =>$team_name);
                if(!in_array( $team,$teams_array) and $team["team_name"] != null ) // check if a team is already exist
                    array_push($teams_array,$team);
            }
            return $teams_array;

        }
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