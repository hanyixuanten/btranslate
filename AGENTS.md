# wp-translate Agent Guide

## Project Scope

Build a WordPress multilingual plugin that uses the [Baidu Translate API](https://fanyi-api.baidu.com/doc/23). Support language-specific subdirectory URLs and domain bindings. Translate WordPress posts and pages, interface titles and body text, SEO fields, excerpts, image alt text, tags, and categories.

Unless a user explicitly requests a refresh, each translatable value must be translated once per target language and then persisted on the server for reuse.

## Architecture Boundaries

- Keep language routing, translation providers, persistence, caching, rendering, and admin settings in separate components.
- Put Baidu-specific signing and HTTP behavior behind a provider interface so tests can use a fake provider without credentials or network access.
- Use deterministic translation identities that include the source value fingerprint, source and target language, field context, and provider/configuration version.
- Persist source language, target language, source fingerprint, translated value, status, and necessary timestamps. Reuse a valid persisted translation before making an API request.
- Invalidate a translation when its source content or configured translatable field changes. Never translate dynamically on every front-end request.
- Make failed translation requests non-fatal: retain a prior valid translation when available; otherwise render the configured source-language fallback.

## WordPress Integration

- Follow WordPress Coding Standards and use WordPress APIs for hooks, settings, options, metadata, rewrite rules, sanitization, escaping, capabilities, nonces, HTTP, and cron/queues.
- Do not modify WordPress core, themes, or third-party plugins.
- Use a single plugin prefix for PHP symbols, options, meta keys, transients, REST routes, and custom database tables; document the chosen prefix once it exists.
- Preserve Gutenberg block markup, HTML structure, shortcodes, placeholders, protected tokens, and URLs when translating content.
- Treat serialized data, executable code, credentials, identifiers, passwords, and arbitrary metadata as non-translatable unless explicitly supported.
- Support classic and block themes without assuming a particular page builder.

## Routing And SEO

- Validate every language code against the configured language list; do not trust raw request input.
- Select one canonical URL strategy for each language: subdirectory, domain binding, or an explicitly configured hybrid. Avoid serving the same translation at multiple canonical URLs.
- Generate language-aware permalinks, canonical URLs, `hreflang` alternates, and compatible sitemap output where applicable.
- Preserve query variables, pagination, previews, and supported REST routes through language resolution.
- Register rewrite rules using WordPress APIs. Flush rewrites only on activation, deactivation, or an explicit routing configuration change.

## Security And Privacy

- Store Baidu credentials through WordPress settings APIs and never expose them in front-end output, JavaScript, logs, exports, or ordinary error messages.
- Use `wp_remote_*` with explicit timeouts and actionable, sanitized error handling.
- Sanitize on write, validate configuration, escape at the rendering boundary, and guard admin actions with capability checks and nonces.
- Do not retain full provider payloads or translated user content in logs unless opt-in debug logging is enabled.

## Quality And Documentation

- Add focused tests for language selection, routing, translation cache reuse and invalidation, persistence, provider signing/error handling, and content/meta filtering.
- Mock Baidu API calls in every test. Tests must not require live credentials or outbound network access.
- Keep changes narrow and avoid unrelated refactors or formatting churn.
- Plugin slug and PHP prefix: `wp-translate` and `wpt_`/`WPT_`. The current minimum supported PHP version is 8.1 and the current minimum WordPress version is 6.4.
- Composer manages the development dependencies. Run `composer test` for isolated unit tests and `composer lint` for PHP syntax checks. WordPress API calls must be mocked in tests; tests must not require live credentials, external network access, or a WordPress database.
- Document supported languages, routing modes, data retention, translation lifecycle, and known limitations in the README whenever the implementation changes.
