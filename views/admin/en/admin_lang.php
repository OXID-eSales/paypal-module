<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

$sLangName = 'English';

$aLang = [
    'charset'                                      => 'UTF-8',
    'paypal'                                       => 'PayPal',
    'tbclorder_oscpaypal'                         => 'PayPal',
    // PayPal Config
    'OSC_PAYPAL_CONFIG'                           => 'Configuration',
    'OSC_PAYPAL_GENERAL'                          => 'General',
    'OSC_PAYPAL_WEBHOOK_ID'                       => 'Webhook ID',
    'OSC_PAYPAL_OPMODE'                           => 'Operation mode',
    'OSC_PAYPAL_OPMODE_LIVE'                      => 'Live',
    'OSC_PAYPAL_OPMODE_SANDBOX'                   => 'Sandbox',
    'OSC_PAYPAL_CLIENT_ID'                        => 'Client ID',
    'OSC_PAYPAL_CLIENT_SECRET'                    => 'Secret',
    'OSC_PAYPAL_CREDENTIALS'                      => 'API credentials',
    'OSC_PAYPAL_LIVE_CREDENTIALS'                 => 'Live API credentials',
    'OSC_PAYPAL_SANDBOX_CREDENTIALS'              => 'Sandbox API credentials',
    'OSC_PAYPAL_LIVE_BUTTON_CREDENTIALS'          => 'SignUp Merchant Integration (Live)',
    'OSC_PAYPAL_LIVE_BUTTON_CREDENTIALS_INIT'     => 'Start Merchant Integration (Live) in a new window ...',
    'OSC_PAYPAL_SANDBOX_BUTTON_CREDENTIALS'       => 'SignUp Merchant Integration (Sandbox)',
    'OSC_PAYPAL_SANDBOX_BUTTON_CREDENTIALS_INIT'  => 'Start Merchant Integration (Sandbox) in a new window ...',
    'OSC_PAYPAL_ONBOARD_CLICK_HELP'               => 'Please close the page if you want to cancel the PayPal integration...',
    'OSC_PAYPAL_ONBOARD_CLOSE_HELP'               => 'You can now close the window.',
    'OSC_PAYPAL_ERR_CONF_INVALID'                 =>
        'One or more configuration values are either not set or incorrect. Please double check them.<br>
        <b>Module inactive.</b>',
    'OSC_PAYPAL_CONF_VALID'                       => 'Configuration values OK.<br><b>Module is active</b>',
    'OSC_PAYPAL_BUTTON_PLACEMEMT_TITLE'           => 'Button placement settings',
    'OSC_PAYPAL_PRODUCT_DETAILS_BUTTON_PLACEMENT' => 'Product details page',
    'OSC_PAYPAL_BASKET_BUTTON_PLACEMENT'          => 'Basket',
    'HELP_OSC_PAYPAL_BUTTON_PLACEMEMT'            => 'Toggle the display of PayPal buttons',
    'OSC_SHOW_PAYPAL_PAYLATER_BUTTON'             => 'Show "Pay later"-Button?',
    'HELP_OSC_SHOW_PAYPAL_PAYLATER_BUTTON'        => 'In addition to the classic PayPal button, there is a "Pay later"-button that can be displayed below the standard button. If it is activated, the customer has the option of paying for the goods later.',

    'OSC_PAYPAL_EXPRESS_LOGIN_TITLE'              => 'Login with PayPal',
    'OSC_PAYPAL_LOGIN_WITH_PAYPAL_EMAIL'          => 'Automatically log in to shop during checkout',
    'HELP_OSC_PAYPAL_EXPRESS_LOGIN'               => 'In case there is already a shop user registered with password to the same mail address as the the PayPal mail,
        it is possible to be autonmatically be logged in to shop when logging in to PayPal. This behavior may not be in the
        security interests of your customers',

    'HELP_OSC_PAYPAL_CREDENTIALS'                 =>
        'If you already have the API credentials, you can enter them directly.<br>
        If you do not yet have any API data and the input fields are still empty, you can also use the
        displayed button for a convenient link.',
    'HELP_OSC_PAYPAL_CLIENT_ID'                   => 'Client ID for live mode.',
    'HELP_OSC_PAYPAL_CLIENT_SECRET'               => 'Secret for live mode.',
    'HELP_OSC_PAYPAL_SANDBOX_CLIENT_ID'           => 'Client ID for sandbox mode.',
    'HELP_OSC_PAYPAL_SANDBOX_CLIENT_SECRET'       => 'Secret for sandbox mode. Please enter the password twice.',
    'HELP_OSC_PAYPAL_SANDBOX_WEBHOOK_ID'          =>
        'The ID of the sandbox-webhook as configured in your Developer Portal account',
    'HELP_OSC_PAYPAL_OPMODE'                      =>
        'To configure and test PayPal, use Sandbox (test). When you\'re ready
        to receive real transactions, switch to Production (live).',
    'HELP_OSC_PAYPAL_WEBHOOK_ID'                  =>
        'The ID of the webhook as configured in your Developer Portal account',
    'OSC_PAYPAL_SPECIAL_PAYMENTS'                 => 'Activation for special payment methods has taken place',
    'OSC_PAYPAL_SPECIAL_PAYMENTS_PUI'             => 'Pay upon Invoice',
    'OSC_PAYPAL_SPECIAL_PAYMENTS_ACDC'            => 'Creditcard',
    'OSC_PAYPAL_SPECIAL_PAYMENTS_ACDC_FALLBACK'   => '(As an alternative to the missing payment method, an additional "credit card" button is displayed under the Paypal buttons.)',

    // PayPal ORDER
    'OSC_PAYPAL_ISSUE_REFUND'                     => 'Issue refund',
    'OSC_PAYPAL_AMOUNT'                           => 'Amount',
    'OSC_PAYPAL_SHOP_PAYMENT_STATUS'              => 'Shop payment status',
    'OSC_PAYPAL_ORDER_PRICE'                      => 'Full order price',
    'OSC_PAYPAL_ORDER_PRODUCTS'                   => 'Ordered products',
    'OSC_PAYPAL_CAPTURED'                         => 'Captured',
    'OSC_PAYPAL_REFUNDED'                         => 'Refunded',
    'OSC_PAYPAL_CAPTURED_NET'                     => 'Resulting payment amount',
    'OSC_PAYPAL_CAPTURED_AMOUNT'                  => 'Captured amount',
    'OSC_PAYPAL_REFUNDED_AMOUNT'                  => 'Refunded amount',
    'OSC_PAYPAL_MONEY_CAPTURE'                    => 'Money capture',
    'OSC_PAYPAL_MONEY_REFUND'                     => 'Money refund',
    'OSC_PAYPAL_CAPTURE'                          => 'Capture',
    'OSC_PAYPAL_REFUND'                           => 'Refund',
    'OSC_PAYPAL_DETAILS'                          => 'Details',
    'OSC_PAYPAL_AUTHORIZATION'                    => 'Authorization',
    'OSC_PAYPAL_CANCEL_AUTHORIZATION'             => 'Void',
    'OSC_PAYPAL_PAYMENT_HISTORY'                  => 'PayPal history',
    'OSC_PAYPAL_HISTORY_DATE'                     => 'Date',
    'OSC_PAYPAL_HISTORY_ACTION'                   => 'Action',
    'OSC_PAYPAL_HISTORY_PAYPAL_STATUS'            => 'PayPal status',
    'OSC_PAYPAL_HISTORY_PAYPAL_STATUS_HELP'       =>
        'Payment status returned from PayPal. For more details see:
        <a href="https://www.paypal.com/webapps/helpcenter/article/?articleID=94021&m=SRE" target="_blank">
            PayPal status
        </a>',
    'OSC_PAYPAL_HISTORY_COMMENT'                  => 'Comment',
    'OSC_PAYPAL_HISTORY_NOTICE'                   => 'Note',
    'OSC_PAYPAL_MONEY_ACTION_FULL'                => 'full',
    'OSC_PAYPAL_MONEY_ACTION_PARTIAL'             => 'partial',
    'OSC_PAYPAL_LIST_STATUS_ALL'                  => 'All',
    'OSC_PAYPAL_STATUS_APPROVED'                  => 'Approved',
    'OSC_PAYPAL_STATUS_COMPLETED'                 => 'Completed',
    'OSC_PAYPAL_STATUS_DECLINED'                  => 'Declined',
    'OSC_PAYPAL_STATUS_PARTIALLY_REFUNDED'        => 'Partially refunded',
    'OSC_PAYPAL_STATUS_PENDING'                   => 'Pending',
    'OSC_PAYPAL_STATUS_REFUNDED'                  => 'Refunded',
    'OSC_PAYPAL_PAYMENT_METHOD'                   => 'Payment method',
    'OSC_PAYPAL_CLOSE'                            => 'Close',
    'OSC_PAYPAL_COMMENT'                          => 'Comment',
    'OSC_PAYPAL_RESPONSE_FROM_PAYPAL'             => 'Error message from PayPal: ',
    'OSC_PAYPAL_AUTHORIZATIONID'                  => 'Authorization ID',
    'OSC_PAYPAL_TRANSACTIONID'                    => 'Transaction ID',
    'OSC_PAYPAL_REFUND_AMOUNT'                    => 'Refund amount',
    'OSC_PAYPAL_INVOICE_ID'                       => 'Invoice No',
    'OSC_PAYPAL_NOTE_TO_BUYER'                    => 'Note to buyer',
    'OSC_PAYPAL_REFUND_ALL'                       => 'Refund all',
    'OSC_PAYPAL_FIRST_NAME'                       => 'First name',
    'OSC_PAYPAL_LAST_NAME'                        => 'Last name',
    'OSC_PAYPAL_FULL_NAME'                        => 'Full name',
    'OSC_PAYPAL_EMAIL'                            => 'Email',
    'OSC_PAYPAL_ADDRESS_LINE_1'                   => 'Address line 1',
    'OSC_PAYPAL_ADDRESS_LINE_2'                   => 'Address line 2',
    'OSC_PAYPAL_ADDRESS_LINE_3'                   => 'Address line 3',
    'OSC_PAYPAL_ADMIN_AREA_1'                     => 'Province, State, or ISO-subdivision',
    'OSC_PAYPAL_ADMIN_AREA_2'                     => 'City',
    'OSC_PAYPAL_ADMIN_AREA_3'                     => 'Sub-locality, Suburb, Neighborhood or District',
    'OSC_PAYPAL_ADMIN_AREA_4'                     => 'The neighborhood, ward, or district',
    'OSC_PAYPAL_POSTAL_CODE'                      => 'Postal code',
    'OSC_PAYPAL_COUNTRY_CODE'                     => 'Country code',
    'OSC_PAYPAL_SHIPPING'                         => 'Shipping',
    'OSC_PAYPAL_BILLING'                          => 'Billing',

    'OSC_PAYPAL_BANNER_TRANSFERLEGACYSETTINGS'     => 'Apply settings from the classic PayPal module',
    'OSC_PAYPAL_BANNER_TRANSFERREDOLDSETTINGS'     => 'Banner settings have been transferred from the classig PayPal module.',
    'OSC_PAYPAL_BANNER_CREDENTIALS'                => 'Banner settings',
    'OSC_PAYPAL_BANNER_INFOTEXT'                   => 'Offer your customers PayPal installment payment with 0% effective annual interest. <a href="https://www.paypal.com/de/webapps/mpp/installments" target="_blank">Read more</a>.',
    'OSC_PAYPAL_BANNER_SHOW_ALL'                   => 'Enable installment banners',
    'HELP_OSC_PAYPAL_BANNER_SHOP_MODULE_SHOW_ALL'  => 'Check this option to enable the banner feature.',
    'OSC_PAYPAL_BANNER_STARTPAGE'                   => 'Show installment banner on start page',
    'OSC_PAYPAL_BANNER_STARTPAGESELECTOR'           => 'CSS selector of the start page after which the banner is displayed.',
    'HELP_OSC_PAYPAL_BANNER_STARTPAGESELECTOR'      => 'Default values for Flow and Wave themes are: \'#wrapper .row\' and \'#wrapper .container\' respectively. After these CSS selectors the banner is displayed.',
    'OSC_PAYPAL_BANNER_CATEGORYPAGE'                => 'Show installment banner on category pages',
    'OSC_PAYPAL_BANNER_CATEGORYPAGESELECTOR'        => 'CSS selector of the category pages after which the banner is displayed.',
    'HELP_OSC_PAYPAL_BANNER_CATEGORYPAGESELECTOR'   => 'Default values for Flow and Wave themes are: \'.page-header\' and \'.page-header\' respectively. After these CSS selectors the banner is displayed.',
    'OSC_PAYPAL_BANNER_SEARCHRESULTSPAGE'           => 'Show installment banner on search results pages',
    'OSC_PAYPAL_BANNER_SEARCHRESULTSPAGESELECTOR'   => 'CSS selector of the search results pages after which the banner is displayed.',
    'HELP_OSC_PAYPAL_BANNER_SEARCHRESULTSPAGESELECTOR' => 'Default values for Flow and Wave themes are: \'#content .page-header .clearfix\' and \'.page-header\' respectively. After these CSS selectors the banner is displayed.',
    'OSC_PAYPAL_BANNER_DETAILSPAGE'                 => 'CSS selector of the product detail pages after which the banner is displayed.',
    'OSC_PAYPAL_BANNER_DETAILSPAGESELECTOR'         => 'CSS-Selektor der Detailseiten hinter dem das Banner angezeigt wird.',
    'HELP_OSC_PAYPAL_BANNER_DETAILSPAGESELECTOR'    => 'Default values for Flow and Wave themes are: \'.detailsParams\' and \'#detailsItemsPager\' respectively. After these CSS selectors the banner is displayed.',
    'OSC_PAYPAL_BANNER_CHECKOUTPAGE'                => 'Show installment banner on checkout pages',
    'OSC_PAYPAL_BANNER_CARTPAGESELECTOR'            => 'CSS selector of the "Cart" page (checkout step 1) after which the banner is displayed.',
    'HELP_OSC_PAYPAL_BANNER_CARTPAGESELECTOR'       => 'Default values for Flow and Wave themes are: \'.cart-buttons\' and \'.cart-buttons\' respectively. After these CSS selectors the banner is displayed.',
    'OSC_PAYPAL_BANNER_PAYMENTPAGESELECTOR'         => 'CSS selector of the "Pay" page (checkout step 3) after which the banner is displayed.',
    'HELP_OSC_PAYPAL_BANNER_PAYMENTPAGESELECTOR'    => 'Default values for Flow and Wave themes are: \'.checkoutSteps ~ .spacer\' and \'.checkout-steps\' respectively. After these CSS selectors the banner is displayed.',
    'OSC_PAYPAL_BANNER_COLORSCHEME'                 => 'Select installment banner\'s color',
    'OSC_PAYPAL_BANNER_COLORSCHEMEBLUE'             => 'blue',
    'OSC_PAYPAL_BANNER_COLORSCHEMEBLACK'            => 'black',
    'OSC_PAYPAL_BANNER_COLORSCHEMEWHITE'            => 'white',
    'OSC_PAYPAL_BANNER_COLORSCHEMEWHITENOBORDER'    => 'white, no border',

    'OSC_PAYPAL_MIGRATELEGACTRANSACTIONDATA'        => 'Transfer transaction data from legacy modules',
    'OSC_PAYPAL_MIGRATELEGACTRANSACTIONDATA_DETAIL' => 'If you have used older modules for PayPal so far, you can transfer the transaction data of previous orders here to view them with the new PayPal Checkout module.',
    'OSC_PAYPAL_TRANSFERLEGACY_OEPP_DATA'           => 'Migrate transaction data from the classic PayPal module (oepaypal version 6.3.x)',
    'OSC_PAYPAL_TRANSFERLEGACY_OEPP_SUCCESS'        => 'Records transferred:',

];
