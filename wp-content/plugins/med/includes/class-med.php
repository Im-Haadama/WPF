<?php


class Med {

	private $version;
	/**
	 * Med constructor.
	 */
	public function __construct() {
		$this->define_constants();
		self::init_hooks();
		$this->version = '1.0';
	}

	function init_hooks() {
		add_shortcode( 'med_plants', array($this, 'med_plants') ); // [med_plants]
		add_shortcode( 'med_symptoms', array($this, 'med_symptoms') ); // [med_symptoms]
		AddAction("gem_add_med_cases", array($this, "add_case"));
		add_action( 'init', array( $this, 'init' ), 11 );
		add_action( 'admin_menu',array($this, 'admin_menu') );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
		add_action('init', __CLASS__ . "::register", 0);

		self::Add('med_create_case');
		self::Add('med_show_case');
	}

	private function define_constants() {
		$upload_dir = wp_upload_dir( null, false );

		define_const( 'MED_ABSPATH', dirname( MED_PLUGIN_FILE ) . '/' );
		define_const( 'MED_PLUGIN_BASENAME', plugin_basename( MED_PLUGIN_FILE ) );
		define_const( 'MED_VERSION', $this->version );
		define_const( 'MED_INCLUDES', MED_ABSPATH . 'includes/' );
		define_const( 'MED_INCLUDES_URL', plugins_url() . '/med/includes/' ); // For js
		define_const( 'MED_INCLUDES_URL', plugins_url() . '/med/includes/' ); // For js
		define_const( 'FLAVOR_INCLUDES_ABSPATH', plugin_dir_path( __FILE__ ) . '../../flavor/includes/' );  // for php
		define_const( 'MED_DELIMITER', '|' );
		define_const( 'MED_LOG_DIR', $upload_dir['basedir'] . '/MED-logs/' );
	}

	function Add($tag)
	{
		AddAction($tag, array($this, $tag));
	}

	function med_show_case()
	{
		$id = GetParam("id");

		$user = new Core_Users($id);

		$result = Core_Html::GuiHeader(1, $user->getName());

		$symtoms = get_usermeta($id, "med_case_symtoms");
		if ($symtoms) {
			$result .= Core_Html::GuiHeader(2, "Symptoms");
			foreach (unserialize($symtoms) as $symtom)
				$result .= Core_Html::GuiHeader(2, $symtom);
		} else {
			$result .= "No symptoms yet. Add below<br/>";
		}
		$result .= self::GuiSelectSym("med_sym");

		$result .= Core_Html::GuiButton("btn_add_symtom", "Add", "");

		print $result;
	}

	public function admin_scripts() {
		$file = MED_INCLUDES_URL . 'med.js';
		wp_enqueue_script( 'med', $file, null, $this->version, false );
	}

	function GuiSelectSym()
	{
		$terms = get_terms('product_cat', array(
			'hide_empty' => false
		));
		foreach ($terms as $term_id => $term) {
			print $term->name . "<br/>";
		}
//		var_dump($q);
	}

	function med_create_case()
	{
		$user = GetParam("client", true);

		update_user_meta($user, "med_case", 1);
		return self::get_link("med_case", $user);
	}

	function init()
	{
		self::addRoles();
	}

	function addRoles() {
		Flavor_Roles::instance()->addRole( "med_guide", array("med_show") );
	}

	static function getPost()
	{
		return Flavor::getPost();
	}

	function add_case() {
		$post_file = Med::getPost();
		$result = Core_Html::GuiHeader(1, "Medical - add new case");
		$args = [];
		$args["post_file"] = self::getPost();
		$result .= __("Select client") . " " .Core_Users::gui_select_user("new_user", null, $args) . "<br/>";
		$result .= Core_Html::GuiButton("btn_save", "Save", "med_new_case('$post_file')");

		print $result;
	}
	function admin_menu()
	{
		$menu = new Core_Admin_Menu();

		$menu->AddMenu('Med', 'Med', 'med_show', 'med', array($this, 'med_main'));
	}

	function med_license()
	{
		return "הטקסט מויקיפדיה מוגש בכפוף ל-" . Core_Html::GuiHyperlink("cc-by-sa", "https://creativecommons.org/licenses/by-sa/3.0/deed.he") .".<br/>";
	}

	function med_plants()
	{
		$result = self::med_license();
		$terms = get_the_terms(get_the_ID(), 'category');
		if ($terms) {
			$result .= Core_Html::GuiHeader(2, "פעילויות");
			$result .= self::med_show_activities( $terms );
		}

		return $result;
	}

	function med_symptoms( $atts, $contents, $tag ) {
		$sympton = get_the_ID();

		$result = self::med_license();


		$terms = get_the_terms(get_the_ID(), 'category');
		if ($terms) {
			$result .= Core_Html::GuiHeader(2, "פעילויות רפואיות הקשורות");
			$result .= self::med_show_activities($terms);
			$result .= self::med_show_herbs($terms, $sympton);
		}

		return $result;
	}

	function med_show_activities($terms)
	{
		$result = "";
		foreach ($terms as $term){
			$result .= Core_Html::GuiHyperlink($term->name,get_category_link($term));
			if ($desc = category_description($term))
				$result .= ": " . strip_tags($desc);
			$result .= "<br/>";
		}
		return $result;
	}

	function med_show_herbs($activities, $sympton)
	{
		$result = "";
		$result .= Core_Html::GuiHeader(1, "צמחים רלוונטיים");
		$result .= "צמחים המכילים חומרים פעילים בעלי הפעילויות הרפואיות הנ\"ל:<br/>";
		$table = new Core_Sparse_Table(array("name" => ""));

		foreach ($activities as $activity_term){
			$table->AddRow($activity_term->term_id, $activity_term->name, '/category/%s');
			$activity = $activity_term->name;
			$query_args = array(
				'post_type'      => 'post',
				'cat' => $activity_term->term_id,
				'orderby'        => 'name',
				'order'          => 'ASC'
			);

			$loop = new WP_Query( $query_args );
			while ( $loop->have_posts() ) {
				$loop->the_post();
				$maybe_plant = get_post();
				if ($maybe_plant->ID != $sympton)  {
					$table->AddColumn($maybe_plant->ID, $maybe_plant->post_title, "/?p=" . $maybe_plant->ID);
					$table->AddItem($activity_term->term_id, $maybe_plant->ID, "&#10004;", $activity_term->name);
				}
			}
		}
		$result .= $table->GetTable();

		return $result;
	}

	function med_main()
	{
		$result = Core_Html::GuiHeader(1, "Medical");

		if ($operation = GetParam("operation", false, null)) {
			print apply_filters( $operation, "" );
			return;
		}

		$cases = array("");
		$args = [];
		$sql = "select user_id as id, u.display_name
       from wp_usermeta join wp_users u 
where meta_key = 'med_case'
and u.ID = user_id";

//		var_dump(Core_Data::TableData( $sql, $args));
		$args["links"] = array("id" => self::get_link("med_case", '%d'));

		print $result . Core_Gem::GemArray(Core_Data::TableData( $sql, $args), $args, "cases");
	}

	function get_link($type, $id)
	{
		switch ($type)
		{
			case "med_case":
				return "/wp-admin/admin.php?page=med&operation=med_show_case&id=$id";
		}
		die ("unknown type");
	}

	static function register()
	{
		// Add new taxonomy, make it hierarchical like categories
		// first do the translations part for GUI
		$labels = array(
			'name' => _x( 'Symptoms', 'symptom_taxonomy' ),
			'singular_name' => _x( 'Symptom', 'symptom singular name' ),
			'search_items' =>  __( 'Search symptoms' ),
			'all_items' => __( 'All Symptoms' ),
			'parent_item' => __( 'Parent Symptom' ),
			'parent_item_colon' => __( 'Parent Symptom:' ),
			'edit_item' => __( 'Edit Symptom' ),
			'update_item' => __( 'Update Symptom' ),
			'add_new_item' => __( 'Add New Symptom' ),
			'new_item_name' => __( 'New Symptom Name' ),
			'menu_name' => __( 'Symptoms' ),
		);

		register_taxonomy('symptom_taxonomy',array('post'), array(
			'hierarchical' => true,
			'labels' => $labels,
			'show_ui' => true,
			'show_admin_column' => true,
			'query_var' => true,
			'rewrite' => array( 'slug' => 'symptom' ),
		));
	}

	/**
	 * Add custom taxonomies
	 *
	 * Additional custom taxonomies can be defined here
	 * http://codex.wordpress.org/Function_Reference/register_taxonomy
	 */
	function add_custom_taxonomies() {
		// Add new "Locations" taxonomy to Posts
		register_taxonomy('symptoms', 'post', array(
		// Hierarchical taxonomy (like categories)
		'hierarchical' => true,
		// This array of options controls the labels displayed in the WordPress Admin UI
		'labels' => array(
			'name' => _x( 'Locations', 'taxonomy general name' ),
			'singular_name' => _x( 'Location', 'taxonomy singular name' ),
			'search_items' =>  __( 'Search Locations' ),
			'all_items' => __( 'All Locations' ),
			'parent_item' => __( 'Parent Location' ),
			'parent_item_colon' => __( 'Parent Location:' ),
			'edit_item' => __( 'Edit Location' ),
			'update_item' => __( 'Update Location' ),
			'add_new_item' => __( 'Add New Location' ),
			'new_item_name' => __( 'New Location Name' ),
			'menu_name' => __( 'Locations' ),
		),
		// Control the slugs used for this taxonomy
		'rewrite' => array(
				'slug' => 'locations', // This controls the base slug that will display before each term
				'with_front' => false, // Don't display the category base before "/locations/"
				'hierarchical' => true // This will allow URL's like "/locations/boston/cambridge/"
			),
	  ));
}

}