# TeamTailor Integrator WordPress Plugin

## Description

The TeamTailor Integrator plugin seamlessly integrates TeamTailor's recruitment services with your WordPress site, enabling you to display job listings, receive applications, and manage your recruitment process directly through your website. This plugin utilizes the TeamTailor API to offer a straightforward method for enhancing your site with powerful recruitment capabilities.

## Features

- **Job Listings Synchronization**: Automatically syncs job listings from your TeamTailor account to your WordPress site.
- **Application Forms**: Embeds TeamTailor application forms into your WordPress pages, allowing candidates to apply directly through your website.
- **API Key Integration**: Securely connects your TeamTailor account with WordPress using an API key.
- **Shortcode Support**: Easily display job listings and application forms anywhere on your site with simple shortcodes.
- **Customizable Settings**: Configure API settings and manage how job listings are displayed directly from the WordPress admin area.
- **API Test Button**: Verify the connection between your WordPress site and TeamTailor with the "Test API" button in the plugin settings.

## Installation

1. **Upload Plugin**: Download the plugin zip file and upload it to your WordPress site via the Plugins > Add New > Upload Plugin page.
2. **Activate Plugin**: Once uploaded, activate the TeamTailor Integrator plugin through the 'Plugins' menu in WordPress.
3. **Configure API Key**: Navigate to the TeamTailor Integrator settings page under the main WordPress settings menu. Enter your TeamTailor API key and save the settings.

## Usage

After installation and configuration, you can start syncing job listings and embedding application forms:

- **Display Job Listings**: Use the `[teamtailor_jobs]` shortcode to display job listings on any post or page.

## Configuration

- **API Key**: Obtain your API key from your TeamTailor account and enter it in the plugin settings.
- **Test API Connection**: Use the "Test API" button in the plugin settings to ensure your site can communicate with TeamTailor.
- **Customize Listings Display**: Configure how job listings are displayed through the plugin settings, including layout and fields shown.

## Development

#### TODO:
- **API Button functionality**: The button shows you the data from the API Key that you have provided.
- **Embed Application Form**: Use the `[teamtailor_application_form job_id="XXX"]` shortcode to embed an application form for a specific job listing.

### Hooks and Filters

The plugin provides various hooks and filters allowing developers to customize functionality and extend the plugin to meet specific needs.

### Extending the Plugin

Developers can extend the plugin by creating add-ons or customizing its behavior through available actions and filters.

## Frequently Asked Questions

**Q: Where do I find my TeamTailor API key?**  
A: Log in to your TeamTailor account, navigate to the API settings, and generate or copy your API key.

**Q: Can I customize the look of the job listings?**  
A: Yes, job listings can be customized via CSS and the plugin's provided hooks and filters.

## Changelog

Detail updates and changes for each version of the plugin.

## Support

For support, please visit the support page or GitHub Issues section of the project repository.

## Contributing

Contributions are welcome. Please read our contributing guidelines on GitHub before submitting pull requests.
