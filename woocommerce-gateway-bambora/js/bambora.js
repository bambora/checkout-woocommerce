jQuery(document).ready(function () {
    jQuery(".bambora_amount").keydown(function (e) {
        var digit = String.fromCharCode(e.which || e.keyCode);
        if (e.which != 8 && e.which != 46 && !(e.which >= 37 && e.which <= 40) && e.which != 110 && e.which != 188
            && e.which != 190 && e.which != 35 && e.which != 36 && !(e.which >= 96 && e.which <= 106)) {
            var reg = new RegExp(/^(?:\d+(?:,\d{0,3})*(?:\.\d{0,2})?|\d+(?:\.\d{0,3})*(?:,\d{0,2})?)$/);
            
            return reg.test(digit);
        }
    });
});

function bambora_action_url(confirmMessage, adminUrl, extentions) {
    
    if (confirm(confirmMessage)) {
        location.href = adminUrl + extentions;
    } else {
        return false;
    }
}