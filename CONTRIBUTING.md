# Contributing to Lifegence AITranslator

Thank you for considering contributing to Lifegence AITranslator! We appreciate your interest in helping make this WordPress translation plugin better.

## Table of Contents

- [Code of Conduct](#code-of-conduct)
- [How Can I Contribute?](#how-can-i-contribute)
- [Development Setup](#development-setup)
- [Coding Standards](#coding-standards)
- [Pull Request Process](#pull-request-process)
- [Issue Reporting](#issue-reporting)

## Code of Conduct

This project and everyone participating in it is governed by our commitment to:

- Be respectful and inclusive
- Welcome newcomers and help them learn
- Focus on what is best for the community
- Show empathy towards other community members

## How Can I Contribute?

### Reporting Bugs

Before creating bug reports, please check existing issues to avoid duplicates. When creating a bug report, include:

- **Clear title and description**
- **Steps to reproduce** the issue
- **Expected behavior** vs actual behavior
- **WordPress version**, PHP version, and plugin version
- **Error messages** or screenshots if applicable
- **Browser and device** information (for frontend issues)

### Suggesting Enhancements

Enhancement suggestions are welcome! Please include:

- **Clear use case** for the feature
- **Expected behavior** of the feature
- **Alternatives considered**
- **Potential implementation approach** (if you have ideas)

### Pull Requests

We actively welcome your pull requests for:

- Bug fixes
- New features
- Documentation improvements
- Code quality improvements
- Translation additions

## Development Setup

### Prerequisites

- WordPress 5.0+ development environment
- PHP 7.4 or higher
- Node.js and npm (for asset building, if needed)
- Git
- Code editor (VS Code, PHPStorm, etc.)

### Local Setup

1. **Clone the repository**
   ```bash
   git clone https://github.com/lifegence/wp-plugin-lifegence-aitranslator.git
   cd wp-plugin-lifegence-aitranslator
   ```

2. **Set up WordPress development environment**
   - Use Local, XAMPP, Docker, or your preferred setup
   - Symlink the plugin to your WordPress plugins directory:
     ```bash
     ln -s /path/to/wp-plugin-lifegence-aitranslator /path/to/wordpress/wp-content/plugins/lifegence-aitranslator
     ```

3. **Get API keys for testing**
   - Gemini API: https://aistudio.google.com/app/apikey
   - OpenAI API: https://platform.openai.com/api-keys

4. **Activate the plugin** in WordPress admin

### Building the Plugin

```bash
# Create distributable ZIP
./create-plugin-zip.sh

# The output will be lg-aitranslator.zip
```

## Coding Standards

### PHP Standards

We follow [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/):

- **Indentation**: Tabs, not spaces
- **Naming**: Snake_case for functions, PascalCase for classes
- **Documentation**: PHPDoc blocks for all functions and classes
- **Security**: Sanitize inputs, escape outputs, use nonces

### Example

```php
<?php
/**
 * Translate text using AI service.
 *
 * @param string $text        Text to translate.
 * @param string $source_lang Source language code.
 * @param string $target_lang Target language code.
 * @return string Translated text.
 */
function lg_aitrans_translate_text( $text, $source_lang, $target_lang ) {
    // Sanitize inputs
    $text = sanitize_text_field( $text );
    $source_lang = sanitize_text_field( $source_lang );
    $target_lang = sanitize_text_field( $target_lang );

    // Implementation here

    return esc_html( $translated_text );
}
```

### JavaScript Standards

- **ES6+** syntax preferred
- **Semicolons** required
- **Single quotes** for strings
- **2 spaces** for indentation
- **JSDoc** comments for functions

### CSS Standards

- **BEM methodology** for class naming
- **Mobile-first** approach
- **Logical grouping** of properties
- **Comments** for complex sections

## Testing

### Manual Testing

1. Test all features in the admin interface
2. Verify translation accuracy for multiple languages
3. Check caching functionality
4. Test with both Gemini and OpenAI providers
5. Verify language switcher widget display
6. Test REST API endpoints

### PHP Syntax Check

```bash
find lifegence-aitranslator -name "*.php" -exec php -l {} \;
```

### WordPress Coding Standards

```bash
# Install PHP_CodeSniffer and WordPress standards
composer global require "squizlabs/php_codesniffer=*"
composer global require wp-coding-standards/wpcs

# Run check
phpcs --standard=WordPress lifegence-aitranslator/
```

## Pull Request Process

### Before Submitting

1. **Create a feature branch** from `main`
   ```bash
   git checkout -b feature/your-feature-name
   ```

2. **Make your changes** following coding standards

3. **Test thoroughly**
   - Manual testing
   - PHP syntax check
   - Coding standards check

4. **Commit with clear messages**
   ```bash
   git commit -m "Add feature: brief description"
   ```

### Commit Message Format

```
Type: Brief description (50 chars max)

Detailed explanation of changes (if needed)
- Bullet points for multiple changes
- Include motivation and context

Closes #issue-number (if applicable)
```

**Types**: `Fix`, `Feature`, `Docs`, `Style`, `Refactor`, `Test`, `Chore`

### Submitting the Pull Request

1. **Push to your fork**
   ```bash
   git push origin feature/your-feature-name
   ```

2. **Create Pull Request** on GitHub
   - Use a clear, descriptive title
   - Reference related issues
   - Describe what changed and why
   - Include screenshots for UI changes
   - List testing performed

3. **Respond to feedback**
   - Address review comments promptly
   - Make requested changes
   - Keep discussion professional and constructive

### PR Review Process

- Maintainers will review within 1-2 weeks
- Automated checks must pass
- At least one maintainer approval required
- Changes may be requested
- Once approved, maintainers will merge

## Issue Reporting

### Bug Report Template

```markdown
**Description**
Clear description of the bug

**Steps to Reproduce**
1. Go to '...'
2. Click on '...'
3. Scroll down to '...'
4. See error

**Expected Behavior**
What should happen

**Actual Behavior**
What actually happens

**Environment**
- WordPress version:
- PHP version:
- Plugin version:
- Browser (if frontend):
- Other plugins:

**Screenshots**
If applicable

**Additional Context**
Any other relevant information
```

### Feature Request Template

```markdown
**Feature Description**
Clear description of the proposed feature

**Use Case**
Who would benefit and how

**Proposed Solution**
How you envision it working

**Alternatives Considered**
Other approaches you've thought about

**Additional Context**
Mockups, examples, or references
```

## Development Guidelines

### Security Best Practices

- **Sanitize all inputs**: Use `sanitize_text_field()`, `sanitize_email()`, etc.
- **Escape all outputs**: Use `esc_html()`, `esc_attr()`, `esc_url()`, etc.
- **Use nonces**: For all form submissions and AJAX calls
- **Check capabilities**: Verify user permissions with `current_user_can()`
- **Prepare SQL queries**: Use `$wpdb->prepare()` for database queries
- **Validate API keys**: Never expose keys in frontend code

### Performance Considerations

- **Minimize database queries**: Use caching and batch operations
- **Optimize caching**: Leverage transients or Redis
- **Lazy load when possible**: Don't load assets unnecessarily
- **Consider mobile users**: Optimize for slower connections
- **Monitor API usage**: Respect rate limits

### Documentation

- **PHPDoc blocks**: For all public functions and classes
- **Inline comments**: For complex logic
- **README updates**: Document new features
- **Code examples**: Provide usage examples for new APIs

## Questions?

- **GitHub Discussions**: For general questions and ideas
- **GitHub Issues**: For specific bugs or feature requests
- **Email**: For private or security-related concerns

## License

By contributing, you agree that your contributions will be licensed under the GPL v2 or later license.

---

Thank you for contributing to Lifegence AITranslator!
