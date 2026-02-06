 (function ($) {
    "use strict";
    $('#addIpRuleForm').on("submit", function (e) {
        e.preventDefault();
        var form = $(this);
        $.ajax({
            url: botblockerData.ajaxurl,
            type: "POST",
            data: form.serialize() + "&action=bbcs_create_ip_rule&nonce=" + botblockerData.nonce,
            success: function (response) {
                if (response.success) {
                    form[0].reset();
                    alert('Success: IP rule added');
                } else {
                    alert("Failed to create rule: " + response.data);
                }
            },
        });
    });

    // IP List Import handlers
    $("#bbcs_ipv4_import_white").on("click", function () {
        importIPList("ipv4", "whitelist");
    });

    $("#bbcs_ipv4_import_black").on("click", function () {
        importIPList("ipv4", "blacklist");
    });

    $("#bbcs_ipv6_import_white").on("click", function () {
        importIPList("ipv6", "whitelist");
    });

    $("#bbcs_ipv6_import_black").on("click", function () {
        importIPList("ipv6", "blacklist");
    });

    function importIPList(ipVersion, listType) {
        var fileInput = $("<input>", {
            type: "file",
            accept: ".txt",
        }).on("change", function () {
            var file = this.files[0];
            if (file) {
                var reader = new FileReader();
                reader.onload = function (e) {
                    var fileContent = e.target.result;
                    $.ajax({
                        url: botblockerData.ajaxurl,
                        type: "POST",
                        data: {
                            action: "bbcs_import_" + ipVersion + "_" + listType,
                            file_content: fileContent,
                            nonce: botblockerData.nonce,
                        },
                        success: function (response) {
                            if (response.success) {
                                alert("Successfully imported " + ipVersion.toUpperCase() + " " + listType + ":\n" + 
                                      "Imported: " + response.data.imported + "\n" + 
                                      "Skipped: " + response.data.skipped);
                            } else {
                                alert("Failed to import " + ipVersion.toUpperCase() + " " + listType + ": " + response.data);
                            }
                        },
                    });
                };
                reader.readAsText(file);
            }
        });
        fileInput.click();
    }

    // Quick Rule Import handlers (existing functionality)
    $("#bbcs_ipv4_import").on("click", function () {
        var fileInput = $("<input>", {
            type: "file",
            accept: "application/json",
        }).on("change", function () {
            var file = this.files[0];
            if (file) {
                readJSONFile(file, function (data) {
                    $.ajax({
                        url: botblockerData.ajaxurl,
                        type: "POST",
                        data: {
                            action: "bbcs_import_ipv4_rules",
                            rules: JSON.stringify(data),
                            nonce: botblockerData.nonce,
                        },
                        success: function (response) {
                            if (response.success) {
                                alert(`Imported: ${response.data.imported} | Skipped ${response.data.skipped}`);
                            } else {
                                alert(
                                    "Failed to import IPv4 rules: " +
                                        response.data
                                );
                            }
                        },
                    });
                });
            }
        });
        fileInput.click();
    });

    $("#bbcs_ipv6_import").on("click", function () {
        var fileInput = $("<input>", {
            type: "file",
            accept: "application/json",
        }).on("change", function () {
            var file = this.files[0];
            if (file) {
                readJSONFile(file, function (data) {
                    $.ajax({
                        url: botblockerData.ajaxurl,
                        type: "POST",
                        data: {
                            action: "bbcs_import_ipv6_rules",
                            rules: JSON.stringify(data),
                            nonce: botblockerData.nonce,
                        },
                        success: function (response) {
                            if (response.success) {
                                alert(`Imported: ${response.data.imported} | Skipped ${response.data.skipped}`);
                            } else {
                                alert(
                                    "Failed to import IPv6 rules: " +
                                        response.data
                                );
                            }
                        },
                    });
                });
            }
        });
        fileInput.click();
    });

    function readJSONFile(file, callback) {
        var reader = new FileReader();
        reader.onload = function (e) {
            try {
                var data = JSON.parse(e.target.result);
                callback(data);
            } catch (err) {
                alert("Invalid JSON file: " + err.message);
            }
        };
        reader.readAsText(file);
    }
})(jQuery);
