<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

function bbcs_alerts_get_all(): array
{
	$alerts = [];

	$failed_alert_err = get_transient('bbcs_cloud_connection_failed_alert');
	if (!empty($failed_alert_err)) {
		$alerts[] = $failed_alert_err;
	}

	$missing_files_alert = get_transient('bbcs_missing_files_alert');
	if (!empty($missing_files_alert)) {
		$alerts[] = $missing_files_alert;
	}

	$cloud_api_expired_alert = get_transient('bbcs_cloud_api_expired_alert');
	if (!empty($cloud_api_expired_alert)) {
		$alerts[] = $cloud_api_expired_alert;
	}

	$cloud_api_hits_exhausted_alert = get_transient('bbcs_cloud_api_hits_exhausted_alert');
	if (!empty($cloud_api_hits_exhausted_alert)) {
		$alerts[] = $cloud_api_hits_exhausted_alert;
	}

	return $alerts;
}

function bbcs_alerts_set_cloud_connection_failed(): void
{
	$alert = [
		'type'    => 'no_connection_bbcloud',
		'icon'    => 'fas fa-signal bg-success text-light',
		'title'   => __('No Connection BBCloud', 'botblocker-security'),
    	'message' => gmdate('d/m/Y'),
	];

	set_transient('bbcs_cloud_connection_failed_alert', $alert, DAY_IN_SECONDS);
}


function bbcs_alerts_set_missing_files(): void
{
	$alert = [
		'type'    => 'missing_files',
		'icon'    => 'fas fa-exclamation-triangle bg-warning text-light',
		'title'   => __('Missing Files', 'botblocker-security'),
		'message' => __('Required files missing. Regenerated.', 'botblocker-security')
	];

	set_transient('bbcs_missing_files_alert', $alert, HOUR_IN_SECONDS);
}

function bbcs_alerts_set_cloud_api_expired($days_left = null): void
{
    /* translators: %d: number of days left before the cloud API expires. */
    $about_to_expire_message = __( 'Your cloud API will expire in %d days.', 'botblocker-security');
	$message = $days_left !== null ? sprintf( $about_to_expire_message, intval( $days_left ) ) : __( 'Your cloud API has expired. Please renew it.', 'botblocker-security');
	$alert = [
		'type'    => 'cloud_api_expired',
		'icon'    => 'fas fa-exclamation-circle bg-danger text-light',
		'title'   => __( 'Cloud API Expired', 'botblocker-security'),
		'message' => $message
	];

	set_transient('bbcs_cloud_api_expired_alert', $alert, DAY_IN_SECONDS);
}

function bbcs_alerts_set_cloud_api_hits_exhausted($hits_left = null): void
{
    /* translators: %d: number of hits remaining before the cloud API is exhausted. */
    $low_hits_message = __( 'Your cloud API has less than %d hits remaining.', 'botblocker-security');
	$message = $hits_left !== null ? sprintf( $low_hits_message, intval( $hits_left ) ) : __( 'Your cloud API has exhausted all its hits. Please renew it.', 'botblocker-security');
	$alert = [
		'type'    => 'cloud_api_hits_exhausted',
		'icon'    => 'fas fa-exclamation-circle bg-danger text-light',
		'title'   => __( 'Cloud API Hits Exhausted', 'botblocker-security'),
		'message' => $message
	];

	set_transient('bbcs_cloud_api_hits_exhausted_alert', $alert, DAY_IN_SECONDS);
}
