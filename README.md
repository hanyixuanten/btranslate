# Btranslate: A Free and Open-Source WordPress Multilingual Plugin Powered by the Baidu Translate API

BTranslate is a multilingual translation plugin for WordPress. It uses the Baidu Translate API to translate posts, pages, and other website content into different languages, with a dedicated URL for each translated version.

Translations are stored on the server. Unless a user explicitly refreshes a translation, the same content is translated only once for each target language. This avoids calling the translation API on every page visit, reducing API usage and improving page load performance.

## Project Links

- GitHub repository: [https://github.com/hanyixuanten/btranslate](https://github.com/hanyixuanten/btranslate)
- WordPress plugin page: [https://wordpress.org/plugins/btranslate/](https://wordpress.org/plugins/btranslate/)

> **Review status:** BTranslate has not yet been approved for the WordPress.org Plugin Directory, so it cannot currently be installed directly from the WordPress admin plugin marketplace. For now, download it from GitHub and install it manually.

## Key Features

### Baidu Translate API Integration

BTranslate uses the translation API provided by the Baidu Translate Open Platform. Users can configure their own App ID and secret key in WordPress Admin, and the plugin handles request signing, API calls, and error handling.

### Post and Page Translation

The plugin supports translating the main content of WordPress posts and pages, including:

- Titles
- Body content
- Excerpts
- SEO fields
- Image alternative text
- Tags
- Categories
- Selected interface text

### Multilingual URLs

BTranslate can generate dedicated URLs for different languages, with support for:

- Language subdirectories such as `/en/` and `/ja/`
- A separate domain for each language
- Language-specific permalinks based on the configured routing mode

### Persistent Translations

After a translation is generated, it is stored on the WordPress server. Future requests for the same translation use the stored result instead of calling the Baidu Translate API again.

When the source content changes, the corresponding translation is marked as requiring an update, preventing an outdated translation from being used for changed content.

### WordPress Content Compatibility

When translating content, the plugin makes every effort to preserve:

- Gutenberg block markup
- HTML structure
- Shortcodes
- Placeholders
- URLs
- Protected special content

### Multilingual SEO

BTranslate provides essential SEO support for multilingual pages, including:

- Multilingual permalinks
- Canonical URLs
- `hreflang` alternate links
- Sitemap compatibility
- SEO field translation

### Failure Fallback

If a Baidu Translate API request fails, the plugin does not cause the website page to fail:

- If no translation is available, the website's source-language content is displayed.
- If a previously saved successful translation exists, the previous translation continues to be used.

## Quick Start

### 1. Get Access to the Baidu Translate API

1. Open the [Baidu Translate Open Platform](https://fanyi-api.baidu.com/).
2. Register or sign in with a Baidu account.
3. Open the management console.
4. Activate the General Text Translation API service.
5. Create an application and enter the required application information.
6. Obtain the **APP ID** and **secret key** from the application management page.

> Your APP ID and secret key are sensitive credentials. Do not commit them to a public GitHub repository or expose them in front-end code or public logs.

For activation instructions, pricing, and usage limits, see the [official Baidu Translate API documentation](https://fanyi-api.baidu.com/doc/23).

### 2. Install the Plugin

Because the plugin has not yet been approved for the WordPress.org Plugin Directory, it must currently be installed manually from GitHub.

#### Method 1: Upload the ZIP Package

1. Visit the [BTranslate GitHub repository](https://github.com/hanyixuanten/btranslate).
2. Select **Releases**.
3. Download the **btranslate-*.zip** file from the latest release.
4. Sign in to WordPress Admin.
5. Open **Plugins > Add New Plugin**.
6. Select **Upload Plugin**.
7. Choose the downloaded ZIP file and start the installation.
8. Activate BTranslate after installation is complete.

#### Method 2: Upload to the Server

1. Visit the [BTranslate GitHub repository](https://github.com/hanyixuanten/btranslate).
2. Select **Releases**.
3. Download the **btranslate-*.zip** file from the latest release.
4. Upload the `btranslate` directory to the WordPress plugin directory: `wp-content/plugins/btranslate/`.
5. Sign in to WordPress Admin.
6. Open **Plugins > Installed Plugins**.
7. Find BTranslate and select **Activate**.

### 3. Configure the Plugin

1. Sign in to WordPress Admin.
2. Open the BTranslate settings page.
3. Enter the **APP ID** and **secret key** provided by the Baidu Translate Open Platform.
4. Set the website's source language.
5. Add the target languages you want to enable.
6. Select a language URL mode, such as language subdirectories or domain binding.
7. Save the settings.

After changing language routing settings, open **Settings > Permalinks** in WordPress Admin and confirm that the permalink configuration is working correctly.

### 4. Start Translating

1. Open the post or page you want to translate in WordPress Admin.
2. Save or update the content.
3. Use the translation feature provided by BTranslate to generate translations for the target languages.
4. Wait for the translation tasks to finish.
5. Check each translation at its language-specific URL.

For example, suppose the source-language page is available at:

```txt
https://example.com/about/
```

When English uses the `/en/` subdirectory, its translated URL is:

```txt
https://example.com/en/about/
```

When Japanese uses the `/ja/` subdirectory, its translated URL is:

```txt
https://example.com/ja/about/
```

The plugin prioritizes saved translations. It calls the translation API again only when the source content changes, a translation becomes invalid, or a user explicitly requests a refresh.

## Recommendations

- Test permalinks and multilingual routing in a staging environment before enabling the plugin in production.
- Set appropriate usage quotas and security policies for the Baidu Translate API.
- Do not store API credentials in source code, front-end JavaScript, or public logs.
- Manually review brand names, technical terminology, and important pages before publishing translations.
- After changing source content, check whether the corresponding translations need to be updated.
- When configuring domain binding, also configure DNS, HTTPS certificates, and the server's domain settings.
- After changing language routing, verify that canonical URLs, `hreflang` links, and sitemaps are correct.

## System Requirements

- WordPress 6.4 or later
- PHP 8.1 or later
- A server environment that can connect to the Baidu Translate API
- A valid Baidu Translate Open Platform APP ID and secret key

## Feedback and Contributions

To report a problem or suggest a feature, visit the GitHub repository:

[https://github.com/hanyixuanten/btranslate](https://github.com/hanyixuanten/btranslate)

When opening an issue, consider including the following information:

- WordPress version
- PHP version
- BTranslate version
- Languages and routing mode in use
- Steps to reproduce the problem
- Error logs with sensitive information removed

Contributions through issues and pull requests are welcome.
