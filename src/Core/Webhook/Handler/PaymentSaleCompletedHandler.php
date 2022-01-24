<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Core\Webhook\Handler;

use OxidSolutionCatalysts\PayPal\Core\Webhook\Event;
use OxidSolutionCatalysts\PayPal\Repository\SubscriptionRepository;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Application\Model\Order;
use OxidEsales\Eshop\Application\Model\OrderArticle;
use OxidEsales\Eshop\Application\Model\Article;
use OxidSolutionCatalysts\PayPal\Core\ServiceFactory;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Price;

class PaymentSaleCompletedHandler implements HandlerInterface
{
    /**
     * @inheritDoc
     */
    public function handle(Event $event): void
    {
        $lang = Registry::getLang();
        $data = $event->getData()['resource'];
        $billingAgreementId = $data['billing_agreement_id'];

        // If that's not a billing agrement hook, don't do anything
        if (!$billingAgreementId) {
            return;
        }

        $subscriptionRepository = new SubscriptionRepository();

        // collect relevant IDs
        $ids = $subscriptionRepository->getAllIdsFromBillingAgreementId($billingAgreementId);
        $parentOrderId = $ids['OXORDERID'];
        $oldArticleId = $ids['OXARTID'];
        $payPalProductId = $ids['PAYPALPRODUCTID'];
        $userId = $ids['OXUSERID'];
        $payPalSubscriptionPlanId = $ids['PAYPALSUBSCRIPTIONPLANID'];

        // collect Paypal-PlanDetails & -ProductDetails
        $sf = Registry::get(ServiceFactory::class);

        $subscriptionPlan = $sf
            ->getSubscriptionService()
            ->showPlanDetails('string', $payPalSubscriptionPlanId, 1);

        $payPalProduct = $sf
            ->getCatalogService()
            ->showProductDetails($payPalProductId);

        $paypalSubscriptionDetails = $sf
            ->getSubscriptionService()
            ->showSubscriptionDetails($billingAgreementId, 'last_failed_payment');

        // find the last cycle
        $cycleExecutions = $paypalSubscriptionDetails->billing_info->cycle_executions;
        $lastCycle = $this->findLastCycle($cycleExecutions);

        // prepare price
        $vat = $subscriptionPlan->taxes->percentage;
        $isBrutto = $subscriptionPlan->taxes->inclusive;
        $enterNetPrice = Registry::getConfig()->getConfigParam('blEnterNetPrice');
        $billingSubTotal = (float)$data['amount']['details']['subtotal'];
        $amount = 1;

        $singlePrice = oxNew(Price::class);
        $singlePrice->setVat($vat);
        if ($isBrutto) {
            $singlePrice->setBruttoPriceMode();
        } else {
            $singlePrice->setNettoPriceMode();
        }
        $singlePrice->setPrice($billingSubTotal);

        // create a temporary article that we can add to the new order
        $oldArticle = oxNew(Article::class);
        $oldArticle->load($oldArticleId);

        $newArticle = oxNew(Article::class);
        $newArticle->assign([
            'oxarticles__oxtitle'  => sprintf(
                $lang->translateString('OSC_PAYPAL_SUBSCRITION_PART_ARTICLE_TITLE'),
                $payPalProduct->name,
                $lastCycle['cycleNumber'],
                $lastCycle['cycleTotal']
            ),
            'oxarticles__oxprice'  => ($enterNetPrice ? $singlePrice->getNettoPrice() : $singlePrice->getBruttoPrice()),
            'oxarticles__oxvat'    => $vat,
            'oxarticles__oxartnum' => $oldArticle->oxarticles__oxartnum->value
        ]);
        $newArticle->save();
        $newArticleId = $newArticle->getId();

        // create order-article based on temporary article
        $newOrderArticle = oxNew(OrderArticle::class);
        $newOrderArticle->oxorderarticles__oxartid = new Field($newArticleId);
        $newOrderArticle->oxorderarticles__oxamount = new Field($amount);
        $newOrderArticle->oxorderarticles__oxartnum = new Field($oldArticle->oxarticles__oxartnum->value);

        // clone the old order and add the new article
        $parentOrder = oxNew(Order::class);
        $parentOrder->load($parentOrderId);
        $newOrder = oxNew(Order::class);
        $newOrder->oxClone($parentOrder);
        $newOrder->oxorder__oxordernr = null;
        $newOrder->oxorder__oxorderdate = null;
        $newOrder->setId();
        $newOrder->recalculateOrder([$newOrderArticle]);

        // save the new reference
        $subscriptionRepository->saveSubscriptionOrder(
            $billingAgreementId,
            $payPalSubscriptionPlanId,
            $userId,
            $newOrder->getId(),
            $parentOrderId,
            $lastCycle['cycleType'],
            $lastCycle['cycleNumber']
        );

        // delete the temporary article
        $newArticle->delete();
    }

    /**
     * PayPal has no Information about the last cycle, so we must iterate the whole data for cycle-execution
     * https://developer.paypal.com/docs/api/subscriptions/v1/#subscriptions-get-response
     */
    protected function findLastCycle($cycleExecutions): array
    {
        $result = [
            'cycleNumber'   => 0,
            'cycleTotal'    => 0,
            'cycleSequence' => 0,
            'cycleType'     => null
        ];

        $lastCycleExecution = null;
        foreach ($cycleExecutions as $cycleExecution) {
            if (is_null($lastCycleExecution)) {
                $lastCycleExecution = $cycleExecution;
            }
            if ($cycleExecution->cycles_completed > 0) {
                if ($cycleExecution->cycles_remaining > 0) {
                    $lastCycleExecution = $cycleExecution;
                    break;
                } elseif ($cycleExecution->cycles_remaining == 0) {
                    $lastCycleExecution = $cycleExecution;
                }
            }
        }

        if (!is_null($lastCycleExecution)) {
            $result = [
                'cycleNumber'   => $lastCycleExecution->cycles_completed,
                'cycleTotal'    => $lastCycleExecution->total_cycles,
                'cycleSequence' => $lastCycleExecution->sequence,
                'cycleType'     => $lastCycleExecution->tenure_type
            ];
        }

        return $result;
    }
}
