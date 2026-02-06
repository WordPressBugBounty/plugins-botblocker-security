(function ($) {
    $(document).on('click', '.delete-plugin', function (e) {
        e.preventDefault();
        const pluginName = $(this).data('plugin');
        const modalContent = `
            <p>Please tell us why you are uninstalling this plugin:</p>
            <form id="bbcsUninstallForm">
                <div class="form-group">
                    <label for="bbcsReason">Select a reason</label>
                    <select id="bbcsReason" name="bbcsReason" required>
                        <option value="not_needed">I don't need this plugin anymore</option>
                        <option value="not_working">The plugin is not working as expected</option>
                        <option value="better_alternative">I found a better alternative</option>
                        <option value="other">Other (please specify below)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="bbcsOtherReason">Other reason (optional)</label>
                    <textarea id="bbcsOtherReason" name="bbcsOtherReason" rows="3"></textarea>
                </div>
            </form>
        `;
        const $dialog = $('<div></div>')
            .html(modalContent)
            .dialog({
                title: 'Reason for uninstalling',
                modal: true,
                buttons: {
                    Cancel: function () {
                        $(this).dialog('close');
                    },
                    'Submit and Uninstall': function () {
                        const reason = $('#bbcsReason').val();
                        const otherReason = $('#bbcsOtherReason').val();

                        document.cookie = `bbcs_uninstall_reason=${reason === 'other' ? otherReason : reason}; path=/`;

                        $(this).dialog('close');

                        window.location.href = $('.delete-plugin[data-plugin="' + pluginName + '"]').attr('href');
                    },
                },
            });
    });
})(jQuery);