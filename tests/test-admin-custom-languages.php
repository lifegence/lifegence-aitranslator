<?php
/**
 * Admin Custom Languages UI Test
 *
 * @package LIFEAI_AITranslator
 */

/**
 * Test admin custom language management UI
 */
class Test_Admin_Custom_Languages extends WP_UnitTestCase {

	/**
	 * Admin settings instance
	 */
	private $admin_settings;

	/**
	 * Set up test environment
	 */
	public function setUp(): void {
		parent::setUp();

		// Set up admin user
		$user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user_id );

		// Clean up options
		delete_option( 'lifeai_aitranslator_custom_languages' );
		delete_option( 'lifeai_aitranslator_settings' );

		// Initialize admin settings
		require_once LIFEAI_AITRANS_PLUGIN_DIR . 'admin/class-admin-settings.php';
		$this->admin_settings = new LIFEAI_AITranslator_Admin_Settings();
	}

	/**
	 * Clean up after tests
	 */
	public function tearDown(): void {
		delete_option( 'lifeai_aitranslator_custom_languages' );
		delete_option( 'lifeai_aitranslator_settings' );
		parent::tearDown();
	}

	/**
	 * Test save_custom_languages() method exists
	 */
	public function test_save_custom_languages_method_exists() {
		$this->assertTrue(
			method_exists( $this->admin_settings, 'save_custom_languages' ),
			'save_custom_languages method should exist'
		);
	}

	/**
	 * Test save_custom_languages() saves valid languages
	 */
	public function test_save_custom_languages_saves_valid_data() {
		$_POST = array(
			'custom_language_codes' => array( 'tl', 'ms', 'fil' ),
			'custom_language_names' => array( 'Tagalog', 'Malay', 'Filipino' ),
		);

		$result = $this->admin_settings->save_custom_languages();

		$this->assertTrue( $result );

		$custom = get_option( 'lifeai_aitranslator_custom_languages', array() );
		$this->assertCount( 3, $custom );
		$this->assertEquals( 'Tagalog', $custom['tl'] );
		$this->assertEquals( 'Malay', $custom['ms'] );
		$this->assertEquals( 'Filipino', $custom['fil'] );
	}

	/**
	 * Test save_custom_languages() filters invalid codes
	 */
	public function test_save_custom_languages_filters_invalid_codes() {
		$_POST = array(
			'custom_language_codes' => array( 'tl', 'invalid code', 'ms' ),
			'custom_language_names' => array( 'Tagalog', 'Invalid', 'Malay' ),
		);

		$result = $this->admin_settings->save_custom_languages();

		$custom = get_option( 'lifeai_aitranslator_custom_languages', array() );
		$this->assertCount( 2, $custom ); // Only 2 valid languages
		$this->assertArrayHasKey( 'tl', $custom );
		$this->assertArrayHasKey( 'ms', $custom );
		$this->assertArrayNotHasKey( 'invalid code', $custom );
	}

	/**
	 * Test save_custom_languages() filters empty names
	 */
	public function test_save_custom_languages_filters_empty_names() {
		$_POST = array(
			'custom_language_codes' => array( 'tl', 'ms', 'fil' ),
			'custom_language_names' => array( 'Tagalog', '', 'Filipino' ),
		);

		$result = $this->admin_settings->save_custom_languages();

		$custom = get_option( 'lifeai_aitranslator_custom_languages', array() );
		$this->assertCount( 2, $custom ); // Only 2 with valid names
		$this->assertArrayHasKey( 'tl', $custom );
		$this->assertArrayHasKey( 'fil', $custom );
		$this->assertArrayNotHasKey( 'ms', $custom );
	}

	/**
	 * Test save_custom_languages() sanitizes input
	 */
	public function test_save_custom_languages_sanitizes_input() {
		$_POST = array(
			'custom_language_codes' => array( 'tl' ),
			'custom_language_names' => array( '<script>alert("xss")</script>Tagalog' ),
		);

		$result = $this->admin_settings->save_custom_languages();

		$custom = get_option( 'lifeai_aitranslator_custom_languages', array() );
		$this->assertStringNotContainsString( '<script>', $custom['tl'] );
		$this->assertStringNotContainsString( 'alert', $custom['tl'] );
	}

	/**
	 * Test save_custom_languages() handles empty arrays
	 */
	public function test_save_custom_languages_handles_empty_arrays() {
		// First add some custom languages
		update_option( 'lifeai_aitranslator_custom_languages', array(
			'tl' => 'Tagalog',
			'ms' => 'Malay',
		) );

		// Now save empty arrays (should clear custom languages)
		$_POST = array(
			'custom_language_codes' => array(),
			'custom_language_names' => array(),
		);

		$result = $this->admin_settings->save_custom_languages();

		$custom = get_option( 'lifeai_aitranslator_custom_languages', array() );
		$this->assertEmpty( $custom );
	}

	/**
	 * Test save_custom_languages() handles mismatched arrays
	 */
	public function test_save_custom_languages_handles_mismatched_arrays() {
		$_POST = array(
			'custom_language_codes' => array( 'tl', 'ms' ),
			'custom_language_names' => array( 'Tagalog' ), // Missing one name
		);

		$result = $this->admin_settings->save_custom_languages();

		$custom = get_option( 'lifeai_aitranslator_custom_languages', array() );
		// Should only save the one with both code and name
		$this->assertCount( 1, $custom );
		$this->assertEquals( 'Tagalog', $custom['tl'] );
	}

	/**
	 * Test render_custom_languages_section() method exists
	 */
	public function test_render_custom_languages_section_exists() {
		$this->assertTrue(
			method_exists( $this->admin_settings, 'render_custom_languages_section' ),
			'render_custom_languages_section method should exist'
		);
	}

	/**
	 * Test render_custom_languages_section() outputs HTML
	 */
	public function test_render_custom_languages_section_outputs_html() {
		ob_start();
		$this->admin_settings->render_custom_languages_section();
		$output = ob_get_clean();

		$this->assertNotEmpty( $output );
		$this->assertStringContainsString( 'lg-custom-languages', $output );
		$this->assertStringContainsString( 'Add Custom Language', $output );
	}

	/**
	 * Test render_custom_languages_section() displays existing custom languages
	 */
	public function test_render_custom_languages_section_displays_existing() {
		// Add custom languages
		update_option( 'lifeai_aitranslator_custom_languages', array(
			'tl' => 'Tagalog',
			'ms' => 'Malay',
		) );

		ob_start();
		$this->admin_settings->render_custom_languages_section();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'Tagalog', $output );
		$this->assertStringContainsString( 'Malay', $output );
		$this->assertStringContainsString( 'tl', $output );
		$this->assertStringContainsString( 'ms', $output );
	}

	/**
	 * Test get_custom_languages_data() returns correct format
	 */
	public function test_get_custom_languages_data_format() {
		update_option( 'lifeai_aitranslator_custom_languages', array(
			'tl' => 'Tagalog',
			'ms' => 'Malay',
		) );

		$this->assertTrue(
			method_exists( $this->admin_settings, 'get_custom_languages_data' ),
			'get_custom_languages_data method should exist'
		);

		$data = $this->admin_settings->get_custom_languages_data();

		$this->assertIsArray( $data );
		$this->assertCount( 2, $data );

		foreach ( $data as $lang ) {
			$this->assertArrayHasKey( 'code', $lang );
			$this->assertArrayHasKey( 'name', $lang );
		}
	}

	/**
	 * Test AJAX handler for adding custom language
	 */
	public function test_ajax_add_custom_language() {
		$this->assertTrue(
			method_exists( $this->admin_settings, 'ajax_add_custom_language' ),
			'ajax_add_custom_language method should exist'
		);
	}

	/**
	 * Test AJAX handler for removing custom language
	 */
	public function test_ajax_remove_custom_language() {
		$this->assertTrue(
			method_exists( $this->admin_settings, 'ajax_remove_custom_language' ),
			'ajax_remove_custom_language method should exist'
		);
	}

	/**
	 * Test duplicate code prevention
	 */
	public function test_prevents_duplicate_codes() {
		$_POST = array(
			'custom_language_codes' => array( 'tl', 'tl', 'ms' ), // Duplicate 'tl'
			'custom_language_names' => array( 'Tagalog1', 'Tagalog2', 'Malay' ),
		);

		$result = $this->admin_settings->save_custom_languages();

		$custom = get_option( 'lifeai_aitranslator_custom_languages', array() );

		// Should only have 2 languages (last 'tl' overwrites first)
		$this->assertCount( 2, $custom );
		$this->assertArrayHasKey( 'tl', $custom );
		$this->assertArrayHasKey( 'ms', $custom );
	}

	/**
	 * Test integration with main settings save
	 */
	public function test_integration_with_settings_save() {
		$_POST = array(
			'lifeai_aitranslator_settings_nonce' => wp_create_nonce( 'lifeai_aitranslator_settings' ),
			'enabled' => '1',
			'default_language' => 'en',
			'supported_languages' => array( 'en', 'ja' ),
			'custom_language_codes' => array( 'tl' ),
			'custom_language_names' => array( 'Tagalog' ),
		);

		// This should save both regular settings and custom languages
		$this->admin_settings->save_settings();

		$settings = get_option( 'lifeai_aitranslator_settings', array() );
		$custom = get_option( 'lifeai_aitranslator_custom_languages', array() );

		$this->assertTrue( $settings['enabled'] );
		$this->assertEquals( 'Tagalog', $custom['tl'] );
	}
}
