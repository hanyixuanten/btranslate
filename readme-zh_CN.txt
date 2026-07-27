=== Btranslate ===
Contributors: hanyixuanten
Requires at least: 6.4
Tested up to: 7.0
Requires PHP: 8.1
Stable tag: 0.2.1
License: GPL-3.0-only
License URI: https://www.gnu.org/licenses/gpl-3.0.html

使用百度翻译 API 生成并持久化保存 WordPress 多语言内容。

== Description ==

Btranslate 使用百度翻译 API 翻译 WordPress 内容，并将每个翻译结果持久化保存以供重复使用。插件支持语言子目录 URL 和语言域名绑定。

支持的内容包括文章和页面标题、正文、摘要、图片替代文本、分类、标签及部分 SEO 字段。完成的翻译保存在插件自建数据表中；前端渲染只读取已保存的翻译，不会动态请求翻译服务。

子目录路由支持 `/en/example-post/` 等路径。域名路由可将配置的主机名映射到目标语言。翻译失败不会中断页面请求；没有有效译文时会回退显示源语言内容。

== Installation ==

1. 将 `btranslate` 目录上传到 `/wp-content/plugins/`。
2. 在 WordPress 的“插件”页面启用 Btranslate。
3. 打开“设置 > Btranslate”，配置百度翻译凭据、源语言、目标语言和路由模式。
4. 保存文章或页面，或使用设置页面中的批量翻译功能安排翻译任务。

== Frequently Asked Questions ==

= 插件会在每次页面请求时翻译内容吗？ =

不会。翻译由计划任务生成并持久化保存。前端请求只读取已保存的翻译结果。

= 支持哪些 URL 路由模式？ =

支持语言子目录、域名绑定，或同时启用这两种模式。域名和 DNS 必须预先指向同一个 WordPress 站点。

= 卸载插件会删除哪些数据？ =

卸载会删除插件设置、计划任务和自建翻译表，包括所有已持久化的翻译。停用插件不会删除这些数据。

== Changelog ==

= 0.2.1 =

* 声明 GPLv3 许可证并补充 WordPress.org 插件元数据。