<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Core;

final class PayPalDefinitions
{
    public const STANDARD_PAYPAL_PAYMENT_ID = 'oscpaypal';
    public const ACDC_PAYPAL_PAYMENT_ID = 'oscpaypal_acdc';
    public const PUI_PAYPAL_PAYMENT_ID = 'oscpaypal_pui';
    public const PUI_REQUEST_PAYMENT_SOURCE_NAME = 'pay_upon_invoice';

    private const PAYMENT_CONSTRAINTS = [
        'oxfromamount' => 0,
        'oxtoamount' => 10000,
        'oxaddsumtype' => 'abs'
    ];

    private const PAYPAL_DEFINTIONS = [
        //Standard PayPal
        self::STANDARD_PAYPAL_PAYMENT_ID => [
            'descriptions' => [
                'de' => [
                    'desc' => 'PayPal v2',
                    'longdesc' => 'Bezahlen Sie bequem mit PayPal'
                ],
                'en' => [
                    'desc' => 'PayPal v2',
                    'longdesc' => 'Pay conveniently with PayPal'
                ]
            ],
            'countries' => [],
            'constraints' => self::PAYMENT_CONSTRAINTS
        ],
        self::PUI_PAYPAL_PAYMENT_ID => [
            'descriptions' => [
                'de' => [
                    'desc' => 'Rechnungskauf',
                    'longdesc' => '<img src="" title="Rechnungskauf" style="float: left;margin-right: 10px;" />
                        Bezahlen Sie bequem mit PayPal'
                ],
                'en' => [
                    'desc' => 'Pay upon Invoice',
                    'longdesc' => '<img src="" title="Pay upon Invoice" style="float: left;margin-right: 10px;" />
                        Pay conveniently with PayPal'
                ]
            ],
            'countries' => ['DE'],
            'constraints' => self::PAYMENT_CONSTRAINTS
        ],
        self::ACDC_PAYPAL_PAYMENT_ID => [
            'descriptions' => [
                'de' => [
                    'desc' => 'Kreditkarte',
                    'longdesc' => '<img src="" title="Kreditkarte" style="float: left;margin-right: 10px;" />
                        Bezahlen Sie bequem mit Kreditkarte'
                ],
                'en' => [
                    'desc' => 'Creditcard',
                    'longdesc' => '<img src="" title="Creditcard" style="float: left;margin-right: 10px;" />
                        Pay conveniently with Creditcard'
                ]
            ],
            'countries' => ['DE'],
            'constraints' => self::PAYMENT_CONSTRAINTS
        ],
        // uAPM Bancontact
        'oscpaypal_bancontact' => [
            'descriptions' => [
                'de' => [
                    'desc' => 'Bancontact',
                    'longdesc' => '<img src="https://www.paypalobjects.com/images/checkout/alternative_payments/paypal_bancontact_color.svg" title="Bancontact" style="float: left;margin-right: 10px;" />
                        Bancontact ist die am weitesten verbreitete, akzeptierte und vertrauenswürdigste
                        elektronische Zahlung Methode in Belgien, mit über 15 Millionen ausgegebenen Bancontact-Karten
                        und 150.000 verarbeiteten online-Transaktionen pro Tag. Bancontact ermöglicht es, direkt durch
                        die Online-Zahlungssysteme aller großen belgischen Banken  zu bezahlen und kann von allen Kunden
                        mit einer Zahlungskarte der Marke Bancontact genutzt werden. Bancontact-Karten werden von mehr
                        als 20 belgische Banken ausgestellt und existiert ausschließlich in Belgien.'
                ],
                'en' => [
                    'desc' => 'Bancontact',
                    'longdesc' => '<img src="https://www.paypalobjects.com/images/checkout/alternative_payments/paypal_bancontact_color.svg" title="Bancontact" style="float: left;margin-right: 10px;" />
                        Bancontact is the most widely used, accepted and trusted electronic payment
                        method in Belgium, with over 15 million Bancontact cards issued, and 150,000 online
                        transactions processed a day. Bancontact makes it possible to pay directly through
                        the online payment systems of all major Belgian banks and can be used by all customers
                        with a Bancontact branded payment card. Bancontact cards are issued by more than
                        20 Belgian banks and exists solely in Belgium.'
                ]
            ],
            'countries' => ['BE'],
            'uapmpaymentsource' => 'bancontact',
            'constraints' => self::PAYMENT_CONSTRAINTS
        ],
        // uAPM Boleto Bancário
        'oscpaypal_boleto' => [
            'descriptions' => [
                'de' => [
                    'desc' => 'Boleto Bancário',
                    'longdesc' => '<img src="https://www.paypalobjects.com/images/checkout/alternative_payments/paypal_boleto_black.svg" title="Boleto Bancário" style="float: left;margin-right: 10px;" />
                        Bezahlen Sie bequem mit Boleto Bancário'
                ],
                'en' => [
                    'desc' => 'Boleto Bancário',
                    'longdesc' => '<img src="https://www.paypalobjects.com/images/checkout/alternative_payments/paypal_boleto_black.svg" title="Boleto Bancário" style="float: left;margin-right: 10px;" />
                        Pay conveniently with Boleto Bancário'
                ]
            ],
            'countries' => ['BR'],
            'uapmpaymentsource' => 'boletobancario',
            'constraints' => self::PAYMENT_CONSTRAINTS
        ],
        // uAPM BLIK
        'oscpaypal_blik' => [
            'descriptions' => [
                'de' => [
                    'desc' => 'BLIK',
                    'longdesc' => '<img src="https://www.paypalobjects.com/images/checkout/alternative_payments/paypal_blik_color.svg" title="BLIK" style="float: left;margin-right: 10px;" />
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
                    'longdesc' => '<img src="https://www.paypalobjects.com/images/checkout/alternative_payments/paypal_blik_color.svg" title="BLIK" style="float: left;margin-right: 10px;" />
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
            'uapmpaymentsource' => 'blik',
            'constraints' => self::PAYMENT_CONSTRAINTS
        ],
        // uAPM EPS
        'oscpaypal_eps' => [
            'descriptions' => [
                'de' => [
                    'desc' => 'EPS',
                    'longdesc' => '<img src="https://www.paypalobjects.com/images/checkout/alternative_payments/paypal_eps_color.svg" title="eps" style="float: left;margin-right: 10px;" />
                        eps ist die wichtigste Zahlungsmethode für Banküberweisungen in Österreich,
                        entwickelt von österreichischen Banken: über 80 % aller Online-Händler in Österreich bieten
                        ihren Kunden eps an. 83% der Österreicher kaufen grenzüberschreitend ein. eps ist eine
                        wichtige Zahlungsmethode für die Zahlung eines Händlers in Europa. Jede Transaktion
                        wird von eps garantiert.'
                ],
                'en' => [
                    'desc' => 'EPS',
                    'longdesc' => '<img src="https://www.paypalobjects.com/images/checkout/alternative_payments/paypal_eps_color.svg" title="eps" style="float: left;margin-right: 10px;" />
                        eps is the main bank transfer payment method in Austria, built by Austrian banks:
                        more than 80% of all online merchants in Austria offer eps to their customers. With 83% of
                        Austrians shopping cross border, eps is a critical payment method for a merchant’s payment
                        method mix in Europe. Every transaction is guaranteed by eps.'
                ]
            ],
            'countries' => ['AT'],
            'uapmpaymentsource' => 'eps',
            'constraints' => self::PAYMENT_CONSTRAINTS
        ],
        // uAPM GiroPay
        'oscpaypal_giropay' => [
            'descriptions' => [
                'de' => [
                    'desc' => 'GiroPay',
                    'longdesc' => '<img src="https://www.paypalobjects.com/images/checkout/alternative_payments/paypal_giropay_color.svg" title="Giropay" style="float: left;margin-right: 10px;" />
                         Mit Giropay nutzen Verbraucher das sichere Online-Banking ihrer Bank:
                         Ihre Bankkontoinformationen und Transaktionsdetails bleiben vollständig geschützt und sicher.
                         Beim Auschecken können Verbraucher Giropay direkt ohne zusätzliche Registrierung nutzen.
                         Giropay unterstützt fast alle Banken in Deutschland, und es gibt über 45 Millionen Online-Banking
                         Kunden, die Giropay verwenden (54 % der Gesamtbevölkerung).'
                ],
                'en' => [
                    'desc' => 'GiroPay',
                    'longdesc' => '<img src="https://www.paypalobjects.com/images/checkout/alternative_payments/paypal_giropay_color.svg" title="Giropay" style="float: left;margin-right: 10px;" />
                        With Giropay, consumers are using their bank’s secure online banking:
                        their bank account information and transaction details remain fully protected and secure.
                        When consumers check out, they can use Giropay directly without any additional registration.
                        Giropay supports nearly all banks in Germany, and there are over 45 million online banking
                        customers who use Giropay (54% overall population).'
                ]
            ],
            'countries' => ['DE'],
            'uapmpaymentsource' => 'giropay',
            'constraints' => self::PAYMENT_CONSTRAINTS
        ],
        // uAPM iDEAL
        'oscpaypal_ideal' => [
            'descriptions' => [
                'de' => [
                    'desc' => 'iDEAL',
                    'longdesc' => '<img src="https://www.paypalobjects.com/images/checkout/alternative_payments/paypal_ideal_color.svg" title="iDEAL" style="float: left;margin-right: 10px;" />
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
                    'longdesc' => '<img src="https://www.paypalobjects.com/images/checkout/alternative_payments/paypal_ideal_color.svg" title="iDEAL" style="float: left;margin-right: 10px;" />
                        iDEAL is one of the most popular payment method in the Netherlands,
                        accounting for 60% of all online transactions and 667 million payments in 2019.
                        iDEAL allows customers to pay via it’s mobile app or within the customers’ online
                        banking website. The system offers consumers the speed and advantages of providing
                        pre-filled payment information, as the method is seamlessly integrated with the
                        online banking systems offered by all banks.'
                ]
            ],
            'countries' => ['NL'],
            'uapmpaymentsource' => 'ideal',
            'constraints' => self::PAYMENT_CONSTRAINTS
        ],
        // uAPM Multibanco
        'oscpaypal_multibanco' => [
            'descriptions' => [
                'de' => [
                    'desc' => 'Multibanco',
                    'longdesc' => '<img src="https://www.paypalobjects.com/images/checkout/alternative_payments/paypal_multibanco_color.svg" title="Multibanco" style="float: left;margin-right: 10px;" />
                        Im Besitz und betrieben von SIBS (Sociedade Interbancária de Serviços S.A.),
                         ermöglicht Multibanco den Verbrauchern, per Banküberweisung oder mit einer Multibanco-Karte an
                         einem Geldautomaten zu bezahlen. Multibanco ist eine zentrale Zahlungsmethode für portugiesische
                         Verbraucher und ist an 11.000 Automaten im ganzen Land an strategischen Orten wie Supermärkten,
                         Flughäfen, Museen, Bahnhöfe, Banken und Einkaufszentren verfügbar. Verbraucher können Multibanco
                         nutzen um für eine Reihe von Waren/Dienstleistungen zu bezahlen, einschließlich E-Commerce,
                         Lizenzen und Steuern.'
                ],
                'en' => [
                    'desc' => 'Multibanco',
                    'longdesc' => '<img src="https://www.paypalobjects.com/images/checkout/alternative_payments/paypal_multibanco_color.svg" title="Multibanco" style="float: left;margin-right: 10px;" />
                        Owned and operated by SIBS (Sociedade Interbancária de Serviços S.A.),
                        Multibanco enables consumers to pay by bank transfer or with a Multibanco card at an ATM.
                        Multibanco is a core payment method for Portuguese consumers and is available at over
                        11,000 machines spread across the country in strategic spots such as supermarkets,
                        airports, museums, stations, banks, and shopping centers. Consumers can use Multibanco
                        to pay for a range of goods/services including e-commerce, licenses, and taxes.'
                ]
            ],
            'countries' => ['PT'],
            'uapmpaymentsource' => 'multibanco',
            'constraints' => self::PAYMENT_CONSTRAINTS
        ],
        // uAPM Multibanco
        'oscpaypal_mybank' => [
            'descriptions' => [
                'de' => [
                    'desc' => 'MyBank',
                    'longdesc' => '<img src="https://www.paypalobjects.com/images/checkout/alternative_payments/paypal_mybank_color.svg" title="MyBank" style="float: left;margin-right: 10px;" />
                        MyBank bietet sichere und geschützte Banküberweisungen in Echtzeit zwischen Kunden
                        und Kaufleute. MyBank ist auf einer Vielzahl von Websites für den Kauf von Produkten verfügbar,
                        Dienstleistungen und für Zahlungen an Einrichtungen der öffentlichen Verwaltung. MyBank
                        ist eine flexibele Lösung für B2B- und B2C-Unternehmen: 40 Millionen aktivierte Kunden und über
                        210 Banken und PSPs haben sich dem MyBank-Netzwerk in ganz Europa angeschlossen.'
                ],
                'en' => [
                    'desc' => 'MyBank',
                    'longdesc' => '<img src="https://www.paypalobjects.com/images/checkout/alternative_payments/paypal_mybank_color.svg" title="MyBank" style="float: left;margin-right: 10px;" />
                        MyBank offers safe and protected real-time bank transfers between customers
                        and merchants. MyBank is available on a wide range of sites for the purchase of products,
                        services and for payments towards public administration entities. MyBank is a flexible
                        solution for both B2B and B2C businesses: 40 million enabled customers and over 210 banks
                        and PSPs have joined the MyBank network throughout Europe.'
                ]
            ],
            'countries' => ['IT'],
            'uapmpaymentsource' => 'mybank',
            'constraints' => self::PAYMENT_CONSTRAINTS
        ],
        // uAPM OXXO
        'oscpaypal_oxxo' => [
            'descriptions' => [
                'de' => [
                    'desc' => 'OXXO',
                    'longdesc' => '<img src="https://www.paypalobjects.com/images/checkout/alternative_payments/paypal_oxxo_color.svg" title="OXXO" style="float: left;margin-right: 10px;" />
                        OXXO ist eine Convenience-Store-Kette aus Mexiko mit über 18.000 Geschäften
                        quer durch Lateinamerika. OXXO wurde vor über 30 Jahren gegründet und ist angeblich das größte
                        Convenience-Store-Kette in Mexiko. E-Commerce-Kunden wählen einfach OXXO als Zahlungsmittel
                        Methode, die einen Sofortgutschein mit einer bestimmten Zahlungsreferenz erstellt. Sobald
                        sie genommen haben diese an einen OXXO-Store und bar bezahlt, versendet der Händler das Produkt.'
                ],
                'en' => [
                    'desc' => 'OXXO',
                    'longdesc' => '<img src="https://www.paypalobjects.com/images/checkout/alternative_payments/paypal_oxxo_color.svg" title="OXXO" style="float: left;margin-right: 10px;" />
                        OXXO is a chain of convenience stores from Mexico, with over 18,000 stores
                        across Latin America. Established over 30 years ago, OXXO is reportedly the largest
                        convenience store chain in Mexico. E-commerce consumers simply choose OXXO as the payment
                        method, creating an instant voucher with a specific payment reference. Once they have taken
                        this to an OXXO store and paid in cash, the merchant ships the product.'
                ]
            ],
            'countries' => ['MX'],
            'uapmpaymentsource' => 'oxxo',
            'constraints' => self::PAYMENT_CONSTRAINTS
        ],
        // uAPM Przelewy24
        'oscpaypal_przelewy24' => [
            'descriptions' => [
                'de' => [
                    'desc' => 'Przelewy24',
                    'longdesc' => '<img src="https://www.paypalobjects.com/images/checkout/alternative_payments/paypal_przelewy24_color.svg" title="Przelewy24" style="float: left;margin-right: 10px;" />
                        Przelewy24, oft auch als P24 bezeichnet, ist eine der wichtigsten Online-Zahlungs-
                        Systeme in Polen. Mehr als 80 % der Online-Shopper in Polen bezahlen über Przelewy24 und
                        fast 10.000 online Händler bieten diese Zahlungsmethode in ihren Online-Shops an.'
                ],
                'en' => [
                    'desc' => 'Przelewy24',
                    'longdesc' => '<img src="https://www.paypalobjects.com/images/checkout/alternative_payments/paypal_przelewy24_color.svg" title="Przelewy24" style="float: left;margin-right: 10px;" />
                        Przelewy24, often referred to as P24, is one of Poland’s primary online payment
                        systems. More than 80% of online shoppers in Poland pay via Przelewy24 and almost 10,000 online
                        merchants offer this payment method in their online shops.'
                ]
            ],
            'countries' => ['PL'],
            'uapmpaymentsource' => 'p24',
            'constraints' => self::PAYMENT_CONSTRAINTS
        ],
        // uAPM Sofort
        'oscpaypal_sofort' => [
            'descriptions' => [
                'de' => [
                    'desc' => 'Sofort',
                    'longdesc' => '<img src="https://www.paypalobjects.com/images/checkout/alternative_payments/paypal_sofort_black.svg" title="Sofort" style="float: left;margin-right: 10px;" />
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
                    'longdesc' => '<img src="https://www.paypalobjects.com/images/checkout/alternative_payments/paypal_sofort_black.svg" title="Sofort" style="float: left;margin-right: 10px;" />
                        Sofort, also known as Pay now with Klarna, is a popular online banking method
                        in Germany, Austria, Switzerland and Belgium, making it a must-have for any business wanting
                        to operate in this area. In 2014 Sofort was acquired by Klarna and the Klarna Group was
                        established. With Sofort, security-conscious consumers use their own online banking details,
                        entered through the secure payment page of Sofort. Once logged in, a one-time confirmation
                        code is used by the consumer to authorize payment.'
                ]
            ],
            'countries' => ['DE', 'AT', 'BE', 'IT', 'NL', 'UK', 'ES'],
            'uapmpaymentsource' => 'sofort',
            'constraints' => self::PAYMENT_CONSTRAINTS
        ],
        // uAPM Trustly
        'oscpaypal_trustly' => [
            'descriptions' => [
                'de' => [
                    'desc' => 'Trustly',
                    'longdesc' => '<img src="https://www.paypalobjects.com/images/checkout/alternative_payments/paypal_trustly_color.svg" title="Trustly" style="float: left;margin-right: 10px;" />
                        Trustly erfüllt die hohe Nachfrage nach Banküberweisungszahlungen in wichtigen
                        europäischen Märkten. Mit Trustly können Verbraucher Zahlungen direkt von ihren Bankkonten
                        mit einem hoch sicheren Zahlungsoption ohne Risiko von gestohlenen Daten oder Betrug initiieren.
                        Trustly integriert sich in Banken zum tranferieren von Geldern in ganz Europa und bietet
                        Echtzeit-Abstimmung durch proprietäre Integrationen zu diesen Konten. Trustly unterstützt
                        nativ Zahlungen an der Händlerkasse und ist für alle Geräte optimiert.'
                ],
                'en' => [
                    'desc' => 'Trustly',
                    'longdesc' => '<img src="https://www.paypalobjects.com/images/checkout/alternative_payments/paypal_trustly_color.svg" title="Trustly" style="float: left;margin-right: 10px;" />
                        Trustly meets the high demand for bank transfer payments in key European markets.
                        With Trustly, consumers can initiate payments directly from their bank accounts, using a highly
                        secure payment option, with no risk of stolen details or fraud. Trustly integrates with banks
                        to collect funds locally across Europe and offers real-time reconciliation through proprietary
                        integrations to these accounts. Trustly natively supports payments on merchant checkout and
                        is optimized for all devices.'
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
            'oxcontent_de' => 'Mit Klicken auf den Button akzeptieren Sie die Ratepay Zahlungsbedingungen und erklären
                sich mit der Durchführung einer Risikoprüfung durch Ratepay, unseren Partner, einverstanden. Sie
                akzeptieren auch PayPals Datenschutzerklärung. Falls Ihre Transaktion per Kauf auf Rechnung erfolgreich
                abgewickelt werden kann, wird der Kaufpreis an Ratepay abgetreten und Sie dürfen nur an Ratepay
                überweisen, nicht an den Händler.',
            'oxcontent_en' => ' By clicking on the button, you agree to the terms of payment and performance of a risk
                check from the payment partner, Ratepay. You also agree to PayPal’s privacy statement. If your request
                to purchase upon invoice is accepted, the purchase price claim will be assigned to Ratepay, and you may
                only pay Ratepay, not the merchant.'
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