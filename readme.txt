=== Your Hidden Trip Builder ===
Contributors: yourhiddentrip
Tags: travel, trip planner, tourism, italy, tuscany, umbria, tour builder
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 6.3.0 <!-- x-release-please-version -->
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Advanced trip builder for Tuscany & Umbria with AI recommendations, custom post types, PDF generation, and WooCommerce integration.

== Description ==

Your Hidden Trip Builder is a comprehensive WordPress plugin designed for tourism professionals and travel agencies focusing on Tuscany and Umbria regions. It provides a complete trip planning solution with modern architecture and advanced features.

**Key Features:**

* **Custom Post Types**: Places (Luoghi), Tours, Accommodations (Alloggi), Partners
* **Custom Taxonomies**: Experiences, Areas, Targets, Seasons  
* **Interactive Trip Builder**: Frontend shortcode with step-by-step interface
* **REST API**: Tour generation, lead management, WooCommerce integration, PDF export
* **CSV Import**: Bulk import functionality for places, accommodations, and tours
* **PDF Generation**: Export itineraries using dompdf library
* **Brevo Integration**: Email marketing integration
* **WooCommerce Integration**: Create products from generated tours
* **Google Analytics**: GA4 dataLayer support
* **AI Recommendations**: Enhanced trip suggestions with gamification
* **PWA Support**: Progressive Web App features for mobile users

== Installation ==

⚠️ **IMPORTANT**: Do NOT download using GitHub's "Download ZIP" button as it downloads source code without required dependencies, causing plugin activation failures.

**For Regular Users (Recommended):**
1. Download the pre-built distribution package from the GitHub Releases page
2. Look for `your-hidden-trip-planner-dist.zip` in the Assets section
3. Extract and upload to your `/wp-content/plugins/` directory
4. Activate the plugin in WordPress admin

**For Developers:**
1. Clone the repository from GitHub
2. Run `composer install` to install dependencies
3. The plugin will work normally

== Frequently Asked Questions ==

= Why does the plugin show a "Missing dependencies" error? =

You likely downloaded the source code directly from GitHub. Please download the distribution package from the Releases page instead, which includes all necessary dependencies.

= How do I create a trip builder form? =

Use the shortcode `[yourhiddentrip_builder]` on any page or post where you want the trip builder interface to appear.

= Does it work with WooCommerce? =

Yes, the plugin integrates with WooCommerce to create products from generated tours with configurable pricing and deposit options.

== Screenshots ==

1. Trip builder interface with step-by-step tour creation
2. Admin dashboard with analytics and system health
3. CSV import interface for bulk data management
4. WooCommerce integration settings

== Changelog ==

= 6.3 =
* Added AI-enhanced recommendations with gamification
* Improved PWA support with offline functionality
* Enhanced analytics system with performance monitoring
* Added security module with threat detection
* Improved mobile responsiveness and UX
* Added system health dashboard
* Enhanced PDF generation capabilities

= 6.2 =
* Refactored architecture from monolithic to modular design
* Improved performance and maintainability
* Added comprehensive error handling
* Enhanced WPML compatibility

== Upgrade Notice ==

= 6.3 =
Major update with AI enhancements, PWA support, and improved security. Backup your site before upgrading.

== Development ==

This plugin follows WordPress coding standards and uses modern PHP practices with:
* Singleton pattern for main plugin class
* Autoloading for class files
* Composer for dependency management
* RESTful API endpoints
* Sanitization and validation throughout

For developers and contributors, please visit the GitHub repository and follow the contribution guidelines.