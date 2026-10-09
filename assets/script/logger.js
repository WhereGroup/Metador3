$(document).on('change', '.-js-show-logs', function() {
    var params = {};
    var resultElement = $('#results');

    if (!$(this).hasClass('-ls-show-logs-page')) {
        $('.-ls-show-logs-page').val('1');
    }

    $('.-js-show-logs').each(function() {
        if ($(this).val() !== "") {
            params[$(this).attr('name')] = $(this).val();
        }
    });

    $.ajax({
        url: Configuration.basedir + "admin/logging/ajax/",
        type: 'GET',
        data: params
    }).done(function(data) {
        if (data && data.template) {
            resultElement.html(data.template);
        }
    }).fail(function(error) {
        metador.displayError("Es ist ein Fehler aufgetreten");
    });
});

$(document).on('click', '[data-change-page]', function() {
    $('.-ls-show-logs-page').val($(this).attr('data-change-page')).change();
});
