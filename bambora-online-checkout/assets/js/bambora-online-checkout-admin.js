/**
 * Copyright (c) 2017. All rights reserved Bambora Online.
 *
 * This program is free software. You are allowed to use the software but NOT allowed to modify the software.
 * It is also not legal to do any changes to the software and distribute it in your own name / brand.
 *
 * All use of the payment modules happens at your own risk. We offer a free test account that you can use to test the module.
 *
 * @author    Bambora Online
 * @copyright Bambora Online (https://bambora.com)
 * @license   Bambora Online
 */

jQuery(document).ready(function () {
    /**
     * Post an action to admin-post.php. The order id and nonce are read from the clicked button.
     * A separate form is used since the meta boxes are inside the order edit form.
     */
    var postAction = function (button, action, fields) {
        var form = jQuery("<form>", {
            method: "POST",
            action: bambora_online_checkout_admin.admin_post_url
        });
        fields.action = action;
        fields.order_id = jQuery(button).data("order-id");
        fields.bambora_nonce = jQuery(button).data("nonce");
        jQuery.each(fields, function (name, value) {
            form.append(jQuery("<input>", { type: "hidden", name: name, value: value }));
        });
        form.appendTo("body").trigger("submit");
    };

    jQuery(".bambora_amount").keydown(function (e) {
        var digit = String.fromCharCode(e.which || e.keyCode);
        if (e.which !== 8 && e.which !== 46 && !(e.which >= 37 && e.which <= 40) && e.which !== 110 && e.which !== 188
            && e.which !== 190 && e.which !== 35 && e.which !== 36 && !(e.which >= 96 && e.which <= 106)) {
            var reg = new RegExp(/^(?:\d+(?:,\d{0,3})*(?:\.\d{0,2})?|\d+(?:\.\d{0,3})*(?:,\d{0,2})?)$/);

            return reg.test(digit);
        }
    });

    jQuery("#woocommerce_bambora_limitforlowvalueexemption").keydown(function (e) {
        var digit = String.fromCharCode(e.which || e.keyCode);

        if (e.which !== 8 && e.which !== 46 && !(e.which >= 37 && e.which <= 40) && e.which !== 110 && e.which !== 188
            && e.which !== 190 && e.which !== 35 && e.which !== 36 && !(e.which >= 96 && e.which <= 106)) {
            var reg = new RegExp(/^(?:\d+(?:,\d{0,3})*(?:\.\d{0,2})?|\d+(?:\.\d{0,3})*(?:,\d{0,2})?)$/);
            return reg.test(digit);
        }
    });

    jQuery("#bambora_capture_submit")
        .click(function (e) {
            e.preventDefault();
            var inputField = jQuery("#bambora_capture_amount");
            var reg = new RegExp(/^(?:[\d]+([,.]?[\d]{0,3}))$/);
            var amount = inputField.val();
            if (inputField.length > 0 && !reg.test(amount)) {
                inputField.addClass("boc-input-error");
                jQuery("#bambora-format-error").show();
                return false;
            }
            inputField.removeClass("boc-input-error");
            jQuery("#bambora-format-error").hide();

            var messageDialogText = jQuery("#bambora_capture_message").val();

            var confirmResult = confirm(messageDialogText);
            if (confirmResult === false) {
                return false;
            }
            postAction(this, "bambora_online_checkout_payment_action", {
                bambora_action: "capture",
                amount: amount,
                currency: jQuery("#bambora_currency").val()
            });
        });

    jQuery("#bambora_create_pr_submit")
        .click(function (e) {
            e.preventDefault();
            var description = jQuery("#bambora_pr_description").val();
            var messageDialogText = jQuery("#bambora_create_pr_message").val();

            var confirmResult = confirm(messageDialogText);
            if (confirmResult === false) {
                return false;
            }
            postAction(this, "bambora_online_checkout_paymentrequest_action", {
                bambora_paymentrequest_action: "create_pr",
                description: description
            });
        });

    jQuery("#bambora_send_pr_submit")
        .click(function (e) {
            e.preventDefault();
            var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;
            var recipientField = jQuery("#bambora_pr_recipient_email");
            var replytoField = jQuery("#bambora_pr_replyto_email");
            var recipient_email = jQuery.trim(recipientField.val());
            var replyto_email = jQuery.trim(replytoField.val());

            // The recipient is required. The reply-to email can be left empty.
            var recipientValid = emailRegex.test(recipient_email);
            var replytoValid = replyto_email === "" || emailRegex.test(replyto_email);
            recipientField.toggleClass("boc-input-error", !recipientValid);
            replytoField.toggleClass("boc-input-error", !replytoValid);
            jQuery("#bambora-email-error").toggle(!recipientValid || !replytoValid);
            if (!recipientValid || !replytoValid) {
                (recipientValid ? replytoField : recipientField).trigger("focus");
                return false;
            }

            var messageDialogText = jQuery("#bambora_send_pr_message").val().replace("{email}", recipient_email);
            var confirmResult = confirm(messageDialogText);
            if (confirmResult === false) {
                return false;
            }
            postAction(this, "bambora_online_checkout_paymentrequest_action", {
                bambora_paymentrequest_action: "send_pr",
                recipient_name: jQuery("#bambora_pr_recipient_name").val(),
                recipient_email: recipient_email,
                replyto_name: jQuery("#bambora_pr_replyto_name").val(),
                replyto_email: replyto_email,
                email_message: jQuery("#bambora_pr_email_message").val()
            });
        });

    jQuery("#bambora_pr_url_copy")
        .click(function (e) {
            e.preventDefault();
            var button = jQuery(this);
            var field = jQuery("#bambora_pr_url");
            var showCopied = function () {
                if (!button.data("label")) {
                    button.data("label", button.text());
                }
                button.text(button.data("copied"));
                setTimeout(function () {
                    button.text(button.data("label"));
                }, 2000);
            };
            // The clipboard API is only available on https and localhost, so the old copy command is the fallback.
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(field.val()).then(showCopied);
            } else {
                field.trigger("focus").trigger("select");
                if (document.execCommand("copy")) {
                    showCopied();
                }
            }
        });

    jQuery("#bambora_delete_pr_submit")
        .click(function (e) {
            e.preventDefault();
            var messageDialogText = jQuery("#bambora_delete_pr_message").val();
            var confirmResult = confirm(messageDialogText);
            if (confirmResult === false) {
                return false;
            }
            postAction(this, "bambora_online_checkout_paymentrequest_action", {
                bambora_paymentrequest_action: "delete_pr"
            });
        });

    jQuery("#bambora_delete_submit")
        .click(function (e) {
            e.preventDefault();
            var messagDialogText = jQuery("#bambora_delete_message").val();
            var confirmResult = confirm(messagDialogText);
            if (confirmResult === false) {
                return false;
            }
            postAction(this, "bambora_online_checkout_payment_action", {
                bambora_action: "delete"
            });
        });
    if (jQuery('#isCollectorTrue').length) {
        jQuery("#order_line_items .refund_line_total").prop("readonly", true);
        jQuery("#order_line_items .refund_line_tax").prop("readonly", true);
    }

});
