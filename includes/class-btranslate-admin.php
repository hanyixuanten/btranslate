<?php

defined( 'ABSPATH' ) || exit;

class BTRANSLATE_Admin {
	public function register() {
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_settings_assets' ) );
		add_action( 'admin_post_btranslate_queue_existing_translations', array( $this, 'queue_existing_translations' ) );
		add_action( 'admin_post_btranslate_clear_translation_cache', array( $this, 'clear_translation_cache' ) );
		add_action( 'admin_post_btranslate_translate_post', array( $this, 'queue_post_translation' ) );
		add_action( 'admin_post_btranslate_retry_failed_translation', array( $this, 'retry_failed_translation' ) );
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

	public function enqueue_settings_assets( $hook_suffix ) {
		if ( 'settings_page_btranslate' !== $hook_suffix ) {
			return;
		}

		wp_enqueue_script(
			'btranslate-admin',
			plugins_url( 'assets/js/btranslate-admin.js', BTRANSLATE_FILE ),
			array(),
			BTRANSLATE_VERSION,
			true
		);
		wp_localize_script(
			'btranslate-admin',
			'btranslateAdmin',
			array(
				'progressUrl'   => admin_url( 'admin-ajax.php' ),
				'progressNonce' => wp_create_nonce( 'btranslate_translation_progress' ),
				'i18n'          => array(
					'contentItems'  => __( '(%1$s / %2$s content items)', 'btranslate' ),
					'latestTask'    => __( 'Latest task: %1$s. Posts and pages: %2$s / %3$s; categories and tags: %4$s / %5$s. Updates automatically every 5 seconds.', 'btranslate' ),
					'failedItems'   => __( 'Failed translations', 'btranslate' ),
					'retranslate'   => __( 'Retranslate', 'btranslate' ),
					'progressError' => __( 'Unable to load translation progress at this time.', 'btranslate' ),
				),
			)
		);
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
					<tr><th scope="row"><label for="btranslate-source-language"><?php esc_html_e( 'Source language', 'btranslate' ); ?></label></th><td><input id="btranslate-source-language" name="btranslate_settings[source_language]" type="text" value="<?php echo esc_attr( $settings['source_language'] ); ?>" /><p class="description"><?php esc_html_e( 'Use a Baidu Translate language code, for example zh.', 'btranslate' ); ?></p></td></tr>
					<tr><th scope="row"><label for="btranslate-target-languages"><?php esc_html_e( 'Target languages', 'btranslate' ); ?></label></th><td><input id="btranslate-target-languages" name="btranslate_settings[target_languages]" type="text" value="<?php echo esc_attr( implode( ',', $settings['target_languages'] ) ); ?>" class="regular-text" /><p class="description"><?php esc_html_e( 'Separate multiple language codes with commas, for example en,ja.', 'btranslate' ); ?></p></td></tr>
					<tr><th scope="row"><?php esc_html_e( 'URL mode', 'btranslate' ); ?></th><td><fieldset id="btranslate-routing-mode"><label><input type="checkbox" name="btranslate_settings[routing_mode][]" value="subdirectory" <?php checked( in_array( 'subdirectory', $settings['routing_mode'], true ) ); ?> /> <?php esc_html_e( 'Subdirectory', 'btranslate' ); ?></label><br /><label><input type="checkbox" name="btranslate_settings[routing_mode][]" value="domain" <?php checked( in_array( 'domain', $settings['routing_mode'], true ) ); ?> /> <?php esc_html_e( 'Domain', 'btranslate' ); ?></label></fieldset></td></tr>
					<tr id="btranslate-domain-bindings-row"<?php echo in_array( 'domain', $settings['routing_mode'], true ) ? '' : ' style="display:none"'; ?>><th scope="row"><label for="btranslate-domain-bindings"><?php esc_html_e( 'Domain bindings', 'btranslate' ); ?></label></th><td><textarea id="btranslate-domain-bindings" name="btranslate_settings[domain_bindings]" rows="4" class="large-text" placeholder="en.example.com=en"><?php echo esc_textarea( implode( "\n", $bindings ) ); ?></textarea><p class="description"><?php esc_html_e( 'Enter one domain=language code pair per line, for example en.example.com=en. Do not include https://, a path, or a port.', 'btranslate' ); ?></p></td></tr>
					<tr><th scope="row"><label for="btranslate-baidu-app-id"><?php esc_html_e( 'Baidu application ID', 'btranslate' ); ?></label></th><td><input id="btranslate-baidu-app-id" name="btranslate_settings[baidu_app_id]" type="text" value="<?php echo esc_attr( $settings['baidu_app_id'] ); ?>" class="regular-text" /></td></tr>
					<tr><th scope="row"><label for="btranslate-baidu-secret-key"><?php esc_html_e( 'Baidu secret key', 'btranslate' ); ?></label></th><td><input id="btranslate-baidu-secret-key" name="btranslate_settings[baidu_secret_key]" type="password" value="<?php echo esc_attr( $settings['baidu_secret_key'] ); ?>" class="regular-text" autocomplete="new-password" /></td></tr>
					<tr><th scope="row"><?php esc_html_e( 'Request logging', 'btranslate' ); ?></th><td><label for="btranslate-log-requests"><input id="btranslate-log-requests" name="btranslate_settings[log_requests]" type="checkbox" value="1" <?php checked( $settings['log_requests'] ); ?> /> <?php esc_html_e( 'Log each Baidu Translate request', 'btranslate' ); ?></label><p class="description"><?php esc_html_e( 'When enabled, fires the btranslate_translation_request_logged action with the language, field, text fingerprint, length, and result status. Credentials, source text, translated text, and full API responses are never included.', 'btranslate' ); ?></p></td></tr>
				</table>
				<?php submit_button(); ?>
				<p class="description"><?php esc_html_e( 'After saving settings for the first time, manually run "Retranslate all content" below.', 'btranslate' ); ?></p>
			</form>
			<hr />
			<h2><?php esc_html_e( 'Retranslate content', 'btranslate' ); ?></h2>
			<p><?php esc_html_e( 'Queue all published posts, pages, categories, and tags for translation again.', 'btranslate' ); ?></p>
			<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" data-btranslate-confirm="<?php echo esc_attr__( 'This will retranslate all content and use your API quota. Continue?', 'btranslate' ); ?>">
				<input type="hidden" name="action" value="btranslate_queue_existing_translations" />
				<?php wp_nonce_field( 'btranslate_queue_existing_translations' ); ?>
				<?php submit_button( __( 'Retranslate all content', 'btranslate' ), 'secondary', 'submit', false ); ?>
			</form>
			<p><?php esc_html_e( 'You can also retranslate a single content type.', 'btranslate' ); ?></p>
			<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" style="display:inline-block;margin-right:8px" data-btranslate-confirm="<?php echo esc_attr__( 'This will retranslate all posts and pages and use your API quota. Continue?', 'btranslate' ); ?>">
				<input type="hidden" name="action" value="btranslate_queue_existing_translations" />
				<input type="hidden" name="scope" value="posts" />
				<?php wp_nonce_field( 'btranslate_queue_existing_translations' ); ?>
				<?php submit_button( __( 'Translate all posts', 'btranslate' ), 'secondary', 'submit', false ); ?>
			</form>
			<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" style="display:inline-block" data-btranslate-confirm="<?php echo esc_attr__( 'This will retranslate all categories and tags and use your API quota. Continue?', 'btranslate' ); ?>">
				<input type="hidden" name="action" value="btranslate_queue_existing_translations" />
				<input type="hidden" name="scope" value="terms" />
				<?php wp_nonce_field( 'btranslate_queue_existing_translations' ); ?>
				<?php submit_button( __( 'Translate all categories and tags', 'btranslate' ), 'secondary', 'submit', false ); ?>
			</form>
			<hr />
			<h2><?php esc_html_e( 'Translation cache', 'btranslate' ); ?></h2>
			<p><?php esc_html_e( 'After clearing the cache, the front end temporarily displays source content. Baidu Translate API calls resume when content is translated again.', 'btranslate' ); ?></p>
			<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" data-btranslate-confirm="<?php echo esc_attr__( 'This will delete all saved translations, cancel pending translation tasks, and reset progress. Continue?', 'btranslate' ); ?>">
				<input type="hidden" name="action" value="btranslate_clear_translation_cache" />
				<?php wp_nonce_field( 'btranslate_clear_translation_cache' ); ?>
				<?php submit_button( __( 'Clear translation cache', 'btranslate' ), 'delete', 'submit', false ); ?>
			</form>
			<hr />
			<h2><?php esc_html_e( 'Translation progress', 'btranslate' ); ?></h2>
			<div id="btranslate-translation-progress" aria-live="polite">
				<p><?php esc_html_e( 'Loading translation progress...', 'btranslate' ); ?></p>
			</div>
		</div>
		<?php
	}

	public function queue_existing_translations() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'btranslate' ) );
		}

		check_admin_referer( 'btranslate_queue_existing_translations' );
		$scope = isset( $_POST['scope'] ) ? sanitize_key( wp_unslash( $_POST['scope'] ) ) : 'all';
		if ( ! in_array( $scope, array( 'all', 'posts', 'terms' ), true ) ) {
			wp_die( esc_html__( 'Invalid translation scope.', 'btranslate' ) );
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
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'btranslate' ) );
		}

		check_admin_referer( 'btranslate_clear_translation_cache' );
		$deleted = ( new BTRANSLATE_Translation_Store() )->clear();

		if ( false === $deleted ) {
			wp_die( esc_html__( 'Failed to clear the translation cache.', 'btranslate' ) );
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
		array_unshift( $links, '<a href="' . esc_url( admin_url( 'options-general.php?page=btranslate' ) ) . '">' . esc_html__( 'Settings', 'btranslate' ) . '</a>' );

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
		$store      = new BTRANSLATE_Translation_Store();
		$counts     = $store->get_completed_item_counts( $languages, $post_ids, $term_ids, $started_at );
		$failures   = $this->prepare_failed_items( $store->get_failed_items( $languages, $post_ids, $term_ids, $started_at ) );

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
				'failed_items'    => $failures,
			)
		);
	}

	public function add_translation_column( $columns ) {
		$columns['btranslate_translation'] = __( 'Translation status', 'btranslate' );

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
			$button_text = $translated ? __( 'Retranslate', 'btranslate' ) : __( 'Translate', 'btranslate' );
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
			echo $translated ? '<span style="color:#008a20">' . esc_html__( 'Translated', 'btranslate' ) . '</span>' : '<span>' . esc_html__( 'Not translated', 'btranslate' ) . '</span>';
			if ( $translated && ! empty( $status['last_translated_at'] ) ) {
				echo '<br><small>' . esc_html__( 'Last translated:', 'btranslate' ) . ' ' . esc_html( get_date_from_gmt( $status['last_translated_at'], 'Y-m-d H:i' ) ) . '</small>';
			}
			echo '<br><a class="button button-small" href="' . esc_url( $action_url ) . '">' . esc_html( $button_text ) . '</a></p>';
		}
	}

	public function queue_post_translation() {
		$post_id         = isset( $_GET['post_id'] ) ? absint( $_GET['post_id'] ) : 0;
		$target_language = isset( $_GET['language'] ) ? sanitize_key( wp_unslash( $_GET['language'] ) ) : '';
		$force_refresh   = ! empty( $_GET['force'] );

		if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) || ! BTRANSLATE_Settings::is_supported_language( $target_language ) || $target_language === BTRANSLATE_Settings::get()['source_language'] ) {
			wp_die( esc_html__( 'Invalid translation request.', 'btranslate' ) );
		}

		check_admin_referer( 'btranslate_translate_post_' . $post_id . '_' . $target_language );
		$this->update_translation_task( array( $post_id ), array(), array( $target_language ) );
		wp_schedule_single_event( time() + 5, 'btranslate_process_translation', array( $post_id, $target_language, $force_refresh ) );

		wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url( 'edit.php?post_type=' . get_post_type( $post_id ) ) );
		exit;
	}

	public function retry_failed_translation() {
		$item_type       = isset( $_GET['item_type'] ) ? sanitize_key( wp_unslash( $_GET['item_type'] ) ) : '';
		$item_id         = isset( $_GET['item_id'] ) ? absint( $_GET['item_id'] ) : 0;
		$target_language = isset( $_GET['language'] ) ? sanitize_key( wp_unslash( $_GET['language'] ) ) : '';

		if ( ! current_user_can( 'manage_options' ) || ! $item_id || ! in_array( $item_type, array( 'post', 'term' ), true ) || ! in_array( $target_language, BTRANSLATE_Settings::target_languages(), true ) ) {
			wp_die( esc_html__( 'Invalid translation request.', 'btranslate' ) );
		}

		check_admin_referer( 'btranslate_retry_failed_translation_' . $item_type . '_' . $item_id . '_' . $target_language );

		if ( 'post' === $item_type ) {
			wp_schedule_single_event( time() + 5, 'btranslate_process_translation', array( $item_id, $target_language, true ) );
		} else {
			wp_schedule_single_event( time() + 5, 'btranslate_process_term_translation', array( $item_id, $target_language, true ) );
		}

		wp_safe_redirect( admin_url( 'options-general.php?page=btranslate' ) );
		exit;
	}

	private function prepare_failed_items( $failures ) {
		$items = array();

		foreach ( (array) $failures as $failure ) {
			$context_parts  = explode( ':', $failure['item_context'], 2 );
			$item_type      = $context_parts[0] ?? '';
			$item_id        = isset( $context_parts[1] ) ? absint( $context_parts[1] ) : 0;
			$target_language = sanitize_key( $failure['target_language'] ?? '' );

			if ( ! $item_id || ! in_array( $item_type, array( 'post', 'term' ), true ) || '' === $target_language ) {
				continue;
			}

			if ( 'post' === $item_type ) {
				$name = get_the_title( $item_id );
			} else {
				$term = get_term( $item_id );
				$name = $term && ! is_wp_error( $term ) ? $term->name : '';
			}

			if ( '' === $name ) {
				$name = sprintf( __( 'Content #%d', 'btranslate' ), $item_id );
			}

			$retry_url = wp_nonce_url(
				add_query_arg(
					array(
						'action'    => 'btranslate_retry_failed_translation',
						'item_type' => $item_type,
						'item_id'   => $item_id,
						'language'  => $target_language,
					),
					admin_url( 'admin-post.php' )
				),
				'btranslate_retry_failed_translation_' . $item_type . '_' . $item_id . '_' . $target_language
			);

			$items[] = array(
				'name'      => $name,
				'language'  => strtoupper( $target_language ),
				'retry_url' => $retry_url,
			);
		}

		return $items;
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
			return __( 'Full translation', 'btranslate' );
		}

		if ( 1 === count( $post_ids ) ) {
			return __( 'Single post translation', 'btranslate' );
		}

		if ( ! empty( $post_ids ) ) {
			return __( 'Posts and pages translation', 'btranslate' );
		}

		if ( ! empty( $term_ids ) ) {
			return __( 'Categories and tags translation', 'btranslate' );
		}

		return __( 'No translation task', 'btranslate' );
	}
}