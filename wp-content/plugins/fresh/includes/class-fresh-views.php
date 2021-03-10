<?php

class Fresh_Views {

	static function init_hooks($loader)
	{
//		add_action( 'product_cat_add_form_fields', array(__CLASS__, 'add_category_data') );
		add_action( 'product_cat_edit_form_fields', array(__CLASS__, 'edit_category_data'), 11);
		$loader->AddAction("needed_products_print", Fresh_Packing::instance(), 'needed_products_print');

	}

	static function edit_category_data()
	{
//		$view = "";
//		$view .= Core_Html::GuiLabel("");
//		print Core_Html::GuiDiv("category_view", $view);
		?>
		<div class="form-field">
			<label for="seconddesc"><?php echo __( 'Second Description', 'woocommerce' ); ?></label>

			<?php
			$settings = array(
				'textarea_name' => 'seconddesc',
				'quicktags' => array( 'buttons' => 'em,strong,link' ),
				'tinymce' => array(
					'theme_advanced_buttons1' => 'bold,italic,strikethrough,separator,bullist,numlist,separator,blockquote,separator,justifyleft,justifycenter,justifyright,separator,link,unlink,separator,undo,redo,separator',
					'theme_advanced_buttons2' => '',
				),
				'editor_css' => '<style>#wp-excerpt-editor-container .wp-editor-area{height:175px; width:100%;}</style>',
			);

			wp_editor( '', 'seconddesc', $settings );
			?>

			<p class="description"><?php echo __( 'This is the description that goes BELOW products on the category page', 'woocommerce' ); ?></p>
		</div>
		<?php
	}
}