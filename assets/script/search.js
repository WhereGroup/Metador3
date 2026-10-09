'use strict';

import $ from 'jquery';
import Storage from './storage.js';
import {Browser} from "../typescript/service/Browser";

var Search = function() {
    Storage.apply(this, arguments);
};

Search.prototype = {
    '__proto__': Storage.prototype,

    searchFieldElement: null,
    searchResultElement: null,
    timeoutDelay: 300,
    timeoutId: null,

    init: function() {
        var self = this;
        this.searchFieldElement  = $('#searchfield');
        this.searchResultElement = $('#search-result');

        if (this.searchFieldElement.length === 0) {
            return;
        }

        $(document).on('change', '.-js-search-filter', function() {
            self.set($(this).attr('name'), $(this).val());
            self.set('page', 1);
            self.find();
        });

        $(document).on('keyup', '#searchfield', function() {
            search.keyup();
        });

        $(document).on('click', '[data-change-page]', function() {
            self.set('page', $(this).attr('data-change-page'));
            self.find();
        });

        $(document).on('click', '.-js-csv-download', function() {
            const url = self.searchFieldElement.attr('data-url');
            const form = self.createForm();
            const formData = new FormData(form);
            formData.set('download', 'true');
            Browser.fetch(url, 'POST', formData, 'text/plain')
                .then(response => {
                    // console.log(response);
                    // const csv: string = await response.text();
                    const a = document.createElement('a');
                    a.href = 'data:text/csv;charset=utf-8,'+encodeURI(response);
                    a.target = '_blank';
                    a.download = 'Download.csv';
                    document.body.appendChild(a);
                    a.click();
                    a.remove();
                })
                .catch(e => {
                    console.error(e);
                });
        });

        $(document).on('click', '.-js-reset-search', function() {
            self.setObject('search-params', {
                'source': self.get('source')
            });
            window.location.reload();
        });
    },

    updateFilters: function() {
        var self = this;

        $('.-js-search-filter').each(function () {
            const name         = $(this).attr('name');
            const defaultValue = $(this).attr('data-value');
            const value        = self.get(name);

            if (typeof value === 'undefined') {
                $('.-js-search-filter[name="' + name + '"]').val(defaultValue);
            }
        });
    },

    initFilters: function() {
        var self = this;

        if (this.searchFieldElement.length === 0) {
            return;
        }

        var source = self.get('source', null);

        if (source !== null) {
            $('.-js-source[data-slug="' + source + '"]').click();
        }

        self.set('page', 1);
        self.set('hits', 10);
        self.searchFieldElement.val(self.get('terms', ''));

        $('.-js-search-filter').each(function () {
            var name         = $(this).attr('name');
            var defaultValue = $(this).attr('data-value');
            var value        = self.get(name);

            if (typeof value === 'undefined') {
                value = defaultValue;
            }

            $('.-js-search-filter[name="' + name + '"]').val(value).change();
        });
    },

    getAll: function() {
        return this.getObject('search-params', {});
    },

    get: function(key, defaultValue) {
        var params = this.getObject('search-params', {});

        if (params[key]) {
            return params[key];
        }

        return defaultValue;
    },

    set: function(key, value) {
        var params = this.getObject('search-params', {});
        params[key] = value;
        this.setObject('search-params', params);
    },

    keyup: function() {
        var self = this;

        if (typeof self.timeoutId !== 'undefined') {
            window.clearTimeout(self.timeoutId);
        }

        self.timeoutId = window.setTimeout(function() {
            self.timeoutId = undefined;
            self.set('page', 1);
            self.set('terms', self.searchFieldElement.val());
            self.find();
        }, self.timeoutDelay);
    },

    find: function() {
        var self = this;

        // Nichts tun, wenn kein Suchfeld auf der Seite existiert
        // (verhindert, dass find() auf Nicht-Home-Seiten wie /public/export/html/{id}
        // trotzdem einen POST-Request an metador_search auslöst).
        if (!self.searchFieldElement || self.searchFieldElement.length === 0) {
            return;
        }

        if (typeof self.timeoutId !== 'undefined') {
            window.clearTimeout(self.timeoutId);
        }

        self.timeoutId = window.setTimeout(function() {
            self.set('source', $('.-js-source.active').attr('data-slug'));
            const url = self.searchFieldElement.attr('data-url');

            if (typeof url === 'undefined' || url === null) {
                return;
            }
            const form = self.createForm();
            const formData = new FormData(form);
            Browser.fetch(url, 'POST', formData, 'application/json')
                .then(response => {
                    self.searchResultElement.html('');
                    if (response && response.status && response.status === "error") {
                        self.searchResultElement.html(response.status);
                        return;
                    }
                    if (response && response.html) {
                        self.searchResultElement.html(response.html);
                    }
                    metador.parseResponse(response);
                })
                .catch(e => {
                        self.searchResultElement.html('');
                        self.searchResultElement.html(e.statusText);
                        console.error(e);
                });
        }, self.timeoutDelay);
    },

    createForm: function (url) {
        var form = document.createElement("form");
        form.method = "POST";
        form.action = url;
        var data = this.getAll();
        var addField = function (key, value, form) {
            var f =  document.createElement("input");
            f.type = "hidden";
            f.name = key;
            f.value = value;
            form.appendChild(f);
        };
        var key;
        for (key in data) {
            var d = data[key];
            if (key === "spatial") {
                d = JSON.stringify(d);
            }
            addField(key, d, form);
        }
        return form;
    },

    clearMetadataMarks: function() {
        $('[data-metadata-uuid]').removeClass('marked');
    },

    markMetadata: function(uuid) {
        $('[data-metadata-uuid="' + uuid + '"]').addClass('marked');
    },

    unmarkMetadata: function(uuid) {
        $('[data-metadata-uuid="' + uuid + '"]').removeClass('marked');
    }
};

var search = new Search();

// Als Global bereitstellen, damit andere Skripte (z.B. ui.js) darauf zugreifen können
if (typeof window !== 'undefined') {
    window.search = search;
}

search.init();


$( document ).ready(function() {
    search.initFilters();
    search.find();
});

export default search;
