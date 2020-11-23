<?php

class Core_Admin_Menu {

	public $top; // Array of (id, title, href);
	protected static $_instance = null;

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( "Flavor" );
		}

		return self::$_instance;
	}

	private function __construct(  ) {
		$this->top = array();
	}

	public function AddMenu($page_title, $menu_title, $capability, $menu_slug, $function)
	{
		return add_menu_page($page_title, $menu_title, $capability, $menu_slug, $function);
	}

	public function AddTop($id, $title, $href, $parent = null)
	{
		array_push($this->top, array($id, $title, $href, $parent));
	}

	function do_modify_admin_bar($wp_admin_bar)
	{
		foreach ($this->top as $item) {
			$id = $item[0];
			$title = $item[1];
			$href = $item[2];
			$parent = ((isset($item[3]) and $item[3]) ? $item[3] : null);

			$new_item =  [
				'id'    => $id,
				'title' => __( $title ),
				'href'  => $href,
			];
			if ($parent) $new_item['parent'] = $parent;
			$wp_admin_bar->add_node($new_item);
		}
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
//		else MyLog("==========================================> $capability is missing " . get_user_id());
	}
}
