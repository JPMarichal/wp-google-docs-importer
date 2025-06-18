# Google Docs Importer for WordPress

A WordPress plugin that allows you to import documents from Google Drive as WordPress posts.

## Features

- Connect to your Google Drive account using OAuth 2.0
- List all Google Docs from a specific folder
- Import documents as WordPress posts with proper formatting
- Support for text formatting (bold, italic, headings, lists, etc.)
- Track imported documents to prevent duplicates
- Clean, user-friendly admin interface

## Requirements

- WordPress 5.6 or higher
- PHP 7.4 or higher
- Google API credentials with Google Drive and Google Docs API enabled

## Installation

1. Upload the `google-docs-importer` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Google Cloud Console (https://console.cloud.google.com/)
4. Create a new project or select an existing one
5. Enable the Google Drive API and Google Docs API
6. Create OAuth 2.0 credentials (OAuth client ID)
7. Add authorized redirect URI: `your-site.com/wp-admin/admin-post.php?action=gd_importer_oauth_callback`
8. Go to the plugin settings page and enter your Client ID, Client Secret, and Google Drive Folder ID
9. Click "Connect with Google" to authorize the plugin

## Usage

1. Go to "Google Docs" in the WordPress admin menu
2. Click "Refresh List" to fetch documents from your Google Drive folder
3. Click "Import" next to any document to import it as a WordPress post
4. The imported post will be saved as a draft
5. Edit the post as needed and publish it when ready

## Filters and Actions

### Filters

- `gd_importer/post_args` - Modify the post arguments before creating/updating a post
- `gd_importer/document_content` - Modify the document content before saving to post
- `gd_importer/document_title` - Modify the document title before saving to post

### Actions

- `gd_importer/post_imported` - Fires after a document is successfully imported
- `gd_importer/post_updated` - Fires after an existing post is updated with new content
- `gd_importer/before_import` - Fires before importing a document
- `gd_importer/after_import` - Fires after importing a document (regardless of success)

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This plugin is licensed under the GPL v2 or later.

## Changelog

### 1.0.0
* Initial release
