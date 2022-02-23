<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Integration\RequestFactory;

use OxidEsales\Eshop\Core\Registry as EshopRegistry;
use OxidSolutionCatalysts\PayPal\Core\Constants;
use OxidSolutionCatalysts\PayPal\Core\OrderRequestFactory;
use OxidSolutionCatalysts\PayPal\Exception\UserPhone as UserPhoneException;
use OxidSolutionCatalysts\PayPal\Tests\Integration\BaseTestCase;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\OrderRequest;
use OxidEsales\Eshop\Application\Model\Basket as EshopModelBasket;
use OxidEsales\Eshop\Application\Model\User as EshopModelUser;
use OxidSolutionCatalysts\PayPal\Core\PayPalDefinitions;

final class OrderTest extends BaseTestCase
{
    const TEST_USER_ID = 'e7af1c3b786fd02906ccd75698f4e6b9';

    const TEST_PRODUCT_ID = 'dc5ffdf380e15674b56dd562a7cb6aec';

    public function testCreatePuiPayPalOrderRequestWithPuiRequiredFields(): void
    {
        $this->setRequestParameter('pui_required_birthdate_day', 1);
        $this->setRequestParameter('pui_required_birthdate_month', 4);
        $this->setRequestParameter('pui_required_birthdate_year', 2000);
        $this->setRequestParameter('pui_required_phonenumber', '040 111222333');

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