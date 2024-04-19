<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

class_alias(
    \OxidEsales\Eshop\Application\Component\UserComponent::class,
    \OxidEsales\Eshop\Application\Component\UserComponent_parent::class
);

class_alias(
    \OxidEsales\EshopCommunity\Application\Component\UserComponent::class,
    \OxidEsales\EshopCommunity\Application\Component\UserComponent_parent::class,
);

class_alias(
    \OxidEsales\Eshop\Application\Controller\Admin\OrderMain::class,
    \OxidSolutionCatalysts\PayPal\Controller\Admin\OrderMain_parent::class
);

class_alias(
    \OxidEsales\Eshop\Application\Controller\Admin\OrderOverview::class,
    \OxidSolutionCatalysts\PayPal\Controller\Admin\OrderOverview_parent::class
);

class_alias(
    \OxidEsales\Eshop\Application\Controller\OrderController::class,
    \OxidSolutionCatalysts\PayPal\Controller\OrderController_parent::class
);

class_alias(
    \OxidEsales\Eshop\Application\Controller\PaymentController::class,
    \OxidSolutionCatalysts\PayPal\Controller\PaymentController_parent::class,
);

class_alias(
    \OxidEsales\EshopCommunity\Core\InputValidator::class,
    \OxidEsales\EshopCommunity\Core\InputValidator_parent::class,
);

class_alias(
    \OxidEsales\EshopCommunity\Core\ShopControl::class,
    \OxidEsales\EshopCommunity\Core\ShopControl_parent::class
);

class_alias(
    \OxidEsales\Eshop\Core\ViewConfig::class,
    \OxidEsales\Eshop\Core\ViewConfig_parent::class,
);

class_alias(
    \OxidEsales\Eshop\Application\Model\Order::class,
    \OxidEsales\Eshop\Application\Model\Order_parent::class,
);

class_alias(
    \OxidSolutionCatalysts\PayPal\Model\Country::class,
    \OxidEsales\Eshop\Application\Model\Country_parent::class,
);

class_alias(
    \OxidEsales\Eshop\Application\Model\User::class,
    \OxidSolutionCatalysts\PayPal\Model\User_parent::class
);

class_alias(
    \OxidEsales\Eshop\Application\Model\State::class,
    \OxidSolutionCatalysts\PayPal\Model\State_parent::class
);

class_alias(
    \OxidEsales\Eshop\Application\Model\Payment::class,
    \OxidSolutionCatalysts\PayPal\Model\Payment_parent::class
);

class_alias(
    \OxidEsales\Eshop\Application\Model\PaymentGateway::class,
    \OxidSolutionCatalysts\PayPal\Model\PaymentGateway_parent::class
);

class_alias(
    \OxidEsales\Eshop\Application\Model\Article::class,
    \OxidSolutionCatalysts\PayPal\Model\Article_parent::class
);

class_alias(
    \OxidEsales\Eshop\Application\Model\Order::class,
    \OxidSolutionCatalysts\PayPal\Model\Order_parent::class
);

class_alias(
    \OxidEsales\Eshop\Application\Model\Basket::class,
    \OxidSolutionCatalysts\PayPal\Model\Basket_parent::class
);

class_alias(
    \OxidEsales\EshopCommunity\Application\Controller\OrderController::class,
    \OxidEsales\EshopCommunity\Application\Controller\OrderController_parent::class
);

class_alias(
    \OxidEsales\EshopCommunity\Application\Model\Article::class,
    \OxidEsales\EshopCommunity\Application\Model\Article_parent::class,
);

class_alias(
    \OxidEsales\EshopCommunity\Application\Controller\OrderController::class,
    \OxidEsales\EshopCommunity\Application\Controller\OrderController_parent::class,
);

class_alias(
    \OxidEsales\EshopCommunity\Application\Model\Order::class,
    \OxidEsales\EshopCommunity\Application\Model\Order_parent::class
);
