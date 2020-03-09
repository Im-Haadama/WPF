<?php


class Core_Admin_Menu {
	public function AddMenu($page_title, $menu_title, $capability, $menu_slug, $function)
	{
		return add_menu_page($page_title, $menu_title, $capability, $menu_slug, $function);
	}

	public function AddSubMenu($parent, $capability, $pages)
	{
		foreach ($pages as $page)
		{
			$title= $page['page_title'];
			$menu_title = ( isset($page['menu_title']) ? $page['menu_title'] : $title);
			$slug = ( isset($page['menu_slug']) ? $page['menu_slug'] :  str_replace(' ', '-', strtolower($menu_title)));
			add_submenu_page($parent, $title, $menu_title, $capability, $slug, $page['function']);
		}
	}
}