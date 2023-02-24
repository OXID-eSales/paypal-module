<?php

namespace OxidSolutionCatalysts\PayPal\Model;

use OxidEsales\Eshop\Core\DatabaseProvider;

class State extends State_parent
{
    public function loadByIdAndCountry($oxid, $countryID)
    {
        // must mimic the original "load" functionality
        $query = $this->buildSelectString([
            $this->getViewName() . '.oxid' => $oxid,
            $this->getViewName() . '.oxcountryid' => $countryID
        ]);
        $this->_isLoaded = $this->assignRecord($query);

        return $this->_isLoaded;
    }
}
