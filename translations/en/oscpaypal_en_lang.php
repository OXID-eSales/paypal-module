<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

$aLang = [
    'charset'                                      => 'UTF-8',
    'OSC_PAYPAL_DESCRIPTION'                       => 'Payment at %s',
    'OSC_PAYPAL_PAY_EXPRESS'                       => 'PayPal Express',
    'OSC_PAYPAL_PAY_PROCESSED'                     => 'Your payment will be processed by PayPal Pay.',
    'OSC_PAYPAL_PAY_UNLINK'                        => 'unlink',

    'OSC_PAYPAL_PAY_EXPRESS_ERROR_DELCOUNTRY'      => 'Unfortunately we do not deliver to your desired delivery country. Please select a different delivery address.',
    'OSC_PAYPAL_PAY_EXPRESS_ERROR_INPUTVALIDATION' => 'Unfortunately, PayPal cannot automatically fill in all mandatory address fields in the shop. Please place the item in the shopping cart, log in to the shop and then complete the order with PayPal.',

    'OSC_PAYPAL_ACDC'                              => 'Advanced Credit and Debit Card',
    'OSC_PAYPAL_ACDC_CARD_NUMBER'                  => 'Card Number',
    'OSC_PAYPAL_ACDC_CARD_EXDATE'                  => 'Expiration Date',
    'OSC_PAYPAL_ACDC_CARD_CVV'                     => 'CVV',
    'OSC_PAYPAL_ACDC_CARD_NAME_ON_CARD'            => 'Name on Card',
    'OSC_PAYPAL_ACDC_PLEASE_RETRY'                 => 'Payment process was stopped due to security reasons. Please enter your credit card data again and press submit button once.',

    'OSC_PAYPAL_VAT_CORRECTION'                    => 'VAT. Correction',

    'OSC_PAYPAL_PUI_HELP'                          => 'To process the invoice, we need your date of birth and a valid telephone number with city- or country code (e.g. 030 123456789 or +49 30 123456789)',
    'OSC_PAYPAL_PUI_BIRTHDAY'                      => 'Birthday',
    'OSC_PAYPAL_PUI_BIRTHDAY_PLACEHOLDER'          => '01.01.1970',
    'OSC_PAYPAL_PUI_PHONENUMBER'                   => 'Phoneno.',
    'OSC_PAYPAL_PUI_PHONENUMBER_PLACEHOLDER'       => '+49 30 123456789',
    'OSC_PAYPAL_PUI_PLEASE_RETRY'                  => 'Please enter your data once again.',
    'PAYPAL_PAYMENT_ERROR_PUI_GENRIC'              => 'Customer data validation for PayPal Pay Upon Invoice with RatePay failed',
    'PUI_PAYMENT_SOURCE_INFO_CANNOT_BE_VERIFIED'   => 'The combination of your name and address could not be validated for PayPal Pay Upon Invoice. Please correct your data and try again. You can find further information in the <a href="https://www.ratepay.com/en/ratepay-data-privacy-statement/">Ratepay Data Privacy Statement</a> or you can contact Ratepay using this <a href="https://www.ratepay.com/en/contact/">contact form</a>.',
    'PUI_PAYMENT_SOURCE_DECLINED_BY_PROCESSOR'     => 'It is not possible to use the selected payment method PayPal Pay Upon Invoice. This decision is based on automated data processing. You can find further information in the  <a href="https://www.ratepay.com/en/ratepay-data-privacy-statement/">Ratepay Data Privacy Statement</a> or you can contact Ratepay using this <a href="https://www.ratepay.com/en/contact/">contact form</a>.',
    'PAYMENT_ERROR_INSTRUMENT_DECLINED'            => 'The chosen payment method at PayPal is not available for you.',

    'OSC_PAYPAL_ORDER_EXECUTION_IN_PROGRESS'       => 'Order execution in progress, please wait for approx 60 seconds then press "order now" again.',
    'OSC_PAYPAL_LOG_IN_TO_CONTINUE'                => 'Please log in to continue checking out.',

    'OSC_PAYPAL_3DSECURITY_ERROR'                  => 'Security check failed, please retry.',
    'OSC_PAYPAL_ORDEREXECUTION_ERROR'              => 'Payment process could not be completed.',

    'OSC_PAYPAL_VAULTING_MENU'                      => 'Save PayPal payment method',
    'OSC_PAYPAL_VAULTING_MENU_CARD'                 => 'Save PayPal card',
    'OSC_PAYPAL_VAULTING_CARD_SAVE'                 => 'Save card',
    'OSC_PAYPAL_VAULTING_SAVE_INSTRUCTION'          => 'Save your PayPal payment method here for a faster checkout.',
    'OSC_PAYPAL_VAULTING_SAVE_INSTRUCTION_CARD'     => 'Save your Card here for a faster checkout.',
    'OSC_PAYPAL_VAULTING_VAULTED_PAYMENTS'          => 'Saved payments',
    'OSC_PAYPAL_VAULTING_ERROR'                     => 'There was an error saving your payment method.',
    'OSC_PAYPAL_VAULTING_SUCCESS'                   => 'Your payment method was saved successfully. You will find your saved payments in your Account area.',
    'OSC_PAYPAL_VAULTING_SAVE'                      => 'Save payment',
    'OSC_PAYPAL_VAULTING_DELETE'                    => 'Delete payment',
    'OSC_PAYPAL_CONTINUE_TO_NEXT_STEP'              => 'Continue with saved payment method',
    'OSC_PAYPAL_CARD_ENDING_IN'                     => 'ending in ●●●',
    'OSC_PAYPAL_CARD_PAYPAL_PAYMENT'                => 'PayPal payment with',
    'OSC_PAYPAL_DELETE_FAILED'                      => 'There was an error deleting your payment method.',
    'OSCPAYPAL_KILL_EXPRESS_SESSION_REASON'         => 'The shopping cart has been changed. For this reason, the active PayPal payment process was automatically canceled. Please restart the payment with PayPal. No money has been collected from PayPal yet.',
];
