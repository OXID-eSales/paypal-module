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
    public const PUI_PAYPAL_PAYMENT_ID = 'oxidpaypal_pui';

    public const PAYMENT_CONSTRAINTS = [
            'oxfromamount' => 0,
            'oxtoamount' => 10000,
            'oxaddsumtype' => 'abs'
        ];

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
            'countries' => [],
            'constraints' => self::PAYMENT_CONSTRAINTS
        ],
        self::PUI_PAYPAL_PAYMENT_ID => [
            'descriptions' => [
                'de' => [
                    'desc' => "Rechnungskauf (über PayPal)",
                    'longdesc' => "Bezahlen Sie bequem mit PayPal"
                ],
                'en' => [
                    'desc' => "Pay upon Invoice (via PayPal)",
                    'longdesc' => "Pay conveniently with PayPal"
                ]
            ],
            'countries' => ['DE'],
            'constraints' => self::PAYMENT_CONSTRAINTS
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
            'countries' => ['DE'],
            'constraints' => self::PAYMENT_CONSTRAINTS
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
            'uapmpaymentsource' => 'bancontact',
            'constraints' => self::PAYMENT_CONSTRAINTS
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
            'uapmpaymentsource' => 'boletobancario',
            'constraints' => self::PAYMENT_CONSTRAINTS
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
            'uapmpaymentsource' => 'blik',
            'constraints' => self::PAYMENT_CONSTRAINTS
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
            'uapmpaymentsource' => 'eps',
            'constraints' => self::PAYMENT_CONSTRAINTS
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
            'uapmpaymentsource' => 'giropay',
            'constraints' => self::PAYMENT_CONSTRAINTS
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
            'uapmpaymentsource' => 'ideal',
            'constraints' => self::PAYMENT_CONSTRAINTS
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
            'uapmpaymentsource' => 'multibanco',
            'constraints' => self::PAYMENT_CONSTRAINTS
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
            'uapmpaymentsource' => 'mybank',
            'constraints' => self::PAYMENT_CONSTRAINTS
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
            'uapmpaymentsource' => 'oxxo',
            'constraints' => self::PAYMENT_CONSTRAINTS
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
            'uapmpaymentsource' => 'p24',
            'constraints' => self::PAYMENT_CONSTRAINTS
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
            'uapmpaymentsource' => 'sofort',
            'constraints' => self::PAYMENT_CONSTRAINTS
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
            'uapmpaymentsource' => 'trustly',
            'constraints' => self::PAYMENT_CONSTRAINTS
        ]
    ];

    private const PAYPAL_STATIC_CONTENTS = [
        'oscpaypalpuiconfirmation' =>
        [
            'oxloadid' => 'oscpaypalpuiconfirmation',
            'oxactive' => 1,
            'oxtitle_de' => 'Rechnungskauf Einverständniserklärung',
            'oxtitle_en' => 'Pay upon Invoice letter of acceptance',
            'oxcontent_de' => 'Mit Klicken auf den Button akzeptieren Sie die Ratepay Zahlungsbedingungen und erklären sich mit der Durchführung einer
                Risikoprüfung durch Ratepay, unseren Partner, einverstanden. Sie akzeptieren auch PayPals Datenschutzerklärung. Falls Ihre Transaktion
                per Kauf auf Rechnung erfolgreich abgewickelt werden kann, wird der Kaufpreis an Ratepay abgetreten und Sie dürfen nur an Ratepay
                überweisen, nicht an den Händler.',
            'oxcontent_en' => ' By clicking on the button, you agree to the terms of payment and performance of a risk check from the payment partner,
                Ratepay. You also agree to PayPal’s privacy statement. If your request to purchase upon invoice is accepted, the purchase price claim will be
                assigned to Ratepay, and you may only pay Ratepay, not the merchant.'
        ]
    ];

    public static function getPayPalDefinitions() {
        return self::PAYPAL_DEFINTIONS;
    }

    public static function getPayPalStaticContents() {
        return self::PAYPAL_STATIC_CONTENTS;
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