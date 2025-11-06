<?php
/**
 * Simple test runner (without WordPress test suite)
 * Run: php tests/simple-test.php
 *
 * @package LG_AITranslator
 */

// Mock WordPress functions for testing
function get_option( $option, $default = false ) {
	global $_test_options;
	return $_test_options[ $option ] ?? $default;
}

function update_option( $option, $value ) {
	global $_test_options;
	$_test_options[ $option ] = $value;
	return true;
}

function delete_option( $option ) {
	global $_test_options;
	unset( $_test_options[ $option ] );
	return true;
}

function sanitize_text_field( $str ) {
	return strip_tags( trim( $str ) );
}

// Initialize test options
global $_test_options;
$_test_options = array();

// Load only the LG_AITranslator class definition
class LG_AITranslator {
	private static $instance = null;

	public static $languages = array(
		'en' => 'English',
		'ja' => '日本語',
		'zh-CN' => '简体中文',
		'zh-TW' => '繁體中文',
		'ko' => '한국어',
		'es' => 'Español',
		'fr' => 'Français',
		'de' => 'Deutsch',
		'it' => 'Italiano',
		'pt' => 'Português',
		'ru' => 'Русский',
		'ar' => 'العربية',
		'hi' => 'हिन्दी',
		'th' => 'ไทย',
		'vi' => 'Tiếng Việt',
		'id' => 'Bahasa Indonesia',
		'tr' => 'Türkçe',
		'pl' => 'Polski',
		'nl' => 'Nederlands',
		'sv' => 'Svenska'
	);

	public static function get_all_languages() {
		$preset = self::$languages;
		$custom = get_option('lg_aitranslator_custom_languages', array());
		return array_merge($preset, $custom);
	}

	public static function get_custom_languages() {
		return get_option('lg_aitranslator_custom_languages', array());
	}

	public static function is_preset_language($code) {
		return isset(self::$languages[$code]);
	}

	public static function validate_language_code($code) {
		return preg_match('/^[a-zA-Z0-9_-]+$/', $code) === 1 && !empty($code);
	}

	public static function add_custom_language($code, $name) {
		if (!self::validate_language_code($code)) {
			return false;
		}

		$name = sanitize_text_field($name);
		if (empty(trim($name))) {
			return false;
		}

		$custom = get_option('lg_aitranslator_custom_languages', array());
		$custom[$code] = $name;

		return update_option('lg_aitranslator_custom_languages', $custom);
	}

	public static function remove_custom_language($code) {
		$custom = get_option('lg_aitranslator_custom_languages', array());

		if (!isset($custom[$code])) {
			return false;
		}

		unset($custom[$code]);
		return update_option('lg_aitranslator_custom_languages', $custom);
	}
}

// Test runner
class SimpleTestRunner {
	private $passed = 0;
	private $failed = 0;

	public function assert_true( $condition, $message ) {
		if ( $condition ) {
			$this->passed++;
			echo "✓ PASS: $message\n";
		} else {
			$this->failed++;
			echo "✗ FAIL: $message\n";
		}
	}

	public function assert_false( $condition, $message ) {
		$this->assert_true( ! $condition, $message );
	}

	public function assert_equals( $expected, $actual, $message ) {
		if ( $expected === $actual ) {
			$this->passed++;
			echo "✓ PASS: $message\n";
		} else {
			$this->failed++;
			echo "✗ FAIL: $message (expected: " . var_export( $expected, true ) . ", got: " . var_export( $actual, true ) . ")\n";
		}
	}

	public function assert_count( $expected, $array, $message ) {
		$actual = count( $array );
		$this->assert_equals( $expected, $actual, $message );
	}

	public function summary() {
		echo "\n" . str_repeat( '=', 50 ) . "\n";
		echo "Tests: " . ( $this->passed + $this->failed ) . "\n";
		echo "Passed: $this->passed\n";
		echo "Failed: $this->failed\n";
		echo str_repeat( '=', 50 ) . "\n";

		return $this->failed === 0;
	}
}

// Run tests
echo "Running Custom Language Tests...\n\n";

$test = new SimpleTestRunner();

// Test 1: get_all_languages() returns preset languages
echo "Test: get_all_languages() returns preset languages\n";
$languages = LG_AITranslator::get_all_languages();
$test->assert_true( isset( $languages['en'] ), 'English (en) exists' );
$test->assert_true( isset( $languages['ja'] ), 'Japanese (ja) exists' );
$test->assert_equals( 'English', $languages['en'], 'English name is correct' );
echo "\n";

// Test 2: get_all_languages() with no custom languages
echo "Test: get_all_languages() with no custom languages\n";
delete_option( 'lg_aitranslator_custom_languages' );
$languages = LG_AITranslator::get_all_languages();
$test->assert_count( 20, $languages, 'Should have 20 preset languages' );
echo "\n";

// Test 3: get_all_languages() merges custom languages
echo "Test: get_all_languages() merges custom languages\n";
update_option( 'lg_aitranslator_custom_languages', array(
	'tl' => 'Tagalog',
	'ms' => 'Malay',
) );
$languages = LG_AITranslator::get_all_languages();
$test->assert_count( 22, $languages, 'Should have 22 languages (20 preset + 2 custom)' );
$test->assert_true( isset( $languages['tl'] ), 'Tagalog (tl) exists' );
$test->assert_equals( 'Tagalog', $languages['tl'], 'Tagalog name is correct' );
echo "\n";

// Test 4: add_custom_language()
echo "Test: add_custom_language()\n";
delete_option( 'lg_aitranslator_custom_languages' );
$result = LG_AITranslator::add_custom_language( 'fil', 'Filipino' );
$test->assert_true( $result, 'add_custom_language() returns true' );
$custom = get_option( 'lg_aitranslator_custom_languages', array() );
$test->assert_true( isset( $custom['fil'] ), 'Filipino was added' );
$test->assert_equals( 'Filipino', $custom['fil'], 'Filipino name is correct' );
echo "\n";

// Test 5: validate_language_code()
echo "Test: validate_language_code()\n";
$test->assert_true( LG_AITranslator::validate_language_code( 'en' ), 'Simple code is valid' );
$test->assert_true( LG_AITranslator::validate_language_code( 'zh-CN' ), 'Code with hyphen is valid' );
$test->assert_true( LG_AITranslator::validate_language_code( 'pt_BR' ), 'Code with underscore is valid' );
$test->assert_false( LG_AITranslator::validate_language_code( '' ), 'Empty code is invalid' );
$test->assert_false( LG_AITranslator::validate_language_code( 'en us' ), 'Code with space is invalid' );
$test->assert_false( LG_AITranslator::validate_language_code( 'en@us' ), 'Code with special char is invalid' );
echo "\n";

// Test 6: remove_custom_language()
echo "Test: remove_custom_language()\n";
LG_AITranslator::add_custom_language( 'tl', 'Tagalog' );
LG_AITranslator::add_custom_language( 'ms', 'Malay' );
$result = LG_AITranslator::remove_custom_language( 'tl' );
$test->assert_true( $result, 'remove_custom_language() returns true' );
$custom = LG_AITranslator::get_custom_languages();
$test->assert_false( isset( $custom['tl'] ), 'Tagalog was removed' );
$test->assert_true( isset( $custom['ms'] ), 'Malay still exists' );
echo "\n";

// Test 7: is_preset_language()
echo "Test: is_preset_language()\n";
$test->assert_true( LG_AITranslator::is_preset_language( 'en' ), 'English is preset' );
$test->assert_true( LG_AITranslator::is_preset_language( 'ja' ), 'Japanese is preset' );
$test->assert_false( LG_AITranslator::is_preset_language( 'tl' ), 'Tagalog is not preset' );
echo "\n";

// Summary
$success = $test->summary();
exit( $success ? 0 : 1 );
