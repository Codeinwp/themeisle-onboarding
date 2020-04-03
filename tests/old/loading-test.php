<?php
/**
 * Loading test for lower PHP versions.
 *
 * @package themeisle-onboarding
 */

/**
 * Test library loading.
 */
class Sdk_Loading_Old_Test extends WP_UnitTestCase {
	/**
	 * Test if the library is loading properly and version is exported.
	 */
	public function test_class_init() {
		$this->assertInstanceOf( 'Themeisle_Onboarding', Themeisle_Onboarding::instance() );
	}

}
