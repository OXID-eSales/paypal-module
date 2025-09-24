<?php

namespace OxidSolutionCatalysts\PayPal\EventSubscriber;

use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Registry;
use OxidSolutionCatalysts\PayPal\Event\PayPalVaultingSucceededEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PayPalVaultingSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            PayPalVaultingSucceededEvent::NAME => 'onVaultingSucceeded',
        ];
    }

    public function onVaultingSucceeded(PayPalVaultingSucceededEvent $event): void
    {
        $user = $event->getUser();
        if (!$user instanceof User) {
            $user = oxNew(User::class);
            $user->loadActiveUser();
        }
        if (!$user) {
            return;
        }

        $payPalCustomerId = $event->getPayPalCustomerId();
        if (!$payPalCustomerId) {
            return;
        }

        $user->oxuser__oscpaypalcustomerid = new Field($payPalCustomerId);
        $user->save();

        // Set the same flag used elsewhere to indicate vault success
        Registry::getSession()->setVariable('vaultSuccess', true);
    }
}
