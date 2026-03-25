<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
?><div class="modal fade" id="createPathModal" tabindex="-1" aria-labelledby="createPathModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createPathModalLabel"><?php esc_html_e('Create Path', 'botblocker-security'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="createPathForm">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="bbcs_text_input">
                                <div class="bbcs_label_input_box">
                                    <span class="bbcs-label-input"><?php esc_html_e('Priority', 'botblocker-security'); ?></span>
                                    <i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="<?php esc_attr_e('Path rule priority (1-100)', 'botblocker-security'); ?>"></i>
                                </div>
                                <div class="bbcs_text_input_inner">
                                    <input type="range" class="bbcs_text_input_input" id="priority" name="priority" min="1" max="100" required>
                                    <output for="priority" id="priorityValue"></output>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="bbcs_text_input">
                                <div class="bbcs_label_input_box">
                                    <span class="bbcs-label-input"><?php esc_html_e('Rule', 'botblocker-security'); ?></span>
                                    <i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="<?php esc_attr_e('Action for this path', 'botblocker-security'); ?>"></i>
                                </div>
                                <div class="bbcs_text_input_inner">
                                    <select class="bbcs_select_input_input" id="rule" name="rule" required>
                                        <option value="allow"><?php esc_html_e('Allow', 'botblocker-security'); ?></option>
                                        <option value="gray"><?php esc_html_e('Gray', 'botblocker-security'); ?></option>
                                        <option value="dark"><?php esc_html_e('Dark', 'botblocker-security'); ?></option>
                                        <option value="block"><?php esc_html_e('Block', 'botblocker-security'); ?></option>
                                        <option value="permanently_ban"><?php esc_html_e('Permanently Ban', 'botblocker-security'); ?></option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="bbcs_text_input">
                                <div class="bbcs_label_input_box">
                                    <span class="bbcs-label-input"><?php esc_html_e('Path (URL)', 'botblocker-security'); ?></span>
                                    <i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-placement="top" 
                                    data-bs-original-title="<?php esc_attr_e('Path substring or pattern to match', 'botblocker-security'); ?>"></i>
                                </div>
                                <div class="bbcs_text_input_inner">
                                    <textarea class="bbcs_text_input_input" id="search" name="search" rows="3" required></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="bbcs_text_input">
                                <div class="bbcs_label_input_box">
                                    <span class="bbcs-label-input"><?php esc_html_e('Comment', 'botblocker-security'); ?></span>
                                    <i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="<?php esc_attr_e('Comment for this path rule', 'botblocker-security'); ?>"></i>
                                </div>
                                <div class="bbcs_text_input_inner">
                                    <textarea class="bbcs_text_input_input" id="comment" name="comment" rows="3"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-xs" data-bs-dismiss="modal"><?php esc_html_e('Close', 'botblocker-security'); ?></button>
                <button type="submit" form="createPathForm" class="btn btn-primary btn-xs"><?php esc_html_e('Save', 'botblocker-security'); ?></button>
            </div>
        </div>
    </div>
</div>
