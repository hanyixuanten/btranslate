=== HTBD - hyx Translator powered by Baidu Translate ===
Contributors: hanyixuanten
Homepage: https://www.vblg.top/index.php/archives/147
Requires at least: 6.4
Tested up to: 7.0
Requires PHP: 8.1
Stable tag: 0.2.3
License: GPL-3.0-only
License URI: https://www.gnu.org/licenses/gpl-3.0.html

使用百度翻译 API 生成并持久化保存 WordPress 多语言内容。

== Description ==

HTBD 使用百度翻译 API 翻译 WordPress 内容，并将每个翻译结果持久化保存以供重复使用。插件支持语言子目录 URL 和语言域名绑定。

WordPress 后台界面会跟随当前 WordPress 语言：默认显示英文，WordPress 设为 `zh_CN` 时使用插件内置的简体中文翻译。

支持的内容包括已发布文章和页面的标题、正文、摘要、图片替代文本、分类、标签及部分 SEO 字段。插件只为 `publish` 状态的文章和页面自动安排翻译。完成的翻译保存在 `{$wpdb->prefix}hyx_bd_translations` 自建数据表中；启用改名后的插件时，已有 `{$wpdb->prefix}btranslate_translations` 数据会自动迁移。前端渲染只读取已保存的翻译，不会动态请求翻译服务。

子目录路由支持 `/en/example-post/` 等路径。域名路由可将配置的主机名映射到目标语言。翻译失败不会中断页面请求；没有有效译文时会回退显示源语言内容。

== Installation ==

1. 将 `hyx-translator-for-baidu-translate` 目录上传到 `/wp-content/plugins/`。
2. 在 WordPress 的“插件”页面启用 HTBD。
3. 打开“设置 > HTBD”，配置百度翻译凭据、源语言、目标语言和路由模式。
4. 第一次保存设置后，手动执行“重新翻译所有内容”，为已有内容安排翻译任务。
5. 此后保存文章或页面时，插件会自动安排翻译任务。

== External Services ==

HTBD 连接由百度提供的百度翻译开放平台 API，以翻译 WordPress 内容。没有该外部服务，插件无法生成新的翻译。站点管理员需要百度翻译账户、应用 ID 和密钥；百度可能根据管理员选择的服务方案实施请求配额或收取使用费用。

已发布文章或页面保存后、支持的分类或标签创建或编辑后，或者管理员明确安排或刷新翻译时，计划任务会请求该 API。前端页面访问只读取已保存的翻译，不会请求百度。已有有效持久化翻译时也不会请求百度，除非管理员明确执行重新翻译。

每次翻译请求会发送待翻译文本、配置的源语言和目标语言代码、百度应用 ID、随机盐值和请求签名。根据待翻译内容，文本可能包含已发布文章或页面的标题、正文、摘要、指定 SEO 标题和描述、附件图片替代文本，以及分类或标签的名称和描述。作为 HTTPS 请求的一部分，百度也能看到来源服务器 IP 等标准网络信息。密钥只在本地用于生成签名，不会作为独立请求字段发送。

HTBD 将百度返回的译文保存在 WordPress 数据库中，但不保存完整百度响应。发送给百度的数据受百度自身条款和隐私规则约束：

* 百度翻译 API 文档：https://fanyi-api.baidu.com/doc/23
* 百度翻译开放平台服务协议：https://fanyi-api.baidu.com/doc/6
* 百度隐私政策：https://privacy.baidu.com/policy

== Frequently Asked Questions ==

= 插件会在每次页面请求时翻译内容吗？ =

不会。翻译由计划任务生成并持久化保存。前端请求只读取已保存的翻译结果。

= 翻译失败后如何重试？ =

失败的文章、页面、分类或标签会显示在设置页进度条下方。点击对应的“重新翻译”按钮可逐项重试。

= 支持哪些 URL 路由模式？ =

支持语言子目录、域名绑定，或同时启用这两种模式。域名和 DNS 必须预先指向同一个 WordPress 站点。

= 卸载插件会删除哪些数据？ =

卸载会删除插件设置、计划任务和自建翻译表，包括所有已持久化的翻译。停用插件不会删除这些数据。

== Changelog ==

= 0.2.3 =

* 将插件及代码库重命名为 HTBD / hyx-translator-for-baidu-translate。
* 自动迁移旧版自建数据表中的翻译数据。
* 更新打包流程、本地化资源、文档和问题模板以适配新的插件标识。
* 修复发布归档构建流程。