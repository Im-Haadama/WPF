<?php

require_once( '../tools_wp_login.php' );
$product_categories = get_terms( 'product_cat', array(
	'hide_empty' => false,
) );

foreach ( $product_categories as $cat ) {
	echo $cat->slug . " " . $cat->name;
}

?>

<html>
<header>

</header>
<form method="get" action="<?php bloginfo( 'url' ); ?>">
    <fieldset>
        <input type="text" name="s" value="<?php echo get_search_query(); ?>"
               placeholder="<?php echo esc_attr_x( 'Search Products…', 'placeholder', 'woocommerce' ); ?>"
               maxlength="50" required="required"
               title="<?php echo esc_attr_x( 'Search for:', 'label', 'woocommerce' ); ?>"/>
        <input type="hidden" name="post_type" value="product"/>
        <select name="product_cat">
            <option value=''>All</option>
			<?php

			$product_categories = get_terms( 'product_cat', array(
				'hide_empty' => false,
			) );
			foreach ( $product_categories as $cat ) {
				echo '<option value="' . $cat->slug . '">' . $cat->name . '</option>';
			}
			?>
        </select>
        <input type="submit" value="<?php echo esc_attr_x( 'Search', 'submit button', 'woocommerce' ); ?>"/>
    </fieldset>
</form>
</html>
