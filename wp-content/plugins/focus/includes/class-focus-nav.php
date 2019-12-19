<?php


class Focus_Nav {
	protected static $_instance = null;
	private $nav_menu_name;
	/**
	 * @var string
	 */
	public function __construct( ) {
	}

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	private function set_toplevel_nav( $menu_id, $user_id ) {
		// Set up default menu items
		// Back to fresh. Todo: check if fresh installed.
		wp_update_nav_menu_item( $menu_id, 0, array(
			'menu-item-title'   => __( 'Fresh' ),
			'menu-item-classes' => 'home',
			'menu-item-url'     => home_url( '/fresh' ),
			'menu-item-status'  => 'publish'
		) );

		wp_update_nav_menu_item( $menu_id, 0, array(
			'menu-item-title'  => __( 'Active tasks' ),
			'menu-item-url'    => home_url( '/focus' ),
			'menu-item-status' => 'publish'
		) );

		wp_update_nav_menu_item( $menu_id, 0, array(
			'menu-item-title'  => __( 'Projects' ),
			'menu-item-url'    => home_url( '/focus?operation=show_projects' ),
			'menu-item-status' => 'publish'
		) );

		wp_update_nav_menu_item( $menu_id, 0, array(
			'menu-item-title'  => __( 'Teams' ),
			'menu-item-url'    => home_url( '/focus?operation=show_teams' ),
			'menu-item-status' => 'publish'
		) );

		wp_update_nav_menu_item( $menu_id, 0, array(
			'menu-item-title'  => __( 'Salary' ),
			'menu-item-url'    => home_url( '/salary?operation=show_main' ),
			'menu-item-status' => 'publish'
		) );

			wp_update_nav_menu_item( $menu_id, 0, array(
				'menu-item-title'     => __( "Month report" ),
				'menu-item-classes'   => 'home',
				'menu-item-url'       => home_url( '/salaray?operation=show_salary' ),
				'menu-item-status'    => 'publish',
				'menu-item-parent-id' => $menu_id
			) );

		if (user_can($user_id, "show_bank")){
			wp_update_nav_menu_item( $menu_id, 0, array(
				'menu-item-title'  => __( 'Bank' ),
				'menu-item-url'    => home_url( '/bank?operation=show_main' ),
				'menu-item-status' => 'publish'
			) );
		}

	}

	private function update_nav_projects($menu_id, $parent, $user_id) {
		// Add new ones.
		$projects    = Org_Project::GetProjects( $user_id );
		$menu_items = wp_get_nav_menu_items( $menu_id );
		foreach ($projects as $project_id => $project_name){
			$found = false;
			if ($menu_items){
				foreach ($menu_items as $item) {
					if ($item->post_title == $project_name) {
						$found = true;
						continue;
					}
				}
			}
			if (! $found) {
				wp_update_nav_menu_item( $menu_id, 0, array(
					'menu-item-title'   => __( $project_name ),
					'menu-item-classes' => 'home',
					'menu-item-url'     => home_url( '/focus?operation=show_project&project_id=' .  $project_id),
					'menu-item-status'  => 'publish',
					'menu-item-parent-id' => $parent
				) );
			}
		}

		// Remove redundant or not active
//		foreach ($menu_items as $item) {
//			$found_id = 0;
//			foreach ($projects as $project_id => $project_name) {
//				if ($item->post_title == $project_name) {
//					$found_id = $project_id;
//					continue;
//				}
//			}
//			if ($found_id) unset ($projects[$found_id]); // First time - just remove from list.
//			else wp_delete_post($item->ID); // second time or inactive project
//		}

//		foreach ($add as $project_id){
//			$index = array_search($project_id, $projects);
//			$project_id = $projects[$index]['project_id'];
//			$project_name = $projects[$index]['project_name'];
//			print $index . " adding $project_id " . $project_name . "<br/>";
//
//		}
//
//		$remove = array_diff($menu_projects, $project_ids);
//		if ($remove) die ("need to complete");
//
//		foreach ( $menu_items as $menu_item ) {
//			var_dump($menu_item);
//			die (1);
//
//		}
	}

	private function update_nav_teams($menu_id, $parent, $user_id) {
		// Add new ones.
		$teams   = Org_Worker::GetTeams( $user_id );
		$menu_items = wp_get_nav_menu_items( $menu_id );
		foreach ( $teams as $team_id ) {
			$team_name = Org_Team::team_get_name($team_id);
			$found = false;
			if ( $menu_items ) {
				foreach ( $menu_items as $item ) {
					if ( $item->post_title == $team_name ) {
						$found = true;
						continue;
					}
				}
			}
			if ( ! $found ) {
				wp_update_nav_menu_item( $menu_id, 0, array(
					'menu-item-title'     => __( $team_name ),
					'menu-item-classes'   => 'home',
					'menu-item-url'       => home_url( '/focus?operation=show_team&team_id=' . $team_id ),
					'menu-item-status'    => 'publish',
					'menu-item-parent-id' => $parent
				) );
			}
		}
	}

	private function set_nav_details($menu_nav, $user_id, $reset = false)
	{
		$menu_items = wp_get_nav_menu_items($menu_nav);
		foreach  ($menu_items as $menu_item){
			switch ($menu_item->post_title){
				case "Projects":
					$project_menu_id = $menu_item->ID;
					self::update_nav_projects($menu_nav, $project_menu_id, $user_id);
					break;
				case "Fresh":
					break;
				case "Teams":
					$team_menu_id = $menu_item->ID;
					self::update_nav_teams($menu_nav, $team_menu_id, $user_id);
					break;
				case "Salary":
				default:

			}
		}

//			array_push($menu_options,
//				array("link" => add_to_url(array("operation"=>"show_project", "id" => $project["project_id"])),
//				      "text"=>$project['project_name']));

	}

	function create_nav($name, $user_id)
	{
		$this->nav_menu_name = $name;

		$menu_nav = null;
		$menu_nav_id = 0;

		// Reset menu - remove all and build from scatch.
		$reset_menu = get_param("reset_menu", false, false);
		if ($reset_menu) wp_delete_nav_menu($this->nav_menu_name);
		else {
			$menu_nav = wp_get_nav_menu_object( $this->nav_menu_name);
			$menu_nav_id = $menu_nav->term_id;
		}

		if (! $menu_nav) {  /// Brand new or reset requested.
			$menu_nav_id = wp_create_nav_menu($this->nav_menu_name); // Create
			self::set_toplevel_nav($menu_nav_id, $user_id);
		}
		self::set_nav_details($menu_nav_id, $user_id);
	}

	function get_nav()
	{
		return $this->nav_menu_name;
	}
}