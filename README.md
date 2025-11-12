# Lifegence AITranslator

[![WordPress Plugin](https://img.shields.io/badge/WordPress-5.0%2B-blue.svg)](https://wordpress.org/)
[![PHP Version](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)](https://www.php.net/)
[![License](https://img.shields.io/badge/License-GPLv2-green.svg)](LICENSE)

AI-powered multilingual translation plugin for WordPress using Google Gemini and OpenAI APIs.

Transform your WordPress site into a multilingual platform with intelligent, context-aware AI translations. Support 20+ languages with smart caching that reduces translation costs by up to 95%.

## Features

### Translation Engine
- **Dual AI Provider Support**: Choose between Google Gemini or OpenAI GPT
- **Context-Aware Translation**: Maintains context and nuance across languages
- **HTML-Safe Translation**: Preserves HTML structure and formatting
- **20+ Languages**: Major world languages including Japanese, Chinese, Spanish, French, and more

### Performance & Cost Optimization
- **Smart Caching System**: Reduces API costs by 80-95%
- **Edit Translations On-Screen**: Click any text to fix AI translations directly
- **Bulk Re-translation**: One-click refresh of all translations
- **Redis Support**: High-performance caching for traffic-intensive sites
- **Rate Limiting**: Prevent excessive API usage
- **Budget Controls**: Set monthly spending limits with auto-disable protection

### User Experience
- **Language Switcher Widget**: Beautiful, customizable language selector
- **Multiple Display Styles**: Dropdown, list, or flag-only display
- **Shortcode Support**: Easy integration with `[lg_language_switcher]`
- **REST API**: Programmatic translation access

### Developer-Friendly
- **REST API Endpoints**: Full translation API access
- **Extensible Architecture**: Hook system for customization
- **PHP Integration**: Direct service access for custom implementations
- **Modern Codebase**: Object-oriented, PSR-compliant code

## Quick Start

### Installation

1. Download the latest release from the [Releases](../../releases) page
2. Upload `lifegence-aitranslator.zip` to WordPress via Plugins → Add New → Upload
3. Activate the plugin
4. Navigate to Settings → Lifegence AITranslator
5. Configure your API key and preferences
6. **Click "Save Settings"** to initialize URL rewriting

### Basic Configuration

1. **Get an API Key**
   - **Gemini (Recommended)**: Visit [Google AI Studio](https://aistudio.google.com/app/apikey)
   - **OpenAI**: Visit [OpenAI Platform](https://platform.openai.com/api-keys)

2. **Configure Plugin**
   - Go to Settings → Lifegence AITranslator
   - Select your AI provider (Gemini or OpenAI)
   - Enter your API key and test connection
   - Select supported languages
   - Enable caching (highly recommended)

3. **Add Language Switcher**
   - Use widget: Appearance → Widgets → "Lifegence Language Switcher"
   - Or use shortcode: `[lg_language_switcher type="dropdown"]`

## Documentation

- **[Installation Guide](docs/user/INSTALLATION.md)** - Detailed setup instructions
- **[User Guide](docs/user/plugin-readme.md)** - Features, usage, and troubleshooting
- **[Developer Documentation](docs/developer/README.md)** - Technical documentation for contributors
- **[API Documentation](docs/)** - Developer integration guides
- **[Contributing](CONTRIBUTING.md)** - How to contribute to this project

## Supported Languages

English • 日本語 • 简体中文 • 繁體中文 • 한국어 • Español • Français • Deutsch • Italiano • Português • Русский • العربية • हिन्दी • ไทย • Tiếng Việt • Bahasa Indonesia • Türkçe • Polski • Nederlands • Svenska

## Cost Estimates

### With 90% Cache Hit Rate (10,000 pageviews/month)

| Provider | Without Cache | With Cache | Savings |
|----------|--------------|------------|---------|
| **Gemini 1.5 Flash** | ~$10/month | ~$1/month | 90% |
| **OpenAI GPT-4o Mini** | ~$20/month | ~$2/month | 90% |

**💡 Pro Tip**: Enable caching and use Gemini for the best cost-to-quality ratio.

## Requirements

- **WordPress**: 5.0 or higher
- **PHP**: 7.4 or higher
- **PHP Extensions**: curl, json, openssl
- **Optional**: Redis server (for high-traffic sites)

## Development

### Project Structure

```
lifegence-aitranslator/
├── lifegence-aitranslator.php       # Main plugin file
├── includes/                         # Core services
│   ├── class-translation-service-*.php
│   ├── class-translation-cache.php
│   └── class-api-key-manager.php
├── admin/                           # Admin interface
│   ├── class-admin-settings.php
│   ├── class-admin-ajax.php
│   └── assets/
└── assets/                          # Frontend assets
    ├── js/frontend.js
    └── css/frontend.css
```

### Building Plugin

```bash
# Create distributable ZIP
./create-plugin-zip.sh

# Check WordPress plugin standards compliance
./fix-plugin-check.sh
```

### Running Tests

```bash
# PHP syntax check
find . -path ./dist -prune -o -name "*.php" -exec php -l {} \;

# WordPress coding standards (requires PHP_CodeSniffer)
phpcs --standard=WordPress --exclude=dist .
```

## Contributing

We welcome contributions! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details on:
- Code of Conduct
- Development workflow
- Coding standards
- Pull request process

## Support

- **Issues**: [GitHub Issues](../../issues)
- **Discussions**: [GitHub Discussions](../../discussions)
- **Documentation**: [Wiki](../../wiki)

## License

This project is licensed under the GPL v2 or later - see the [LICENSE](LICENSE) file for details.

## Credits

**Developed by**: [Lifegence Corporation](https://lifegence.com)

Inspired by GTranslate plugin architecture, rebuilt from the ground up with modern AI translation capabilities.

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for detailed version history.

### Latest Release

#### 1.0.0 (Initial Release)
- Google Gemini integration
- OpenAI GPT integration
- Smart caching system with Redis support
- Language switcher widget
- REST API support
- Admin settings interface
- Cost management and budget controls

---

**Made with ❤️ for the WordPress community**
