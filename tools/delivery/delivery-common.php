<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 05/08/17
 * Time: 12:38
 */

require_once( "../multi-site/multi-site.php" );

?>
    <script>
        const product_name_id = 0;
        const q_quantity_ordered_id = 1; // Only display
        const q_supply_id = 2;
        const has_vat_id = 3;
        const line_vat_id = 4;
        const p_id = 5;
        const line_total_id = 6;
        const term_id = 7;
        const q_refund_id = 8;
        const refund_total_id = 9;

    </script>
<?php
function print_fresh_category() {
	$list = "";
	if ( MultiSite::LocalSiteID() == 1 ) {
		foreach ( get_term_children( 15, "product_cat" ) as $child_term_id ) {
			$list .= $child_term_id . ", ";
		}
	}
	print rtrim( $list, ", " );
}
