<?php

namespace OxidSolutionCatalysts\PayPal\Controller;

use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Request;
use OxidSolutionCatalysts\PayPal\Core\ServiceFactory;
use OxidEsales\Eshop\Application\Controller\FrontendController;

class VaultingTokenController extends FrontendController
{
    protected $vaultingService;

    public function generateSetupToken()
    {
        $vaultingService = $this->getVaultingService();
        $card = (bool)Registry::get(Request::class)->getRequestEscapedParameter("card");
        $setupToken = $vaultingService->createVaultSetupToken($card);

        if ($this->storeSetupToken($setupToken["id"])) {
            $this->outputJson($setupToken);
        }
    }

    /**
     * Generate a Payment Token using a previously generated setup token
     * @return void
     */
    public function generatePaymentToken()
    {
        $vaultingService = $this->getVaultingService();
        $setupToken = Registry::getRequest()->getRequestParameter("token");

        $paymentToken = $vaultingService->createVaultPaymentToken($setupToken);
        if($this->storePayPalUserId($paymentToken["customer"]["id"])) {
            $this->outputJson(["state" => "SUCCESS"]);
        }else {
            $this->outputJson(["state" => "ERROR"]);
        }
    }

    public function generateAccessTokenFromCustomerId($payPalCustomerId)
    {
        $vaultingService = $this->getVaultingService();

        $this->outputJson($vaultingService->generateAccessTokenFromCustomerId($payPalCustomerId));
    }

    protected function getVaultingService()
    {
        if(!$this->vaultingService) {
            $this->vaultingService = Registry::get(ServiceFactory::class)->getVaultingService();
        }

        return $this->vaultingService;
    }

    /**
     * @param string $token
     * @return bool
     */
    protected function storeSetupToken($token)
    {
        $oUser = $this->getUser();
        $oUser->oxuser__oscpaypalvaultsetuptoken = new Field($token);

        return $oUser->save();
    }

    /**
     * @param string $id
     * @return bool
     */
    protected function storePayPalUserId($id)
    {
        $user = $this->getUser();
        $user->oxuser__oscpaypalcustomerid = new Field($id);

        return $user->save();
    }

    /**
     * Encodes and sends response as json
     *
     * @param $response
     */
    protected function outputJson($response)
    {
        $utils = Registry::getUtils();
        $utils->setHeader('Content-Type: application/json; charset=utf-8');
        $utils->showMessageAndExit(json_encode($response));
    }
}