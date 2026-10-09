'use strict';

import $ from 'jquery';

// Sicherstellen, dass jQuery global verfügbar ist bevor Zebra_DatePicker geladen wird
window.$ = $;
window.jQuery = $;

// Zebra_DatePicker als jQuery Plugin manuell laden
// Das Plugin erwartet window.jQuery
(function() {
    // Dynamischer Import, um sicherzustellen dass jQuery zuerst gesetzt ist
    require('zebra_datepicker');
})();

// CSS importieren
import 'zebra_datepicker/dist/css/default/zebra_datepicker.min.css';

export default $;

