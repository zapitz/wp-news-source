# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Release Build Instructions

When creating a release package, use the build script which creates a clean production build:

```bash
./build-release.sh
```

This will generate `wp-news-source.zip` (without version number in filename) containing only essential production files.

## CRITICAL: LANGUAGE REQUIREMENTS

**THIS PLUGIN MUST USE ENGLISH FOR ALL CODE, VARIABLES, AND INTERNAL STRUCTURES**

- **PRIMARY LANGUAGE**: English is the ONLY language for code, variables, functions, classes, database fields, JSON structures, and ALL internal identifiers
- **NO SPANISH IN CODE**: NEVER use Spanish for variable names, function names, JSON field names, or any code elements
- **TRANSLATIONS**: Spanish translations are provided ONLY via .po/.mo language files
- **JSON FIELD NAMES**: ALL JSON structures MUST use English field names (e.g., `source_type` NOT `tipo_fuente`, `identifiers` NOT `identificadores`)
- **UI TEXT**: User-facing text uses WordPress translation functions `__()` and `_e()` with English as the base language

## Important Release Guidelines

### When Creating a New Release:

1. **Provide ALL release information in English:**
   - Release Title (e.g., "Critical Bug Fixes and UI Improvements")
   - Version Tag (e.g., "v2.3.2")
   - Full Description with bullet points of changes
   - All documentation MUST be in English

2. **Create production-ready ZIP file:**
   - **FILE NAME**: ALWAYS use `wp-news-source.zip` (NO version number in filename)
   - **CRITICAL**: Never include version numbers in the ZIP filename to prevent duplicate plugin installations
   - **FOLDER INSIDE ZIP**: MUST ALWAYS be named `wp-news-source/` (NO version numbers)
   - This ensures WordPress recognizes it as an update, not a new plugin
   - WordPress updater requires consistent file and folder names

3. **Exclude ALL development files:**
   - No test files (test-*.php, test-*.sh, fix-*.php)
   - No development configs (composer.json, webpack.config.js, phpunit.xml)
   - No build tools (node_modules/, vendor/)
   - No version control (.git/, .gitignore)
   - No editor files (.DS_Store, *.bak, *~)
   - No development documentation (RELEASE-*.md, BUGFIX-*.md, FIXES-*.md)
   - No workflow JSONs (n8n-*.json, subflujo-*.json, flujo-*.json)
   - No temporary folders (temp-*, tmp-*)
   - Keep only: readme.txt and essential production files

4. **ZIP Structure (ALWAYS use this exact structure):**
   ```
   wp-news-source.zip              <-- NO version in filename!
   └── wp-news-source/             <-- NO version in folder name!
       ├── admin/
       ├── includes/
       ├── languages/
       ├── wp-news-source.php
       └── readme.txt
   ```
   
   **IMPORTANT**: Using version numbers in either the ZIP filename or the folder name will cause WordPress to install it as a NEW plugin instead of updating the existing one. This creates duplicate plugins and confuses users.

## Project Overview

WP News Source is a WordPress plugin that manages news sources and press releases for automated categorization and tagging. The plugin is designed for n8n integration and helps media organizations automate content processing from multiple sources.

## Architecture

The plugin follows WordPress plugin architecture with these key components:

- **Main Plugin File**: `wp-news-source.php` - Entry point and activation hooks
- **Core Classes**: Located in `/includes/`
  - `WP_News_Source` - Main plugin class that orchestrates all functionality
  - `WP_News_Source_Loader` - Hook registration system
  - `WP_News_Source_API` - REST API endpoints with webhooks
  - `WP_News_Source_Activator` - Database setup and plugin activation
- **Admin Interface**: `/admin/class-wp-news-source-admin.php` - WordPress admin panel with AJAX handlers
- **Database Layer**: `/database/class-wp-news-source-db.php` - All database operations

## Key Features

- **Source Detection**: Simple name-based detection in content
- **Webhook Support**: Real-time notifications when sources are detected
- **Statistics & History**: Tracks detection performance and maintains history
- **Import/Export**: JSON-based configuration sharing
- **API Security**: Optional API key protection

## REST API Endpoints

All endpoints are under `/wp-json/wp-news-source/v1/`:

- `GET /sources` - List all sources
- `GET /mapping` - Complete mapping for n8n
- `POST /detect` - Source detection by name
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
- Autocomplete functionality for categories and tags selection

**Testing:**
- Test in WordPress environment (DevKinsta, Local, etc.)
- Use REST API tools to test endpoints
- Check database changes in phpMyAdmin
- Test detection with various content samples


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