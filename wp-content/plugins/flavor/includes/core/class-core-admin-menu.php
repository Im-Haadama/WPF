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
			add_submenu_page($parent, $page['page_title'], $page['menu_title'], $capability, $page['menu_slug'], $page['function']);
		}
	}
}