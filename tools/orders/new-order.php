<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 12/09/15
 * Time: 21:42
 */

require_once( '../im_tools.php' );
?>

<html dir="rtl">
<header>
    <script>
        function calc_line(elm) {
            elm.parentElement.nextSibling.firstChild.nodeValue = elm.parentElement.previousSibling.firstChild.noveValue *
                elm.nodeValue;
        }
    </script>
</header>

<body>
<p>ממשק הזנה מרשימה. גרסת בדיקה</p>
<table>
    <tr>
        <td></td>
        <td>מבצע</td>
        <td>תמונה</td>
        <td>שם מוצר</td>
        <td>מחיר</td>
        <td>כמות</td>
        <td>סהכ</td>
    </tr>
	<?php
	$args = array(
		'post_type'      => 'product',
		'posts_per_page' => 1000,
		'product_cat'    => 'תוצרת טבעית ואורגנית',
		'orderby'        => 'name',
		'order'          => 'ASC'
	);
	$loop = new WP_Query( $args );
	while ( $loop->have_posts() ) : $loop->the_post();
		global $product; ?>
        <tr>
            <td>
                <a href="<?php echo get_permalink( $loop->post->ID ) ?>"
                   title="<?php echo esc_attr( $loop->post->post_title ? $loop->post->post_title : $loop->post->ID ); ?>">
                </a>

            </td>
            <td>
				<?php woocommerce_show_product_sale_flash( $post, $product ); ?>
            </td>
            <td>
				<?php if ( has_post_thumbnail( $loop->post->ID ) ) {
					echo get_the_post_thumbnail( $loop->post->ID, 'shop_catalog' );
				} else {
					echo '<img src="' . woocommerce_placeholder_img_src() . '" alt="Placeholder" width="300px" height="300px" />';
				} ?>
            </td>
            <td>
				<?php the_title(); ?>
            </td>
            <td>
				<?php echo $product->get_price_html(); ?>
            </td>
            <td><input type="text" onkeypress="calc_line(this)"</td>
            <td>0</td>

            <!--          <?php //woocommerce_template_loop_add_to_cart( $loop->post, $product ); ?> -->
        </tr>
	<?php endwhile; ?>
	<?php wp_reset_query(); ?>
</table>
</body>

</html>