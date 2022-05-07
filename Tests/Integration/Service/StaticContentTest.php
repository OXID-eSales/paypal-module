<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Integration\Service;

use OxidSolutionCatalysts\PayPal\Core\PayPalDefinitions;
use OxidSolutionCatalysts\PayPal\Tests\Integration\BaseTestCase;
use OxidSolutionCatalysts\PayPal\Service\StaticContent;
use OxidEsales\Eshop\Application\Model\Payment as EshopModelPayment;
use OxidEsales\Eshop\Application\Model\Content as EshopModelContent;

final class StaticContentTest extends BaseTestCase
{
    public function testCreateStaticContent()
    {
        $before = oxNew(EshopModelContent::class);
        $before->loadByIdent('oscpaypalpuiconfirmation');
        $before->delete();

        $deleted = oxNew(EshopModelContent::class);
        $this->assertFalse($deleted->loadByIdent('oscpaypalpuiconfirmation'));

        $service = $this->getServiceFromContainer(StaticContent::class);
        $service->ensureStaticContents();

        $payPalStaticContent = PayPalDefinitions::getPayPalStaticContents();

        $after = oxNew(EshopModelContent::class);
        $after->loadByIdent('oscpaypalpuiconfirmation');
        $after->loadInLang(0, $after->getId());
        $this->assertEquals(
            $payPalStaticContent['oscpaypalpuiconfirmation']['oxtitle_de'],
            $after->getTitle()
        );
        $after->loadInLang(1, $after->getId());
        $this->assertEquals(
            $payPalStaticContent['oscpaypalpuiconfirmation']['oxtitle_en'],
            $after->getTitle()
        );
    }

    public function testExistingContentIsNotChanged(): void
    {
        $before = oxNew(EshopModelContent::class);
        $before->loadByIdent('oscpaypalpuiconfirmation');
        $before->delete();

        $deleted = oxNew(EshopModelContent::class);
        $this->assertFalse($deleted->loadByIdent('oscpaypalpuiconfirmation'));

        $service = $this->getServiceFromContainer(StaticContent::class);
        $service->ensureStaticContents();

        $changeme = oxNew(EshopModelContent::class);
        $changeme->loadByIdent('oscpaypalpuiconfirmation');
        $changeme->loadInLang(0, $changeme->getId());
        $changeme->assign(['oxtitle' => 'some test title']);
        $changeme->save();

        //now run service again
        $service = $this->getServiceFromContainer(StaticContent::class);
        $service->ensureStaticContents();

        $after = oxNew(EshopModelContent::class);
        $after->loadByIdent('oscpaypalpuiconfirmation');
        $this->assertEquals('some test title', $after->getTitle());
        $after->loadInLang(1, $after->getId());
        $payPalStaticContent = PayPalDefinitions::getPayPalStaticContents();
        $this->assertEquals(
            $payPalStaticContent['oscpaypalpuiconfirmation']['oxtitle_en'],
            $after->getTitle()
        );
    }

    public function testExistingPaymentsAreNotChanged(): void
    {
        $payment = oxNew(EshopModelPayment::class);
        $payment->loadInLang(0, PayPalDefinitions::STANDARD_PAYPAL_PAYMENT_ID);
        $payment->assign(
            [
                'oxdesc' => 'test_desc_de',
                'oxlongdesc' => 'test_longdesc_de'
            ]
        );
        $payment->save();

        $service = $this->getServiceFromContainer(StaticContent::class);
        $service->ensurePayPalPaymentMethods();

        $payment = oxNew(EshopModelPayment::class);
        $payment->loadInLang(0, PayPalDefinitions::STANDARD_PAYPAL_PAYMENT_ID);
        $this->assertEquals('test_desc_de', $payment->getFieldData('oxdesc'));
        $this->assertEquals('test_longdesc_de', $payment->getFieldData('oxlongdesc'));

        $payPalDefinitions = PayPalDefinitions::getPayPalDefinitions();

        $payment->loadInLang(1, PayPalDefinitions::STANDARD_PAYPAL_PAYMENT_ID);
        $this->assertEquals(
            $payPalDefinitions[PayPalDefinitions::STANDARD_PAYPAL_PAYMENT_ID]['descriptions']['en']['desc'],
            $payment->getFieldData('oxdesc')
        );
        $this->assertEquals(
            $payPalDefinitions[PayPalDefinitions::STANDARD_PAYPAL_PAYMENT_ID]['descriptions']['en']['longdesc'],
            $payment->getFieldData('oxlongdesc')
        );
    }

    public function testEnsurePaymentMethods(): void
    {
        $paymentIds = array_keys(PayPalDefinitions::getPayPalDefinitions());

        //clean up before test
        foreach ($paymentIds as $paymentId) {
            $payment = oxNew(EshopModelPayment::class);
            $payment->load($paymentId);
            $payment->delete();
        }

        $service = $this->getServiceFromContainer(StaticContent::class);
        $service->ensurePayPalPaymentMethods();

        $payPalDefinitions = PayPalDefinitions::getPayPalDefinitions();

        foreach ($paymentIds as $paymentId) {
            $payment = oxNew(EshopModelPayment::class);
            $this->assertTrue($payment->load($paymentId));

            $payment->loadInLang(0, $paymentId);
            $this->assertEquals(
                htmlspecialchars(
                    $payPalDefinitions[$paymentId]['descriptions']['de']['desc']
                ),
                $payment->getFieldData('oxdesc')
            );
            $this->assertEquals(
                htmlspecialchars(
                    $payPalDefinitions[$paymentId]['descriptions']['de']['longdesc']
                ),
                $payment->getFieldData('oxlongdesc')
            );

            $payment->loadInLang(1, $paymentId);
            $this->assertEquals(
                htmlspecialchars(
                    $payPalDefinitions[$paymentId]['descriptions']['en']['desc']
                ),
                $payment->getFieldData('oxdesc')
            );
            $this->assertEquals(
                htmlspecialchars(
                    $payPalDefinitions[$paymentId]['descriptions']['en']['longdesc']
                ),
                $payment->getFieldData('oxlongdesc')
            );

            $this->assertNotEmpty($payment->getCountries(), $paymentId);
        }
    }
}
