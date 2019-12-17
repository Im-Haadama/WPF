<?php


class Fresh_Orders {
	private $plugin_name;
	private $version;

	/**
	 * Fresh_Orders constructor.
	 *
	 * @param $plugin_name
	 */
	public function __construct($plugin_name, $version) {
		$this->plugin_name = $plugin_name;
		$this->version = '1.0';
	}

	public function enqueue_scripts() {
//		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'orders/orders.js', array( 'jquery' ), $this->version, false );
//		wp_localize_script( $this->plugin_name, 'WPaAjax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
//		if (! file_exists($file) and get_user_id() == 1) print $file . " not exists <br/>";
		$file = plugin_dir_url( __FILE__ ) . 'core/data/data.js';
		wp_enqueue_script( 'data', $file, null, $this->version, false );

		$file = plugin_dir_url( __FILE__ ) . 'core/gui/client_tools.js';
		wp_enqueue_script( 'client_tools', $file, null, $this->version, false );

	}
}