$(document).on('click', '.-js-get-harvest-hits', function() {
    var self = this;
    $.ajax({
        url: Configuration.basedir + 'admin/csw/harvester/hits/' + $(self).attr('data-id'),
        type: 'GET',
        dataType: "json",
        success: function (data) {
            if (data && data.hits) {
                $(self)
                    .closest('.-js-harvester-parent')
                    .find('.-js-harvester-hits')
                    .text(data.hits);
            }

            metador.parseResponse(data);
        }
    });
});
