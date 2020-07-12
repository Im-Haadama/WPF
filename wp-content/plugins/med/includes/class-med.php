<?php


class Med {

	function init_hooks() {
		add_shortcode( 'med_plants', array($this, 'med_plants') ); // [med_plants]
		add_shortcode( 'med_symptoms', array($this, 'med_symptoms') ); // [med_symptoms]
		add_action( 'admin_menu',array($this, 'admin_menu') );

	}

	function admin_menu()
	{
		$menu = new Core_Admin_Menu();

		$menu->AddMenu('Med', 'Med', 'show_med', 'med', array($this, 'main'));
	}

	function med_license()
	{
		return "הטקסט מויקיפדיה מוגש בכפוף ל-" . Core_Html::GuiHyperlink("cc-by-sa", "https://creativecommons.org/licenses/by-sa/3.0/deed.he") .".<br/>";
	}

	function med_plants()
	{
		$result = med_license();
		$terms = get_the_terms(get_the_ID(), 'category');
		if ($terms) {
			$result .= Core_Html::GuiHeader(2, "פעילויות");
			$result .= med_show_activities( $terms );
		}

		return $result;
	}

	function med_symptoms( $atts, $contents, $tag ) {
		$sympton = get_the_ID();

		$result = med_license();

		$result .= Core_Html::GuiHeader(2, "פעילויות רפואיות הקשורות");

		$result .= "צמחים המכילים חומרים פעילים בעלי הפעילויות הרפואיות הנ\"ל:<br/>";
		$terms = get_the_terms(get_the_ID(), 'category');
		if ($terms) {
			$result .= med_show_activities($terms);
			$result .= med_show_herbs($terms, $sympton);
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
		$result .= Core_Html::GuiHeader(1, "צמחים רלוונטיים:");
		$table = new Core_Sparse_Table(array("name" => ""));

		foreach ($activities as $activity_term){
			$table->AddRow($activity_term->term_id, $activity_term->name);
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
//				var_dump($activity_term->name);
				}
			}
		}
		$result .= $table->GetTable();

		return $result;
	}

// private stuff
//				$post->post_status = 'private';
//				wp_update_post( $post );

}