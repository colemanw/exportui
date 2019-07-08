<?php
// This file declares an Angular module which can be autoloaded
// in CiviCRM. See also:
// http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_angularModules

return [
  'js' => [
    'ang/exportui.js',
    'ang/exportui/*.js',
    'ang/exportui/*/*.js',
  ],
  'css' => [
    'ang/exportui.css',
  ],
  'partials' => [
    'ang/exportui',
  ],
  'basePages' => [],
  'requires' => [
    'crmUi',
    'crmUtil',
  ],
  'settings' => [],
];
