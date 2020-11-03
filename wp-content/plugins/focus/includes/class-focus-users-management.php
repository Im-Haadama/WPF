<?php


class Focus_Users_Management {
	function init_hooks()
	{
		AddAction("focus_create_user", array($this, 'add_user'));
	}
	function add_user()
	{
		// Input
		$email = GetParam("email", true);
		$user_name = GetParam("user_name", true);
		$password = GetParam("password", true);

		// Create wordpress users.
		$u = Core_Users::get_user_by_email($email);
		if ($u) {
			print "user with email $email exists<br/>";
			return false;
		}
		$u = Core_Users::create_user($email, $user_name, $password);

		return Subscription_Manager::instance()->add_subscription($u, "2 Month", "Focus user", "show_tasks");
	}

	function showNewUser() {
		$result = "";
		// narrow class is not defined. Todo: https://e-fresh.co.il/task?id=6088
		$args   = array( "class" => "narrow" );
		$result .= Core_Html::gui_table_args( array(
			array( __( "Your login name" ) . ": ", Core_Html::GuiInput( "new_user_name", "" ) ),
			array( __( "Your email" ) . ": ", Core_Html::GuiInput( "new_email", "" ) ),
			array(__("Your password") . ": ", Core_Html::GuiInput("password", Core_Fund::RandomPassword()))
		),
			"new_user", $args
		);
		$result .= Core_Html::GuiButton( "btn_add", "Create", "focus_create_user('" . Flavor::getPost() . "')" );

		return $result;
	}
}