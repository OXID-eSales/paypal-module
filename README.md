# PayPal Checkout for OXID

PayPal checkout integration for OXID eShop 6.1 and above.

## Documentation

* Official German PayPal checkout for OXID [documentation](https://docs.oxid-esales.com/modules/paypal-checkout/de/latest/).
* Official English PayPal checkout for OXID [documentation](https://docs.oxid-esales.com/modules/paypal-checkout/en/latest/).

## Branch Compatibility

* b-6.3.x module branch is compatible with OXID eShop compilation 6.3, 6.4, 6.5
* b-6.1.x module branch is compatible with OXID eShop compilation 6.1, 6.2

## Install for OXID

* see Official documentation

## Limitations

* no limitations

## Merging Strategy

* The b-6.1.x branch is compatible with OXID6.1 and will not be merged into the b-6.3.x branch
* if something changes in the b-6.3.x main branch, it must be ported to the b-6.1.x branch

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
    * Use OXID official [vagrant box environment](https://github.com/OXID-eSales/oxvm_eshop).
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
in OXID 6.2 and above:
```
vendor/bin/runtests-codeception
```
in OXID 6.1:
```
vendor/bin/runtests-selenium
```

Running codeception tests example with specific host/browser/testgroup:
in OXID 6.2 and above:
```
SELENIUM_SERVER_HOST=seleniumchrome BROWSER_NAME=chrome vendor/bin/runtests-codeception --group=examplegroup
```
in OXID 6.1:
```
SELENIUM_SERVER_HOST=seleniumchrome BROWSER_NAME=chrome vendor/bin/runtests-selenium --group=examplegroup
```
