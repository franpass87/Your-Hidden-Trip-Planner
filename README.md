# Your Hidden Trip Builder Plugin - Refactored Architecture

**Current version: 6.3.0** <!-- x-release-please-version -->

## Overview

This WordPress plugin has been refactored from a monolithic single-file structure (1365+ lines) to a modern, modular architecture following WordPress best practices and SOLID principles.

## ⚠️ Important Installation Note

**DO NOT use GitHub's "Download ZIP" button** as it downloads source code without required dependencies, causing plugin activation failures.

### For Regular Users (Recommended)
1. Download the pre-built distribution package from the [Releases page](https://github.com/franpass87/Your-Hidden-Trip-Planner/releases)
2. Look for `your-hidden-trip-planner-dist.zip` in the Assets section
3. Extract and upload to your `/wp-content/plugins/` directory
4. Activate the plugin in WordPress admin

### For Developers
1. Clone this repository
2. Run `composer install` to install dependencies
3. The plugin will work normally

## Plugin Features

- **Custom Post Types**: Places (Luoghi), Tours, Accommodations (Alloggi), Partners
- **Custom Taxonomies**: Experiences, Areas, Targets, Seasons  
- **Interactive Trip Builder**: Frontend shortcode with step-by-step interface
- **REST API**: Tour generation, lead management, WooCommerce integration, PDF export
- **CSV Import**: Bulk import functionality for places, accommodations, and tours
- **PDF Generation**: Export itineraries using dompdf library
- **Brevo Integration**: Email marketing integration
- **WooCommerce Integration**: Create products from generated tours
- **Google Analytics**: GA4 dataLayer support

## Architecture

### File Structure

```
your-hidden-trip-planner.php     # Main plugin bootstrap (26 lines)
includes/
├── class-yht-plugin.php         # Main singleton plugin class
├── admin/                       # Admin functionality
│   ├── class-yht-admin.php      # Menu management
│   ├── class-yht-settings.php   # Plugin settings
│   ├── class-yht-importer.php   # CSV import
│   └── views/                   # Admin templates
│       └── luogo-meta-box.php
├── post-types/                  # WordPress content types
│   └── class-yht-post-types.php
├── rest-api/                    # API endpoints
│   └── class-yht-rest-controller.php
├── frontend/                    # Public-facing functionality
│   ├── class-yht-shortcode.php
│   └── views/
│       └── trip-builder.php
├── pdf/                         # PDF generation
│   └── class-yht-pdf-generator.php
└── utilities/                   # Helper functions
    └── class-yht-helpers.php
```

The refactored architecture makes the plugin more maintainable, testable, and extensible while preserving 100% backward compatibility.