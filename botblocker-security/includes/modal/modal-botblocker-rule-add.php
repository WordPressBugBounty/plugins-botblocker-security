<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
?><div class="modal fade" id="createRuleModal" tabindex="-1" aria-labelledby="createRuleModalLabel"
    aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createRuleModalLabel"><?php esc_html_e('Create Rule', 'botblocker-security'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="createRuleForm">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="bbcs_text_input">
                                <div class="bbcs_label_input_box">
                                    <span class="bbcs-label-input"><?php esc_html_e('Type', 'botblocker-security'); ?></span>
                                    <i class="fa-regular fa-circle-question" data-bs-toggle="tooltip"
                                        data-bs-placement="top"
                                        data-bs-original-title="<?php esc_attr_e('Select the type of rule to apply', 'botblocker-security'); ?>"></i>
                                </div>
                                <div class="bbcs_text_input_inner">
                                    <select class="bbcs_select_input_input" id="type" name="type" required>
                                        <option value="useragent"><?php esc_html_e('User Agent', 'botblocker-security'); ?>
                                        </option>
                                        <option value="ptr"><?php esc_html_e('PTR', 'botblocker-security'); ?></option>
                                        <option value="referer"><?php esc_html_e('Referer', 'botblocker-security'); ?></option>
                                        <option value="lang"><?php esc_html_e('Language', 'botblocker-security'); ?></option>
                                        <option value="uri"><?php esc_html_e('URI', 'botblocker-security'); ?></option>
                                        <option value="httpaccept"><?php esc_html_e('HTTP Accept', 'botblocker-security'); ?>
                                        </option>
                                        <option value="page"><?php esc_html_e('Page', 'botblocker-security'); ?></option>
                                        <option value="asname"><?php esc_html_e('AS Name', 'botblocker-security'); ?></option>
                                        <option value="asnum"><?php esc_html_e('AS Number', 'botblocker-security'); ?></option>
                                        <option value="country"><?php esc_html_e('Country', 'botblocker-security'); ?></option>
                                        <option value="scriptname"><?php esc_html_e('Script Name', 'botblocker-security'); ?>
                                        </option>
                                        <option value="ym_uid"><?php esc_html_e('Yandex.Metrica ID', 'botblocker-security'); ?>
                                        </option>
                                        <option value="ga_uid"><?php esc_html_e('Google Analytics ID', 'botblocker-security'); ?>
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="bbcs_text_input">
                                <div class="bbcs_label_input_box">
                                    <span class="bbcs-label-input"><?php esc_html_e('Priority', 'botblocker-security'); ?></span>
                                    <i class="fa-regular fa-circle-question" data-bs-toggle="tooltip"
                                        data-bs-placement="top"
                                        data-bs-original-title="<?php esc_attr_e('Set the priority of the rule (1-100)', 'botblocker-security'); ?>"></i>
                                </div>
                                <div class="bbcs_text_input_inner">
                                    <input type="range" class="bbcs_text_input_input" id="priority" name="priority"
                                        min="1" max="100" required>
                                    <output for="priority" id="priorityValue"></output>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="bbcs_text_input">
                                <div class="bbcs_label_input_box">
                                    <span class="bbcs-label-input"><?php esc_html_e('Data', 'botblocker-security'); ?></span>
                                    <i class="fa-regular fa-circle-question" data-bs-toggle="tooltip"
                                        data-bs-placement="top"
                                        data-bs-original-title="<?php esc_attr_e('Enter the data for the rule', 'botblocker-security'); ?>"></i>
                                </div>
                                <div class="bbcs_text_input_inner">
                                    <textarea class="bbcs_text_input_input" id="data" name="data" rows="3"></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="bbcs_text_input">
                                <div class="bbcs_label_input_box">
                                    <span class="bbcs-label-input"><?php esc_html_e('Comment', 'botblocker-security'); ?></span>
                                    <i class="fa-regular fa-circle-question" data-bs-toggle="tooltip"
                                        data-bs-placement="top"
                                        data-bs-original-title="<?php esc_attr_e('Add a comment for this rule', 'botblocker-security'); ?>"></i>
                                </div>
                                <div class="bbcs_text_input_inner">
                                    <textarea class="bbcs_text_input_input" id="comment" name="comment"
                                        rows="3"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="bbcs_text_input">
                                <div class="bbcs_label_input_box">
                                    <span class="bbcs-label-input"><?php esc_html_e('Rule', 'botblocker-security'); ?></span>
                                    <i class="fa-regular fa-circle-question" data-bs-toggle="tooltip"
                                        data-bs-placement="top"
                                        data-bs-original-title="<?php esc_attr_e('Select the action for this rule', 'botblocker-security'); ?>"></i>
                                </div>
                                <div class="bbcs_text_input_inner">
                                    <select class="bbcs_select_input_input" id="rule" name="rule" required>
                                        <option value="allow"><?php esc_html_e('Allow', 'botblocker-security'); ?></option>
                                        <option value="gray"><?php esc_html_e('Gray', 'botblocker-security'); ?></option>
                                        <option value="dark"><?php esc_html_e('Dark', 'botblocker-security'); ?></option>
                                        <option value="block"><?php esc_html_e('Block', 'botblocker-security'); ?></option>
                                        <option value="permanently_ban">
                                            <?php esc_html_e('Permanently Ban', 'botblocker-security'); ?></option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="bbcs_text_input">
                                <div class="bbcs_label_input_box">
                                    <span class="bbcs-label-input"><?php esc_html_e('Expires', 'botblocker-security'); ?></span>
                                    <i class="fa-regular fa-circle-question" data-bs-toggle="tooltip"
                                        data-bs-placement="top"
                                        data-bs-original-title="<?php esc_attr_e('Set the expiration date and time for this rule', 'botblocker-security'); ?>"></i>
                                </div>
                                <div class="bbcs_text_input_inner">
                                    <input type="datetime-local" class="bbcs_text_input_input" id="expires"
                                        name="expires" required>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-xs"
                    data-bs-dismiss="modal"><?php esc_html_e('Close', 'botblocker-security'); ?></button>
                <button type="submit" form="createRuleForm"
                    class="btn btn-primary btn-xs"><?php esc_html_e('Save changes', 'botblocker-security'); ?></button>
            </div>
        </div>
    </div>
</div>
