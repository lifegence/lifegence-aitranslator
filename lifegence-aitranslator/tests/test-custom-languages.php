<?php
/**
 * Custom Languages Test
 *
 * @package LG_AITranslator
 */

/**
 * Test custom language functionality
 */
class Test_Custom_Languages extends WP_UnitTestCase {

	/**
	 * Set up test environment
	 */
	public function setUp(): void {
		parent::setUp();
		// Clean up options before each test
		delete_option( 'lg_aitranslator_custom_languages' );
	}

	/**
	 * Clean up after tests
	 */
	public function tearDown(): void {
		delete_option( 'lg_aitranslator_custom_languages' );
		parent::tearDown();
	}

	/**
	 * Test get_all_languages() returns preset languages
	 */
	public function test_get_all_languages_returns_preset_languages() {
		$languages = LG_AITranslator::get_all_languages();

		// Check preset languages exist
		$this->assertArrayHasKey( 'en', $languages );
		$this->assertArrayHasKey( 'ja', $languages );
		$this->assertEquals( 'English', $languages['en'] );
		$this->assertEquals( '日本語', $languages['ja'] );
	}

	/**
	 * Test get_all_languages() with no custom languages
	 */
	public function test_get_all_languages_with_no_custom() {
		$languages = LG_AITranslator::get_all_languages();

		// Should only have preset languages (20 languages)
		$this->assertCount( 20, $languages );
	}

	/**
	 * Test get_all_languages() merges custom languages
	 */
	public function test_get_all_languages_merges_custom() {
		// Add custom languages
		$custom = array(
			'tl' => 'Tagalog',
			'ms' => 'Malay',
		);
		update_option( 'lg_aitranslator_custom_languages', $custom );

		$languages = LG_AITranslator::get_all_languages();

		// Should have preset (20) + custom (2) = 22 languages
		$this->assertCount( 22, $languages );
		$this->assertArrayHasKey( 'tl', $languages );
		$this->assertArrayHasKey( 'ms', $languages );
		$this->assertEquals( 'Tagalog', $languages['tl'] );
		$this->assertEquals( 'Malay', $languages['ms'] );
	}

	/**
	 * Test custom language overrides preset (edge case)
	 */
	public function test_custom_language_can_override_preset() {
		// Custom language with same code as preset
		$custom = array(
			'en' => 'Custom English',
		);
		update_option( 'lg_aitranslator_custom_languages', $custom );

		$languages = LG_AITranslator::get_all_languages();

		// Custom should override preset
		$this->assertEquals( 'Custom English', $languages['en'] );
	}

	/**
	 * Test add_custom_language() method
	 */
	public function test_add_custom_language() {
		$result = LG_AITranslator::add_custom_language( 'tl', 'Tagalog' );

		$this->assertTrue( $result );

		$custom = get_option( 'lg_aitranslator_custom_languages', array() );
		$this->assertArrayHasKey( 'tl', $custom );
		$this->assertEquals( 'Tagalog', $custom['tl'] );
	}

	/**
	 * Test add_custom_language() with invalid code
	 */
	public function test_add_custom_language_invalid_code() {
		// Empty code
		$result = LG_AITranslator::add_custom_language( '', 'Invalid' );
		$this->assertFalse( $result );

		// Invalid characters
		$result = LG_AITranslator::add_custom_language( 'in valid', 'Invalid' );
		$this->assertFalse( $result );

		// Special characters
		$result = LG_AITranslator::add_custom_language( 'test@#$', 'Invalid' );
		$this->assertFalse( $result );
	}

	/**
	 * Test add_custom_language() with valid code formats
	 */
	public function test_add_custom_language_valid_codes() {
		// Simple code
		$result = LG_AITranslator::add_custom_language( 'tl', 'Tagalog' );
		$this->assertTrue( $result );

		// Code with hyphen
		$result = LG_AITranslator::add_custom_language( 'zh-HK', 'Chinese (Hong Kong)' );
		$this->assertTrue( $result );

		// Code with underscore
		$result = LG_AITranslator::add_custom_language( 'pt_BR', 'Portuguese (Brazil)' );
		$this->assertTrue( $result );
	}

	/**
	 * Test add_custom_language() with empty name
	 */
	public function test_add_custom_language_empty_name() {
		$result = LG_AITranslator::add_custom_language( 'tl', '' );
		$this->assertFalse( $result );

		$result = LG_AITranslator::add_custom_language( 'tl', '   ' );
		$this->assertFalse( $result );
	}

	/**
	 * Test remove_custom_language() method
	 */
	public function test_remove_custom_language() {
		// Add a custom language first
		LG_AITranslator::add_custom_language( 'tl', 'Tagalog' );
		LG_AITranslator::add_custom_language( 'ms', 'Malay' );

		// Remove one
		$result = LG_AITranslator::remove_custom_language( 'tl' );
		$this->assertTrue( $result );

		$custom = get_option( 'lg_aitranslator_custom_languages', array() );
		$this->assertArrayNotHasKey( 'tl', $custom );
		$this->assertArrayHasKey( 'ms', $custom );
	}

	/**
	 * Test remove_custom_language() non-existent
	 */
	public function test_remove_custom_language_nonexistent() {
		$result = LG_AITranslator::remove_custom_language( 'nonexistent' );
		$this->assertFalse( $result );
	}

	/**
	 * Test get_custom_languages() method
	 */
	public function test_get_custom_languages() {
		// No custom languages
		$custom = LG_AITranslator::get_custom_languages();
		$this->assertIsArray( $custom );
		$this->assertEmpty( $custom );

		// Add custom languages
		update_option( 'lg_aitranslator_custom_languages', array(
			'tl' => 'Tagalog',
			'ms' => 'Malay',
		) );

		$custom = LG_AITranslator::get_custom_languages();
		$this->assertCount( 2, $custom );
		$this->assertEquals( 'Tagalog', $custom['tl'] );
	}

	/**
	 * Test is_preset_language() method
	 */
	public function test_is_preset_language() {
		$this->assertTrue( LG_AITranslator::is_preset_language( 'en' ) );
		$this->assertTrue( LG_AITranslator::is_preset_language( 'ja' ) );
		$this->assertFalse( LG_AITranslator::is_preset_language( 'tl' ) );
		$this->assertFalse( LG_AITranslator::is_preset_language( 'nonexistent' ) );
	}

	/**
	 * Test language code validation
	 */
	public function test_validate_language_code() {
		// Valid codes
		$this->assertTrue( LG_AITranslator::validate_language_code( 'en' ) );
		$this->assertTrue( LG_AITranslator::validate_language_code( 'zh-CN' ) );
		$this->assertTrue( LG_AITranslator::validate_language_code( 'pt_BR' ) );
		$this->assertTrue( LG_AITranslator::validate_language_code( 'EN' ) ); // Uppercase OK
		$this->assertTrue( LG_AITranslator::validate_language_code( 'fil' ) );

		// Invalid codes
		$this->assertFalse( LG_AITranslator::validate_language_code( '' ) );
		$this->assertFalse( LG_AITranslator::validate_language_code( 'en us' ) ); // Space
		$this->assertFalse( LG_AITranslator::validate_language_code( 'en@us' ) ); // Special char
		$this->assertFalse( LG_AITranslator::validate_language_code( '日本' ) ); // Non-ASCII
	}

	/**
	 * Test multiple custom languages
	 */
	public function test_multiple_custom_languages() {
		LG_AITranslator::add_custom_language( 'tl', 'Tagalog' );
		LG_AITranslator::add_custom_language( 'ms', 'Malay' );
		LG_AITranslator::add_custom_language( 'fil', 'Filipino' );

		$languages = LG_AITranslator::get_all_languages();
		$this->assertCount( 23, $languages ); // 20 preset + 3 custom

		$custom = LG_AITranslator::get_custom_languages();
		$this->assertCount( 3, $custom );
	}

	/**
	 * Test sanitization of language data
	 */
	public function test_language_data_sanitization() {
		// HTML should be stripped from name
		LG_AITranslator::add_custom_language( 'test', '<script>alert("xss")</script>Test' );

		$custom = LG_AITranslator::get_custom_languages();
		$this->assertStringNotContainsString( '<script>', $custom['test'] );
		$this->assertStringNotContainsString( 'alert', $custom['test'] );
	}
}
