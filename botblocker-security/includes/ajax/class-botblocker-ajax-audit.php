<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BotBlockerAjaxAudit {

	private const EXPORT_BATCH = BotBlockerAuditRepository::PAGE_LENGTH_MAX;

	public static function handleGetAuditLog(): void {
		check_ajax_referer( 'botblocker_nonce', 'nonce' );
		if ( ! current_user_can( BotBlockerMultisite::canManage() ) ) {
			wp_send_json_error( __( 'Unauthorized.', 'botblocker-security' ) );
		}

		$start  = isset( $_POST['start'] ) ? absint( wp_unslash( $_POST['start'] ) ) : 0;
		$length = isset( $_POST['length'] ) ? absint( wp_unslash( $_POST['length'] ) ) : 25;
		$draw   = isset( $_POST['draw'] ) ? absint( wp_unslash( $_POST['draw'] ) ) : 0;
		$search = isset( $_POST['search']['value'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['search']['value'] ) ) ) : '';

		$order = self::readOrder();

		$args = array(
			'start'       => $start,
			'length'      => $length,
			'search'      => $search,
			'orderby'     => $order['orderby'],
			'order'       => $order['dir'],
			'event_key'   => isset( $_POST['event_key'] ) ? sanitize_text_field( wp_unslash( $_POST['event_key'] ) ) : '',
			'category'    => isset( $_POST['category'] ) ? sanitize_text_field( wp_unslash( $_POST['category'] ) ) : '',
			'actor_id'    => isset( $_POST['actor_id'] ) ? absint( wp_unslash( $_POST['actor_id'] ) ) : 0,
			'role'        => isset( $_POST['role'] ) ? sanitize_text_field( wp_unslash( $_POST['role'] ) ) : '',
			'object_type' => isset( $_POST['object_type'] ) ? sanitize_text_field( wp_unslash( $_POST['object_type'] ) ) : '',
			'object_id'   => isset( $_POST['object_id'] ) ? absint( wp_unslash( $_POST['object_id'] ) ) : 0,
			'ip'          => isset( $_POST['ip'] ) ? sanitize_text_field( wp_unslash( $_POST['ip'] ) ) : '',
			'severity'    => isset( $_POST['severity'] ) ? absint( wp_unslash( $_POST['severity'] ) ) : 0,
			'context'     => isset( $_POST['context'] ) ? sanitize_text_field( wp_unslash( $_POST['context'] ) ) : '',
			'date_from'   => isset( $_POST['date_from'] ) ? absint( wp_unslash( $_POST['date_from'] ) ) : 0,
			'date_to'     => isset( $_POST['date_to'] ) ? absint( wp_unslash( $_POST['date_to'] ) ) : 0,
		);

		$result = BotBlockerAuditRepository::query( $args );
		$rows   = array();

		foreach ( $result['rows'] as $row ) {
			$rows[] = array(
				'time'           => self::formatTime( (int) $row['created_at'] ),
				'event_key'      => (string) $row['event_key'],
				'category'       => self::eventCategory( (string) $row['event_key'] ),
				'message'        => self::buildMessage( $row ),
				'severity'       => (int) $row['severity'],
				'severity_label' => BotBlockerAuditLogger::severityLabel( $row['severity'] ),
				'actor'          => self::resolveActorLabel( $row ),
				'role'           => (string) $row['actor_role'],
				'object_type'    => (string) $row['object_type'],
				'object_id'      => (string) $row['object_id'],
				'ip'             => (string) $row['ip'],
				'context'        => (string) $row['context'],
				'path'           => (string) $row['request_path'],
				'user_agent'     => isset( $row['user_agent'] ) ? (string) $row['user_agent'] : '',
				'data'           => self::formatDataPretty( (string) $row['data'] ),
			);
		}

		wp_send_json(
			array(
				'draw'            => $draw,
				'recordsTotal'    => BotBlockerAuditRepository::countAll(),
				'recordsFiltered' => $result['total'],
				'data'            => $rows,
			)
		);
	}

	public static function handleExportAuditLog(): void {
		check_ajax_referer( 'botblocker_nonce', 'nonce' );
		if ( ! current_user_can( BotBlockerMultisite::canManage() ) ) {
			wp_die( esc_html__( 'Unauthorized.', 'botblocker-security' ) );
		}

		$args = array(
			'search'      => isset( $_POST['search'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['search'] ) ) ) : '',
			'event_key'   => isset( $_POST['event_key'] ) ? sanitize_text_field( wp_unslash( $_POST['event_key'] ) ) : '',
			'category'    => isset( $_POST['category'] ) ? sanitize_text_field( wp_unslash( $_POST['category'] ) ) : '',
			'actor_id'    => isset( $_POST['actor_id'] ) ? absint( wp_unslash( $_POST['actor_id'] ) ) : 0,
			'role'        => isset( $_POST['role'] ) ? sanitize_text_field( wp_unslash( $_POST['role'] ) ) : '',
			'object_type' => isset( $_POST['object_type'] ) ? sanitize_text_field( wp_unslash( $_POST['object_type'] ) ) : '',
			'object_id'   => isset( $_POST['object_id'] ) ? absint( wp_unslash( $_POST['object_id'] ) ) : 0,
			'ip'          => isset( $_POST['ip'] ) ? sanitize_text_field( wp_unslash( $_POST['ip'] ) ) : '',
			'severity'    => isset( $_POST['severity'] ) ? absint( wp_unslash( $_POST['severity'] ) ) : 0,
			'context'     => isset( $_POST['context'] ) ? sanitize_text_field( wp_unslash( $_POST['context'] ) ) : '',
			'date_from'   => isset( $_POST['date_from'] ) ? absint( wp_unslash( $_POST['date_from'] ) ) : 0,
			'date_to'     => isset( $_POST['date_to'] ) ? absint( wp_unslash( $_POST['date_to'] ) ) : 0,
		);

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=bbcs-audit-log.csv' );

		$out = fopen( 'php://output', 'w' );
		if ( ! $out ) {
			wp_die( esc_html__( 'Export failed.', 'botblocker-security' ) );
		}

		self::writeExportCsv( $out, $args );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		fclose( $out );
		exit;
	}

	/**
	 * Walks the same page size the listing uses, so the loop never asks for more
	 * rows than query() will return.
	 *
	 * @param resource             $handle
	 * @param array<string, mixed> $args
	 */
	public static function writeExportCsv( $handle, array $args ): int {
		self::putCsvRow(
			$handle,
			array( 'created_at', 'event_key', 'message', 'severity', 'actor_user_id', 'actor_username', 'actor_role', 'object_type', 'object_id', 'ip', 'context', 'request_path', 'user_agent', 'data' )
		);

		$offset  = 0;
		$written = 0;
		do {
			$args['start']  = $offset;
			$args['length'] = self::EXPORT_BATCH;
			$result         = BotBlockerAuditRepository::query( $args );
			$batch          = $result['rows'];
			foreach ( $batch as $row ) {
				self::putCsvRow(
					$handle,
					array(
						BotBlockerAuditRepository::sanitizeCsvCell( self::formatTime( (int) $row['created_at'] ) ),
						BotBlockerAuditRepository::sanitizeCsvCell( $row['event_key'] ),
						BotBlockerAuditRepository::sanitizeCsvCell( self::buildMessage( $row ) ),
						BotBlockerAuditRepository::sanitizeCsvCell( BotBlockerAuditLogger::severityLabel( $row['severity'] ) ),
						BotBlockerAuditRepository::sanitizeCsvCell( $row['actor_user_id'] ),
						BotBlockerAuditRepository::sanitizeCsvCell( self::resolveActorLabel( $row ) ),
						BotBlockerAuditRepository::sanitizeCsvCell( $row['actor_role'] ),
						BotBlockerAuditRepository::sanitizeCsvCell( $row['object_type'] ),
						BotBlockerAuditRepository::sanitizeCsvCell( $row['object_id'] ),
						BotBlockerAuditRepository::sanitizeCsvCell( $row['ip'] ),
						BotBlockerAuditRepository::sanitizeCsvCell( $row['context'] ),
						BotBlockerAuditRepository::sanitizeCsvCell( $row['request_path'] ),
						BotBlockerAuditRepository::sanitizeCsvCell( isset( $row['user_agent'] ) ? $row['user_agent'] : '' ),
						BotBlockerAuditRepository::sanitizeCsvCell( $row['data'] ),
					)
				);
				$written++;
			}
			$offset += count( $batch );
		} while ( count( $batch ) === self::EXPORT_BATCH );

		return $written;
	}

	/**
	 * DataTables sends the sorted column by index into its own columns list, so the
	 * index is resolved back to a field name before the repository whitelists it.
	 *
	 * @return array{orderby: string, dir: string}
	 */
	private static function readOrder(): array {
		$fallback = array( 'orderby' => 'created_at', 'dir' => 'DESC' );

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- checked by the caller.
		if ( ! isset( $_POST['order'][0]['column'] ) ) {
			return $fallback;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- checked by the caller.
		$index = absint( wp_unslash( $_POST['order'][0]['column'] ) );
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- checked by the caller.
		if ( ! isset( $_POST['columns'][ $index ]['data'] ) ) {
			return $fallback;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- checked by the caller.
		$field = sanitize_text_field( wp_unslash( $_POST['columns'][ $index ]['data'] ) );

		$map = array(
			'time'     => 'created_at',
			'severity' => 'severity',
			'message'  => 'event_key',
			'actor'    => 'actor_username',
			'ip'       => 'ip',
		);

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- checked by the caller.
		$dir = isset( $_POST['order'][0]['dir'] ) && strtolower( sanitize_text_field( wp_unslash( $_POST['order'][0]['dir'] ) ) ) === 'asc'
			? 'ASC'
			: 'DESC';

		return array(
			'orderby' => isset( $map[ $field ] ) ? $map[ $field ] : 'created_at',
			'dir'     => $dir,
		);
	}

	private static function putCsvRow( $handle, array $fields ): void {
		fputcsv( $handle, $fields, ',', '"', '' );
	}

	private static function formatTime( int $timestamp ): string {
		if ( $timestamp <= 0 ) {
			return '';
		}
		return (string) BotBlockerCompatibility::wpDate( 'Y-m-d H:i:s', $timestamp );
	}

	/**
	 * get_userdata() is a fallback only: it returns nothing once the user is deleted.
	 *
	 * @param array<string, mixed> $row
	 */
	private static function resolveActorLabel( array $row ): string {
		if ( ! empty( $row['actor_username'] ) ) {
			return (string) $row['actor_username'];
		}

		$actor_id = isset( $row['actor_user_id'] ) ? (int) $row['actor_user_id'] : 0;
		if ( $actor_id <= 0 ) {
			return '';
		}

		$user = get_userdata( $actor_id );

		return $user instanceof WP_User ? $user->user_login : '#' . $actor_id;
	}

	/**
	 * Who the message is about, which is not always who performed it.
	 *
	 * Falling back to the actor on someone else's row names the wrong person:
	 * "Deleted user account admin" when admin deleted somebody else.
	 *
	 * @param array<string, mixed> $row
	 */
	private static function resolveSubjectLogin( array $row ): string {
		$object_type = isset( $row['object_type'] ) ? (string) $row['object_type'] : '';
		$object_id   = isset( $row['object_id'] ) ? (int) $row['object_id'] : 0;
		$actor_id    = isset( $row['actor_user_id'] ) ? (int) $row['actor_user_id'] : 0;

		if ( $object_type === 'user' && $object_id > 0 && $object_id !== $actor_id ) {
			$user = get_userdata( $object_id );

			return $user instanceof WP_User ? (string) $user->user_login : '#' . $object_id;
		}

		return self::resolveActorLabel( $row );
	}

	/**
	 * @param array<string, mixed> $row
	 */
	private static function buildMessage( array $row ): string {
		$data = json_decode( (string) $row['data'], true );
		$data = is_array( $data ) ? $data : array();

		$data += array(
			'object_id'   => isset( $row['object_id'] ) ? (int) $row['object_id'] : 0,
			'object_type' => isset( $row['object_type'] ) ? (string) $row['object_type'] : '',
			'login'       => self::resolveSubjectLogin( $row ),
		);

		return BotBlockerAuditLogger::renderMessage( (string) $row['event_key'], $data );
	}

	private static function eventCategory( string $event_key ): string {
		$pos = strpos( $event_key, '.' );

		return $pos === false ? $event_key : substr( $event_key, 0, $pos );
	}

	private static function formatDataPretty( string $json ): string {
		if ( $json === '' ) {
			return '';
		}
		$decoded = json_decode( $json, true );
		if ( ! is_array( $decoded ) ) {
			return $json;
		}
		$encoded = wp_json_encode( $decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );

		return is_string( $encoded ) ? $encoded : '';
	}
}
