<?php


class Core_Settings {
	static public function show_settings()
	{
		print __CLASS__ . ':' . __FUNCTION__;
		$result = "";
		$classes = array("Focus", "Fresh");
		foreach ($classes as $class){
			print "check for $class<br/>";
			if (class_exists($class)) {
				$operation = get_param("operation", false, "show_settings");

				$result .= $class::instance()->handle_operation($operation);
			}
		}
		print $result;
	}
}