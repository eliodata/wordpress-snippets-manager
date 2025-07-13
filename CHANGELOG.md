# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.2.0] - 2024-12-19

### Added
- **🌐 Multi-Site Management**: Complete support for managing multiple WordPress sites from a single interface
  - Configure and store multiple WordPress site connections
  - Quick switching between sites with visual indicators (🟢 for active, ⚪ for inactive)
  - Custom naming for connections ("Production", "Staging", "Client Site", etc.)
  - Secure storage of all connection data in VS Code's secret storage
- **New Commands**:
  - `WordPress Snippets: Manage Connections` - Full connection management interface
  - `WordPress Snippets: Switch Connection` - Quick connection switching
- **Enhanced UI**:
  - 🖥️ Connection manager icon in toolbar
  - 🌍 Quick connection switcher icon in toolbar
  - Visual connection status indicators
- **Backward Compatibility**: Existing single-site configurations are automatically migrated to the new multi-site system

### Changed
- **ConfigManager**: Extended to support multiple connections while maintaining backward compatibility
- **Extension Activation**: Now checks for existing connections and provides appropriate setup flow
- **Connection Storage**: Moved to more robust multi-site configuration structure

### Fixed
- **TypeScript Compilation**: Resolved compilation issues with updated architecture
- **FluentSnippets Toggle**: Fixed active/inactive status inconsistency and toggle functionality
- **FluentSnippets Status Detection**: Improved status parsing to handle various status values ('published', 'active', '1', etc.)
- **FluentSnippets API Integration**: Added dedicated toggle endpoint for proper status management
- **WordPress Plugin API**: Added missing `/fluent-snippets/{id}/toggle` endpoint
- **Code Snippets Toggle**: Fixed toggle functionality for Code Snippets by correcting table name usage
- **FluentSnippets Status Display**: Implemented proper active/inactive detection using file system location

## [2.1.0] - 2024-07-13

### Added
- **FluentSnippets Support**: Full integration with FluentSnippets plugin alongside existing Code Snippets support
- **Multi-Plugin Architecture**: Seamless switching between Code Snippets and FluentSnippets providers
- **Enhanced Search Functionality**: 
  - Search by ID now supports both numeric IDs (e.g., "2") and prefixed IDs (e.g., "FS2")
  - Intelligent ID resolution for FluentSnippets with "FS" prefix system
- **Improved Name Display**: FluentSnippets now display correct names extracted from Internal Doc sections
- **Conflict Resolution**: Implemented ID prefixing system to prevent conflicts between different snippet plugins

### Changed
- **Repository URL**: Updated from trae-ai to eliodata organization
- **Snippet Provider Factory**: Refactored to support multiple snippet plugin providers
- **Cache System**: Enhanced to handle mixed ID types (string/number) for different providers
- **Search Algorithm**: Improved to handle both numeric and prefixed ID searches

### Fixed
- **FluentSnippets Name Extraction**: Corrected display of snippet names instead of descriptions
- **ID Search Issues**: Resolved search functionality for FluentSnippets with FS-prefixed IDs
- **Provider Switching**: Fixed seamless switching between different snippet plugin providers

## [2.0.0] - 2024-07-XX

### Added
- **Multi-Provider Support**: Foundation for supporting multiple WordPress snippet plugins
- **Enhanced UI**: Improved tree view with better filtering and sorting options
- **Provider Switching**: Added ability to switch between different snippet plugin providers

## [1.2.0] - 2024-01-XX

### Changed
- Updated extension documentation to reflect new WordPress plugin naming (IDE Code Snippets Bridge)
- Improved compatibility descriptions for broader IDE support
- Enhanced installation instructions in README
- Updated repository references throughout documentation
- Aligned extension version with WordPress plugin version

### Fixed
- Corrected plugin folder name references in installation guide
- Updated plugin name references from "Trae Snippet Connector" to "IDE Code Snippets Bridge"

## [1.1.0] - 2024-01-XX

### Added
- WordPress plugin rebranded to "IDE Code Snippets Bridge"
- Enhanced security and error handling
- Improved API documentation
- WordPress 6.4 compatibility
- Comprehensive PHPDoc comments
- Visual assets integration

### Changed
- Plugin metadata optimized for WordPress.org submission
- Broader compatibility messaging for various IDE extensions
- Updated screenshots and descriptions

## [1.0.0] - 2024-01-XX

### Added
- Initial release
- Basic CRUD operations for WordPress snippets
- WordPress authentication integration
- Secure REST API endpoints
- Code Snippets plugin integration
- AI-powered snippet management capabilities
- Multi-model AI support (GPT-4, Claude, Gemini)
- Automatic backup and restore functionality
- Real-time synchronization between IDE and WordPress