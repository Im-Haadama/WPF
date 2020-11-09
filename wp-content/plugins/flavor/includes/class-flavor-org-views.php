<?php


class Flavor_Org_Views {

	static $_instance;

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( __CLASS__ );
		}

		return self::$_instance;
	}


	function init_hooks($loader)
	{
		$loader->AddAction( 'company_add_worker', $this, 'company_add_worker' );
		$loader->AddAction( 'company_remove_worker', $this, 'company_remove_worker' );
		$loader->AddAction( 'gem_add_team_members', $this, 'show_edit_team', 10, 3 );
		$loader->AddAction( 'gem_edit_projects', $this, 'ShowProjectMembers', 11, 3 );
		$loader->AddAction( 'gem_add_project_members', $this, 'AddProjectMember', 11, 3 );
		$loader->AddAction( 'project_add_member', $this, 'ProjectAddMember', 11, 3 );
		$loader->AddAction( 'show_add_company_worker', $this, 'AddCompanyWorker', 11, 3 );
		$loader->AddAction( "team_show_edit", $this, "team_show_edit" );

	}
	static function CompanySettings( Org_Company $company ) {
		$args = []; // self::Args();
		$tabs = [];

		array_push( $tabs, array(
			"teams",
			"Teams",
			self::company_teams( $company, $args )
		) );

		array_push( $tabs, array(
			"workers",
			"Workers",
			self::company_workers( $company, $args )
		) );

		$args["class"]         = "company_tabs";
		$args["tabs_load_all"] = true;
//		var_dump($tabs);
		$t = Core_Html::GuiTabs( "company_settings", $tabs, $args );

//		print $t;
		return $t;
	}

	static function company_teams( Org_Company $company, $args ) {
		$args["company"] = $company->getId();
		$db_prefix       = GetTablePrefix();
		$result          = Core_Html::GuiHeader( 1, "All the teams in the company " . $company->getName() );
		$args["query"]   = "manager = 1";
		$args["links"]     = array( "id" => AddToUrl( array( "operation" => "team_show_edit&id=%s" ) ) );
		$args["selectors"] = array(
			"team_members" => __CLASS__ . "::gui_show_team",
			"senders"      => __CLASS__ . "::gui_show_team"
		);

		$sql   = "select id, team_name from ${db_prefix}working_teams where manager in \n" .
		         "(" . CommaImplode( $company->getWorkers() ) . ")";
		$teams = Core_Data::TableData( $sql, $args );
		if ( $teams ) {
			foreach ( $teams as $key => &$row ) {
				if ( $key == "header" ) {
					$row [] = __( "Team members", 'e-fresh' );
					$row [] = __( "Senders", 'e-fresh' );
				} else {
					// Members
					$team                = new Org_Team( $row["id"] );
					$all                 = $team->AllMembers();
					$row["team_members"] = ( $all ? CommaImplode( $all ) : null );

					// Senders
					$senders        = $team->CanSendTasks();
					$row['senders'] = ( $senders ? CommaImplode( $senders ) : null );
					// " ";				.				                  Core_Html::GuiHyperlink("[Edit]", AddToUrl(array("operation"=>"team_sender_show_edit", "id" => $team->getId())));

				}
			}
		}

//		$args["actions"] = array(array("Add", AddToUrl("operation", "show_edit_team&team_id=%s")));
		$result .= Core_Gem::GemArray( $teams, $args, "working_teams" );

		return $result;
	}

	static function gui_show_team( $id, $selected, $args ) {
		if ( GetArg( $args, "edit", false ) ) {
			$team                 = new Org_Team( GetArg( $args, "team_id", null ) );
			$company              = new Org_Company( $team->getCompany() );
			$args["add_checkbox"] = true;

			return Core_Html::gui_table_args( $company->getWorkers( true ), $selected, $args );
		} else {
			$team_id = substr( $id, strripos( $id, "_" ) + 1 );
			$members = explode( ",", $selected );
			$result  = "";
			foreach ( $members as $member ) {
				$result .= ( new Core_Users( $member ) )->getName() . ", ";
			}

			return rtrim( $result, ", " );
//			. " " . Core_Html::GuiHyperlink( "[Edit]", AddToUrl( array(
//				       "operation" => "team_show_edit",
//				       "id"        => $team_id
//			       ) ) );
		}

//		return rtrim( $result, ", " ) . Core_Html::GuiHyperlink("[Ed")
//		       Core_Html::GuiButton("btn_$id", "Edit", "teams_edit_team($id)");
//		var_dump($company->getWorkers());1
//		die (1);
	}

	static function company_workers( Org_Company $company, $args ) {
		$company_id    = $company->getId();
		$html          = Core_Html::GuiHeader( 1, "All the workers in the company " . $company->getName() );
		$args["query"] = "manager = 1";
		$args["links"] = array( "id" => AddToUrl( array( "operation" => "show_edit_worker&worker_id=%s" ) ) );
//		$args["selectors"] = array( "team_members" => __CLASS__ . "::gui_show_team" );
		//$args["post_file"] .= "operation=company_teams";

		$worker_ids = $company->GetWorkers(); // Should return the admin at least.
		if ( ! $worker_ids ) {
			$html .= "No workers in the company";
		} else {
			$workers = Core_Data::TableData( "select id from wp_users where id in (" . CommaImplode( $worker_ids ) . ")" );
			foreach ( $workers as $id => $row ) {
				$u = new Core_Users( $id );
				if ( $id > 0 ) {
					$workers[ $id ]["display_name"] = $u->getName();
				} else // the header
				{
					$workers[ $id ]["display_name"] = __( "Name" );
				}
			}
			$args["post_file"]    .= "?company=" . $company_id;
			$args["add_button"]   = false;
			$args["add_checkbox"] = true;
			$html                 .= Core_Gem::GemArray( $workers, $args, "company_workers" );
		}
		$post_file = Focus::getPost();
		$html      .= "<div>" . Core_Html::GuiInput( "worker_email", null, $args ) . Core_Html::GuiButton( "btn_add", "Add", "company_add_worker('$post_file', $company_id)" ) . "</div>";
		$html      .= Core_Html::GuiButton( "btn_remove", "Remove", "company_remove('$post_file', $company_id)" );

		$html .= "<div>" . Core_Html::GuiInput( "user_to_add", null, $args ) . Core_Html::GuiButton( "btn_add", "Add", "company_add_worker('$post_file', $company_id)" ) . "</div>";
		//$html .= "<div>" . Core_Users::gui_select_user("user_to_add", null, $args). Core_Html::GuiButton("btn_add", "Add", "company_add_worker('$post_file', $company_id)") . "</div>";

//		$html .= Core_Html::GuiHyperlink("Add new user", AddToUrl(array("operation"=>"show_add_company_worker", "company" => $company_id)));

		return $html;
	}

	function team_show_edit(  ) {
		$args = [];
		$team_id = GetParam("id", true);

		$result = Core_Html::GuiHeader( 1, "Edit team" );

		$team_args = array("fields" => array("id", "team_name", "manager"),
			"selectors" => array( "manager" => "Focus_Views::gui_select_worker" ));

		$team_info_box = Core_Html::GuiDiv("team_info_box",
			Core_Gem::GemElement( "working_teams", $team_id, $team_args ));

		// Remove box
		$table           = array();
		$table["header"] = array( "select", "name" );
		$team            = new Org_Team( $team_id );
		foreach ( $team->AllMembers() as $member ) {
			$table[ $member ]["name"] = GetUserName( $member );
			$table[$member] ["id"] = $member;
		}
		$args["add_checkbox"]   = true;
		$args["edit"]           = true;
		$args["checkbox_class"] = "workers";

		$member_remove = Core_Html::GuiDiv("members_remove",  	// $args["post_file"] = GetUrl( 1 ) . "?team_id=" . $team_id;
			Core_Html::GuiHeader(2, "Member remove") .
			Core_Html::gui_table_args( $table, "team_members", $args ) .
			Core_Html::GuiButton( "btn_delete", "Delete", array( "action" => "team_remove_member('" . Focus::getPost() . "', $team_id)" )),
			array("style" => 'width:400px; float: right'));


//		$result          .= Core_Html::GuiHeader( 2, "Team members" );

//		$result .= Core_Html::GuiDiv("team",
//			Core_Html::GuiDiv("members",
//			) .
//
//			Core_Html::GuiDiv("members", Core_Html::GuiHeader( 1, "Add member" )));

         $member_add = Core_Html::GuiDiv("add_member", Core_Html::GuiHeader(2, "add") .
                 Focus_Views::gui_select_worker( "new_member", null, $args ) .
		    Core_Html::GuiButton( "btn_add_member", "add", array( "action" => "team_add_member('" . Focus::getPost() . "', $team_id )" )));

         $result .= Core_Html::GuiDiv("team_add_and_remove",  $team_info_box . $member_remove . $member_add);
		// Who can send work to this team
//		$result .= Core_Html::GuiDiv( "can_send",
//			Core_Html::GuiHeader( 1, "Who can send tasks?" ) .
//			Core_Html::gui_table_args( $team->CanSendTasks() ) );

		return $result;
	}

}