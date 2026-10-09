/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// jQuery must be set up globally FIRST, before any other scripts
// Zebra_DatePicker wird ebenfalls in jquery-global.js geladen
import './script/jquery-global';

// any CSS you import will output into a single css file (app.css in this case)
import './styles/app.scss';
import './styles/style.css';
import './styles/app.min.css';
import './styles/logger.css';


import './script/background-form';
import './script/core';

import './script/csw-harvester';
import './script/ui';
import './script/xpath';
import './script/address';
import './script/plugin';
import './script/storage';
import './script/session';
import './script/prism';
import './script/ui/tooltip';
import './script/ui/modal-dialog';
import './script/ui/checkbox';
import './script/logger';

import './script/basicProfile/operateson';
import './script/basicProfile/help.js';
import './script/basicProfile/multifieldset';
import './script/basicProfile/multiform';
import './script/basicProfile/multiinput';
import './script/basicProfile/profile';
import './script/basicProfile/validator';
