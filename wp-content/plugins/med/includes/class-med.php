<?php


class Med {


	/**
	 * Med constructor.
	 */
	public function __construct() {
		self::init_hooks();
	}

	function init_hooks() {
		add_shortcode( 'med_plants', array($this, 'med_plants') ); // [med_plants]
		add_shortcode( 'med_symptoms', array($this, 'med_symptoms') ); // [med_symptoms]
		AddAction("gem_add_med_cases", array($this, "add_case"));
		add_action( 'init', array( $this, 'init' ), 11 );
		add_action( 'admin_menu',array($this, 'admin_menu') );
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
		return "/wp-content/plugins/med/post.php";
	}

	function add_case() {
		$result = Core_Html::GuiHeader(1, "Medical - add new case");
		$args = [];
		$args["post_file"] = self::getPost();
		$result .= __("Select client") . " " .Core_Users::gui_select_user("new_user", null, $args);

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
		$result .= Core_Gem::GemArray($cases, $args, "med_cases");

		print $result;
	}

// private stuff
//				$post->post_status = 'private';
//				wp_update_post( $post );

}