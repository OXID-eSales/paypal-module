<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Integration\Controller;

use OxidEsales\Eshop\Application\Model\User as EshopModelUser;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Utils;
use OxidSolutionCatalysts\PayPal\Controller\ProxyController;
use OxidSolutionCatalysts\PayPal\Tests\Integration\BaseTestCase;
use ReflectionMethod;
use RuntimeException;

/**
 * Google Pay / Apple Pay order requests without a user in the session (0008026).
 */
final class ProxyControllerTest extends BaseTestCase
{
    private const CUSTOMER_ID = '_test_wallet_customer';
    private const CUSTOMER_EMAIL = 'wallet-customer@example.test';

    /** @var Utils */
    private $originalUtils;

    /** @var string|null */
    private $output;

    protected function setUp(): void
    {
        parent::setUp();

        $user = oxNew(EshopModelUser::class);
        $user->setId(self::CUSTOMER_ID);
        $user->assign([
            'oxactive' => 1,
            'oxshopid' => 1,
            'oxrights' => 'user',
            'oxusername' => self::CUSTOMER_EMAIL,
            'oxpassword' => md5('password'),
            'oxfname' => 'Wallet',
            'oxlname' => 'Customer',
        ]);
        $user->save();

        // outputJson() ends with Utils::showMessageAndExit(): catch the answer instead.
        $this->originalUtils = Registry::getUtils();
        $utils = $this->getMockBuilder(Utils::class)
            ->onlyMethods(['showMessageAndExit', 'setHeader'])
            ->getMock();
        $utils->method('showMessageAndExit')->willReturnCallback(function ($message): void {
            $this->output = $message;
            throw new RuntimeException('exit');
        });
        Registry::set(Utils::class, $utils);

        $this->output = null;
        Registry::getSession()->deleteVariable('usr');
        Registry::getSession()->getBasket()->deleteBasket();
    }

    protected function tearDown(): void
    {
        Registry::set(Utils::class, $this->originalUtils);
        Registry::getSession()->deleteVariable('usr');
        Registry::getSession()->getBasket()->deleteBasket();
        $this->cleanUpTable('oxuser');

        parent::tearDown();
    }

    /**
     * @dataProvider walletMethodDataProvider
     */
    public function testWalletOrderWithoutUserIsRefused(string $method): void
    {
        $controller = oxNew(ProxyController::class);

        try {
            $controller->$method();
            $this->fail('Expected the request to be answered and stopped.');
        } catch (RuntimeException $exception) {
            $this->assertSame('exit', $exception->getMessage());
        }

        $this->assertSame('{"ERROR":"No user in the session."}', $this->output);
        // Nobody was logged in and the basket was not touched.
        $this->assertEmpty(Registry::getSession()->getVariable('usr'));
        $this->assertFalse($controller->getUser());
        $this->assertSame(0, Registry::getSession()->getBasket()->getItemsCount());
    }

    public function walletMethodDataProvider(): array
    {
        return [
            'Google Pay' => ['createGooglePayOrder'],
            'Apple Pay' => ['createApplepayOrder'],
        ];
    }

    public function testWalletOrderWithUserIsNotRefused(): void
    {
        $user = oxNew(EshopModelUser::class);
        $user->load(self::CUSTOMER_ID);
        $controller = oxNew(ProxyController::class);
        $controller->setUser($user);

        $method = new ReflectionMethod(ProxyController::class, 'isWalletRequestWithoutUser');
        $method->setAccessible(true);

        $this->assertFalse($method->invoke($controller, 'createGooglePayOrder'));
        $this->assertNull($this->output);
    }
}
