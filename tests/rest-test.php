<?php
/**
 * `loading` test.
 *
 * @package themeisle-onboarding
 */

/**
 * Test onboarding loading.
 */
class Onboarding_Rest_Test extends WP_UnitTestCase {
	public static $admin_id;

	/**
	 * @var Themeisle_OB_Rest_Server
	 */
	private $rest_api;

	/**
	 * @var string
	 */
	private $json;

	/**
	 * @var Themeisle_OB_Widgets_Importer
	 */
	private $widget_importer;

	/**
	 * @param $factory
	 */
	public static function wpSetUpBeforeClass( $factory ) {
		self::$admin_id = $factory->user->create( array( 'role' => 'administrator' ) );
	}

	/**
	 * setUp
	 */
	public function setUp() {
		parent::setUp();
		wp_set_current_user( self::$admin_id );
		add_theme_support( 'themeisle-demo-import', array(
			'editors' => array(
				'elementor',
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
		tests_add_filter( 'template_directory', function () {
			return dirname( __FILE__ ) . '/sample-theme';
		} );
		Themeisle_Onboarding::instance();

		$this->json     = json_decode( file_get_contents( dirname( __FILE__ ) . '/sample-theme/onboarding/neve-main/data.json' ), true );
		$this->rest_api = new Themeisle_OB_Rest_Server();
		$this->rest_api->init();

		require_once dirname( __FILE__ ) . '/../includes/importers/class-themeisle-ob-widgets-importer.php';
		$this->widget_importer = new Themeisle_OB_Widgets_Importer();
		add_filter( 'async_update_translation', '__return_false' );
	}

	/**
	 * @covers Themeisle_OB_Rest_Server::init
	 */
	public function test_theme_support_loading() {

		$templates = $this->rest_api->init_library();
		$templates = $templates->data['data'];
		$this->assertNotEmpty( $templates );
		$this->assertArrayHasKey( 'local', $templates );
		$this->assertNotEmpty( $templates['local'] );
	}

	/**
	 * @covers Themeisle_OB_Theme_Mods_Importer::import_theme_mods
	 */
	public function test_theme_mods_importing() {

		$request = new WP_REST_Request();

		$request->set_header( 'content-type', 'application/json' );
		$request->set_method( 'POST' );
		$request->set_body_params( array(
				'data' => array(
					'theme_mods' => $this->json['theme_mods'],
					'source_url' => 'https://demo.themeisle.com/neve-charity',
				),
			)
		);

		$response = $this->rest_api->run_theme_mods_importer( $request );

		$this->assertInstanceOf( 'WP_REST_Response', $response );
		$this->assertEquals( 200, $response->get_status() );
		$this->assertTrue( $response->get_data()['success'] );
		//Test that theme mods have been successfully imported.
		$this->assertEquals( '#404248', get_theme_mod( 'neve_text_color' ) );

	}

	/**
	 * @covers Themeisle_OB_Plugin_Importer::install_plugins
	 */
	public function test_plugin_importing() {
		$request = new WP_REST_Request();

		$request->set_header( 'content-type', 'application/json' );
		$request->set_method( 'POST' );
		$request->set_body_params( array(
			'data' => array(
				'themeisle-companion' => true,
				'elementor'           => true,
			),
		) );

		$response = $this->rest_api->run_plugin_importer( $request );

		$this->assertInstanceOf( 'WP_REST_Response', $response );
		$this->assertEquals( 200, $response->get_status() );
		$this->assertTrue( $response->get_data()['success'] );
		//Test that plugin has been installed and activated.
		$this->assertTrue( is_plugin_active( 'themeisle-companion/themeisle-companion.php' ) );
		$this->assertTrue( function_exists( 'run_orbit_fox' ) );
	}

	/**
	 * @covers Themeisle_OB_Content_Importer::import_remote_xml
	 */
	public function test_content_import() {
		$request = new WP_REST_Request();

		$request->set_header( 'content-type', 'application/json' );
		$request->set_method( 'POST' );
		$request->set_body_params( array(
			'data' => array(
				'contentFile' => dirname( __FILE__ ) . '/sample-theme/onboarding/neve-main/export.xml',
				'frontPage'   => array(
					'front_page' => $this->json['front_page']['front_page'],
					'blog_page'  => $this->json['front_page']['blog_page'],
				),
				'shop_pages'  => null,
				'source'      => 'local',
				'demoSlug'    => 'test-demo',
			),
		) );

		$response = $this->rest_api->run_xml_importer( $request );
		$this->assertInstanceOf( 'WP_REST_Response', $response );
		$this->assertEquals( 200, $response->get_status() );
		$this->assertTrue( $response->get_data()['success'] );

		//Test that front page has been set up.
		$this->assertEquals( 'page', get_option( 'show_on_front' ) );
		//Test that front page WP_Post object exists and is set as front page.
		$front_page_id = get_option( 'page_on_front' );
		$front_page    = get_post( $front_page_id );
		$this->assertInstanceOf( 'WP_Post', $front_page );
		$this->assertNotEmpty( $front_page->ID );
	}

	/**
	 * @covers Themeisle_OB_Widgets_Importer::import_widgets
	 */
	public function test_widgets_import() {
		$this->remove_all_widgets();

		$request = new WP_REST_Request();
		$request->set_header( 'content-type', 'application/json' );
		$request->set_method( 'POST' );
		$request->set_body_params( array(
			'data' => array(
				'blog-sidebar' => array(
					"search-2"       => array(
						'title' => 'Search Widget Test',
					),
					'recent-posts-2' => array(
						'title'  => 'Recent Posts Widget Test',
						'number' => 5,
					),
				),
			),
		) );

		$response = $this->rest_api->run_widgets_importer( $request );
		$this->assertEquals( 200, $response->get_status() );
		$this->assertTrue( $response->get_data()['success'] );
		$this->assertInstanceOf( 'WP_REST_Response', $response );

		$available_widgets = $this->widget_importer->available_widgets();
		$widget_instances  = array();
		foreach ( $available_widgets as $widget_data ) {
			$instances = get_option( 'widget_' . $widget_data['id_base'] );
			if ( ! empty( $instances ) ) {
				foreach ( $instances as $instance_id => $instance_data ) {
					if ( is_numeric( $instance_id ) ) {
						$unique_instance_id                      = $widget_data['id_base'] . '-' . $instance_id;
						$widget_instances[ $unique_instance_id ] = $instance_data;
					}
				}
			}
		}

		$accepted_titles = array( 'Search Widget Test', 'Recent Posts Widget Test' );

		foreach ( $widget_instances as $widget_instance ) {
			$this->assertNotEmpty( $widget_instance['title'] );
			$this->assertTrue( in_array( $widget_instance['title'], $accepted_titles ) );
		}
	}

	/**
	 * Empty site widgets.
	 */
	private function remove_all_widgets() {
		$available_widgets = $this->widget_importer->available_widgets();
		foreach ( $available_widgets as $widget_data ) {
			delete_option( 'widget_' . $widget_data['id_base'] );
		}
	}
}
