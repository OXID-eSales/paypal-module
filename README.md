# PayPal Checkout for OXID

PayPal checkout integration for OXID eShop 6.1 and above.

## Documentation

* Official [German PayPal Checkout for OXID 6.1 to 6.2 documentation](https://docs.oxid-esales.com/modules/paypal-checkout/de/1.3/)
* Official [German PayPal Checkout for OXID 6.3 to 6.5 documentation](https://docs.oxid-esales.com/modules/paypal-checkout/de/2.6/)
* Official [German PayPal Checkout for OXID from 7.0 documentation](https://docs.oxid-esales.com/modules/paypal-checkout/de/3.5/)
* Official [English PayPal Checkout for OXID 6.1 to 6.2 documentation](https://docs.oxid-esales.com/modules/paypal-checkout/en/1.3/)
* Official [English PayPal Checkout for OXID 6.3 to 6.5 documentation](https://docs.oxid-esales.com/modules/paypal-checkout/en/2.6/)
* Official [English PayPal Checkout for OXID from 7.0 documentation](https://docs.oxid-esales.com/modules/paypal-checkout/en/3.5/)


## Branch Compatibility

* b-8.0.x module branch is compatible with OXID eShop compilation 8.0,
* b-7.2.x module branch is compatible with OXID eShop compilation 7.2, 
* b-7.1.x module branch is compatible with OXID eShop compilation 7.1, ...
* b-7.0.x module branch is compatible with OXID eShop compilation 7.0,
* b-6.3.x module branch is compatible with OXID eShop compilation 6.3, 6.4, 6.5
* b-6.1.x module branch is compatible with OXID eShop compilation 6.1, 6.2

## Install for OXID

* see Official documentation

## Install with new recipe for development OXID >= v7.x

1. Download SDK
    ```
     echo oxidshop && git clone git@github.com:OXID-eSales/docker-eshop-sdk.git $_ --branch=b-7.0.x && cd $_
    ```
2. Download Paypal Module into temporary folder 
    ```
     git clone --recurse-submodules git@github.com:OXID-eSales/paypal-module.git extensions/paypal --branch=b-7.0.x
    ```
  
3. Check if you have all submodules:

    ```
    ls -la extensions/paypal/recipe/parts
    ```

4. If you don't have the submodules, run the following command:
    ```
    cd extensions/paypal
    git submodule update --init --recursive
    ```
    If you still do not see the submodule, download it manually

  ```
  git clone https://github.com/OXID-eSales/docker-eshop-sdk-recipe-parts.git recipe/parts
  ```
5. Run the installation
  ```

  ./extensions/paypal/recipe/setup-twig-dev.sh

  ```

  * SMARTY version is available only for OXID 7.0.x
    ```
    ./extensions/paypal/recipe/setup-smarty-dev.sh
    ```
    



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

#### Run

Running phpunit tests:
```
vendor/bin/runtests
```

Running phpunit tests with coverage reports (report is generated in ``.../paypal/Tests/reports/`` directory):
```
XDEBUG_MODE=coverage vendor/bin/runtests-coverage
```