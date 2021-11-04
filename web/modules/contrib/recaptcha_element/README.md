CONTENTS OF THIS FILE
---------------------
   
 * Introduction
 * Installation
 * Configuration

INTRODUCTION
------------

This module enables you to easily use Google reCAPTCHA v3.

This module does not rely on any other Drupal module.
   
INSTALLATION
------------
 
```
$ composer require drupal/simple_recaptcha:^1.0
```

CONFIGURATION
-------------
  
 * Register reCAPTCHA v3 keys (https://www.google.com/recaptcha/admin/create).
    
   - The documentation for Google reCAPTCHA V3
     https://developers.google.com/recaptcha/docs/v3 

 * Navigate to /admin/config/services/recaptcha_element to set up reCAPTCHA 
   keys and element defaults.
    
 * Add a recaptcha element:
 
   - By adding the Recaptcha Element webform handler to a webform.
   - By manually adding a recaptcha_element type element to a form in code.
