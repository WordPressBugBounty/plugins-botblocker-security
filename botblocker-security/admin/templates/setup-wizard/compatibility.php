<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return static function ( Botblocker_SetupWizard_ViewModel $d ): void { ?>
	<div class="bbcs-wizstep" data-step="2">
		<h2 class="bbcs-wiztitle"><?php esc_html_e( 'Compatibility check', 'botblocker-security' ); ?></h2>
		<p class="bbcs-wizsub"><?php esc_html_e( 'Testing key pages of your site', 'botblocker-security' ); ?></p>

		<div class="bbcs-wiztests" id="bbcs-wiz-tests">
			<div class="bbcs-wiztest" data-test="homepage">
				<span class="bbcs-wiztest-name"><?php esc_html_e( 'Homepage', 'botblocker-security' ); ?></span>
				<span class="bbcs-wiztest-status"><svg class="bbcs-ico bbcs-ico--spinner"><use href="#bbcs-i-refresh"></use></svg> <?php esc_html_e( 'Checking...', 'botblocker-security' ); ?></span>
			</div>
			<div class="bbcs-wiztest" data-test="admin">
				<span class="bbcs-wiztest-name"><?php esc_html_e( 'Admin panel', 'botblocker-security' ); ?></span>
				<span class="bbcs-wiztest-status"><svg class="bbcs-ico bbcs-ico--spinner"><use href="#bbcs-i-refresh"></use></svg> <?php esc_html_e( 'Checking...', 'botblocker-security' ); ?></span>
			</div>
			<div class="bbcs-wiztest" data-test="login">
				<span class="bbcs-wiztest-name"><?php esc_html_e( 'Login page', 'botblocker-security' ); ?></span>
				<span class="bbcs-wiztest-status"><svg class="bbcs-ico bbcs-ico--spinner"><use href="#bbcs-i-refresh"></use></svg> <?php esc_html_e( 'Checking...', 'botblocker-security' ); ?></span>
			</div>
			<div class="bbcs-wiztest" data-test="rest">
				<span class="bbcs-wiztest-name"><?php esc_html_e( 'REST API', 'botblocker-security' ); ?></span>
				<span class="bbcs-wiztest-status"><svg class="bbcs-ico bbcs-ico--spinner"><use href="#bbcs-i-refresh"></use></svg> <?php esc_html_e( 'Checking...', 'botblocker-security' ); ?></span>
			</div>
		</div>

		<div id="bbcs-wiz-test-warn" hidden>
			<div class="bbcs-card bbcs-card-pad bbcs-mb-4h bbcs-amber-card">
				<div class="bbcs-row bbcs-g-2h bbcs-mb-3"><svg class="bbcs-ico bbcs-ico--md bbcs-tx-amber"><use href="#bbcs-i-warning"></use></svg><b><?php esc_html_e( 'Minor compatibility issues detected', 'botblocker-security' ); ?></b></div>
				<div class="bbcs-dim bbcs-fs-xs bbcs-mb-3h"><?php esc_html_e( 'Can be fixed automatically. The site will remain functional.', 'botblocker-security' ); ?></div>
				<div class="bbcs-row bbcs-g-2h">
					<button class="bbcs-btn bbcs-btn--pri" id="bbcs-wiz-fix-auto"><svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-sparkle"></use></svg> <?php esc_html_e( 'Auto-fix', 'botblocker-security' ); ?></button>
					<button class="bbcs-btn" id="bbcs-wiz-fix-manual"><svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-gear"></use></svg> <?php esc_html_e( 'Manually', 'botblocker-security' ); ?></button>
				</div>
			</div>
		</div>

		<div id="bbcs-wiz-test-ok" hidden>
			<div class="bbcs-card bbcs-card-pad bbcs-mb-4h bbcs-green-card">
				<div class="bbcs-row bbcs-g-2h"><svg class="bbcs-ico bbcs-ico--md bbcs-tx-green"><use href="#bbcs-i-check"></use></svg><b><?php esc_html_e( 'All tests passed. Your site is ready for protection.', 'botblocker-security' ); ?></b></div>
			</div>
			<div class="bbcs-wizcta">
				<button class="bbcs-btn bbcs-btn--lg" id="bbcs-wiz-back2"><svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-arrowL"></use></svg> <?php esc_html_e( 'Back', 'botblocker-security' ); ?></button>
				<button class="bbcs-btn bbcs-btn--pri bbcs-btn--lg" id="bbcs-wiz-next2"><?php esc_html_e( 'Continue', 'botblocker-security' ); ?> <svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-arrowR"></use></svg></button>
			</div>
		</div>
	</div>
<?php };
