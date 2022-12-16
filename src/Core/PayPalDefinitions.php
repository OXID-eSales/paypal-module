<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Core;

final class PayPalDefinitions
{
    const STANDARD_PAYPAL_PAYMENT_ID = 'oscpaypal';
    const PAYLATER_PAYPAL_PAYMENT_ID = 'oscpaypal_paylater';
    const EXPRESS_PAYPAL_PAYMENT_ID = 'oscpaypal_express';
    const ACDC_PAYPAL_PAYMENT_ID = 'oscpaypal_acdc';
    const PUI_PAYPAL_PAYMENT_ID = 'oscpaypal_pui';
    const PUI_REQUEST_PAYMENT_SOURCE_NAME = 'pay_upon_invoice';
    const GIROPAY_PAYPAL_PAYMENT_ID = 'oscpaypal_giropay';
    const SOFORT_PAYPAL_PAYMENT_ID = 'oscpaypal_sofort';


    const PAYMENT_CONSTRAINTS_PAYPAL = [
        'oxfromamount' => 0.01,
        'oxtoamount' => 60000,
        'oxaddsumtype' => 'abs'
    ];

    const PAYMENT_CONSTRAINTS_UAPM = [
        'oxfromamount' => 1,
        'oxtoamount' => 10000,
        'oxaddsumtype' => 'abs'
    ];

    const PAYMENT_CONSTRAINTS_PUI = [
        'oxfromamount' => 5,
        'oxtoamount' => 1500,
        'oxaddsumtype' => 'abs'
    ];

    const PAYPAL_DEFINTIONS = [
        //Standard PayPal
        self::STANDARD_PAYPAL_PAYMENT_ID => [
            'descriptions' => [
                'de' => [
                    'desc' => 'PayPal',
                    'longdesc' => '',
                    'longdesc_beta' => 'Bezahlen Sie bequem mit PayPal.'
                ],
                'en' => [
                    'desc' => 'PayPal',
                    'longdesc' => '',
                    'longdesc_beta' => 'Pay conveniently with PayPal.'
                ]
            ],
            'countries' => [],
            'currencies' => ['AUD', 'BRL', 'CAD', 'CNY', 'CZK', 'DKK', 'EUR', 'HKD', 'HUF', 'ILS', 'JPY', 'MYR', 'MXN', 'TWD', 'NZD', 'NOK', 'PHP', 'PLN', 'GBP', 'RUB', 'SGD', 'SEK', 'CHF', 'THB', 'USD'],
            'constraints' => self::PAYMENT_CONSTRAINTS_PAYPAL,
            'onlybrutto' => false
        ],
        //Paylater PayPal
        self::PAYLATER_PAYPAL_PAYMENT_ID => [
            'descriptions' => [
                'de' => [
                    'desc' => 'PayPal - später bezahlen',
                    'longdesc' => '',
                    'longdesc_beta' => 'Kaufen Sie jetzt und bezahlen sie später mit PayPal.'
                ],
                'en' => [
                    'desc' => 'PayPal- pay later',
                    'longdesc' => '',
                    'longdesc_beta' => 'Buy now and pay later with PayPal.'
                ]
            ],
            'countries' => ['DE', 'ES', 'FR', 'GB', 'IT', 'US', 'AU'],
            'currencies' => ['AUD', 'EUR', 'GBP', 'USD'],
            'constraints' => self::PAYMENT_CONSTRAINTS_PAYPAL,
            'onlybrutto' => false
        ],
        //Express PayPal
        self::EXPRESS_PAYPAL_PAYMENT_ID => [
            'descriptions' => [
                'de' => [
                    'desc' => 'PayPal Express',
                    'longdesc' => '',
                    'longdesc_beta' => 'Bezahlen Sie bequem mit PayPal. Starten Sie direkt von der Detailsseite oder im Warenkorb.'
                ],
                'en' => [
                    'desc' => 'PayPal Express',
                    'longdesc' => '',
                    'longdesc_beta' => 'Pay conveniently with PayPal. Start directly from the details page or in the shopping cart.'
                ]
            ],
            'countries' => [],
            'currencies' => ['AUD', 'BRL', 'CAD', 'CNY', 'CZK', 'DKK', 'EUR', 'HKD', 'HUF', 'ILS', 'JPY', 'MYR', 'MXN', 'TWD', 'NZD', 'NOK', 'PHP', 'PLN', 'GBP', 'RUB', 'SGD', 'SEK', 'CHF', 'THB', 'USD'],
            'constraints' => self::PAYMENT_CONSTRAINTS_PAYPAL,
            'onlybrutto' => false
        ],
        self::PUI_PAYPAL_PAYMENT_ID => [
            'descriptions' => [
                'de' => [
                    'desc' => 'Rechnungskauf',
                    'longdesc' => '',
                    'longdesc_beta' => '<img src="" title="Rechnungskauf" style="float: left;margin-right: 10px;" />
                        Bezahlen Sie bequem mit PayPal'
                ],
                'en' => [
                    'desc' => 'Pay upon Invoice',
                    'longdesc' => '',
                    'longdesc_beta' => '<img src="" title="Pay upon Invoice" style="float: left;margin-right: 10px;" />
                        Pay conveniently with PayPal'
                ]
            ],
            'countries' => ['DE'],
            'currencies' => ['EUR'],
            'constraints' => self::PAYMENT_CONSTRAINTS_PUI,
            'onlybrutto' => true
        ],
        self::ACDC_PAYPAL_PAYMENT_ID => [
            'descriptions' => [
                'de' => [
                    'desc' => 'Kreditkarte',
                    'longdesc' => '',
                    'longdesc_beta' => '<img src="" title="Kreditkarte" style="float: left;margin-right: 10px;" />
                        Bezahlen Sie bequem mit Kreditkarte'
                ],
                'en' => [
                    'desc' => 'Creditcard',
                    'longdesc' => '',
                    'longdesc_beta' => '<img src="" title="Creditcard" style="float: left;margin-right: 10px;" />
                        Pay conveniently with Creditcard'
                ]
            ],
            'countries' => [],
            'currencies' => ['AUD', 'CAD', 'CHF', 'CZK', 'DKK', 'EUR', 'GBP', 'HKD', 'HUF', 'JPY', 'NOK', 'NZD', 'PLN', 'SEK', 'SGD', 'USD'],
            'constraints' => self::PAYMENT_CONSTRAINTS_PAYPAL,
            'onlybrutto' => false
        ],
        // uAPM Bancontact
        'oscpaypal_bancontact' => [
            'descriptions' => [
                'de' => [
                    'desc' => 'Bancontact',
                    'longdesc' => '',
                    'longdesc_beta' => '<img src="https://www.paypalobjects.com/images/checkout/alternative_payments/paypal_bancontact_color.svg" title="Bancontact" style="float: left;margin-right: 10px;" />
                        Bancontact ist die am weitesten verbreitete, akzeptierte und vertrauenswürdigste
                        elektronische Zahlung Methode in Belgien, mit über 15 Millionen ausgegebenen Bancontact-Karten
                        und 150.000 verarbeiteten online-Transaktionen pro Tag. Bancontact ermöglicht es, direkt durch
                        die Online-Zahlungssysteme aller großen belgischen Banken  zu bezahlen und kann von allen Kunden
                        mit einer Zahlungskarte der Marke Bancontact genutzt werden. Bancontact-Karten werden von mehr
                        als 20 belgische Banken ausgestellt und existiert ausschließlich in Belgien.'
                ],
                'en' => [
                    'desc' => 'Bancontact',
                    'longdesc' => '',
                    'longdesc_beta' => '<img src="https://www.paypalobjects.com/images/checkout/alternative_payments/paypal_bancontact_color.svg" title="Bancontact" style="float: left;margin-right: 10px;" />
                        Bancontact is the most widely used, accepted and trusted electronic payment
                        method in Belgium, with over 15 million Bancontact cards issued, and 150,000 online
                        transactions processed a day. Bancontact makes it possible to pay directly through
                        the online payment systems of all major Belgian banks and can be used by all customers
                        with a Bancontact branded payment card. Bancontact cards are issued by more than
                        20 Belgian banks and exists solely in Belgium.'
                ]
            ],
            'countries' => ['BE'],
            'currencies' => ['EUR'],
            'uapmpaymentsource' => 'bancontact',
            'constraints' => self::PAYMENT_CONSTRAINTS_UAPM,
            'onlybrutto' => false
        ],
        // uAPM BLIK
        'oscpaypal_blik' => [
            'descriptions' => [
                'de' => [
                    'desc' => 'BLIK',
                    'longdesc' => '',
                    'longdesc_beta' => '<img src="https://www.paypalobjects.com/images/checkout/alternative_payments/paypal_blik_color.svg" title="BLIK" style="float: left;margin-right: 10px;" />
                        BLIK wurde 2015 in Polen gegründet, eingeführt und entwickelt und ist ein Muss für
                        als Zahlungsmethode für E-Commerce-Sites in Polen. BLIK ist ein mobiler Zahlungsdienst mit einem
                        Alleinstellungsmerkmal in Form eines Kooperationsmodells zwischen Banken, Acquirern und Händlern.
                        BLIK steht für Smartphone-Nutzer zur Verfügung, die Mobile-Banking-Apps von teilnehmenden Banken
                        installiert haben. Der Verbraucher stellt seinen BLIK-Code (6 Ziffern) bereit, um sich im BLIK-
                        System zu authentifizieren, ohne seine sensiblen personenbezogenen Daten preiszugeben. BLIK
                        OneClick ist eine nahtlose Flow-Integration, die eine großartiges Verbrauchererlebnis bietet.'
                ],
                'en' => [
                    'desc' => 'BLIK',
                    'longdesc' => '',
                    'longdesc_beta' => '<img src="https://www.paypalobjects.com/images/checkout/alternative_payments/paypal_blik_color.svg" title="BLIK" style="float: left;margin-right: 10px;" />
                        Introduced in 2015, and created and developed in Poland, BLIK is a must-have local
                        payment method for e-commerce sites in Poland. BLIK is a mobile payment service with a unique
                        model of cooperation between banks, payment acquirers and merchants. BLIK is available to
                        smartphone users who have installed mobile banking apps from participating banks. The consumer
                        provides their BLIK code (6 digits) to authenticate in the BLIK system, without giving away any
                        sensitive personal data. BLIK OneClick is a seamless flow integration that offers a great
                        consumer experience.'
                ]
            ],
            'countries' => ['PL'],
            'currencies' => ['PLN'],
            'uapmpaymentsource' => 'blik',
            'constraints' => self::PAYMENT_CONSTRAINTS_UAPM,
            'onlybrutto' => false
        ],
        // uAPM EPS
        'oscpaypal_eps' => [
            'descriptions' => [
                'de' => [
                    'desc' => 'EPS',
                    'longdesc' => '',
                    'longdesc_beta' => '<img src="https://www.paypalobjects.com/images/checkout/alternative_payments/paypal_eps_color.svg" title="eps" style="float: left;margin-right: 10px;" />
                        eps ist die wichtigste Zahlungsmethode für Banküberweisungen in Österreich,
                        entwickelt von österreichischen Banken: über 80 % aller Online-Händler in Österreich bieten
                        ihren Kunden eps an. 83% der Österreicher kaufen grenzüberschreitend ein. eps ist eine
                        wichtige Zahlungsmethode für die Zahlung eines Händlers in Europa. Jede Transaktion
                        wird von eps garantiert.'
                ],
                'en' => [
                    'desc' => 'EPS',
                    'longdesc' => '',
                    'longdesc_beta' => '<img src="https://www.paypalobjects.com/images/checkout/alternative_payments/paypal_eps_color.svg" title="eps" style="float: left;margin-right: 10px;" />
                        eps is the main bank transfer payment method in Austria, built by Austrian banks:
                        more than 80% of all online merchants in Austria offer eps to their customers. With 83% of
                        Austrians shopping cross border, eps is a critical payment method for a merchant’s payment
                        method mix in Europe. Every transaction is guaranteed by eps.'
                ]
            ],
            'countries' => ['AT'],
            'currencies' => ['EUR'],
            'uapmpaymentsource' => 'eps',
            'constraints' => self::PAYMENT_CONSTRAINTS_UAPM,
            'onlybrutto' => false
        ],
        // uAPM GiroPay
        self::GIROPAY_PAYPAL_PAYMENT_ID => [
            'descriptions' => [
                'de' => [
                    'desc' => 'GiroPay',
                    'longdesc' => '',
                    'longdesc_beta' => '<img src="https://www.paypalobjects.com/images/checkout/alternative_payments/paypal_giropay_color.svg" title="Giropay" style="float: left;margin-right: 10px;" />
                         Mit Giropay nutzen Verbraucher das sichere Online-Banking ihrer Bank:
                         Ihre Bankkontoinformationen und Transaktionsdetails bleiben vollständig geschützt und sicher.
                         Beim Auschecken können Verbraucher Giropay direkt ohne zusätzliche Registrierung nutzen.
                         Giropay unterstützt fast alle Banken in Deutschland, und es gibt über 45 Millionen Online-Banking
                         Kunden, die Giropay verwenden (54 % der Gesamtbevölkerung).'
                ],
                'en' => [
                    'desc' => 'GiroPay',
                    'longdesc' => '',
                    'longdesc_beta' => '<img src="https://www.paypalobjects.com/images/checkout/alternative_payments/paypal_giropay_color.svg" title="Giropay" style="float: left;margin-right: 10px;" />
                        With Giropay, consumers are using their bank’s secure online banking:
                        their bank account information and transaction details remain fully protected and secure.
                        When consumers check out, they can use Giropay directly without any additional registration.
                        Giropay supports nearly all banks in Germany, and there are over 45 million online banking
                        customers who use Giropay (54% overall population).'
                ]
            ],
            'countries' => ['DE'],
            'currencies' => ['EUR'],
            'uapmpaymentsource' => 'giropay',
            'constraints' => self::PAYMENT_CONSTRAINTS_UAPM,
            'onlybrutto' => false
        ],
        // uAPM iDEAL
        'oscpaypal_ideal' => [
            'descriptions' => [
                'de' => [
                    'desc' => 'iDEAL',
                    'longdesc' => '',
                    'longdesc_beta' => '<img src="https://www.paypalobjects.com/images/checkout/alternative_payments/paypal_ideal_color.svg" title="iDEAL" style="float: left;margin-right: 10px;" />
                        iDEAL ist eine der beliebtesten Zahlungsmethoden in den Niederlanden.
                         60% aller Online-Transaktionen und 667 Millionen Zahlungen im Jahr 2019 wurden
                         mit iDEAL abgewickelt. iDEAL ermöglicht es seinene Kunden, über die mobile App
                         oder online via Kundenkonto auf der Banking-Website zu bezahlen. Das System bietet
                         den Verbrauchern die Geschwindigkeit und die Vorteile der Bereitstellung
                         vorausgefüllte Zahlungsinformationen, da die Methode nahtlos in die Online-Banking-Systeme
                         integriert ist, die von allen Banken angeboten werden.'
                ],
                'en' => [
                    'desc' => 'iDEAL',
                    'longdesc' => '',
                    'longdesc_beta' => '<img src="https://www.paypalobjects.com/images/checkout/alternative_payments/paypal_ideal_color.svg" title="iDEAL" style="float: left;margin-right: 10px;" />
                        iDEAL is one of the most popular payment method in the Netherlands,
                        accounting for 60% of all online transactions and 667 million payments in 2019.
                        iDEAL allows customers to pay via it’s mobile app or within the customers’ online
                        banking website. The system offers consumers the speed and advantages of providing
                        pre-filled payment information, as the method is seamlessly integrated with the
                        online banking systems offered by all banks.'
                ]
            ],
            'countries' => ['NL'],
            'currencies' => ['EUR'],
            'uapmpaymentsource' => 'ideal',
            'constraints' => self::PAYMENT_CONSTRAINTS_UAPM,
            'onlybrutto' => false
        ],
        // uAPM MyBank
        'oscpaypal_mybank' => [
            'descriptions' => [
                'de' => [
                    'desc' => 'MyBank',
                    'longdesc' => '',
                    'longdesc_beta' => '<img src="https://www.paypalobjects.com/images/checkout/alternative_payments/paypal_mybank_color.svg" title="MyBank" style="float: left;margin-right: 10px;" />
                        MyBank bietet sichere und geschützte Banküberweisungen in Echtzeit zwischen Kunden
                        und Kaufleute. MyBank ist auf einer Vielzahl von Websites für den Kauf von Produkten verfügbar,
                        Dienstleistungen und für Zahlungen an Einrichtungen der öffentlichen Verwaltung. MyBank
                        ist eine flexibele Lösung für B2B- und B2C-Unternehmen: 40 Millionen aktivierte Kunden und über
                        210 Banken und PSPs haben sich dem MyBank-Netzwerk in ganz Europa angeschlossen.'
                ],
                'en' => [
                    'desc' => 'MyBank',
                    'longdesc' => '',
                    'longdesc_beta' => '<img src="https://www.paypalobjects.com/images/checkout/alternative_payments/paypal_mybank_color.svg" title="MyBank" style="float: left;margin-right: 10px;" />
                        MyBank offers safe and protected real-time bank transfers between customers
                        and merchants. MyBank is available on a wide range of sites for the purchase of products,
                        services and for payments towards public administration entities. MyBank is a flexible
                        solution for both B2B and B2C businesses: 40 million enabled customers and over 210 banks
                        and PSPs have joined the MyBank network throughout Europe.'
                ]
            ],
            'countries' => ['IT'],
            'currencies' => ['EUR'],
            'uapmpaymentsource' => 'mybank',
            'constraints' => self::PAYMENT_CONSTRAINTS_UAPM,
            'onlybrutto' => false
        ],
        // uAPM Przelewy24
        'oscpaypal_przelewy24' => [
            'descriptions' => [
                'de' => [
                    'desc' => 'Przelewy24',
                    'longdesc' => '',
                    'longdesc_beta' => '<img src="https://www.paypalobjects.com/images/checkout/alternative_payments/paypal_przelewy24_color.svg" title="Przelewy24" style="float: left;margin-right: 10px;" />
                        Przelewy24, oft auch als P24 bezeichnet, ist eine der wichtigsten Online-Zahlungs-
                        Systeme in Polen. Mehr als 80 % der Online-Shopper in Polen bezahlen über Przelewy24 und
                        fast 10.000 online Händler bieten diese Zahlungsmethode in ihren Online-Shops an.'
                ],
                'en' => [
                    'desc' => 'Przelewy24',
                    'longdesc' => '',
                    'longdesc_beta' => '<img src="https://www.paypalobjects.com/images/checkout/alternative_payments/paypal_przelewy24_color.svg" title="Przelewy24" style="float: left;margin-right: 10px;" />
                        Przelewy24, often referred to as P24, is one of Poland’s primary online payment
                        systems. More than 80% of online shoppers in Poland pay via Przelewy24 and almost 10,000 online
                        merchants offer this payment method in their online shops.'
                ]
            ],
            'countries' => ['PL'],
            'currencies' => ['EUR', 'PLN'],
            'uapmpaymentsource' => 'p24',
            'constraints' => self::PAYMENT_CONSTRAINTS_UAPM,
            'onlybrutto' => false
        ],
        // uAPM Sofort
        self::SOFORT_PAYPAL_PAYMENT_ID => [
            'descriptions' => [
                'de' => [
                    'desc' => 'Sofort',
                    'longdesc' => '',
                    'longdesc_beta' => '<img src="https://www.paypalobjects.com/images/checkout/alternative_payments/paypal_sofort_black.svg" title="Sofort" style="float: left;margin-right: 10px;" />
                        Sofort, auch bekannt als Jetzt bezahlen mit Klarna, ist eine beliebte Online-
                        Banking-Methode in Deutschland, Österreich, der Schweiz und Belgien, was es zu einem Muss für
                        jedes Unternehmen macht, das in diesem Bereich tätig sein will. Im Jahr 2014 wurde Sofort von
                        Klarna und der Klarna Group übernommen. Bei Sofort nutzen sicherheitsbewusste Verbraucher ihre
                        eigenen Online-Banking-Daten, über die sichere Zahlungsseite von Sofort eingegeben. Einmal
                        eingeloggt, wird ein einmaliger Bestätigungs-Code vom Verbraucher verwendet, um die Zahlung zu
                        autorisieren.'
                ],
                'en' => [
                    'desc' => 'Sofort',
                    'longdesc' => '',
                    'longdesc_beta' => '<img src="https://www.paypalobjects.com/images/checkout/alternative_payments/paypal_sofort_black.svg" title="Sofort" style="float: left;margin-right: 10px;" />
                        Sofort, also known as Pay now with Klarna, is a popular online banking method
                        in Germany, Austria, Switzerland and Belgium, making it a must-have for any business wanting
                        to operate in this area. In 2014 Sofort was acquired by Klarna and the Klarna Group was
                        established. With Sofort, security-conscious consumers use their own online banking details,
                        entered through the secure payment page of Sofort. Once logged in, a one-time confirmation
                        code is used by the consumer to authorize payment.'
                ]
            ],
            'countries' => ['DE', 'AT', 'BE', 'IT', 'NL', 'GB', 'ES'],
            'currencies' => ['EUR', 'GBP'],
            'uapmpaymentsource' => 'sofort',
            'constraints' => self::PAYMENT_CONSTRAINTS_UAPM,
            'onlybrutto' => false
        ],
    ];

    const PAYPAL_STATIC_CONTENTS = [
        'oscpaypalpuiconfirmation' =>
        [
            'oxloadid' => 'oscpaypalpuiconfirmation',
            'oxactive' => 1,
            'oxtitle_de' => 'Rechnungskauf Einverständniserklärung',
            'oxtitle_en' => 'Pay upon Invoice letter of acceptance',
            'oxcontent_de' => 'Mit Klicken auf den "Zahlungspflichtig bestellen" - Button akzeptieren Sie die
                <a href="https://www.ratepay.com/legal-payment-terms" target="_blank">Ratepay Zahlungsbedingungen</a>
                und erklären sich mit der Durchführung einer
                <a href="https://www.ratepay.com/legal-payment-dataprivacy" target="_blank">Risikoprüfung durch Ratepay</a>,
                unseren Partner, einverstanden. Sie akzeptieren auch PayPals
                <a href="https://www.paypal.com/de/webapps/mpp/ua/rechnungskauf-mit-ratepay?locale.x=de_DE"
                target="_blank">Datenschutzerklärung</a>. Falls Ihre Transaktion per Kauf auf Rechnung erfolgreich abgewickelt
                werden kann, wird der Kaufpreis an Ratepay abgetreten und Sie dürfen nur an Ratepay überweisen, nicht an den Händler.',
            'oxcontent_en' => ' By clicking on the "Order now" - button, you agree to the
                <a href="https://www.ratepay.com/en/ratepay-terms-of-payment/" target="_blank">terms of payment</a>
                and performance of a <a href="https://www.ratepay.com/en/ratepay-data-privacy-statement/" target="_blank">risk check</a>
                from the payment partner, Ratepay. You also agree to PayPal’s
                <a href="https://www.paypal.com/de/webapps/mpp/ua/rechnungskauf-mit-ratepay?locale.x=en_EN"
                target="_blank">privacy statement</a>. If your request to purchase upon invoice is accepted, the purchase price claim
                will be assigned to Ratepay, and you may only pay Ratepay, not the merchant.'
        ]
    ];

    public static function getPayPalDefinitions()
    {
        return self::PAYPAL_DEFINTIONS;
    }

    public static function getPayPalStaticContents()
    {
        return self::PAYPAL_STATIC_CONTENTS;
    }

    public static function isUAPMPayment(string $oxid): bool
    {
        return (isset(self::PAYPAL_DEFINTIONS[$oxid]['uapmpaymentsource']));
    }

    public static function getPaymentSourceRequestName(string $oxid): string
    {
        return self::isUAPMPayment($oxid) ?
            self::PAYPAL_DEFINTIONS[$oxid]['uapmpaymentsource'] :
            '';
    }

    public static function isPayPalPayment(string $paymentId): bool
    {
        return (isset(self::PAYPAL_DEFINTIONS[$paymentId]));
    }
}
