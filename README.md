# WP DocuGenius

**Version**: 1.0  
**Author**: Melissa Redd  
**License**: GPL2

WP DocuGenius is a custom WordPress plugin designed to automate theme function documentation. By using OpenAI’s GPT model, this plugin translates code into human-readable documentation, saving time and improving code comprehension directly within the WordPress admin.

---

## Features

- **Automated Function Documentation**: Analyzes theme functions and generates summaries, parameter descriptions, and return details using AI.
- **User-Friendly Admin Interface**: Simple settings page to configure and access documentation.
- **Refresh & Loading Screen**: Seamlessly update documentation without timeout or crash errors.
- **Export to Markdown**: One-click export of documentation to Markdown for easy sharing and external reference.

---

## Installation

### Prerequisites
1. **OpenAI API Key**: You’ll need a valid OpenAI API key to enable the AI-powered documentation generation.

### Steps
1. **Upload Plugin**: Download the `WP DocuGenius` plugin folder and upload it to the `/wp-content/plugins/` directory.
2. **Activate Plugin**: Go to the WordPress admin dashboard and navigate to **Plugins**. Find **WP DocuGenius** and click **Activate**.
3. **API Key Setup**: After activation, go to **Theme Docs > API Settings**. Enter your OpenAI API key and save the settings.

---

## Usage

1. **Access Documentation**:
   - Navigate to **Theme Docs > Theme Documentation** in the WordPress admin dashboard.
   - If documentation is cached, it will display instantly; otherwise, it may take a moment to generate.

2. **Refresh Documentation**:
   - Click the **Refresh Documentation** button to generate updated documentation.
   - The plugin will display a loading indicator during the process.

3. **Export Documentation**:
   - Click **Export to Markdown** to download a Markdown file of the documentation, which can be used in external tools or stored in version control.

---

## Troubleshooting

- **Documentation not generating**: Ensure the API key is entered correctly in the settings.
- **Refresh Button Not Working**: Check that the OpenAI API and plugin settings are correctly configured.

---

## Changelog

### 1.0
- Initial release with automated function documentation, user-friendly interface, refresh button, and Markdown export.

---

## License

This plugin is licensed under GPL2. See the `LICENSE` file for more information.

---

## Support

For any issues, contact [mredd2019@gmail.com]
