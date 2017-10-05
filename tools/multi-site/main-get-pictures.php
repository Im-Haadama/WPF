<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 10/06/17
 * Time: 20:53
 * To run in main site - site with multisite mapping
 * Calculate pictures to receive and send "send command" to remote
 */

require_once( "../tools_wp_login.php" );
require_once( "multi-site.php" );
print header_text();
$sql = "select m.remote_prod_id as rid, p.post_title as title, m.local_prod_id as id " .
       " from im_multisite_map m " .
       " join wp_posts p " .
       " where  p.id = m.local_prod_id ";

$result = mysqli_query( $conn, $sql );

$req = "multi-site/secondary-send-pictures.php?ids=";

while ( $r = mysqli_fetch_assoc( $result ) ) {
	$id = $r["id"];
	// print  $id . " " . $r["title"] . " ";
	// print strlen($req) . "<br/>";
	$tid = get_post_thumbnail_id( $id );
	if ( strlen( $tid ) ) {
		continue;
	} // We have pictures already
	print $r["title"] . "<br/>";
	$req .= $id . "," . $r["rid"] . ",";
}
$req = rtrim( $req, "," );
print $req . "<br/>";

$info = MultiSite::Execute( $req, 2 );

foreach ( preg_split( "/<br\/>/", $info ) as $line ) {
	if ( strlen( $line ) > 2 ) {
		$data = preg_split( "/,/", $line );
		// $img_file = preg_split(",", $line);
		$id   = $data[0];
		$path = $data[1];
		print "id = " . $id . " img_file = " . $path . "<br/>";

		if ( update_post_meta( $id, 'fifu_image_url', $path ) ) {
			$count_changed ++;
		}
	}
}

print "התקבלו " . $count . " תמונות " . $count_changed . " חדשות " . "<br/>";

