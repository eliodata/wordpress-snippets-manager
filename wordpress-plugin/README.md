# IDE Code Snippets Bridge

*Bridge your WordPress development with AI-powered snippet management*

This WordPress plugin serves as the essential bridge between your WordPress site and IDE extensions (like [Trae AI](https://github.com/trae-ai/wordpress-snippets-manager) or VS Code extensions), enabling seamless code snippet management with cutting-edge AI assistance.

## 🚀 What are compatible IDEs?

This plugin works with various IDE extensions that bring artificial intelligence directly to your WordPress development workflow. For example, Trae AI supports multiple AI models (GPT-4, Claude, Gemini) and allows you to:

- **Edit snippets with natural language**: Simply describe what you want to change
- **Create complex functionality**: From simple design tweaks to advanced logic
- **Manage snippets effortlessly**: List, view, edit, and organize all your snippets
- **Sync in real-time**: Changes are instantly reflected on your WordPress site
- **Automatic backups**: Every modification is safely backed up with easy restoration

## 🔧 How It Works

This plugin creates a secure REST API that allows compatible IDE extensions to:

1. **Retrieve** all your existing code snippets
2. **Create** new snippets directly from your IDE
3. **Update** snippet content with AI-powered modifications
4. **Delete** snippets you no longer need
5. **Toggle** snippet activation status

## 📋 Requirements

- **WordPress**: 5.0 or higher
- **PHP**: 7.4 or higher
- **Code Snippets Plugin**: Required for snippet management
- **Administrator Access**: Required for API authentication

## 🛠️ Installation

### Method 1: WordPress Admin (Recommended)

1. Download the latest release from the [WordPress Plugin Directory](https://wordpress.org/plugins/ide-snippets-bridge/)
2. In your WordPress admin, go to **Plugins > Add New**
3. Click **Upload Plugin** and select the downloaded zip file
4. Click **Install Now** and then **Activate**

### Method 2: Manual Installation

1. Download and extract the plugin files
2. Upload the `ide-snippets-bridge` folder to `/wp-content/plugins/`
3. Activate the plugin through the WordPress admin

## ⚙️ Configuration

### WordPress Setup

1. **Install Code Snippets Plugin**: This plugin requires the popular [Code Snippets](https://wordpress.org/plugins/code-snippets/) plugin to manage your snippets
2. **Activate the Bridge**: Once both plugins are active, the API endpoints are automatically available
3. **No additional configuration needed**: The plugin works out of the box

### IDE Extension Setup

1. **Install Compatible IDE Extension**: Get extensions like Trae AI from the VS Code marketplace or other compatible IDEs
2. **Configure Connection**: Enter your WordPress site URL in the extension settings
3. **Authenticate**: The extension will use your WordPress admin credentials
4. **Start Creating**: Begin managing your snippets with AI assistance

## 🔒 Security Features

- **WordPress Authentication**: Uses built-in WordPress user authentication
- **Administrator Only**: Requires `manage_options` capability
- **Secure API**: All endpoints are properly sanitized and validated
- **No External Dependencies**: Works entirely within WordPress security framework

## 🔌 API Endpoints

The plugin provides the following REST API endpoints:

- `GET /wp-json/ide/v1/snippets` - List all snippets
- `POST /wp-json/ide/v1/snippets` - Create new snippet
- `GET /wp-json/ide/v1/snippets/{id}` - Get specific snippet
- `PUT /wp-json/ide/v1/snippets/{id}` - Update snippet
- `DELETE /wp-json/ide/v1/snippets/{id}` - Delete snippet

## 🤝 Compatibility

- **Code Snippets Plugin**: Full compatibility with all versions
- **WordPress Multisite**: Supported
- **Popular Themes**: Works with any WordPress theme
- **Other Plugins**: No known conflicts

## 🆘 Support

- **Documentation**: [Complete setup guide](https://github.com/ide-snippets/wordpress-snippets-manager)
- **Issues**: [Report bugs or request features](https://github.com/ide-snippets/wordpress-snippets-manager/issues)
- **Community**: Join our [Discord community](https://discord.gg/ide-snippets)

## 🔄 Changelog

### 1.1.0
- Rebranded to IDE Code Snippets Bridge
- Enhanced security and error handling
- Improved API documentation
- WordPress 6.4 compatibility

### 1.0.0
- Initial release
- Basic CRUD operations for snippets
- WordPress authentication integration

## 👥 Contributing

We welcome contributions! Please see our [Contributing Guidelines](https://github.com/ide-snippets/wordpress-snippets-manager/blob/main/CONTRIBUTING.md) for details.

## 📄 License

This plugin is licensed under the GPL v3 or later. See [LICENSE](LICENSE) for details.

---

**Made with ❤️ by [IDE Snippets](https://eliodata.com)**

*Supercharge your WordPress development with AI-powered snippet management.*