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
	private $nav_menu_name;
	private $nav_menu;

	/**
	 * @return mixed
	 */
	public function getNavMenuName() {
		return $this->nav_menu_name;
	}

	/**
	 * @var string
	 */
	public function __construct($nav_menu_name) {
		$this->nav_menu_name = $nav_menu_name;
		$this->nav_menu = wp_get_nav_menu_object( $this->nav_menu_name );
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
			print sql_trace();
//			die ("call constructor" . __CLASS__);
		}

		return self::$_instance;
	}

	function AddMain($item)
	{
		$menu_id = self::getMenuId();

		$new_item = wp_update_nav_menu_item( $menu_id, 0, array(
			'menu-item-title'   => __( $item['title'] ),
			'menu-item-classes' => 'home',
			'menu-item-url'     => $item['url'],
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

	/**
	 * @param $menu_id
	 * Create nav menu from items memeber. It's array of the first level with values for 'title', 'url'.
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