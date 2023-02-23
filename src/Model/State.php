<?php

namespace OxidSolutionCatalysts\PayPal\Model;

use OxidEsales\Eshop\Core\DatabaseProvider;

class State extends State_parent
{
    public function loadByIdAndCountry($oxid, $countryID)
    {
        $objOxId = DatabaseProvider::getDb()->getOne(
            "SELECT `OXID` FROM oxstates
            WHERE 1 AND oxid = :oxid
            AND oxcountryid = :oxcountryid",
            [
                ':oxid' => $oxid,
                ':oxcountryid' => $countryID
            ]
        );

        return $this->load($objOxId);
    }
}
