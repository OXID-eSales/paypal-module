<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Integration\RequestFactory;

use OxidEsales\Eshop\Core\Registry as EshopRegistry;
use OxidEsales\EshopCommunity\Core\Request;
use OxidSolutionCatalysts\PayPal\Core\Constants;
use OxidSolutionCatalysts\PayPal\Service\Factory\OrderRequestFactory;
use OxidSolutionCatalysts\PayPal\Tests\Integration\BaseTestCase;
use OxidSolutionCatalysts\PayPal\Tests\Integration\Trait\TestProductTrait;
use OxidSolutionCatalysts\PayPal\Traits\ServiceContainer;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\OrderRequest;
use OxidEsales\Eshop\Application\Model\Basket as EshopModelBasket;
use OxidEsales\Eshop\Application\Model\User as EshopModelUser;
use OxidSolutionCatalysts\PayPal\Core\PayPalDefinitions;

final class OrderTest extends BaseTestCase
{
    use TestProductTrait;
    use ServiceContainer;

    protected const TEST_USER_ID = 'testuser';

    public function testCreatePuiPayPalOrderRequestWithPuiRequiredFields(): void
    {
        $_POST['pui_required'] =
            [
                'birthdate' => [
                    'day' => 1,
                    'month' => 4,
                    'year' => 2000
                ]
            ];

        $user = oxNew(EshopModelUser::class);
        $user->load(self::TEST_USER_ID);

        $basket = oxNew(EshopModelBasket::class);
        $basket->addToBasket($this->testProductOxid, 1);
        $basket->setUser($user);
        $basket->setBasketUser($user);
        $basket->setPayment(PayPalDefinitions::PAYMENT_SOURCE_PUI);
        $basket->setShipping('oxidstandard');
        $basket->calculateBasket(true);

        EshopRegistry::getSession()->setVariable('paymentid', PayPalDefinitions::PUI_PAYPAL_PAYMENT_ID);

        /** @var OrderRequestFactory $requestFactory */
        $requestFactory = $this->getServiceFromContainer(\OxidSolutionCatalysts\PayPal\Service\Factory\OrderRequestFactory::class);
        $session = EshopRegistry::getSession();
        $session->setVariable('paymentid', PayPalDefinitions::PUI_PAYPAL_PAYMENT_ID);

        $request = $requestFactory->getRequest(
            $basket,
            OrderRequest::INTENT_CAPTURE,
            OrderRequestFactory::USER_ACTION_CONTINUE,
            '',
            Constants::PAYPAL_PUI_PROCESSING_INSTRUCTIONS,
            PayPalDefinitions::PUI_PAYPAL_PAYMENT_ID,
        );

        $birthdate = $request->payment_source->pay_upon_invoice->birthdate;

        $dateString = sprintf(
            '%04d-%02d-%02d',
            $birthdate['year'],
            $birthdate['month'],
            $birthdate['day']
        );

        $this->assertEquals('2000-04-01', $dateString);
    }

    public function tearDown(): void
    {
        EshopRegistry::set(Request::class, new Request());
        parent::tearDown();
    }
}
