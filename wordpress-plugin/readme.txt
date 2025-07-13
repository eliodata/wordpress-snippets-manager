=== IDE Code Snippets Bridge ===
Contributors: idesnippets, eliodata
Donate link: https://eliodata.com/donate
Tags: snippets, code, ide, api, development
Requires at least: 5.0
Tested up to: 6.8
Stable tag: 1.3.0
Requires PHP: 7.4
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Bridge plugin that connects your WordPress site with IDE extensions (like Trae AI, VS Code) for seamless AI-powered code snippet management.

== Description ==

**Transform your WordPress development workflow with AI-powered snippet management directly from your IDE.**

IDE Code Snippets Bridge serves as the essential connection between your WordPress site and compatible IDE extensions, enabling seamless code snippet management with cutting-edge artificial intelligence assistance.

= 🚀 What are compatible IDEs? =

This plugin works with various IDE extensions that bring artificial intelligence directly to your WordPress development workflow. For example, Trae AI supports multiple AI models (GPT-4, Claude, Gemini) and allows you to:

* **Edit snippets with natural language**: Simply describe what you want to change
* **Create complex functionality**: From simple design tweaks to advanced logic
* **Manage snippets effortlessly**: List, view, edit, and organize all your snippets
* **Sync in real-time**: Changes are instantly reflected on your WordPress site
* **Automatic backups**: Every modification is safely backed up with easy restoration

= 🔧 How It Works =

This plugin creates a secure REST API that allows compatible IDE extensions to:

1. **Retrieve** all your existing code snippets
2. **Create** new snippets directly from your IDE
3. **Update** snippet content with AI-powered modifications
4. **Delete** snippets you no longer need
5. **Toggle** snippet activation status

= 📋 Requirements =

* **WordPress**: 5.0 or higher
* **PHP**: 7.4 or higher
* **Snippet Management Plugin**: Either Code Snippets or FluentSnippets plugin
* **Administrator Access**: Required for API authentication

= 🔒 Security Features =

* **WordPress Authentication**: Uses built-in WordPress user authentication
* **Administrator Only**: Requires `manage_options` capability
* **Secure API**: All endpoints are properly sanitized and validated
* **No External Dependencies**: Works entirely within WordPress security framework

= 🤝 Compatibility =

* **Code Snippets Plugin**: Full compatibility with all versions
* **FluentSnippets Plugin**: Full compatibility with advanced snippet management features
* **WordPress Multisite**: Supported
* **Popular Themes**: Works with any WordPress theme
* **Other Plugins**: No known conflicts

== Installation ==

= Automatic Installation =

1. Log in to your WordPress admin dashboard
2. Go to **Plugins > Add New**
3. Search for "IDE Code Snippets Bridge"
4. Click **Install Now** and then **Activate**

= Manual Installation =

1. Download the plugin zip file
2. Go to **Plugins > Add New > Upload Plugin**
3. Choose the downloaded zip file and click **Install Now**
4. Activate the plugin

= Setup =

1. **Install Code Snippets Plugin**: This plugin requires the [Code Snippets](https://wordpress.org/plugins/code-snippets/) plugin
2. **Activate Both Plugins**: Once both are active, the API endpoints are automatically available
3. **Install Compatible IDE Extension**: Get extensions like Trae AI from VS Code marketplace or other compatible IDEs
4. **Configure Connection**: Enter your WordPress site URL in the extension settings

== Frequently Asked Questions ==

= Do I need the Code Snippets plugin? =

You need either the Code Snippets plugin OR the FluentSnippets plugin to manage your snippets. This bridge plugin supports both:

* **Code Snippets**: The most popular WordPress snippet plugin with over 1 million active installations
* **FluentSnippets**: Advanced snippet management with enhanced organization features

You only need one of these plugins installed and activated.

= Is this plugin secure? =

Absolutely. The plugin uses WordPress's built-in authentication system and requires administrator privileges (`manage_options` capability) to access any API endpoints.

= Does this work with any IDE? =

This plugin is designed to work with various IDE extensions that support the REST API endpoints. Currently tested with Trae AI for VS Code and other compatible IDE extensions.

= Can I use this without a compatible IDE extension? =

While the plugin provides REST API endpoints that could theoretically be used by other applications, it's specifically designed and optimized for use with compatible IDE extensions like Trae AI.

= Does this affect my site's performance? =

No, the plugin is lightweight and only activates when API calls are made from the IDE extension. It has no impact on your site's frontend performance.

= Is this compatible with WordPress Multisite? =

Yes, the plugin is fully compatible with WordPress Multisite installations.

== Screenshots ==

1. IDE extension interface showing AI-powered snippet management in action
2. AI model selection interface with support for GPT-4, Claude, and Gemini
3. Automatic backup and restore functionality with one-click recovery
4. Real-time synchronization between IDE and WordPress
5. Visual previews of code changes with AI assistance

== Changelog ==

= 1.3.0 =
* Added full support for FluentSnippets plugin alongside Code Snippets
* Enhanced API endpoints to handle multiple snippet management plugins
* Improved snippet parsing for FluentSnippets with Internal Doc sections
* Added intelligent ID handling for different snippet plugin formats
* Enhanced search capabilities across multiple snippet providers
* Updated compatibility documentation

= 1.2.0 =
* Updated extension documentation to reflect new plugin naming
* Improved compatibility descriptions for broader IDE support
* Enhanced installation instructions
* Updated repository references

= 1.1.0 =
* Rebranded to IDE Code Snippets Bridge
* Enhanced security and error handling
* Improved API documentation
* WordPress 6.4 compatibility
* Added comprehensive PHPDoc comments
* Improved plugin metadata for WordPress.org

= 1.0.0 =
* Initial release
* Basic CRUD operations for snippets
* WordPress authentication integration
* Secure REST API endpoints
* Code Snippets plugin integration

== Upgrade Notice ==

= 1.1.0 =
Major update with improved security, better documentation, and WordPress 6.4 compatibility. Recommended for all users.

== Support ==

For support, documentation, and feature requests:

* **Documentation**: [Complete setup guide](https://github.com/ide-snippets/wordpress-snippets-manager)
* **Issues**: [Report bugs or request features](https://github.com/ide-snippets/wordpress-snippets-manager/issues)
* **Community**: Join our Discord community for help and discussions

== Privacy Policy ==

This plugin does not collect, store, or transmit any personal data. All snippet management is handled locally within your WordPress installation using the existing Code Snippets plugin database structure.

== Third Party Services ==

This plugin does not connect to any external services. It only provides REST API endpoints for communication with compatible IDE extensions running on your local development environment.