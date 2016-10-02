/**
 * @author    Apium | Niels Wouda <n.wouda@apium.nl>
 * @copyright 2016, Apium
 * @license   MIT <https://opensource.org/licenses/MIT>
 */
$(document).ready(function () {
    $("[name=submitNewsletter]").click(function () {
        ajax_sendy_subscribe($(this).prev('input[name=email]').val());
    });
});

function ajax_sendy_subscribe(email) {
    return $.ajax({
        type: 'POST',
        url: baseUri + 'module/sendynewsletterfree/subscribe',
        headers: {
            "cache-control": "no-cache"
        },
        dataType: 'JSON',
        data: {
            email: email,
            ajax: true
        }
    });
}
