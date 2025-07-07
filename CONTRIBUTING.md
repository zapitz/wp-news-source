# Contributing to WP News Source

Thank you for your interest in contributing to WP News Source! This document provides guidelines and information for contributors.

## How to Contribute

### Reporting Issues

1. **Search existing issues** first to avoid duplicates
2. **Use the issue template** when creating new issues
3. **Provide detailed information**:
   - WordPress version
   - PHP version
   - Plugin version
   - Steps to reproduce
   - Expected vs actual behavior
   - Screenshots if applicable

### Suggesting Features

1. **Check the roadmap** in issues labeled `enhancement`
2. **Open a feature request** with:
   - Clear description of the feature
   - Use case and benefits
   - Possible implementation approach
   - Any relevant examples or mockups

### Code Contributions

#### Development Setup

1. **Fork the repository**
2. **Clone your fork**:
   ```bash
   git clone https://github.com/your-username/wp-news-source.git
   cd wp-news-source
   ```

3. **Set up WordPress development environment**:
   - Use Local, DevKinsta, or similar
   - Place plugin in `/wp-content/plugins/wp-news-source`
   - Activate the plugin

#### Coding Standards

- **Follow WordPress coding standards**
- **Use proper sanitization and validation**
- **Include security checks (nonces, permissions)**
- **Write clear, documented code**
- **Use meaningful variable and function names**

#### File Structure

```
wp-news-source/
├── wp-news-source.php          # Main plugin file
├── includes/                   # Core plugin classes
├── admin/                      # Admin interface
│   ├── css/                    # Admin styles
│   ├── js/                     # Admin JavaScript
│   └── partials/               # Admin templates
├── database/                   # Database operations
├── languages/                  # Translation files
└── assets/                     # Public assets
```

#### Making Changes

1. **Create a feature branch**:
   ```bash
   git checkout -b feature/your-feature-name
   ```

2. **Make your changes**:
   - Follow existing code patterns
   - Add comments for complex logic
   - Update relevant documentation

3. **Test your changes**:
   - Test in fresh WordPress installation
   - Test with large datasets (performance)
   - Test API endpoints with tools like Postman
   - Test in different browsers

4. **Commit your changes**:
   ```bash
   git add .
   git commit -m "Add: clear description of changes"
   ```

5. **Push to your fork**:
   ```bash
   git push origin feature/your-feature-name
   ```

6. **Create a Pull Request**

#### Pull Request Guidelines

- **Use clear title and description**
- **Reference related issues** (e.g., "Fixes #123")
- **Include screenshots** for UI changes
- **List breaking changes** if any
- **Update documentation** if needed

## Development Guidelines

### Performance Considerations

- **Avoid loading unnecessary data** (e.g., all categories/tags)
- **Use pagination and limits** for large datasets
- **Implement proper caching** where appropriate
- **Optimize database queries**

### Security Best Practices

- **Sanitize all inputs** using WordPress functions
- **Validate data** before processing
- **Use nonces** for form submissions
- **Check user permissions** before actions
- **Escape output** to prevent XSS

### API Development

- **Follow REST API conventions**
- **Include proper error handling**
- **Validate request parameters**
- **Return consistent response formats**
- **Document all endpoints**

### Internationalization

- **Use WordPress i18n functions** (`__()`, `_e()`, etc.)
- **Create translation strings** for all user-facing text
- **Test with different languages**
- **Update .pot files** when adding strings

## Testing

### Manual Testing Checklist

- [ ] Plugin activation/deactivation
- [ ] Source creation and editing
- [ ] Category/tag autocomplete functionality
- [ ] API endpoints with various parameters
- [ ] Import/export functionality
- [ ] Performance with large datasets
- [ ] Mobile responsiveness
- [ ] Cross-browser compatibility

### Testing API Endpoints

Use tools like Postman or curl to test:

```bash
# Test source detection
curl -X POST "/wp-json/wp-news-source/v1/detect" \
  -H "Content-Type: application/json" \
  -d '{"content": "Test content", "use_ai": true}'

# Test category search
curl "/wp-json/wp-news-source/v1/categories/search?search=news&limit=10"
```

## Documentation

### Code Documentation

- **Use PHPDoc blocks** for classes and methods
- **Comment complex logic** and algorithms
- **Explain "why" not just "what"**
- **Keep comments up to date**

### User Documentation

- **Update README.md** for new features
- **Add API documentation** for new endpoints
- **Include usage examples**
- **Update CLAUDE.md** for development guidance

## Release Process

1. **Update version numbers** in relevant files
2. **Update changelog** in README.md
3. **Test thoroughly** in clean environment
4. **Create release notes**
5. **Tag the release**

## Getting Help

- **Join discussions** in GitHub Issues
- **Ask questions** in pull request comments
- **Review existing code** for patterns and examples

## Recognition

Contributors will be recognized in:
- GitHub contributors list
- Release notes for significant contributions
- Plugin credits (for major features)

Thank you for helping make WP News Source better! 🚀