Installation
============
- Via composer: `composer install alexanderc/mashery-php-api-bundle`

Enable the bundle
=================
To start using the bundle, register the bundle in your application's kernel class:

```php
// app/AppKernel.php
public function registerBundles()
{
    $bundles = array(
        // ...
        new AlexanderC\Api\MasheryBundle\MasheryApiBundle(),
        // ...
    );
)
```

Configuration
=============

```yml
# app/config/config.yml
mashery_api:
    version: ~              # available: version2
                            # default: version2
    transport: ~            # available: curl
                            # default: curl
    application: "mysite"   # application identified (aka site_id)
    api_key: "123123123"    # application key
    secret: "secrethere"    # secret token
```

Usage
=====

Getting the service:

```php
$this->get('mashery.api')->...;
```

For usage examples visit [Mashery-PHP-API/README.md](https://github.com/AlexanderC/Mashery-PHP-API/blob/master/README.md)

