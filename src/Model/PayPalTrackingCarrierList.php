<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Model;

use OxidEsales\Eshop\Core\Model\ListModel;
use OxidSolutionCatalysts\PayPal\Traits\ServiceContainer;

class PayPalTrackingCarrierList extends ListModel
{
    use ServiceContainer;

    /**
     * List Object class name
     *
     * @var string
     */
    protected $_sObjectsInListName // phpcs:ignore PSR2.Classes.PropertyDeclaration
        = 'OxidSolutionCatalysts\PayPal\Model\PayPalTrackingCarrier';

    /**
     * Load Tracking-Carrier models
     *
     * @param string $countryCode - optional
     */
    public function loadTrackingCarriers(string $countryCode = '')
    {
        $baseObject = $this->getBaseObject();
        $viewName = $baseObject->getViewName();
        $select = "select * from {$viewName} where 1 ";
        $selectParams = [];
        if ($countryCode) {
            $select .= "and {$viewName}.oxcountrycode = :oxcountrycode";
            $selectParams[':oxcountrycode'] = $countryCode;
        }
        $this->selectString($select, $selectParams);
    }

    /**
     * Load allowed Tracking-Carrier Country-Codes
     *
     */
    public function getAllowedTrackingCarrierCountryCodes(): array
    {
        $result = [];
        $db = DatabaseProvider::getDb();
        $baseObject = $this->getBaseObject();
        $viewName = $baseObject->getViewName();
        $viewNameCountry = $this->getViewName('oxcountry');

        $select = "select count({$viewName}.oxid), {$viewName}.oxcountrycode
            from {$viewName}
            where {$viewName}.oxcountrycode in (select {$viewNameCountry}.oxisoalpha2 from {$viewNameCountry} where {$viewNameCountry}.oxactive = 1)
            or {$viewName}.oxcountrycode not in (select {$viewNameCountry}.oxisoalpha2 from {$viewNameCountry})
            group by {$viewName}.oxcountrycode";


        /** @var \OxidEsales\Eshop\Core\Database\Adapter\Doctrine\ResultSet $resultDB */
        $resultDB = $db->select($select);
        if ($resultDB != false && $resultDB->count() > 0) {
            while (!$resultDB->EOF) {
                $result[] = $resultDB->fields['oxcountrycode'];
            }
        }

        return $result;
    }
}
