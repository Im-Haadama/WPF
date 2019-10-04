<?php

function gui_select_supply_status($id, $value, $args)
{
	$args["values"] = array(
		SupplyStatus::NewSupply => "new",
	SupplyStatus::Sent => "sent",
	SupplyStatus::Supplied => "supplied",
	SupplyStatus::Merged => "merged",
	SupplyStatus::Deleted => "delete");

	return GuiSimpleSelect($id, $value, $args);
}
