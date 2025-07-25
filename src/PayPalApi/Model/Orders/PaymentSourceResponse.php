<?php

namespace OxidSolutionCatalysts\PayPalApi\Model\Orders;

use JsonSerializable;
use OxidSolutionCatalysts\PayPalApi\Model\BaseModel;
use Webmozart\Assert\Assert;

/**
 * The payment source used to fund the payment.
 *
 * generated from: MerchantsCommonComponentsSpecification-v1-schema-payment_source_response.json
 */
class PaymentSourceResponse implements JsonSerializable
{
    use BaseModel;

    /**
     * The payment card to use to fund a payment. Card can be a credit or debit card.
     *
     * @var CardResponse | null
     */
    public $card;

    /**
     * The PayPal Wallet response.
     *
     * @var PaypalWalletResponse | null
     */
    public $paypal;

    /**
     * The customer's wallet used to fund the transaction.
     *
     * @var WalletsResponse | null
     */
    public $wallet;

    /**
     * The bank source used to fund the payment.
     *
     * @var BankResponse | null
     */
    public $bank;

    /**
     * Information used to pay using Alipay.
     *
     * @var Alipay | null
     */
    public $alipay;

    /**
     * Information used to pay Bancontact.
     *
     * @var Bancontact | null
     */
    public $bancontact;

    /**
     * Information used to pay using BLIK.
     *
     * @var Blik | null
     */
    public $blik;

    /**
     * Information used to pay using Boleto Bancário.
     *
     * @var Boletobancario | null
     */
    public $boletobancario;

    /**
     * Information used to pay using eps.
     *
     * @var Eps | null
     */
    public $eps;

    /**
     * Information needed to pay using giropay.
     *
     * @var Giropay | null
     */
    public $giropay;

    /**
     * Information used to pay using iDEAL.
     *
     * @var Ideal | null
     */
    public $ideal;

    /**
     * Information used to pay using Multibanco.
     *
     * @var Multibanco | null
     */
    public $multibanco;

    /**
     * Information used to pay using MyBank.
     *
     * @var Mybank | null
     */
    public $mybank;

    /**
     * Information used to pay using OXXO.
     *
     * @var Oxxo | null
     */
    public $oxxo;

    /**
     * Information needed to pay using PayU.
     *
     * @var Payu | null
     */
    public $payu;

    /**
     * Information used to pay using P24(Przelewy24).
     *
     * @var PTwoFour | null
     */
    public $p24;

    /**
     * Information used to pay using Pui.
     *
     * @var Pui | null
     */
    public $pay_upon_invoice;

    /**
     * Information needed to pay using SafetyPay.
     *
     * @var Safetypay | null
     */
    public $safetypay;

    /**
     * Information used to pay using SatisPay.
     *
     * @var Satispay | null
     */
    public $satispay;

    /**
     * Information used to pay using Sofort.
     *
     * @var Sofort | null
     */
    public $sofort;

    /**
     * Information needed to pay using Trustly.
     *
     * @var Trustly | null
     */
    public $trustly;

    /**
     * Information used to pay using Verkkopankki (Finnish Online Banking).
     *
     * @var Verkkopankki | null
     */
    public $verkkopankki;

    /**
     * Information needed to pay using WeChat Pay.
     *
     * @var Wechatpay | null
     */
    public $wechatpay;

    /**
     * Information needed to pay using ApplePay.
     *
     * @var ApplePay | null
     */
    public $apple_pay;
    /**
     * Information needed to pay using ApplePay.
     *
     * @var ApplePay | null
     */
    public $google_pay;

    public $experience_context;

    public function validate($from = null)
    {
        $within = isset($from) ? "within $from" : "";
        !isset($this->card) || Assert::isInstanceOf(
            $this->card,
            CardResponse::class,
            "card in PaymentSourceResponse must be instance of CardResponse $within"
        );
        !isset($this->card) ||  $this->card->validate(PaymentSourceResponse::class);
        !isset($this->paypal) || Assert::isInstanceOf(
            $this->paypal,
            PaypalWalletResponse::class,
            "paypal in PaymentSourceResponse must be instance of PaypalWalletResponse $within"
        );
        !isset($this->paypal) ||  $this->paypal->validate(PaymentSourceResponse::class);
        !isset($this->wallet) || Assert::isInstanceOf(
            $this->wallet,
            WalletsResponse::class,
            "wallet in PaymentSourceResponse must be instance of WalletsResponse $within"
        );
        !isset($this->wallet) ||  $this->wallet->validate(PaymentSourceResponse::class);
        !isset($this->bank) || Assert::isInstanceOf(
            $this->bank,
            BankResponse::class,
            "bank in PaymentSourceResponse must be instance of BankResponse $within"
        );
        !isset($this->bank) ||  $this->bank->validate(PaymentSourceResponse::class);
        !isset($this->alipay) || Assert::isInstanceOf(
            $this->alipay,
            Alipay::class,
            "alipay in PaymentSourceResponse must be instance of Alipay $within"
        );
        !isset($this->alipay) ||  $this->alipay->validate(PaymentSourceResponse::class);
        !isset($this->bancontact) || Assert::isInstanceOf(
            $this->bancontact,
            Bancontact::class,
            "bancontact in PaymentSourceResponse must be instance of Bancontact $within"
        );
        !isset($this->bancontact) ||  $this->bancontact->validate(PaymentSourceResponse::class);
        !isset($this->blik) || Assert::isInstanceOf(
            $this->blik,
            Blik::class,
            "blik in PaymentSourceResponse must be instance of Blik $within"
        );
        !isset($this->blik) ||  $this->blik->validate(PaymentSourceResponse::class);
        !isset($this->boletobancario) || Assert::isInstanceOf(
            $this->boletobancario,
            Boletobancario::class,
            "boletobancario in PaymentSourceResponse must be instance of Boletobancario $within"
        );
        !isset($this->boletobancario) ||  $this->boletobancario->validate(PaymentSourceResponse::class);
        !isset($this->eps) || Assert::isInstanceOf(
            $this->eps,
            Eps::class,
            "eps in PaymentSourceResponse must be instance of Eps $within"
        );
        !isset($this->eps) ||  $this->eps->validate(PaymentSourceResponse::class);
        !isset($this->giropay) || Assert::isInstanceOf(
            $this->giropay,
            Giropay::class,
            "giropay in PaymentSourceResponse must be instance of Giropay $within"
        );
        !isset($this->giropay) ||  $this->giropay->validate(PaymentSourceResponse::class);
        !isset($this->ideal) || Assert::isInstanceOf(
            $this->ideal,
            Ideal::class,
            "ideal in PaymentSourceResponse must be instance of Ideal $within"
        );
        !isset($this->ideal) ||  $this->ideal->validate(PaymentSourceResponse::class);
        !isset($this->multibanco) || Assert::isInstanceOf(
            $this->multibanco,
            Multibanco::class,
            "multibanco in PaymentSourceResponse must be instance of Multibanco $within"
        );
        !isset($this->multibanco) ||  $this->multibanco->validate(PaymentSourceResponse::class);
        !isset($this->mybank) || Assert::isInstanceOf(
            $this->mybank,
            Mybank::class,
            "mybank in PaymentSourceResponse must be instance of Mybank $within"
        );
        !isset($this->mybank) ||  $this->mybank->validate(PaymentSourceResponse::class);
        !isset($this->oxxo) || Assert::isInstanceOf(
            $this->oxxo,
            Oxxo::class,
            "oxxo in PaymentSourceResponse must be instance of Oxxo $within"
        );
        !isset($this->oxxo) ||  $this->oxxo->validate(PaymentSourceResponse::class);
        !isset($this->payu) || Assert::isInstanceOf(
            $this->payu,
            Payu::class,
            "payu in PaymentSourceResponse must be instance of Payu $within"
        );
        !isset($this->payu) ||  $this->payu->validate(PaymentSourceResponse::class);
        !isset($this->p24) || Assert::isInstanceOf(
            $this->p24,
            PTwoFour::class,
            "p24 in PaymentSourceResponse must be instance of PTwoFour $within"
        );
        !isset($this->p24) ||  $this->p24->validate(PaymentSourceResponse::class);
        !isset($this->pay_upon_invoice) || Assert::isInstanceOf(
            $this->pay_upon_invoice,
            Pui::class,
            "pay_upon_invoice in PaymentSourceResponse must be instance of Pui $within"
        );
        !isset($this->pay_upon_invoice) ||  $this->pay_upon_invoice->validate(PaymentSourceResponse::class);
        !isset($this->safetypay) || Assert::isInstanceOf(
            $this->safetypay,
            Safetypay::class,
            "safetypay in PaymentSourceResponse must be instance of Safetypay $within"
        );
        !isset($this->safetypay) ||  $this->safetypay->validate(PaymentSourceResponse::class);
        !isset($this->satispay) || Assert::isInstanceOf(
            $this->satispay,
            Satispay::class,
            "satispay in PaymentSourceResponse must be instance of Satispay $within"
        );
        !isset($this->satispay) ||  $this->satispay->validate(PaymentSourceResponse::class);
        !isset($this->sofort) || Assert::isInstanceOf(
            $this->sofort,
            Sofort::class,
            "sofort in PaymentSourceResponse must be instance of Sofort $within"
        );
        !isset($this->sofort) ||  $this->sofort->validate(PaymentSourceResponse::class);
        !isset($this->trustly) || Assert::isInstanceOf(
            $this->trustly,
            Trustly::class,
            "trustly in PaymentSourceResponse must be instance of Trustly $within"
        );
        !isset($this->trustly) ||  $this->trustly->validate(PaymentSourceResponse::class);
        !isset($this->verkkopankki) || Assert::isInstanceOf(
            $this->verkkopankki,
            Verkkopankki::class,
            "verkkopankki in PaymentSourceResponse must be instance of Verkkopankki $within"
        );
        !isset($this->verkkopankki) ||  $this->verkkopankki->validate(PaymentSourceResponse::class);
        !isset($this->wechatpay) || Assert::isInstanceOf(
            $this->wechatpay,
            Wechatpay::class,
            "wechatpay in PaymentSourceResponse must be instance of Wechatpay $within"
        );
        !isset($this->wechatpay) ||  $this->wechatpay->validate(PaymentSourceResponse::class);
        !isset($this->apple_pay) || Assert::isInstanceOf(
            $this->apple_pay,
            ApplePay::class,
            "apple_pay in PaymentSourceResponse must be instance of ApplePay $within"
        );
        !isset($this->apple_pay) ||  $this->apple_pay->validate(PaymentSourceResponse::class);
        !isset($this->google_pay) || Assert::isInstanceOf(
            $this->google_pay,
            GooglePay::class,
            "google_pay in PaymentSourceResponse must be instance of GooglePay $within"
        );
        !isset($this->google_pay) ||  $this->google_pay->validate(PaymentSourceResponse::class);
    }

    private function map(array $data)
    {
        if (isset($data['card'])) {
            $this->card = new CardResponse($data['card']);
        }
        if (isset($data['paypal'])) {
            $this->paypal = new PaypalWalletResponse($data['paypal']);
        }
        if (isset($data['wallet'])) {
            $this->wallet = new WalletsResponse($data['wallet']);
        }
        if (isset($data['bank'])) {
            $this->bank = new BankResponse($data['bank']);
        }
        if (isset($data['alipay'])) {
            $this->alipay = new Alipay($data['alipay']);
        }
        if (isset($data['bancontact'])) {
            $this->bancontact = new Bancontact($data['bancontact']);
        }
        if (isset($data['blik'])) {
            $this->blik = new Blik($data['blik']);
        }
        if (isset($data['boletobancario'])) {
            $this->boletobancario = new Boletobancario($data['boletobancario']);
        }
        if (isset($data['eps'])) {
            $this->eps = new Eps($data['eps']);
        }
        if (isset($data['giropay'])) {
            $this->giropay = new Giropay($data['giropay']);
        }
        if (isset($data['ideal'])) {
            $this->ideal = new Ideal($data['ideal']);
        }
        if (isset($data['multibanco'])) {
            $this->multibanco = new Multibanco($data['multibanco']);
        }
        if (isset($data['mybank'])) {
            $this->mybank = new Mybank($data['mybank']);
        }
        if (isset($data['oxxo'])) {
            $this->oxxo = new Oxxo($data['oxxo']);
        }
        if (isset($data['payu'])) {
            $this->payu = new Payu($data['payu']);
        }
        if (isset($data['p24'])) {
            $this->p24 = new PTwoFour($data['p24']);
        }
        if (isset($data['pay_upon_invoice'])) {
            $this->pay_upon_invoice = new Pui($data['pay_upon_invoice']);
        }
        if (isset($data['safetypay'])) {
            $this->safetypay = new Safetypay($data['safetypay']);
        }
        if (isset($data['satispay'])) {
            $this->satispay = new Satispay($data['satispay']);
        }
        if (isset($data['sofort'])) {
            $this->sofort = new Sofort($data['sofort']);
        }
        if (isset($data['trustly'])) {
            $this->trustly = new Trustly($data['trustly']);
        }
        if (isset($data['verkkopankki'])) {
            $this->verkkopankki = new Verkkopankki($data['verkkopankki']);
        }
        if (isset($data['wechatpay'])) {
            $this->wechatpay = new Wechatpay($data['wechatpay']);
        }
        if (isset($data['apple_pay'])) {
            $this->apple_pay = new ApplePay($data['apple_pay']);
        }
        if (isset($data['google_pay'])) {
            $this->google_pay = new GooglePay($data['google_pay']);
        }
    }

    public function __construct(array $data = null)
    {
        if (isset($data)) {
            $this->map($data);
        }
    }

    public function initCard(): CardResponse
    {
        return $this->card = new CardResponse();
    }

    public function initPaypal(): PaypalWalletResponse
    {
        return $this->paypal = new PaypalWalletResponse();
    }

    public function initWallet(): WalletsResponse
    {
        return $this->wallet = new WalletsResponse();
    }

    public function initBank(): BankResponse
    {
        return $this->bank = new BankResponse();
    }

    public function initAlipay(): Alipay
    {
        return $this->alipay = new Alipay();
    }

    public function initBancontact(): Bancontact
    {
        return $this->bancontact = new Bancontact();
    }

    public function initBlik(): Blik
    {
        return $this->blik = new Blik();
    }

    public function initBoletobancario(): Boletobancario
    {
        return $this->boletobancario = new Boletobancario();
    }

    public function initEps(): Eps
    {
        return $this->eps = new Eps();
    }

    public function initGiropay(): Giropay
    {
        return $this->giropay = new Giropay();
    }

    public function initIdeal(): Ideal
    {
        return $this->ideal = new Ideal();
    }

    public function initMultibanco(): Multibanco
    {
        return $this->multibanco = new Multibanco();
    }

    public function initMybank(): Mybank
    {
        return $this->mybank = new Mybank();
    }

    public function initOxxo(): Oxxo
    {
        return $this->oxxo = new Oxxo();
    }

    public function initPayu(): Payu
    {
        return $this->payu = new Payu();
    }

    public function initP24(): PTwoFour
    {
        return $this->p24 = new PTwoFour();
    }

    public function initPui(): Pui
    {
        return $this->pay_upon_invoice = new Pui();
    }

    public function initSafetypay(): Safetypay
    {
        return $this->safetypay = new Safetypay();
    }

    public function initSatispay(): Satispay
    {
        return $this->satispay = new Satispay();
    }

    public function initSofort(): Sofort
    {
        return $this->sofort = new Sofort();
    }

    public function initTrustly(): Trustly
    {
        return $this->trustly = new Trustly();
    }

    public function initVerkkopankki(): Verkkopankki
    {
        return $this->verkkopankki = new Verkkopankki();
    }

    public function initWechatpay(): Wechatpay
    {
        return $this->wechatpay = new Wechatpay();
    }

    public function initApplePay(): ApplePay
    {
        return $this->apple_pay = new ApplePay();
    }
    public function initGooglePay(): GooglePay
    {
        return $this->google_pay = new GooglePay();
    }
}
