<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Codeception\Page;

use Facebook\WebDriver\Exception\ElementNotVisibleException;
use OxidEsales\Codeception\Page\Checkout\OrderCheckout;
use OxidEsales\Codeception\Page\Page;
use OxidSolutionCatalysts\PayPal\Tests\Codeception\AcceptanceTester;

/**
 * Class PayPalLogin
 * @package OxidEsales\PayPalModule\Tests\Codeception\Page
 */
class PayPalLogin extends Page
{
    public $userLoginEmail = '#email';
    public $userPassword = '#password';

    public $continueButton = '#continueButton';
    public $nextButton = '#btnNext';
    public $loginButton = '#btnLogin';
    public $newConfirmButton = '#confirmButtonTop';

    public $oneTouchNotNowLink = '#notNowLink';

    public $spinner = '#spinner';

    public $gdprContainer = "#gdpr-container";
    public $gdprCookieBanner = "#gdprCookieBanner";
    public $acceptAllPaypalCookies = "#acceptAllButton";

    public $payLaterBubble = "//button[contains(@class, 'ppvx_icon-button')]";

    public $loginSection = "#loginSection";
    public $oldLoginSection = "#passwordSection";

    public $cancelLink = "#cancelLink";
    public $returnToShop = "#cancel_return";

    public $breadCrumb = "#breadCrumb";

    public $paymentConfirmButton = "#payment-submit-btn";
    public $globalSpinner = "//div[@data-testid='global-spinner']";
    public $preloaderSpinner = "//div[@id='preloaderSpinner']";

    public $paypalBannerContainer = "//div[@id='paypal-installment-banner-container']";

    public $backToInputEmail = "#backToInputEmailLink";
    public $errorSection = '#notifications #pageLevelErrors';
    public $splitPassword = '#splitPassword';
    public $splitEmail = '#splitEmail';
    public $rememberedEmail = "//div[@class='profileRememberedEmail']";

    public $agreeConnectButton = '#agreeAndConnectButton';

    /** @var string */
    private $token = '';

    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * NOTE: we use GET here  but in real life, POST is used for Buttons. GET will reuse any sid cookie
     *       that's already present but it will not force the shop into returning a fresh sid cookie.
     *       When testing details page express without former session, use openPayPalApprovalPageAsAnonymousUser method.
     */
    public function openPayPalApprovalPage(AcceptanceTester $I, string $addParams = ''): self
    {
        $I->amOnPage('/index.php?cl=oscpaypalproxy&fnc=createOrder' . $addParams);
        $I->see('checkout');

        $text = $I->grabTextFrom('//body');
        $response = json_decode($text);
        $urlApproveWithPayPal = '';
        $this->token = (string) $response->id;
        $links = $response->links;
        foreach ($links as $link) {
            if ('approve' == $link->rel) {
                $urlApproveWithPayPal = $link->href;
            }
        }

        $I->amOnUrl($urlApproveWithPayPal);

        return $this;
    }

    public function openPayPalApprovalPageAsAnonymousUser(
        AcceptanceTester $I,
        string $addParams = '',
        array $headers = []
    ): self {
        //send this as post request
        $I->postTo(
            $I->getShopUrl() . '/index.php?cl=oscpaypalproxy&fnc=createOrder&context=continue' . $addParams,
            $headers
        );

        $sid = $I->extractSidFromResponseCookies();
        $I->setCookie('sid', $sid);
        $I->setCookie('sid_key', 'oxid');

        $response = $I->grabJsonResponseAsArray();
        $urlApproveWithPayPal = '';
        $this->token = (string) $response['id'];
        foreach ($response['links'] as $link) {
            if ('approve' == $link['rel']) {
                $urlApproveWithPayPal = $link['href'];
            }
        }

        $I->amOnUrl($urlApproveWithPayPal);

        return $this;
    }

    /**
     * @param string $userName
     * @param string $userPassword
     *
     * @return OrderCheckout
     */
    public function checkoutWithStandardPayPal(string $userName, string $userPassword): OrderCheckout
    {
        $I = $this->user;

        $this->loginToPayPal($userName, $userPassword);

        $this->confirmPayPal();

        //retry
        $this->waitForSpinnerDisappearance();
        $this->confirmPayPal();

        return new OrderCheckout($I);
    }

    public function loginToPayPal(string $userName, string $userPassword): void
    {
        $I = $this->user;

        $this->waitForPayPalPage();
        $this->removeCookieConsent();

        if (
            $I->seePageHasElement($this->splitPassword)
            && $I->seePageHasElement($this->rememberedEmail)
            && $I->seePageHasElement($this->backToInputEmail)
        ) {
            try {
                $I->seeAndClick($this->backToInputEmail);
                $I->waitForDocumentReadyState();
                $this->waitForSpinnerDisappearance();
                $I->waitForElementNotVisible($this->backToInputEmail);
            } catch (ElementNotVisibleException $e) {
                //nothing to be done, element was not visible
            }
        }

        if ($I->seePageHasElement($this->oldLoginSection)) {
            $I->waitForElementVisible($this->userLoginEmail, 5);
            $I->fillField($this->userLoginEmail, $userName);

            if ($I->seePageHasElement($this->nextButton)) {
                $I->retryClick($this->nextButton);
            }

            $I->waitForElementVisible($this->userPassword, 5);
            $I->fillField($this->userPassword, $userPassword);
            $I->retryClick($this->loginButton);
        }

        if ($I->seePageHasElement($this->oneTouchNotNowLink)) {
            $I->retryClick($this->oneTouchNotNowLink);
        }

        $this->waitForSpinnerDisappearance();
        $this->removeCookieConsent();
        $this->waitForSpinnerDisappearance();
        $I->wait(3);
    }


    public function loginToPayPalOnboarding(string $userName, string $userPassword): void
    {
        $I = $this->user;

        $this->waitForPayPalPage();
        $this->removeCookieConsent();

        if ($I->seePageHasElement($this->userLoginEmail)) {
            $I->waitForElementVisible($this->userLoginEmail, 5);
            $I->fillField($this->userLoginEmail, $userName);

            if ($I->seePageHasElement($this->continueButton)) {
                $I->retryClick($this->continueButton);
            }
            $I->waitForElementVisible($this->userPassword, 10);
            $I->retryFillField($this->userPassword, $userPassword);
            $I->seeElement($this->loginButton);
            $I->retryClick($this->loginButton);
        }

        if ($I->seePageHasElement($this->userPassword)) {
            $I->fillField($this->userPassword, $userPassword);
            $I->retryClick($this->loginButton);
        }

        $this->waitForSpinnerDisappearance();
        $this->removeCookieConsent();
        $this->waitForSpinnerDisappearance();
        $I->wait(3);
    }

    /**
     * @param string $userName
     * @param string $userPassword
     */
    public function approveExpressPayPal(string $userName, string $userPassword): void
    {
        $I = $this->user;

        $this->loginToPayPal($userName, $userPassword);

        $this->removePayLaterBubble();

        $I->seeElement('//button[@id="change-shipping"]');
        $I->click('//button[@id="change-shipping"]');
        $I->wait(2);
        $I->seeElement('//select[@id="shippingDropdown"]');

        $this->confirmPayPal();
    }

    /**
     * @param string $userName
     * @param string $userPassword
     */
    public function approveStandardPayPal(string $userName, string $userPassword): void
    {
        $I = $this->user;

        $this->loginToPayPal($userName, $userPassword);

        $this->removePayLaterBubble();

        $I->seeElement('//button[@id="change-shipping"]');
        $I->click('//button[@id="change-shipping"]');
        $I->wait(1);
        $I->dontSeeElement('//select[@id="shippingDropdown"]');

        $this->confirmPayPal();
        $I->waitForPageLoad();
    }

    public function confirmPayPal()
    {
        $I = $this->user;

        $this->waitForSpinnerDisappearance();
        $this->removeCookieConsent();

        if ($I->seePageHasElement(substr($this->newConfirmButton, 1))) {
            $I->retryClick($this->newConfirmButton);
            $I->waitForDocumentReadyState();
            $I->waitForElementNotVisible($this->globalSpinner, 60);
            $I->wait(10);
        }

        if ($I->seePageHasElement("//input[@id='" . substr($this->newConfirmButton, 1) . "']")) {
            $I->executeJS("document.getElementById('" . substr($this->newConfirmButton, 1) . "').click();");
            $I->waitForDocumentReadyState();
            $I->waitForElementNotVisible($this->globalSpinner, 60);
            $I->wait(10);
        }

        if ($I->seePageHasElement($this->paymentConfirmButton)) {
            $I->retryClick($this->paymentConfirmButton);
            $I->waitForDocumentReadyState();
            $I->waitForElementNotVisible($this->globalSpinner, 60);
            $I->wait(10);
        }
    }

    public function waitForPayPalPage(): PayPalLogin
    {
        $I = $this->user;

        $I->waitForDocumentReadyState();
        $I->waitForElementNotVisible($this->spinner, 90);
        $I->wait(10);

        if ($I->seePageHasElement($this->loginSection)) {
            $I->retryClick('.loginRedirect a');
            $I->waitForDocumentReadyState();
            $this->waitForSpinnerDisappearance();
            $I->waitForElementNotVisible($this->loginSection);
        }

        return $this;
    }

    /**
     * Click cancel on payPal side to return to shop.
     */
    public function cancelPayPal(bool $isRetry = false): void
    {
        $I = $this->user;

        if ($I->seePageHasElement($this->cancelLink)) {
            $I->amOnUrl($I->grabAttributeFrom($this->cancelLink, 'href'));
            $I->waitForDocumentReadyState();
        } elseif ($I->seePageHasElement($this->returnToShop)) {
            $I->amOnUrl($I->grabAttributeFrom($this->returnToShop, 'href'));
            $I->waitForDocumentReadyState();
        }

        //we should be redirected back to shop at this point
        if (
            $I->dontSeeElement($this->breadCrumb) &&
            $I->dontSeeElement(strtolower($this->breadCrumb)) &&
            !$isRetry
        ) {
            $this->cancelPayPal(true);
        }
    }

    private function acceptAllPayPalCookies()
    {
        $I = $this->user;

        // In case we have cookie message, accept all cookies
        if ($I->seePageHasElement($this->acceptAllPaypalCookies)) {
            $I->retryClick($this->acceptAllPaypalCookies);
            $I->waitForElementNotVisible($this->acceptAllPaypalCookies);
        }
    }

    private function waitForSpinnerDisappearance()
    {
        $I = $this->user;
        $I->waitForElementNotVisible($this->preloaderSpinner, 30);
        $I->waitForElementNotVisible($this->globalSpinner, 30);
        $I->waitForElementNotVisible($this->spinner, 30);
    }

    private function removeCookieConsent()
    {
        $I = $this->user;
        if ($I->seePageHasElement($this->gdprContainer)) {
            $I->executeJS("document.getElementById('" . substr($this->gdprContainer, 1) . "').remove();");
        }
        if ($I->seePageHasElement($this->gdprCookieBanner)) {
            $I->executeJS("document.getElementById('" . substr($this->gdprCookieBanner, 1) . "').remove();");
        }
    }

    private function removePayLaterBubble()
    {
        $I = $this->user;
        if ($I->seePageHasElement($this->payLaterBubble)) {
            $I->click($this->payLaterBubble);
        }
    }

}
