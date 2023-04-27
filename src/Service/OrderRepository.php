<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Service;

use Doctrine\DBAL\Query\QueryBuilder;
use OxidEsales\Eshop\Application\Model\Order as EshopModelOrder;
use OxidEsales\Eshop\Core\Config as EshopCoreConfig;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\ContextInterface;
use OxidSolutionCatalysts\PayPal\Core\Constants;
use OxidSolutionCatalysts\PayPal\Exception\NotFound;
use OxidSolutionCatalysts\PayPal\Model\PayPalOrder as PayPalOrderModel;
use PDO;

class OrderRepository
{
    /** @var QueryBuilderFactoryInterface */
    private $queryBuilderFactory;

    /** @var ContextInterface */
    private $context;

    /** @var EshopCoreConfig */
    private $config;

    public function __construct(
        QueryBuilderFactoryInterface $queryBuilderFactory,
        ContextInterface             $context,
        EshopCoreConfig              $config
    )
    {
        $this->queryBuilderFactory = $queryBuilderFactory;
        $this->context = $context;
        $this->config = $config;
    }

    public function paypalOrderByOrderIdAndPayPalId(
        string $shopOrderId,
        string $paypalOrderId = '',
        string $payPalTransactionId = ''
    ): PayPalOrderModel
    {

        $oxid = $this->getId(
            $shopOrderId,
            $paypalOrderId,
            $payPalTransactionId,
            Constants::PAYPAL_TRANSACTION_TYPE_CAPTURE
        );


        #$oxid = empty($oxid) && empty($payPalTransactionId) ? $this->getId($shopOrderId, $paypalOrderId, '') : '';
        #$oxid = empty($oxid) && !empty($payPalTransactionId) ? $this->getId($shopOrderId, $paypalOrderId, '') : '';

        #$oxid = $oxid ?:
        #    (empty($payPalTransactionId) ? '' : $this->getId($shopOrderId, $paypalOrderId));

        /**
         * TODO:
         *
         * 1) Datensatz mit orderid und paypalid vorhanden, keine transid -> transid speichern - abgeschlossen?
         * 2) Datensatz mit orderid und paypalid vorhanden, andere transid -> neue datensatz speichern
         * 3) Datensatz mit orderid und paypalid vorhanden, genaue transid -> einfach laden
         */

        $order = oxNew(PayPalOrderModel::class);
        $order1 = null;
        if ($oxid === '') {
            // Fall 1)
            $oxid = $this->getId($shopOrderId, $paypalOrderId, '');
            $order->load($oxid);
            $isOrderLoaded = $order->isLoaded();
            if (!empty($payPalTransactionId) && $isOrderLoaded) {
                $order->setTransactionId($payPalTransactionId);
                $order->save();
            }

            // Fall 2
            $oxid = $this->getId($shopOrderId, $paypalOrderId);
            $order->load($oxid);
            $isOrderLoaded = $order->isLoaded();
            if (!empty($payPalTransactionId) && !$isOrderLoaded) {
                $order1 = oxNew(PayPalOrderModel::class);
                $order1->assign(
                    [
                        'oxorderid' => $shopOrderId,
                        'oxpaypalorderid' => $paypalOrderId
                    ]
                );
                $order1->setTransactionId($payPalTransactionId);
                $order1->save();
            }

            // Fall 3
            if (!$isOrderLoaded) {
                $order = oxNew(PayPalOrderModel::class);
                $order->load($oxid);

                if (!$order->isLoaded()) {
                    $order->assign(
                        [
                            'oxorderid' => $shopOrderId,
                            'oxpaypalorderid' => $paypalOrderId
                        ]
                    );
                    $order->setTransactionId($payPalTransactionId);
                }
            }


            /*
                    if ($order->getTransactionId() !== '') {
                        $order1 = oxNew(PayPalOrderModel::class);
                        $order1->assign(
                            [
                                'oxorderid' => $shopOrderId,
                                'oxpaypalorderid' => $paypalOrderId
                            ]
                        );
                        $order1->setTransactionId($payPalTransactionId);
                        $order1->save();
                    }
                } else {
                    $order1 = oxNew(PayPalOrderModel::class);
                    $order1->assign(
                        [
                            'oxorderid' => $shopOrderId,
                            'oxpaypalorderid' => $paypalOrderId
                        ]
                    );
                    $order1->setTransactionId($payPalTransactionId);
                    $order1->save();
                }
            } else {
                $oxid = $this->getId($shopOrderId, $paypalOrderId);
            }
            */
        }


        return $order1 ?: $order;
    }

    public function paypalOrderByOrderId(
        string $shopOrderId
    ): PayPalOrderModel
    {
        $result = null;

        $oxid = $this->getId($shopOrderId);
        $order = oxNew(PayPalOrderModel::class);
        $order->load($oxid);
        return $order;
    }

    /**
     * @throws NotFound
     */
    public function getShopOrderByPayPalOrderId(string $paypalOrderId): EshopModelOrder
    {
        $orderId = $this->getShopOrderIdByPaypalOrderId($paypalOrderId);
        if (empty($orderId)) {
            throw NotFound::orderNotFoundByPayPalOrderId();
        }

        $order = oxNew(EshopModelOrder::class);
        $order->load($orderId);
        if (!$order->isLoaded()) {
            throw NotFound::orderNotFound();
        }

        return $order;
    }

    /**
     * @throws NotFound
     */
    public function getShopOrderByPayPalTransactionId(string $paypalTransactionId): EshopModelOrder
    {
        $orderId = $this->getShopOrderIdByPaypalTransactionId($paypalTransactionId);
        if (empty($orderId)) {
            throw NotFound::orderNotFoundByPayPalTransactionId();
        }

        $order = oxNew(EshopModelOrder::class);
        $order->load($orderId);
        if (!$order->isLoaded()) {
            throw NotFound::orderNotFound();
        }

        return $order;
    }

    public function getPayPalOrderIdByShopOrderId(string $shopOrderId): string
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->queryBuilderFactory->create();

        $parameters = [
            'oxorderid' => $shopOrderId
        ];

        $queryBuilder->select('oxpaypalorderid')
            ->from('oscpaypal_order')
            ->where('oxorderid = :oxorderid');

        $id = $queryBuilder->setParameters($parameters)
            ->setMaxResults(1)
            ->execute()
            ->fetch(PDO::FETCH_COLUMN);

        return (string)$id;
    }

    public function cleanUpNotFinishedOrders(): void
    {
        if (!$this->config->getConfigParam('oscPayPalCleanUpNotFinishedOrdersAutomaticlly')) {
            return;
        }

        $sessiontime = (int)$this->config->getConfigParam('oscPayPalStartTimeCleanUpOrders');
        $shopId = $this->config->getShopId();

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->queryBuilderFactory->create();

        $parameters = [
            'oxordernr' => '0',
            'oxtransstatus' => 'NOT_FINISHED',
            'oxpaymenttype' => 'oscpaypal',
            'sessiontime' => $sessiontime,
            'oxshopid' => $shopId
        ];

        $queryBuilder->select('oxid')
            ->from('oxorder')
            ->where('oxordernr = :oxordernr')
            ->andWhere('oxtransstatus = :oxtransstatus')
            ->andWhere('oxshopid = :oxshopid')
            ->andWhere($queryBuilder->expr()->like(
                'oxpaymenttype',
                $queryBuilder->expr()->literal('%' . $parameters['oxpaymenttype'] . '%')
            ))
            ->andWhere('oxorderdate < now() - interval :sessiontime MINUTE');

        $ids = $queryBuilder->setParameters($parameters)
            ->execute()
            ->fetchAll(PDO::FETCH_COLUMN);

        foreach ($ids as $id) {
            $order = oxNew(EshopModelOrder::class);
            if ($order->load($id)) {
                // storno
                $order->cancelOrder();
                // delete
                $order->delete();
            }
        }
    }

    private function getId(
        string  $shopOrderId,
        ?string $paypalOrderId = null,
        ?string $payPalTransactionId = null,
        ?string $payPalTransactionType = null
    ): string
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->queryBuilderFactory->create();

        $parameters = [
            'oxorderid' => $shopOrderId,
        ];

        $queryBuilder->select('oxid')
            ->from('oscpaypal_order')
            ->where('oxorderid = :oxorderid');

        if (!is_null($paypalOrderId)) {
            $parameters['oxpaypalorderid'] = $paypalOrderId;
            $queryBuilder->andWhere('oxpaypalorderid = :oxpaypalorderid');
        }
        if (!is_null($payPalTransactionId)) {
            $parameters['oscpaypaltransactionid'] = $payPalTransactionId;
            $queryBuilder->andWhere('oscpaypaltransactionid = :oscpaypaltransactionid');
        }
        if (!is_null($payPalTransactionType)) {
            $parameters['oscpaypaltransactiontype'] = $payPalTransactionType;
            $queryBuilder->andWhere('oscpaypaltransactiontype = :oscpaypaltransactiontype');
        }

        $sql = $queryBuilder->setParameters($parameters)->getSQL();
        fwrite(STDERR, "SQL: " . var_export($sql, true) . "\n");
        fwrite(STDERR, "params: " . var_export($queryBuilder->getParameters(), true) . "\n");

        $id = $queryBuilder->setParameters($parameters)
            ->setMaxResults(1)
            ->execute()
            ->fetch(PDO::FETCH_COLUMN);

        return (string)$id;
    }

    private function getShopOrderIdByPaypalOrderId(string $paypalOrderId): string
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->queryBuilderFactory->create();

        $parameters = [
            'oxpaypalorderid' => $paypalOrderId
        ];

        $queryBuilder->select('oxorderid')
            ->from('oscpaypal_order')
            ->where('oxpaypalorderid = :oxpaypalorderid')
            ->andWhere('LENGTH(oxorderid) > 0');

        $id = $queryBuilder->setParameters($parameters)
            ->setMaxResults(1)
            ->execute()
            ->fetch(PDO::FETCH_COLUMN);

        return (string)$id;
    }

    private function getShopOrderIdByPaypalTransactionId(string $paypalTransactionId): string
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->queryBuilderFactory->create();

        $parameters = [
            'oscpaypaltransactionid' => $paypalTransactionId
        ];

        $queryBuilder->select('oxorderid')
            ->from('oscpaypal_order')
            ->where('oscpaypaltransactionid = :oscpaypaltransactionid')
            ->andWhere('LENGTH(oxorderid) > 0');

        $id = $queryBuilder->setParameters($parameters)
            ->setMaxResults(1)
            ->execute()
            ->fetch(PDO::FETCH_COLUMN);

        return (string)$id;
    }
}
