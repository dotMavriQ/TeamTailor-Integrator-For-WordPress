=== TeamTailor Integrator ===
Contributors: dotmavriq
Donate link: https://github.com/sponsors/dotMavriQ
Tags: teamtailor, jobs, recruitment, careers, job listings
Requires at least: 5.8
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.2.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Integrate TeamTailor recruitment with WordPress. Sync job listings, display via shortcodes, and manage recruitment directly from your dashboard.

== Description ==

The TeamTailor Integrator plugin seamlessly integrates TeamTailor's recruitment services with your WordPress site, enabling you to display job listings, receive applications, and manage your recruitment process directly through your website. This plugin utilizes the TeamTailor API to offer a straightforward method for enhancing your site with powerful recruitment capabilities.

= Features =

* **Job Listings Synchronization** — Automatically syncs job listings from your TeamTailor account to your WordPress site.
* **Application Forms** — Embeds TeamTailor application forms into your WordPress pages, allowing candidates to apply directly through your website.
* **API Key Integration** — Securely connects your TeamTailor account with WordPress using an API key.
* **Shortcode Support** — Easily display job listings and application forms anywhere on your site with simple shortcodes.
* **Customizable Settings** — Configure API settings and manage how job listings are displayed directly from the WordPress admin area.
* **API Test Button** — Verify the connection between your WordPress site and TeamTailor with the "Test API" button in the plugin settings.
* **Filter by Department, Location & Role** — Visitors can filter job listings by department, location, and role using dropdown selectors.
* **Mock Data Mode** — Test and develop with built-in simulated job data (Meridian ERP) without needing a live TeamTailor API key.

= Compatibility =

This plugin is compatible with:
* Classic Editor
* Block Editor (Gutenberg)
* Elementor Pro Dynamic Tags
* Advanced Custom Fields (ACF)
* Any theme following WordPress coding standards

= External Services =

This plugin communicates with the TeamTailor API (api.teamtailor.com) to fetch job listings. No personal data is sent to TeamTailor from your WordPress site. The API key is stored in your WordPress database and used solely for authenticating with TeamTailor's services.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/teamtailor-integrator/` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Use the Settings → TeamTailor Integrator screen to configure your API key.
4. Obtain your API key from your TeamTailor account under Settings → Integrations → API.
5. Once configured, use the `[teamtailor_jobs]` shortcode to display job listings on any post or page.

== Frequently Asked Questions ==

= Where do I find my TeamTailor API key? =

Log in to your TeamTailor account, navigate to Settings → Integrations → API, and generate or copy your API key.

= Can I customize the look of the job listings? =

Yes, job listings can be customized via CSS and the plugin's provided hooks and filters. The plugin outputs semantic HTML with clear CSS classes for easy styling.

= Can I test the plugin without a live TeamTailor API key? =

Yes. Go to TeamTailor Integrator → Advanced and enable "Use Mock Data". The plugin will use 12 realistic jobs from "Meridian ERP" (a fictional European ERP company) spanning 8 departments, 12 European cities, 2 role types. You can fully test synchronization, filters, single job views, and the API test tab without any live API key.

= Does this plugin work with any theme? =

Yes, the plugin is designed to work with any WordPress theme. The public-facing output uses semantic HTML that adapts to your theme's styling.

= Is this plugin compatible with the Block Editor? =

Yes. The custom post type for jobs has `show_in_rest` enabled, making it compatible with the Block Editor and WordPress's REST API.

= What data does this plugin send to TeamTailor? =

This plugin only reads data from TeamTailor's API. It fetches job listings using your API key and stores them as WordPress posts. No data from your WordPress site is sent to TeamTailor.

== Screenshots ==

1. The TeamTailor Integrator settings page in the WordPress admin.
2. Job listings displayed on the front-end using the `[teamtailor_jobs]` shortcode.
3. API Test tab showing raw JSON response from TeamTailor.

== Changelog ==

= 1.2.2 =
* Fix CSRF - added nonce validation to API key save form
* Fix escaping - added esc_attr() to shortcode data attributes and esc_html() to debug output
* Fix i18n - ordered placeholders in sync strings and added translators comment for _n()
* Fix compatibility - removed load_plugin_textdomain() (auto-loaded for .org plugins)
* Fix security - sanitized post_type input, replaced rand() with wp_rand()
* Fix metadata - updated Tested up to to WordPress 6.9
* License updated to GPLv2 or later to meet WordPress requirements

= 1.2.0 =
* Added Mock Data Mode for testing without a live API key
* Created 12 realistic test jobs for "Meridian ERP" across 12 European cities
* Added "Use Mock Data" checkbox to Advanced settings tab
* Updated connection status indicator to show mock mode
* Sidebar and API Test tab reflect mock mode status
* Updated sync flow to work without API key in mock mode
* Full cleanup of mock option on uninstall

= 1.1.1 =
* Improved admin UI with enhanced visual feedback
* Added loading indicators for API requests
* Fixed CSS conflicts with some WordPress themes
* Optimized JavaScript performance for admin interface
* Updated documentation with more detailed examples

= 1.1.0 =
* Added support for filtering job listings by department
* Implemented custom templates for job display
* Enhanced shortcode functionality with additional parameters
* Added caching layer for improved performance
* Fixed pagination issues on job listing pages
* Improved responsive design for mobile devices
* Added option to customize application form fields

= 1.0.2 =
* Fixed API connection timeout issues
* Improved error handling and user feedback
* Added support for multilingual sites
* Updated TeamTailor API integration to latest version
* Fixed bug with job location display

= 1.0.1 =
* Added support for TeamTailor webhooks
* Fixed CSS styling issues in admin panel
* Improved sanitization and validation of user inputs
* Added automatic daily synchronization option
* Fixed compatibility issues with WordPress 6.4

= 1.0.0 =
* Initial release
* Basic TeamTailor API integration
* Job listings synchronization
* Settings page with API key configuration
* Test API connection functionality
* Basic shortcode support for displaying jobs

== Upgrade Notice ==

= 1.1.1 =
Maintenance release with UI improvements and bug fixes. Recommended upgrade for all users.

= 1.1.0 =
Feature release adding department filtering, custom templates, caching, and responsive improvements.

== Additional Information ==

For support, feature requests, or bug reports, please visit the [GitHub repository](https://github.com/dotMavriQ/TeamTailor-Integrator-For-WordPress).
