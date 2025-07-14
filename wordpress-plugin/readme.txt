=== IDE Code Snippets Bridge ===
Contributors: idesnippets, eliodata
Donate link: https://eliodata.com/donate
Tags: snippets, code, ide, api, development
Requires at least: 5.0
Tested up to: 6.8
Stable tag: 1.5.1
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

= 1.5.1 =
* CRITICAL API FIX: WordPress API now reads @status from Internal Doc
* Fixed get_fluent_snippets endpoint to extract real status instead of file location
* API now returns accurate active/inactive status based on @status: published/draft
* Eliminates root cause of status synchronization issues
* Perfect alignment between WordPress API and IDE extension achieved

= 1.5.0 =
* DEFINITIVE FIX: Eliminates cache/API conflict completely
* API status from Internal Doc now ALWAYS takes priority over cached status
* Cache automatically updates with fresh API status on every refresh
* Prevents status reverting when toggling between draft/published
* Perfect bidirectional synchronization achieved - no more conflicts
* Status changes from WordPress site instantly reflect in IDE

= 1.4.9 =
* CRITICAL FIX: @active now reads status directly from Internal Doc
* createCacheFile method now extracts real status from @status field in Internal Doc
* Eliminates dependency on snippet.active which was always true
* Ensures @active true/false perfectly matches @status published/draft
* DEFINITIVE solution for persistent @active synchronization issues

= 1.4.8 =
* FINAL REGEX FIX: Enhanced @status detection in Internal Doc comments
* Updated regex pattern to correctly match @status in both /* */ and // comment formats
* Improved synchronization accuracy for FluentSnippets @active/@status fields
* Ensures perfect detection of @status: draft/published in all comment styles
* Complete resolution of persistent @active synchronization issues

= 1.4.7 =
* ULTIMATE FIX: Perfect @active synchronization - @active tag in cache header now reflects the real @status from Internal Doc
* Adopts CodeSnippets structure for consistent behavior across all snippet types
* Ensures @active true/false matches @status published/draft instantly
* Resolves all synchronization conflicts definitively

= 1.4.6 =
* FINAL FIX: Proper Internal Doc preservation - maintains the complete Internal Doc section with @status field while removing conflicting @active tags from headers
* Ensures perfect synchronization between draft/published states and cache files
* Resolves persistent @active true conflicts definitively

= 1.4.5 =
* CRITICAL FIX: Complete header extraction - removes original snippet headers containing @active tags
* Fixed: Cache generation now extracts only clean PHP code without original comment headers
* Enhanced: Smart code extraction that skips original headers and preserves only functional code
* Resolved: Definitive elimination of @active tag persistence from original snippet headers
* Updated: Cache files now contain only clean code with new headers, no legacy @active conflicts

= 1.4.4 =
* CRITICAL FIX: Code cleaning in cache generation to remove @active tags from snippet content
* Fixed: Cache files no longer contain @active true/false tags from original snippet code
* Enhanced: createCacheFile method now strips @active tags before writing to cache
* Resolved: Final elimination of dual @active/@status conflict in cache files
* Updated: Cache regeneration with clean code without legacy @active tags

= 1.4.3 =
* CRITICAL FIX: Eliminated @active tag completely from cache files to prevent status conflicts
* Fixed toggle behavior to delete cache and force regeneration with fresh API data
* Resolved issue where @active true would persist despite @status: draft in Internal Doc
* Improved cache management to rely solely on authoritative @status from FluentSnippets
* Enhanced status synchronization by removing dual-state confusion

= 1.4.2 =
* MAJOR FIX: Corrected status synchronization between IDE extension and FluentSnippets
* Fixed IDE extension to read status from Internal Doc @status: published/draft instead of @active header
* Updated WordPress plugin to regenerate index.php based on actual file status, not directory location
* Eliminated inconsistencies between website status, extension display, and @active tags
* Improved FluentSnippets compatibility by reading authoritative @status field from Internal Doc
* Enhanced status extraction with fallback mechanisms for legacy formats

= 1.4.1 =
* Fixed: Toggle status persistence issue in IDE extension
* Improved: Cache management for snippet status updates
* Fixed: Status reverting to previous state after toggle
* Enhanced: Prioritization of cached status over API responses
* Fixed: Race condition between API updates and cache refresh

= 1.4.0 =
* Synchronized @active tag with FluentSnippets @status (published/draft)
* Corrected toggle state reflection in the IDE extension
* Ensured @active true/false is updated alongside @status
* Improved status synchronization between the plugin and the IDE
* Fixed issue where IDE status was not reflecting the actual snippet state

= 1.3.9 =
* Corrected FluentSnippets status toggle to prevent file moving
* Aligned snippet activation/deactivation with native FluentSnippets behavior
* Removed file system operations for status changes, now only updates metadata
* Added fallback to move snippets from legacy 'disabled' directory
* Ensured toggling status only changes 'published'/'draft' in file headers

= 1.3.6 =
* Fixed PHP parse errors in FluentSnippets with malformed comment blocks
* Corrected orphaned PHP comment syntax (e.g., "* @status published")
* Enhanced snippet validation and error detection
* Improved automatic snippet correction for syntax issues

= 1.3.5 =
* Added automatic HTML snippet correction during toggle operations
* Implemented intelligent detection of full HTML structure in snippets
* Enhanced snippet processing to prevent header conflicts automatically
* Improved snippet content parsing and reconstruction using WordPress hooks

= 1.3.4 =
* Fixed PHP syntax error in generated index.php file (variable escaping issue)
* Corrected output buffering code generation to prevent parse errors
* Enhanced variable handling in dynamically generated PHP code

= 1.3.3 =
* Added automatic output buffering protection for all FluentSnippets to prevent header conflicts
* Enhanced index.php regeneration with built-in header safety mechanisms
* Improved handling of snippets containing complete HTML documents or immediate output
* Added intelligent content filtering to prevent DOCTYPE and HTML tag conflicts

= 1.3.2 =
* Fixed "Cannot modify header information" error when toggling FluentSnippets status
* Improved snippet output handling to use WordPress hooks instead of direct HTML output
* Enhanced compatibility with snippets containing full HTML documents
* Fixed header conflicts by properly using wp_head and wp_footer actions

= 1.3.1 =
* Fixed critical issue with FluentSnippets index.php file deletion during snippet toggle
* Improved FluentSnippets compatibility with proper draft/published status handling
* Enhanced snippet deactivation to maintain visibility in FluentSnippets interface
* Added automatic index.php regeneration with correct Fluent Snippets data structure
* Improved error handling and debug logging for FluentSnippets operations

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