<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../../../../../vendor/autoload.php';

define('TEST_LIBRARY_HELPERS_PATH', __DIR__ . '/../../../../../../vendor/oxid-esales/testing-library/library/helpers/');

class_alias(
    OxidEsales\Eshop\Application\Component\UserComponent::class,
    OxidEsales\Eshop\Application\Component\UserComponent_parent::class
);


class_alias(
    \OxidEsales\Eshop\Application\Model\User::class,
    \OxidSolutionCatalysts\PayPal\Model\User_parent::class
);

class_alias(
    \OxidEsales\Eshop\Application\Component\UserComponent::class,
    \OxidSolutionCatalysts\PayPal\Component\UserComponent_parent::class
);

class_alias(
    \OxidEsales\Eshop\Application\Controller\Admin\DeliverySetMain::class,
    \OxidSolutionCatalysts\PayPal\Controller\Admin\DeliverySetMain_parent::class
);

class_alias(
    \OxidEsales\EshopCommunity\Application\Controller\Admin\OrderArticle::class,
    \OxidSolutionCatalysts\PayPal\Controller\Admin\OrderArticle_parent::class
);

class_alias(
    \OxidEsales\Eshop\Application\Controller\Admin\OrderList::class,
    \OxidSolutionCatalysts\PayPal\Controller\Admin\OrderList_parent::class
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
    \OxidEsales\Eshop\Application\Controller\ArticleDetailsController::class,
    \OxidSolutionCatalysts\PayPal\Controller\ArticleDetailsController_parent::class
);

class_alias(
    \OxidEsales\Eshop\Application\Controller\OrderController::class,
    \OxidSolutionCatalysts\PayPal\Controller\OrderController_parent::class
);

class_alias(
    \OxidEsales\Eshop\Application\Controller\UserController::class,
    \OxidSolutionCatalysts\PayPal\Controller\UserController_parent::class
);

class_alias(
    \OxidEsales\Eshop\Core\ViewConfig::class,
    \OxidSolutionCatalysts\PayPal\Core\ViewConfig_parent::class
);

class_alias(
    \OxidEsales\Eshop\Core\InputValidator::class,
    \OxidSolutionCatalysts\PayPal\Core\AmazonInputValidator_parent::class
);

class_alias(
    \OxidEsales\Eshop\Application\Model\Article::class,
    \OxidSolutionCatalysts\PayPal\Model\Article_parent::class
);

class_alias(
    \OxidEsales\Eshop\Application\Model\Basket::class,
    \OxidSolutionCatalysts\PayPal\Model\Basket_parent::class
);

class_alias(
    \OxidEsales\Eshop\Application\Model\Category::class,
    \OxidSolutionCatalysts\PayPal\Model\Category_parent::class
);

class_alias(
    \OxidEsales\Eshop\Application\Model\Order::class,
    \OxidSolutionCatalysts\PayPal\Model\Order_parent::class
);

class_alias(
    \OxidEsales\Eshop\Application\Controller\PaymentController::class,
    \OxidSolutionCatalysts\PayPal\Controller\PaymentController_parent::class
);
