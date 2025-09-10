<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Service;

use OxidEsales\Eshop\Application\Model\Address;
use OxidEsales\Eshop\Application\Model\User as EshopUserModel;
use OxidEsales\EshopCommunity\Core\Registry as EshopRegistry;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;


class UserAddressPaypalService
{
    private QueryBuilderFactoryInterface $queryBuilderFactory;

    public function __construct(
        QueryBuilderFactoryInterface $queryBuilderFactory
    ) {
        $this->queryBuilderFactory = $queryBuilderFactory;
    }

    public function changePayPalUserData(EshopUserModel $oUser, array $newInvoiceAddress, array $newDeliveryAddress)
    {
        $oUser->assign($newInvoiceAddress);

        if (count($newDeliveryAddress)) {

            $queryBuilder = $this->queryBuilderFactory->create();

            $queryBuilder
                ->select('OXID')
                ->from('oxaddress')
                ->where('OXUSERID = :userid')
                ->andWhere('OXFNAME = :fname')
                ->andWhere('OXLNAME = :lname')
                ->andWhere('OXSTREET = :street')
                ->andWhere('OXSTREETNR = :streetnr')
                ->andWhere('OXCITY = :city')
                ->andWhere('OXCOUNTRYID = :countryid')
                ->andWhere('OXZIP = :zip');
            $queryBuilder->setParameters([
                'userid'    => $oUser->getId(),
                'fname'     => $newDeliveryAddress['oxaddress__oxfname'],
                'lname'     => $newDeliveryAddress['oxaddress__oxlname'],
                'street'    => $newDeliveryAddress['oxaddress__oxstreet'],
                'streetnr'  => $newDeliveryAddress['oxaddress__oxstreetnr'],
                'city'      => $newDeliveryAddress['oxaddress__oxcity'],
                'countryid' => $newDeliveryAddress['oxaddress__oxcountryid'],
                'zip'       => $newDeliveryAddress['oxaddress__oxzip']
            ]);

            $result = $queryBuilder->execute();
            $sAddressId = $result->fetchOne();

            $oAddress = oxNew(Address::class);
            if ($sAddressId) {
                $oAddress->load($sAddressId);
                $oAddress->setId($sAddressId);
            }

            $oAddress->assign($newDeliveryAddress);
            $oAddress->save();

            $oUser->resetAddresses();

            EshopRegistry::getSession()->setVariable('deladrid', $oAddress->getId());
        } else {
            EshopRegistry::getSession()->setVariable('deladrid', null);
        }
    }
}
