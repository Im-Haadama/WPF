<?php

if (! function_exists('gui_select_task')){
	function gui_select_task( $id, $value, $args )
	{
		if ($value > 0) {
			$t = new Focus_Tasklist($value);
			$selected = $value . ")" . $t->getTaskDescription();
		} else
			$selected = $value;

		$args["selected"] = $selected;
		$args["name"] = "task_description";
		$args["query"] =  GetArg($args, "query", " status = 0 ");
	//	              "include_id" => 1,
	//	              "datalist" =>1,
		$args["multiple_inline"] = 1;

		return Core_Html::GuiAutoList($id, "tasks", $args);
	}
}