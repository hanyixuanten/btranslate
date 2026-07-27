=== Btranslate ===
Contributors: hanyixuanten
Requires at least: 6.4
Tested up to: 7.0
Requires PHP: 8.1
Stable tag: 0.2.1
License: GPL-3.0-only
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Persistent multilingual WordPress translations powered by the Baidu Translate API.

== Description ==

Btranslate translates supported WordPress content with the Baidu Translate API and persists each translation for reuse. It supports language-specific subdirectory URLs and domain bindings.

Supported values include post and page titles, content, excerpts, image alternative text, categories, tags, and selected SEO fields. Completed translations are stored in the plugin's custom database table. Front-end rendering reuses stored values and never calls the translation provider dynamically.

Subdirectory routing supports paths such as `/en/example-post/`. Domain routing maps configured hostnames to target languages. Translation failures are non-fatal and fall back to source-language content.

== Installation ==

1. Upload the `btranslate` directory to `/wp-content/plugins/`.
2. Activate Btranslate through the Plugins screen in WordPress.
3. Open Settings > Btranslate and configure the Baidu credentials, languages, and routing mode.

== Frequently Asked Questions ==

= Does the plugin translate content on every page request? =

No. Each value is translated by a scheduled task and persisted for reuse. Front-end requests read stored translations only.

= What data is removed during uninstall? =

Uninstalling removes plugin settings, scheduled tasks, and the custom translation table, including all persisted translations.

== Changelog ==

= 0.2.1 =

* Declare GPLv3 licensing and WordPress.org plugin metadata.