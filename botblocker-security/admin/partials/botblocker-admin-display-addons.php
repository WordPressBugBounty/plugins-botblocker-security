<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

require 'botblocker-section-header.php';

$bbcs_ctx           = BotBlockerUI::get_addons_context();
$bbcs_addons        = $bbcs_ctx['addons'];
$bbcs_active        = $bbcs_ctx['active'];
$bbcs_market        = $bbcs_ctx['market'];
$bbcs_marketBySlug  = $bbcs_ctx['marketBySlug'];
$bbcs_addons_locked = $bbcs_ctx['addons_locked'];
$bbcs_has_cloud_api = $bbcs_ctx['has_cloud_api'];
$bbcs_addons_local  = ! empty( $bbcs_ctx['addons_local_mode'] );

$bbcs_updates_count = $bbcs_ctx['updates_count'];
?>
<section role="main" class="content-body">
	<div class="row">
		<div class="col-xs-12 col-sm-12 col-md-9 col-lg-9 col-xl-9 col-xxl-10">
			<section class="card">
				<header class="card-header d-flex align-items-center justify-content-between bbcs-addon-card-header">
					<h2 class="card-title m-0"><?php esc_html_e( 'Add-ons', 'botblocker-security' ); ?></h2>
					<div class="card-actions bbcs-addon-header-actions">
						<button type="button" class="btn btn-primary btn-sm bbcs-addon-upload-toggle" data-bbcs-toggle-upload aria-expanded="false" aria-controls="bbcs-addon-upload-panel"<?php echo $bbcs_addons_local ? ' disabled' : ''; ?>>
							<i class="fa-solid fa-cloud-arrow-up me-1"></i><?php esc_html_e( 'Upload ZIP', 'botblocker-security' ); ?>
						</button>
						<?php if ( $bbcs_updates_count > 0 && ! $bbcs_addons_locked && $bbcs_has_cloud_api ) : ?>
							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="bbcs-inline m-0">
								<input type="hidden" name="action" value="bbcs_update_all_addons">
								<?php wp_nonce_field( 'bbcs_update_all_addons', 'bbcs_update_all_addons_nonce' ); ?>
								<button type="submit" class="btn btn-warning btn-sm">
									<i class="fa-solid fa-arrows-rotate me-1"></i>
									<?php
										/* translators: %d: number of add-ons with available updates. */
										echo esc_html( sprintf( __( 'Update All (%d)', 'botblocker-security' ), $bbcs_updates_count ) );
									?>
								</button>
							</form>
						<?php endif; ?>
					</div>
				</header>
				<div class="card-body bbcs-addon-card-body">

					<?php if ( ! $bbcs_addons_local ) : ?>
					<div id="bbcs-addon-upload-panel" class="bbcs-addon-upload-panel" hidden>
						<div class="bbcs-addon-upload-copy">
							<div class="bbcs-addon-upload-icon"><i class="fa-solid fa-file-zipper"></i></div>
							<div>
								<h3 class="bbcs-card-title-compact"><?php esc_html_e( 'Install add-on from ZIP', 'botblocker-security' ); ?></h3>
								<p class="bbcs-info-text bbcs-card-title-compact"><?php esc_html_e( 'Upload a BotBlocker add-on package. It will be installed inactive, then you can review and activate it from the Installed tab.', 'botblocker-security' ); ?></p>
							</div>
						</div>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data" class="bbcs-addon-upload-form">
							<input type="hidden" name="action" value="bbcs_upload_addon">
							<?php wp_nonce_field( 'bbcs_upload_addon', 'bbcs_upload_addon_nonce' ); ?>
							<label for="bbcs_addon_zip" class="bbcs-addon-upload-drop">
								<span class="bbcs-addon-upload-drop-icon"><i class="fa-solid fa-upload"></i></span>
								<span class="bbcs-addon-upload-drop-text">
									<span class="bbcs-addon-upload-drop-title"><?php esc_html_e( 'Choose ZIP package', 'botblocker-security' ); ?></span>
									<span class="bbcs-addon-upload-file-name" data-bbcs-upload-file-name><?php esc_html_e( 'No file selected', 'botblocker-security' ); ?></span>
								</span>
								<input type="file" id="bbcs_addon_zip" name="bbcs_addon_zip" accept=".zip,application/zip" required>
							</label>
							<button type="submit" class="btn bbcs-btn-addons btn-primary btn-sm">
								<i class="fa-solid fa-circle-check me-1"></i><?php esc_html_e( 'Install Package', 'botblocker-security' ); ?>
							</button>
						</form>
					</div>
					<?php endif; ?>

			<?php if ( $bbcs_addons_locked ) : ?>
				<div class="alert alert-warning bbcs-mb-16" role="alert">
					<h4 class="alert-heading"><i class="fa-solid fa-puzzle-piece me-1"></i><?php esc_html_e( 'Premium add-ons - included with BotBlocker PRO', 'botblocker-security' ); ?></h4>
					<p class="mb-2"><?php esc_html_e( 'Each add-on is a turnkey extension: install in one click, configure in minutes, and gain a new layer of protection or speed without writing a line of code.', 'botblocker-security' ); ?></p>
					<p class="mb-2 small bbcs-text-muted"><?php esc_html_e( 'Examples: Security Headers (HSTS, CSP, X-Frame), Hide Admin URL, Speed-up, Early Init, Anti-spam, Telegram alerts and more.', 'botblocker-security' ); ?></p>
					<?php if ( isset( $BBCSA->pages_cloud_api ) ) : ?>
						<?php if ( $bbcs_has_cloud_api ) : ?>
							<a href="<?php echo esc_url( $BBCSA->pages_cloud_api ); ?>" class="btn btn-sm bbcs-btn-upgrade"><i class="fa-solid fa-rocket me-1"></i><?php esc_html_e( 'Activate access', 'botblocker-security' ); ?></a>
						<?php else : ?>
							<a href="<?php echo esc_url( $BBCSA->pages_cloud_api ); ?>" class="btn btn-sm bbcs-btn-upgrade"><i class="fa-solid fa-crown me-1"></i><?php esc_html_e( 'Get BotBlocker PRO', 'botblocker-security' ); ?></a>
							<a href="<?php echo esc_url( 'https://botblocker.top/pricing/' ); ?>" class="btn btn-sm btn-default" target="_blank" rel="noopener noreferrer"><i class="fa-solid fa-table-list me-1"></i><?php esc_html_e( 'Compare Plans', 'botblocker-security' ); ?></a>
						<?php endif; ?>
					<?php endif; ?>
				</div>
			<?php endif; ?>

					<ul class="nav nav-tabs">
						<li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#bbcs-market"><?php esc_html_e( 'Marketplace', 'botblocker-security' ); ?></a></li>
						<li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#bbcs-installed"><?php esc_html_e( 'Installed', 'botblocker-security' ); ?></a></li>
					</ul>
					<div class="tab-content mt-3">
						<div class="tab-pane container fade show active" id="bbcs-market">
							<div class="bbcs-info-inner bbcs-my-16">
								<h3 class="bbcs-card-title-compact"><?php esc_html_e( 'Popular add-ons, new possibilities', 'botblocker-security' ); ?></h3>
								<p class="bbcs-info-text bbcs-card-title-compact">
									<?php esc_html_e( 'Boost your BotBlocker experience with add-ons and tools selected to increase security, productivity and enhance your website', 'botblocker-security' ); ?>
								</p>
							</div>
							<div class="bbcs-grid-5 bbcs-pb-20">
								<?php foreach ( $bbcs_market as $bbcs_item ) : ?>
									<?php $bbcs_tools_link = ( $bbcs_item['is_active'] && isset( $BBCSA->pages_tools ) ) ? esc_url( $BBCSA->pages_tools . '#addon-' . $bbcs_item['slug'] ) : ''; ?>
									<div class="card bbcs-card-addon">
										<div class="card-body bbcs-flex-col">
											<div class="bbcs-item-header">
												<?php if ( ! empty( $bbcs_item['icon'] ) ) : ?>
                                                    <?php // phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage ?>
													<img src="<?php echo esc_url( $bbcs_item['icon'] ); ?>" alt="" class="bbcs-addon-icon">
													<?php
												else :
													?>
													<i class="fa-solid fa-puzzle-piece"></i><?php endif; ?>
												<h5 class="card-title bbcs-card-title-compact"><?php echo esc_html( $bbcs_item['name'] ?? $bbcs_item['slug'] ); ?></h5>
											</div>
											<?php /* translators: %s is the add-on version number. */ ?>
											<div class="bbcs-text-muted bbcs-mb-8"><?php echo esc_html( sprintf( __( 'Version %s', 'botblocker-security' ), $bbcs_item['remote_ver'] ?: '' ) ); ?>
											<?php
											if ( $bbcs_item['show_installed_ver'] ) :
												?>
												<?php /* translators: %s is the installed version of the add-on. */ ?>
												• <?php echo esc_html( sprintf( __( 'Installed %s', 'botblocker-security' ), $bbcs_item['installed_ver'] ) ); ?><?php endif; ?></div>
											<?php if ( $bbcs_item['is_incompatible'] ) : ?>
												<div class="alert alert-warning p-1 bbcs-mb-8" style="font-size:0.8em;">
													<?php /* translators: %s is the minimum required BotBlocker version. */ ?>
													<i class="fa-solid fa-triangle-exclamation me-1"></i><?php echo esc_html( sprintf( __( 'Requires BotBlocker >= %s', 'botblocker-security' ), $bbcs_item['requires_core'] ) ); ?>
												</div>
											<?php elseif ( $bbcs_item['update_blocked'] ) : ?>
												<div class="alert alert-warning p-1 bbcs-mb-8" style="font-size:0.8em;">
													<?php /* translators: %s is the minimum required BotBlocker version for the update. */ ?>
													<i class="fa-solid fa-triangle-exclamation me-1"></i><?php echo esc_html( sprintf( __( 'Update available, requires BotBlocker >= %s', 'botblocker-security' ), $bbcs_item['requires_core'] ) ); ?>
												</div>
											<?php endif; ?>
											<p class="card-text bbcs-card-text-flex"><?php echo esc_html( $bbcs_item['description'] ?? '' ); ?></p>
											<div class="d-flex align-items-center flex-wrap gap-1">
												<?php if ( $bbcs_item['is_incompatible'] ) : ?>
													<?php /* translators: %s is the minimum required BotBlocker version. */ ?>
													<button class="btn bbcs-btn-addons btn-secondary btn-xs" disabled title="<?php echo esc_attr( sprintf( __( 'Update BotBlocker to %s to use this add-on', 'botblocker-security' ), $bbcs_item['requires_core'] ) ); ?>"><?php esc_html_e( 'Incompatible', 'botblocker-security' ); ?></button>
												<?php elseif ( ! $bbcs_item['is_installed'] ) : ?>
													<?php if ( ! $bbcs_addons_locked && $bbcs_has_cloud_api ) : ?>
														<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="bbcs-inline m-0">
															<input type="hidden" name="action" value="bbcs_install_addon">
															<input type="hidden" name="slug" value="<?php echo esc_attr( $bbcs_item['slug'] ); ?>">
															<input type="hidden" name="url" value="<?php echo esc_attr( $bbcs_item['url'] ); ?>">
															<input type="hidden" name="requires_core" value="<?php echo esc_attr( $bbcs_item['requires_core'] ); ?>">
															<?php wp_nonce_field( 'bbcs_install_addon', 'bbcs_install_addon_nonce' ); ?>
															<button type="submit" class="btn bbcs-btn-addons btn-primary btn-xs"><?php esc_html_e( 'Install', 'botblocker-security' ); ?></button>
														</form>
													<?php else : ?>
														<?php if ( isset( $BBCSA->pages_cloud_api ) ) : ?>
															<a href="<?php echo esc_url( $BBCSA->pages_cloud_api ); ?>" class="btn bbcs-btn-addons btn-warning btn-xs" title="<?php esc_attr_e( 'Install requires BotBlocker PRO', 'botblocker-security' ); ?>"><i class="fa-solid fa-crown me-1"></i><?php esc_html_e( 'Install (PRO)', 'botblocker-security' ); ?></a>
														<?php else : ?>
															<button type="button" class="btn bbcs-btn-addons btn-secondary btn-xs" disabled title="<?php esc_attr_e( 'Install requires BotBlocker PRO', 'botblocker-security' ); ?>"><?php esc_html_e( 'Install (PRO)', 'botblocker-security' ); ?></button>
														<?php endif; ?>
													<?php endif; ?>
												<?php elseif ( $bbcs_item['update_avail'] ) : ?>
													<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="bbcs-inline m-0">
														<input type="hidden" name="action" value="bbcs_update_addon">
														<input type="hidden" name="slug" value="<?php echo esc_attr( $bbcs_item['slug'] ); ?>">
														<input type="hidden" name="url" value="<?php echo esc_attr( $bbcs_item['url'] ); ?>">
														<input type="hidden" name="requires_core" value="<?php echo esc_attr( $bbcs_item['requires_core'] ); ?>">
														<?php wp_nonce_field( 'bbcs_update_addon', 'bbcs_update_addon_nonce' ); ?>
														<button type="submit" class="btn bbcs-btn-addons btn-warning btn-xs"><?php esc_html_e( 'Update', 'botblocker-security' ); ?></button>
													</form>
												<?php elseif ( $bbcs_item['update_blocked'] ) : ?>
													<?php /* translators: %s is the minimum required BotBlocker version for the update. */ ?>
													<button class="btn bbcs-btn-addons btn-secondary btn-xs" disabled title="<?php echo esc_attr( sprintf( __( 'Update requires BotBlocker >= %s', 'botblocker-security' ), $bbcs_item['requires_core'] ) ); ?>"><?php esc_html_e( 'Installed', 'botblocker-security' ); ?></button>
												<?php else : ?>
													<button class="btn bbcs-btn-addons btn-secondary btn-xs" disabled><?php esc_html_e( 'Installed', 'botblocker-security' ); ?></button>
												<?php endif; ?>
												<?php
												if ( $bbcs_tools_link ) :
													?>
													<a href="<?php echo esc_url( $bbcs_tools_link ); ?>" class="btn bbcs-btn-addons btn-outline-secondary btn-xs" title="<?php esc_attr_e( 'Addon Settings', 'botblocker-security' ); ?>"><i class="fa-solid fa-gear"></i></a><?php endif; ?>
											</div>
										</div>
									</div>
								<?php endforeach; ?>
							</div>
						</div>
						<div class="tab-pane container fade" id="bbcs-installed">
							<div class="bbcs-grid-5 bbcs-pb-20">
								<?php foreach ( $bbcs_addons as $bbcs_slug => $bbcs_addon ) : ?>
									<?php $bbcs_tools_link = ( $bbcs_addon['is_active'] && ! empty( $bbcs_addon['has_settings'] ) && isset( $BBCSA->pages_tools ) ) ? esc_url( $BBCSA->pages_tools . '#addon-' . $bbcs_slug ) : ''; ?>
									<div class="card bbcs-card-addon-rel">
										<div class="card-body bbcs-flex-col">
											<div class="bbcs-item-header">
												<?php if ( $bbcs_addon['icon'] ) : ?>
                                                    <?php // phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage ?>
													<img src="<?php echo esc_url( $bbcs_addon['icon'] ); ?>" alt="" class="bbcs-addon-icon">
													<?php
												else :
													?>
													<i class="fa-solid fa-puzzle-piece"></i><?php endif; ?>
												<h5 class="card-title bbcs-card-title-compact"><?php echo esc_html( $bbcs_addon['name'] ?: $bbcs_slug ); ?></h5>
											</div>
											<?php /* translators: %s is the add-on version number. */ ?>
											<div class="bbcs-text-muted bbcs-mb-8"><?php echo esc_html( sprintf( __( 'Version %s', 'botblocker-security' ), $bbcs_addon['version'] ?: '' ) ); ?></div>
											<?php if ( $bbcs_addon['incompatible'] ) : ?>
												<div class="alert alert-warning p-1 bbcs-mb-8" style="font-size:0.8em;">
													<?php /* translators: %s is the minimum required BotBlocker version. */ ?>
													<i class="fa-solid fa-triangle-exclamation me-1"></i><?php echo $bbcs_addon['req_core'] ? esc_html( sprintf( __( 'Requires BotBlocker >= %s', 'botblocker-security' ), $bbcs_addon['req_core'] ) ) : esc_html__( 'Requires a newer BotBlocker version', 'botblocker-security' ); ?>
												</div>
											<?php endif; ?>
											<p class="card-text bbcs-card-text-flex">&nbsp;<?php echo esc_html( $bbcs_addon['description'] ); ?></p>
											<div class="bbcs-actions-row d-flex align-items-center flex-wrap gap-1">
												<?php if ( $bbcs_addon['broken'] ) : ?>
													<button class="btn bbcs-btn-addons btn-secondary btn-xs" disabled><?php esc_html_e( 'Broken', 'botblocker-security' ); ?></button>
												<?php elseif ( $bbcs_addon['incompatible'] ) : ?>
													<?php /* translators: %s is the minimum required BotBlocker version. */ ?>
													<button type="button" class="btn bbcs-btn-addons btn-secondary btn-xs" disabled title="<?php echo esc_attr( sprintf( __( 'Update BotBlocker to %s to use this add-on', 'botblocker-security' ), $bbcs_addon['req_core'] ) ); ?>"><?php esc_html_e( 'Incompatible', 'botblocker-security' ); ?></button>
												<?php elseif ( $bbcs_addon['incompatible_remote'] ) : ?>
													<?php /* translators: %s is the minimum required BotBlocker version. */ ?>
													<button type="button" class="btn bbcs-btn-addons btn-secondary btn-xs" disabled title="<?php echo esc_attr( sprintf( __( 'Update BotBlocker to %s to use this add-on', 'botblocker-security' ), $bbcs_addon['req_core_remote'] ) ); ?>"><?php esc_html_e( 'Incompatible', 'botblocker-security' ); ?></button>
												<?php elseif ( empty( $bbcs_addon['req_core_local'] ) ) : ?>
													<?php // Case 1: local add-on has no Requires-Core - update or delete only ?>
													<?php if ( $bbcs_addon['update_repair'] || $bbcs_addon['update_avail'] ) : ?>
														<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="bbcs-inline m-0">
															<input type="hidden" name="action" value="bbcs_update_addon">
															<input type="hidden" name="slug" value="<?php echo esc_attr( $bbcs_slug ); ?>">
															<input type="hidden" name="url" value="<?php echo esc_attr( $bbcs_addon['update_url'] ); ?>">
															<input type="hidden" name="requires_core" value="<?php echo esc_attr( $bbcs_addon['update_requires_core'] ); ?>">
															<?php wp_nonce_field( 'bbcs_update_addon', 'bbcs_update_addon_nonce' ); ?>
															<button type="submit" class="btn bbcs-btn-addons btn-warning btn-xs"><?php esc_html_e( 'Update', 'botblocker-security' ); ?></button>
														</form>
													<?php else : ?>
														<button type="button" class="btn bbcs-btn-addons btn-secondary btn-xs" disabled><?php esc_html_e( 'No Update Source', 'botblocker-security' ); ?></button>
													<?php endif; ?>
												<?php else : ?>
													<?php // Case 2: local compatible and remote compatible for update ?>
													<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="bbcs-inline m-0">
														<input type="hidden" name="action" value="bbcs_toggle_addon">
														<input type="hidden" name="slug" value="<?php echo esc_attr( $bbcs_slug ); ?>">
														<?php wp_nonce_field( 'bbcs_toggle_addon', 'bbcs_toggle_addon_nonce' ); ?>
														<?php if ( $bbcs_addon['is_active'] ) : ?>
															<button type="submit" class="btn bbcs-btn-addons btn-danger btn-xs"><?php esc_html_e( 'Deactivate', 'botblocker-security' ); ?></button>
														<?php else : ?>
															<button type="submit" class="btn bbcs-btn-addons btn-primary btn-xs"><?php esc_html_e( 'Activate', 'botblocker-security' ); ?></button>
														<?php endif; ?>
													</form>
													<?php if ( $bbcs_addon['update_avail'] ) : ?>
														<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="bbcs-inline m-0">
															<input type="hidden" name="action" value="bbcs_update_addon">
															<input type="hidden" name="slug" value="<?php echo esc_attr( $bbcs_slug ); ?>">
															<input type="hidden" name="url" value="<?php echo esc_attr( $bbcs_addon['update_url'] ); ?>">
															<input type="hidden" name="requires_core" value="<?php echo esc_attr( $bbcs_addon['update_requires_core'] ); ?>">
															<?php wp_nonce_field( 'bbcs_update_addon', 'bbcs_update_addon_nonce' ); ?>
															<button type="submit" class="btn bbcs-btn-addons btn-warning btn-xs"><?php esc_html_e( 'Update', 'botblocker-security' ); ?></button>
														</form>
													<?php endif; ?>
												<?php endif; ?>
												<?php
												if ( $bbcs_tools_link ) :
													?>
													<a href="<?php echo esc_url( $bbcs_tools_link ); ?>" class="btn bbcs-btn-addons btn-outline-secondary btn-xs" title="<?php esc_attr_e( 'Addon Settings', 'botblocker-security' ); ?>"><i class="fa-solid fa-gear"></i></a><?php endif; ?>
												<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="bbcs-inline bbcs-ml-auto m-0">
													<input type="hidden" name="action" value="bbcs_delete_addon">
													<input type="hidden" name="slug" value="<?php echo esc_attr( $bbcs_slug ); ?>">
													<?php wp_nonce_field( 'bbcs_delete_addon', 'bbcs_delete_addon_nonce' ); ?>
													<?php $bbcs_delete_confirm = $bbcs_addon['incompatible_remote'] ? __( 'This add-on is incompatible with your BotBlocker version. Delete anyway?', 'botblocker-security' ) : __( 'Delete this add-on?', 'botblocker-security' ); ?>
													<button type="submit" class="btn bbcs-btn-addons btn-light btn-xs" title="<?php esc_attr_e( 'Delete', 'botblocker-security' ); ?>" onclick="return confirm('<?php echo esc_js( $bbcs_delete_confirm ); ?>');"><i class="fa-regular fa-trash-can"></i></button>
												</form>
											</div>
										</div>
									</div>
								<?php endforeach; ?>
							</div>
						</div>
					</div>
				</div>
			</section>
		</div>
		<div class="col-xs-12 col-sm-12 col-md-3 col-lg-3 col-xl-3 col-xxl-2">
			<?php require 'botblocker-section-right-sidebar.php'; ?>
		</div>
	</div>
</section>
