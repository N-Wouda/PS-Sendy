/**
 * @author    Apium | Niels Wouda <n.wouda@apium.nl>
 * @copyright 2016, Apium
 * @license   MIT <https://opensource.org/licenses/MIT>
 */
$(document).ready(function () {
    $("button[name=submitNewsletter]").click(function () {
        ajax_sendy_subscribe();
    });
});

function ajax_sendy_subscribe() {
    return $.ajax({
        type: 'POST',
        url: baseUri + 'module/sendynewsletterfree/subscribe',
        headers: {
            "cache-control": "no-cache"
        },
        dataType: 'JSON',
        data: {
            email: $("#newsletter-input").val(),
            ajax: true
        }
    });
}
