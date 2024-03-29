<?php


class Focus_Users_Management {
	function init_hooks($loader)
	{
        //FocusLog(__FUNCTION__);
		$loader->AddAction("focus_create_user", __CLASS__, 'add_user');
	}

	static function add_user()
	{
		// Input
		$email = GetParam("email", true); //XndDuirQ
		$user_name = GetParam("user_name", true);
		$password = GetParam("password", true);

		// Create wordpress users.
		$u = Core_Users::get_user_by_email($email);
		if ($u) {
			print "failed: user with email $email exists!";
			return false;
		}
		$u = Core_Users::create_user($email, $user_name, $password);
        if(!$u) return null;

		return Subscription_Manager::instance()->add_subscription($u, "2 Month", "Focus user", "show_tasks");
	}

	function register() {
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
		$result .= Core_Html::GuiButton( "btn_add", "Create", "focus_create_user('" . WPF_Flavor::getPost("focus_create_user") . "')" );

		return $result;
	}

	function login(){
	    $result = "";
        $args   = array( "class" => "narrow" );
        $result .= Core_Html::gui_table_args( array(
            array( __( "name or email" ) . ": ", Core_Html::GuiInput( "name_or_email", "" ) ),
            array( __( "Your email" ) . ": ", Core_Html::GuiInput( "new_email", "" ) ),
            array(__("Your password") . ": ", Core_Html::GuiInput("password", Core_Fund::RandomPassword()))
        ),
            "login_user", $args
        );
        //$result .= Core_Html::GuiButton( "btn_login", "Login", "focus_create_user('" . Flavor::getPost() . "')" );

        return $result;
    }
}