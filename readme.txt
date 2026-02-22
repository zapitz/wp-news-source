=== WP News Source ===
Contributors: arielurtaza
Donate link: https://urtaza.com/donate/
Tags: news, sources, automation, n8n, press releases, content management
Requires at least: 5.0
Tested up to: 6.4
Stable tag: 2.5.3
Requires PHP: 7.2
License: GPL-2.0+
License URI: http://www.gnu.org/licenses/gpl-2.0.txt

News source management for automated categorization and tagging. Perfect for n8n integration.

== Description ==

WP News Source is a WordPress plugin that automates the management of news sources and press releases. With comprehensive API integration, it's the perfect solution for media outlets, news agencies, and websites that receive content from multiple sources.

= Key Features =

* **Smart Source Management**: Add and manage news sources (government, companies, NGOs, etc.)
* **Automatic Detection**: Detects sources by name in content
* **Automatic Mapping**: Assigns categories and tags based on detected source
* **AI Prompts System**: Store and manage AI prompts for content processing
* **Post Creation API**: Create WordPress posts directly via API with full control
* **REST API**: Complete API endpoints for n8n and automation tools integration
* **n8n Workflow Examples**: Production-ready workflows included
* **Webhook Support**: Real-time notifications when sources are detected
* **Detection History**: Track all detections with confidence scores
* **Statistics Dashboard**: Monitor source performance and detection rates
* **Import/Export**: Share source configurations between sites

= Use Cases =

* News organizations receiving press releases
* Government websites publishing official communications
* Corporate blogs with multiple contributors
* Automated news aggregators
* Content syndication networks
* Multi-author publishing platforms

= API Endpoints =

* `/wp-json/wp-news-source/v1/create-post` - Create WordPress posts with auto-detection
* `/wp-json/wp-news-source/v1/prompts` - Manage AI prompts (GET/POST)
* `/wp-json/wp-news-source/v1/prompts/{key}` - Get specific prompt
* `/wp-json/wp-news-source/v1/search-posts` - Search posts with advanced filters
* `/wp-json/wp-news-source/v1/mapping` - Get complete source mapping
* `/wp-json/wp-news-source/v1/detect` - Detect source from content
* `/wp-json/wp-news-source/v1/sources` - List all sources
* `/wp-json/wp-news-source/v1/validate` - Validate content before publishing
* `/wp-json/wp-news-source/v1/categories` - Get WordPress categories
* `/wp-json/wp-news-source/v1/tags` - Get WordPress tags
* `/wp-json/wp-news-source/v1/tags/search` - Search tags with autocomplete
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

= 2.5.3 =
* PERFORMANCE: Added ability to disable update checks when server cannot connect to external services
* PERFORMANCE: Reduced timeout for GitHub API requests from 10 to 3 seconds
* ADDED: Performance optimization class to handle connectivity issues
* ADDED: Option to completely disable WordPress update checks for isolated environments
* FIXED: Extremely slow site loading due to failed connections to WordPress.org and GitHub

= 2.5.2 =
* CRITICAL FIX: Removed remaining references to deleted Publibot files causing fatal error
* FIXED: Plugin activation error due to missing class files

= 2.5.1 =
* FIXED: Import/export now handles category mismatches between WordPress sites
* NEW: Import options to match categories by name instead of ID
* NEW: Option to skip sources if category not found during import
* NEW: Option to use default category for missing categories
* IMPROVED: Better handling of cross-site migrations
* FIXED: Category ID mismatch issue when importing from different WordPress installations

= 2.5.0 =
* REMOVED: All Publibot/AI/OpenAI functionality - plugin now focuses on API for n8n agents
* SIMPLIFIED: Plugin architecture for better performance and reliability
* IMPROVED: Core functionality for news source management and detection
* UPDATED: Database cleanup removes unused Publibot tables

= 2.4.0 =
* NEW: Completely redesigned Settings page with 2/3 main content and 1/3 sidebar layout
* NEW: Toggle switches for API Key requirement setting
* NEW: Regenerate buttons for both API Key and Webhook Secret in sidebar
* IMPROVED: Better error handling for version checks when GitHub API is unavailable
* IMPROVED: Version management shows proper status even with API connectivity issues
* FIXED: "Check for updates" error that appeared despite showing "up to date" status
* FIXED: Export/import functionality now properly handles double-encoded JSON
* UPDATED: n8n integration code to remove references to deprecated prompts feature
* VERIFIED: Webhook secret is actively used for validating incoming webhooks

= 2.3.1 =
* IMPROVED: Visual interface for AI detection configuration - no JSON knowledge required
* ADDED: Tag-based input system for identifiers, context, and exclusions
* ADDED: AI Help button with natural language processing
* ADDED: Toggle between Visual Mode and Advanced JSON Mode
* ADDED: Load Template, Validate, and Format buttons for JSON mode
* IMPROVED: User experience with visual feedback and success messages
* FIXED: Form now properly converts visual inputs to JSON configuration

= 2.3.0 =
* NEW: AI Detection Configuration system for sophisticated source detection
* NEW: Source type templates (government, company, association, person, institution)
* NEW: JSON-based detection rules with context and validation
* NEW: /generate-detection-prompt endpoint for AI integration
* IMPROVED: detection_rules field exposed in all API endpoints
* IMPROVED: Visual template generator in admin interface
* ADDED: Support for complex detection scenarios (jurisdiction, sector, exclusions)
* ADDED: Comprehensive templates for diverse source types
* FIXED: AI detection can now handle entities beyond government sources

= 2.2.3 =
* FIXED: Critical error - method add_detection() changed to log_detection()
* FIXED: 500 Internal Server Error when creating posts via API
* VERIFIED: Image upload functionality working correctly
* VERIFIED: Telegram image support via file_id is functional

= 2.2.2 =
* FIXED: Keywords and description fields not saving when updating sources
* FIXED: JavaScript was sending empty strings for keywords/description fields
* IMPROVED: Form now properly reads and submits textarea values

= 2.2.1 =
* FIXED: Tags not being applied when using source_id in create-post endpoint
* FIXED: Post content not saving correctly with HTML
* FIXED: source_used field returning null in API response
* IMPROVED: Better handling of tag_ids string/array formats
* IMPROVED: Added post meta tracking for source information
* IMPROVED: Debug logging only in WP_DEBUG mode
* ADDED: Automatic detection history tracking for manual source selection

= 2.2.0 =
* NEW: Keywords and description fields exposed in API endpoints for better AI detection
* NEW: Source detection by keywords with confidence scoring (0-1)
* NEW: `/upload-image` endpoint for independent image uploads
* NEW: Telegram image support via file_id in upload and create-post endpoints
* NEW: Support for attachment_id in create-post to use pre-uploaded images
* IMPROVED: Detection algorithm now searches by keywords (30% minimum match)
* IMPROVED: Admin form includes keywords and description fields with examples
* IMPROVED: API responses include detection method and confidence level
* ADDED: Comprehensive AI integration guide with optimized prompts
* ADDED: Telegram integration documentation
* FIXED: Image handling now supports multiple sources seamlessly

= 2.1.0 =
* NEW: Post Creation API endpoint (/create-post) for direct WordPress post creation
* NEW: AI Agent Prompts management system with GUI and API
* NEW: Complete n8n integration with example workflow (bulletin-processor-workflow.json)
* NEW: Search posts endpoint with advanced filtering
* NEW: AJAX autocomplete for tags and categories (performance improvement)
* NEW: Prompt templates for bulletin processing, content analysis, and title generation
* NEW: Support for featured images via URL or base64
* NEW: Automatic tag creation if not exists
* NEW: Spanish (Mexico) translation
* IMPROVED: API error handling and validation
* IMPROVED: Performance optimizations for large tag/category lists
* IMPROVED: Documentation with complete API examples
* ADDED: Test scripts and templates for easy integration testing
* ADDED: n8n Code node integration library
* FIXED: Timezone handling in detection history
* FIXED: Category assignment edge cases

= 2.0.0 =
* BREAKING CHANGE: Complete removal of AI/intelligent detection features
* BREAKING CHANGE: Simplified to name-based detection only
* Removed: All AI-related fields (description, keywords, detection_rules)
* Removed: Confidence scoring and detection methods
* Removed: Complex multi-tab forms
* Added: Simple, clean interface with only essential fields
* Improved: Honest, straightforward functionality
* Fixed: Autocomplete for categories and tags
* Updated: All documentation to reflect actual functionality

= 1.3.0 =
* Fixed: Autocomplete functionality for categories and tags
* Fixed: Restored original add/edit source form with full features
* Improved: Removed all debug code and console.log statements
* Improved: Cleaned up 21 test/debug files
* Added: Compiled translation files (.mo) for Spanish locales
* Improved: Restructured Loader class to proper file
* Updated: Documentation to reflect current state

= 1.1.0 =
* Detection history tracking
* Statistics dashboard
* Webhook support for real-time notifications
* Import/Export functionality
* API key security option
* Performance optimizations

= 1.0.0 =
* Initial release
* Basic source management
* Simple name-based detection
* REST API endpoints
* Category and tag mapping

== Upgrade Notice ==

= 2.3.1 =
Major UX improvement: Visual interface for AI detection configuration. Configure complex detection rules without touching JSON. Fully backward compatible.

= 2.3.0 =
Major feature update: Advanced AI detection configuration system. Configure complex detection rules for all types of sources (not just government). Fully backward compatible.

= 2.2.3 =
CRITICAL FIX: Resolves 500 error when creating posts. Update immediately if using the API.

= 2.2.2 =
Fixes critical issue where keywords and description were not saving. Update immediately if you use these fields for AI detection.

= 2.2.1 =
Critical bugfix: Tags and content now save correctly when creating posts via API. Update immediately if using the create-post endpoint.

= 2.2.0 =
Enhanced AI detection with keywords and confidence scoring. New image upload endpoint with Telegram support. Fully backward compatible.

= 2.1.0 =
MAJOR UPDATE: Adds post creation API, AI prompts system, and complete n8n integration. Includes example workflows and extensive documentation. Fully backward compatible.

= 2.0.0 =
MAJOR UPDATE: Removed all AI features. Plugin now uses simple name-based detection only. This is a breaking change - backup before updating.

= 1.3.0 =
Maintenance update: Fixed autocomplete functionality, cleaned production code, and improved overall stability.

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