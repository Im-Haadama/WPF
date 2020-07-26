<?php

class Core_Admin_Menu {
	public function AddMenu($page_title, $menu_title, $capability, $menu_slug, $function)
	{
		return add_menu_page($page_title, $menu_title, $capability, $menu_slug, $function);
	}

	public function AddSubMenu($parent, $capability, $page)
	{
		$title = (isset($page['page_title']) ? $page['page_title'] : 'not set');
		$menu_title = ( isset($page['menu_title']) ? $page['menu_title'] : $title);
		$slug = ( isset($page['menu_slug']) ? $page['menu_slug'] :  str_replace(' ', '-', strtolower($menu_title)));
		$function  = (isset($page['function']) ? $page['function'] : null);
		if (im_user_can($capability)) {
//				print "================adding $parent $title $menu_title $capability $slug" . $function[1] . "<br/>";
			if ( ! add_submenu_page( $parent, $title, $menu_title, $capability, $slug, $function ) ) {
				print "cant add $title $parent $capability<br/>";
			}
		}
//		else MyLog("==========================================> $capability is missing " . get_user_id());
	}

	public function Add($parent, $capability, $slug, callable $callable)
	{
		$title = convert_to_title($slug);
		$menu_title = $title;
		if (im_user_can($capability)) {
			if ( ! add_submenu_page( $parent, $title, $menu_title, $capability, $slug, $callable ) ) {
				print "cant add $title $parent $capability<br/>";
			}
		}
		else MyLog("==========================================> $capability is missing " . get_user_id());
	}
}
