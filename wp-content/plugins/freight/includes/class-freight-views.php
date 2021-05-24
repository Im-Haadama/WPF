<?php
/*
 * Copyright (c) 2021. Lorem ipsum dolor sit amet, consectetur adipiscing elit.
 * Morbi non lorem porttitor neque feugiat blandit. Ut vitae ipsum eget quam lacinia accumsan.
 * Etiam sed turpis ac ipsum condimentum fringilla. Maecenas magna.
 * Proin dapibus sapien vel ante. Aliquam erat volutpat. Pellentesque sagittis ligula eget metus.
 * Vestibulum commodo. Ut rhoncus gravida arcu.
 */

class Freight_Views {

	function init_hooks($loader)
	{
		$loader->AddFilter("show_missions", $this, "show_imports");
	}

	function show_imports($result)
	{
		$result .= Core_Html::GuiHeader(1, __("Last imports"));
		$last_imports = SqlQueryArray("select id, info_key from im_info where info_key like 'import_result%' order by 1 desc limit 5");

		foreach ($last_imports as $import)
		{
			$id = $import[0];
			$time = substr($import[1], 14);
			$result .= Core_Html::GuiHyperlink(date('Y-m-d h:i', $time), AddToUrl(array("operation" => "show_import", "id"=>$id))) . "<br/>";
		}

		return $result;
	}
}
