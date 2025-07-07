=== WP News Source ===
Contributors: arielurtaza
Donate link: https://urtaza.com/donate/
Tags: news, sources, automation, n8n, press releases, AI detection, content management
Requires at least: 5.0
Tested up to: 6.4
Stable tag: 1.1.0
Requires PHP: 7.2
License: GPL-2.0+
License URI: http://www.gnu.org/licenses/gpl-2.0.txt

Smart news source management with AI-powered detection for automated categorization and tagging. Perfect for n8n integration.

== Description ==

WP News Source is an advanced WordPress plugin that automates the management of news sources and press releases. With AI-powered detection and comprehensive API integration, it's the perfect solution for media outlets, news agencies, and websites that receive content from multiple sources.

= Key Features =

* **Smart Source Management**: Add and manage news sources (government, companies, NGOs, etc.)
* **AI-Powered Detection**: Automatically detects sources using context, keywords, and custom rules
* **Automatic Mapping**: Assigns categories and tags based on detected source
* **REST API**: Complete API endpoints for n8n and automation tools integration
* **Webhook Support**: Real-time notifications when sources are detected
* **Detection History**: Track all detections with confidence scores
* **Statistics Dashboard**: Monitor source performance and detection rates
* **Import/Export**: Share source configurations between sites
* **Custom Detection Rules**: Create advanced JSON-based detection patterns

= Use Cases =

* News organizations receiving press releases
* Government websites publishing official communications
* Corporate blogs with multiple contributors
* Automated news aggregators
* Content syndication networks
* Multi-author publishing platforms

= API Endpoints =

* `/wp-json/wp-news-source/v1/mapping` - Get complete source mapping
* `/wp-json/wp-news-source/v1/detect` - Detect source from content (AI-powered)
* `/wp-json/wp-news-source/v1/sources` - List all sources
* `/wp-json/wp-news-source/v1/validate` - Validate content before publishing
* `/wp-json/wp-news-source/v1/history` - Get detection history
* `/wp-json/wp-news-source/v1/stats` - View statistics
* `/wp-json/wp-news-source/v1/export` - Export configuration
* `/wp-json/wp-news-source/v1/import` - Import configuration

= n8n Integration =

This plugin is specifically designed to work seamlessly with n8n workflows. Use the API endpoints to:
- Automatically categorize incoming content
- Route content based on source detection
- Trigger different workflows for different sources
- Validate content before publishing
- Get real-time notifications via webhooks

== Installation ==

1. Upload the `wp-news-source` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to 'News Sources' in the admin menu
4. Add your first source with description and keywords
5. Configure API settings if needed
6. Start detecting sources automatically!

== Frequently Asked Questions ==

= How does the AI detection work? =

The plugin uses multiple detection methods:
1. Exact name matching (highest priority)
2. Keyword matching (medium priority)
3. Context analysis from description
4. Custom detection rules (JSON-based)

= Can I use this without n8n? =

Yes! While optimized for n8n, the plugin works with any system that can make HTTP requests to the REST API.

= How do I create custom detection rules? =

In the Advanced Settings, you can add JSON rules like:
`[{"type": "contains", "value": "government", "weight": 30}]`

= Is there a limit to the number of sources? =

No, you can create unlimited sources. Performance remains optimal even with hundreds of sources.

= Can multiple sources share the same category? =

Yes, multiple sources can use the same WordPress category while having different tags.

== Screenshots ==

1. Main dashboard showing all configured sources
2. Add new source form with context description
3. API endpoints for integration
4. Statistics dashboard
5. Detection history log
6. Import/Export functionality

== Changelog ==

= 1.1.0 =
* Added AI-powered detection with context analysis
* New description/context field for better detection
* Keywords field for improved matching
* Detection history tracking
* Statistics dashboard
* Webhook support for real-time notifications
* Import/Export functionality
* Custom detection rules (JSON)
* API key security option
* Performance optimizations

= 1.0.0 =
* Initial release
* Basic source management
* Simple name-based detection
* REST API endpoints
* Category and tag mapping

== Upgrade Notice ==

= 1.1.0 =
Major update! Adds AI detection, context analysis, and many new features. Database will be automatically updated.

= 1.0.0 =
First version of the plugin.

== Developer Information ==

Developed by Ariel Urtaza
Website: https://urtaza.com
Plugin Home: https://github.com/zapitz/wp-news-source

For support, feature requests, or bug reports, please visit:
https://github.com/zapitz/wp-news-source/issues