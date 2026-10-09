'use strict';

import $ from 'jquery';
import Storage from './storage.js';

var Xpath = function() {
    Storage.apply(this, arguments);
};

Xpath.prototype = {
    '__proto__': Storage.prototype,

    searchFieldElement: null,
    searchResultElement: null,
    timeoutDelay: 200,
    timeoutId: null,

    init: function() {
        var self = this;
        self.searchFieldElement  = $('.-js-find-xpath');
        self.searchResultElement = $('.-js-find-xpath-result');

        $(document).on('keyup', '.-js-find-xpath', function() {
            self.keyup();
        });
    },

    keyup: function() {
        var self = this;

        if (typeof self.timeoutId !== 'undefined') {
            window.clearTimeout(self.timeoutId);
        }

        self.timeoutId = window.setTimeout(function() {
            self.timeoutId = undefined;
            self.find();
        }, self.timeoutDelay);
    },

    find: function() {
        var self = this;

        // Nichts tun, wenn kein XPath-Suchfeld auf der Seite existiert.
        // Sonst würde $.ajax({url: undefined, type: 'POST', ...}) auf die
        // aktuelle Seiten-URL zurückfallen und dort einen POST erzeugen.
        if (!self.searchFieldElement || self.searchFieldElement.length === 0) {
            return;
        }

        const url = self.searchFieldElement.attr('data-url');
        if (typeof url === 'undefined' || url === null || url === '') {
            return;
        }

        if (typeof self.timeoutId !== 'undefined') {
            window.clearTimeout(self.timeoutId);
        }

        self.timeoutId = window.setTimeout(function() {
            $.ajax({
                'url': url,
                'type': 'POST',
                'dataType': 'json',
                'data': {
                    'xpath': self.searchFieldElement.val()
                },
                'success': function(data) {
                    self.searchResultElement.html('');

                    if (data && data.html) {
                        self.searchResultElement.html(data.html);
                    }

                    metador.parseResponse(data);
                },
                'error': function() {
                    self.searchResultElement.html('');
                }
            });
        }, self.timeoutDelay);
    }
};

var xpath = new Xpath();

$( document ).ready(function() {
    xpath.init();

    // find() nur auslösen, wenn tatsächlich ein XPath-Suchfeld existiert.
    if ($('.-js-find-xpath').length > 0) {
        xpath.find();
    }
});
