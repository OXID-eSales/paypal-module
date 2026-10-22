# PayPal Checkout for OXID

PayPal checkout integration for OXID eShop 6.1 and above.

## ⚠️ End of life

This branch (`b-6.1.x`, module version 1.x for OXID eShop 6.1 and 6.2) is **no longer maintained**.
Version **1.4.2 is the final release**. There will be no further bug fixes or security updates.

Please update your shop. We strongly recommend OXID eShop 7 together with PayPal Checkout 3.x.
If that is not possible right away, update at least to OXID eShop 6.3 - 6.5 and use PayPal Checkout 2.x (branch `b-6.3.x`).

## Documentation

* Official [German PayPal Checkout for OXID 6.1 to 6.2 documentation](https://docs.oxid-esales.com/modules/paypal-checkout/de/1.2/)
* Official [German PayPal Checkout for OXID 6.3 to 6.5 documentation](https://docs.oxid-esales.com/modules/paypal-checkout/de/2.4/)
* Official [German PayPal Checkout for OXID from 7.0 documentation](https://docs.oxid-esales.com/modules/paypal-checkout/de/3.3/)
* Official [English PayPal Checkout for OXID 6.1 to 6.2 documentation](https://docs.oxid-esales.com/modules/paypal-checkout/en/1.2/)
* Official [English PayPal Checkout for OXID 6.3 to 6.5 documentation](https://docs.oxid-esales.com/modules/paypal-checkout/en/2.4/)
* Official [English PayPal Checkout for OXID from 7.0 documentation](https://docs.oxid-esales.com/modules/paypal-checkout/en/3.3/)


## Branch Compatibility

* b-7.0.x module branch is compatible with OXID eShop compilation 7.0, 7.1
* b-6.3.x module branch is compatible with OXID eShop compilation 6.3, 6.4, 6.5
* b-6.1.x module branch is compatible with OXID eShop compilation 6.1, 6.2 (end of life, final release 1.4.2)

## Install for OXID

* see Official documentation

## Limitations

* no limitations

## Merging Strategy

* The b-6.3.x branch is compatible with OXID6.3 to 6.5 and will not be merged automatically into the b-7.0.x branch
* if something changes in the b-6.3.x main branch, it must be ported to the b-7.0.x branch

## Running tests

Warning: Running tests will reset the shop.

#### Requirements
* Ensure test_config.yml is configured:
    ```
    partial_module_paths: osc/paypal
    ```
    ```
    activate_all_modules: true
    run_tests_for_shop: false
    run_tests_for_modules: true
    ```
* For codeception tests to be running, selenium server should be available, several options to solve this:
  * Use OXID official [docker sdk configuration](https://github.com/OXID-eSales/docker-eshop-sdk).
  * Use other preconfigured containers, example: ``image: 'selenium/standalone-chrome-debug:3.141.59'``

#### Run

Running phpunit tests:
```
vendor/bin/runtests
```

Running phpunit tests with coverage reports (report is generated in ``.../paypal/Tests/reports/`` directory):
```
XDEBUG_MODE=coverage vendor/bin/runtests-coverage
```

Running codeception tests default way (Host: selenium, browser: chrome):
in OXID 6.3 and above:
```
vendor/bin/runtests-codeception
```

Running codeception tests example with specific host/browser/testgroup:
in OXID 6.3 and above:
```
SELENIUM_SERVER_HOST=seleniumchrome BROWSER_NAME=chrome vendor/bin/runtests-codeception --group=examplegroup
```