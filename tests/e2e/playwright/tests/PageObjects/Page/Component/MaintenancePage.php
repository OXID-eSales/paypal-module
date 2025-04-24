<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\Codeception\Page\Component;

trait MaintenancePage
{
    public function isInMaintenanceMode(): static
    {
        $I = $this->user;
        $I->waitForText('Maintenance mode');

        return $this;
    }
}
