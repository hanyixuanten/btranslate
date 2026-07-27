<?php

defined( 'ABSPATH' ) || exit;

class BTRANSLATE_Admin {
	public function register() {
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_post_btranslate_queue_existing_translations', array( $this, 'queue_existing_translations' ) );
		add_action( 'admin_post_btranslate_clear_translation_cache', array( $this, 'clear_translation_cache' ) );
		add_action( 'admin_post_btranslate_translate_post', array( $this, 'queue_post_translation' ) );
		add_action( 'wp_ajax_btranslate_translation_progress', array( $this, 'translation_progress' ) );
		add_filter( 'manage_post_posts_columns', array( $this, 'add_translation_column' ) );
		add_filter( 'manage_page_posts_columns', array( $this, 'add_translation_column' ) );
		add_action( 'manage_post_posts_custom_column', array( $this, 'render_translation_column' ), 10, 2 );
		add_action( 'manage_page_posts_custom_column', array( $this, 'render_translation_column' ), 10, 2 );
		add_filter( 'plugin_action_links_' . plugin_basename( BTRANSLATE_FILE ), array( $this, 'add_plugin_settings_link' ) );
	}

	public function add_settings_page() {
		add_options_page( __( 'Btranslate', 'btranslate' ), __( 'Btranslate', 'btranslate' ), 'manage_options', 'btranslate', array( $this, 'render_settings_page' ) );
	}

	public function register_settings() {
		register_setting( 'btranslate_settings', 'btranslate_settings', array( $this, 'sanitize_settings' ) );
	}

	public function sanitize_settings( $settings ) {
		$settings = (array) $settings;
		$bindings = array();
		$routing_modes = BTRANSLATE_Settings::routing_modes( $settings['routing_mode'] ?? array() );
		$target_languages = array_values( array_filter( array_map( 'sanitize_key', explode( ',', (string) ( $settings['target_languages'] ?? 'en' ) ) ) ) );

		foreach ( preg_split( '/\r\n|\r|\n/', (string) ( $settings['domain_bindings'] ?? '' ) ) as $line ) {
			$parts = array_map( 'trim', explode( '=', $line, 2 ) );
			$domain = 2 === count( $parts ) ? BTRANSLATE_Settings::normalize_domain( $parts[0] ) : '';
			$language = 2 === count( $parts ) ? sanitize_key( $parts[1] ) : '';
			if ( '' !== $domain && in_array( $language, $target_languages, true ) && ! in_array( $language, $bindings, true ) ) {
				$bindings[ $domain ] = $language;
			}
		}

		return array(
			'source_language'   => sanitize_key( $settings['source_language'] ?? 'zh' ),
			'target_languages'  => $target_languages,
			'routing_mode'      => $routing_modes,
			'domain_bindings'   => $bindings,
			'fallback_language' => sanitize_key( $settings['fallback_language'] ?? 'zh' ),
			'baidu_app_id'      => sanitize_text_field( $settings['baidu_app_id'] ?? '' ),
			'baidu_secret_key'  => sanitize_text_field( $settings['baidu_secret_key'] ?? '' ),
			'log_requests'      => ! empty( $settings['log_requests'] ),
		);
	}

	public function render_settings_page() {
		$settings = BTRANSLATE_Settings::get();
		$bindings = array();
		foreach ( (array) $settings['domain_bindings'] as $domain => $language ) {
			$bindings[] = $domain . '=' . $language;
		}
		?>
		<div class="wrap">
			<h1>Btranslate</h1>
			<form action="options.php" method="post">
				<?php settings_fields( 'btranslate_settings' ); ?>
				<table class="form-table" role="presentation">
					<tr><th scope="row"><label for="btranslate-source-language">源语言</label></th><td><input id="btranslate-source-language" name="btranslate_settings[source_language]" type="text" value="<?php echo esc_attr( $settings['source_language'] ); ?>" /><p class="description">使用百度翻译语言代码，例如 <code>zh</code>。</p></td></tr>
					<tr><th scope="row"><label for="btranslate-target-languages">目标语言</label></th><td><input id="btranslate-target-languages" name="btranslate_settings[target_languages]" type="text" value="<?php echo esc_attr( implode( ',', $settings['target_languages'] ) ); ?>" class="regular-text" /><p class="description">多个语言代码用英文逗号分隔，例如 <code>en,ja</code>。</p></td></tr>
					<tr><th scope="row">网址模式</th><td><fieldset id="btranslate-routing-mode"><label><input type="checkbox" name="btranslate_settings[routing_mode][]" value="subdirectory" <?php checked( in_array( 'subdirectory', $settings['routing_mode'], true ) ); ?> /> 子目录</label><br /><label><input type="checkbox" name="btranslate_settings[routing_mode][]" value="domain" <?php checked( in_array( 'domain', $settings['routing_mode'], true ) ); ?> /> 子域名</label></fieldset></td></tr>
					<tr id="btranslate-domain-bindings-row"<?php echo in_array( 'domain', $settings['routing_mode'], true ) ? '' : ' style="display:none"'; ?>><th scope="row"><label for="btranslate-domain-bindings">域名绑定</label></th><td><textarea id="btranslate-domain-bindings" name="btranslate_settings[domain_bindings]" rows="4" class="large-text" placeholder="en.example.com=en"><?php echo esc_textarea( implode( "\n", $bindings ) ); ?></textarea><p class="description">每行一个 <code>域名=语言代码</code>，例如 <code>en.example.com=en</code>。请勿填写 <code>https://</code>、路径或端口。</p></td></tr>
					<tr><th scope="row"><label for="btranslate-baidu-app-id">百度应用 ID</label></th><td><input id="btranslate-baidu-app-id" name="btranslate_settings[baidu_app_id]" type="text" value="<?php echo esc_attr( $settings['baidu_app_id'] ); ?>" class="regular-text" /></td></tr>
					<tr><th scope="row"><label for="btranslate-baidu-secret-key">百度密钥</label></th><td><input id="btranslate-baidu-secret-key" name="btranslate_settings[baidu_secret_key]" type="password" value="<?php echo esc_attr( $settings['baidu_secret_key'] ); ?>" class="regular-text" autocomplete="new-password" /></td></tr>
					<tr><th scope="row">请求日志</th><td><label for="btranslate-log-requests"><input id="btranslate-log-requests" name="btranslate_settings[log_requests]" type="checkbox" value="1" <?php checked( $settings['log_requests'] ); ?> /> 记录每次百度翻译请求</label><p class="description">日志写入 PHP 错误日志，包含语言、字段、文本指纹、长度和结果状态；不会记录密钥、原文、译文或完整 API 响应。</p></td></tr>
				</table>
				<?php submit_button(); ?>
			</form>
			<script>
				(function () {
					var domainMode = document.querySelector('#btranslate-routing-mode input[value="domain"]');
					var bindingsRow = document.getElementById('btranslate-domain-bindings-row');

					function toggleDomainBindings() {
						bindingsRow.style.display = domainMode.checked ? '' : 'none';
					}

					domainMode.addEventListener('change', toggleDomainBindings);
				}());
			</script>
			<hr />
			<h2>重新翻译所有内容</h2>
			<p>此操作会将所有已发布文章、页面、分类和标签重新加入翻译队列。</p>
			<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" onsubmit="return window.confirm('这将重新翻译所有内容，请注意 API 消耗。确认继续吗？');">
				<input type="hidden" name="action" value="btranslate_queue_existing_translations" />
				<?php wp_nonce_field( 'btranslate_queue_existing_translations' ); ?>
				<?php submit_button( '重新翻译所有内容', 'secondary', 'submit', false ); ?>
			</form>
			<p>也可以只重新翻译一种内容类型。</p>
			<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" style="display:inline-block;margin-right:8px" onsubmit="return window.confirm('这将重新翻译所有文章和页面，请注意 API 消耗。确认继续吗？');">
				<input type="hidden" name="action" value="btranslate_queue_existing_translations" />
				<input type="hidden" name="scope" value="posts" />
				<?php wp_nonce_field( 'btranslate_queue_existing_translations' ); ?>
				<?php submit_button( '翻译所有文章', 'secondary', 'submit', false ); ?>
			</form>
			<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" style="display:inline-block" onsubmit="return window.confirm('这将重新翻译所有分类和标签，请注意 API 消耗。确认继续吗？');">
				<input type="hidden" name="action" value="btranslate_queue_existing_translations" />
				<input type="hidden" name="scope" value="terms" />
				<?php wp_nonce_field( 'btranslate_queue_existing_translations' ); ?>
				<?php submit_button( '翻译所有分类标签', 'secondary', 'submit', false ); ?>
			</form>
			<hr />
			<h2>翻译缓存</h2>
			<p>清除后，前台会暂时显示源文；重新翻译时将再次调用百度翻译 API。</p>
			<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" onsubmit="return window.confirm('这将删除所有已保存的翻译、取消待执行翻译任务并重置进度。确认继续吗？');">
				<input type="hidden" name="action" value="btranslate_clear_translation_cache" />
				<?php wp_nonce_field( 'btranslate_clear_translation_cache' ); ?>
				<?php submit_button( '清除已翻译的缓存', 'delete', 'submit', false ); ?>
			</form>
			<hr />
			<h2>翻译进度</h2>
			<div id="btranslate-translation-progress" aria-live="polite">
				<p>正在读取翻译进度...</p>
			</div>
			<script>
				(function () {
					var container = document.getElementById('btranslate-translation-progress');
					var endpoint = <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>;
					var nonce = <?php echo wp_json_encode( wp_create_nonce( 'btranslate_translation_progress' ) ); ?>;

					function updateProgress() {
						fetch(endpoint + '?action=btranslate_translation_progress&_ajax_nonce=' + encodeURIComponent(nonce), { credentials: 'same-origin' })
							.then(function (response) { return response.json(); })
							.then(function (response) {
								if (!response.success) {
									throw new Error('progress_request_failed');
								}
								var progress = response.data;
								container.innerHTML = '<p><strong>' + progress.percent + '%</strong>（' + progress.completed + ' / ' + progress.total + ' 个内容项）</p>' +
									'<div style="max-width:480px;height:12px;background:#dcdcde"><div style="height:12px;width:' + progress.percent + '%;background:#2271b1"></div></div>' +
									'<p class="description">最近任务：' + progress.task_label + '。文章和页面：' + progress.posts_completed + ' / ' + progress.posts_total + '；分类和标签：' + progress.terms_completed + ' / ' + progress.terms_total + '。每 5 秒自动更新。</p>';
							})
							.catch(function () {
								container.innerHTML = '<p>暂时无法读取翻译进度。</p>';
							});
					}

					updateProgress();
					window.setInterval(updateProgress, 5000);
				}());
			</script>
		</div>
		<?php
	}

	public function queue_existing_translations() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( '您没有执行此操作的权限。' );
		}

		check_admin_referer( 'btranslate_queue_existing_translations' );
		$scope = isset( $_POST['scope'] ) ? sanitize_key( wp_unslash( $_POST['scope'] ) ) : 'all';
		if ( ! in_array( $scope, array( 'all', 'posts', 'terms' ), true ) ) {
			wp_die( '无效的翻译范围。' );
		}

		$scheduled = 0;
		$next_run  = time();
		$post_ids  = array();
		$term_ids  = array();
		$languages = BTRANSLATE_Settings::target_languages();

		if ( 'all' === $scope || 'posts' === $scope ) {
			$post_ids = get_posts(
				array(
					'post_type'      => array( 'post', 'page' ),
					'post_status'    => 'publish',
					'posts_per_page' => -1,
					'fields'         => 'ids',
				)
			);

		}

		if ( 'all' === $scope || 'terms' === $scope ) {
			$term_ids = get_terms(
				array(
					'taxonomy'   => array( 'category', 'post_tag' ),
					'hide_empty' => false,
					'fields'     => 'ids',
				)
			);
			if ( is_wp_error( $term_ids ) ) {
				$term_ids = array();
			}
		}

		$this->update_translation_task( $post_ids, $term_ids, $languages );

		foreach ( $post_ids as $post_id ) {
			foreach ( $languages as $target_language ) {
				$this->clear_scheduled_event( 'btranslate_process_translation', array( $post_id, $target_language, true ) );
				wp_schedule_single_event( $next_run, 'btranslate_process_translation', array( $post_id, $target_language, true ) );
				$next_run += 2;
				++$scheduled;
			}
		}

		foreach ( $term_ids as $term_id ) {
			foreach ( $languages as $target_language ) {
				$this->clear_scheduled_event( 'btranslate_process_term_translation', array( $term_id, $target_language, true ) );
				wp_schedule_single_event( $next_run, 'btranslate_process_term_translation', array( $term_id, $target_language, true ) );
				$next_run += 2;
				++$scheduled;
			}
		}

		wp_safe_redirect( add_query_arg( 'btranslate_queued', $scheduled, admin_url( 'options-general.php?page=btranslate' ) ) );
		exit;
	}

	public function clear_translation_cache() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( '您没有执行此操作的权限。' );
		}

		check_admin_referer( 'btranslate_clear_translation_cache' );
		$deleted = ( new BTRANSLATE_Translation_Store() )->clear();

		if ( false === $deleted ) {
			wp_die( '翻译缓存清除失败。' );
		}

		wp_clear_scheduled_hook( 'btranslate_process_translation' );
		wp_clear_scheduled_hook( 'btranslate_process_term_translation' );
		wp_clear_scheduled_hook( 'btranslate_process_seo_output_translation' );
		delete_option( 'btranslate_translation_task' );
		delete_option( 'btranslate_retranslation_batch' );

		wp_safe_redirect( add_query_arg( 'btranslate_cache_cleared', 1, admin_url( 'options-general.php?page=btranslate' ) ) );
		exit;
	}

	private function clear_scheduled_event( $hook, $args ) {
		while ( false !== ( $timestamp = wp_next_scheduled( $hook, $args ) ) ) {
			wp_unschedule_event( $timestamp, $hook, $args );
		}
	}

	public function add_plugin_settings_link( $links ) {
		array_unshift( $links, '<a href="' . esc_url( admin_url( 'options-general.php?page=btranslate' ) ) . '">设置</a>' );

		return $links;
	}

	public function translation_progress() {
		check_ajax_referer( 'btranslate_translation_progress' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'forbidden' ), 403 );
		}

		$task      = (array) get_option( 'btranslate_translation_task', array() );
		$post_ids  = array_values( array_filter( array_map( 'absint', (array) ( $task['post_ids'] ?? array() ) ) ) );
		$term_ids  = array_values( array_filter( array_map( 'absint', (array) ( $task['term_ids'] ?? array() ) ) ) );
		$languages = array_values( array_filter( array_map( 'sanitize_key', (array) ( $task['target_languages'] ?? array() ) ) ) );
		$started_at = isset( $task['started_at'] ) ? sanitize_text_field( $task['started_at'] ) : '';
		$counts     = ( new BTRANSLATE_Translation_Store() )->get_completed_item_counts( $languages, $post_ids, $term_ids, $started_at );

		$posts_total      = count( $post_ids ) * count( $languages );
		$terms_total      = count( $term_ids ) * count( $languages );
		$total            = $posts_total + $terms_total;
		$posts_completed  = min( $counts['posts'], $posts_total );
		$terms_completed  = min( $counts['terms'], $terms_total );
		$completed        = $posts_completed + $terms_completed;
		$percent          = $total ? (int) floor( ( $completed / $total ) * 100 ) : 100;

		wp_send_json_success(
			array(
				'posts_completed' => $posts_completed,
				'posts_total'     => $posts_total,
				'terms_completed' => $terms_completed,
				'terms_total'     => $terms_total,
				'completed'       => $completed,
				'total'           => $total,
				'percent'         => $percent,
				'batch_started_at' => $started_at,
				'task_label'      => $this->translation_task_label( $post_ids, $term_ids ),
			)
		);
	}

	public function add_translation_column( $columns ) {
		$columns['btranslate_translation'] = '翻译状态';

		return $columns;
	}

	public function render_translation_column( $column, $post_id ) {
		if ( 'btranslate_translation' !== $column ) {
			return;
		}

		$store = new BTRANSLATE_Translation_Store();

		foreach ( BTRANSLATE_Settings::target_languages() as $target_language ) {
			$status      = $store->get_post_language_status( $post_id, $target_language );
			$translated  = ! empty( $status['translated_fields'] );
			$button_text = $translated ? '重新翻译' : '翻译';
			$action_url  = wp_nonce_url(
				add_query_arg(
					array(
						'action'   => 'btranslate_translate_post',
						'post_id'  => $post_id,
						'language' => $target_language,
						'force'    => $translated ? '1' : '0',
					),
					admin_url( 'admin-post.php' )
				),
				'btranslate_translate_post_' . $post_id . '_' . $target_language
			);

			echo '<p><strong>' . esc_html( strtoupper( $target_language ) ) . '</strong>: ';
			echo $translated ? '<span style="color:#008a20">已翻译</span>' : '<span>未翻译</span>';
			if ( $translated && ! empty( $status['last_translated_at'] ) ) {
				echo '<br><small>上次翻译：' . esc_html( get_date_from_gmt( $status['last_translated_at'], 'Y-m-d H:i' ) ) . '</small>';
			}
			echo '<br><a class="button button-small" href="' . esc_url( $action_url ) . '">' . esc_html( $button_text ) . '</a></p>';
		}
	}

	public function queue_post_translation() {
		$post_id         = isset( $_GET['post_id'] ) ? absint( $_GET['post_id'] ) : 0;
		$target_language = isset( $_GET['language'] ) ? sanitize_key( wp_unslash( $_GET['language'] ) ) : '';
		$force_refresh   = ! empty( $_GET['force'] );

		if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) || ! BTRANSLATE_Settings::is_supported_language( $target_language ) || $target_language === BTRANSLATE_Settings::get()['source_language'] ) {
			wp_die( '无效的翻译请求。' );
		}

		check_admin_referer( 'btranslate_translate_post_' . $post_id . '_' . $target_language );
		$this->update_translation_task( array( $post_id ), array(), array( $target_language ) );
		wp_schedule_single_event( time() + 5, 'btranslate_process_translation', array( $post_id, $target_language, $force_refresh ) );

		wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url( 'edit.php?post_type=' . get_post_type( $post_id ) ) );
		exit;
	}

	private function update_translation_task( $post_ids, $term_ids, $target_languages ) {
		update_option(
			'btranslate_translation_task',
			array(
				'started_at'       => current_time( 'mysql', true ),
				'post_ids'         => array_values( array_filter( array_map( 'absint', (array) $post_ids ) ) ),
				'term_ids'         => array_values( array_filter( array_map( 'absint', (array) $term_ids ) ) ),
				'target_languages' => array_values( array_filter( array_map( 'sanitize_key', (array) $target_languages ) ) ),
			),
			false
		);
	}

	private function translation_task_label( $post_ids, $term_ids ) {
		if ( ! empty( $post_ids ) && ! empty( $term_ids ) ) {
			return '全量翻译';
		}

		if ( 1 === count( $post_ids ) ) {
			return '单篇文章翻译';
		}

		if ( ! empty( $post_ids ) ) {
			return '文章和页面翻译';
		}

		if ( ! empty( $term_ids ) ) {
			return '分类和标签翻译';
		}

		return '暂无翻译任务';
	}
}