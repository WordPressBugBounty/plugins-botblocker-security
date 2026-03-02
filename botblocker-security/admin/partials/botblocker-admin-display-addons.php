<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

include('botblocker-section-header.php');

$bbcs_ctx = BotBlockerUI::get_addons_context();
$bbcs_addons        = $bbcs_ctx['addons'];
$bbcs_active        = $bbcs_ctx['active'];
$bbcs_market        = $bbcs_ctx['market'];
$bbcs_marketBySlug  = $bbcs_ctx['marketBySlug'];
$bbcs_addons_locked = $bbcs_ctx['addons_locked'];
$bbcs_has_cloud_api = $bbcs_ctx['has_cloud_api'];
?>
<section role="main" class="content-body">
    <div class="row">
        <div class="col-xs-12 col-sm-12 col-md-9 сol-lg-9 col-xl-9 col-xxl-10">
            <section class="card">
                <header class="card-header"><h2 class="card-title"><?php esc_html_e('Add-ons','botblocker-security'); ?></h2></header>
                <div class="card-body bbcs-addon-card-body">

            <?php if ( $bbcs_addons_locked || !$bbcs_has_cloud_api) : ?>
                <div class="alert alert-warning bbcs-mb-16" role="alert">
                    <h4 class="alert-heading"><?php esc_html_e('Unlock Add-ons Marketplace','botblocker-security'); ?></h4>
                    <p><?php esc_html_e('Activate a Cloud API connection to install and manage premium add-ons.','botblocker-security'); ?></p>
                    <?php if ( isset( $BBCSA->pages_cloud_api ) ) : ?>
                        <?php if ( $bbcs_has_cloud_api ) : ?>
                            <a href="<?php echo esc_url( $BBCSA->pages_cloud_api ); ?>" target="_blank" class="btn btn-default btn-sm bbcs-link-blink"><i class="fa-solid fa-rocket me-1"></i><?php esc_html_e('Get Access Now','botblocker-security'); ?></a>
                        <?php else: ?>
                            <a href="<?php echo esc_url('https://botblocker.top/pricing/'); ?>" class="btn btn-default bbcs-cloud-api-color bbcs-link-blink" target="_blank" rel="noopener noreferrer"><i class="fa-solid fa-arrow-up-right-from-square"></i>&nbsp;<?php esc_html_e( 'Compare Plans', 'botblocker-security' ); ?></a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

                    <ul class="nav nav-tabs">
                        <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#bbcs-market"><?php esc_html_e('Marketplace','botblocker-security'); ?></a></li>
                        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#bbcs-installed"><?php esc_html_e('Installed','botblocker-security'); ?></a></li>
                    </ul>
                    <div class="tab-content mt-3">
                        <div class="tab-pane container fade show active" id="bbcs-market">
                            <div class="bbcs-info-inner bbcs-my-16">
                                <h3 class="bbcs-card-title-compact"><?php esc_html_e('Popular add-ons, new possibilities', 'botblocker-security'); ?></h3>
                                <p class="bbcs-info-text bbcs-card-title-compact">
                                    <?php esc_html_e('Boost your BotBlocker experience with add-ons and tools selected to increase security, productivity and enhance your website', 'botblocker-security'); ?>
                                </p>
                            </div>
                            <div class="bbcs-grid-5 bbcs-pb-20">
                                <?php foreach ( $bbcs_market as $bbcs_item ): ?>
                                    <?php $bbcs_slug = BotBlockerUI::get_addons_context() ? preg_replace('/\.zip$/','',basename((string)wp_parse_url($bbcs_item['url'],PHP_URL_PATH))) : ''; $bbcs_isInstalled = isset($bbcs_addons[$bbcs_slug]); $bbcs_installedVer = $bbcs_isInstalled ? ($bbcs_addons[$bbcs_slug]['version'] ?? '') : ''; $bbcs_remoteVer = $bbcs_item['version'] ?? ''; $bbcs_updateAvail = $bbcs_isInstalled && $bbcs_installedVer && $bbcs_remoteVer && version_compare($bbcs_remoteVer,$bbcs_installedVer,'>'); $bbcs_isActive = in_array($bbcs_slug,$bbcs_active,true); $bbcs_tools_link = ($bbcs_isActive && isset($BBCSA->pages_tools)? esc_url($BBCSA->pages_tools.'#addon-'.$bbcs_slug):''); ?>
                                    <div class="card bbcs-card-addon">
                                        <div class="card-body bbcs-flex-col">
                                            <div class="bbcs-item-header">
                                                <?php if ( !empty($bbcs_item['icon']) ): ?>
                                                    <?php // phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage ?>
                                                    <img src="<?php echo esc_url($bbcs_item['icon']); ?>" alt="" class="bbcs-addon-icon">
                                                <?php else: ?><i class="fa-solid fa-puzzle-piece"></i><?php endif; ?>
                                                <h5 class="card-title bbcs-card-title-compact"><?php echo esc_html($bbcs_item['name'] ?? $bbcs_slug); ?></h5>
                                            </div>
                                            <div class="bbcs-text-muted bbcs-mb-8">Version <?php echo esc_html($bbcs_remoteVer ?: ''); ?><?php if($bbcs_isInstalled && $bbcs_installedVer): ?> • Installed <?php echo esc_html($bbcs_installedVer); ?><?php endif; ?></div>
                                            <p class="card-text bbcs-card-text-flex"><?php echo esc_html($bbcs_item['description'] ?? ''); ?></p>
                                            <div class="d-flex align-items-center flex-wrap gap-1">
                                                <?php if(!$bbcs_isInstalled): ?>
                                                    <?php if(!$bbcs_addons_locked && $bbcs_has_cloud_api): ?>
                                                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="bbcs-inline m-0">
                                                            <input type="hidden" name="action" value="bbcs_install_addon">
                                                            <input type="hidden" name="slug" value="<?php echo esc_attr($bbcs_slug); ?>">
                                                            <input type="hidden" name="url" value="<?php echo esc_attr($bbcs_item['url']); ?>">
                                                            <?php wp_nonce_field('bbcs_install_addon','bbcs_install_addon_nonce'); ?>
                                                            <button type="submit" class="btn bbcs-btn-addons btn-primary btn-xs"><?php esc_html_e('Install','botblocker-security'); ?></button>
                                                        </form>
                                                    <?php else: ?>
                                                        <button class="btn bbcs-btn-addons btn-primary btn-xs bbcs-btn-blink"><?php esc_html_e('Install','botblocker-security'); ?></button>
                                                    <?php endif; ?>
                                                <?php elseif($bbcs_updateAvail): ?>
                                                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="bbcs-inline m-0">
                                                        <input type="hidden" name="action" value="bbcs_update_addon">
                                                        <input type="hidden" name="slug" value="<?php echo esc_attr($bbcs_slug); ?>">
                                                        <input type="hidden" name="url" value="<?php echo esc_attr($bbcs_item['url']); ?>">
                                                        <?php wp_nonce_field('bbcs_update_addon','bbcs_update_addon_nonce'); ?>
                                                        <button type="submit" class="btn bbcs-btn-addons btn-warning btn-xs"><?php esc_html_e('Update','botblocker-security'); ?></button>
                                                    </form>
                                                <?php else: ?>
                                                    <button class="btn bbcs-btn-addons btn-secondary btn-xs" disabled><?php esc_html_e('Installed','botblocker-security'); ?></button>
                                                <?php endif; ?>
                                                <?php if($bbcs_tools_link): ?><a href="<?php echo esc_url( $bbcs_tools_link ); ?>" class="btn bbcs-btn-addons btn-outline-secondary btn-xs" title="Addon Settings"><i class="fa-solid fa-gear"></i></a><?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="tab-pane container fade" id="bbcs-installed">
                            <div class="bbcs-grid-5 bbcs-pb-20">
                                <?php foreach ( $bbcs_addons as $bbcs_slug => $bbcs_addon ): ?>
                                    <?php $bbcs_isActive = in_array($bbcs_slug,$bbcs_active,true); $bbcs_broken = !$bbcs_addon['valid']; $bbcs_remote = $bbcs_marketBySlug[$bbcs_slug] ?? null; $bbcs_updateAvail = (!$bbcs_broken && $bbcs_remote && !empty($bbcs_remote['version']) && !empty($bbcs_addon['version']) && version_compare($bbcs_remote['version'],$bbcs_addon['version'],'>')); $bbcs_tools_link = ($bbcs_isActive && isset($BBCSA->pages_tools)? esc_url($BBCSA->pages_tools.'#addon-'.$bbcs_slug):''); ?>
                                    <div class="card bbcs-card-addon-rel">
                                        <div class="card-body bbcs-flex-col">
                                            <div class="bbcs-item-header">
                                                <?php if($bbcs_addon['icon']): ?>
                                                    <?php // phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage ?>
                                                    <img src="<?php echo esc_url($bbcs_addon['icon']); ?>" alt="" class="bbcs-addon-icon">
                                                <?php else: ?><i class="fa-solid fa-puzzle-piece"></i><?php endif; ?>
                                                <h5 class="card-title bbcs-card-title-compact"><?php echo esc_html($bbcs_addon['name'] ?: $bbcs_slug); ?></h5>
                                            </div>
                                            <div class="bbcs-text-muted bbcs-mb-8">Version <?php echo esc_html($bbcs_addon['version'] ?: ''); ?></div>
                                            <p class="card-text bbcs-card-text-flex">&nbsp;<?php echo esc_html($bbcs_addon['description']); ?></p>
                                            <div class="bbcs-actions-row d-flex align-items-center flex-wrap gap-1">
                                                <?php if($bbcs_broken): ?>
                                                    <button class="btn bbcs-btn-addons btn-secondary btn-xs" disabled><?php esc_html_e('Broken','botblocker-security'); ?></button>
                                                <?php else: ?>
                                                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="bbcs-inline m-0">
                                                        <input type="hidden" name="action" value="bbcs_toggle_addon">
                                                        <input type="hidden" name="slug" value="<?php echo esc_attr($bbcs_slug); ?>">
                                                        <?php wp_nonce_field('bbcs_toggle_addon','bbcs_toggle_addon_nonce'); ?>
                                                        <?php if($bbcs_isActive): ?>
                                                            <button type="submit" class="btn bbcs-btn-addons btn-danger btn-xs"><?php esc_html_e('Deactivate','botblocker-security'); ?></button>
                                                        <?php else: ?>
                                                            <button type="submit" class="btn bbcs-btn-addons btn-primary btn-xs"><?php esc_html_e('Activate','botblocker-security'); ?></button>
                                                        <?php endif; ?>
                                                    </form>
                                                    <?php if($bbcs_updateAvail && $bbcs_remote): ?>
                                                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="bbcs-inline m-0">
                                                            <input type="hidden" name="action" value="bbcs_update_addon">
                                                            <input type="hidden" name="slug" value="<?php echo esc_attr($bbcs_slug); ?>">
                                                            <input type="hidden" name="url" value="<?php echo esc_attr($bbcs_remote['url']); ?>">
                                                            <?php wp_nonce_field('bbcs_update_addon','bbcs_update_addon_nonce'); ?>
                                                            <button type="submit" class="btn bbcs-btn-addons btn-warning btn-xs"><?php esc_html_e('Update','botblocker-security'); ?></button>
                                                        </form>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                                <?php if($bbcs_tools_link): ?><a href="<?php echo esc_url( $bbcs_tools_link ); ?>" class="btn bbcs-btn-addons btn-outline-secondary btn-xs" title="Addon Settings"><i class="fa-solid fa-gear"></i></a><?php endif; ?>
                                                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="bbcs-inline bbcs-ml-auto m-0">
                                                    <input type="hidden" name="action" value="bbcs_delete_addon">
                                                    <input type="hidden" name="slug" value="<?php echo esc_attr($bbcs_slug); ?>">
                                                    <?php wp_nonce_field('bbcs_delete_addon','bbcs_delete_addon_nonce'); ?>
                                                    <button type="submit" class="btn bbcs-btn-addons btn-light btn-xs" title="Delete" onclick="return confirm('Delete this add-on?');"><i class="fa-regular fa-trash-can"></i></button>
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
        <div class="col-xs-12 col-sm-12 col-md-3 сol-lg-3 col-xl-3 col-xxl-2">
            <?php include('botblocker-section-right-sidebar.php'); ?>
        </div>
    </div>
</section>
