<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Core\Events;

use OxidEsales\DoctrineMigrationWrapper\MigrationsBuilder;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Application\Model\Payment as EshopModelPayment;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Bridge\ModuleConfigurationDaoBridgeInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Bridge\ModuleSettingBridgeInterface;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\ContextInterface;
use OxidSolutionCatalysts\PayPal\Core\PayPalDefinitions;
use OxidSolutionCatalysts\PayPal\Service\Logger;
use OxidSolutionCatalysts\PayPal\Service\ModuleSettings;
use OxidSolutionCatalysts\PayPal\Traits\ServiceContainer;
use OxidSolutionCatalysts\PayPal\Service\StaticContent;
use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\Console\Output\BufferedOutput;

class Events
{
    use ServiceContainer;

    /**
     * Execute action on activate event
     */
    public static function onActivate(): void
    {
        // execute module migrations
        self::executeModuleMigrations();

        //add static contents and payment methods
        self::addStaticContents();

        //extend session required controller
        self::addRequireSession();
    }

    /**
     * Execute action on deactivate event
     *
     * @return void
     * @throws \Exception
     */
    public static function onDeactivate(): void
    {
        $activePayments = [];
        foreach (PayPalDefinitions::getPayPalDefinitions() as $paymentId => $paymentDefinitions) {
            /** @var \OxidEsales\Eshop\Application\Model\Payment $paymentMethod */
            $paymentMethod = oxNew(EshopModelPayment::class);
            if (isset($paymentMethod->oxpayments__oxactive) &&
                $paymentMethod->load($paymentId) &&
                (bool)$paymentMethod->oxpayments__oxactive->value
            ) {
                $activePayments[] = $paymentId;
                $paymentMethod->oxpayments__oxactive = new Field(false);
                $paymentMethod->save();
            }
        }
        $service = self::getModuleSettingsService();
        $service->saveActivePayments($activePayments);
    }

    /**
     * Execute necessary module migrations on activate event
     *
     * @return void
     */
    private static function executeModuleMigrations(): void
    {
        $migrations = (new MigrationsBuilder())->build();

        $output = new BufferedOutput();
        $migrations->setOutput($output);
        $neeedsUpdate = $migrations->execute('migrations:up-to-date', 'osc_paypal');

        if ($neeedsUpdate) {
            $migrations->execute('migrations:migrate', 'osc_paypal');
        }
    }

    /**
     * Execute necessary module migrations on activate event
     *
     * @return void
     */
    private static function addStaticContents(): void
    {
        $service = self::getStaticContentService();
        $service->ensureStaticContents();
        $service->ensurePayPalPaymentMethods();
    }

    /**
     * add details controller to requireSession
     */
    private static function addRequireSession(): void
    {
        $service = self::getModuleSettingsService();
        $service->addRequireSession();
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private static function getStaticContentService(): StaticContent
    {
        /*
        Normally I would fetch the StaticContents service like this:

        $service = ContainerFactory::getInstance()
            ->getContainer()
            ->get(StaticContent::class);

        But the services are not ready when the onActivate method is triggered.
        That's why I build the containers by hand as an exception.:
        */

        /** @var ContainerInterface $container */
        $container = ContainerFactory::getInstance()
            ->getContainer();
        /** @var QueryBuilderFactoryInterface $queryBuilderFactory */
        $queryBuilderFactory = $container->get(QueryBuilderFactoryInterface::class);
        $moduleSettings = self::getModuleSettingsService();

        return new StaticContent(
            $queryBuilderFactory,
            $moduleSettings
        );
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private static function getModuleSettingsService(): ModuleSettings
    {
        /*
        Normally I would fetch the StaticContents service like this:

        $service = ContainerFactory::getInstance()
            ->getContainer()
            ->get(ModuleSettings::class);

        But the services are not ready when the onActivate method is triggered.
        That's why I build the containers by hand as an exception.:
        */

        /** @var ContainerInterface $container */
        $container = ContainerFactory::getInstance()
            ->getContainer();
        /** @var ModuleSettingBridgeInterface $moduleSettingsBridge */
        $moduleSettingsBridge = $container->get(ModuleSettingBridgeInterface::class);
        /** @var ContextInterface $context */
        $context = $container->get(ContextInterface::class);
        /** @var ModuleConfigurationDaoBridgeInterface $moduleConfigurationDaoBridgeInterface */
        $moduleConfigurationDaoBridgeInterface = $container->get(ModuleConfigurationDaoBridgeInterface::class);
        /** @var Logger $logger */
        $logger = $container->get(Logger::class);
        return new ModuleSettings(
            $moduleSettingsBridge,
            $context,
            $moduleConfigurationDaoBridgeInterface,
            $logger
        );
    }
}
