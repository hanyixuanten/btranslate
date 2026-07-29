=== Btranslate ===
Contributors: hanyixuanten
Homepage: https://github.com/hanyixuanten/btranslate
Requires at least: 6.4
Tested up to: 7.0
Requires PHP: 8.1
Stable tag: 0.2.1
License: GPL-3.0-only
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Persistent multilingual WordPress translations powered by the Baidu Translate API.

== Description ==

Btranslate translates supported WordPress content with the Baidu Translate API and persists each translation for reuse. It supports language-specific subdirectory URLs and domain bindings.

The WordPress admin interface follows the current WordPress locale. English is the default, and a bundled Simplified Chinese translation is used when WordPress is set to `zh_CN`.

Supported values include published post and page titles, content, excerpts, image alternative text, categories, tags, and selected SEO fields. Automatic translation is only scheduled for posts and pages with the `publish` status. Completed translations are stored in the plugin's custom database table. Front-end rendering reuses stored values and never calls the translation provider dynamically.

Subdirectory routing supports paths such as `/en/example-post/`. Domain routing maps configured hostnames to target languages. Translation failures are non-fatal and fall back to source-language content.

== Installation ==

1. Upload the `btranslate` directory to `/wp-content/plugins/`.
2. Activate Btranslate through the Plugins screen in WordPress.
3. Open Settings > Btranslate and configure the Baidu credentials, languages, and routing mode.
4. After saving settings for the first time, manually run Retranslate all content to queue existing content.

== External Services ==

Btranslate connects to the Baidu Translate Open Platform API, a service provided by Baidu, to translate WordPress content. The plugin cannot generate new translations without this service. A Baidu Translate account, application ID, and secret key are required. Baidu may apply request quotas or usage charges according to the service plan selected by the site administrator.

The API is contacted by scheduled tasks after a published post or page is saved, after a supported category or tag is created or edited, or after an administrator explicitly queues or refreshes translations. Front-end page views reuse stored translations and do not contact Baidu. The plugin also makes no Baidu request when valid persisted translations are already available, unless an administrator explicitly requests a refresh.

For each translation request, the plugin sends the text being translated, the configured source and target language codes, the Baidu application ID, a random salt, and a request signature. Depending on the content being translated, the text may contain published post or page titles, body text, excerpts, selected SEO title and description values, attached image alternative text, and category or tag names and descriptions. Standard network information, including the originating server IP address, is also visible to Baidu as part of the HTTPS request. The secret key is used locally to create the signature and is not sent as a separate request field.

Btranslate stores the returned translated text in the WordPress database but does not store complete Baidu response payloads. Data sent to Baidu is subject to Baidu's own terms and privacy practices:

* Baidu Translate API documentation: https://fanyi-api.baidu.com/doc/23
* Baidu Translate Open Platform service agreement: https://fanyi-api.baidu.com/doc/6
* Baidu privacy policy: https://privacy.baidu.com/policy

== Frequently Asked Questions ==

= Does the plugin translate content on every page request? =

No. Each value is translated by a scheduled task and persisted for reuse. Front-end requests read stored translations only.

= How do I retry a failed translation? =

Failed posts, pages, categories, or tags appear below the progress bar on the Settings page. Use the Retranslate button beside an item to retry it.

= What data is removed during uninstall? =

Uninstalling removes plugin settings, scheduled tasks, and the custom translation table, including all persisted translations.

== Changelog ==

= 0.2.1 =

* Declare GPLv3 licensing and WordPress.org plugin metadata.