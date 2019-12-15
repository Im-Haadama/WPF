<?php

function gui_select_supply_status($id, $value, $args = null)
{
	$args["values"] = array(
		SupplyStatus::NewSupply => "new",
	SupplyStatus::Sent => "sent",
	SupplyStatus::OnTheGo => "on the go",
	SupplyStatus::Supplied => "supplied",
	SupplyStatus::Merged => "merged",
	SupplyStatus::Deleted => "delete");

	if (is_null($id)) return $args["values"][$value];

	return GuiSimpleSelect($id, $value, $args);
}
