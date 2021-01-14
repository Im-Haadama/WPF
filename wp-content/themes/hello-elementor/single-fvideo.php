<?php
/**
 * The Template for displaying all single fvideo
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see         https://docs.woocommerce.com/document/template-structure/
 * @package     WooCommerce\Templates
 * @version     1.6.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

get_header();
//FinanceLog(__FILE__);
# get_header( 'shop' );
#
#
?>
<br/>
<br/><br/><br/><br/><br/><br/><br/>
<div>
<!--	-->

</div>
<?php
$post = get_post();
$v = new FVideo_Video($post->ID);
$torrent_key = $v->get_video();
//print $torrent_key;
while ( have_posts() )the_post();
?>

<script>
    var client = new WebTorrent();

    // Sintel, a free, Creative Commons movie
    var torrentId = // 'magnet:?xt=urn:btih:1fe155cdf63ed3a4ed725df4e2e7c1b0c6cf8e82&dn=bbb&tr=udp%3A%2F%2Ftracker.openbittorrent.com%3A80&tr=udp%3A%2F%2Fopentor.org%3A2710&tr=udp%3A%2F%2Ftracker.ccc.de%3A80&tr=udp%3A%2F%2Ftracker.blackunicorn.xyz%3A6969&tr=udp%3A%2F%2Ftracker.coppersurfer.tk%3A6969&tr=udp%3A%2F%2Ftracker.leechers-paradise.org%3A6969';
        'magnet:?xt=urn:btih:08ada5a7a6183aae1e09d831df6748d566095a10&dn=Sintel&tr=udp%3A%2F%2Fexplodie.org%3A6969&tr=udp%3A%2F%2Ftracker.coppersurfer.tk%3A6969&tr=udp%3A%2F%2Ftracker.empire-js.us%3A1337&tr=udp%3A%2F%2Ftracker.leechers-paradise.org%3A6969&tr=udp%3A%2F%2Ftracker.opentrackr.org%3A1337&tr=wss%3A%2F%2Ftracker.btorrent.xyz&tr=wss%3A%2F%2Ftracker.fastcast.nz&tr=wss%3A%2F%2Ftracker.openwebtorrent.com&ws=https%3A%2F%2Fwebtorrent.io%2Ftorrents%2F&xs=https%3A%2F%2Fwebtorrent.io%2Ftorrents%2Fsintel.torrent'
    client.add(torrentId, function (torrent) {
        // Torrents can contain many files. Let's use the .mp4 file
        var file = torrent.files.find(function (file) {
            return file.name.endsWith('.mp4')
        })

        // Display the file by adding it to the DOM.
        // Supports video, audio, image files, and more!
        file.appendTo('body')
    });

    // client.add(magnetURI, function (torrent) {
    //     // create HTTP server for this torrent
    //     var server = torrent.createServer()
    //     server.listen(port) // start the server listening to a port
    //
    //     // visit http://localhost:<port>/ to see a list of files
    //
    //     // access individual files at http://localhost:<port>/<index> where index is the index
    //     // in the torrent.files array
    //
    //     // later, cleanup...
    //     server.close()
    //     client.destroy()
    // })


</script>
<script>
var client = new WebTorrent();
</script>

