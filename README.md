# PayPal Checkout for OXID

PayPal checkout integration for OXID eShop 6.1 and above.

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
* b-6.1.x module branch is compatible with OXID eShop compilation 6.1, 6.2

## Install for OXID

* see Official documentation

## Limitations

* no limitations

## Merging Strategy

* The b-6.3.x branch is compatible with OXID 6.3 to 6.5 and will not be merged automatically into the b-7.0.x branch
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

### Running tests using Docker with xDebug
```
docker compose exec -T \
    -e PARTIAL_MODULE_PATHS='osc/paypal' \
    -e ACTIVATE_ALL_MODULES=1 \
    -e RUN_TESTS_FOR_SHOP=0 \
    -e RUN_TESTS_FOR_MODULES=1 \
    -e XDEBUG_MODE=debug \
    -e XDEBUG_CONFIG='idekey=PHPSTORM' \
    -e OXID_PHP_UNIT=true \
    -e ADDITIONAL_TEST_PATHS='/var/www/vendor/oxid-solution-catalysts/paypal-module/Tests' \
    php php -dxdebug.mode=debug -dxdebug.client_port=9003 -dxdebug.client_host=172.17.0.1 vendor/bin/runtests \
      --log-junit=/var/www/phpunit.xml \
      AllTestsUnit
```
#### Additional ENV variables:
PHP_IDE_CONFIG='serverName=local.domain' ex.: john.oxiddev.de 
#### PHP interpreter config variables:
-dxdebug.client_host=172.17.0.1 will work on PC Linux 
-dxdebug.client_host=host.docker.internal will work on Mac

### Running tests using Docker with coverage
```
docker compose exec -T \
    -e PARTIAL_MODULE_PATHS='osc/paypal' \
    -e ACTIVATE_ALL_MODULES=1 \
    -e RUN_TESTS_FOR_SHOP=0 \
    -e RUN_TESTS_FOR_MODULES=1 \
    -e XDEBUG_MODE=coverage \    
    -e OXID_PHP_UNIT=true \
    -e ADDITIONAL_TEST_PATHS='/var/www/vendor/oxid-solution-catalysts/paypal-module/Tests' \
    php php -dxdebug.mode=debug -dxdebug.client_port=9003 -dxdebug.client_host=172.17.0.1 vendor/bin/runtests \
      --log-junit=/var/www/phpunit.xml \
      AllTestsUnit
```

### Running code static analysis
Tools for checking various parts of written code making analysis and corrections.


#### PHPStan
```docker compose exec -w /var/www/source/modules/osc/paypal -T php composer phpstan```

#### Php Code Sniffer
```docker compose exec -w /var/www/source/modules/osc/paypal -T php composer phpcs```

#### Php Mess Detector
```docker compose exec -w /var/www/source/modules/osc/paypal -T php composer phpmd```