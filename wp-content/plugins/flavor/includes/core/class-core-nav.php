<?php
/**
 * Class Core_Nav
 * Created 25/12/2019
 */
class Core_Nav {
	/**
	 * @var null
	 */
	protected static $_instance = null;
	/**
	 * @var
	 */
	private $nav_menu_name; // String
	private $nav_menu; // Object

	/**
	 * @return mixed
	 */
	public function getNavMenuName() {
		return $this->nav_menu_name;
	}

	/**
	 * @var string
	 */
	public function __construct($nav_menu_name = null) {
		$this->nav_menu_name = $nav_menu_name;
		$this->nav_menu = wp_get_nav_menu_object( $this->nav_menu_name );
		if (! $this->nav_menu) {
			$menu_id = wp_create_nav_menu($this->nav_menu_name);
			if (! $menu_id) die("can't create menu " . $this->nav_menu_name);

			$this->nav_menu = wp_get_nav_menu_object( $this->nav_menu_name );
		}
	}

	public function getMenuId()
	{
		if ($this->nav_menu) return $this->nav_menu->term_id;
		die ("no menu");
	}

	/**
	 * @return Core_Nav|null
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			print debug_trace();
//			die ("call constructor" . __CLASS__);
		}

		return self::$_instance;
	}

	function AddMain($item)
	{
		$menu_id = self::getMenuId();
		$title = $item['title'];
		$url = $item['url'];

		$exists = wp_get_nav_menu_items($menu_id);

		foreach ($exists as $key => $n)
			if ($title == $exists[$key]->post_title){
//				print "found $title at $key<br/>";
				return $exists[$key]->ID;
			}
		$new_item = wp_update_nav_menu_item( $menu_id, 0, array(
			'menu-item-title'   => __( $title ),
			'menu-item-classes' => 'home',
			'menu-item-url'     => $url,
			'menu-item-status'  => 'publish'
		) );
//		if (isset($items['childs'])) foreach ($item['childs'] as $menu_child)
//			wp_update_nav_menu_item( $menu_id, 0, array(
//				'menu-item-title'   => __( $item['title'] ),
//				'menu-item-classes' => 'home',
//				'menu-item-url'     => $item['url'],
//				'menu-item-status'  => 'publish',
//				'menu-item-parent-id' => $new_item
//			) );
		return $new_item;
	}

	public function AddSub($parent, $item)
	{
		// Make sure the parent is there or create it.
		$parent_id = self::AddMain($parent);
		return $this->doAddSub($parent_id, $item);
	}

	protected function doAddSub($parent_id, $item)
	{
		$menu_id = self::getMenuId();

		return wp_update_nav_menu_item( $menu_id, 0, array(
			'menu-item-title'   => __( $item['title'] ),
			'menu-item-classes' => 'home',
			'menu-item-url'     => $item['url'],
			'menu-item-status'  => 'publish',
			'menu-item-parent-id' => $parent_id
		) );
	}

	/**
	 * @param $menu_id
	 * Create nav menu from items member. It's array of the first level with values for 'title', 'url'.
	 * Optional childs is array for the second level with 'title', 'url' for the childs.
	 * Called by reset
	 */
	private function create_nav( $menu_id )
	{
		foreach ($this->items as $item) {
			self::AddMain($item);
		}
	}

	/**
	 * @param $name
	 * @param $user_id
	 * @param bool $reset_menu
	 *
	 * @return string
	 */
	function ResetNav( $name, $user_id, $reset_menu = false ) {
		$result              = "";
		$this->nav_menu_name = $name;

		$menu_nav = null;
		if ( $reset_menu ) { // Reset menu - remove all and build from scratch.
			$result .= "deleting " . $this->nav_menu_name;
			$rc     = wp_delete_nav_menu( $this->nav_menu_name );
			if ( $rc === false ) {
				$result .= " delete failed";

				return $result;
			}
			if ( $rc !== true ) {
				$result .= $rc->get_error_messages();

				return $result;
			}
		} else {
			$menu_nav = wp_get_nav_menu_object( $this->nav_menu_name );
		}

		if ( ! $menu_nav ) {  /// Brand new or reset requested.
			$menu_nav_id = wp_create_nav_menu( $this->nav_menu_name ); // Create
			self::set_toplevel_nav( $menu_nav_id, $user_id );
		} else {
			$menu_nav_id = $menu_nav->term_id;
		}
		self::set_nav_details( $menu_nav_id, $user_id );

		print $result;
	}

	/**
	 * @return mixed
	 */
	function get_nav() {
		return $this->nav_menu_name;
	}
}