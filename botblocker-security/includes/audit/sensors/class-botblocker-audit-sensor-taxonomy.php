<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BotBlockerAuditSensorTaxonomy {

	public static function register(): void {
		add_action( 'created_term', array( self::class, 'onCreatedTerm' ), 10, 3 );
		add_action( 'edited_term', array( self::class, 'onEditedTerm' ), 10, 3 );
		add_action( 'delete_term', array( self::class, 'onDeleteTerm' ), 10, 4 );
	}

	public static function onCreatedTerm( $term_id, $tt_id, $taxonomy ): void {
		unset( $tt_id );
		BotBlockerAuditLogger::record(
			BotBlockerAuditEvents::TAXONOMY_TERM_CREATED,
			array(
				'object_id' => (int) $term_id,
				'data'      => array(
					'taxonomy' => (string) $taxonomy,
				),
			)
		);
	}

	public static function onEditedTerm( $term_id, $tt_id, $taxonomy ): void {
		unset( $tt_id );
		BotBlockerAuditLogger::record(
			BotBlockerAuditEvents::TAXONOMY_TERM_UPDATED,
			array(
				'object_id' => (int) $term_id,
				'data'      => array(
					'taxonomy' => (string) $taxonomy,
				),
			)
		);
	}

	public static function onDeleteTerm( $term_id, $tt_id, $taxonomy, $deleted_term ): void {
		unset( $tt_id, $deleted_term );
		BotBlockerAuditLogger::record(
			BotBlockerAuditEvents::TAXONOMY_TERM_DELETED,
			array(
				'object_id' => (int) $term_id,
				'data'      => array(
					'taxonomy' => (string) $taxonomy,
				),
			)
		);
	}
}
