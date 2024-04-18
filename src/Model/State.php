<?php

namespace OxidSolutionCatalysts\PayPal\Model;

class State extends State_parent
{
    public function loadByIdAndCountry(string $oxIsoAlpha2, string $countryID): bool
    {
        // must mimic the original "load" functionality
        $query = $this->buildSelectString([
            $this->getViewName() . '.oxisoalpha2' => $oxIsoAlpha2,
            $this->getViewName() . '.oxcountryid' => $countryID
        ]);
        $this->_isLoaded = $this->assignRecord($query);

        return $this->_isLoaded;
    }
}
