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
    'OSC_PAYPAL_ACDC_ERROR_INBOX'                   => 'Ein Fehler ist aufgetreten. Bitte überprüfen Sie Ihre Zahlungsinformationen.',

    'OSC_PAYPAL_VAT_CORRECTION'                     => 'Mwst. Korrektur',

    'OSC_PAYPAL_ACDC_ERROR_MISSING_NAME'            => 'Bitte geben Sie den Namen auf Ihrer Karte ein',
    'OSC_PAYPAL_ACDC_ERROR_MISSING_NUMBER'          => 'Bitte geben Sie Ihre Kartennummer ein',
    'OSC_PAYPAL_ACDC_ERROR_MISSING_CVV'             => 'Bitte geben Sie den CVV-Code ein',
    'OSC_PAYPAL_ACDC_ERROR_MISSING_EXDATE'          => 'Bitte geben Sie das Ablaufdatum ein',

    'OSC_PAYPAL_PUI_HELP'                           => 'Für die Abwicklung des Rechnungskaufes benötigen wir Ihr Geburtsdatum sowie eine gültige Telefonnummer mit Orts- oder Ländervorwahl (z.B. 030 123456789 oder +49 30 123456789)',
    'OSC_PAYPAL_PUI_BIRTHDAY'                       => 'Geburtstag',
    'OSC_PAYPAL_PUI_BIRTHDAY_PLACEHOLDER'           => '01.01.1970',
    'OSC_PAYPAL_PUI_PHONENUMBER'                    => 'Telefonnr.',
    'OSC_PAYPAL_PUI_PHONENUMBER_PLACEHOLDER'        => '+49 30 123456789',
    'OSC_PAYPAL_PUI_PLEASE_RETRY'                   => 'Bitte geben Sie ihre Daten erneut ein.',
    'OSC_PAYPAL_PUI_AGE_WARNING'                    => 'Sie müssen mindestens 18 Jahre alt sein, um einen Kauf auf Rechnung durchzuführen.',
    'PAYPAL_PAYMENT_ERROR_PUI_GENRIC'               => 'Validierung der Kundendaten für PayPal Rechnungskauf mit Ratepay fehlgeschlagen.',
    'PUI_PAYMENT_SOURCE_INFO_CANNOT_BE_VERIFIED'    => 'Die Kombination aus Ihrem Namen und Ihrer Anschrift konnte nicht für  PayPal Rechnungskauf validiert werden. Bitte korrigieren Sie Ihre Daten und versuchen Sie es erneut. Weitere Informationen finden Sie in den <a href="https://www.ratepay.com/legal-payment-dataprivacy/?lang=de">Ratepay Datenschutzbestimmungen</a> oder nutzen Sie das <a href="https://www.ratepay.com/kontakt/">Ratepay Kontaktformular</a>.',
    'PUI_PAYMENT_SOURCE_DECLINED_BY_PROCESSOR'      => 'Die gewählte Zahlungsart  PayPal Rechnungskauf kann nicht genutzt werden. Diese Entscheidung basiert auf einem automatisierten Datenverarbeitungsverfahren. Weitere Informationen finden Sie in den <a href="https://www.ratepay.com/legal-payment-dataprivacy/?lang=de">Ratepay Datenschutzbestimmungen</a> oder nutzen Sie das <a href="https://www.ratepay.com/kontakt/">Ratepay Kontaktformular</a>.',
    'PAYMENT_ERROR_INSTRUMENT_DECLINED'             => 'Die gewählte Zahlart steht Ihnen bei PayPal nicht zur Verfügung.',
    'OSC_PAYPAL_ORDER_NOT_APPROVED'                 => 'Die Bestellung wurde nicht genehmigt. Bitte versuchen Sie es später oder mit einer anderen Zahlart.',

    'OSC_PAYPAL_LOG_IN_TO_CONTINUE'                 => 'Bitte loggen Sie sich ein, um die Bestellung abzuschliessen.',
    'OSC_PAYPAL_3DSECURITY_ERROR'                   => 'Die Sicherheitsüberprüfung ist fehlgeschlagen, bitte erneut versuchen.',
    'OSC_PAYPAL_AUTHORIZATION_DENIED_ERROR'         => 'Die Zahlungsautorisierung wurde abgelehnt. Bitte überprüfen Sie Ihre Zahlungsdaten und versuchen Sie es erneut.',
    'OSC_PAYPAL_UNKNOWN_ERROR'                      => 'Bei der Zahlung ist ein unbekannter Fehler aufgetreten. Bitte versuchen Sie es erneut oder wählen Sie eine andere Zahlart.',
    'OSC_PAYPAL_ERROR_INVALID_ADDRESS'              => 'Ihre Rechnungs- oder Lieferadresse ist unvollständig oder ungültig. Bitte überprüfen Sie insbesondere Postleitzahl und Stadt und versuchen Sie es erneut.',
    'OSC_PAYPAL_PAYMENT_CANCELED'                     => 'Die Zahlung wurde storniert',
    'OSC_PAYPAL_CAPTURE_DENIED_ERROR'               => 'Die Überweisung wurde abgelehnt. Bitte überprüfen Sie Ihre Zahlungsdaten und versuchen Sie es erneut.',
    'OSC_PAYPAL_INTERNAL_SERVICE_ERROR'             => 'Bei der Bearbeitung Ihrer Zahlung ist ein unbekannter Fehler aufgetreten. Bitte versuchen Sie es später erneut.',
    'OSC_PAYPAL_ORDEREXECUTION_ERROR'               => 'Der Bezahlvorgang wurde abgebrochen.',
    'OSC_PAYPAL_PAYMENT_INTERRUPTED'                => 'Der Bezahlvorgang konnte nicht abgeschlossen werden. Bitte versuchen Sie es erneut oder wählen Sie eine andere Zahlungsart. Falls Sie diese Seite gerade in einer App-Ansicht (z. B. der Google-, Facebook- oder Instagram-App) geöffnet haben, kann das Öffnen in einem Standard-Browser (Safari, Chrome, Firefox) das Problem möglicherweise beheben.',
    'OSCPAYPAL_KILL_EXPRESS_SESSION_REASON'         => 'Der Warenkorb wurde geändert. Aus diesem Grund wurde der aktive PayPal-Zahlvorgang automatisch abgebrochen. Bitte starten Sie die Zahlung mit PayPal erneut. Es wurde noch kein Geld von PayPal eingezogen.',

    'OSC_PAYPAL_VAULTING_MENU'                      => 'PayPal verwalten',
    'OSC_PAYPAL_VAULTING_MENU_CARD'                 => 'Kredit- oder Debitkarte verwalten',
    'OSC_PAYPAL_VAULTING_VAULTED_PAYMENTS'          => 'Gespeicherte Zahlungsarten',
    'OSC_PAYPAL_VAULTING_ERROR'                     => 'Beim Speichern Ihrer Zahlart ist etwas schiefgelaufen.',
    'OSC_PAYPAL_VAULTING_SUCCESS'                   => 'Beim bezahlen haben Sie Ihre gespeicherten Zahlungsdaten genutzt. Wenn Sie das zum ersten Mal getan haben, dann sehen Sie diese Daten in ca. 15min auch in Ihrem Shop-Kundenaccount.',
    'OSC_PAYPAL_VAULTING_SAVE'                      => 'Zahlart speichern',
    'OSC_PAYPAL_VAULTING_DELETE'                    => 'Zahlart löschen',
    'OSC_PAYPAL_VAULTING_REFRESH_CACHE'              => 'Zahlungsarten aktualisieren',
    'OSC_PAYPAL_CONTINUE_TO_NEXT_STEP'              => 'Weiter mit gespeicherter Zahlungsart',
    'OSC_PAYPAL_CARD_ENDING_IN'                     => 'endet mit ●●●',
    'OSC_PAYPAL_CARD_PAYPAL_PAYMENT'                => 'PayPal Zahlung mit',
    'OSC_PAYPAL_VAULTING_USE_HINT'                  => 'Bezahlung mit gespeichertem PayPal<br ><br >Wenn Sie einen anderen PayPal-Zugang nutzen wollen, so löschen Sie den aktuellen Zugang in Ihrem Kundenkonto und melden sich anschließend hier erneut an.',
    'OSC_PAYPAL_DELETE_FAILED'                      => 'Beim Löschen Ihrer Zahlart ist etwas schiefgelaufen.',
    'OSC_RUNNING_PAYPAL_CHECKOUT_SESSION_HINT' => 'Zahlung erfolgt mit PayPal-Zahlart.',
    'OSC_RUNNING_PAYPAL_CHECKOUT_SESSION_HINT_AFREF' => 'Hier zum Bestellabschluss.',

    'OSC_PAYPAL_PAYMENT_PUI'                      => 'Kauf auf Rechnung - Bankdaten',
    'OSC_PAYPAL_PAYMENT_PUI_REFERENCE'            => 'Verwendungszweck',
    'OSC_PAYPAL_PAYMENT_PUI_BIC'                  => 'BIC',
    'OSC_PAYPAL_PAYMENT_PUI_IBAN'                 => 'IBAN',
    'OSC_PAYPAL_PAYMENT_PUI_BANKNAME'             => 'Bankname',
    'OSC_PAYPAL_PAYMENT_PUI_ACCOUNTHOLDER'        => 'Kontoinhaber',
    'OSC_PAYPAL_PAYMENT_PUI_FOLLOW'               => 'In wenigen Minuten erhalten Sie eine weitere eMail mit den Kontodaten für die Überweisung. Bitte haben Sie einen Augenblick Geduld.',
    'OSC_PAYPAL_PAYMENT_PUI_NOTE'                 => 'Bitte nutzen Sie folgende Daten für die Überweisung der Bestellsumme',
    'OSC_PAYPAL_PAYMENT_PUI_HEADING'              => 'Zahlungsinformationen',

    'OSC_PAYPAL_ORDER_SUBMITTING'                 => 'Bestellung wird verarbeitet …',
    'OSC_PAYPAL_REFUND_MAIL_TITLE'                => 'Rückerstattung zu Ihrer Bestellung',
    'OSC_PAYPAL_REFUND_MAIL_SUBJECT'              => 'Rückerstattung zu Ihrer Bestellung %s',
    'OSC_PAYPAL_REFUND_MAIL_SUBJECT_OWNER'        => 'PayPal: Rückerstattung zur Bestellung %s veranlasst',
    'OSC_PAYPAL_REFUND_MAIL_SALUTATION'           => 'Guten Tag',
    'OSC_PAYPAL_REFUND_MAIL_INTRO'                => 'wir haben eine Rückerstattung für Sie veranlasst.',
    'OSC_PAYPAL_REFUND_MAIL_INTRO_OWNER'          => 'Für die folgende Bestellung wurde eine Rückerstattung veranlasst '
        . '(PayPal Payment Provider).',
    'OSC_PAYPAL_REFUND_MAIL_AMOUNT'               => 'Erstatteter Betrag',
    'OSC_PAYPAL_REFUND_MAIL_ORDER_TOTAL'          => 'Bestellwert',
    'OSC_PAYPAL_REFUND_MAIL_NOTE'                 => 'Die Rückerstattung wurde Ihrer ursprünglich verwendeten '
        . 'Zahlungsart gutgeschrieben. Die Wertstellung hängt von Ihrem Zahlungsmittel und Ihrer Bank ab.',
    'OSC_PAYPAL_CANCEL_MAIL_TITLE'                => 'Stornierung Ihrer Bestellung',
    'OSC_PAYPAL_CANCEL_MAIL_SUBJECT'              => 'Stornierung Ihrer Bestellung %s',
    'OSC_PAYPAL_CANCEL_MAIL_SUBJECT_OWNER'        => 'PayPal: Bestellung %s storniert',
    'OSC_PAYPAL_CANCEL_MAIL_SALUTATION'           => 'Guten Tag',
    'OSC_PAYPAL_CANCEL_MAIL_INTRO'                => 'Ihre Bestellung wurde storniert.',
    'OSC_PAYPAL_CANCEL_MAIL_INTRO_OWNER'          => 'Die folgende Bestellung wurde storniert.',
    'OSC_PAYPAL_CANCEL_MAIL_ORDER_TOTAL'          => 'Bestellwert',
    'OSC_PAYPAL_CANCEL_MAIL_REFUNDED'             => 'Erstatteter Betrag',
    'OSC_PAYPAL_CANCEL_MAIL_NOTE_NO_REFUND'       => 'Sollte für diese Bestellung bereits eine Zahlung erfolgt sein, '
        . 'erhalten Sie die Rückerstattung in einer separaten Nachricht bestätigt.',
];
