<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Core;

final class PayPalDefinitions
{
    public const STANDARD_PAYPAL_PAYMENT_ID = 'oxidpaypal';
    public const ACDC_PAYPAL_PAYMENT_ID = 'oxidpaypal_acdc';

    private const PAYPAL_DEFINTIONS = [
        //Standard PayPal
         self::STANDARD_PAYPAL_PAYMENT_ID => [
            'descriptions' => [
                'de' => [
                    'desc' => "PayPal v2",
                    'longdesc' => "Bezahlen Sie bequem mit PayPal"
                ],
                'en' => [
                    'desc' => "PayPal v2",
                    'longdesc' => "Pay conveniently with PayPal"
                ]
            ],
            'countries' => []
        ],
        self::ACDC_PAYPAL_PAYMENT_ID => [
            'descriptions' => [
                'de' => [
                    'desc' => "Kreditkarte (über PayPal)",
                    'longdesc' => "Bezahlen Sie bequem mit PayPal"
                ],
                'en' => [
                    'desc' => "Creditcard (via PayPal)",
                    'longdesc' => "Pay conveniently with PayPal"
                ]
            ],
            'countries' => ['DE']
        ],
        // uAPM Bancontact
        'oxidpaypal_bancontact' => [
            'descriptions' => [
                'de' => [
                    'desc' => "Bancontact (über PayPal)",
                    'longdesc' => "Bezahlen Sie bequem mit Bancontact"
                ],
                'en' => [
                    'desc' => "Bancontact (via PayPal)",
                    'longdesc' => "Pay conveniently with Bancontact"
                ]
            ],
            'countries' => ['BE'],
            'uapmpaymentsource' => 'bancontact'
        ],
        // uAPM Boleto Bancário
        'oxidpaypal_boleto' => [
            'descriptions' => [
                'de' => [
                    'desc' => "Boleto Bancário (über PayPal)",
                    'longdesc' => "Bezahlen Sie bequem mit Boleto Bancário"
                ],
                'en' => [
                    'desc' => "Boleto Bancário (via PayPal)",
                    'longdesc' => "Pay conveniently with Boleto Bancário"
                ]
            ],
            'countries' => ['BR'],
            'uapmpaymentsource' => 'boletobancario'
        ],
        // uAPM BLIK
        'oxidpaypal_blik' => [
            'descriptions' => [
                'de' => [
                    'desc' => "BLIK (über PayPal)",
                    'longdesc' => "Bezahlen Sie bequem mit BLIK"
                ],
                'en' => [
                    'desc' => "BLIK (via PayPal)",
                    'longdesc' => "Pay conveniently with BLIK"
                ]
            ],
            'countries' => ['PL'],
            'uapmpaymentsource' => 'blik'
        ],
        // uAPM EPS
        'oxidpaypal_eps' => [
            'descriptions' => [
                'de' => [
                    'desc' => "EPS (über PayPal)",
                    'longdesc' => "Bezahlen Sie bequem mit EPS"
                ],
                'en' => [
                    'desc' => "EPS (via PayPal)",
                    'longdesc' => "Pay conveniently with EPS"
                ]
            ],
            'countries' => ['AT'],
            'uapmpaymentsource' => 'eps'
        ],
        // uAPM GiroPay
        'oxidpaypal_giropay' => [
            'descriptions' => [
                'de' => [
                    'desc' => "GiroPay (über PayPal)",
                    'longdesc' => "Bezahlen Sie bequem mit GiroPay"
                ],
                'en' => [
                    'desc' => "GiroPay (via PayPal)",
                    'longdesc' => "Pay conveniently with GiroPay"
                ]
            ],
            'countries' => ['DE'],
            'uapmpaymentsource' => 'giropay'
        ],
        // uAPM iDEAL
        'oxidpaypal_ideal' => [
            'descriptions' => [
                'de' => [
                    'desc' => "iDEAL (über PayPal)",
                    'longdesc' => "Bezahlen Sie bequem mit iDEAL"
                ],
                'en' => [
                    'desc' => "iDEAL (via PayPal)",
                    'longdesc' => "Pay conveniently with iDEAL"
                ]
            ],
            'countries' => ['NL'],
            'uapmpaymentsource' => 'ideal'
        ],
        // uAPM Multibanco
        'oxidpaypal_multibanco' => [
            'descriptions' => [
                'de' => [
                    'desc' => "Multibanco (über PayPal)",
                    'longdesc' => "Bezahlen Sie bequem mit Multibanco"
                ],
                'en' => [
                    'desc' => "Multibanco (via PayPal)",
                    'longdesc' => "Pay conveniently with Multibanco"
                ]
            ],
            'countries' => ['PT'],
            'uapmpaymentsource' => 'multibanco'
        ],
        // uAPM Multibanco
        'oxidpaypal_mybank' => [
            'descriptions' => [
                'de' => [
                    'desc' => "MyBank (über PayPal)",
                    'longdesc' => "Bezahlen Sie bequem mit MyBank"
                ],
                'en' => [
                    'desc' => "MyBank (via PayPal)",
                    'longdesc' => "Pay conveniently with MyBank"
                ]
            ],
            'countries' => ['IT'],
            'uapmpaymentsource' => 'mybank'
        ],
        // uAPM OXXO
        'oxidpaypal_oxxo' => [
            'descriptions' => [
                'de' => [
                    'desc' => "OXXO (über PayPal)",
                    'longdesc' => "Bezahlen Sie bequem mit OXXO"
                ],
                'en' => [
                    'desc' => "OXXO (via PayPal)",
                    'longdesc' => "Pay conveniently with OXXO"
                ]
            ],
            'countries' => ['MX'],
            'uapmpaymentsource' => 'oxxo'
        ],
        // uAPM Przelewy24
        'oxidpaypal_przelewy24' => [
            'descriptions' => [
                'de' => [
                    'desc' => "Przelewy24 (über PayPal)",
                    'longdesc' => "Bezahlen Sie bequem mit Przelewy24"
                ],
                'en' => [
                    'desc' => "Przelewy24 (via PayPal)",
                    'longdesc' => "Pay conveniently with Przelewy24"
                ]
            ],
            'countries' => ['PL'],
            'uapmpaymentsource' => 'p24'
        ],
        // uAPM Sofort
        'oxidpaypal_sofort' => [
            'descriptions' => [
                'de' => [
                    'desc' => "Sofort (über PayPal)",
                    'longdesc' => "Bezahlen Sie bequem mit Sofort"
                ],
                'en' => [
                    'desc' => "Sofort (via PayPal)",
                    'longdesc' => "Pay conveniently with Sofort"
                ]
            ],
            'countries' => ['DE', 'AT', 'BE', 'IT', 'NL', 'UK', 'ES'],
            'uapmpaymentsource' => 'sofort'
        ],
        // uAPM Trustly
        'oxidpaypal_trustly' => [
            'descriptions' => [
                'de' => [
                    'desc' => "Trustly (über PayPal)",
                    'longdesc' => "Bezahlen Sie bequem mit Trustly"
                ],
                'en' => [
                    'desc' => "Trustly (via PayPal)",
                    'longdesc' => "Pay conveniently with Trustly"
                ]
            ],
            'countries' => ['SE', 'FI', 'NL', 'EE'],
            'uapmpaymentsource' => 'trustly'
        ]
    ];

    public static function getPayPalDefinitions() {
        return self::PAYPAL_DEFINTIONS;
    }

    public static function isUAPMPayment(string $oxid) : bool {
        return (isset(self::PAYPAL_DEFINTIONS[$oxid]['uapmpaymentsource']));
    }

    public static function getPaymentSourceRequestName(string $oxid) : string {
        return self::isUAPMPayment($oxid) ?
            self::PAYPAL_DEFINTIONS[$oxid]['uapmpaymentsource'] :
            '';
    }
}