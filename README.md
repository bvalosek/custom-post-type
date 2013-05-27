# Fluent-style Custom Post Type for Wordpress
A simple [http://en.wikipedia.org/wiki/Fluent_interface](fluent API wrapper)
for creating custom post types in Wordpress.

## Installing
All functionality is contained in the `CustomPostType` class in the
`CustomPostType.php` file. You can either copy it into an included class path
directory and use an
[http://php.net/manual/en/language.oop5.autoload.php](autoloader) or simply use
a `require_once()` call in your PHP file.

## Usage

Assuming you're using a class autoloader

```php
<?php

use bvalosek\CustomPostType as CustomPostType;

CustomPostType::factory()
    ->slug('product')
    ->labels('Product', 'Products')
    ->custom_text_meta('Tagline')
    ->custom_text_meta('Product URL')
    ->menu_icon(plugin_dir_url(__FILE__) . 'img/store.png')
    ->supports(array('title', 'editor', 'thumbnail', 'revisions'))
    ->create();
```

