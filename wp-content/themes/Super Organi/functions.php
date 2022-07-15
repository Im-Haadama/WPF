<?php



add_action( 'wp_enqueue_scripts', 'enqueue_parent_styles' );

function enqueue_parent_styles() {
	wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );
}

//load child theme custom CSS
add_action( 'wp_enqueue_scripts', 'my_theme_enqueue_styles', 11 );
function my_theme_enqueue_styles() {
	wp_enqueue_style( 'child-style', get_stylesheet_uri() );
}

add_action('wp_head', 'google_code', 10, 0);

function google_code()
{
	echo '<meta name="google-site-verification" content="4rP5fFtqV97zfw1rTGVDuf-u-Rb5An6f56Pxyfr6AYY" />';
}