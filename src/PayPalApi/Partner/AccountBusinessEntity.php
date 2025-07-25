<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */


declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPalApi\Partner;

use JsonSerializable;
use OxidSolutionCatalysts\PayPalApi\Model\BaseModel;
use OxidSolutionCatalysts\PayPalApi\Model\Partner\BusinessAddressDetail2;
use OxidSolutionCatalysts\PayPalApi\Model\Partner\BusinessTypeInfo;

class AccountBusinessEntity implements JsonSerializable
{
    use BaseModel;

    /** @var BusinessTypeInfo */
    public $business_type;

    /** @var BusinessAddressDetail2[]*/
    public $addresses;
}