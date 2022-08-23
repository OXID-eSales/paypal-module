<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Core\Events;

use OxidEsales\DoctrineMigrationWrapper\MigrationsBuilder;
use OxidSolutionCatalysts\PayPal\Core\PayPalDefinitions;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Application\Model\Payment as EshopModelPayment;
use OxidSolutionCatalysts\PayPal\Service\ModuleSettings;
use OxidSolutionCatalysts\PayPal\Traits\ServiceContainer;
use OxidSolutionCatalysts\PayPal\Service\StaticContent;
use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
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
        //NOTE: this assumes the module's servies.yaml is already in place at the time this method is called
        self::addStaticContents();

        //extend session required controller
        self::addRequireSession();
    }

    /**
     * Execute action on deactivate event
     *
     * @return void
     */
    public static function onDeactivate(): void
    {
        foreach (PayPalDefinitions::getPayPalDefinitions() as $paymentId => $paymentDefinitions) {
            $paymentMethod = oxNew(EshopModelPayment::class);
            if ($paymentMethod->load($paymentId)) {
                $paymentMethod->oxpayments__oxactive = new Field(false);
                $paymentMethod->save();
            }
        }
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
        /** @var StaticContent $service */
        $service = ContainerFactory::getInstance()
            ->getContainer()
            ->get(StaticContent::class);

        $service->ensureStaticContents();
        $service->ensurePayPalPaymentMethods();
    }

    /**
     * add details controller to requireSession
     */
    private static function addRequireSession(): void
    {
        /** @var ModuleSettings $moduleSettings */
        $moduleSettings = ContainerFactory::getInstance()
            ->getContainer()
            ->get(ModuleSettings::class);
        $moduleSettings->addRequireSession();
    }
}
