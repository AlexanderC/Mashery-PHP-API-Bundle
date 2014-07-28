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

```yaml
# app/config/config.yml
mashery_api:
    client: ~                                    # default: alexanderc_api_call/0.1b (the string used to identify the client on Mashery side)
    version: ~                                   # available: version2, default: version2
    transport: ~                                 # available: curl, default: curl
    application: 777                             # application identified (aka site_id)
    api_key: "kug324iuy3i25gi2"                  # application key
    secret: "l2kj34o2h34o2iu3h4o2iu3h4o23iuh"    # secret token
    sync_schemas: "%kernel.root_dir%/SyncSchemas # path where sync schemas are located
```

Usage
=====

Getting the service:

```php
$this->get('mashery.api')->...;
```

For usage examples visit [Mashery-PHP-API/README.md](https://github.com/AlexanderC/Mashery-PHP-API/blob/master/README.md)

Sync configuration example
==========================

```yaml
# app/SyncSchemas/plans.yml
entity: HRMData\AppBundle\Entity\Plan
repository: HRMDataAppBundle:Plan
sync_query: SELECT id FROM plans
identifier: id
links:
  - Link HRMDataAppBundle:Package as package using plan updated by addPlan reversed by setPackage
  # resolves into: SELECT id FROM packages REQUIRE RELATED plans WITH id = %plan_id%
  # flush relations: $package->addPlan($plan) and $plan->setPackage($package)
```

