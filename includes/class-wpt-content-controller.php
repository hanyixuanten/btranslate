<?php

defined( 'ABSPATH' ) || exit;

class WPT_Content_Controller {
	private $store;
	private $router;

	public function __construct( WPT_Translation_Store $store, WPT_Language_Router $router ) {
		$this->store  = $store;
		$this->router = $router;
	}

	public function register() {
		add_action( 'save_post', array( $this, 'schedule_post_translation' ), 20, 3 );
		add_action( 'wpt_process_translation', array( $this, 'translate_post' ), 10, 3 );
		add_filter( 'the_title', array( $this, 'translate_title' ), 20, 2 );
		add_filter( 'the_content', array( $this, 'translate_content' ), 20 );
		add_filter( 'get_the_excerpt', array( $this, 'translate_excerpt' ), 20, 2 );
		add_filter( 'wp_get_attachment_image_attributes', array( $this, 'translate_image_alt' ), 20, 2 );
		add_filter( 'single_term_title', array( $this, 'translate_term_title' ), 20, 2 );
		add_filter( 'term_description', array( $this, 'translate_term_description' ), 20, 3 );
		add_filter( 'wpseo_title', array( $this, 'translate_seo_value' ), 20 );
		add_filter( 'wpseo_metadesc', array( $this, 'translate_seo_value' ), 20 );
		add_filter( 'rank_math/frontend/title', array( $this, 'translate_seo_value' ), 20 );
		add_filter( 'rank_math/frontend/description', array( $this, 'translate_seo_value' ), 20 );
		add_filter( 'post_link', array( $this, 'localize_permalink' ), 20 );
		add_filter( 'page_link', array( $this, 'localize_permalink' ), 20 );
		add_filter( 'post_type_link', array( $this, 'localize_permalink' ), 20 );
		add_filter( 'term_link', array( $this, 'localize_permalink' ), 20 );
	}

	public function schedule_post_translation( $post_id, $post, $update ) {
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) || ! in_array( $post->post_type, array( 'post', 'page' ), true ) ) {
			return;
		}

		foreach ( WPT_Settings::target_languages() as $target_language ) {
			if ( ! wp_next_scheduled( 'wpt_process_translation', array( $post_id, $target_language ) ) ) {
				wp_schedule_single_event( time() + 30, 'wpt_process_translation', array( $post_id, $target_language ) );
			}
		}
	}

	public function translate_post( $post_id, $target_language, $force_refresh = false ) {
		$post     = get_post( $post_id );
		$settings = WPT_Settings::get();

		if ( ! $post || ! WPT_Settings::is_supported_language( $target_language ) ) {
			return;
		}

		$service = new WPT_Translation_Service(
			$this->store,
			new WPT_Baidu_Provider( $settings['baidu_app_id'], $settings['baidu_secret_key'] )
		);

		$fields = array(
			'post_title'   => $post->post_title,
			'post_content' => $post->post_content,
			'post_excerpt' => $post->post_excerpt,
		);
		$seo_meta_keys = array(
			'_yoast_wpseo_title',
			'_yoast_wpseo_metadesc',
			'rank_math_title',
			'rank_math_description',
		);

		foreach ( $seo_meta_keys as $meta_key ) {
			$meta_value = get_post_meta( $post_id, $meta_key, true );
			if ( is_string( $meta_value ) && '' !== $meta_value ) {
				$fields[ 'meta:' . $meta_key ] = $meta_value;
			}
		}

		foreach ( $fields as $field => $value ) {
			if ( '' !== $value ) {
				$service->get_or_translate( $value, $settings['source_language'], $target_language, 'post:' . $post_id . ':' . $field, $force_refresh );
			}
		}

		$this->translate_attachment_alt_text( $post_id, $service, $settings, $target_language, $force_refresh );
		$this->translate_terms( $post_id, $service, $settings, $target_language, $force_refresh );
	}

	public function translate_title( $title, $post_id ) {
		return $this->translated_value( $title, 'post:' . $post_id . ':post_title' );
	}

	public function translate_content( $content ) {
		return $this->translated_value( $content, 'post:' . get_the_ID() . ':post_content' );
	}

	public function translate_excerpt( $excerpt, $post ) {
		return $this->translated_value( $excerpt, 'post:' . $post->ID . ':post_excerpt' );
	}

	public function translate_image_alt( $attributes, $attachment ) {
		if ( empty( $attributes['alt'] ) ) {
			return $attributes;
		}

		$attributes['alt'] = $this->translated_value( $attributes['alt'], 'attachment:' . $attachment->ID . ':alt' );

		return $attributes;
	}

	public function translate_term_title( $term_name, $term ) {
		return $this->translated_value( $term_name, 'term:' . $term->term_id . ':name' );
	}

	public function translate_term_description( $description, $term_id, $taxonomy ) {
		return $this->translated_value( $description, 'term:' . $term_id . ':description' );
	}

	public function translate_seo_value( $value ) {
		$post_id = get_queried_object_id();

		if ( ! $post_id || '' === $value ) {
			return $value;
		}

		$seo_meta_keys = array(
			'_yoast_wpseo_title',
			'_yoast_wpseo_metadesc',
			'rank_math_title',
			'rank_math_description',
		);

		foreach ( $seo_meta_keys as $meta_key ) {
			if ( $value === get_post_meta( $post_id, $meta_key, true ) ) {
				return $this->translated_value( $value, 'post:' . $post_id . ':meta:' . $meta_key );
			}
		}

		return $value;
	}

	public function localize_permalink( $url ) {
		return $this->router->localized_url( $url );
	}

	private function translated_value( $source_value, $context ) {
		$settings        = WPT_Settings::get();
		$target_language = $this->router->current_language();

		if ( $target_language === $settings['source_language'] || '' === $source_value ) {
			return $source_value;
		}

		$provider     = new WPT_Baidu_Provider( $settings['baidu_app_id'], $settings['baidu_secret_key'] );
		$identity_key = WPT_Translation_Identity::key( $source_value, $settings['source_language'], $target_language, $context, $provider->get_version() );
		$translation  = $this->store->find_valid( $identity_key );

		return ! empty( $translation['translated_value'] ) ? $translation['translated_value'] : $source_value;
	}

	private function translate_attachment_alt_text( $post_id, $service, $settings, $target_language, $force_refresh ) {
		$attachments = get_attached_media( 'image', $post_id );

		foreach ( $attachments as $attachment ) {
			$alt_text = get_post_meta( $attachment->ID, '_wp_attachment_image_alt', true );
			if ( is_string( $alt_text ) && '' !== $alt_text ) {
				$service->get_or_translate( $alt_text, $settings['source_language'], $target_language, 'attachment:' . $attachment->ID . ':alt', $force_refresh );
			}
		}
	}

	private function translate_terms( $post_id, $service, $settings, $target_language, $force_refresh ) {
		$terms = get_the_terms( $post_id, array( 'category', 'post_tag' ) );

		if ( ! is_array( $terms ) ) {
			return;
		}

		foreach ( $terms as $term ) {
			if ( '' !== $term->name ) {
				$service->get_or_translate( $term->name, $settings['source_language'], $target_language, 'term:' . $term->term_id . ':name', $force_refresh );
			}
			if ( '' !== $term->description ) {
				$service->get_or_translate( $term->description, $settings['source_language'], $target_language, 'term:' . $term->term_id . ':description', $force_refresh );
			}
		}
	}
}