<?php


class Org_Team {
	private $id;

	/**
	 * Org_Team constructor.
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

	function getName()
	{
		return sql_query_single_scalar("select team_name from im_working_teams where id = " . $this->id);
	}

	static function getByName($name)
	{
		$id = sql_query_single_scalar("select id from im_working_teams where team_name = " . QuoteText($name));
		if ($id)
			return new self($id);
		return null;
	}

	static function team_get_name($team_id)
	{
		return sql_query_single_scalar("select team_name from im_working_teams where id = " . $team_id);
	}

	/**
	 * @param $worker_id
	 *
	 * @return array
	 * @throws Exception
	 */
	static function team_managed_teams($worker_id)
	{
		$result = sql_query_array_scalar("select id from im_working_teams where manager = " . $worker_id);
		return $result;
		// if (! $result) team_add($worker_id, "");
	}

	static function team_all_members($team_id)
	{
		// return sql_query_array_scalar("select id from im_working_teams where manager = " . $user_id);
		return sql_query_array_scalar("select user_id from wp_usermeta where meta_key = 'teams' and meta_value like '%:" . $team_id . ":%'");
	}

	function RemoveMember($members)
	{
		$team_id = $this->id;
		if (is_array($members)){
			foreach ($members as $member)
				if (! self::RemoveMember($member)) return false;
			return true;
		}
		$member = $members;
		$current = get_usermeta($member, 'teams');
		$teams = CommaArrayExplode($current);

		$idx = array_search($team_id, $teams);
		if ($idx === false) {
			print "not member" . Core_Html::Br();
			return false;
		}

		unset ($teams[$idx]);

//	debug_var($teams);
		$new =  ':' . implode(':', $teams) . ':';
//	debug_var($new);
//	die(1);
		return update_usermeta($member, 'teams', $new);
	}

}