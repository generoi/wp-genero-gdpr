# wp-genero-gdpr

> Various tools for becoming GDPR complaint

## Requirements

- For encrypting gravityform submission fields you need either PHP 7.0 with the PECL libsodium extension or PHP 7.2+

## Features

- Expire gravityfrom submissions (defaults to all forms with 1 year but can be changed on a per-form basis)
- Encrypt gravityform submission entries

## Installation

### Form submission encryption

When the plugin is first installed a key-pair is automatically generated. Follow the instructions and add the required defines to `wp-config.php`

```php
define('GENERO_GDPR_ENCRYPT_ENABLED', true);
define('GENERO_GDPR_PUBLIC_KEY', $root_dir . '/genero-gdpr.public.key');
```

If the key-pair wasn't saved you can manually generate a new key-pair by running `composer run generate-keys` in the plugin directory. Move the generated `genero-gdpr.public.key` to place and add it to `wp-config.php`.

**WARNING! If you lose the private key and have encryption enabled it will be impossible to recover the data.**

## API

```php
// Change the default expiration time of gravityform submissions
add_filter('wp-genero-gdpr/expire-submissions/default_expiration_time', function ($time) {
  return '3 months';
});

// Override the expiration time of gravityform submissions
add_filter('wp-genero-gdpr/expire-submissions/expiration_time', function ($time) {
  return '1 day';
});

// Change the amount of submissions that are deleted at a time
add_filter('wp-genero-gdpr/expire-submissions/pager', function ($pager) {
  return 50;
});
```

## Development

Install dependencies

    composer install

Generate a new key-pair

    composer run generate-keys
