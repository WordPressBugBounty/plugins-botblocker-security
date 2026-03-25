<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

include('botblocker-section-header.php');

?><section role="main" class="content-body">
    <div class="row">
        <div class="col-xs-12 col-sm-12 col-md-9 сol-lg-9 col-xl-9 col-xxl-10">
            <div class="row">
                <div class="col-md-6">
                    <section class="card bbcs-fill-height">
                        <header class="card-header">
                            <h2 class="card-title"><?php esc_html_e( 'Contacts and Support', 'botblocker-security'); ?></h2>
                        </header>
                        <div class="card-body">
							<?php
								include_once BOTBLOCKER_DIR . 'includes/section/about/botblocker-about-contacts.php';
							?>
                        </div>
                    </section>
                </div>
                <div class="col-md-6">
                    <section class="card bbcs-fill-height">
                        <header class="card-header">
                            <h2 class="card-title"><?php esc_html_e( 'Legal Information', 'botblocker-security'); ?></h2>
                        </header>
                        <div class="card-body">
							<?php
								include_once BOTBLOCKER_DIR . 'includes/section/about/botblocker-about-legal.php';
							?>							
                        </div>
                    </section>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <section class="card">
                        <header class="card-header">
                            <h2 class="card-title"><?php esc_html_e( 'BotBlocker PRO', 'botblocker-security'); ?></h2>
                        </header>
                        <div class="card-body">
							<?php
								include_once BOTBLOCKER_DIR . 'includes/section/about/botblocker-about-status.php';
							?>
                        </div>
                    </section>
                </div>
            </div>
        </div>

        <div class="col-xs-12 col-sm-12 col-md-3 сol-lg-3 col-xl-3 col-xxl-2">
            <?php include('botblocker-section-right-sidebar.php'); ?>
        </div>
    </div>
</section>
