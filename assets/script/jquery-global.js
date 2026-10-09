'use strict';

// This file must be imported first to make jQuery available globally
// before any other scripts that depend on window.jQuery
import $ from 'jquery';

window.$ = $;
window.jQuery = $;

// jQuery Plugins müssen NACH dem Setzen von window.jQuery geladen werden
// Verwende require() statt import, da imports gehoisted werden
require('zebra_datepicker');
require('zebra_datepicker/dist/css/default/zebra_datepicker.min.css');

// jquery-form global laden, damit $.fn.ajaxForm überall verfügbar ist.
//
// Der UMD-Wrapper von jquery-form wird unter Webpack als AMD-Modul erkannt
// (`define(["jquery"], factory)`), Webpack löst `jquery` über unsere einzige
// jQuery-Instanz auf (siehe `overrides` in package.json) und registriert
// $.fn.ajaxForm direkt auf window.$.

// keine Verwendung mehr
// require('jquery-form');

