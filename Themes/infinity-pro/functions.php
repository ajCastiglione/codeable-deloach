<?php

/**
 * Infinity Pro.
 *
 * This file adds functions to the Infinity Pro Theme.
 *
 * @package Infinity
 * @author  StudioPress
 * @license GPL-2.0+
 * @link    http://my.studiopress.com/themes/infinity/
 */

// Start the engine.
include_once(get_template_directory() . '/lib/init.php');

// Child theme (do not remove).
define('CHILD_THEME_NAME', 'Infinity Pro');
define('CHILD_THEME_URL', 'http://my.studiopress.com/themes/infinity/');
define('CHILD_THEME_VERSION', '1.2.0');

// Setup Theme.
include_once(get_stylesheet_directory() . '/lib/theme-defaults.php');

// Helper functions.
include_once(get_stylesheet_directory() . '/lib/helper-functions.php');

// Include customizer CSS.
include_once(get_stylesheet_directory() . '/lib/output.php');

// Add image upload and color select to theme customizer.
require_once(get_stylesheet_directory() . '/lib/customize.php');

// Add the required WooCommerce functions.
include_once(get_stylesheet_directory() . '/lib/woocommerce/woocommerce-setup.php');

// Add the required WooCommerce custom CSS.
include_once(get_stylesheet_directory() . '/lib/woocommerce/woocommerce-output.php');

// Include notice to install Genesis Connect for WooCommerce.
include_once(get_stylesheet_directory() . '/lib/woocommerce/woocommerce-notice.php');

add_action('after_setup_theme', 'genesis_child_gutenberg_support');
/**
 * Adds Gutenberg opt-in features and styling.
 *
 * Allows plugins to remove support if required.
 *
 * @since 1.2.0
 */
function genesis_child_gutenberg_support()
{

	require_once get_stylesheet_directory() . '/lib/gutenberg/init.php';
}

// Set Localization (do not remove).
add_action('after_setup_theme', 'infinity_localization_setup');
function infinity_localization_setup()
{
	load_child_theme_textdomain('infinity-pro', get_stylesheet_directory() . '/languages');
}

// Enqueue scripts and styles.
add_action('wp_enqueue_scripts', 'infinity_enqueue_scripts_styles');
function infinity_enqueue_scripts_styles()
{

	wp_enqueue_style('infinity-fonts', 'https://fonts.googleapis.com/css?family=Cormorant+Garamond:400,400i,700%7CRaleway:700%7CLato%7COswald:300,400,700', array(), CHILD_THEME_VERSION);
	wp_enqueue_style('infinity-ionicons', '//code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css', array(), CHILD_THEME_VERSION);

	wp_enqueue_script('infinity-match-height', get_stylesheet_directory_uri() . '/js/match-height.js', array('jquery'), '0.5.2', true);
	wp_enqueue_script('infinity-global', get_stylesheet_directory_uri() . '/js/global.js', array('jquery', 'infinity-match-height'), '1.0.0', true);

	$suffix = (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG) ? '' : '.min';
	wp_enqueue_script('infinity-responsive-menu', get_stylesheet_directory_uri() . '/js/responsive-menus' . $suffix . '.js', array('jquery'), CHILD_THEME_VERSION, true);
	wp_localize_script(
		'infinity-responsive-menu',
		'genesis_responsive_menu',
		infinity_responsive_menu_settings()
	);
}

// Define our responsive menu settings.
function infinity_responsive_menu_settings()
{

	$settings = array(
		'mainMenu'         => __('Menu', 'infinity-pro'),
		'menuIconClass'    => 'ionicons-before ion-ios-drag',
		'subMenu'          => __('Submenu', 'infinity-pro'),
		'subMenuIconClass' => 'ionicons-before ion-chevron-down',
		'menuClasses'      => array(
			'others' => array(
				'.nav-primary',
			),
		),
	);

	return $settings;
}

// Add HTML5 markup structure.
add_theme_support('html5', array('caption', 'comment-form', 'comment-list', 'gallery', 'search-form'));

// Add accessibility support.
add_theme_support('genesis-accessibility', array('404-page', 'drop-down-menu', 'headings', 'rems', 'search-form', 'skip-links'));

// Add viewport meta tag for mobile browsers.
add_theme_support('genesis-responsive-viewport');

// Add support for custom header.
add_theme_support('custom-header', array(
	'width'           => 400,
	'height'          => 130,
	'header-selector' => '.site-title a',
	'header-text'     => false,
	'flex-height'     => true,
));

// Add image sizes.
add_image_size('mini-thumbnail', 75, 75, TRUE);
add_image_size('team-member', 600, 600, TRUE);

// Add support for after entry widget.
add_theme_support('genesis-after-entry-widget-area');

// Remove header right widget area.
unregister_sidebar('header-right');

// Remove secondary sidebar.
unregister_sidebar('sidebar-alt');

// Remove site layouts.
genesis_unregister_layout('content-sidebar-sidebar');
genesis_unregister_layout('sidebar-content-sidebar');
genesis_unregister_layout('sidebar-sidebar-content');

// Remove output of primary navigation right extras.
remove_filter('genesis_nav_items', 'genesis_nav_right', 10, 2);
remove_filter('wp_nav_menu_items', 'genesis_nav_right', 10, 2);

// Remove navigation meta box.
add_action('genesis_theme_settings_metaboxes', 'infinity_remove_genesis_metaboxes');
function infinity_remove_genesis_metaboxes($_genesis_theme_settings_pagehook)
{
	remove_meta_box('genesis-theme-settings-nav', $_genesis_theme_settings_pagehook, 'main');
}

// Remove skip link for primary navigation.
add_filter('genesis_skip_links_output', 'infinity_skip_links_output');
function infinity_skip_links_output($links)
{

	if (isset($links['genesis-nav-primary'])) {
		unset($links['genesis-nav-primary']);
	}

	return $links;
}

// Rename primary and secondary navigation menus.
add_theme_support('genesis-menus', array('primary' => __('Header Menu', 'infinity-pro'), 'secondary' => __('Footer Menu', 'infinity-pro')));

// Reposition primary navigation menu.
remove_action('genesis_after_header', 'genesis_do_nav');
add_action('genesis_header', 'genesis_do_nav', 12);

// Reposition the secondary navigation menu.
remove_action('genesis_after_header', 'genesis_do_subnav');
add_action('genesis_footer', 'genesis_do_subnav', 5);

// Add offscreen content if active.
add_action('genesis_after_header', 'infinity_offscreen_content_output');
function infinity_offscreen_content_output()
{

	$button = '<button class="offscreen-content-toggle"><i class="icon ion-ios-close-empty"></i> <span class="screen-reader-text">' . __('Hide Offscreen Content', 'infinity-pro') . '</span></button>';

	if (is_active_sidebar('offscreen-content')) {

		echo '<div class="offscreen-content-icon"><button class="offscreen-content-toggle"><i class="icon ion-ios-more"></i> <span class="screen-reader-text">' . __('Show Offscreen Content', 'infinity-pro') . '</span></button></div>';
	}

	genesis_widget_area('offscreen-content', array(
		'before' => '<div class="offscreen-content"><div class="offscreen-container"><div class="widget-area"><div class="wrap">',
		'after'  => '</div>' . $button . '</div></div></div>',
	));
}

// Reduce secondary navigation menu to one level depth.
add_filter('wp_nav_menu_args', 'infinity_secondary_menu_args');
function infinity_secondary_menu_args($args)
{

	if ('secondary' != $args['theme_location']) {
		return $args;
	}

	$args['depth'] = 1;

	return $args;
}

// Modify size of the Gravatar in the author box.
add_filter('genesis_author_box_gravatar_size', 'infinity_author_box_gravatar');
function infinity_author_box_gravatar($size)
{
	return 100;
}

// Modify size of the Gravatar in the entry comments.
add_filter('genesis_comment_list_args', 'infinity_comments_gravatar');
function infinity_comments_gravatar($args)
{

	$args['avatar_size'] = 60;

	return $args;
}

// Setup widget counts.
function infinity_count_widgets($id)
{

	$sidebars_widgets = wp_get_sidebars_widgets();

	if (isset($sidebars_widgets[$id])) {
		return count($sidebars_widgets[$id]);
	}
}

// Determine the widget area class.
function infinity_widget_area_class($id)
{

	$count = infinity_count_widgets($id);

	$class = '';

	if ($count == 1) {
		$class .= ' widget-full';
	} elseif ($count % 3 == 1) {
		$class .= ' widget-thirds';
	} elseif ($count % 4 == 1) {
		$class .= ' widget-fourths';
	} elseif ($count % 2 == 0) {
		$class .= ' widget-halves uneven';
	} else {
		$class .= ' widget-halves';
	}

	return $class;
}

// Add support for 3-column footer widgets.
add_theme_support('genesis-footer-widgets', 3);

// Register widget areas.
genesis_register_sidebar(array(
	'id'          => 'front-page-1',
	'name'        => __('Front Page 1', 'infinity-pro'),
	'description' => __('This is the front page 1 section.', 'infinity-pro'),
));
genesis_register_sidebar(array(
	'id'          => 'front-page-2',
	'name'        => __('Front Page 2', 'infinity-pro'),
	'description' => __('This is the front page 2 section.', 'infinity-pro'),
));
genesis_register_sidebar(array(
	'id'          => 'front-page-3',
	'name'        => __('Front Page 3', 'infinity-pro'),
	'description' => __('This is the front page 3 section.', 'infinity-pro'),
));
genesis_register_sidebar(array(
	'id'          => 'front-page-4',
	'name'        => __('Front Page 4', 'infinity-pro'),
	'description' => __('This is the front page 4 section.', 'infinity-pro'),
));
genesis_register_sidebar(array(
	'id'          => 'front-page-5',
	'name'        => __('Front Page 5', 'infinity-pro'),
	'description' => __('This is the front page 5 section.', 'infinity-pro'),
));
genesis_register_sidebar(array(
	'id'          => 'front-page-6',
	'name'        => __('Front Page 6', 'infinity-pro'),
	'description' => __('This is the front page 6 section.', 'infinity-pro'),
));
genesis_register_sidebar(array(
	'id'          => 'front-page-7',
	'name'        => __('Front Page 7', 'infinity-pro'),
	'description' => __('This is the front page 7 section.', 'infinity-pro'),
));
genesis_register_sidebar(array(
	'id'          => 'lead-capture',
	'name'        => __('Lead Capture', 'infinity-pro'),
	'description' => __('This is the lead capture section.', 'infinity-pro'),
));
genesis_register_sidebar(array(
	'id'          => 'offscreen-content',
	'name'        => __('Offscreen Content', 'infinity-pro'),
	'description' => __('This is the offscreen content section.', 'infinity-pro'),
));






/****************************************\
# MANUALLY ADDED WIDGET AREAS CREATED ON 07/17/2019
\****************************************/

/********************\
## Manually Added Header Widget
\********************/

function techreative_header_image_widget()
{

	register_sidebar(array(
		'name'          => 'Header Image Widget (Manually Added on 07/17/2019)',
		'id'            => 'techreative-header-image-widget',
	));
}
add_action('widgets_init', 'techreative_header_image_widget');


/********************\
## Manually Added Header Widget
\********************/

function techreative_header_widget()
{

	register_sidebar(array(
		'name'          => 'Header Widget (Manually Added on 07/17/2019)',
		'id'            => 'techreative-header-widget',
	));
}
add_action('widgets_init', 'techreative_header_widget');


/********************\
## Manually Added Footer Widget
\********************/

function techreative_footer_widget()
{

	register_sidebar(array(
		'name'          => 'Footer Widget (Manually Added on 07/17/2019)',
		'id'            => 'techreative-footer-widget',
		'before_title'  => '<span class="techreative-hide-these-titles">',
		'after_title'   => '</span>',
	));
}
add_action('widgets_init', 'techreative_footer_widget');


/********************\
## Show Genesis footer widgets only on Front Page (dded on 8/18/2019)
\********************/

add_action('genesis_before_footer', 'footer_widgets_on_home_only', 1);
function footer_widgets_on_home_only()
{
	if (!is_front_page()) {
		remove_action('genesis_before_footer', 'genesis_footer_widget_areas');
	}
}

/****************************************/


// Step-1: Create Extra Widget Area

genesis_register_sidebar(array(
	'id' => 'crunchifybeforefooterarea',
	'name' => __('Crunchify_Before_Footer_Area', 'child theme'),
	'description' => __('This is Crunchify Before Footer Widget Headline...', 'child theme'),
));

// Step-2: Position Widget Header - Place widget before Widget area

add_action('genesis_before_footer', 'crunchify_before_footer_area', 15);
function crunchify_before_footer_area()
{
	genesis_widget_area('crunchifyBeforeFooterArea', array(
		'before' => '<div class="crunchify-matched-content-footer" id="beforefooterid">',
		'after'  => '</div>',
	));
}

// Add featured image on single post
add_action('genesis_entry_header', 'themeprefix_featured_image', 1);
function themeprefix_featured_image()
{
	$image = genesis_get_image(array( // more options here -> genesis/lib/functions/image.php
		'format'  => 'html',
		'size'    => 'large', // add in your image size large, medium or thumbnail - for custom see the post
		'context' => '',
		'attr'    => array('class' => 'aligncenter'), // set a default WP image class

	));
	if (is_singular()) {
		if ($image) {
			printf('<div class="featured-image-class">%s</div>', $image); // wraps the featured image in a div with css class you can control
		}
	}
}

// Remove fontawesome enqueue

add_action('wp_enqueue_scripts', 'remove_atomic_block_css', 100);

function remove_atomic_block_css()
{
	wp_dequeue_style('atomic-blocks-fontawesome');
}

function add_rel_preload($html, $handle, $href, $media)
{
	if (is_admin())
		return $html;

	// var_dump($handle . ' Links To ' . $href);
	if (strstr($handle, 'sb_instagram_styles')) {
		$html = <<<EOT
<link rel='preload' as='style' onload="this.onload=null;this.rel='stylesheet'" id='$handle' href='$href' type='text/css' media='all' />
EOT;
	}
	if (strstr($handle, 'atomic-blocks-style-css')) {
		$html = <<<EOT
<link rel='preload' as='style' onload="this.onload=null;this.rel='stylesheet'" id='$handle' href='$href' type='text/css' media='all' />
EOT;
	}
	if (strstr($handle, 'post-views-counter-frontend')) {
		$html = <<<EOT
<link rel='preload' as='style' onload="this.onload=null;this.rel='stylesheet'" id='$handle' href='$href' type='text/css' media='all' />
EOT;
	}
	if (strstr($handle, 'infinity-ionicons')) {
		$html = <<<EOT
<link rel='preload' as='style' onload="this.onload=null;this.rel='stylesheet'" id='$handle' href='$href' type='text/css' media='all' />
EOT;
	}
	if (strstr($handle, 'wp-block-library')) {
		$html = <<<EOT
<link rel='preload' as='style' onload="this.onload=null;this.rel='stylesheet'" id='$handle' href='$href' type='text/css' media='all' />
EOT;
	}
	if (strstr($handle, 'dashicons')) {
		$html = <<<EOT
<link rel='preload' as='style' onload="this.onload=null;this.rel='stylesheet'" id='$handle' href='$href' type='text/css' media='all' />
EOT;
	}
	if (strstr($handle, 'simple-social-icons-font')) {
		$html = <<<EOT
<link rel='preload' as='style' onload="this.onload=null;this.rel='stylesheet'" id='$handle' href='$href' type='text/css' media='all' />
EOT;
	}
	if (strstr($handle, 'infinity-pro-gutenberg')) {
		$html = <<<EOT
<link rel='preload' as='style' onload="this.onload=null;this.rel='stylesheet'" id='$handle' href='$href' type='text/css' media='all' />
EOT;
	}

	return $html;
}
add_filter('style_loader_tag', 'add_rel_preload', 10, 4);
