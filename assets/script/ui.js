import $ from 'jquery';
const jQuery = $;

// Sicherstellen, dass jQuery global verfügbar ist für Plugins wie Zebra_DatePicker
if (typeof window !== 'undefined') {
    window.$ = $;
    window.jQuery = $;
}

$(document).on('click', '[data-tab-menu]', function() {
    let parent = $(this).closest('.-js-tabs');

    parent.find('[data-tab-menu]').removeClass('active');
    $(this).addClass('active');

    parent.find('[data-tab-content]').removeClass('active');
    parent.find('[data-tab-content="' + $(this).attr('data-tab-menu') + '"]').addClass('active');
});

$(document).on('click', '.-js-toggle-user-menu', function() {
    $(this).closest('.-js-user').toggleClass('active');
});

$(document).on('click', '.-js-toggle-info-box', function() {
    $(this).siblings('.-js-info-box-content').toggleClass('active');
});

$(document).on('click', '.-js-close-notify', function() {
    $(this).parent().remove();
});

$(document).on('mouseenter', '.-js-sys-msg', function() {
    $(this).addClass('large');
});

$(document).on('mouseleave', '.-js-sys-msg', function() {
    $(this).removeClass('large');
});

$(document).on('click', '.-js-fieldset-toggle', function() {
    var mainItem = $(this).closest('.-js-fieldset');

    $(this).find('.-js-fieldset-status').toggleClass('icon-minimize icon-maximize');

    if (mainItem.hasClass('minimize')) {
        mainItem.removeClass('minimize');
        return;
    }

    mainItem.addClass('minimize')
});

$(document).on('mousedown', '.-js-datepicker', function() {
    var self = this;
    $(self).Zebra_DatePicker({
        show_icon: false,
        offset:[-177,120],
        onSelect: function() {
            $(self).change();
        },
        onClear: function() {
            $(self).change();
        },
        //months: ['','']
        //days: ['','']
    });
});

$(document).on('mousedown', '.-js-date-time-picker', function() {
    var self = this;
    $(self).Zebra_DatePicker({
        show_icon: false,
        show_week_number: 'Wk',
        offset:[-177,120],
        format: 'Y-m-dTH:i:sZ',
        onSelect: function() {
            $(self).change();
        },
        onClear: function() {
            $(self).change();
        },
        //months: ['','']
        //days: ['','']
    });
});


$(document).ready(function() {
    $('.-js-show-tooltip').tooltip();
    $('.-js-checkbox').checkbox();

    $('.-js-source').on('click', function () {
        $(this).trigger( "change-tab", $(this).attr('data-slug'));
        $(this).siblings().removeClass('active');
        $(this).addClass('active');
        $('.-js-profile-menu').removeClass('active');
        $('#source-' + $(this).attr('data-slug')).addClass('active');
        $('#search-result').html('');

        if (typeof window.search !== 'undefined') {
            window.search.set('page', 1);
            window.search.find();
        }
    });
});

$(document).on('click', '.-js-close-notify', function () {
    $(this).parent().remove();
});



(function($) {

    var dialog = {
        opts: null,

        init: function(options) {
            dialog.opts = $.extend({
                modal: true,
                width: 600,
                height: 300,
                title: 'Title',
                html: '',
                buttons: {
                    'abort': {
                        'label': 'Abbrechen',
                        'type': 'info',
                        'click': function(result) {
                            $(this).modalDialog('close', false);
                        }
                    },
                    'ok': {
                        'label': 'Ok',
                        'type': 'info',
                        'click': function() {
                            $(this).modalDialog('close', true);
                        }
                    }
                },
                onClose: function(status) {
                }
            }, options);
        },

        open: function() {
            if(dialog.opts.modal) {
                $('<div>', {'class': 'modal'}).appendTo("body");
            }

            var head = $('<div>', {'class': 'head'})
                .text(dialog.opts.title);

            var body = $('<div>', {'class': 'body'}).html(dialog.opts.html);
            var foot = $('<div>', {'class': 'foot'});

            $.each(dialog.opts.buttons, function(button, value) {
                foot.append(
                    $('<span>', {'class': 'btn'})
                        .text(value.label)
                        .addClass(value.type)
                        .bind('click', value.click)
                );
            });

            $('<div>', {
                'class': 'dialog',
                css: {
                    'width': dialog.opts.width,
                    'height': dialog.opts.height,
                    'margin-left': -(dialog.opts.width / 2),
                    'margin-top': -(dialog.opts.height / 2)
                }
            }).append(head, body, foot).appendTo("body").fadeIn(100);

            dialog.opts.onOpen();
        },

        close: function(result) {
            $('.dialog').remove();
            $('.modal').remove();
            dialog.opts.onClose(result);
        }
    };

    $.fn.modalDialog = function(options) {
        if(dialog[options] ) {
            return dialog[options].apply( this, Array.prototype.slice.call( arguments, 1 ));
        } else if (typeof options === 'object' || ! options ) {
            return dialog.init.apply(this, arguments);
        } else {
            $.error( 'Method ' +  options + ' does not exist.' );
        }

        return this.each(function() {
        });
    };

})(jQuery);

