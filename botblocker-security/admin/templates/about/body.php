<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
	exit;
}

return static function (Botblocker_AboutViewModel $data): void {
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
			require_once BOTBLOCKER_DIR . 'includes/shortcode/botblocker-shortcode-sidebar.php';
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- component renderer returns safe HTML
			echo bbcs_plugins_themes_view();
			?>
		</div>
	</div>
<?php
};
