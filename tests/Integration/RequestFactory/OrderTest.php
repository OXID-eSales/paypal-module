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
use OxidSolutionCatalysts\PayPalApi\Model\Orders\OrderRequest;
use OxidEsales\Eshop\Application\Model\Basket as EshopModelBasket;
use OxidEsales\Eshop\Application\Model\User as EshopModelUser;
use OxidEsales\Eshop\Application\Model\Payment as EshopModelPayment;
use OxidEsales\Eshop\Core\Price;
use OxidEsales\Eshop\Core\UtilsObject;
use OxidSolutionCatalysts\PayPal\Core\PayPalDefinitions;

final class OrderTest extends BaseTestCase
{
    protected const TEST_USER_ID = '06823b68-e4c3-4da8-b011-147195d9';

    protected const TEST_PRODUCT_ID = '5e6a374e212258abbfd76b6adf911772';

    public function testCreatePuiPayPalOrderRequestWithPuiRequiredFields(): void
    {
        $puiRequired = [
            'birthdate' => [
                'day' => 1,
                'month' => 4,
                'year' => 2000
            ],
            'phonenumber' => '040 111222333'
        ];

        $request = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();
        $request->method('getRequestParameter')->willReturn($puiRequired);

        EshopRegistry::set(Request::class, $request);

        //DE demo user
        $user = oxNew(EshopModelUser::class);
        $user->load(self::TEST_USER_ID);

        $basket = oxNew(EshopModelBasket::class);
        $basket->addToBasket(self::TEST_PRODUCT_ID, 1);
        $basket->setUser($user);
        $basket->setBasketUser($user);
        $basket->setPayment(PayPalDefinitions::EXPRESS_PAYPAL_PAYMENT_ID);
        $basket->setShipping('oxidstandard');
        $basket->calculateBasket(true);

        /** @var OrderRequestFactory $requestFactory */
        $requestFactory = EshopRegistry::get(OrderRequestFactory::class);
        $request = $requestFactory->getRequest(
            $basket,
            OrderRequest::INTENT_CAPTURE,
            OrderRequestFactory::USER_ACTION_CONTINUE,
            '',
            Constants::PAYPAL_PUI_PROCESSING_INSTRUCTIONS,
            PayPalDefinitions::PUI_REQUEST_PAYMENT_SOURCE_NAME,
        );

        $this->assertEquals('2000-04-01', $request->payment_source['pay_upon_invoice']->birth_date);
        $this->assertEquals('49', $request->payment_source['pay_upon_invoice']->phone->country_code);
        $this->assertEquals('40111222333', $request->payment_source['pay_upon_invoice']->phone->national_number);
    }

    public function testCreatePuiPayPalOrderRequestWithoutPuiRequiredFields(): void
    {
        $payment = $this->getMockBuilder(EshopModelPayment::class)
            ->onlyMethods([
                'getPaymentValue',
                'load',
            ])
            ->disableOriginalConstructor()
            ->getMock();
        $payment->method('getPaymentValue')->willReturn(5);

        EshopRegistry::getUtilsObject()::setClassInstance(EshopModelPayment::class, $payment);

        //DE demo user
        $user = oxNew(EshopModelUser::class);
        $user->load(self::TEST_USER_ID);

        $basket = oxNew(EshopModelBasket::class);
        $basket->addToBasket(self::TEST_PRODUCT_ID, 1);
        $basket->setUser($user);
        $basket->setBasketUser($user);
        $basket->setPayment(PayPalDefinitions::STANDARD_PAYPAL_PAYMENT_ID);
        $basket->setShipping('oxidstandard');
        $basket->calculateBasket(true);

        EshopRegistry::getUtilsObject()::resetClassInstances();

        /** @var OrderRequestFactory $requestFactory */
        $requestFactory = EshopRegistry::get(OrderRequestFactory::class);

        $this->expectException(UserPhoneException::class);
        $this->expectExceptionMessage(UserPhoneException::byRequestData()->getMessage());

        $requestFactory->getRequest(
            $basket,
            OrderRequest::INTENT_CAPTURE,
            OrderRequestFactory::USER_ACTION_CONTINUE,
            '',
            Constants::PAYPAL_PUI_PROCESSING_INSTRUCTIONS,
            PayPalDefinitions::PUI_REQUEST_PAYMENT_SOURCE_NAME,
        );
    }
}
