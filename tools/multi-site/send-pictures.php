<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 26/02/17
 * Time: 23:02
 * Sends pictures from site with multisite mapping to secondary site.
 * As June 2017 from Im haadama to Organic Store
 */
require_once( "../tools_wp_login.php" );
require_once( "multi-site.php" );

//
//select m.remote_prod_id, pm.meta_value
//  from im_multisite_map m
//    join wp_postmeta pm
//    join wp_posts p
//
//where remote_site_id = 2
//and meta_key  = '_wp_attached_file'
//and p.post_parent = m.local_prod_id
//and pm.post_id = p.ID

//$sql = "select m.remote_prod_id as id, p.post_title as title " .
//    " from im_multisite_map m " .
//    " join wp_postmeta pm " .
////    " join wp_posts p " .
//    " where remote_site_id = 2 ".
//    " and meta_key  = '_wp_attached_file' " .
//    " and p.id = m.local_prod_id " .
//    " and pm.post_id = m.local_prod_id + 1";

$sql = "select m.remote_prod_id as rid, p.post_title as title, m.local_prod_id as id " .
       " from im_multisite_map m " .
       " join wp_posts p " .
       " where  p.id = m.local_prod_id ";

$result = mysqli_query( $conn, $sql );

$uploads    = wp_upload_dir();
$req_prefix = "multi-site/recv-pictures.php?prefix=" . urlencode( $uploads['baseurl'] ) .
              "&imgs=";
$req        = $req_prefix;
print "שולח..." . "<br/>";
while ( $r = mysqli_fetch_assoc( $result ) ) {
	$id = $r["id"];
	print  $id . " " . $r["title"] . " ";
	// print strlen($req) . "<br/>";
	$tid = get_post_thumbnail_id( $id );
	print "tid=" . $tid . " ";

	$img_a = wp_get_attachment_image_src( $tid );
	$img   = $img_a[0];
	print $img . "<br/>";
	if ( strlen( $img ) > 1 ) {
		$req .= $r["rid"] . "," . urlencode( $img ) . ",";

		// print strlen($req) . "<br/>";
	}
	if ( strlen( $req ) >= 1500 ) {
		$req = rtrim( $req, ',' );
		// print $req . "<br/>";
		print MultiSite::Execute( $req, 2 ) . "<br/>";
		$req = $req_prefix;
	}
}
if ( strlen( $req ) > strlen( $req_prefix ) ) {
	$req = rtrim( $req, ',' );
	// print $req . "<br/>";
	print MultiSite::Execute( $req, 2 ) . "<br/>";
}
