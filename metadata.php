<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

use OxidEsales\Eshop\Application\Component\BasketComponent;
use OxidEsales\Eshop\Application\Component\UserComponent;
use OxidEsales\Eshop\Application\Controller\Admin\ModuleConfiguration;
use OxidEsales\Eshop\Application\Controller\OrderController;
use OxidEsales\Eshop\Application\Controller\PaymentController;
use OxidEsales\Eshop\Application\Controller\Admin\OrderMain;
use OxidEsales\Eshop\Application\Controller\Admin\OrderArticle;
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
use OxidSolutionCatalysts\PayPal\Component\BasketComponent as PayPalBasketComponent;
use OxidSolutionCatalysts\PayPal\Component\UserComponent as PayPalUserComponent;
use OxidSolutionCatalysts\PayPal\Controller\Admin\ModuleConfiguration as PaypalModuleConfiguration;
use OxidSolutionCatalysts\PayPal\Controller\Admin\PayPalOrderController;
use OxidSolutionCatalysts\PayPal\Controller\Admin\OrderArticle as PayPalOrderArticleController;
use OxidSolutionCatalysts\PayPal\Controller\Admin\OrderMain as PayPalOrderMainController;
use OxidSolutionCatalysts\PayPal\Controller\Admin\OrderOverview as PayPalOrderOverviewController;
use OxidSolutionCatalysts\PayPal\Controller\OrderController as PayPalFrontEndOrderController;
use OxidSolutionCatalysts\PayPal\Controller\PaymentController as PayPalPaymentController;
use OxidSolutionCatalysts\PayPal\Controller\PayPalVaultingCardController;
use OxidSolutionCatalysts\PayPal\Controller\ProxyController;
use OxidSolutionCatalysts\PayPal\Controller\VaultingTokenController;
use OxidSolutionCatalysts\PayPal\Controller\WebhookController;
use OxidSolutionCatalysts\PayPal\Controller\PayPalVaultingController;
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
    'version' => '3.5.0-rc.1',
    'author' => 'OXID eSales AG',
    'url' => 'https://www.oxid-esales.com',
    'email' => 'info@oxid-esales.com',
    'extend' => [
        ModuleConfiguration::class => PaypalModuleConfiguration::class,
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
        BasketComponent::class => PayPalBasketComponent::class,
        OrderMain::class => PayPalOrderMainController::class,
        OrderArticle::class => PayPalOrderArticleController::class,
        OrderOverview::class => PayPalOrderOverviewController::class,
        State::class => PayPalState::class
    ],
    'controllers' => [
        'oscpaypalwebhook'    => WebhookController::class,
        'oscpaypalproxy'      => ProxyController::class,
        'oscpaypalorder'      => PayPalOrderController::class,
        'oscaccountvault'     => PayPalVaultingController::class,
        'oscaccountvaultcard' => PayPalVaultingCardController::class,
        'osctokencontroller'  => VaultingTokenController::class,
    ],
    'events' => [
        'onActivate' => '\OxidSolutionCatalysts\PayPal\Core\Events\Events::onActivate',
        'onDeactivate' => '\OxidSolutionCatalysts\PayPal\Core\Events\Events::onDeactivate'
    ],
    'templates' => [
        // Admin: Config
        '@osc_paypal/admin/oscpaypalconfig.tpl' => 'views/smarty/admin/oscpaypalconfig.tpl',

        // Admin: Order
        '@osc_paypal/admin/oscpaypalorder.tpl' => 'views/smarty/admin/oscpaypalorder.tpl',
        '@osc_paypal/admin/oscpaypalorder_pp.tpl' => 'views/smarty/admin/oscpaypalorder_pp.tpl',
        '@osc_paypal/admin/oscpaypalorder_ppplus.tpl' => 'views/smarty/admin/oscpaypalorder_ppplus.tpl',

        '@osc_paypal/frontend/shared/paymentbuttons.tpl' => 'views/smarty/frontend/shared/paymentbuttons.tpl',
        '@osc_paypal/frontend/shared/paypalexpresshint.tpl' => 'views/smarty/frontend/shared/paypalexpresshint.tpl',

        '@osc_paypal/frontend/flow/pui.tpl' => 'views/smarty/frontend/flow/page/checkout/pui.tpl',
        '@osc_paypal/frontend/wave/pui.tpl' => 'views/smarty/frontend/wave/page/checkout/pui.tpl',
        '@osc_paypal/frontend/shared/pui_fraudnet.tpl' => 'views/smarty/frontend/shared/page/checkout/pui_fraudnet.tpl',
        '@osc_paypal/frontend/shared/apple_pay.tpl' => 'views/smarty/frontend/shared/page/checkout/apple_pay.tpl',
        '@osc_paypal/frontend/flow/shipping_and_payment.tpl' => 'views/smarty/frontend/flow/page/checkout/shipping_and_payment.tpl',
        '@osc_paypal/frontend/wave/shipping_and_payment.tpl' => 'views/smarty/frontend/wave/page/checkout/shipping_and_payment.tpl',
        '@osc_paypal/frontend/flow/shipping_and_payment_paypal.tpl' => 'views/smarty/frontend/flow/page/checkout/shipping_and_payment_paypal.tpl',
        '@osc_paypal/frontend/wave/shipping_and_payment_paypal.tpl' => 'views/smarty/frontend/wave/page/checkout/shipping_and_payment_paypal.tpl',
        '@osc_paypal/frontend/flow/checkout_order_btn_submit_bottom.tpl' => 'views/smarty/frontend/flow/page/checkout/checkout_order_btn_submit_bottom.tpl',
        '@osc_paypal/frontend/wave/checkout_order_btn_submit_bottom.tpl' => 'views/smarty/frontend/wave/page/checkout/checkout_order_btn_submit_bottom.tpl',

        // PAYPAL-486 Register templates for overloading here;
        // use theme name in key when theme-specific. Shared templates don't receive a theme-specific key.
        '@osc_paypal/frontend/shared/acdc.tpl' => 'views/smarty/frontend/shared/page/checkout/acdc.tpl',
        '@osc_paypal/frontend/shared/sepa_cc_alternative.tpl' => 'views/smarty/frontend/shared/page/checkout/sepa_cc_alternative.tpl',
        '@osc_paypal/frontend/shared/select_payment.tpl' => 'views/smarty/frontend/shared/page/checkout/select_payment.tpl',
        '@osc_paypal/frontend/shared/details_productmain_tobasket.tpl' =>
            'views/smarty/frontend/shared/page/details/inc/details_productmain_tobasket.tpl',
        // PAYPAL-486 Theme-specific
        '@osc_paypal/frontend/flow/change_payment.tpl' => 'views/smarty/frontend/flow/page/checkout/change_payment.tpl',
        '@osc_paypal/frontend/wave/change_payment.tpl' => 'views/smarty/frontend/wave/page/checkout/change_payment.tpl',

        // PSPAYPAL-822 Button customization
         '@osc_paypal/frontend/layout/base_paypal_button_config.tpl' => 'views/smarty/frontend/shared/layout/base_paypal_button_config.tpl',

        // PSPAYPAL-491 Installment banners
        '@osc_paypal/frontend/shared/installment_banners.tpl' => 'views/smarty/frontend/shared/installment_banners.tpl',

        // PSPAYPAL-685 Installment banners
        '@osc_paypal/frontend/shared/googlepay.tpl' => 'views/smarty/frontend/shared/googlepay.tpl',

        '@osc_paypal/frontend/shared/applepay.tpl' => 'views/smarty/frontend/shared/applepay.tpl',

        //PSPAYPAL-680 Vaulting
        '@osc_paypal/frontend/account_vaulting_paypal.tpl'    => 'views/smarty/frontend/shared/page/account/account_vaulting_paypal.tpl',
        '@osc_paypal/frontend/account_vaulting_card.tpl'      => 'views/smarty/frontend/shared/page/account/account_vaulting_card.tpl',
        '@osc_paypal/frontend/shared/vaultedpaymentsources.tpl'      => 'views/smarty/frontend/shared/vaultedpaymentsources.tpl',
        '@osc_paypal/frontend/flow/vaultedpaymentsources.tpl' => 'views/smarty/frontend/flow/vaulting/vaultedpaymentsources.tpl',
        '@osc_paypal/frontend/wave/vaultedpaymentsources.tpl' => 'views/smarty/frontend/wave/vaulting/vaultedpaymentsources.tpl',
    ],
    'blocks'    => [
        [
            'template' => 'headitem.tpl',
            'block' => 'admin_headitem_inccss',
            'file' => 'views/smarty/admin/blocks/admin_headitem_inccss.tpl'
        ],
        [
            'template' => 'headitem.tpl',
            'block' => 'admin_headitem_incjs',
            'file' => 'views/smarty/admin/blocks/admin_headitem_incjs.tpl'
        ],
        [
            'template' => 'order_main.tpl',
            'block' => 'admin_order_main_form_shipping',
            'file' => 'views/smarty/admin/blocks/admin_order_main_form_shipping.tpl'
        ],
        [
            'template' => 'order_main.tpl',
            'block' => 'admin_order_main_send_order',
            'file' => 'views/smarty/admin/blocks/admin_order_main_send_order.tpl'
        ],
        [
            'template' => 'layout/base.tpl',
            'block' => 'base_js',
            'file' => 'views/smarty/frontend/blocks/layout/base_js.tpl'
        ],
        [
            'template' => 'layout/base.tpl',
            'block' => 'base_style',
            'file' => 'views/smarty/frontend/blocks/layout/base_style.tpl'
        ],
        [
            'template' => 'page/account/inc/account_menu.tpl',
            'block' => 'account_menu',
            'file' => 'views/smarty/frontend/blocks/page/account/inc/account_menu.tpl',
        ],
        [
            'template' => 'page/checkout/basket.tpl',
            'block' => 'basket_btn_next_bottom',
            'file' => 'views/smarty/frontend/blocks/page/checkout/basket_btn_next_bottom.tpl',
        ],
        [
            'template' => 'page/checkout/basket.tpl',
            'block' => 'checkout_basket_next_step_top',
            'file' => 'views/smarty/frontend/blocks/page/checkout/basket_installment_banner_after.tpl',
        ],
        [
            'template' => 'page/checkout/basket.tpl',
            'block' => 'checkout_basket_backtoshop_bottom',
            'file' => 'views/smarty/frontend/blocks/page/checkout/checkout_basket_backtoshop_bottom.tpl'
        ],
        [
            'template' => 'page/checkout/basket.tpl',
            'block' => 'checkout_basket_emptyshippingcart',
            'file' => 'views/smarty/frontend/blocks/page/checkout/basket_installment_banner_before.tpl',
        ],
        [
            'template' => 'page/checkout/order.tpl',
            'block' => 'checkout_order_btn_submit_bottom',
            'file' => 'views/smarty/frontend/blocks/page/checkout/checkout_order_btn_submit_bottom.tpl'
        ],
        [
            'template' => 'page/checkout/order.tpl',
            'block' => 'shippingAndPayment',
            'file' => 'views/smarty/frontend/blocks/page/checkout/shipping_and_payment.tpl',
        ],
        [
            'template' => 'page/checkout/payment.tpl',
            'block' => 'change_payment',
            'file' => 'views/smarty/frontend/blocks/page/checkout/change_payment.tpl',
        ],
        [
            'template' => 'page/checkout/payment.tpl',
            'block' => 'checkout_payment_main',
            'file' => 'views/smarty/frontend/blocks/page/checkout/basket_installment_banner_before.tpl',
        ],
        [
            'template' => 'page/checkout/payment.tpl',
            'block' => 'select_payment',
            'file' => 'views/smarty/frontend/blocks/page/checkout/select_payment.tpl',
        ],
        [
            'template' => 'page/checkout/thankyou.tpl',
            'block' => 'checkout_thankyou_info',
            'file' => 'views/smarty/frontend/blocks/page/checkout/checkout_thankyou_info.tpl',
        ],
        [
            'template' => 'page/details/inc/productmain.tpl',
            'block' => 'details_productmain_price_value',
            'file' => 'views/smarty/frontend/blocks/page/details/inc/details_productmain_price_value.tpl',
        ],
        [
            'template' => 'page/details/inc/productmain.tpl',
            'block' => 'details_productmain_tobasket',
            'file' => 'views/smarty/frontend/blocks/page/details/inc/details_productmain_tobasket.tpl',
        ],
        [
            'template' => 'page/list/list.tpl',
            'block' => 'page_list_listhead',
            'file' => 'views/smarty/frontend/blocks/page/list/page_list_listhead.tpl',
        ],
        [
            'template' => 'page/search/search.tpl',
            'block' => 'search_header',
            'file' => 'views/smarty/frontend/blocks/page/search/search_header.tpl'
        ],
        [
            'template' => 'page/shop/start.tpl',
            'block' => 'start_welcome_text',
            'file' => 'views/smarty/frontend/blocks/page/shop/start_welcome_text.tpl',
        ],
        [
            'template' => 'widget/minibasket/minibasket.tpl',
            'block' => 'dd_layout_page_header_icon_menu_minibasket_functions',
            'file' => 'views/smarty/frontend/blocks/widget/minibasket/dd_layout_page_header_icon_menu_minibasket_functions.tpl',
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
            'constraints' => 'directly|delivery|manually',
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
            'value' => '#detailsItemsPager',
            'group' => null
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
            'name' => 'oscPayPalVaultingEligibility',
            'type' => 'bool',
            'value' => false,
            'group' => null
        ],
        [
            'name' => 'oscPayPalApplePayEligibility',
            'type' => 'bool',
            'value' => false,
            'group' => null
        ],
        [
            'name' => 'oscPayPalGooglePayEligibility',
            'type' => 'bool',
            'value' => false,
            'group' => null
        ],
        [
            'name' => 'oscPayPalEpsEligibility',
            'type' => 'bool',
            'value' => false,
            'group' => null
        ],
        [
            'name' => 'oscPayPalPrzelewy24Eligibility',
            'type' => 'bool',
            'value' => false,
            'group' => null
        ],
        [
            'name' => 'oscPayPalSepaEligibility',
            'type' => 'bool',
            'value' => false,
            'group' => null
        ],
        [
            'name' => 'oscPayPalBlikEligibility',
            'type' => 'bool',
            'value' => false,
            'group' => null
        ],
        [
            'name' => 'oscPayPalBanContactEligibility',
            'type' => 'bool',
            'value' => false,
            'group' => null
        ],
        [
            'name' => 'oscPayPalIDealEligibility',
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
            'name' => 'oscPayPalSandboxVaultingEligibility',
            'type' => 'bool',
            'value' => false,
            'group' => null
        ],
        [
            'name' => 'oscPayPalSandboxApplePayEligibility',
            'type' => 'bool',
            'value' => false,
            'group' => null
        ],
        [
            'name' => 'oscPayPalSandboxGooglePayEligibility',
            'type' => 'bool',
            'value' => false,
            'group' => null
        ],
        [
            'name' => 'oscPayPalSandboxEpsEligibility',
            'type' => 'bool',
            'value' => false,
            'group' => null
        ],
        [
            'name' => 'oscPayPalSandboxPrzelewy24Eligibility',
            'type' => 'bool',
            'value' => false,
            'group' => null
        ],
        [
            'name' => 'oscPayPalSandboxSepaEligibility',
            'type' => 'bool',
            'value' => false,
            'group' => null
        ],
        [
            'name' => 'oscPayPalSandboxBlikEligibility',
            'type' => 'bool',
            'value' => false,
            'group' => null
        ],
        [
            'name' => 'oscPayPalSandboxBanContactEligibility',
            'type' => 'bool',
            'value' => false,
            'group' => null
        ],
        [
            'name' => 'oscPayPalSandboxIDealEligibility',
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
            'group' => null,
            'name' => 'oscPayPalLocales',
            'type' => 'str',
            'value' => 'de_DE,en_US',
        ],
        [
            'name' => 'oscPayPalSetVaulting',
            'type' => 'bool',
            'value' => true,
            'group' => null
        ],
        [
            'name' => 'oscPayPalUseGooglePayAddress',
            'type' => 'bool',
            'value' => false,
            'group' => null
        ],
        [
            'name' => 'oscPayPalDefaultShippingPriceExpress',
            'type' => 'str',
            'value' => '3.5',
            'group' => null
        ],
        [
            'name' => 'oscPayPalUseStructuralCustomIdSchema',
            'type' => 'bool',
            'value' => true,
            'group' => null
        ],
        [
            'name' => 'oscPayPalButtonStyleLayout',
            'type' => 'select',
            'constraints' => 'vertical|horizontal',
            'value' => 'vertical',
            'group' => null
        ],
        [
            'name' => 'oscPayPalButtonStyleColor',
            'type' => 'select',
            'constraints' => 'gold|blue|silver|white|black',
            'value' => 'gold',
            'group' => null
        ],
        [
            'name' => 'oscPayPalButtonStyleShape',
            'type' => 'select',
            'constraints' => 'rect|pill|sharp',
            'value' => 'rect',
            'group' => null
        ],
        [
            'name' => 'oscPayPalButtonStyleLabel',
            'type' => 'select',
            'constraints' => 'paypal|checkout|buynow|pay|installment',
            'value' => 'paypal',
            'group' => null
        ],
    ],
];
