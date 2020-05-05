<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 04/02/18
 * Time: 19:41
 */

require_once( "../r-shop_manager.php" );
require_once( "bundles.php" );

print header_text( false, true, false );

// update_bundle_prices();
fix_bundles();

function fix_bundles() {
	$sql    = "SELECT id, prod_id, bundle_prod_id, margin FROM im_bundles ORDER BY 2";
	$result = SqlQuery( $sql );

	while ( $row = mysqli_fetch_row( $result ) ) {
		$id      = $row[0];
		$prod_id = $row[1];
		if ( get_post_status( $prod_id ) == 'draft' ) {
			continue;
		} // Handle publish products.

		$b              = Fresh_Bundle::CreateFromDb( $id );             // load the bundle
		$bundle_prod_id = $b->GetBundleProdId();      // Check the bundle status
		$status         = get_post_status( $bundle_prod_id );
		if ( $status == 'publish' ) {
			continue;
		}           // All ok.

		// Find the connected bundle and publish it
		if ( $status == 'draft' ) {
			$my_post                = array();
			$my_post['ID']          = $bundle_prod_id;
			$my_post['post_status'] = 'published';

			// Update the post into the database
			wp_update_post( $my_post );
		}


		print "<br/>";
		print $bundle_prod_id . " " . $status;
		// if (get_price($b->GetBundleProdId()) != $b->CalculatePrice())
		//	$b->Update();
		// print "<br/>" . get_product_name($prod_id) . " " . get_price($b->GetBundleProdId()) . " " . $b->CalculatePrice();
	}

}

function update_bundle_prices() {
	$sql    = "SELECT id, prod_id, bundle_prod_id, margin FROM im_bundles ORDER BY 2";
	$result = SqlQuery( $sql );

	while ( $row = mysqli_fetch_row( $result ) ) {
		$id      = $row[0];
		$prod_id = $row[1];

		$b = Fresh_Bundle::CreateFromDb( $id );
		if ( get_price( $b->GetBundleProdId() ) != $b->CalculatePrice() ) {
			$b->Update();
		}
		// print "<br/>" . get_product_name($prod_id) . " " . get_price($b->GetBundleProdId()) . " " . $b->CalculatePrice();
	}
}

function delete_duplicate_bundles() {
	$sql    = "SELECT id, prod_id, bundle_prod_id, margin FROM im_bundles ORDER BY 2";
	$result = SqlQuery( $sql );

	$prev_prod_id   = 0;
	$prev_bundle_id = 0;
	$prev_margin    = 0;

	while ( $row = mysqli_fetch_row( $result ) ) {
		$id        = $row[0];
		$prod_id   = $row[1];
		$bundle_id = $row[2];
		$margin    = $row[3];

		if ( $prod_id == $prev_prod_id and $prev_margin == $margin ) {
			print "<br/>" . $prod_id . " has multiple bundles";
			//		print "marking to delete bundle product - " . $prod_id . " ";
			//		wp_set_object_terms( $prod_id, 'delete', 'product_cat', true );
			print "marking to delete bundle - " . $bundle_id;
			wp_set_object_terms( $bundle_id, 'delete', 'product_cat', true );
			print " deleting bundle " . $id;
			SqlQuery( "DELETE FROM im_bundles WHERE id = " . $id );
			// : " . get_product_name($prev_prod_id) . " ";
			//print get_product_name($prod_id) . " " . get_product_name($prev_bundle_id) . " " . get_product_name($bundle_id);
		}
		$prev_prod_id   = $prod_id;
		$prev_bundle_id = $bundle_id;
		$prev_margin    = $margin;
	}
}