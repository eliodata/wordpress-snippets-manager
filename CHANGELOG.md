# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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