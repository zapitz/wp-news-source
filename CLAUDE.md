# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

WP News Source is a WordPress plugin that manages news sources and press releases with AI-powered detection for automated categorization and tagging. The plugin is designed for n8n integration and helps media organizations automate content processing from multiple sources.

## Architecture

The plugin follows WordPress plugin architecture with these key components:

- **Main Plugin File**: `wp-news-source.php` - Entry point and activation hooks
- **Core Classes**: Located in `/includes/`
  - `WP_News_Source` - Main plugin class that orchestrates all functionality
  - `WP_News_Source_Loader` - Hook registration system (embedded in main class)
  - `WP_News_Source_API` - REST API endpoints with AI detection and webhooks
  - `WP_News_Source_Activator` - Database setup and plugin activation
- **Admin Interface**: `/admin/class-wp-news-source-admin.php` - WordPress admin panel with AJAX handlers
- **Database Layer**: `/database/class-wp-news-source-db.php` - All database operations including AI detection

## Key Features

- **AI-Powered Detection**: Uses intelligent scoring system with name matching, keyword detection, and context analysis
- **Detection Rules**: JSON-based custom detection rules (contains, regex, starts_with, word_count_min)
- **Webhook Support**: Real-time notifications when sources are detected
- **Statistics & History**: Tracks detection performance and maintains history
- **Import/Export**: JSON-based configuration sharing
- **API Security**: Optional API key protection

## REST API Endpoints

All endpoints are under `/wp-json/wp-news-source/v1/`:

- `GET /sources` - List all sources
- `GET /mapping` - Complete mapping for n8n (includes AI features flag)
- `POST /detect` - AI-powered source detection (supports use_ai parameter)
- `POST /validate` - Validate content against source rules
- `POST /webhook/{source_id}` - Webhook handler
- `GET /history` - Detection history (with source_id filter)
- `GET /stats` - Statistics dashboard
- `GET /export` - Export configuration
- `POST /import` - Import configuration
- `GET /categories` - WordPress categories
- `GET /tags` - WordPress tags

## Database Schema

**Main table**: `wp_news_sources`
- Core fields: `id`, `name`, `slug`, `source_type`
- AI fields: `description`, `keywords`, `detection_rules`
- Mapping: `category_id`, `category_name`, `tag_ids`, `tag_names`
- Automation: `auto_publish`, `requires_review`, `webhook_url`
- Security: `api_key`
- Statistics: `detection_count`, `last_detected`

**History table**: `wp_news_source_detections`
- Fields: `source_id`, `post_id`, `detection_confidence`, `detection_method`, `detected_content`

## Development Commands

This is a standard WordPress plugin - no build process required.

**Plugin Management:**
- Activate in WordPress Admin → Plugins
- Access via WordPress Admin → News Sources menu
- Four admin pages: All Sources, Add New, Statistics, Settings

**Testing:**
- Test in WordPress environment (DevKinsta, Local, etc.)
- Use REST API tools to test endpoints
- Check database changes in phpMyAdmin
- Test AI detection with various content samples

## AI Detection System

The intelligent detection system uses a weighted scoring approach:
1. **Exact name matching** (weight: 50)
2. **Keyword matching** (weight: 20 per keyword)
3. **Context analysis** from description (weight: 15 per phrase)
4. **Custom JSON rules** (configurable weights)

Minimum confidence threshold is 30 (configurable via `wpns_min_detection_confidence` filter).

## Professional UI System

The plugin implements a comprehensive professional UI design system:

**CSS Architecture:**
- CSS custom properties for consistent theming
- WordPress-compatible color palette and spacing
- Professional component library (cards, tabs, buttons, forms)
- Responsive design with mobile-first approach
- Accessibility features (ARIA labels, focus management, high contrast support)

**JavaScript Framework:**
- Modular namespace pattern (`window.WPNewsSource`)
- Professional notification system (`WPNSNotices`)
- Enhanced AJAX with error handling and loading states
- Auto-save functionality with debouncing
- Form validation with visual feedback

**Performance Optimizations:**
- AJAX autocomplete for categories/tags (replaces loading thousands at once)
- Debounced search with 300ms delay
- Pagination and limits on API endpoints
- Efficient database queries with proper indexing

**UI Components:**
- Tab navigation system with URL hash support
- Enhanced buttons with loading states and hover effects
- Professional form components with validation
- Autocomplete system with keyboard navigation
- Real-time notifications with auto-dismiss
- Copy-to-clipboard functionality

## Integration Notes

- Designed for n8n workflow automation
- Supports webhook notifications for real-time integration
- API key protection available for production use
- All database operations use WordPress $wpdb for security
- Uses WordPress capabilities system for permissions
- Professional admin interface follows WordPress design guidelines
- Fully responsive and accessible design
- Internationalization ready with Spanish translations included