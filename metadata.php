<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

use OxidEsales\Eshop\Application\Component\UserComponent;
use OxidEsales\Eshop\Application\Component\Widget\ArticleDetails;
use OxidEsales\Eshop\Application\Controller\Admin\ArticleList;
use OxidEsales\Eshop\Application\Controller\ArticleDetailsController;
use OxidEsales\Eshop\Application\Controller\BasketController;
use OxidEsales\Eshop\Application\Controller\OrderController;
use OxidEsales\Eshop\Application\Controller\PaymentController;
use OxidEsales\Eshop\Application\Model\Article;
use OxidEsales\Eshop\Application\Model\Basket;
use OxidEsales\Eshop\Application\Model\BasketItem;
use OxidEsales\Eshop\Application\Model\Order;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Application\Model\Payment;
use OxidEsales\Eshop\Application\Model\PaymentGateway;
use OxidEsales\Eshop\Core\InputValidator;
use OxidEsales\Eshop\Core\ShopControl;
use OxidEsales\Eshop\Core\ViewConfig;
use OxidSolutionCatalysts\PayPal\Component\UserComponent as PayPalUserComponent;
use OxidSolutionCatalysts\PayPal\Component\Widget\ArticleDetails as ArticleDetailsComponent;
use OxidSolutionCatalysts\PayPal\Controller\Admin\ArticleListController;
use OxidSolutionCatalysts\PayPal\Controller\Admin\PayPalBalanceController;
use OxidSolutionCatalysts\PayPal\Controller\Admin\DisputeController;
use OxidSolutionCatalysts\PayPal\Controller\Admin\DisputeDetailsController;
use OxidSolutionCatalysts\PayPal\Controller\Admin\OnboardingController;
use OxidSolutionCatalysts\PayPal\Controller\Admin\PayPalConfigController;
use OxidSolutionCatalysts\PayPal\Controller\Admin\PayPalOrderController;
use OxidSolutionCatalysts\PayPal\Controller\Admin\PayPalSubscribeController;
use OxidSolutionCatalysts\PayPal\Controller\Admin\SubscriptionController;
use OxidSolutionCatalysts\PayPal\Controller\Admin\SubscriptionDetailsController;
use OxidSolutionCatalysts\PayPal\Controller\Admin\SubscriptionTransactionController;
use OxidSolutionCatalysts\PayPal\Controller\Admin\PayPalTransactionController;
use OxidSolutionCatalysts\PayPal\Controller\ArticleDetailsController as PayPalArticleDetailsController;
use OxidSolutionCatalysts\PayPal\Controller\BasketController as PayPalBasketController;
use OxidSolutionCatalysts\PayPal\Controller\OrderController as PayPalFrontEndOrderController;
use OxidSolutionCatalysts\PayPal\Controller\PaymentController as PayPalPaymentController;
use OxidSolutionCatalysts\PayPal\Controller\ProxyController;
use OxidSolutionCatalysts\PayPal\Controller\WebhookController;
use OxidSolutionCatalysts\PayPal\Core\InputValidator as PayPalInputValidator;
use OxidSolutionCatalysts\PayPal\Core\ShopControl as PayPalShopControl;
use OxidSolutionCatalysts\PayPal\Core\ViewConfig as PayPalViewConfig;
use OxidSolutionCatalysts\PayPal\Model\Basket as PayPalBasket;
use OxidSolutionCatalysts\PayPal\Model\BasketItem as PayPalBasketItem;
use OxidSolutionCatalysts\PayPal\Model\Order as PayPalOrder;
use OxidSolutionCatalysts\PayPal\Model\User as PayPalUser;
use OxidSolutionCatalysts\PayPal\Model\Payment as PayPalPayment;
use OxidSolutionCatalysts\PayPal\Model\PaymentGateway as PayPalPaymentGateway;
use OxidSolutionCatalysts\PayPal\Model\Article as PayPalArticle;

$sMetadataVersion = '2.1';

/**
 * Module information
 */
$aModule = [
    'id' => 'osc_paypal',
    'title' => [
        'de' => 'PayPal Checkout für OXID',
        'en' => 'PayPal Checkout for OXID'
    ],
    'description' => [
        'de' => 'Nutzung des Online-Bezahldienstes von PayPal. Dokumentation: <a href="https://docs.oxid-esales.com/modules/paypal-checkout/de/latest/" target="_blank">PayPal Checkout</a>',
        'en' => 'Use of the online payment service from PayPal. Documentation: <a href="https://docs.oxid-esales.com/modules/paypal-checkout/en/latest/" target="_blank">PayPal Checkout</a>'
    ],
    'thumbnail' => 'out/img/paypal.png',
    'version' => '1.0.0',
    'author' => 'OXID eSales AG',
    'url' => 'https://www.oxid-esales.com',
    'email' => 'info@oxid-esales.com',
    'extend' => [
        InputValidator::class => PayPalInputValidator::class,
        ShopControl::class => PayPalShopControl::class,
        ViewConfig::class => PayPalViewConfig::class,
        Order::class => PayPalOrder::class,
        User::class => PayPalUser::class,
        Basket::class => PayPalBasket::class,
        BasketItem::class => PayPalBasketItem::class,
        Article::class => PayPalArticle::class,
        Payment::class => PayPalPayment::class,
        PaymentGateway::class => PayPalPaymentGateway::class,
        ArticleList::class => ArticleListController::class,
        ArticleDetailsController::class => PayPalArticleDetailsController::class,
        BasketController::class => PayPalBasketController::class,
        ArticleDetails::class => ArticleDetailsComponent::class,
        OrderController::class => PayPalFrontEndOrderController::class,
        PaymentController::class => PayPalPaymentController::class,
        UserComponent::class => PayPalUserComponent::class
    ],
    'controllers' => [
        'oscpaypalconfig' => PayPalConfigController::class,
        'oscpaypalbalance' => PayPalBalanceController::class,
        'oscpaypalwebhook' => WebhookController::class,
        'oscpaypalproxy' => ProxyController::class,

        'oscpaypalonboarding' => OnboardingController::class,
        'oscpaypalorder' => PayPalOrderController::class,
        //TODO: for the first release we start without PayPal Subscriptions
        // 'oscpaypaltransactions' => PayPalTransactionController::class,
        // 'oscpaypalsubscriptiontransaction' => SubscriptionTransactionController::class,
        // 'oscpaypalsubscribe' => PayPalSubscribeController::class,
        // 'oscpaypalsubscriptiondetails' => SubscriptionDetailsController::class,
        // 'oscpaypalsubscription' => SubscriptionController::class,
        // 'oscpaypaldispute' => DisputeController::class,
        // 'oscpaypaldisputedetails' => DisputeDetailsController::class
    ],
    'templates' => [
        // Admin: Config
        'oscpaypalconfig.tpl' => 'osc/paypal/views/admin/tpl/oscpaypalconfig.tpl',
        'oscpaypalconfig_popup.tpl' => 'osc/paypal/views/admin/tpl/oscpaypalconfig_popup.tpl',

        // Admin: Order
        'oscpaypalorder.tpl' => 'osc/paypal/views/admin/tpl/oscpaypalorder.tpl',

        // Admin: Subscriptions
        //TODO: for the first release we start without PayPal Subscriptions
        // 'oscpaypaldisputedetails.tpl' => 'osc/paypal/views/admin/tpl/oscpaypaldisputedetails.tpl',
        // 'oscpaypaldisputes.tpl' => 'osc/paypal/views/admin/tpl/oscpaypaldisputes.tpl',
        // 'oscpaypalbillingplan.tpl' => 'osc/paypal/views/admin/tpl/inc/oscpaypalbillingplan.tpl',
        // 'oscpaypalsubscriptionform.tpl' => 'osc/paypal/views/admin/tpl/inc/oscpaypalsubscriptionform.tpl',
        // 'oscpaypalbillingplandata.tpl' => 'osc/paypal/views/admin/tpl/inc/oscpaypalbillingplandata.tpl',
        // 'oscpaypallistpagination.tpl' => 'osc/paypal/views/admin/tpl/inc/oscpaypallistpagination.tpl',
        // 'oscpaypalsubscriptions.tpl' => 'osc/paypal/views/admin/tpl/oscpaypalsubscriptions.tpl',
        // 'oscpaypaltransactions.tpl' => 'osc/paypal/views/admin/tpl/oscpaypaltransactions.tpl',
        // 'oscpaypalbalances.tpl' => 'osc/paypal/views/admin/tpl/oscpaypalbalances.tpl',
        // 'oscpaypalsubscriptiontransactions.tpl' => 'osc/paypal/views/admin/tpl/oscpaypalsubscriptiontransactions.tpl',
        // 'oscpaypalsubscriptiondetails.tpl' => 'osc/paypal/views/admin/tpl/oscpaypalsubscriptiondetails.tpl',
        // 'oscpaypalpartsubscriptiondetails.tpl' => 'osc/paypal/views/admin/tpl/oscpaypalpartsubscriptiondetails.tpl',
        // 'oscpaypalsubscribe.tpl'    => 'osc/paypal/views/admin/tpl/oscpaypalsubscribe.tpl',

        'modules/osc/paypal/smartpaymentbuttons.tpl' => 'osc/paypal/views/tpl/shared/smartpaymentbuttons.tpl',
        'modules/osc/paypal/paymentbuttons.tpl' => 'osc/paypal/views/tpl/shared/paymentbuttons.tpl',
        'modules/osc/paypal/subscriptionbuttons.tpl' => 'osc/paypal/views/tpl/shared/subscriptionbuttons.tpl',

        'modules/osc/paypal/acdc_flow.tpl' => 'osc/paypal/views/tpl/flow/page/checkout/acdc.tpl',
        'modules/osc/paypal/acdc_wave.tpl' => 'osc/paypal/views/tpl/wave/page/checkout/acdc.tpl',
        'modules/osc/paypal/pui_flow.tpl' => 'osc/paypal/views/tpl/flow/page/checkout/pui.tpl',
        'modules/osc/paypal/pui_wave.tpl' => 'osc/paypal/views/tpl/wave/page/checkout/pui.tpl',
        'modules/osc/paypal/pui_fraudnet.tpl' => 'osc/paypal/views/tpl/shared/page/checkout/pui_fraudnet.tpl',
        'modules/osc/paypal/shipping_and_payment_flow.tpl' => 'osc/paypal/views/tpl/flow/page/checkout/shipping_and_payment.tpl',
        'modules/osc/paypal/shipping_and_payment_wave.tpl' => 'osc/paypal/views/tpl/wave/page/checkout/shipping_and_payment.tpl',
        'modules/osc/paypal/checkout_order_btn_submit_bottom_flow.tpl' => 'osc/paypal/views/tpl/flow/page/checkout/checkout_order_btn_submit_bottom.tpl',
        'modules/osc/paypal/checkout_order_btn_submit_bottom_wave.tpl' => 'osc/paypal/views/tpl/wave/page/checkout/checkout_order_btn_submit_bottom.tpl',

        // PAYPAL-486 Register templates for overloading here;
        // use theme name in key when theme-specific. Shared templates don't receive a theme-specific key.
        'modules/osc/paypal/base_js.tpl' => 'osc/paypal/views/tpl/shared/layout/base_js.tpl',
        'modules/osc/paypal/base_style.tpl' => 'osc/paypal/views/tpl/shared/layout/base_style.tpl',
        'modules/osc/paypal/basket_btn_next_bottom.tpl' =>
            'osc/paypal/views/tpl/shared/page/checkout/basket_btn_next_bottom.tpl',
        'modules/osc/paypal/select_payment.tpl' => 'osc/paypal/views/tpl/shared/page/checkout/select_payment.tpl',
        'modules/osc/paypal/details_productmain_tobasket.tpl' =>
            'osc/paypal/views/tpl/shared/page/details/inc/details_productmain_tobasket.tpl',
        'modules/osc/paypal/checkout_steps_main.tpl' =>
            'osc/paypal/views/tpl/shared/page/checkout/inc/checkout_steps_main.tpl',
        // PAYPAL-486 Theme-specific
        'modules/osc/paypal/change_payment_flow.tpl' => 'osc/paypal/views/tpl/flow/page/checkout/change_payment.tpl',
        'modules/osc/paypal/change_payment_wave.tpl' => 'osc/paypal/views/tpl/wave/page/checkout/change_payment.tpl',

        // PSPAYPAL-491 Installment banners
        'modules/osc/paypal/installment_banners.tpl' => 'osc/paypal/views/tpl/shared/installment_banners.tpl',

        // PSPAYPAL-483/PSPAYPAL-484 Subscription basics
        //TODO: for the first release we start without PayPal Subscriptions
        // 'modules/osc/paypal/order_and_subscription_overview.tpl' =>
        //     'osc/paypal/views/tpl/shared/page/account/order_and_subscription_overview.tpl',
        // 'modules/osc/paypal/order_and_partsubscription_overview.tpl' =>
        //     'osc/paypal/views/tpl/shared/page/account/order_and_partsubscription_overview.tpl',
    ],
    'events' => [
        'onActivate' => '\OxidSolutionCatalysts\PayPal\Core\Events\Events::onActivate',
        'onDeactivate' => '\OxidSolutionCatalysts\PayPal\Core\Events\Events::onDeactivate'
    ],
    'blocks' => [
        [
            'template' => 'page/checkout/order.tpl',
            'block' => 'shippingAndPayment',
            'file' => 'views/blocks/page/checkout/shipping_and_payment.tpl'
        ],
        [
            'template' => 'page/checkout/order.tpl',
            'block' => 'checkout_order_btn_submit_bottom',
            'file' => '/views/blocks/page/checkout/checkout_order_btn_submit_bottom.tpl'
        ],

        //TODO: for the first release we start without PayPal Subscriptions
        // [
        //     'template' => 'article_list.tpl',
        //     'block' => 'admin_article_list_item',
        //     'file' => 'views/blocks/admin/article_list_extended.tpl'
        // ],
        // [
        //     'template' => 'article_list.tpl',
        //     'block' => 'admin_article_list_colgroup',
        //     'file' => 'views/blocks/admin/article_list_colgroup_extended.tpl'
        // ],
        // [
        //     'template' => 'article_list.tpl',
        //     'block' => 'admin_article_list_sorting',
        //     'file' => 'views/blocks/admin/article_list_sorting_extended.tpl'
        // ],
        [
            'template' => 'headitem.tpl',
            'block' => 'admin_headitem_inccss',
            'file' => 'views/blocks/admin/admin_headitem_inccss.tpl'
        ],
        [
            'template' => 'headitem.tpl',
            'block' => 'admin_headitem_incjs',
            'file' => 'views/blocks/admin/admin_headitem_incjs.tpl'
        ],
        [
            'template' => 'layout/base.tpl',
            'block' => 'base_js',
            'file' => 'views/blocks/layout/base_js.tpl'
        ],
        [
            'template' => 'layout/base.tpl',
            'block' => 'base_style',
            'file' => 'views/blocks/layout/base_style.tpl'
        ],
        [
            'template' => 'widget/product/listitem_line.tpl',
            'block' => 'widget_product_listitem_line_price',
            'file' => 'views/blocks/widget/product/widget_product_listitem_line_price.tpl'
        ],
        [
            'template' => 'widget/product/listitem_infogrid.tpl',
            'block' => 'widget_product_listitem_infogrid_price',
            'file' => 'views/blocks/widget/product/widget_product_listitem_infogrid_price.tpl'
        ],
        [
            'template' => 'widget/product/listitem_grid.tpl',
            'block' => 'widget_product_listitem_grid_price',
            'file' => 'views/blocks/widget/product/widget_product_listitem_grid_price.tpl'
        ],
        [
            'template' => 'page/checkout/basket.tpl',
            'block' => 'basket_btn_next_bottom',
            'file' => '/views/blocks/page/checkout/basket_btn_next_bottom.tpl',
        ],
        // @Todo PAYPAL-486: Using the same file, with 2 themes. Should be more generic, if possible.
        [
            'template' => 'page/checkout/inc/steps.tpl',
            'block' => 'checkout_steps_main',
            'file' => '/views/blocks/page/checkout/inc/checkout_steps_main.tpl',
        ],
        [
            'template' => 'page/checkout/payment.tpl',
            'block' => 'select_payment',
            'file' => '/views/blocks/page/checkout/select_payment.tpl',
        ],
        [
            'template' => 'page/checkout/payment.tpl',
            'block' => 'change_payment',
            'file' => '/views/blocks/page/checkout/change_payment.tpl',
        ],
        [
            'template' => 'page/details/inc/productmain.tpl',
            'block' => 'details_productmain_tobasket',
            'file' => '/views/blocks/page/details/inc/details_productmain_tobasket.tpl',
        ],
        [
            'template' => 'page/details/inc/productmain.tpl',
            'block' => 'details_productmain_price_value',
            'file' => '/views/blocks/page/details/inc/details_productmain_price_value.tpl',
        ],

        // PSPAYPAL-491 Installment banners -->
        [
            'template' => 'page/checkout/basket.tpl',
            'block' => 'checkout_basket_next_step_top',
            'file' => '/views/blocks/page/checkout/basket_installment_banner_after.tpl'
        ],
        [
            'template' => 'page/checkout/basket.tpl',
            'block' => 'checkout_basket_emptyshippingcart',
            'file' => '/views/blocks/page/checkout/basket_installment_banner_before.tpl'
        ],
        [
            'template' => 'page/checkout/payment.tpl',
            'block' => 'checkout_payment_main',
            'file' => '/views/blocks/page/checkout/basket_installment_banner_before.tpl'
        ],
        [
            'template' => 'page/details/inc/productmain.tpl',
            'block' => 'details_productmain_price_value',
            'file' => '/views/blocks/page/details/inc/productmain.tpl'
        ],
        [
            'template' => 'page/list/list.tpl',
            'block' => 'page_list_listhead',
            'file' => '/views/blocks/page/list/list.tpl'
        ],
        [
            'template' => 'page/search/search.tpl',
            'block' => 'search_header',
            'file' => '/views/blocks/page/search/search.tpl'
        ],
        [
            'template' => 'page/shop/start.tpl',
            'block' => 'start_welcome_text',
            'file' => '/views/blocks/page/shop/start.tpl',
        ],
        // <-- PSPAYPAL-491
        [
            'template' => 'page/account/order.tpl',
            'block' => 'account_order_history_cart_items',
            'file' => '/views/blocks/page/account/order.tpl'
        ],

    ],
    'settings' => [
        [
            'name' => 'oscPayPalSandboxMode',
            'type' => 'bool',
            'value' => false,
            'group' => null
        ],
        [
            'name' => 'oscPayPalClientId',
            'type' => 'str',
            'value' => '',
            'group' => null
        ],
        [
            'name' => 'oscPayPalClientSecret',
            'type' => 'str',
            'value' => '',
            'group' => null
        ],
        [
            'name' => 'oscPayPalSandboxClientId',
            'type' => 'str',
            'value' => '',
            'group' => null
        ],
        [
            'name' => 'oscPayPalWebhookId',
            'type' => 'str',
            'value' => '',
            'group' => null
        ],
        [
            'name' => 'oscPayPalSandboxWebhookId',
            'type' => 'str',
            'value' => '',
            'group' => null
        ],
        [
            'name' => 'oscPayPalSandboxClientSecret',
            'type' => 'str',
            'value' => '',
            'group' => null
        ],
        [
            'name' => 'oscPayPalShowProductDetailsButton',
            'type' => 'bool',
            'value' => true,
            'group' => null
        ],
        [
            'name' => 'oscPayPalShowBasketButton',
            'type' => 'bool',
            'value' => true,
            'group' => null
        ],
        [
            'name' => 'oscPayPalShowPayLaterButton',
            'type' => 'bool',
            'value' => true,
            'group' => null
        ],
        [
            'name' => 'oscPayPalAutoBillOutstanding',
            'type' => 'bool',
            'value' => true,
            'group' => null
        ],
        [
            'name' => 'oscPayPalSetupFeeFailureAction',
            'type' => 'select',
            'value' => 'CONTINUE',
            'constraints' => 'CONTINUE|CANCEL',
            'group' => null
        ],
        [
            'name' => 'oscPayPalPaymentFailureThreshold',
            'type' => 'str',
            'value' => '',
            'group' => null
        ],
        [
            'name' => 'oscPayPalBannersShowAll',
            'type' => 'bool',
            'value' => true,
            'group' => null
        ],
        [
            'name' => 'oscPayPalBannersStartPage',
            'type' => 'bool',
            'value' => true,
            'group' => null
        ],
        [
            'name' => 'oscPayPalBannersStartPageSelector',
            'type' => 'str',
            'value' => '#wrapper .row',
            'group' => null
        ],
        [
            'name' => 'oscPayPalBannersCategoryPage',
            'type' => 'bool',
            'value' => true,
            'group' => null
        ],
        [
            'name' => 'oscPayPalBannersCategoryPageSelector',
            'type' => 'str',
            'value' => '.page-header',
            'group' => null
        ],
        [
            'name' => 'oscPayPalBannersSearchResultsPage',
            'type' => 'bool',
            'value' => true,
            'group' => null
        ],
        [
            'name' => 'oscPayPalBannersSearchResultsPageSelector',
            'type' => 'str',
            'value' => '#content .page-header .clearfix',
            'group' => null
        ],
        [
            'name' => 'oscPayPalBannersProductDetailsPage',
            'type' => 'bool',
            'value' => true,
            'group' => null
        ],
        [
            'name' => 'oscPayPalBannersProductDetailsPageSelector',
            'type' => 'str',
            'value' => '#detailsItemsPager'
        ],
        [
            'name' => 'oscPayPalBannersCheckoutPage',
            'type' => 'bool',
            'value' => true,
            'group' => null
        ],
        [
            'name' => 'oscPayPalBannersCartPageSelector',
            'type' => 'str',
            'value' => '.cart-buttons',
            'group' => null
        ],
        [
            'name' => 'oscPayPalBannersPaymentPageSelector',
            'type' => 'str',
            'value' => '.checkoutSteps ~ .spacer',
            'group' => null
        ],
        [
            'name' => 'oscPayPalBannersColorScheme',
            'type' => 'select',
            'constraints' => 'blue|black|white|white-no-border',
            'value' => 'blue',
            'group' => null
        ],
        [
            'name' => 'oscPayPalShowCheckoutButton',
            'type' => 'bool',
            'value' => true,
            'group' => null
        ],
        [
            'name' => 'oscPayPalLoginWithPayPalEMail',
            'type' => 'bool',
            'value' => true,
            'group' => null
        ],
        [
            'name' => 'oscPayPalAcdcEligibility',
            'type' => 'bool',
            'value' => false,
            'group' => null
        ],
        [
            'name' => 'oscPayPalPuiEligibility',
            'type' => 'bool',
            'value' => false,
            'group' => null
        ],
        [
            'name' => 'oscPayPalSandboxAcdcEligibility',
            'type' => 'bool',
            'value' => false,
            'group' => null
        ],
        [
            'name' => 'oscPayPalSandboxPuiEligibility',
            'type' => 'bool',
            'value' => false,
            'group' => null
        ]
    ]
];
