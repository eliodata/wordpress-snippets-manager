# Contributing to Trae AI - WordPress Snippet Manager

Thank you for your interest in contributing to this project! This guide will help you get started.

## 🚀 Quick Start

### Prerequisites

- Node.js 16+ and npm
- VS Code or compatible IDE
- WordPress development environment
- Git

### Development Setup

1. **Clone the repository**
   ```bash
   git clone https://github.com/ide-snippets/wordpress-snippets-manager.git
   cd wordpress-snippets-manager
   ```

2. **Install dependencies**
   ```bash
   npm install
   ```

3. **Set up WordPress plugin**
   - Navigate to `wordpress-plugin/` directory
   - Compress the folder into `ide-snippets-bridge.zip`
   - Install on your WordPress test site

4. **Build the extension**
   ```bash
   npm run compile
   ```

## 📁 Project Structure

```
├── assets/                 # Extension assets (images, icons)
├── wordpress-plugin/       # WordPress plugin source
│   ├── ide-snippets-bridge.php
│   ├── includes/
│   └── assets/
├── package.json           # Extension metadata
├── tsconfig.json          # TypeScript configuration
└── README.md              # Main documentation
```

## 🔧 Development Workflow

### WordPress Plugin Development

1. Make changes in `wordpress-plugin/` directory
2. Test on local WordPress installation
3. Update version numbers in:
   - `ide-snippets-bridge.php`
   - `readme.txt`
   - `package.json`
4. Update changelog in `readme.txt`

### Extension Development

1. Modify TypeScript source files
2. Run `npm run compile` to build
3. Test in VS Code development environment
4. Update `package.json` version

## 📝 Coding Standards

### WordPress Plugin (PHP)

- Follow [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/)
- Use PHPDoc for all functions and classes
- Sanitize and validate all inputs
- Use WordPress security best practices

### Extension (TypeScript)

- Follow TypeScript best practices
- Use ESLint configuration provided
- Document public APIs
- Handle errors gracefully

## 🧪 Testing

### WordPress Plugin Testing

1. Test with latest WordPress version
2. Verify compatibility with Code Snippets plugin
3. Test all API endpoints
4. Check security permissions

### Extension Testing

1. Test in VS Code development environment
2. Verify API communication
3. Test error handling
4. Check UI responsiveness

## 📋 Pull Request Process

1. **Fork the repository**
2. **Create a feature branch**
   ```bash
   git checkout -b feature/your-feature-name
   ```
3. **Make your changes**
4. **Update documentation** if needed
5. **Test thoroughly**
6. **Commit with clear messages**
   ```bash
   git commit -m "feat: add new feature description"
   ```
7. **Push to your fork**
   ```bash
   git push origin feature/your-feature-name
   ```
8. **Create a Pull Request**

## 🐛 Bug Reports

When reporting bugs, please include:

- WordPress version
- PHP version
- Plugin versions (this plugin + Code Snippets)
- Steps to reproduce
- Expected vs actual behavior
- Error messages or logs

## 💡 Feature Requests

For feature requests, please:

- Check existing issues first
- Describe the use case
- Explain the expected behavior
- Consider implementation complexity

## 📄 License

By contributing, you agree that your contributions will be licensed under the same license as the project (GPL v3 for WordPress plugin, MIT for extension).

## 🤝 Code of Conduct

Please be respectful and constructive in all interactions. We're building this together!

## 📞 Getting Help

- **Documentation**: Check the README files
- **Issues**: Search existing GitHub issues
- **Discussions**: Use GitHub Discussions for questions
- **Community**: Join our Discord (link in main README)

Thank you for contributing! 🎉