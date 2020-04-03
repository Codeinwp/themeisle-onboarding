<?php
if ( ! class_exists( 'Themeisle_Onboarding', false ) ) {
	include_once dirname( dirname( dirname( __FILE__ ) ) ) . '/load.php';
}
/**
 * This example use the composer library as mu-plugin.
 * If you use this in the theme vendor folder, you dont need to tweak this.
 */
add_filter( 'themeisle_site_import_uri', 'change_uri' );

function change_uri() {
	return WPMU_PLUGIN_URL;
}

/**
 * Bootstraping the onboarding library.
 */

add_theme_support( 'themeisle-demo-import', array(
	'editors' => array(
		'elementor'
	),
	'local'   => array(
		'elementor' => array(
			'neve-main' => array(
				'url'   => 'https://demo.themeisle.com/neve',
				'title' => 'Neve 2018',
			),
		),
	),
	'i18n'    => array(
		'templates_title'       => __( 'Ready to use pre-built websites with 1-click installation', 'neve' ),
		'templates_description' => __( 'With Neve, you can choose from multiple unique demos, specially designed for you, that can be installed with a single click. You just need to choose your favorite, and we will take care of everything else.', 'neve' ),
	),
) );

add_action( 'init', '_load_onboarding' );
function _load_onboarding() {
	Themeisle_Onboarding::instance();
}

add_action( 'admin_menu', 'stub_admin_menu' );
/**
 * Adds stub admin page for rendering the site library
 */
function stub_admin_menu() {
	add_menu_page( 'My sites', 'My sites', 'manage_options', 'tionboard_sites', array(
		Themeisle_Onboarding::instance(),
		'render_onboarding'
	), 'dashicons-tickets', 6 );
}
