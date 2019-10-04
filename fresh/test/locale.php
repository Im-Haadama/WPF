<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 14/07/17
 * Time: 15:55
 */
require_once( '../r-shop_manager.php' );

// print get_locale();

if ( ! did_action( 'woocommerce_init' ) ) {
	wc_doing_it_wrong( __FUNCTION__, __( 'This function should not be called before woocommerce_init.', 'woocommerce' ), '2.3' );
	return;
}

$mofile = ROOT_DIR . '/wp-content/languages/plugins/im_haadama-he_IL.mo';
if (! file_exists($mofile)){
	print "$mofile not exists";
	die (1);
}
if (! load_textdomain('im-haadama', $mofile))
	print "can't load $mofile";

// print esc_html( translate('Product Name', 'im_haadama') );
print im_translate('Product Name');
