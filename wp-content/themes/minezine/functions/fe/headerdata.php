<?php
/**
 * Headerdata of Theme options.
 * @package MineZine
 * @since MineZine 1.0.0
*/  

// additional js and css
if(	!is_admin()){
function minezine_fonts_include () {
// Google Fonts
$bodyfont = get_theme_mod('minezine_body_google_fonts', minezine_default_options('minezine_body_google_fonts'));
$headingfont = get_theme_mod('minezine_headings_google_fonts', minezine_default_options('minezine_headings_google_fonts'));
$descriptionfont = get_theme_mod('minezine_description_google_fonts', minezine_default_options('minezine_description_google_fonts'));
$headlinefont = get_theme_mod('minezine_headline_google_fonts', minezine_default_options('minezine_headline_google_fonts'));
$headlineboxfont = get_theme_mod('minezine_headline_box_google_fonts', minezine_default_options('minezine_headline_box_google_fonts'));
$postentryfont = get_theme_mod('minezine_postentry_google_fonts', minezine_default_options('minezine_postentry_google_fonts'));
$sidebarfont = get_theme_mod('minezine_sidebar_google_fonts', minezine_default_options('minezine_sidebar_google_fonts'));
$menufont = get_theme_mod('minezine_menu_google_fonts', minezine_default_options('minezine_menu_google_fonts'));
$topmenufont = get_theme_mod('minezine_top_menu_google_fonts', minezine_default_options('minezine_top_menu_google_fonts'));

$fonturl = "//fonts.googleapis.com/css?family=";

$bodyfonturl = $fonturl.$bodyfont;
$headingfonturl = $fonturl.$headingfont;
$descriptionfonturl = $fonturl.$descriptionfont;
$headlinefonturl = $fonturl.$headlinefont;
$headlineboxfonturl = $fonturl.$headlineboxfont;
$postentryfonturl = $fonturl.$postentryfont;
$sidebarfonturl = $fonturl.$sidebarfont;
$menufonturl = $fonturl.$menufont;
$topmenufonturl = $fonturl.$topmenufont;
	// Google Fonts
     if ($bodyfont != 'default' && $bodyfont != ''){
      wp_enqueue_style('minezine-google-font1', $bodyfonturl); 
		 }
     if ($headingfont != 'default' && $headingfont != ''){
      wp_enqueue_style('minezine-google-font2', $headingfonturl);
		 }
     if ($descriptionfont != 'default' && $descriptionfont != ''){
      wp_enqueue_style('minezine-google-font3', $descriptionfonturl);
		 }
     if ($headlinefont != 'default' && $headlinefont != ''){
      wp_enqueue_style('minezine-google-font4', $headlinefonturl); 
		 }
     if ($postentryfont != 'default' && $postentryfont != ''){
      wp_enqueue_style('minezine-google-font5', $postentryfonturl); 
		 }
     if ($sidebarfont != 'default' && $sidebarfont != ''){
      wp_enqueue_style('minezine-google-font6', $sidebarfonturl);
		 }
     if ($menufont != 'default' && $menufont != ''){
      wp_enqueue_style('minezine-google-font8', $menufonturl);
		 }
     if ($topmenufont != 'default' && $topmenufont != ''){
      wp_enqueue_style('minezine-google-font9', $topmenufonturl);
		 }
     if ($headlineboxfont != 'default' && $headlineboxfont != ''){
      wp_enqueue_style('minezine-google-font10', $headlineboxfonturl); 
		 }
}
add_action( 'wp_enqueue_scripts', 'minezine_fonts_include' );
}

// additional js and css
function minezine_css_include () {
		if (get_theme_mod('minezine_css', minezine_default_options('minezine_css')) == 'Blue'){
			wp_enqueue_style('minezine-style-blue', get_template_directory_uri().'/css/blue.css');
		}
    
    if (get_theme_mod('minezine_css', minezine_default_options('minezine_css')) == 'Green'){
			wp_enqueue_style('minezine-style-green', get_template_directory_uri().'/css/green.css');
		}
}
add_action( 'wp_enqueue_scripts', 'minezine_css_include' );

// Background Pattern Opacity
function minezine_get_background_pattern_opacity() {
    $background_pattern_opacity = get_theme_mod('minezine_background_pattern_opacity', minezine_default_options('minezine_background_pattern_opacity')); 
		if ($background_pattern_opacity != '' && $background_pattern_opacity != '100' && $background_pattern_opacity != 'Default') { ?>
		<?php echo '#wrapper .pattern { opacity: 0.'; ?><?php echo $background_pattern_opacity ?><?php echo '; filter: alpha(opacity='; ?><?php echo $background_pattern_opacity ?><?php echo '); }'; ?>
<?php } 
    elseif ($background_pattern_opacity == '100') { ?>
    <?php echo '#wrapper .pattern { opacity: 1; filter: alpha(opacity=100); }';
}  
} 

// Display sidebar
function minezine_display_sidebar() {
    $display_sidebar = get_theme_mod('minezine_display_sidebar', minezine_default_options('minezine_display_sidebar')); 
		if ($display_sidebar == 'Hide') { ?>
		<?php _e('#wrapper #container #main-content #content { width: 100%; }', 'minezine'); ?>
<?php } 
}

// Display header Search Form - header content width
function minezine_display_search_form() {
    $display_search_form = get_theme_mod('minezine_display_search_form', minezine_default_options('minezine_display_search_form')); 
		if ($display_search_form == 'Hide') { ?>
		<?php _e('#wrapper #header .header-content .site-title, #wrapper #header .header-content .site-description, #wrapper #header .header-content .header-logo { max-width: 100%; }', 'minezine'); ?>
<?php } 
}

// Display Meta Box on posts - post entries styling
function minezine_display_meta_post_entry() {
    $display_meta_post_entry = get_theme_mod('minezine_display_meta_post', minezine_default_options('minezine_display_meta_post')); 
		if ($display_meta_post_entry == 'Hide') { ?>
		<?php _e('#wrapper #main-content .post-entry .attachment-post-thumbnail { margin-bottom: 17px; } #wrapper #main-content .post-entry .post-entry-content { margin-bottom: -4px; }', 'minezine'); ?>
<?php } 
}

// FONTS
// Body font
function minezine_get_body_font() {
    $bodyfont = get_theme_mod('minezine_body_google_fonts', minezine_default_options('minezine_body_google_fonts'));
    if ($bodyfont != 'default' && $bodyfont != '') { ?>
    <?php _e('html body, #wrapper blockquote, #wrapper q, #wrapper #container #comments .comment, #wrapper #container #comments .comment time, #wrapper #container #commentform .form-allowed-tags, #wrapper #container #commentform p, #wrapper input, #wrapper button, #wrapper select, #wrapper #content .breadcrumb-navigation, #wrapper #main-content .post-meta { font-family: "', 'minezine'); ?><?php echo $bodyfont ?><?php _e('", Arial, Helvetica, sans-serif; }', 'minezine'); ?>
<?php } 
}

// Site title font
function minezine_get_headings_google_fonts() {
    $headingfont = get_theme_mod('minezine_headings_google_fonts', minezine_default_options('minezine_headings_google_fonts')); 
		if ($headingfont != 'default' && $headingfont != '') { ?>
		<?php _e('#wrapper #header .site-title { font-family: "', 'minezine'); ?><?php echo $headingfont ?><?php _e('", Arial, Helvetica, sans-serif; }', 'minezine'); ?>
<?php } 
}

// Site description font
function minezine_get_description_font() {
    $descriptionfont = get_theme_mod('minezine_description_google_fonts', minezine_default_options('minezine_description_google_fonts')); 
    if ($descriptionfont != 'default' && $descriptionfont != '') { ?>
    <?php _e('#wrapper #header .site-description {font-family: "', 'minezine'); ?><?php echo $descriptionfont ?><?php _e('", Arial, Helvetica, sans-serif; }', 'minezine'); ?>
<?php } 
}

// Page/post headlines font
function minezine_get_headlines_font() {
    $headlinefont = get_theme_mod('minezine_headline_google_fonts', minezine_default_options('minezine_headline_google_fonts')); 
    if ($headlinefont != 'default' && $headlinefont != '') { ?>
		<?php _e('#wrapper h1, #wrapper h2, #wrapper h3, #wrapper h4, #wrapper h5, #wrapper h6, #wrapper #container .navigation .section-heading { font-family: "', 'minezine'); ?><?php echo $headlinefont ?><?php _e('", Arial, Helvetica, sans-serif; }', 'minezine'); ?>
<?php } 
}

// MineZine Posts-List Widgets headlines font
function minezine_get_headline_box_google_fonts() {
    $headline_box_google_fonts = get_theme_mod('minezine_headline_box_google_fonts', minezine_default_options('minezine_headline_box_google_fonts')); 
		if ($headline_box_google_fonts != 'default' && $headline_box_google_fonts != '') { ?>
		<?php _e('#wrapper #container #main-content section .entry-headline { font-family: "', 'minezine'); ?><?php echo $headline_box_google_fonts ?><?php _e('", Arial, Helvetica, sans-serif; }', 'minezine'); ?>
<?php } 
}

// Post entry font
function minezine_get_postentry_font() {
    $postentryfont = get_theme_mod('minezine_postentry_google_fonts', minezine_default_options('minezine_postentry_google_fonts')); 
		if ($postentryfont != 'default' && $postentryfont != '') { ?>
		<?php _e('#wrapper #main-content .post-entry .post-entry-headline, #wrapper #main-content .slides li a, #wrapper #main-content .home-list-posts ul li a { font-family: "', 'minezine'); ?><?php echo $postentryfont ?><?php _e('", Arial, Helvetica, sans-serif; }', 'minezine'); ?>
<?php } 
}

// Sidebar and Footer widget headlines font
function minezine_get_sidebar_widget_font() {
    $sidebarfont = get_theme_mod('minezine_sidebar_google_fonts', minezine_default_options('minezine_sidebar_google_fonts'));
    if ($sidebarfont != 'default' && $sidebarfont != '') { ?>
		<?php _e('#wrapper #container #sidebar .sidebar-widget .sidebar-headline, #wrapper #wrapper-footer #footer .footer-widget .footer-headline { font-family: "', 'minezine'); ?><?php echo $sidebarfont ?><?php _e('", Arial, Helvetica, sans-serif; }', 'minezine'); ?>
<?php } 
}

// Main Header menu font
function minezine_get_menu_font() {
    $menufont = get_theme_mod('minezine_menu_google_fonts', minezine_default_options('minezine_menu_google_fonts')); 
		if ($menufont != 'default' && $menufont != '') { ?>
		<?php _e('#wrapper #header .menu-box ul li a { font-family: "', 'minezine'); ?><?php echo $menufont ?><?php _e('", Arial, Helvetica, sans-serif; }', 'minezine'); ?>
<?php } 
}

// Top Header menu font
function minezine_get_top_menu_font() {
    $topmenufont = get_theme_mod('minezine_top_menu_google_fonts', minezine_default_options('minezine_top_menu_google_fonts')); 
		if ($topmenufont != 'default' && $topmenufont != '') { ?>
		<?php _e('#wrapper #top-navigation-wrapper .top-navigation ul li { font-family: "', 'minezine'); ?><?php echo $topmenufont ?><?php _e('", Arial, Helvetica, sans-serif; }', 'minezine'); ?>
<?php } 
}

// User defined CSS.
function minezine_get_own_css() {
    $own_css = get_theme_mod('minezine_own_css'); 
    $own_css_def = minezine_default_options('minezine_own_css');
		if ($own_css != '') { ?>
		<?php echo esc_attr($own_css); ?>
<?php } elseif ($own_css == '' && $own_css_def != '') { echo esc_attr($own_css_def); } 
}

// Display custom CSS.
function minezine_custom_styles() { ?>
<?php echo ("<style type='text/css'>"); ?>
<?php minezine_get_own_css(); ?>
<?php minezine_get_background_pattern_opacity(); ?>
<?php minezine_display_sidebar(); ?>
<?php minezine_display_search_form(); ?>
<?php minezine_display_meta_post_entry(); ?>
<?php minezine_get_body_font(); ?>
<?php minezine_get_headings_google_fonts(); ?>
<?php minezine_get_description_font(); ?>
<?php minezine_get_headlines_font(); ?>
<?php minezine_get_headline_box_google_fonts(); ?>
<?php minezine_get_postentry_font(); ?>
<?php minezine_get_sidebar_widget_font(); ?>
<?php minezine_get_menu_font(); ?>
<?php minezine_get_top_menu_font(); ?>
<?php echo ("</style>"); ?>
<?php
} 
add_action('wp_enqueue_scripts', 'minezine_custom_styles');	?>