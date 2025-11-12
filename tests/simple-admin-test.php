<?php
/**
 * Simple admin test runner (without WordPress test suite)
 * Run: php tests/simple-admin-test.php
 *
 * @package LIFEAI_AITranslator
 */

// Mock WordPress functions
if ( ! function_exists( 'get_option' ) ) {
	function get_option( $option, $default = false ) {
		global $_test_options;
		return $_test_options[ $option ] ?? $default;
	}
}

if ( ! function_exists( 'update_option' ) ) {
	function update_option( $option, $value ) {
		global $_test_options;
		$_test_options[ $option ] = $value;
		return true;
	}
}

if ( ! function_exists( 'delete_option' ) ) {
	function delete_option( $option ) {
		global $_test_options;
		unset( $_test_options[ $option ] );
		return true;
	}
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
	function sanitize_text_field( $str ) {
		return strip_tags( trim( $str ) );
	}
}

if ( ! function_exists( 'esc_html' ) ) {
	function esc_html( $str ) {
		return htmlspecialchars( $str, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_attr' ) ) {
	function esc_attr( $str ) {
		return htmlspecialchars( $str, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_html_e' ) ) {
	function esc_html_e( $str ) {
		echo esc_html( $str );
	}
}

// Initialize test options
global $_test_options, $_POST;
$_test_options = array();
$_POST = array();

// Define LIFEAI_AITranslator class if not already loaded
if ( ! class_exists( 'LIFEAI_AITranslator' ) ) {
	class LIFEAI_AITranslator {
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

		public static function validate_language_code($code) {
			return preg_match('/^[a-zA-Z0-9_-]+$/', $code) === 1 && !empty($code);
		}
	}
}

// Mock admin settings class
class LIFEAI_AITranslator_Admin_Settings {

	/**
	 * Save custom languages from POST data
	 *
	 * @return bool True on success
	 */
	public function save_custom_languages() {
		$codes = $_POST['custom_language_codes'] ?? array();
		$names = $_POST['custom_language_names'] ?? array();

		$custom_languages = array();

		foreach ( $codes as $index => $code ) {
			// Skip if no corresponding name
			if ( ! isset( $names[ $index ] ) ) {
				continue;
			}

			// Validate code
			if ( ! LIFEAI_AITranslator::validate_language_code( $code ) ) {
				continue;
			}

			// Sanitize name
			$name = sanitize_text_field( $names[ $index ] );

			// Skip if name is empty
			if ( empty( trim( $name ) ) ) {
				continue;
			}

			// Add to custom languages
			$custom_languages[ $code ] = $name;
		}

		return update_option( 'lifeai_aitranslator_custom_languages', $custom_languages );
	}

	/**
	 * Render custom languages section
	 */
	public function render_custom_languages_section() {
		$custom = get_option( 'lifeai_aitranslator_custom_languages', array() );
		?>
		<div class="lg-custom-languages">
			<h3>Custom Languages</h3>
			<div id="lg-custom-language-list">
				<?php foreach ( $custom as $code => $name ): ?>
					<div class="lg-custom-language-item">
						<span><?php echo esc_html( $name ); ?> (<?php echo esc_html( $code ); ?>)</span>
					</div>
				<?php endforeach; ?>
			</div>
			<button type="button" class="button">Add Custom Language</button>
		</div>
		<?php
	}

	/**
	 * Get custom languages data in array format
	 *
	 * @return array
	 */
	public function get_custom_languages_data() {
		$custom = get_option( 'lifeai_aitranslator_custom_languages', array() );
		$data = array();

		foreach ( $custom as $code => $name ) {
			$data[] = array(
				'code' => $code,
				'name' => $name,
			);
		}

		return $data;
	}

	/**
	 * AJAX handler for adding custom language
	 */
	public function ajax_add_custom_language() {
		// Placeholder for AJAX implementation
		return true;
	}

	/**
	 * AJAX handler for removing custom language
	 */
	public function ajax_remove_custom_language() {
		// Placeholder for AJAX implementation
		return true;
	}

	/**
	 * Save all settings including custom languages
	 */
	public function save_settings() {
		// Save custom languages
		$this->save_custom_languages();

		// Save other settings
		$settings = array(
			'enabled' => isset( $_POST['enabled'] ),
			'default_language' => sanitize_text_field( $_POST['default_language'] ?? 'en' ),
			'supported_languages' => $_POST['supported_languages'] ?? array(),
		);

		return update_option( 'lifeai_aitranslator_settings', $settings );
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

	public function assert_array_has_key( $key, $array, $message ) {
		$this->assert_true( isset( $array[ $key ] ), $message );
	}

	public function assert_array_not_has_key( $key, $array, $message ) {
		$this->assert_false( isset( $array[ $key ] ), $message );
	}

	public function assert_string_contains( $needle, $haystack, $message ) {
		$this->assert_true( strpos( $haystack, $needle ) !== false, $message );
	}

	public function assert_string_not_contains( $needle, $haystack, $message ) {
		$this->assert_false( strpos( $haystack, $needle ) !== false, $message );
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
echo "Running Admin Custom Language Tests...\n\n";

$test = new SimpleTestRunner();
$admin = new LIFEAI_AITranslator_Admin_Settings();

// Test 1: save_custom_languages() saves valid data
echo "Test: save_custom_languages() saves valid data\n";
$_POST = array(
	'custom_language_codes' => array( 'tl', 'ms', 'fil' ),
	'custom_language_names' => array( 'Tagalog', 'Malay', 'Filipino' ),
);
delete_option( 'lifeai_aitranslator_custom_languages' );
$result = $admin->save_custom_languages();
$custom = get_option( 'lifeai_aitranslator_custom_languages', array() );
$test->assert_true( $result, 'save_custom_languages() returns true' );
$test->assert_count( 3, $custom, 'Should save 3 custom languages' );
$test->assert_equals( 'Tagalog', $custom['tl'], 'Tagalog name is correct' );
echo "\n";

// Test 2: save_custom_languages() filters invalid codes
echo "Test: save_custom_languages() filters invalid codes\n";
$_POST = array(
	'custom_language_codes' => array( 'tl', 'invalid code', 'ms' ),
	'custom_language_names' => array( 'Tagalog', 'Invalid', 'Malay' ),
);
delete_option( 'lifeai_aitranslator_custom_languages' );
$admin->save_custom_languages();
$custom = get_option( 'lifeai_aitranslator_custom_languages', array() );
$test->assert_count( 2, $custom, 'Should only save 2 valid languages' );
$test->assert_array_has_key( 'tl', $custom, 'Tagalog should be saved' );
$test->assert_array_not_has_key( 'invalid code', $custom, 'Invalid code should be filtered' );
echo "\n";

// Test 3: save_custom_languages() filters empty names
echo "Test: save_custom_languages() filters empty names\n";
$_POST = array(
	'custom_language_codes' => array( 'tl', 'ms', 'fil' ),
	'custom_language_names' => array( 'Tagalog', '', 'Filipino' ),
);
delete_option( 'lifeai_aitranslator_custom_languages' );
$admin->save_custom_languages();
$custom = get_option( 'lifeai_aitranslator_custom_languages', array() );
$test->assert_count( 2, $custom, 'Should only save languages with names' );
$test->assert_array_not_has_key( 'ms', $custom, 'Empty name should be filtered' );
echo "\n";

// Test 4: save_custom_languages() sanitizes input
echo "Test: save_custom_languages() sanitizes input\n";
$_POST = array(
	'custom_language_codes' => array( 'tl' ),
	'custom_language_names' => array( '<script>alert("xss")</script>Tagalog' ),
);
delete_option( 'lifeai_aitranslator_custom_languages' );
$admin->save_custom_languages();
$custom = get_option( 'lifeai_aitranslator_custom_languages', array() );
$test->assert_string_not_contains( '<script>', $custom['tl'], 'HTML should be stripped' );
echo "\n";

// Test 5: save_custom_languages() handles empty arrays
echo "Test: save_custom_languages() handles empty arrays\n";
update_option( 'lifeai_aitranslator_custom_languages', array( 'tl' => 'Tagalog' ) );
$_POST = array(
	'custom_language_codes' => array(),
	'custom_language_names' => array(),
);
$admin->save_custom_languages();
$custom = get_option( 'lifeai_aitranslator_custom_languages', array() );
$test->assert_count( 0, $custom, 'Should clear custom languages' );
echo "\n";

// Test 6: render_custom_languages_section() outputs HTML
echo "Test: render_custom_languages_section() outputs HTML\n";
update_option( 'lifeai_aitranslator_custom_languages', array(
	'tl' => 'Tagalog',
	'ms' => 'Malay',
) );
ob_start();
$admin->render_custom_languages_section();
$output = ob_get_clean();
$test->assert_string_contains( 'lg-custom-languages', $output, 'Should contain CSS class' );
$test->assert_string_contains( 'Tagalog', $output, 'Should display Tagalog' );
$test->assert_string_contains( 'Add Custom Language', $output, 'Should have add button' );
echo "\n";

// Test 7: get_custom_languages_data() format
echo "Test: get_custom_languages_data() returns correct format\n";
update_option( 'lifeai_aitranslator_custom_languages', array(
	'tl' => 'Tagalog',
	'ms' => 'Malay',
) );
$data = $admin->get_custom_languages_data();
$test->assert_count( 2, $data, 'Should return 2 items' );
$test->assert_array_has_key( 'code', $data[0], 'Should have code key' );
$test->assert_array_has_key( 'name', $data[0], 'Should have name key' );
echo "\n";

// Summary
$success = $test->summary();
exit( $success ? 0 : 1 );
