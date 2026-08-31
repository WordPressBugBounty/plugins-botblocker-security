<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
	exit;
}

$bbcs_docs = array(
	'payment' => array(
		'file'  => BOTBLOCKER_DIR . 'docs/PAYMENT-BYPASS.md',
		'title' => __( 'PAYMENT-BYPASS.md', 'botblocker-security' ),
	),
	'ddos'    => array(
		'file'  => BOTBLOCKER_DIR . 'docs/DDOS-COMPATIBILITY.md',
		'title' => __( 'DDOS-COMPATIBILITY.md', 'botblocker-security' ),
	),
	'tls'     => array(
		'file'  => BOTBLOCKER_DIR . 'docs/TLS-FINGERPRINTING.md',
		'title' => __( 'TLS-FINGERPRINTING.md', 'botblocker-security' ),
	),
);

foreach ( $bbcs_docs as $bbcs_doc_key => $bbcs_doc ) {
	$bbcs_docs[ $bbcs_doc_key ]['content'] = file_exists( $bbcs_doc['file'] ) ? (string) file_get_contents( $bbcs_doc['file'] ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
}

return static function (Botblocker_AboutViewModel $data) use ( $bbcs_docs ): void {
?>
	<div class="bbcs-card bbcs-card-pad bbcs-mb-5h">
		<div class="bbcs-row bbcs-ai-center bbcs-g-3">
			<div class="bbcs-tx-green">
				<svg class="bbcs-ico" style="width:48px;height:48px;"><use href="#bbcs-i-shield"></use></svg>
			</div>
			<div>
				<div class="bbcs-fs-lg bbcs-fw-bold"><?php echo esc_html( BOTBLOCKER_PLUGIN_NAME ); ?> <span class="bbcs-dim bbcs-fs-sm bbcs-fw-normal bbcs-ml-2"><?php echo esc_html($data->plugin_version); ?></span></div>
				<div class="bbcs-dim bbcs-mt-1"><?php esc_html_e( 'Advanced protection against bots, spam, and brute-force attacks.', 'botblocker-security' ); ?></div>
			</div>
		</div>
	</div>

	<div class="bbcs-grid bbcs-grid--2 bbcs-mb-5h">
		<div class="bbcs-card bbcs-card-pad bbcs-card--stretch">
			<div class="bbcs-section-title bbcs-fs-md bbcs-mb-3h"><?php esc_html_e('Contact us', 'botblocker-security'); ?></div>
			<a class="bbcs-list-row bbcs-link-reset" href="javascript:void(0);" onclick="event.stopPropagation(); document.getElementById('bbcs-support-btn')?.click();"><span class="bbcs-tx-green"><svg class="bbcs-ico bbcs-ico--md">
						<use href="#bbcs-i-headset"></use>
					</svg></span>
				<div class="bbcs-fill">
					<div class="bbcs-fs-sm bbcs-fw-semibold"><?php esc_html_e('Support ticket', 'botblocker-security'); ?></div>
					<div class="bbcs-dim bbcs-fs-xs"><?php esc_html_e('open support form', 'botblocker-security'); ?></div>
				</div><span class="bbcs-dim"><svg class="bbcs-ico bbcs-ico--sm">
						<use href="#bbcs-i-external"></use>
					</svg></span>
			</a>
			<a class="bbcs-list-row bbcs-link-reset" href="<?php echo esc_url($data->telegram_support_url); ?>" target="_blank"><span class="bbcs-tx-green"><svg class="bbcs-ico bbcs-ico--md">
						<use href="#bbcs-i-telegram"></use>
					</svg></span>
				<div class="bbcs-fill">
					<div class="bbcs-fs-sm bbcs-fw-semibold"><?php esc_html_e('Telegram support', 'botblocker-security'); ?></div>
					<div class="bbcs-dim bbcs-fs-xs">@botblocker</div>
				</div><span class="bbcs-dim"><svg class="bbcs-ico bbcs-ico--sm">
						<use href="#bbcs-i-external"></use>
					</svg></span>
			</a>
			<a class="bbcs-list-row bbcs-link-reset" href="<?php echo esc_url($data->email_support_url); ?>"><span class="bbcs-tx-green"><svg class="bbcs-ico bbcs-ico--md">
						<use href="#bbcs-i-mail"></use>
					</svg></span>
				<div class="bbcs-fill">
					<div class="bbcs-fs-sm bbcs-fw-semibold">admin@botblocker.top</div>
					<div class="bbcs-dim bbcs-fs-xs"><?php esc_html_e('email', 'botblocker-security'); ?></div>
				</div><span class="bbcs-dim"><svg class="bbcs-ico bbcs-ico--sm">
						<use href="#bbcs-i-external"></use>
					</svg></span>
			</a>
			<a class="bbcs-list-row bbcs-link-reset" href="<?php echo esc_url($data->support_forum_url); ?>" target="_blank"><span class="bbcs-tx-green"><svg class="bbcs-ico bbcs-ico--md">
						<use href="#bbcs-i-user"></use>
					</svg></span>
				<div class="bbcs-fill">
					<div class="bbcs-fs-sm bbcs-fw-semibold"><?php esc_html_e('Support forum', 'botblocker-security'); ?></div>
					<div class="bbcs-dim bbcs-fs-xs"><?php esc_html_e('community', 'botblocker-security'); ?></div>
				</div><span class="bbcs-dim"><svg class="bbcs-ico bbcs-ico--sm">
						<use href="#bbcs-i-external"></use>
					</svg></span>
			</a>
			<div class="bbcs-mt-auto">
				<div class="bbcs-divider bbcs-row bbcs-row--wrap bbcs-g-4">
					<a class="bbcs-link bbcs-tx-violet bbcs-fs-xs" href="<?php echo esc_url($data->docs_url); ?>/privacy-policy/"><svg class="bbcs-ico bbcs-ico--sm">
							<use href="#bbcs-i-doc"></use>
						</svg><?php esc_html_e('Privacy policy', 'botblocker-security'); ?></a>
					<a class="bbcs-link bbcs-tx-violet bbcs-fs-xs" href="<?php echo esc_url($data->docs_url); ?>/terms-of-service/"><svg class="bbcs-ico bbcs-ico--sm">
							<use href="#bbcs-i-doc"></use>
						</svg><?php esc_html_e('Terms of service', 'botblocker-security'); ?></a>
					<a class="bbcs-link bbcs-tx-violet bbcs-fs-xs" href="<?php echo esc_url($data->docs_url); ?>/refund_returns/"><svg class="bbcs-ico bbcs-ico--sm">
							<use href="#bbcs-i-doc"></use>
						</svg><?php esc_html_e('Refund policy', 'botblocker-security'); ?></a>
				</div>
			</div>
		</div>

		<div class="bbcs-card bbcs-card-pad bbcs-card--stretch">
			<div class="bbcs-section-title bbcs-fs-md bbcs-mb-3h"><?php esc_html_e('System status', 'botblocker-security'); ?></div>
			<div class="bbcs-inner bbcs-code bbcs-code--lg">
				<?php
				require_once BOTBLOCKER_DIR . 'includes/data/class-botblocker-system-info-data.php';
				$render_si = require BOTBLOCKER_DIR . 'admin/templates/shared/system-status-lines.php';
				$render_si( BotBlockerSystemInfoData::getInstance() );
				?>
			</div>
			<div class="bbcs-mt-auto">
				<div class="bbcs-row bbcs-g-1h bbcs-mt-3">
					<span class="bbcs-dot"></span>
					<span class="bbcs-dim bbcs-fs-xs"><?php esc_html_e('All services operating normally', 'botblocker-security'); ?></span>
				</div>
			</div>
		</div>
	</div>

	<div class="bbcs-card bbcs-card-pad bbcs-mb-5h">
		<div class="bbcs-section-title bbcs-fs-md bbcs-mb-3h"><?php esc_html_e('Documentation', 'botblocker-security'); ?></div>
		<div class="bbcs-grid bbcs-grid--3">
			<a class="bbcs-inner bbcs-doclink" href="<?php echo esc_url($data->docs_url); ?>/"><span class="bbcs-doclink-ic"><svg class="bbcs-ico bbcs-ico--md">
						<use href="#bbcs-i-doc"></use>
					</svg></span><span class="bbcs-doclink-label"><?php esc_html_e('User guide', 'botblocker-security'); ?></span><span class="bbcs-dim bbcs-doclink-arr"><svg class="bbcs-ico bbcs-ico--sm">
						<use href="#bbcs-i-external"></use>
					</svg></span></a>
			<a class="bbcs-inner bbcs-doclink" href="<?php echo esc_url($data->docs_url); ?>/api/"><span class="bbcs-doclink-ic"><svg class="bbcs-ico bbcs-ico--md">
						<use href="#bbcs-i-code"></use>
					</svg></span><span class="bbcs-doclink-label"><?php esc_html_e('API & Developers', 'botblocker-security'); ?></span><span class="bbcs-dim bbcs-doclink-arr"><svg class="bbcs-ico bbcs-ico--sm">
						<use href="#bbcs-i-external"></use>
					</svg></span></a>
			<a class="bbcs-inner bbcs-doclink" href="<?php echo esc_url($data->docs_url); ?>/faq/"><span class="bbcs-doclink-ic"><svg class="bbcs-ico bbcs-ico--md">
						<use href="#bbcs-i-about"></use>
					</svg></span><span class="bbcs-doclink-label"><?php esc_html_e('FAQ', 'botblocker-security'); ?></span><span class="bbcs-dim bbcs-doclink-arr"><svg class="bbcs-ico bbcs-ico--sm">
						<use href="#bbcs-i-external"></use>
					</svg></span></a>
			<a class="bbcs-inner bbcs-doclink" href="<?php echo esc_url($data->docs_url); ?>/video-tutorials/"><span class="bbcs-doclink-ic"><svg class="bbcs-ico bbcs-ico--md">
						<use href="#bbcs-i-system"></use>
					</svg></span><span class="bbcs-doclink-label"><?php esc_html_e('Video tutorials', 'botblocker-security'); ?></span><span class="bbcs-dim bbcs-doclink-arr"><svg class="bbcs-ico bbcs-ico--sm">
						<use href="#bbcs-i-external"></use>
					</svg></span></a>
			<a class="bbcs-inner bbcs-doclink" href="https://botblocker.com/blog/"><span class="bbcs-doclink-ic"><svg class="bbcs-ico bbcs-ico--md">
						<use href="#bbcs-i-shield"></use>
					</svg></span><span class="bbcs-doclink-label"><?php esc_html_e('Security blog', 'botblocker-security'); ?></span><span class="bbcs-dim bbcs-doclink-arr"><svg class="bbcs-ico bbcs-ico--sm">
						<use href="#bbcs-i-external"></use>
					</svg></span></a>
			<a class="bbcs-inner bbcs-doclink" href="<?php echo esc_url($data->docs_url); ?>/changelog/"><span class="bbcs-doclink-ic"><svg class="bbcs-ico bbcs-ico--md">
						<use href="#bbcs-i-changelog"></use>
					</svg></span><span class="bbcs-doclink-label"><?php esc_html_e('Changelog', 'botblocker-security'); ?></span><span class="bbcs-dim bbcs-doclink-arr"><svg class="bbcs-ico bbcs-ico--sm">
						<use href="#bbcs-i-external"></use>
					</svg></span></a>
		</div>
	</div>

	<div class="bbcs-grid bbcs-grid--2 bbcs-mb-5h">
		<div class="bbcs-card bbcs-card-pad bbcs-card--stretch">
			<div class="bbcs-section-title bbcs-fs-md bbcs-mb-3h"><?php esc_html_e('Third-party', 'botblocker-security'); ?></div>
			<p class="bbcs-dim bbcs-fs-sm bbcs-mb-1h"><?php esc_html_e('BotBlocker uses third-party libraries selected for security, compatibility, and open-source license compliance.', 'botblocker-security'); ?></p>
			<p class="bbcs-dim bbcs-fs-sm bbcs-mb-3h"><?php esc_html_e('Libraries and resources used in BotBlocker, with licenses and sources.', 'botblocker-security'); ?></p>

			<div class="bbcs-col bbcs-g-2h">
				<h4 class="bbcs-fs-sm bbcs-fw-semibold bbcs-mb-1h bbcs-mt-2h"><?php esc_html_e('Icons:', 'botblocker-security'); ?></h4>
				<div class="bbcs-list-row">
					<span class="bbcs-tx-green bbcs-row bbcs-ai-center"><svg class="bbcs-ico bbcs-ico--md"><use href="#bbcs-i-copyright"></use></svg></span>
					<div class="bbcs-fill">
						<div class="bbcs-fs-sm bbcs-fw-semibold">Font Awesome</div>
						<div class="bbcs-dim bbcs-fs-xs"><?php esc_html_e('Licensed under the', 'botblocker-security'); ?> <a href="https://fontawesome.com/license/free" target="_blank" class="bbcs-link">Font Awesome Free License</a>.</div>
					</div>
				</div>

				<h4 class="bbcs-fs-sm bbcs-fw-semibold bbcs-mb-1h bbcs-mt-2h"><?php esc_html_e('JavaScript Library:', 'botblocker-security'); ?></h4>
				<div class="bbcs-list-row">
					<span class="bbcs-tx-green bbcs-row bbcs-ai-center"><svg class="bbcs-ico bbcs-ico--md"><use href="#bbcs-i-copyright"></use></svg></span>
					<div class="bbcs-fill">
						<div class="bbcs-fs-sm bbcs-fw-semibold">jQuery</div>
						<div class="bbcs-dim bbcs-fs-xs"><?php esc_html_e('Licensed under the', 'botblocker-security'); ?> <a href="https://jquery.org/license/" target="_blank" class="bbcs-link">MIT License</a>.</div>
					</div>
				</div>

				<h4 class="bbcs-fs-sm bbcs-fw-semibold bbcs-mb-1h bbcs-mt-2h"><?php esc_html_e('Images and Icons:', 'botblocker-security'); ?></h4>
				<div class="bbcs-list-row">
					<span class="bbcs-tx-green bbcs-row bbcs-ai-center"><svg class="bbcs-ico bbcs-ico--md"><use href="#bbcs-i-copyright"></use></svg></span>
					<div class="bbcs-fill">
						<div class="bbcs-fs-sm bbcs-fw-semibold">SVG Repo</div>
						<div class="bbcs-dim bbcs-fs-xs"><?php esc_html_e('Licensed under', 'botblocker-security'); ?> <a href="https://www.svgrepo.com" target="_blank" class="bbcs-link">CC0 Public Domain Dedication</a>.</div>
					</div>
				</div>
				<div class="bbcs-list-row">
					<span class="bbcs-tx-green bbcs-row bbcs-ai-center"><svg class="bbcs-ico bbcs-ico--md"><use href="#bbcs-i-copyright"></use></svg></span>
					<div class="bbcs-fill">
						<div class="bbcs-fs-sm bbcs-fw-semibold">Flaticon Icons</div>
						<div class="bbcs-dim bbcs-fs-xs"><?php esc_html_e('Icons designed by', 'botblocker-security'); ?> <a href="https://www.freepik.com" target="_blank" class="bbcs-link">Freepik</a> <?php esc_html_e('from', 'botblocker-security'); ?> <a href="https://www.flaticon.com" target="_blank" class="bbcs-link">Flaticon</a> (<?php esc_html_e('used with attribution', 'botblocker-security'); ?>).</div>
					</div>
				</div>

				<h4 class="bbcs-fs-sm bbcs-fw-semibold bbcs-mb-1h bbcs-mt-2h"><?php esc_html_e('Geolocation Database:', 'botblocker-security'); ?></h4>
				<div class="bbcs-list-row">
					<span class="bbcs-tx-green bbcs-row bbcs-ai-center"><svg class="bbcs-ico bbcs-ico--md"><use href="#bbcs-i-copyright"></use></svg></span>
					<div class="bbcs-fill">
						<div class="bbcs-fs-sm bbcs-fw-semibold">SypexGeo Lite</div>
						<div class="bbcs-dim bbcs-fs-xs"><?php esc_html_e('Licensed under BSD License, available at', 'botblocker-security'); ?> <a href="https://sypexgeo.net" target="_blank" class="bbcs-link">sypexgeo.net</a>.</div>
					</div>
				</div>

				<h4 class="bbcs-fs-sm bbcs-fw-semibold bbcs-mb-1h bbcs-mt-2h"><?php esc_html_e('Device Detection:', 'botblocker-security'); ?></h4>
				<div class="bbcs-list-row">
					<span class="bbcs-tx-green bbcs-row bbcs-ai-center"><svg class="bbcs-ico bbcs-ico--md"><use href="#bbcs-i-copyright"></use></svg></span>
					<div class="bbcs-fill">
						<div class="bbcs-fs-sm bbcs-fw-semibold">Mobile Detect Library</div>
						<div class="bbcs-dim bbcs-fs-xs"><?php esc_html_e('Licensed under the', 'botblocker-security'); ?> <a href="https://github.com/serbanghita/Mobile-Detect" target="_blank" class="bbcs-link">MIT License</a>.</div>
					</div>
				</div>

				<h4 class="bbcs-fs-sm bbcs-fw-semibold bbcs-mb-1h bbcs-mt-2h"><?php esc_html_e('Toast Notifications:', 'botblocker-security'); ?></h4>
				<div class="bbcs-list-row">
					<span class="bbcs-tx-green bbcs-row bbcs-ai-center"><svg class="bbcs-ico bbcs-ico--md"><use href="#bbcs-i-copyright"></use></svg></span>
					<div class="bbcs-fill">
						<div class="bbcs-fs-sm bbcs-fw-semibold">Toastify JS</div>
						<div class="bbcs-dim bbcs-fs-xs"><?php esc_html_e('Licensed under the', 'botblocker-security'); ?> <a href="https://github.com/apvarun/toastify-js/blob/master/LICENSE" target="_blank" class="bbcs-link">MIT License</a>.</div>
					</div>
				</div>
			</div>
		</div>

		<div class="bbcs-card bbcs-card-pad bbcs-card--stretch">
			<div class="bbcs-section-title bbcs-fs-md bbcs-mb-3h"><?php esc_html_e('Software versions', 'botblocker-security'); ?></div>
			<?php
			if ( class_exists( 'BotBlockerSidebarShortcodes' ) ) {
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- component renderer returns safe HTML
				echo BotBlockerSidebarShortcodes::pluginsThemesView();
			}
			?>
		</div>
	</div>
	<div class="bbcs-card bbcs-card-pad bbcs-mb-5h">
		<div class="bbcs-section-title bbcs-fs-md bbcs-mb-3h"><?php esc_html_e('Plugin compatibility guides', 'botblocker-security'); ?></div>
		<div class="bbcs-option bbcs-hoverbg">
			<button type="button" class="bbcs-btn" id="bbcs-about-doc-payment-trigger">
				<svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-doc"></use></svg>
				<?php esc_html_e( 'Open PAYMENT-BYPASS.md', 'botblocker-security' ); ?>
			</button>
			<span class="bbcs-option-label"><?php esc_html_e( 'Full guide: recognition layers, bypass modes and hardening recommendations for payment callbacks.', 'botblocker-security' ); ?></span>
			<span class="bbcs-help"><span class="bbcs-help-q">?</span><span class="bbcs-help-tip"><?php esc_attr_e( 'Shows the raw contents of docs/PAYMENT-BYPASS.md shipped with the plugin.', 'botblocker-security' ); ?></span></span>
		</div>
		<div class="bbcs-option bbcs-hoverbg">
			<button type="button" class="bbcs-btn" id="bbcs-about-doc-ddos-trigger">
				<svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-doc"></use></svg>
				<?php esc_html_e( 'Open DDOS-COMPATIBILITY.md', 'botblocker-security' ); ?>
			</button>
			<span class="bbcs-option-label"><?php esc_html_e( 'Full guide: running behind DDoS-Guard, Stormwall, Cloudflare UAM, Qrator and similar services.', 'botblocker-security' ); ?></span>
			<span class="bbcs-help"><span class="bbcs-help-q">?</span><span class="bbcs-help-tip"><?php esc_attr_e( 'Shows the raw contents of docs/DDOS-COMPATIBILITY.md shipped with the plugin.', 'botblocker-security' ); ?></span></span>
		</div>
		<div class="bbcs-option bbcs-hoverbg">
			<button type="button" class="bbcs-btn" id="bbcs-about-doc-tls-trigger">
				<svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-doc"></use></svg>
				<?php esc_html_e( 'Open TLS-FINGERPRINTING.md', 'botblocker-security' ); ?>
			</button>
			<span class="bbcs-option-label"><?php esc_html_e( 'Full guide: JA3/JA4 requirements, server modules and setup options.', 'botblocker-security' ); ?></span>
			<span class="bbcs-help"><span class="bbcs-help-q">?</span><span class="bbcs-help-tip"><?php esc_attr_e( 'Shows the raw contents of docs/TLS-FINGERPRINTING.md shipped with the plugin.', 'botblocker-security' ); ?></span></span>
		</div>
	</div>

	<?php foreach ( $bbcs_docs as $bbcs_doc_key => $bbcs_doc ) : ?>
	<div class="bbcs-modal-overlay" id="bbcsAboutDoc<?php echo esc_attr( ucfirst( $bbcs_doc_key ) ); ?>Modal" style="display:none;">
		<div class="bbcs-modal bbcs-modal--wide">
			<div class="bbcs-modal-header">
				<div class="bbcs-modal-title">
					<svg class="bbcs-ico bbcs-ico--sm" style="margin-right:var(--bbcs-sp-1);"><use href="#bbcs-i-doc"></use></svg>
					<?php echo esc_html( $bbcs_doc['title'] ); ?>
				</div>
				<button type="button" class="bbcs-modal-close" data-modal-close>
					<svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-x"></use></svg>
				</button>
			</div>
			<div class="bbcs-modal-body">
				<pre class="bbcs-md-view"><?php echo esc_html( $bbcs_doc['content'] ); ?></pre>
			</div>
			<div class="bbcs-modal-footer">
				<button type="button" class="bbcs-btn" data-modal-close><?php esc_html_e( 'Close', 'botblocker-security' ); ?></button>
			</div>
		</div>
	</div>
	<?php endforeach; ?>

	<script>
	(function() {
		'use strict';
		var pairs = {
			'bbcs-about-doc-payment-trigger': 'bbcsAboutDocPaymentModal',
			'bbcs-about-doc-ddos-trigger': 'bbcsAboutDocDdosModal',
			'bbcs-about-doc-tls-trigger': 'bbcsAboutDocTlsModal'
		};

		Object.keys(pairs).forEach(function(triggerId) {
			var trigger = document.getElementById(triggerId);
			var overlay = document.getElementById(pairs[triggerId]);
			if (!trigger || !overlay) return;

			trigger.addEventListener('click', function(e) {
				e.preventDefault();
				overlay.style.display = 'flex';
			});

			overlay.addEventListener('click', function(e) {
				var btn = e.target.closest('[data-modal-close]');
				if (btn) {
					overlay.style.display = 'none';
					return;
				}
				if (e.target === overlay) {
					overlay.style.display = 'none';
				}
			});

			document.addEventListener('keydown', function(e) {
				if (e.key === 'Escape' && overlay.style.display === 'flex') {
					overlay.style.display = 'none';
				}
			});
		});
	})();
	</script>
<?php
};
