<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * Prepares a shop with demo data for the Playwright specs (CI and local runs):
 * - customers have to confirm the terms and conditions (the specs test that),
 * - the countries of the local payment methods (AT: EPS, BE: Bancontact, NL: iDEAL, PL: P24)
 *   are active and can be shipped to with the delivery set "Standard".
 *
 * Idempotent. Run from the shop root: php <path>/prepare-shop.php
 */

declare(strict_types=1);

use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Registry;

require getcwd() . '/source/bootstrap.php';

const PLAYWRIGHT_COUNTRIES = ['AT', 'BE', 'NL', 'PL'];
const PLAYWRIGHT_DELIVERY_SET = 'oxidstandard';

$db = DatabaseProvider::getDb();
$config = Registry::getConfig();
$shopId = $config->getShopId();

$config->saveShopConfVar('bool', 'blConfirmAGB', true);

// the "Standard" shipping rule for the rest of the EU, whichever id the demo data gave it
$deliveryIds = $db->getCol(
    'select d.oxid from oxdelivery d
       join oxdel2delset x on x.oxdelid = d.oxid
      where x.oxdelsetid = ? and d.oxactive = 1 and d.oxtitle like ?',
    [PLAYWRIGHT_DELIVERY_SET, '%Rest EU%']
);

foreach (PLAYWRIGHT_COUNTRIES as $iso) {
    $countryId = (string)$db->getOne('select oxid from oxcountry where oxisoalpha2 = ?', [$iso]);
    if ($countryId === '') {
        fwrite(STDERR, "Country $iso not found\n");
        exit(1);
    }
    $db->execute('update oxcountry set oxactive = 1 where oxid = ?', [$countryId]);

    $assignments = [['oxdelset', PLAYWRIGHT_DELIVERY_SET]];
    foreach ($deliveryIds as $deliveryId) {
        $assignments[] = ['oxcountry', $deliveryId];
    }
    foreach ($assignments as [$type, $deliveryId]) {
        $exists = $db->getOne(
            'select 1 from oxobject2delivery where oxdeliveryid = ? and oxobjectid = ? and oxtype = ?',
            [$deliveryId, $countryId, $type]
        );
        if (!$exists) {
            $db->execute(
                'insert into oxobject2delivery (oxid, oxdeliveryid, oxobjectid, oxtype) values (?, ?, ?, ?)',
                [Registry::getUtilsObject()->generateUId(), $deliveryId, $countryId, $type]
            );
        }
    }
    echo "$iso: active, delivery set " . PLAYWRIGHT_DELIVERY_SET . ', ' . count($deliveryIds) . " shipping rule(s)\n";
}

echo "blConfirmAGB: on\n";
