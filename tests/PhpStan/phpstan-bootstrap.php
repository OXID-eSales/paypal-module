<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../../../../../vendor/autoload.php';

define('TEST_LIBRARY_HELPERS_PATH', __DIR__ . '/../../../../../../vendor/oxid-esales/testing-library/library/helpers/');

class_alias(
    OxidEsales\Eshop\Application\Component\UserComponent::class,
    OxidEsales\Eshop\Application\Component\UserComponent_parent::class
);
