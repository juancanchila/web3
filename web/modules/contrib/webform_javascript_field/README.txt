Description
-----------
This module adds support of JavaScript snippets for the Webform components.

Features
- use custom JavaScript code per webform component
- specify JavaScript file which will be loaded for the webform
  (it may contain helpers that will be used in the JavaScript
  snippet at the component edit page)

Requirements
------------
Drupal 7.x
Webform (>= 4.0)
Fieldgroup


Installation
------------
1. Copy the entire webform_javascript_field directory the Drupal
   sites/all/modules directory.

2. Login as an administrator. Enable the module in the "Administer" -> "Modules"
   (or you can use drush command: drush en webform_javascript_field).

Configuration
------------
At the component edit page (e.g. node/[nid]/webform/components/[cid]) you can
configure what JavaScript code will be used for the webform. For example you can
write your custom formatter and use it to format input value:

jQuery("input[name='submitted[sphere]']").focusout(function() {
  trim_value(this);
  format_sph(this);
});

Also you can use your custom JavaScript file with helpers. This module can load
the file, just place file path at the webform node edit page in the field 
JavaScript Field Library
(e.g. sites/all/libraries/Calculation/js/formatter.js).
 

Support
-------
Please use the issue queue for filing bugs with this module at
https://drupal.org/project/issues/2271743
