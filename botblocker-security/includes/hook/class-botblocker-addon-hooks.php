<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

require_once __DIR__ . '/hook-addon-validation.php';
require_once __DIR__ . '/hook-addon-install.php';
require_once BOTBLOCKER_DIR . 'includes/class-botblocker-toastify.php';

class BotBlockerAddonHooks {

	public static function onPluginUpdated( $upgrader, $hook_extra ): void {
		if ( empty( $hook_extra['type'] ) || $hook_extra['type'] !== 'plugin' ) {
			return;
		}
		$plugins     = $hook_extra['plugins'] ?? ( isset( $hook_extra['plugin'] ) ? array( $hook_extra['plugin'] ) : array() );
		$bbcs_plugin = plugin_basename( BOTBLOCKER_DIR . 'botblocker-security.php' );
		if ( ! in_array( $bbcs_plugin, $plugins, true ) ) {
			return;
		}

		$plugin_data = get_file_data( BOTBLOCKER_DIR . 'botblocker-security.php', array( 'Version' => 'Version' ) );
		$new_version = ! empty( $plugin_data['Version'] ) ? $plugin_data['Version'] : BOTBLOCKER_VERSION;

		BotBlockerMultisite::forEachSite(
			function ( $site_id ) use ( $new_version ) {
				$result = BotBlockerAddons::autoUpdate( $new_version );
				if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
					error_log( '[BBCS DEBUG] [Addons] PluginUpdate: site=' . $site_id . ' updated=' . count( $result['updated'] ?? array() ) . ' failed=' . count( $result['failed'] ?? array() ) );
				}
				if ( ! empty( $result['failed'] ) ) {
					$failed_slugs = array_column( $result['failed'], 'slug' );
					$active       = BotBlockerMultisite::getOption( 'bbcs_active_addons', array() );
					if ( is_array( $active ) && ! empty( $failed_slugs ) ) {
						BotBlockerMultisite::updateOption( 'bbcs_active_addons', array_values( array_diff( $active, $failed_slugs ) ) );
					}
					BotBlockerAlerts::setAddonUpdateFailed( $result['failed'] );
				}
				self::deactivateIncompatible( $new_version );
				if ( class_exists( 'BotBlockerAsnDb' ) ) {
					BotBlockerAsnDb::scheduleDownload( 'upgrade' );
				}
			}
		);
	}

	public static function deactivateIncompatible( string $core_version = '' ): void {
		$active = BotBlockerMultisite::getOption( 'bbcs_active_addons', array() );
		if ( ! is_array( $active ) || empty( $active ) ) {
			return;
		}

		$addons       = BotBlockerAddons::scanAll();
		$incompatible = array();

		foreach ( $active as $slug ) {
			if ( ! isset( $addons[ $slug ] ) || ! $addons[ $slug ]['valid'] ) {
				continue;
			}
			if ( ! BotBlockerAddons::isCompatible( $addons[ $slug ], $core_version ) ) {
				$incompatible[] = array(
					'name'          => $addons[ $slug ]['name'] ?: $slug,
					'requires_core' => $addons[ $slug ]['requires_core'],
				);
			}
		}

		$new_active = array_values(
			array_filter(
				$active,
				function ( $s ) use ( $addons, $core_version ) {
					return isset( $addons[ $s ] ) && $addons[ $s ]['valid'] && BotBlockerAddons::isCompatible( $addons[ $s ], $core_version );
				}
			)
		);

		if ( $new_active !== array_values( $active ) ) {
			BotBlockerMultisite::updateOption( 'bbcs_active_addons', $new_active );
		}

		if ( ! empty( $incompatible ) ) {
			BotBlockerAlerts::setAddonIncompatible( $incompatible );
		}
	}

	public static function handleUpdateAll(): void {
		if ( ! current_user_can( BotBlockerMultisite::canManage() ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'botblocker-security' ) );
		}
		$nonce = isset( $_POST['bbcs_update_all_addons_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['bbcs_update_all_addons_nonce'] ) ) : '';
		if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'bbcs_update_all_addons' ) ) {
			wp_die( 'Nonce verification failed' );
		}
		$result = BotBlockerAddons::autoUpdate();
		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [Addons] UpdateAll: updated=' . count( $result['updated'] ?? array() ) . ' failed=' . count( $result['failed'] ?? array() ) );
		}
		if ( ! empty( $result['failed'] ) ) {
			BotBlockerAlerts::setAddonUpdateFailed( $result['failed'] );
		}
		self::deactivateIncompatible();
		BBCS_Toastify::flash( __( 'All add-ons have been updated.', 'botblocker-security' ), BBCS_Toastify::TYPE_SUCCESS, BBCS_Toastify::PAGE_ADDONS );
		wp_safe_redirect( BotBlockerMultisite::getAdminPageUrl( 'bbcs_addons' ) );
		exit;
	}

	public static function handleToggle(): void {
		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( '[BBCS DEBUG] [Addons] handleToggle: entered' );
		}

		if ( ! current_user_can( BotBlockerMultisite::canManage() ) ) {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( '[BBCS DEBUG] [Addons] handleToggle: insufficient permissions' );
			}
			wp_die( esc_html__( 'Insufficient permissions.', 'botblocker-security' ) );
		}
		check_admin_referer( 'bbcs_toggle_addon', 'bbcs_toggle_addon_nonce' );

		$slug = isset( $_POST['slug'] ) ? sanitize_key( wp_unslash( $_POST['slug'] ) ) : '';
		if ( $slug === '' ) {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( '[BBCS DEBUG] [Addons] handleToggle: empty slug' );
			}
			wp_safe_redirect( BotBlockerMultisite::getAdminPageUrl( 'bbcs_addons' ) );
			exit;
		}

		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( '[BBCS DEBUG] [Addons] handleToggle: slug=' . $slug );
		}

		if ( ! class_exists( 'BotBlockerAddons' ) ) {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( '[BBCS DEBUG] [Addons] handleToggle: BotBlockerAddons class not found' );
			}
			wp_safe_redirect( BotBlockerMultisite::getAdminPageUrl( 'bbcs_addons' ) );
			exit;
		}
		$addons = BotBlockerAddons::scanAll();
		$active = BotBlockerMultisite::getOption( 'bbcs_active_addons', array() );
		if ( ! is_array( $active ) ) {
			$active = array();
		}
		if ( ! isset( $addons[ $slug ] ) || ! $addons[ $slug ]['valid'] ) {
			$active = array_values( array_diff( $active, array( $slug ) ) );
			BotBlockerMultisite::updateOption( 'bbcs_active_addons', $active );
			BBCS_Toastify::flash_addon_error( 'invalid' );
			wp_safe_redirect( BotBlockerMultisite::getAdminPageUrl( 'bbcs_addons' ) );
			exit;
		}
		if ( ! in_array( $slug, $active, true ) && ! BotBlockerAddons::isCompatible( $addons[ $slug ] ) ) {
			BBCS_Toastify::flash_addon_error( 'requires_core', $addons[ $slug ]['requires_core'] ?? null );
			wp_safe_redirect( BotBlockerMultisite::getAdminPageUrl( 'bbcs_addons' ) );
			exit;
		}
		$was_active = in_array( $slug, $active, true );

		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( '[BBCS DEBUG] [Addons] Toggle: slug=' . $slug . ' was_active=' . ( $was_active ? '1' : '0' ) . ' step=before_loadCore' );
		}

		BotBlockerAddons::loadCore( $addons[ $slug ] );

		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( '[BBCS DEBUG] [Addons] Toggle: slug=' . $slug . ' step=after_loadCore' );
		}

		BotBlockerAddons::includeLifecycleFile( $addons[ $slug ] );

		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( '[BBCS DEBUG] [Addons] Toggle: slug=' . $slug . ' step=after_includeLifecycleFile' );
		}

		if ( $was_active ) {
			$active = array_values( array_diff( $active, array( $slug ) ) );
		} else {
			$active[] = $slug;
			$active   = array_values( array_unique( $active ) );
		}
		BotBlockerMultisite::updateOption( 'bbcs_active_addons', $active );

		$is_now_active = in_array( $slug, $active, true );

		BotBlockerAddons::dispatchLifecycle( $slug, $is_now_active ? 'activate' : 'deactivate', $addons[ $slug ] );

		do_action( 'bbcs_addon_toggled', $slug, $is_now_active );

		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [Addons] Toggle: slug=' . $slug . ' action=' . ( $is_now_active ? 'activate' : 'deactivate' ) . ' status=success' );
		}

		BBCS_Toastify::flash( __( 'Add-on updated successfully.', 'botblocker-security' ), BBCS_Toastify::TYPE_SUCCESS, BBCS_Toastify::PAGE_ADDONS );
		wp_safe_redirect( BotBlockerMultisite::getAdminPageUrl( 'bbcs_addons' ) );
		exit;
	}

	public static function handleInstall(): void {
		if ( ! current_user_can( BotBlockerMultisite::canManage() ) ) {
			wp_die( esc_html__( 'Forbidden.', 'botblocker-security' ) );
		}
		$nonce_install = isset( $_POST['bbcs_install_addon_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['bbcs_install_addon_nonce'] ) ) : '';
		if ( empty( $nonce_install ) || ! wp_verify_nonce( $nonce_install, 'bbcs_install_addon' ) ) {
			wp_die( esc_html__( 'Nonce verification failed.', 'botblocker-security' ) );
		}
		$slug          = isset( $_POST['slug'] ) ? sanitize_key( wp_unslash( $_POST['slug'] ) ) : '';
		$url           = isset( $_POST['url'] ) ? esc_url_raw( wp_unslash( $_POST['url'] ) ) : '';
		$requires_core = isset( $_POST['requires_core'] ) ? sanitize_text_field( wp_unslash( $_POST['requires_core'] ) ) : '';
		$redir         = BotBlockerMultisite::getAdminPageUrl( 'bbcs_addons' );
		if ( $slug === '' || $url === '' ) {
			BBCS_Toastify::flash_addon_error( 'install_args' );
			wp_safe_redirect( $redir );
			exit;
		}
		if ( ! BotBlockerPro::isActive() ) {
			BBCS_Toastify::flash_addon_error( 'pro_required' );
			wp_safe_redirect( $redir );
			exit;
		}
		if ( ! bbcs_is_allowed_addon_url( $url ) ) {
			BBCS_Toastify::flash_addon_error( 'url_not_allowed' );
			wp_safe_redirect( $redir );
			exit;
		}
		if ( ! empty( $requires_core ) && version_compare( BOTBLOCKER_VERSION, $requires_core, '<' ) ) {
			BBCS_Toastify::flash_addon_error( 'requires_core', $requires_core );
			wp_safe_redirect( $redir );
			exit;
		}
		if ( ! function_exists( 'download_url' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		$tmp = download_url( $url );
		if ( is_wp_error( $tmp ) ) {
			BBCS_Toastify::flash_addon_error( 'download', $tmp->get_error_message() );
			wp_safe_redirect( $redir );
			exit;
		}

		$installed = bbcs_install_addon_package(
			$tmp,
			array(
				'slug'            => $slug,
				'filename'        => basename( (string) wp_parse_url( $url, PHP_URL_PATH ) ),
				'lifecycle_event' => 'install',
			)
		);
		if ( file_exists( $tmp ) ) {
			wp_delete_file( $tmp );
		}
		if ( is_wp_error( $installed ) ) {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [Addons] Install: slug=' . $slug . ' status=failed error=' . $installed->get_error_code() );
			}
			BBCS_Toastify::flash_addon_error( $installed->get_error_code(), $installed->get_error_message() );
			wp_safe_redirect( $redir );
			exit;
		}

		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [Addons] Install: slug=' . $slug . ' status=success' );
		}

		BBCS_Toastify::flash( __( 'Add-on installed successfully.', 'botblocker-security' ), BBCS_Toastify::TYPE_SUCCESS, BBCS_Toastify::PAGE_ADDONS );
		wp_safe_redirect( $redir );
		exit;
	}

	public static function handleUpload(): void {
		if ( ! current_user_can( BotBlockerMultisite::canManage() ) ) {
			wp_die( esc_html__( 'Forbidden.', 'botblocker-security' ) );
		}
		$nonce_upload = isset( $_POST['bbcs_upload_addon_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['bbcs_upload_addon_nonce'] ) ) : '';
		if ( empty( $nonce_upload ) || ! wp_verify_nonce( $nonce_upload, 'bbcs_upload_addon' ) ) {
			wp_die( esc_html__( 'Nonce verification failed.', 'botblocker-security' ) );
		}

		$redir = BotBlockerMultisite::getAdminPageUrl( 'bbcs_addons' );
		if ( empty( $_FILES['bbcs_addon_zip'] ) || ! is_array( $_FILES['bbcs_addon_zip'] ) ) {
			BBCS_Toastify::flash_addon_error( 'upload_missing' );
			wp_safe_redirect( $redir );
			exit;
		}

	    // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$file  = $_FILES['bbcs_addon_zip'];
		$error = isset( $file['error'] ) ? (int) $file['error'] : UPLOAD_ERR_NO_FILE;
		if ( $error !== UPLOAD_ERR_OK ) {
			BBCS_Toastify::flash_addon_error( 'upload_failed', (string) $error );
			wp_safe_redirect( $redir );
			exit;
		}

		$tmp_name = isset( $file['tmp_name'] ) ? (string) $file['tmp_name'] : '';
		$name     = isset( $file['name'] ) ? sanitize_file_name( (string) $file['name'] ) : '';
		if ( $tmp_name === '' || ! file_exists( $tmp_name ) || strtolower( pathinfo( $name, PATHINFO_EXTENSION ) ) !== 'zip' ) {
			BBCS_Toastify::flash_addon_error( 'zip_extension' );
			wp_safe_redirect( $redir );
			exit;
		}
		if ( ! is_uploaded_file( $tmp_name ) && ! defined( 'WP_CLI' ) ) {
			BBCS_Toastify::flash_addon_error( 'upload_untrusted' );
			wp_safe_redirect( $redir );
			exit;
		}

		$installed = bbcs_install_addon_package(
			$tmp_name,
			array(
				'filename'        => $name,
				'lifecycle_event' => 'install',
			)
		);
		if ( is_wp_error( $installed ) ) {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [Addons] Upload: file=' . $name . ' status=failed error=' . $installed->get_error_code() );
			}
			BBCS_Toastify::flash_addon_error( $installed->get_error_code(), $installed->get_error_message() );
			wp_safe_redirect( $redir );
			exit;
		}

		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [Addons] Upload: file=' . $name . ' slug=' . ( $installed['slug'] ?? '' ) . ' status=success' );
		}

		BBCS_Toastify::flash( __( 'Add-on package uploaded. Find it below, then activate it when ready.', 'botblocker-security' ), BBCS_Toastify::TYPE_SUCCESS, BBCS_Toastify::PAGE_ADDONS );
		wp_safe_redirect( $redir );
		exit;
	}

	public static function handleDelete(): void {
		if ( ! current_user_can( BotBlockerMultisite::canManage() ) ) {
			wp_die( esc_html__( 'Forbidden.', 'botblocker-security' ) );
		}
		$nonce_delete = isset( $_POST['bbcs_delete_addon_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['bbcs_delete_addon_nonce'] ) ) : '';
		if ( empty( $nonce_delete ) || ! wp_verify_nonce( $nonce_delete, 'bbcs_delete_addon' ) ) {
			wp_die( esc_html__( 'Nonce verification failed.', 'botblocker-security' ) );
		}
		$slug = isset( $_POST['slug'] ) ? sanitize_key( wp_unslash( $_POST['slug'] ) ) : '';
		if ( $slug === '' ) {
			wp_safe_redirect( BotBlockerMultisite::getAdminPageUrl( 'bbcs_addons' ) );
			exit;
		}
		$active = BotBlockerMultisite::getOption( 'bbcs_active_addons', array() );
		if ( ! is_array( $active ) ) {
			$active = array();
		}
		$addons     = class_exists( 'BotBlockerAddons' ) ? BotBlockerAddons::scanAll() : array();
		$addon      = $addons[ $slug ] ?? array();
		$was_active = in_array( $slug, $active, true );
		if ( class_exists( 'BotBlockerAddons' ) ) {
			BotBlockerAddons::loadCore( $addon );
		}
		if ( ! empty( $addon ) ) {
			BotBlockerAddons::includeLifecycleFile( $addon );
		}

		if ( $was_active ) {
			$active = array_values( array_diff( $active, array( $slug ) ) );
			BotBlockerMultisite::updateOption( 'bbcs_active_addons', $active );
			if ( ! empty( $addon ) ) {
				BotBlockerAddons::dispatchLifecycle( $slug, 'deactivate', $addon, array( 'reason' => 'delete' ) );
			}
			do_action( 'bbcs_addon_toggled', $slug, false );
		}
		if ( ! empty( $addon ) ) {
			BotBlockerAddons::dispatchLifecycle( $slug, 'delete', $addon );
		}
		$folder = trailingslashit( BotBlockerMultisite::getAddonsDir() ) . $slug;
		if ( is_dir( $folder ) ) {
			bbcs_rrmdir( $folder );
		}

		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [Addons] Delete: slug=' . $slug . ' status=success' );
		}

		BBCS_Toastify::flash( __( 'Add-on deleted.', 'botblocker-security' ), BBCS_Toastify::TYPE_SUCCESS, BBCS_Toastify::PAGE_ADDONS );
		wp_safe_redirect( BotBlockerMultisite::getAdminPageUrl( 'bbcs_addons' ) );
		exit;
	}

	public static function handleUpdate(): void {
		if ( ! current_user_can( BotBlockerMultisite::canManage() ) ) {
			wp_die( esc_html__( 'Forbidden.', 'botblocker-security' ) );
		}
		$nonce_update = isset( $_POST['bbcs_update_addon_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['bbcs_update_addon_nonce'] ) ) : '';
		if ( empty( $nonce_update ) || ! wp_verify_nonce( $nonce_update, 'bbcs_update_addon' ) ) {
			wp_die( esc_html__( 'Nonce verification failed.', 'botblocker-security' ) );
		}
		$slug          = isset( $_POST['slug'] ) ? sanitize_key( wp_unslash( $_POST['slug'] ) ) : '';
		$url           = isset( $_POST['url'] ) ? esc_url_raw( wp_unslash( $_POST['url'] ) ) : '';
		$requires_core = isset( $_POST['requires_core'] ) ? sanitize_text_field( wp_unslash( $_POST['requires_core'] ) ) : '';
		$redir         = BotBlockerMultisite::getAdminPageUrl( 'bbcs_addons' );
		if ( $slug === '' || $url === '' ) {
			wp_safe_redirect( esc_url_raw( $redir ) );
			exit;
		}
		if ( ! bbcs_is_allowed_addon_url( $url ) ) {
			BBCS_Toastify::flash_addon_error( 'url_not_allowed' );
			wp_safe_redirect( $redir );
			exit;
		}
		if ( ! empty( $requires_core ) && version_compare( BOTBLOCKER_VERSION, $requires_core, '<' ) ) {
			BBCS_Toastify::flash_addon_error( 'requires_core', $requires_core );
			wp_safe_redirect( $redir );
			exit;
		}
		if ( ! function_exists( 'download_url' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		if ( ! WP_Filesystem() ) {
			BBCS_Toastify::flash_addon_error( 'fs_unavailable' );
			wp_safe_redirect( $redir );
			exit;
		}

		$tmp = download_url( $url );
		if ( is_wp_error( $tmp ) ) {
			BBCS_Toastify::flash_addon_error( 'download', $tmp->get_error_message() );
			wp_safe_redirect( $redir );
			exit;
		}

		$active = BotBlockerMultisite::getOption( 'bbcs_active_addons', array() );
		if ( ! is_array( $active ) ) {
			$active = array();
		}
		$wasActive  = in_array( $slug, $active, true );
		$old_addons = class_exists( 'BotBlockerAddons' ) ? BotBlockerAddons::scanAll() : array();
		$old_addon  = $old_addons[ $slug ] ?? array();

		if ( $wasActive ) {
			if ( ! empty( $old_addon['core'] ) && file_exists( $old_addon['core'] ) ) {
				include_once $old_addon['core'];
			}
			if ( ! empty( $old_addon ) ) {
				BotBlockerAddons::dispatchLifecycle( $slug, 'deactivate', $old_addon, array( 'reason' => 'update' ) );
			}
			do_action( 'bbcs_addon_toggled', $slug, false );
			$active = array_values( array_diff( $active, array( $slug ) ) );
			BotBlockerMultisite::updateOption( 'bbcs_active_addons', $active );
		}

		$installed = bbcs_install_addon_package(
			$tmp,
			array(
				'slug'            => $slug,
				'filename'        => basename( (string) wp_parse_url( $url, PHP_URL_PATH ) ),
				'lifecycle_event' => 'update',
			)
		);
		if ( file_exists( $tmp ) ) {
			wp_delete_file( $tmp );
		}

		if ( is_wp_error( $installed ) ) {
			if ( $wasActive ) {
				$active   = BotBlockerMultisite::getOption( 'bbcs_active_addons', array() );
				$active[] = $slug;
				BotBlockerMultisite::updateOption( 'bbcs_active_addons', array_values( array_unique( $active ) ) );
				if ( ! empty( $old_addon ) ) {
					BotBlockerAddons::dispatchLifecycle( $slug, 'activate', $old_addon, array( 'reason' => 'update_rollback' ) );
				}
				do_action( 'bbcs_addon_toggled', $slug, true );
			}
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [Addons] Update: slug=' . $slug . ' status=failed error=' . $installed->get_error_code() );
			}
			BBCS_Toastify::flash_addon_error( $installed->get_error_code(), $installed->get_error_message() );
			wp_safe_redirect( $redir );
			exit;
		}

		$requires_core_after_update = '';
		if ( $wasActive ) {
			$updated_addons = class_exists( 'BotBlockerAddons' ) ? BotBlockerAddons::scanAll() : array();
			if ( isset( $updated_addons[ $slug ] ) && BotBlockerAddons::isCompatible( $updated_addons[ $slug ] ) ) {
				$active   = BotBlockerMultisite::getOption( 'bbcs_active_addons', array() );
				$active[] = $slug;
				BotBlockerMultisite::updateOption( 'bbcs_active_addons', array_values( array_unique( $active ) ) );
				if ( ! empty( $updated_addons[ $slug ]['core'] ) && file_exists( $updated_addons[ $slug ]['core'] ) ) {
					include_once $updated_addons[ $slug ]['core'];
				}
				BotBlockerAddons::dispatchLifecycle( $slug, 'activate', $updated_addons[ $slug ], array( 'reason' => 'update' ) );
				do_action( 'bbcs_addon_toggled', $slug, true );
			} elseif ( isset( $updated_addons[ $slug ] ) && ! empty( $updated_addons[ $slug ]['requires_core'] ) ) {
				$requires_core_after_update = $updated_addons[ $slug ]['requires_core'];
			}
		}

		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [Addons] Update: slug=' . $slug . ' status=success' );
		}

		if ( $requires_core_after_update !== '' ) {
			/* translators: %s: required BotBlocker version */
			BBCS_Toastify::flash( sprintf( __( 'Add-on was updated but not reactivated - it requires BotBlocker %s or higher. Please update the plugin first.', 'botblocker-security' ), $requires_core_after_update ), BBCS_Toastify::TYPE_WARNING, BBCS_Toastify::PAGE_ADDONS );
		} else {
			BBCS_Toastify::flash( __( 'Add-on updated successfully.', 'botblocker-security' ), BBCS_Toastify::TYPE_SUCCESS, BBCS_Toastify::PAGE_ADDONS );
		}
		wp_safe_redirect( $redir );
		exit;
	}
}

add_action( 'upgrader_process_complete', array( 'BotBlockerAddonHooks', 'onPluginUpdated' ), 10, 2 );
add_action( 'admin_post_bbcs_update_all_addons', array( 'BotBlockerAddonHooks', 'handleUpdateAll' ) );
add_action( 'admin_post_bbcs_toggle_addon', array( 'BotBlockerAddonHooks', 'handleToggle' ) );
add_action( 'admin_post_bbcs_install_addon', array( 'BotBlockerAddonHooks', 'handleInstall' ) );
add_action( 'admin_post_bbcs_upload_addon', array( 'BotBlockerAddonHooks', 'handleUpload' ) );
add_action( 'admin_post_bbcs_delete_addon', array( 'BotBlockerAddonHooks', 'handleDelete' ) );
add_action( 'admin_post_bbcs_update_addon', array( 'BotBlockerAddonHooks', 'handleUpdate' ) );
