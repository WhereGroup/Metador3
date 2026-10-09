'use strict';

import $ from 'jquery';
import {Browser} from "../typescript/service/Browser";

var BackgroundProcess = function() {};

BackgroundProcess.prototype = {
    interval: 5000,
    iconPlay: 'icon-play',
    iconPause: 'icon-pause',
    iconStop: 'icon-stop',

    activateOperation: function(form) {
        $(form).find('[data-bg-process-icon]').removeClass(this.iconStop).removeClass(this.iconPause).addClass(this.iconPlay);
        $(form).find('[data-bg-process-button]').removeAttr('disabled');
    },

    enableOperation: function(form) {
        $(form).removeAttr('data-handle');
        $(form).find('[data-bg-process-icon]').removeClass(this.iconStop).removeClass(this.iconPause).addClass(this.iconPlay);
        $(form).find('[data-bg-process-button]').removeAttr('disabled');
    },

    disableOperation: function(form, handle) {
        $(form).attr('data-handle', handle);
        $(form).find('[data-bg-process-icon]').removeClass(this.iconPlay).addClass(this.iconStop);
        $(form).find('[data-bg-process-button]').attr('disabled');
    },

    checkStatus: function(form, initial = false) {
        const self = this;
        const handle = $(form).attr('data-bg-process');

        $.ajax({
            'url': Configuration.basedir + 'process/' + handle,
            'type': 'GET',
            'dataType': 'json',
            'success': function(data) {
                if (!data || typeof data.process === 'undefined') {
                    return;
                }

                if (data.process === true) {
                    self.disableOperation(form, handle);
                } else if (data.process === false && !initial) {
                    self.enableOperation(form);
                } else if (data.process === false && initial) {
                    self.activateOperation(form);
                }
            }
        });
    },

    init: function() {
        const self = this;
        const $processForms = $('form[data-bg-process]');

        if ($processForms.length === 0) {
            return;
        }

        // if (typeof $.fn.ajaxForm !== 'function') {
        //     console.error('jquery-form Plugin ist nicht geladen. Bitte in jquery-global.js sicherstellen.');
        //     return;
        // }
        //
        // $processForms.ajaxForm({
        //     target: this,
        //     dataType: 'json',
        //     success: function(data, statusText, xhr, form) {
        //         if (data && data.handle) {
        //             self.disableOperation(form, data.handle);
        //         }
        //     }
        // });
        const forms = document.querySelectorAll('form[data-bg-process]');
        for (const form of forms) {
            form.addEventListener('submit', (event) => {
                try {
                    event.preventDefault();
                    const formData = new FormData(form);
                    Browser.fetch(form.getAttribute("action"), form.getAttribute("method"), formData, 'application/json')
                        .then(data => {
                            if (data && data.handle) {
                                self.disableOperation(form, data.handle);
                            }
                        });
                } catch (error) {
                    console.error(error);
                }
                return false;
            });
        }

        $processForms.each(function() {
            self.checkStatus(this, true);
        });

        setInterval(function() {
            $('form[data-bg-process][data-handle]').each(function() {
                self.checkStatus(this);
            });
        }, this.interval);
    }
};

const backgroundProcess = new BackgroundProcess();

$(function() {
    backgroundProcess.init();
});
