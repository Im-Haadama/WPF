<?php

/**
 * Register all actions and filters for the plugin
 *
 * @link       https://www.deviodigital.com
 * @since      1.0.0
 *
 * @package    Focus
 * @subpackage Focus/loader
 */

/**
 * Register all actions and filters for the plugin.
 *
 * Maintain a list of all hooks that are registered throughout
 * the plugin, and register them with the WordPress API. Call the
 * run function to execute the list of actions and filters.
 *
 * @package    Focus
 * @subpackage Focus/includes
 * @author     Devio Digital <deviodigital@gmail.com>
 */
class Core_Hook_Handler {

	private $debug = false;

	static private $_instance;

	/**
	 * The array of actions registered with WordPress.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      array    $actions    The actions registered with WordPress to fire when the plugin loads.
	 */
	protected $actions;

	/**
	 * The array of filters registered with WordPress.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      array    $filters    The filters registered with WordPress to fire when the plugin loads.
	 */
	protected $filters;

	/**
	 * Initialize the collections used to maintain the actions and filters.
	 *
	 * @since    1.0.0
	 */
	private function __construct()
	{
		$this->actions = array();
		$this->filters = array();
	}

	public static function instance() : Core_Hook_Handler {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( );
		}

		return self::$_instance;
	}

	/**
	 * Add a new action to the collection to be registered with WordPress.
	 *
	 * @since    1.0.0
	 * @param    string               $hook             The name of the WordPress action that is being registered.
	 * @param    object               $component        A reference to the instance of the object on which the action is defined.
	 * @param    string               $callback         The name of the function definition on the $component.
	 * @param    int                  $priority         Optional. The priority at which the function should be fired. Default is 10.
	 * @param    int                  $accepted_args    Optional. The number of arguments that should be passed to the $callback. Default is 1.
	 */
	public function AddAction( $hook, $component, $callback = null, $priority = 10, $accepted_args = 1 ) {
		$debug = ($hook=='gem_v_show');
		
//		if ($debug) dd($callback);
		if (!$callback) {
			if ($debug) print "using $hook<br/>";
			$callback = $hook;
		}
		if (is_callable(array($component, $callback . "_wrap"))) {
			if ($debug) print "using ${callback}_wrap<br/>";
			$callback .= "_wrap";
		}
//		if ($debug) var_dump($component);
		if ($this->debug) print "=-======================================" . __FUNCTION__ . " h=$hook c=$callback" . "<br/>";
		if ($this->debug and ! is_callable(array($component, $callback))) die ("Not callable");
		$this->add_action( $hook, $component, $callback, $priority, $accepted_args );
	}

	/**
	 * Add a new filter to the collection to be registered with WordPress.
	 *
	 * @since    1.0.0
	 * @param    string               $hook             The name of the WordPress filter that is being registered.
	 * @param    object               $component        A reference to the instance of the object on which the filter is defined.
	 * @param    string               $callback         The name of the function definition on the $component.
	 * @param    int                  $priority         Optional. The priority at which the function should be fired. Default is 10.
	 * @param    int                  $accepted_args    Optional. The number of arguments that should be passed to the $callback. Default is 1
	 */
	public function AddFilter( $hook, $component, $callback = null, $priority = 10, $accepted_args = 1 ) {
		if (!$callback) $callback = $hook;
		$this->add_filter( $hook, $component, $callback, $priority, $accepted_args );
	}

	/**
	 * A utility function that is used to register the actions and hooks into a single
	 * collection.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @param    string               $hook             The name of the WordPress filter that is being registered.
	 * @param    object               $component        A reference to the instance of the object on which the filter is defined.
	 * @param    string               $callback         The name of the function definition on the $component.
	 * @param    int                  $priority         The priority at which the function should be fired.
	 * @param    int                  $accepted_args    The number of arguments that should be passed to the $callback.
	 * @return   array                                  The collection of actions and filters registered with WordPress.
	 */

	private function add_action( $hook, $component, $callback, $priority, $accepted_args ) 
	{
		if (! isset($this->actions[$hook])) $this->actions[$hook] = array();
		array_push($this->actions[$hook], array(
			'hook'          => $hook,
			'component'     => $component,
			'callback'      => $callback,
			'priority'      => $priority,
			'accepted_args' => $accepted_args
		));
	}

	private function add_filter( $hook, $component, $callback, $priority, $accepted_args )
	{
		if (! isset($this->filters[$hook])) $this->filters[$hook] = array();
		array_push($this->filters[$hook], array(
			'hook'          => $hook,
			'component'     => $component,
			'callback'      => $callback,
			'priority'      => $priority,
			'accepted_args' => $accepted_args
		));
	}

	/**
	 * Register the filters and actions with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run()
	{
//		if (get_user_id() == 1) print '============================== RUN<br/>';

		$this->debug = false; // (get_user_id() == 1);
//		if ($this->debug) print 1/0;
		foreach ( $this->filters as $hooks) {
//			if ($this->debug) print ("Adding " . $hook['hook']) . "<br/>";
			foreach ($hooks as $hook)
				add_filter( $hook['hook'], array( $hook['component'], $hook['callback'] ), $hook['priority'], $hook['accepted_args'] );
		}

		foreach ( $this->actions as $hooks ) {
//			if ($this->debug and ($hook['hook'] == 'gem_v_show')) print ("===================== Adding " . $hook['hook'] . " " .$hook['callback'])  . "<br/>";
 			// print "adding " . $hook['hook'] . " " . var_dump($hook['component']) . " " . $hook['callback'] . "<br/>";
			foreach ($hooks as $hook)
				add_action($hook['hook'], array( $hook['component'], $hook['callback'] ), $hook['priority'], $hook['accepted_args'] );
		}
	}

	public function DoAction($action, $params)
	{
		if ($this->debug) FinanceLog($action);
		if (! isset($this->actions[$action]))
			print "Failed: no handler for $action";
		else {
//			var_dump($action);
//			var_dump($params);
			do_action( $action, $params );
		}
	}

	public function DebugOn()
	{
		$this->debug = true;
	}
}
