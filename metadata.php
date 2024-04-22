<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

use OxidEsales\Eshop\Application\Component\UserComponent;
use OxidEsales\Eshop\Application\Controller\OrderController;
use OxidEsales\Eshop\Application\Controller\PaymentController;
use OxidEsales\Eshop\Application\Controller\Admin\OrderMain;
use OxidEsales\Eshop\Application\Controller\Admin\OrderOverview;
use OxidEsales\Eshop\Application\Model\Article;
use OxidEsales\Eshop\Application\Model\Basket;
use OxidEsales\Eshop\Application\Model\Order;
use OxidEsales\Eshop\Application\Model\State;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Application\Model\Payment;
use OxidEsales\Eshop\Application\Model\PaymentGateway;
use OxidEsales\Eshop\Core\InputValidator;
use OxidEsales\Eshop\Core\ShopControl;
use OxidEsales\Eshop\Core\ViewConfig;
use OxidSolutionCatalysts\PayPal\Component\UserComponent as PayPalUserComponent;
use OxidSolutionCatalysts\PayPal\Controller\Admin\PayPalConfigController;
use OxidSolutionCatalysts\PayPal\Controller\Admin\PayPalOrderController;
use OxidSolutionCatalysts\PayPal\Controller\Admin\OrderMain as PayPalOrderMainController;
use OxidSolutionCatalysts\PayPal\Controller\Admin\OrderOverview as PayPalOrderOverviewController;
use OxidSolutionCatalysts\PayPal\Controller\OrderController as PayPalFrontEndOrderController;
use OxidSolutionCatalysts\PayPal\Controller\PaymentController as PayPalPaymentController;
use OxidSolutionCatalysts\PayPal\Controller\ProxyController;
use OxidSolutionCatalysts\PayPal\Controller\WebhookController;
use OxidSolutionCatalysts\PayPal\Core\InputValidator as PayPalInputValidator;
use OxidSolutionCatalysts\PayPal\Core\ShopControl as PayPalShopControl;
use OxidSolutionCatalysts\PayPal\Core\ViewConfig as PayPalViewConfig;
use OxidSolutionCatalysts\PayPal\Model\Article as PayPalArticle;
use OxidSolutionCatalysts\PayPal\Model\Basket as PayPalBasket;
use OxidSolutionCatalysts\PayPal\Model\Order as PayPalOrder;
use OxidSolutionCatalysts\PayPal\Model\State as PayPalState;
use OxidSolutionCatalysts\PayPal\Model\User as PayPalUser;
use OxidSolutionCatalysts\PayPal\Model\Payment as PayPalPayment;
use OxidSolutionCatalysts\PayPal\Model\PaymentGateway as PayPalPaymentGateway;

$sMetadataVersion = '2.1';

/**
 * Module information
 */
$aModule = [
    'id' => \OxidSolutionCatalysts\PayPal\Module::MODULE_ID,
    'title' => [
        'de' => 'PayPal Checkout für OXID',
        'en' => 'PayPal Checkout for OXID'
    ],
    'description' => [
        'de' => 'Nutzung des Online-Bezahldienstes von PayPal. Dokumentation: <a href="https://docs.oxid-esales.com/modules/paypal-checkout/de/latest/" target="_blank">PayPal Checkout</a>',
        'en' => 'Use of the online payment service from PayPal. Documentation: <a href="https://docs.oxid-esales.com/modules/paypal-checkout/en/latest/" target="_blank">PayPal Checkout</a>'
    ],
    'thumbnail' => 'img/paypal.png',
    'version' => '3.3.5-rc.3',
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
        Article::class => PayPalArticle::class,
        Payment::class => PayPalPayment::class,
        PaymentGateway::class => PayPalPaymentGateway::class,
        OrderController::class => PayPalFrontEndOrderController::class,
        PaymentController::class => PayPalPaymentController::class,
        UserComponent::class => PayPalUserComponent::class,
        OrderMain::class => PayPalOrderMainController::class,
        OrderOverview::class => PayPalOrderOverviewController::class,
        State::class => PayPalState::class
    ],
    'controllers' => [
        'oscpaypalconfig' => PayPalConfigController::class,
        'oscpaypalwebhook' => WebhookController::class,
        'oscpaypalproxy' => ProxyController::class,
        'oscpaypalorder' => PayPalOrderController::class,
    ],
    'events' => [
        'onActivate' => '\OxidSolutionCatalysts\PayPal\Core\Events\Events::onActivate',
        'onDeactivate' => '\OxidSolutionCatalysts\PayPal\Core\Events\Events::onDeactivate'
    ],
    'templates' => [
        '@osc_paypal/admin/oscpaypalconfig.tpl' => 'views/smarty/admin/oscpaypalconfig.tpl',
        '@osc_paypal/admin/oscpaypalorder.tpl' => 'views/smarty/admin/oscpaypalorder.tpl',
        '@osc_paypal/admin/oscpaypalorder_pp.tpl' => 'views/smarty/admin/oscpaypalorder_pp.tpl',
        '@osc_paypal/admin/oscpaypalorder_ppplus.tpl' => 'views/smarty/admin/oscpaypalorder_ppplus.tpl',
    ],
    'blocks'    => [
        [
            'template' => 'headitem.tpl',
            'block' => 'admin_headitem_inccss',
            'file' => 'views/smarty/extensions/theme/default/headitem__admin_headitem_inccss.tpl'
        ],
        [
            'template' => 'headitem.tpl',
            'block' => 'admin_headitem_incjs',
            'file' => 'views/smarty/extensions/theme/default/headitem__admin_headitem_incjs.tpl'
        ],
        [
            'template' => 'order_main.tpl',
            'block' => 'admin_order_main_form_shipping',
            'file' => 'views/smarty/extensions/theme/default/order_main__admin_order_main_form_shipping.tpl'
        ],
        [
            'template' => 'order_main.tpl',
            'block' => 'admin_order_main_send_order',
            'file' => 'views/smarty/extensions/theme/default/order_main__admin_order_main_send_order.tpl'
        ],

        [
            'template' => 'layout/base.tpl',
            'block' => 'base_js',
            'file' => 'views/smarty/extensions/theme/default/layout/base__base_js.tpl'
        ],
        [
            'template' => 'layout/base.tpl',
            'block' => 'base_style',
            'file' => 'views/smarty/extensions/theme/default/layout/base__base_style.tpl'
        ],
        [
            'template' => 'page/checkout/basket.tpl',
            'block' => 'basket_btn_next_bottom',
            'file' => 'views/smarty/extensions/theme/default/page/checkout/inc/basket__basket_btn_next_bottom.tpl',
        ],
        [
            'template' => 'page/checkout/basket.tpl',
            'block' => 'checkout_basket_next_step_top',
            'file' => 'views/smarty/extensions/theme/default/page/checkout/basket__checkout_basket_next_step_top.tpl',
        ],
        [
            'template' => 'page/checkout/basket.tpl',
            'block' => 'checkout_basket_emptyshippingcart',
            'file' => 'views/smarty/extensions/theme/default/page/checkout/basket__checkout_basket_emptyshippingcart.tpl',
        ],
        [
            'template' => 'page/checkout/payment.tpl',
            'block' => 'select_payment',
            'file' => 'views/smarty/extensions/theme/default/page/checkout/payment__select_payment.tpl',
        ],
        [
            'template' => 'page/checkout/payment.tpl',
            'block' => 'change_payment',
            'file' => 'views/smarty/extensions/theme/default/page/checkout/payment__change_payment.tpl',
        ],
        [
            'template' => 'page/checkout/payment.tpl',
            'block' => 'checkout_payment_main',
            'file' => 'views/smarty/extensions/theme/default/page/checkout/payment__checkout_payment_main.tpl',
        ],
        [
            'template' => 'page/checkout/order.tpl',
            'block' => 'shippingAndPayment',
            'file' => 'views/smarty/extensions/theme/default/page/checkout/order__shippingAndPayment.tpl',
        ],
        [
            'template' => 'page/details/inc/productmain.tpl',
            'block' => 'details_productmain_tobasket',
            'file' => 'views/smarty/extensions/theme/default/page/details/inc/productmain__details_productmain_tobasket.tpl',
        ],
        [
            'template' => 'page/details/inc/productmain.tpl',
            'block' => 'details_productmain_price_value',
            'file' => 'views/smarty/extensions/theme/default/page/details/inc/productmain__details_productmain_price_value.tpl',
        ],
        [
            'template' => 'page/list/list.tpl',
            'block' => 'page_list_listhead',
            'file' => 'views/smarty/extensions/theme/default/page/list/list__page_list_listhead.tpl',
        ],
        [
            'template' => 'page/shop/start.tpl',
            'block' => 'start_newest_articles',
            'file' => 'views/smarty/extensions/theme/default/page/shop/start__start_newest_articles.tpl',
        ],
        [
            'template' => 'widget/minibasket/minibasket.tpl',
            'block' => 'dd_layout_page_header_icon_menu_minibasket_functions',
            'file' => 'views/smarty/extensions/theme/default/widget/minibasket/minibasket__dd_layout_page_header_icon_menu_minibasket_functions.tpl',
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
            'name' => 'oscPayPalClientMerchantId',
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
            'name' => 'oscPayPalSandboxClientId',
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
            'name' => 'oscPayPalSandboxClientMerchantId',
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
            'name' => 'oscPayPalShowMiniBasketButton',
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
            'name' => 'oscPayPalStandardCaptureStrategy',
            'type' => 'select',
            'value' => 'directly',
            'constraints' => 'directly|delivery|manually'
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
            'value' => '.list-header',
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
            'value' => '.list-header',
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
            'value' => '.breadcrumb-wrapper > .container-xxl'
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
            'value' => '#basket-paypal-installment-banner',
            'group' => null
        ],
        [
            'name' => 'oscPayPalBannersPaymentPageSelector',
            'type' => 'str',
            'value' => '#shipping',
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
            'name' => 'oscPayPalLegacySettingsTransferred',
            'type' => 'bool',
            'value' => false,
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
        ],
        [
            'name' => 'oscPayPalSCAContingency',
            'type' => 'select',
            'value' => 'SCA_ALWAYS',
            'constraints' => 'SCA_ALWAYS|SCA_WHEN_REQUIRED|SCA_DISABLED',
            'group' => null
        ],
        [
            'name' => 'oscPayPalCleanUpNotFinishedOrdersAutomaticlly',
            'type' => 'bool',
            'value' => false,
            'group' => null
        ],
        [
            'name' => 'oscPayPalStartTimeCleanUpOrders',
            'type' => 'num',
            'value' => 60,
            'group' => null
        ],
        [
            'name' => 'oscPayPalActivePayments',
            'type' => 'arr',
            'value' => [],
            'group' => null
        ],
        [
            'group' => null,
            'name' => 'oscPayPalLocales',
            'type' => 'str',
            'value' => 'de_DE,en_US',
        ],
    ],
];
