# WP News Source

Smart news source management with AI-powered detection for automated categorization and tagging. Perfect for n8n integration and automated content workflows.

## Features

- **🤖 AI-Powered Detection**: Intelligent source detection using context analysis, keywords, and custom rules
- **⚡ High Performance**: AJAX autocomplete for categories/tags, optimized for sites with thousands of entries
- **🔗 n8n Integration**: Complete REST API with webhook support for automated workflows
- **📊 Statistics & Analytics**: Track detection performance and source statistics
- **🔄 Import/Export**: Share configurations between sites
- **🎯 Custom Detection Rules**: JSON-based advanced detection patterns
- **🔐 API Security**: Optional API key protection for production environments

## Installation

1. Download the plugin zip file
2. Upload to `/wp-content/plugins/` directory
3. Activate through WordPress admin
4. Go to 'News Sources' in admin menu
5. Configure your first source

## API Endpoints

All endpoints are available under `/wp-json/wp-news-source/v1/`:

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/sources` | GET | List all sources |
| `/mapping` | GET | Complete mapping for n8n |
| `/detect` | POST | AI-powered source detection |
| `/validate` | POST | Validate content against rules |
| `/history` | GET | Detection history |
| `/stats` | GET | Statistics dashboard |
| `/categories/search` | GET | Search categories (autocomplete) |
| `/tags/search` | GET | Search tags (autocomplete) |
| `/export` | GET | Export configuration |
| `/import` | POST | Import configuration |

## n8n Integration Example

```javascript
// Detect source from content
const response = await fetch('/wp-json/wp-news-source/v1/detect', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'X-API-Key': 'your-api-key' // if enabled
  },
  body: JSON.stringify({
    content: 'Your news content here',
    title: 'Optional title for better detection',
    use_ai: true
  })
});

const result = await response.json();
if (result.detected) {
  console.log('Source:', result.source.name);
  console.log('Category:', result.source.category.name);
  console.log('Confidence:', result.confidence);
}
```

## Detection System

The AI detection system uses weighted scoring:

1. **Exact name matching** (weight: 50)
2. **Keyword matching** (weight: 20 per keyword)
3. **Context analysis** (weight: 15 per phrase)
4. **Custom JSON rules** (configurable weights)

### Custom Detection Rules

Create advanced detection patterns using JSON:

```json
[
  {"type": "contains", "value": "government", "weight": 30},
  {"type": "regex", "value": "\\b(minister|secretary)\\b", "weight": 25},
  {"type": "word_count_min", "value": 100, "weight": 10}
]
```

## Performance Optimizations

- AJAX autocomplete for categories/tags (no more loading thousands at once)
- Debounced search (300ms delay)
- Pagination and limits on API endpoints
- Efficient database queries with proper indexing

## Requirements

- WordPress 5.0+
- PHP 7.2+
- MySQL 5.7+ or MariaDB 10.2+

## Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## Support

- [GitHub Issues](https://github.com/zapitz/wp-news-source/issues)
- [Documentation](https://github.com/zapitz/wp-news-source/wiki)


## Changelog

### v1.1.0
- Added AI-powered detection with context analysis
- Implemented AJAX autocomplete for performance
- Added webhook support for real-time notifications
- Included import/export functionality
- Added detection history and statistics
- Performance optimizations for large datasets

### v1.0.0
- Initial release
- Basic source management
- Simple detection system
- REST API endpoints