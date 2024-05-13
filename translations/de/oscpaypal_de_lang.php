<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

$aLang = [
    'charset'                                       => 'UTF-8',
    'OSC_PAYPAL_DESCRIPTION'                        => 'Zahlung bei %s',
    'OSC_PAYPAL_PAY_EXPRESS'                        => 'PayPal Express',
    'OSC_PAYPAL_PAY_PROCESSED'                      => 'Ihre Zahlung wird von PayPal verarbeitet.',
    'OSC_PAYPAL_PAY_UNLINK'                         => 'aufheben',

    'OSC_PAYPAL_PAY_EXPRESS_ERROR_DELCOUNTRY'       => 'Leider liefern wir nicht in Ihr gewünschtes Lieferland. Bitte wählen Sie eine andere Lieferadresse aus.',
    'OSC_PAYPAL_PAY_EXPRESS_ERROR_INPUTVALIDATION'  => 'Leider kann PayPal nicht alle Adress-Pflichtfelder des Shops automatisch befüllen. Bitte legen Sie den Artikel in den Warenkorb, melden sich im Shop an und schließen die Bestellung dann mit PayPal ab.',

    'OSC_PAYPAL_ACDC'                               => 'Advanced Credit and Debit Card',
    'OSC_PAYPAL_ACDC_CARD_NUMBER'                   => 'Kartennummer',
    'OSC_PAYPAL_ACDC_CARD_EXDATE'                   => 'Ablaufdatum',
    'OSC_PAYPAL_ACDC_CARD_CVV'                      => 'CVV',
    'OSC_PAYPAL_ACDC_CARD_NAME_ON_CARD'             => 'Karteninhaber',
    'OSC_PAYPAL_ACDC_PLEASE_RETRY'                  => 'Bezahlvorgang wurde aus Sicherheitsgründen abgebrochen. Bitte geben Sie ihre Kreditkartendaten erneut ein und klicken einmal auf den Bestellbutton.',

    'OSC_PAYPAL_VAT_CORRECTION'                     => 'Mwst. Korrektur',

    'OSC_PAYPAL_PUI_HELP'                           => 'Für die Abwicklung des Rechnungskaufes benötigen wir Ihr Geburtsdatum sowie eine gültige Telefonnummer mit Orts- oder Ländervorwahl (z.B. 030 123456789 oder +49 30 123456789)',
    'OSC_PAYPAL_PUI_BIRTHDAY'                       => 'Geburtstag',
    'OSC_PAYPAL_PUI_BIRTHDAY_PLACEHOLDER'           => '01.01.1970',
    'OSC_PAYPAL_PUI_PHONENUMBER'                    => 'Telefonnr.',
    'OSC_PAYPAL_PUI_PHONENUMBER_PLACEHOLDER'        => '+49 30 123456789',
    'OSC_PAYPAL_PUI_PLEASE_RETRY'                   => 'Bitte geben Sie ihre Daten erneut ein.',
    'PAYPAL_PAYMENT_ERROR_PUI_GENRIC'               => 'Validierung der Kundendaten für PayPal Rechnungskauf mit Ratepay fehlgeschlagen.',
    'PUI_PAYMENT_SOURCE_INFO_CANNOT_BE_VERIFIED'    => 'Die Kombination aus Ihrem Namen und Ihrer Anschrift konnte nicht für  PayPal Rechnungskauf validiert werden. Bitte korrigieren Sie Ihre Daten und versuchen Sie es erneut. Weitere Informationen finden Sie in den <a href="https://www.ratepay.com/legal-payment-dataprivacy/?lang=de">Ratepay Datenschutzbestimmungen</a> oder nutzen Sie das <a href="https://www.ratepay.com/kontakt/">Ratepay Kontaktformular</a>.',
    'PUI_PAYMENT_SOURCE_DECLINED_BY_PROCESSOR'      => 'Die gewählte Zahlungsart  PayPal Rechnungskauf kann nicht genutzt werden. Diese Entscheidung basiert auf einem automatisierten Datenverarbeitungsverfahren. Weitere Informationen finden Sie in den <a href="https://www.ratepay.com/legal-payment-dataprivacy/?lang=de">Ratepay Datenschutzbestimmungen</a> oder nutzen Sie das <a href="https://www.ratepay.com/kontakt/">Ratepay Kontaktformular</a>.',
    'PAYMENT_ERROR_INSTRUMENT_DECLINED'             => 'Die gewählte Zahlart steht Ihnen bei PayPal nicht zur Verfügung.',

    'OSC_PAYPAL_ORDER_EXECUTION_IN_PROGRESS'        => 'Ihre Bestellung wird geprüft, das kann bis zu 60 Sekunden dauern. Bitte kurz warten und dann erneut auf "zahlungspflichtig bestellen" klicken.',
    'OSC_PAYPAL_LOG_IN_TO_CONTINUE'                 => 'Bitte loggen Sie sich ein, um die Bestellung abzuschliessen.',
    'OSC_PAYPAL_3DSECURITY_ERROR'                   => 'Die Sicherheitsüberprüfung ist fehlgeschlagen, bitte erneut versuchen.',
    'OSC_PAYPAL_ORDEREXECUTION_ERROR'               => 'Der Bezahlvorgang wurde abgebrochen.',

    'OSC_PAYPAL_VAULTING_MENU'                      => 'PayPal Zahlart speichern',
    'OSC_PAYPAL_VAULTING_MENU_CARD'                 => 'PayPal Kreditkarte speichern',
    'OSC_PAYPAL_VAULTING_CARD_SAVE'                 => 'Karte speichern',
    'OSC_PAYPAL_VAULTING_SAVE_INSTRUCTION'          => 'Speichern Sie hier Ihre PayPal Zahlungsmethode für einen schnelleren Checkout.',
    'OSC_PAYPAL_VAULTING_SAVE_INSTRUCTION_CARD'     => 'Speichern Sie hier Ihre Karte für einen schnelleren Checkout.',
    'OSC_PAYPAL_VAULTING_VAULTED_PAYMENTS'          => 'Gespeicherte Zahlungsarten',
    'OSC_PAYPAL_VAULTING_ERROR'                     => 'Beim Speichern Ihrer Zahlart ist etwas schiefgelaufen.',
    'OSC_PAYPAL_VAULTING_SUCCESS'                   => 'Ihre Zahlart wurde erfolgreich gespeichert. Sie finden Ihre gespeicherten Zahlungsarten im "Mein Konto" Bereich.',
    'OSC_PAYPAL_VAULTING_SAVE'                      => 'Zahlart speichern',
    'OSC_PAYPAL_VAULTING_DELETE'                    => 'Zahlart löschen',
    'OSC_PAYPAL_CONTINUE_TO_NEXT_STEP'              => 'Weiter mit gespeicherter Zahlungsart',
    'OSC_PAYPAL_CARD_ENDING_IN'                     => 'endet mit ●●●',
    'OSC_PAYPAL_CARD_PAYPAL_PAYMENT'                => 'PayPal Zahlung mit',
    'OSC_PAYPAL_DELETE_FAILED'                      => 'Beim Löschen Ihrer Zahlart ist etwas schiefgelaufen.',
    'OSCPAYPAL_KILL_EXPRESS_SESSION_REASON'         => 'Der Warenkorb wurde geändert. Aus diesem Grund wurde der aktive PayPal-Zahlvorgang automatisch abgebrochen. Bitte starten Sie die Zahlung mit PayPal erneut. Es wurde noch kein Geld von PayPal eingezogen.',
];
