<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BotBlockerAuditSensorContent {

	/** @var array<int, array<string, mixed>> */
	private static $delete_cache = array();

	/** @var array<int, array<string>> */
	private static $meta_keys = array();

	public static function register(): void {
		add_action( 'wp_insert_post', array( self::class, 'onInsertPost' ), 20, 3 );
		add_action( 'post_updated', array( self::class, 'onPostUpdated' ), 10, 3 );
		add_action( 'trashed_post', array( self::class, 'onTrashedPost' ), 10, 1 );
		add_action( 'untrashed_post', array( self::class, 'onUntrashedPost' ), 10, 1 );
		add_action( 'before_delete_post', array( self::class, 'onBeforeDeletePost' ), 10, 1 );
		add_action( 'deleted_post', array( self::class, 'onDeletedPost' ), 10, 1 );
		add_action( 'added_post_meta', array( self::class, 'onPostMetaChanged' ), 10, 4 );
		add_action( 'updated_post_meta', array( self::class, 'onPostMetaChanged' ), 10, 4 );
		add_action( 'deleted_post_meta', array( self::class, 'onPostMetaChanged' ), 10, 4 );
		add_action( 'shutdown', array( self::class, 'flushMetaChanges' ), 20 );
	}

	private static function shouldSkipPost( $post ): bool {
		if ( ! $post instanceof WP_Post ) {
			return true;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return true;
		}
		if ( $post->post_type === 'revision' || $post->post_type === 'nav_menu_item' ) {
			return true;
		}
		if ( wp_is_post_revision( $post ) || wp_is_post_autosave( $post ) ) {
			return true;
		}
		if ( $post->post_status === 'auto-draft' ) {
			return true;
		}
		if ( $post->post_type === 'attachment' ) {
			return true;
		}
		return false;
	}

	public static function onInsertPost( $post_id, $post, $update ): void {
		if ( $update ) {
			return;
		}
		if ( ! $post instanceof WP_Post || self::shouldSkipPost( $post ) ) {
			return;
		}

		BotBlockerAuditLogger::record(
			BotBlockerAuditEvents::CONTENT_POST_CREATED,
			array(
				'object_id' => (int) $post_id,
				'data'      => array(
					'post_type' => $post->post_type,
					'title'     => $post->post_title,
					'status'    => $post->post_status,
				),
			)
		);
	}

	public static function onPostUpdated( $post_id, $post_after, $post_before ): void {
		if ( ! $post_after instanceof WP_Post || ! $post_before instanceof WP_Post || self::shouldSkipPost( $post_after ) ) {
			return;
		}

		$changes = array();
		if ( $post_after->post_title !== $post_before->post_title ) {
			$changes['title'] = array( 'from' => $post_before->post_title, 'to' => $post_after->post_title );
		}
		if ( $post_after->post_status !== $post_before->post_status ) {
			$changes['status'] = array( 'from' => $post_before->post_status, 'to' => $post_after->post_status );
		}
		if ( $post_after->post_content !== $post_before->post_content ) {
			$changes['content'] = true;
		}

		if ( ! $changes ) {
			return;
		}

		BotBlockerAuditLogger::record(
			BotBlockerAuditEvents::CONTENT_POST_UPDATED,
			array(
				'object_id' => (int) $post_id,
				'data'      => array(
					'post_type' => $post_after->post_type,
					'changes'   => $changes,
				),
			)
		);
	}

	public static function onTrashedPost( $post_id ): void {
		$post = get_post( $post_id );
		if ( ! $post instanceof WP_Post || self::shouldSkipPost( $post ) ) {
			return;
		}

		BotBlockerAuditLogger::record(
			BotBlockerAuditEvents::CONTENT_POST_TRASHED,
			array(
				'object_id' => (int) $post_id,
				'data'      => array(
					'post_type' => $post->post_type,
					'title'     => $post->post_title,
				),
			)
		);
	}

	public static function onUntrashedPost( $post_id ): void {
		$post = get_post( $post_id );
		if ( ! $post instanceof WP_Post || self::shouldSkipPost( $post ) ) {
			return;
		}

		BotBlockerAuditLogger::record(
			BotBlockerAuditEvents::CONTENT_POST_UNTRASHED,
			array(
				'object_id' => (int) $post_id,
				'data'      => array(
					'post_type' => $post->post_type,
					'title'     => $post->post_title,
				),
			)
		);
	}

	public static function onBeforeDeletePost( $post_id ): void {
		$post = get_post( $post_id );
		if ( ! $post instanceof WP_Post || self::shouldSkipPost( $post ) ) {
			return;
		}

		self::$delete_cache[ (int) $post_id ] = array(
			'post_type' => $post->post_type,
			'title'     => $post->post_title,
		);
	}

	public static function onDeletedPost( $post_id ): void {
		$post_id = (int) $post_id;
		if ( ! isset( self::$delete_cache[ $post_id ] ) ) {
			return;
		}

		$context = self::$delete_cache[ $post_id ];
		unset( self::$delete_cache[ $post_id ] );

		BotBlockerAuditLogger::record(
			BotBlockerAuditEvents::CONTENT_POST_DELETED,
			array(
				'object_id' => $post_id,
				'data'      => $context,
			)
		);
	}

	public static function onPostMetaChanged( $meta_id, $object_id, $meta_key, $_meta_value ): void {
		unset( $meta_id, $_meta_value );
		$post_id = (int) $object_id;
		$post    = get_post( $post_id );
		if ( ! $post instanceof WP_Post || self::shouldSkipPost( $post ) ) {
			return;
		}

		if ( ! is_string( $meta_key ) || $meta_key === '' ) {
			return;
		}
		if ( strpos( $meta_key, '_' ) === 0 ) {
			return;
		}

		if ( ! isset( self::$meta_keys[ $post_id ] ) ) {
			self::$meta_keys[ $post_id ] = array();
		}
		self::$meta_keys[ $post_id ][ $meta_key ] = true;
	}

	public static function discardBufferedMetaChanges(): void {
		self::$meta_keys = array();
	}

	public static function flushMetaChanges(): void {
		foreach ( self::$meta_keys as $post_id => $keys_map ) {
			$keys = array_keys( $keys_map );
			if ( ! $keys ) {
				continue;
			}
			BotBlockerAuditLogger::record(
				BotBlockerAuditEvents::CONTENT_POST_META_UPDATED,
				array(
					'object_id' => (int) $post_id,
					'data'      => array(
						'keys' => $keys,
					),
					'dedup'     => implode( ',', $keys ),
				)
			);
		}
		self::$meta_keys = array();
	}
}
