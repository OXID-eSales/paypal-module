<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Unit\Service;

use OxidEsales\TestingLibrary\UnitTestCase;
use OxidSolutionCatalysts\PayPal\Service\SCAValidator;
use OxidSolutionCatalysts\PayPal\Exception\CardValidation as CardValidationException;
use OxidSolutionCatalysts\PayPalApi\Model\Orders\Order as PayPalApiOrder;

class CardValidationTest extends UnitTestCase
{
    private $missingCardAuthentication = 'O:50:"OxidSolutionCatalysts\PayPalApi\Model\Orders\Order":13:{s:2:"id";N;s:14:"payment_source";O:66:"OxidSolutionCatalysts\PayPalApi\Model\Orders\PaymentSourceResponse":24:{s:4:"card";O:57:"OxidSolutionCatalysts\PayPalApi\Model\Orders\CardResponse":11:{s:2:"id";N;s:4:"name";N;s:15:"billing_address";N;s:12:"last_n_chars";N;s:11:"last_digits";s:4:"9760";s:5:"brand";s:4:"VISA";s:4:"type";s:6:"CREDIT";s:6:"issuer";N;s:3:"bin";N;s:21:"authentication_result";N;s:10:"attributes";N;}s:6:"paypal";N;s:6:"wallet";N;s:4:"bank";N;s:6:"alipay";N;s:10:"bancontact";N;s:4:"blik";N;s:14:"boletobancario";N;s:3:"eps";N;s:7:"giropay";N;s:5:"ideal";N;s:10:"multibanco";N;s:4:"oxxo";N;s:4:"payu";N;s:3:"p24";N;s:16:"pay_upon_invoice";N;s:9:"safetypay";N;s:8:"satispay";N;s:7:"trustly";N;s:12:"verkkopankki";N;s:9:"wechatpay";N;s:9:"apple_pay";N;}s:6:"intent";N;s:22:"processing_instruction";s:14:"NO_INSTRUCTION";s:5:"payer";N;s:15:"expiration_time";N;s:14:"purchase_units";a:0:{}s:6:"status";N;s:5:"links";N;s:22:"credit_financing_offer";N;s:19:"application_context";N;s:11:"create_time";N;s:11:"update_time";N;}';

    private $nonCardPaymentSource = 'O:50:"OxidSolutionCatalysts\PayPalApi\Model\Orders\Order":13:{s:2:"id";N;s:14:"payment_source";O:66:"OxidSolutionCatalysts\PayPalApi\Model\Orders\PaymentSourceResponse":24:{s:4:"card";N;s:6:"paypal";N;s:6:"wallet";N;s:4:"bank";N;s:6:"alipay";N;s:10:"bancontact";N;s:4:"blik";N;s:14:"boletobancario";N;s:3:"eps";N;s:7:"giropay";O:52:"OxidSolutionCatalysts\PayPalApi\Model\Orders\Giropay":3:{s:4:"name";s:11:"Marc Muster";s:12:"country_code";s:2:"DE";s:3:"bic";N;}s:5:"ideal";N;s:10:"multibanco";N;s:4:"oxxo";N;s:4:"payu";N;s:3:"p24";N;s:16:"pay_upon_invoice";N;s:9:"safetypay";N;s:8:"satispay";N;s:7:"trustly";N;s:12:"verkkopankki";N;s:9:"wechatpay";N;s:9:"apple_pay";N;}s:6:"intent";N;s:22:"processing_instruction";s:14:"NO_INSTRUCTION";s:5:"payer";N;s:15:"expiration_time";N;s:14:"purchase_units";a:0:{}s:6:"status";N;s:5:"links";N;s:22:"credit_financing_offer";N;s:19:"application_context";N;s:11:"create_time";N;s:11:"update_time";N;}';

    private $standardCard3D = 'O:50:"OxidSolutionCatalysts\PayPalApi\Model\Orders\Order":13:{s:2:"id";N;s:14:"payment_source";O:66:"OxidSolutionCatalysts\PayPalApi\Model\Orders\PaymentSourceResponse":24:{s:4:"card";O:57:"OxidSolutionCatalysts\PayPalApi\Model\Orders\CardResponse":11:{s:2:"id";N;s:4:"name";N;s:15:"billing_address";N;s:12:"last_n_chars";N;s:11:"last_digits";s:4:"9760";s:5:"brand";s:4:"VISA";s:4:"type";s:6:"CREDIT";s:6:"issuer";N;s:3:"bin";N;s:21:"authentication_result";O:67:"OxidSolutionCatalysts\PayPalApi\Model\Orders\AuthenticationResponse":2:{s:15:"liability_shift";s:2:"NO";s:14:"three_d_secure";O:79:"OxidSolutionCatalysts\PayPalApi\Model\Orders\ThreeDSecureAuthenticationResponse":2:{s:21:"authentication_status";N;s:17:"enrollment_status";s:1:"U";}}s:10:"attributes";N;}s:6:"paypal";N;s:6:"wallet";N;s:4:"bank";N;s:6:"alipay";N;s:10:"bancontact";N;s:4:"blik";N;s:14:"boletobancario";N;s:3:"eps";N;s:7:"giropay";N;s:5:"ideal";N;s:10:"multibanco";N;s:4:"oxxo";N;s:4:"payu";N;s:3:"p24";N;s:16:"pay_upon_invoice";N;s:9:"safetypay";N;s:8:"satispay";N;s:7:"trustly";N;s:12:"verkkopankki";N;s:9:"wechatpay";N;s:9:"apple_pay";N;}s:6:"intent";N;s:22:"processing_instruction";s:14:"NO_INSTRUCTION";s:5:"payer";N;s:15:"expiration_time";N;s:14:"purchase_units";a:0:{}s:6:"status";N;s:5:"links";N;s:22:"credit_financing_offer";N;s:19:"application_context";N;s:11:"create_time";N;s:11:"update_time";N;}';

    private $success3DCard = 'O:50:"OxidSolutionCatalysts\PayPalApi\Model\Orders\Order":13:{s:2:"id";N;s:14:"payment_source";O:66:"OxidSolutionCatalysts\PayPalApi\Model\Orders\PaymentSourceResponse":24:{s:4:"card";O:57:"OxidSolutionCatalysts\PayPalApi\Model\Orders\CardResponse":11:{s:2:"id";N;s:4:"name";N;s:15:"billing_address";N;s:12:"last_n_chars";N;s:11:"last_digits";s:4:"7704";s:5:"brand";s:4:"VISA";s:4:"type";s:6:"CREDIT";s:6:"issuer";N;s:3:"bin";N;s:21:"authentication_result";O:67:"OxidSolutionCatalysts\PayPalApi\Model\Orders\AuthenticationResponse":2:{s:15:"liability_shift";s:8:"POSSIBLE";s:14:"three_d_secure";O:79:"OxidSolutionCatalysts\PayPalApi\Model\Orders\ThreeDSecureAuthenticationResponse":2:{s:21:"authentication_status";s:1:"Y";s:17:"enrollment_status";s:1:"Y";}}s:10:"attributes";N;}s:6:"paypal";N;s:6:"wallet";N;s:4:"bank";N;s:6:"alipay";N;s:10:"bancontact";N;s:4:"blik";N;s:14:"boletobancario";N;s:3:"eps";N;s:7:"giropay";N;s:5:"ideal";N;s:10:"multibanco";N;s:4:"oxxo";N;s:4:"payu";N;s:3:"p24";N;s:16:"pay_upon_invoice";N;s:9:"safetypay";N;s:8:"satispay";N;s:7:"trustly";N;s:12:"verkkopankki";N;s:9:"wechatpay";N;s:9:"apple_pay";N;}s:6:"intent";N;s:22:"processing_instruction";s:14:"NO_INSTRUCTION";s:5:"payer";N;s:15:"expiration_time";N;s:14:"purchase_units";a:0:{}s:6:"status";N;s:5:"links";N;s:22:"credit_financing_offer";N;s:19:"application_context";N;s:11:"create_time";N;s:11:"update_time";N;}';

    private $failedSignature = 'O:50:"OxidSolutionCatalysts\PayPalApi\Model\Orders\Order":13:{s:2:"id";N;s:14:"payment_source";O:66:"OxidSolutionCatalysts\PayPalApi\Model\Orders\PaymentSourceResponse":24:{s:4:"card";O:57:"OxidSolutionCatalysts\PayPalApi\Model\Orders\CardResponse":11:{s:2:"id";N;s:4:"name";N;s:15:"billing_address";N;s:12:"last_n_chars";N;s:11:"last_digits";s:4:"4992";s:5:"brand";s:4:"VISA";s:4:"type";s:6:"CREDIT";s:6:"issuer";N;s:3:"bin";N;s:21:"authentication_result";O:67:"OxidSolutionCatalysts\PayPalApi\Model\Orders\AuthenticationResponse":2:{s:15:"liability_shift";s:7:"UNKNOWN";s:14:"three_d_secure";O:79:"OxidSolutionCatalysts\PayPalApi\Model\Orders\ThreeDSecureAuthenticationResponse":2:{s:21:"authentication_status";s:1:"U";s:17:"enrollment_status";s:1:"Y";}}s:10:"attributes";N;}s:6:"paypal";N;s:6:"wallet";N;s:4:"bank";N;s:6:"alipay";N;s:10:"bancontact";N;s:4:"blik";N;s:14:"boletobancario";N;s:3:"eps";N;s:7:"giropay";N;s:5:"ideal";N;s:10:"multibanco";N;s:4:"oxxo";N;s:4:"payu";N;s:3:"p24";N;s:16:"pay_upon_invoice";N;s:9:"safetypay";N;s:8:"satispay";N;s:7:"trustly";N;s:12:"verkkopankki";N;s:9:"wechatpay";N;s:9:"apple_pay";N;}s:6:"intent";N;s:22:"processing_instruction";s:14:"NO_INSTRUCTION";s:5:"payer";N;s:15:"expiration_time";N;s:14:"purchase_units";a:0:{}s:6:"status";N;s:5:"links";N;s:22:"credit_financing_offer";N;s:19:"application_context";N;s:11:"create_time";N;s:11:"update_time";N;}';

    private $failedAuthentication = 'O:50:"OxidSolutionCatalysts\PayPalApi\Model\Orders\Order":13:{s:2:"id";N;s:14:"payment_source";O:66:"OxidSolutionCatalysts\PayPalApi\Model\Orders\PaymentSourceResponse":24:{s:4:"card";O:57:"OxidSolutionCatalysts\PayPalApi\Model\Orders\CardResponse":11:{s:2:"id";N;s:4:"name";N;s:15:"billing_address";N;s:12:"last_n_chars";N;s:11:"last_digits";s:4:"2421";s:5:"brand";s:4:"VISA";s:4:"type";s:6:"CREDIT";s:6:"issuer";N;s:3:"bin";N;s:21:"authentication_result";O:67:"OxidSolutionCatalysts\PayPalApi\Model\Orders\AuthenticationResponse":2:{s:15:"liability_shift";s:2:"NO";s:14:"three_d_secure";O:79:"OxidSolutionCatalysts\PayPalApi\Model\Orders\ThreeDSecureAuthenticationResponse":2:{s:21:"authentication_status";s:1:"N";s:17:"enrollment_status";s:1:"Y";}}s:10:"attributes";N;}s:6:"paypal";N;s:6:"wallet";N;s:4:"bank";N;s:6:"alipay";N;s:10:"bancontact";N;s:4:"blik";N;s:14:"boletobancario";N;s:3:"eps";N;s:7:"giropay";N;s:5:"ideal";N;s:10:"multibanco";N;s:4:"oxxo";N;s:4:"payu";N;s:3:"p24";N;s:16:"pay_upon_invoice";N;s:9:"safetypay";N;s:8:"satispay";N;s:7:"trustly";N;s:12:"verkkopankki";N;s:9:"wechatpay";N;s:9:"apple_pay";N;}s:6:"intent";N;s:22:"processing_instruction";s:14:"NO_INSTRUCTION";s:5:"payer";N;s:15:"expiration_time";N;s:14:"purchase_units";a:0:{}s:6:"status";N;s:5:"links";N;s:22:"credit_financing_offer";N;s:19:"application_context";N;s:11:"create_time";N;s:11:"update_time";N;}';

    private $noPrompt = 'O:50:"OxidSolutionCatalysts\PayPalApi\Model\Orders\Order":13:{s:2:"id";N;s:14:"payment_source";O:66:"OxidSolutionCatalysts\PayPalApi\Model\Orders\PaymentSourceResponse":24:{s:4:"card";O:57:"OxidSolutionCatalysts\PayPalApi\Model\Orders\CardResponse":11:{s:2:"id";N;s:4:"name";N;s:15:"billing_address";N;s:12:"last_n_chars";N;s:11:"last_digits";s:4:"5422";s:5:"brand";s:4:"VISA";s:4:"type";s:6:"CREDIT";s:6:"issuer";N;s:3:"bin";N;s:21:"authentication_result";O:67:"OxidSolutionCatalysts\PayPalApi\Model\Orders\AuthenticationResponse":2:{s:15:"liability_shift";s:8:"POSSIBLE";s:14:"three_d_secure";O:79:"OxidSolutionCatalysts\PayPalApi\Model\Orders\ThreeDSecureAuthenticationResponse":2:{s:21:"authentication_status";s:1:"A";s:17:"enrollment_status";s:1:"Y";}}s:10:"attributes";N;}s:6:"paypal";N;s:6:"wallet";N;s:4:"bank";N;s:6:"alipay";N;s:10:"bancontact";N;s:4:"blik";N;s:14:"boletobancario";N;s:3:"eps";N;s:7:"giropay";N;s:5:"ideal";N;s:10:"multibanco";N;s:4:"oxxo";N;s:4:"payu";N;s:3:"p24";N;s:16:"pay_upon_invoice";N;s:9:"safetypay";N;s:8:"satispay";N;s:7:"trustly";N;s:12:"verkkopankki";N;s:9:"wechatpay";N;s:9:"apple_pay";N;}s:6:"intent";N;s:22:"processing_instruction";s:14:"NO_INSTRUCTION";s:5:"payer";N;s:15:"expiration_time";N;s:14:"purchase_units";a:0:{}s:6:"status";N;s:5:"links";N;s:22:"credit_financing_offer";N;s:19:"application_context";N;s:11:"create_time";N;s:11:"update_time";N;}';

    private $timeout = 'O:50:"OxidSolutionCatalysts\PayPalApi\Model\Orders\Order":13:{s:2:"id";N;s:14:"payment_source";O:66:"OxidSolutionCatalysts\PayPalApi\Model\Orders\PaymentSourceResponse":24:{s:4:"card";O:57:"OxidSolutionCatalysts\PayPalApi\Model\Orders\CardResponse":11:{s:2:"id";N;s:4:"name";N;s:15:"billing_address";N;s:12:"last_n_chars";N;s:11:"last_digits";s:4:"7210";s:5:"brand";s:4:"VISA";s:4:"type";s:6:"CREDIT";s:6:"issuer";N;s:3:"bin";N;s:21:"authentication_result";O:67:"OxidSolutionCatalysts\PayPalApi\Model\Orders\AuthenticationResponse":2:{s:15:"liability_shift";s:2:"NO";s:14:"three_d_secure";N;}s:10:"attributes";N;}s:6:"paypal";N;s:6:"wallet";N;s:4:"bank";N;s:6:"alipay";N;s:10:"bancontact";N;s:4:"blik";N;s:14:"boletobancario";N;s:3:"eps";N;s:7:"giropay";N;s:5:"ideal";N;s:10:"multibanco";N;s:4:"oxxo";N;s:4:"payu";N;s:3:"p24";N;s:16:"pay_upon_invoice";N;s:9:"safetypay";N;s:8:"satispay";N;s:7:"trustly";N;s:12:"verkkopankki";N;s:9:"wechatpay";N;s:9:"apple_pay";N;}s:6:"intent";N;s:22:"processing_instruction";s:14:"NO_INSTRUCTION";s:5:"payer";N;s:15:"expiration_time";N;s:14:"purchase_units";a:0:{}s:6:"status";N;s:5:"links";N;s:22:"credit_financing_offer";N;s:19:"application_context";N;s:11:"create_time";N;s:11:"update_time";N;}';

    private $notEnrolled = 'O:50:"OxidSolutionCatalysts\PayPalApi\Model\Orders\Order":13:{s:2:"id";N;s:14:"payment_source";O:66:"OxidSolutionCatalysts\PayPalApi\Model\Orders\PaymentSourceResponse":24:{s:4:"card";O:57:"OxidSolutionCatalysts\PayPalApi\Model\Orders\CardResponse":11:{s:2:"id";N;s:4:"name";N;s:15:"billing_address";N;s:12:"last_n_chars";N;s:11:"last_digits";s:4:"8803";s:5:"brand";s:4:"VISA";s:4:"type";s:6:"CREDIT";s:6:"issuer";N;s:3:"bin";N;s:21:"authentication_result";O:67:"OxidSolutionCatalysts\PayPalApi\Model\Orders\AuthenticationResponse":2:{s:15:"liability_shift";s:2:"NO";s:14:"three_d_secure";O:79:"OxidSolutionCatalysts\PayPalApi\Model\Orders\ThreeDSecureAuthenticationResponse":2:{s:21:"authentication_status";N;s:17:"enrollment_status";s:1:"U";}}s:10:"attributes";N;}s:6:"paypal";N;s:6:"wallet";N;s:4:"bank";N;s:6:"alipay";N;s:10:"bancontact";N;s:4:"blik";N;s:14:"boletobancario";N;s:3:"eps";N;s:7:"giropay";N;s:5:"ideal";N;s:10:"multibanco";N;s:4:"oxxo";N;s:4:"payu";N;s:3:"p24";N;s:16:"pay_upon_invoice";N;s:9:"safetypay";N;s:8:"satispay";N;s:7:"trustly";N;s:12:"verkkopankki";N;s:9:"wechatpay";N;s:9:"apple_pay";N;}s:6:"intent";N;s:22:"processing_instruction";s:14:"NO_INSTRUCTION";s:5:"payer";N;s:15:"expiration_time";N;s:14:"purchase_units";a:0:{}s:6:"status";N;s:5:"links";N;s:22:"credit_financing_offer";N;s:19:"application_context";N;s:11:"create_time";N;s:11:"update_time";N;}';

    private $systemNotAvailable = 'O:50:"OxidSolutionCatalysts\PayPalApi\Model\Orders\Order":13:{s:2:"id";N;s:14:"payment_source";O:66:"OxidSolutionCatalysts\PayPalApi\Model\Orders\PaymentSourceResponse":24:{s:4:"card";O:57:"OxidSolutionCatalysts\PayPalApi\Model\Orders\CardResponse":11:{s:2:"id";N;s:4:"name";N;s:15:"billing_address";N;s:12:"last_n_chars";N;s:11:"last_digits";s:4:"8803";s:5:"brand";s:4:"VISA";s:4:"type";s:6:"CREDIT";s:6:"issuer";N;s:3:"bin";N;s:21:"authentication_result";O:67:"OxidSolutionCatalysts\PayPalApi\Model\Orders\AuthenticationResponse":2:{s:15:"liability_shift";s:2:"NO";s:14:"three_d_secure";O:79:"OxidSolutionCatalysts\PayPalApi\Model\Orders\ThreeDSecureAuthenticationResponse":2:{s:21:"authentication_status";N;s:17:"enrollment_status";s:1:"U";}}s:10:"attributes";N;}s:6:"paypal";N;s:6:"wallet";N;s:4:"bank";N;s:6:"alipay";N;s:10:"bancontact";N;s:4:"blik";N;s:14:"boletobancario";N;s:3:"eps";N;s:7:"giropay";N;s:5:"ideal";N;s:10:"multibanco";N;s:4:"oxxo";N;s:4:"payu";N;s:3:"p24";N;s:16:"pay_upon_invoice";N;s:9:"safetypay";N;s:8:"satispay";N;s:7:"trustly";N;s:12:"verkkopankki";N;s:9:"wechatpay";N;s:9:"apple_pay";N;}s:6:"intent";N;s:22:"processing_instruction";s:14:"NO_INSTRUCTION";s:5:"payer";N;s:15:"expiration_time";N;s:14:"purchase_units";a:0:{}s:6:"status";N;s:5:"links";N;s:22:"credit_financing_offer";N;s:19:"application_context";N;s:11:"create_time";N;s:11:"update_time";N;}';

    private $merchantNotActive = 'O:50:"OxidSolutionCatalysts\PayPalApi\Model\Orders\Order":13:{s:2:"id";N;s:14:"payment_source";O:66:"OxidSolutionCatalysts\PayPalApi\Model\Orders\PaymentSourceResponse":24:{s:4:"card";O:57:"OxidSolutionCatalysts\PayPalApi\Model\Orders\CardResponse":11:{s:2:"id";N;s:4:"name";N;s:15:"billing_address";N;s:12:"last_n_chars";N;s:11:"last_digits";s:4:"6405";s:5:"brand";s:4:"VISA";s:4:"type";s:6:"CREDIT";s:6:"issuer";N;s:3:"bin";N;s:21:"authentication_result";O:67:"OxidSolutionCatalysts\PayPalApi\Model\Orders\AuthenticationResponse":2:{s:15:"liability_shift";s:2:"NO";s:14:"three_d_secure";N;}s:10:"attributes";N;}s:6:"paypal";N;s:6:"wallet";N;s:4:"bank";N;s:6:"alipay";N;s:10:"bancontact";N;s:4:"blik";N;s:14:"boletobancario";N;s:3:"eps";N;s:7:"giropay";N;s:5:"ideal";N;s:10:"multibanco";N;s:4:"oxxo";N;s:4:"payu";N;s:3:"p24";N;s:16:"pay_upon_invoice";N;s:9:"safetypay";N;s:8:"satispay";N;s:7:"trustly";N;s:12:"verkkopankki";N;s:9:"wechatpay";N;s:9:"apple_pay";N;}s:6:"intent";N;s:22:"processing_instruction";s:14:"NO_INSTRUCTION";s:5:"payer";N;s:15:"expiration_time";N;s:14:"purchase_units";a:0:{}s:6:"status";N;s:5:"links";N;s:22:"credit_financing_offer";N;s:19:"application_context";N;s:11:"create_time";N;s:11:"update_time";N;}';

    private $failedSignature3DS1 = 'O:50:"OxidSolutionCatalysts\PayPalApi\Model\Orders\Order":13:{s:2:"id";N;s:14:"payment_source";O:66:"OxidSolutionCatalysts\PayPalApi\Model\Orders\PaymentSourceResponse":24:{s:4:"card";O:57:"OxidSolutionCatalysts\PayPalApi\Model\Orders\CardResponse":11:{s:2:"id";N;s:4:"name";N;s:15:"billing_address";N;s:12:"last_n_chars";N;s:11:"last_digits";s:4:"0010";s:5:"brand";s:4:"VISA";s:4:"type";s:7:"UNKNOWN";s:6:"issuer";N;s:3:"bin";N;s:21:"authentication_result";O:67:"OxidSolutionCatalysts\PayPalApi\Model\Orders\AuthenticationResponse":2:{s:15:"liability_shift";s:2:"NO";s:14:"three_d_secure";N;}s:10:"attributes";N;}s:6:"paypal";N;s:6:"wallet";N;s:4:"bank";N;s:6:"alipay";N;s:10:"bancontact";N;s:4:"blik";N;s:14:"boletobancario";N;s:3:"eps";N;s:7:"giropay";N;s:5:"ideal";N;s:10:"multibanco";N;s:4:"oxxo";N;s:4:"payu";N;s:3:"p24";N;s:16:"pay_upon_invoice";N;s:9:"safetypay";N;s:8:"satispay";N;s:7:"trustly";N;s:12:"verkkopankki";N;s:9:"wechatpay";N;s:9:"apple_pay";N;}s:6:"intent";N;s:22:"processing_instruction";s:14:"NO_INSTRUCTION";s:5:"payer";N;s:15:"expiration_time";N;s:14:"purchase_units";a:0:{}s:6:"status";N;s:5:"links";N;s:22:"credit_financing_offer";N;s:19:"application_context";N;s:11:"create_time";N;s:11:"update_time";N;}';

    private $cmpiLookupError = 'O:50:"OxidSolutionCatalysts\PayPalApi\Model\Orders\Order":13:{s:2:"id";N;s:14:"payment_source";O:66:"OxidSolutionCatalysts\PayPalApi\Model\Orders\PaymentSourceResponse":24:{s:4:"card";O:57:"OxidSolutionCatalysts\PayPalApi\Model\Orders\CardResponse":11:{s:2:"id";N;s:4:"name";N;s:15:"billing_address";N;s:12:"last_n_chars";N;s:11:"last_digits";s:4:"3346";s:5:"brand";s:4:"VISA";s:4:"type";s:6:"CREDIT";s:6:"issuer";N;s:3:"bin";N;s:21:"authentication_result";O:67:"OxidSolutionCatalysts\PayPalApi\Model\Orders\AuthenticationResponse":2:{s:15:"liability_shift";s:2:"NO";s:14:"three_d_secure";N;}s:10:"attributes";N;}s:6:"paypal";N;s:6:"wallet";N;s:4:"bank";N;s:6:"alipay";N;s:10:"bancontact";N;s:4:"blik";N;s:14:"boletobancario";N;s:3:"eps";N;s:7:"giropay";N;s:5:"ideal";N;s:10:"multibanco";N;s:4:"oxxo";N;s:4:"payu";N;s:3:"p24";N;s:16:"pay_upon_invoice";N;s:9:"safetypay";N;s:8:"satispay";N;s:7:"trustly";N;s:12:"verkkopankki";N;s:9:"wechatpay";N;s:9:"apple_pay";N;}s:6:"intent";N;s:22:"processing_instruction";s:14:"NO_INSTRUCTION";s:5:"payer";N;s:15:"expiration_time";N;s:14:"purchase_units";a:0:{}s:6:"status";N;s:5:"links";N;s:22:"credit_financing_offer";N;s:19:"application_context";N;s:11:"create_time";N;s:11:"update_time";N;}';

    private $cmpiAuthError = 'O:50:"OxidSolutionCatalysts\PayPalApi\Model\Orders\Order":13:{s:2:"id";N;s:14:"payment_source";O:66:"OxidSolutionCatalysts\PayPalApi\Model\Orders\PaymentSourceResponse":24:{s:4:"card";O:57:"OxidSolutionCatalysts\PayPalApi\Model\Orders\CardResponse":11:{s:2:"id";N;s:4:"name";N;s:15:"billing_address";N;s:12:"last_n_chars";N;s:11:"last_digits";s:4:"4542";s:5:"brand";s:4:"VISA";s:4:"type";s:6:"CREDIT";s:6:"issuer";N;s:3:"bin";N;s:21:"authentication_result";O:67:"OxidSolutionCatalysts\PayPalApi\Model\Orders\AuthenticationResponse":2:{s:15:"liability_shift";s:2:"NO";s:14:"three_d_secure";O:79:"OxidSolutionCatalysts\PayPalApi\Model\Orders\ThreeDSecureAuthenticationResponse":2:{s:21:"authentication_status";N;s:17:"enrollment_status";s:1:"Y";}}s:10:"attributes";N;}s:6:"paypal";N;s:6:"wallet";N;s:4:"bank";N;s:6:"alipay";N;s:10:"bancontact";N;s:4:"blik";N;s:14:"boletobancario";N;s:3:"eps";N;s:7:"giropay";N;s:5:"ideal";N;s:10:"multibanco";N;s:4:"oxxo";N;s:4:"payu";N;s:3:"p24";N;s:16:"pay_upon_invoice";N;s:9:"safetypay";N;s:8:"satispay";N;s:7:"trustly";N;s:12:"verkkopankki";N;s:9:"wechatpay";N;s:9:"apple_pay";N;}s:6:"intent";N;s:22:"processing_instruction";s:14:"NO_INSTRUCTION";s:5:"payer";N;s:15:"expiration_time";N;s:14:"purchase_units";a:0:{}s:6:"status";N;s:5:"links";N;s:22:"credit_financing_offer";N;s:19:"application_context";N;s:11:"create_time";N;s:11:"update_time";N;}';

    private $unavailableAuth = 'O:50:"OxidSolutionCatalysts\PayPalApi\Model\Orders\Order":13:{s:2:"id";N;s:14:"payment_source";O:66:"OxidSolutionCatalysts\PayPalApi\Model\Orders\PaymentSourceResponse":24:{s:4:"card";O:57:"OxidSolutionCatalysts\PayPalApi\Model\Orders\CardResponse":11:{s:2:"id";N;s:4:"name";N;s:15:"billing_address";N;s:12:"last_n_chars";N;s:11:"last_digits";s:4:"8815";s:5:"brand";s:4:"VISA";s:4:"type";s:6:"CREDIT";s:6:"issuer";N;s:3:"bin";N;s:21:"authentication_result";O:67:"OxidSolutionCatalysts\PayPalApi\Model\Orders\AuthenticationResponse":2:{s:15:"liability_shift";s:7:"UNKNOWN";s:14:"three_d_secure";O:79:"OxidSolutionCatalysts\PayPalApi\Model\Orders\ThreeDSecureAuthenticationResponse":2:{s:21:"authentication_status";s:1:"U";s:17:"enrollment_status";s:1:"Y";}}s:10:"attributes";N;}s:6:"paypal";N;s:6:"wallet";N;s:4:"bank";N;s:6:"alipay";N;s:10:"bancontact";N;s:4:"blik";N;s:14:"boletobancario";N;s:3:"eps";N;s:7:"giropay";N;s:5:"ideal";N;s:10:"multibanco";N;s:4:"oxxo";N;s:4:"payu";N;s:3:"p24";N;s:16:"pay_upon_invoice";N;s:9:"safetypay";N;s:8:"satispay";N;s:7:"trustly";N;s:12:"verkkopankki";N;s:9:"wechatpay";N;s:9:"apple_pay";N;}s:6:"intent";N;s:22:"processing_instruction";s:14:"NO_INSTRUCTION";s:5:"payer";N;s:15:"expiration_time";N;s:14:"purchase_units";a:0:{}s:6:"status";N;s:5:"links";N;s:22:"credit_financing_offer";N;s:19:"application_context";N;s:11:"create_time";N;s:11:"update_time";N;}';

    private $bypassedAuth = 'O:50:"OxidSolutionCatalysts\PayPalApi\Model\Orders\Order":13:{s:2:"id";N;s:14:"payment_source";O:66:"OxidSolutionCatalysts\PayPalApi\Model\Orders\PaymentSourceResponse":24:{s:4:"card";O:57:"OxidSolutionCatalysts\PayPalApi\Model\Orders\CardResponse":11:{s:2:"id";N;s:4:"name";N;s:15:"billing_address";N;s:12:"last_n_chars";N;s:11:"last_digits";s:4:"8584";s:5:"brand";s:4:"VISA";s:4:"type";s:6:"CREDIT";s:6:"issuer";N;s:3:"bin";N;s:21:"authentication_result";O:67:"OxidSolutionCatalysts\PayPalApi\Model\Orders\AuthenticationResponse":2:{s:15:"liability_shift";s:2:"NO";s:14:"three_d_secure";O:79:"OxidSolutionCatalysts\PayPalApi\Model\Orders\ThreeDSecureAuthenticationResponse":2:{s:21:"authentication_status";N;s:17:"enrollment_status";s:1:"B";}}s:10:"attributes";N;}s:6:"paypal";N;s:6:"wallet";N;s:4:"bank";N;s:6:"alipay";N;s:10:"bancontact";N;s:4:"blik";N;s:14:"boletobancario";N;s:3:"eps";N;s:7:"giropay";N;s:5:"ideal";N;s:10:"multibanco";N;s:4:"oxxo";N;s:4:"payu";N;s:3:"p24";N;s:16:"pay_upon_invoice";N;s:9:"safetypay";N;s:8:"satispay";N;s:7:"trustly";N;s:12:"verkkopankki";N;s:9:"wechatpay";N;s:9:"apple_pay";N;}s:6:"intent";N;s:22:"processing_instruction";s:14:"NO_INSTRUCTION";s:5:"payer";N;s:15:"expiration_time";N;s:14:"purchase_units";a:0:{}s:6:"status";N;s:5:"links";N;s:22:"credit_financing_offer";N;s:19:"application_context";N;s:11:"create_time";N;s:11:"update_time";N;}';

    public function testMissingPaymentSource(): void
    {
        $validator = new SCAValidator();

        $this->expectException(CardValidationException::class);
        $this->expectExceptionMessage(CardValidationException::byMissingPaymentSource()->getMessage());

        $validator->getCardAuthenticationResult(new PayPalApiOrder());
    }

    public function testNonCardPaymentSource(): void
    {
        $validator = new SCAValidator();

        $this->expectException(CardValidationException::class);
        $this->expectExceptionMessage(CardValidationException::byPaymentSource()->getMessage());

        $validator->getCardAuthenticationResult(unserialize($this->nonCardPaymentSource));
    }

    public function testMissingCardAutentication(): void
    {
        $validator = new SCAValidator();

        $this->assertNull($validator->getCardAuthenticationResult(unserialize($this->missingCardAuthentication)));
    }

    public function testAuthenticationResultSuccess()
    {
        $validator = new SCAValidator();

        $validationResult = $validator->getCardAuthenticationResult(unserialize($this->success3DCard));
        $this->assertSame(SCAValidator::LIABILITY_SHIFT_POSSIBLE, $validationResult->liability_shift);
        $this->assertSame(SCAValidator::AUTH_STATUS_SUCCESS, $validationResult->three_d_secure->authentication_status);
        $this->assertSame(SCAValidator::ENROLLMENT_STATUS_YES, $validationResult->three_d_secure->enrollment_status);
    }

    public function testIsCardSafeToUseFail()
    {
        $validator = new SCAValidator();

        $this->assertFalse($validator->isCardUsableForPayment(unserialize($this->missingCardAuthentication)));
    }

    /**
     * @dataProvider providerPayPalApiOrderResults
     */
    public function testIsCardSafeToUse(string $serializedOrder, string $assertMethod)
    {
        $validator = new SCAValidator();

        $this->$assertMethod($validator->isCardUsableForPayment(unserialize($serializedOrder)));
    }

    public function providerPayPalApiOrderResults(): array
    {
        return [
            'success' => [
                'success' => $this->success3DCard,
                'method' => 'assertTrue'
            ],
            'standardcard' => [
                'success' => $this->standardCard3D,
                'method' => 'assertTrue'
            ],
            'failesignature' => [
                'success' => $this->failedSignature,
                'method' => 'assertFalse'
            ],
            'failedauth' => [
                'success' => $this->failedAuthentication,
                'method' => 'assertFalse'
            ],
            'no_credemtial_prompt' => [
                'success' => $this->noPrompt,
                'method' => 'assertTrue'
            ],
            'timeout' => [
                'success' => $this->timeout,
                'method' => 'assertFalse'
            ],
            'not_enrolled' => [
                'success' => $this->notEnrolled,
                'method' => 'assertTrue'
            ],
            'system_not_available' => [
                'success' => $this->systemNotAvailable,
                'method' => 'assertTrue'
            ],
            'merchant_not_active' => [
                'success' => $this->merchantNotActive,
                'method' => 'assertFalse'
            ],
            'failed_3Ds1' => [
                'success' => $this->failedSignature3DS1,
                'method' => 'assertFalse'
            ],
            'cmpiLookupError' => [
                'success' => $this->cmpiLookupError,
                'method' => 'assertFalse'
            ],
            'cmpiAuthError' => [
                'success' => $this->cmpiAuthError,
                'method' => 'assertFalse'
            ],
            'unavailableAuth' => [
                'success' => $this->unavailableAuth,
                'method' => 'assertFalse'
            ],
            'bypassedAuth' => [
                'success' => $this->bypassedAuth,
                'method' => 'assertTrue'
            ],
        ];
    }
}
