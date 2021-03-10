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
 *
 */

jQuery(document).ready(function () {
    jQuery( ".bambora_amount" ).keydown(function (e) {
        var digit = String.fromCharCode( e.which || e.keyCode );
        if (e.which !== 8 && e.which !== 46 && ! (e.which >= 37 && e.which <= 40) && e.which !== 110 && e.which !== 188
            && e.which !== 190 && e.which !== 35 && e.which !== 36 && ! (e.which >= 96 && e.which <= 106)) {
            var reg = new RegExp( /^(?:\d+(?:,\d{0,3})*(?:\.\d{0,2})?|\d+(?:\.\d{0,3})*(?:,\d{0,2})?)$/ );

            return reg.test( digit );
        }
    });

    jQuery( "#bambora_capture_submit" )
        .click(function (e) {
            e.preventDefault();
            var inputField = jQuery( "#bambora_capture_amount" );
            var reg = new RegExp( /^(?:[\d]+([,.]?[\d]{0,3}))$/ );
            var amount = inputField.val();
            if (inputField.length > 0 && ! reg.test( amount )) {
                jQuery( "#bambora-format-error" ).toggle();
                return false;
            }

            var messagDialogText = jQuery( "#bambora_capture_message" ).val();

            var confirmResult = confirm( messagDialogText );
            if (confirmResult === false) {
                return false;
            }
            var currency = jQuery("#bambora_currency").val();
            var nonce = jQuery("#bambora_nonce").val();
            var params = "&bambora_action=capture&amount=" + amount + "&currency=" + currency + "&bambora_nonce=" + nonce;
            var url = window.location.href + params;

            window.location.href = url;
        });

    jQuery( "#bambora_delete_submit" )
        .click(function (e) {
            e.preventDefault();
            var messagDialogText = jQuery( "#bambora_delete_message" ).val();
            var confirmResult = confirm( messagDialogText );
            if (confirmResult === false) {
                return false;
            }
            var nonce = jQuery("#bambora_nonce").val();
            var params = "&bambora_action=delete&bambora_nonce=" + nonce;
            var url = window.location.href + params;

            window.location.href = url;
            return true;
        });
    if(jQuery('#isCollectorFalse').length){
        jQuery("#order_line_items .refund_line_total").prop("readonly", true);
        jQuery("#order_line_items .refund_line_tax").prop("readonly", true);
     }

});
