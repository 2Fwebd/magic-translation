Laravel Magic Translation
=========================

Automatically translate your Laravel localization files to any language using Google Translate through an Artisan command.

This package supports for any string: 
* Parameters (example: 'localized string :foo')
* Emojis (example: 'localized string 🚀')
* HTML tags (example: 'localized string 	&lt;i&gt;yes	&lt;/i&gt;')

## Installation 

As any Laravel package, simply

``composer require alkalab/magic-translation``

## How to use it? 

Simple, once installed the Artisan command is: 

``php artisan magic:translate {file} {target} {--no-validation}``

* **{file}**: the file that needs to be translated inside your ``resources/assets/lang/en/``, without the .php extension. 
  *Example: "validation" to translate resources/assets/lang/en/validation.php*
* **{target}**: the language code (2 characters) in which the strings will be translated. 
  *Example: "fr" to translate to resources/assets/lang/fr/*
* **--no-validation**: an optional command option. If set, the strings will be automatically translated without validation. 
  You can always change them afterward of course. Otherwise, you will be validating every translation 1 by 1. 
  
Example: 

``php artisan magic:translate validation fr --no-validation``


## Important notes

* This is using Google Translator, please **check the translations** as even though it's pretty good sometimes 
  it will not work correctly 😉. 
* This first version only translates **from** English to another language. Not the other way around.
* As of right now, this is using the wonderful [Stichoza/google-translate-php](https://github.com/Stichoza/google-translate-php)
  package. This package does not use the Google Cloud API but directly the Google Translate website. Therefore, it might
  stop working at some point. If that happens, this package will be updated to use the Cloud API instead.


**Any bug, idea or improvement, feel free to improve it or create a new issue.**