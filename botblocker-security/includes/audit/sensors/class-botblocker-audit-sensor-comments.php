<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BotBlockerAuditSensorComments {

	public static function register(): void {
		add_action( 'edit_comment', array( self::class, 'onEditComment' ), 10, 2 );
		add_action( 'transition_comment_status', array( self::class, 'onTransitionCommentStatus' ), 10, 3 );
		add_action( 'deleted_comment', array( self::class, 'onDeletedComment' ), 10, 2 );
	}

	public static function onEditComment( $comment_id, $commentdata ): void {
		unset( $commentdata );
		BotBlockerAuditLogger::record(
			BotBlockerAuditEvents::COMMENT_UPDATED,
			array(
				'object_id' => (int) $comment_id,
			)
		);
	}

	public static function onTransitionCommentStatus( $new_status, $old_status, $comment ): void {
		if ( ! $comment instanceof WP_Comment ) {
			return;
		}

		BotBlockerAuditLogger::record(
			BotBlockerAuditEvents::COMMENT_STATUS_CHANGED,
			array(
				'object_id' => (int) $comment->comment_ID,
				'data'      => array(
					'from' => (string) $old_status,
					'to'   => (string) $new_status,
				),
			)
		);
	}

	public static function onDeletedComment( $comment_id, $comment ): void {
		unset( $comment );
		BotBlockerAuditLogger::record(
			BotBlockerAuditEvents::COMMENT_DELETED,
			array(
				'object_id' => (int) $comment_id,
			)
		);
	}
}
