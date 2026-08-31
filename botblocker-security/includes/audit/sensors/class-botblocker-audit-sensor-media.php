<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BotBlockerAuditSensorMedia {

	public static function register(): void {
		add_action( 'add_attachment', array( self::class, 'onAddAttachment' ), 10, 1 );
		add_action( 'edit_attachment', array( self::class, 'onEditAttachment' ), 10, 1 );
		add_action( 'delete_attachment', array( self::class, 'onDeleteAttachment' ), 10, 1 );
	}

	public static function onAddAttachment( $post_id ): void {
		$post = get_post( $post_id );
		if ( ! $post instanceof WP_Post ) {
			return;
		}

		BotBlockerAuditLogger::record(
			BotBlockerAuditEvents::MEDIA_CREATED,
			array(
				'object_id' => (int) $post_id,
				'data'      => array(
					'title' => $post->post_title,
					'mime'  => $post->post_mime_type,
				),
			)
		);
	}

	public static function onEditAttachment( $post_id ): void {
		$post = get_post( $post_id );
		if ( ! $post instanceof WP_Post ) {
			return;
		}

		BotBlockerAuditLogger::record(
			BotBlockerAuditEvents::MEDIA_UPDATED,
			array(
				'object_id' => (int) $post_id,
				'data'      => array(
					'title' => $post->post_title,
				),
			)
		);
	}

	public static function onDeleteAttachment( $post_id ): void {
		BotBlockerAuditLogger::record(
			BotBlockerAuditEvents::MEDIA_DELETED,
			array(
				'object_id' => (int) $post_id,
			)
		);
	}
}
