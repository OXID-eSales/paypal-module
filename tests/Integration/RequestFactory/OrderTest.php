<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Integration\RequestFactory;

use OxidEsales\Eshop\Core\Registry as EshopRegistry;
use OxidEsales\Eshop\Core\Request;
use OxidSolutionCatalysts\PayPal\Core\Constants;
use OxidSolutionCatalysts\PayPal\Core\OrderRequestFactory;
use OxidSolutionCatalysts\PayPal\Exception\UserPhone as UserPhoneException;
use OxidSolutionCatalysts\PayPal\Tests\Integration\BaseTestCase;
use OxidSolutionCatalysts\PayPal\Tests\Integration\Trait\TestProductTrait;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\OrderRequest;
use OxidEsales\Eshop\Application\Model\Basket as EshopModelBasket;
use OxidEsales\Eshop\Application\Model\User as EshopModelUser;
use OxidSolutionCatalysts\PayPal\Core\PayPalDefinitions;

final class OrderTest extends BaseTestCase
{
    use TestProductTrait;

    protected const TEST_USER_ID = 'testuser';

    public function setUp(): void
    {
        parent::setUp();
    }

    /**
     * @throws \OxidEsales\Eshop\Core\Exception\OutOfStockException
     * @throws \OxidEsales\Eshop\Core\Exception\NoArticleException
     * @throws \OxidEsales\Eshop\Core\Exception\ArticleInputException
     */
    public function testCreatePuiPayPalOrderWithExperienceContext(): void
    {
        $user = oxNew(EshopModelUser::class);
        $user->load(self::TEST_USER_ID);

        $basket = oxNew(EshopModelBasket::class);
        $basket->addToBasket($this->testProductOxid, 1);
        $basket->setUser($user);
        $basket->setBasketUser($user);
        $basket->setPayment(PayPalDefinitions::EXPRESS_PAYPAL_PAYMENT_ID);
        $basket->setShipping('oxidstandard');
        $basket->calculateBasket(true);

        EshopRegistry::getSession()->setVariable('paymentid', PayPalDefinitions::PUI_PAYPAL_PAYMENT_ID);

        /** @var OrderRequestFactory $requestFactory */
        $requestFactory = EshopRegistry::get(OrderRequestFactory::class);
        $request = $requestFactory->getRequest(
            $basket,
            OrderRequest::INTENT_CAPTURE,
            OrderRequestFactory::USER_ACTION_CONTINUE,
            '',
            Constants::PAYPAL_PUI_PROCESSING_INSTRUCTIONS,
            PayPalDefinitions::PUI_PAYPAL_PAYMENT_ID,
        );

        $this->assertEquals(
            'SET_PROVIDED_ADDRESS',
            $request->payment_source["pay_upon_invoice"]
            ["experience_context"]
            ["shipping_preference"]
        );
        $this->assertStringContainsString(
            "cl=order&fnc=finalizepaypalsession",
            $request->payment_source["pay_upon_invoice"]["experience_context"]["return_url"]
        );
        $this->assertStringContainsString(
            "cl=order&fnc=cancelpaypalsession",
            $request->payment_source["pay_upon_invoice"]["experience_context"]["cancel_url"]
        );
    }

    public function tearDown(): void
    {
        EshopRegistry::set(Request::class, new Request());
        parent::tearDown();
    }
}
