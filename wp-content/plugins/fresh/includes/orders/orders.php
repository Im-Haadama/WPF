<?php

function handle_anonymous_operation($operation, $args)
{
	// Actions
	switch ($operation)
	{
	}

	$args["script_files"] = array("/niver/gui/client_tools.js", "orders.js");
	print HeaderText($args);
	switch ($operation)
	{
		case "show_order_form":
			print show_order_form();
			return;
	}
	// Content
}

function show_order_form()
{
	$result = "";
	$result .=  gui_table_args( array(
			array( "כתובת המייל של המזמין:", GuiInput( "email", "", array( "events" => "onchange=update_email()" ) ) ),
			array( "שם הלקוח:", gui_label( "user_info", "" ) ),
			array( "מועד המשלוח", gui_div( "delivery_info" ) ),
			array( 'סה"כ הזמנה:', gui_label( "total", "0" ) )
		)
	);

	return $result;
}

