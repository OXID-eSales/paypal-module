<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Service;

use OxidEsales\Eshop\Core\Session as EshopSession;
use PDO;
use Doctrine\DBAL\Query\QueryBuilder;
use OxidEsales\Eshop\Core\Registry as EshopRegistry;
use OxidEsales\Eshop\Application\Model\Shop as EshopModelShop;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;
use OxidSolutionCatalysts\PayPalApi\Model\Partner\ReferralDataReferralData;
use OxidSolutionCatalysts\PayPalApi\Model\Partner\ReferralDataPartnerConfigOverride;
use OxidSolutionCatalysts\PayPalApi\Partner\AccountBusinessEntity;
use OxidSolutionCatalysts\PayPalApi\Model\Partner\BusinessAddressDetail2;
use OxidSolutionCatalysts\PayPalApi\Model\Partner\BusinessTypeInfo;
use OxidSolutionCatalysts\PayPalApi\Model\Partner\ReferralDataRestApiIntegrationFirstPartyDetails;
use OxidSolutionCatalysts\PayPalApi\Model\Partner\ReferralDataRestApiIntegration;
use OxidSolutionCatalysts\PayPalApi\Model\Partner\ReferralDataOperation;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\ContextInterface;

final class PartnerRequestBuilder
{
    /** @var ContextInterface */
    private $context;

    /** @var QueryBuilderFactoryInterface */
    private $queryBuilderFactory;

    /**
     * @var EshopSession
     */
    private $eshopSession;

    public function __construct(
        EshopSession $eshopSession,
        ContextInterface $context,
        QueryBuilderFactoryInterface $queryBuilderFactory
    )
    {
        $this->eshopSession = $eshopSession;
        $this->context = $context;
        $this->queryBuilderFactory = $queryBuilderFactory;
    }
    
    public function getRequest(string $nonce, string $trackingId): ReferralDataReferralData
    {
        /** @var ReferralDataReferralData $request */
        $request = new ReferralDataReferralData();

        $langAbbr = EshopRegistry::getLang()->getLanguageAbbr();

        $request->tracking_id = $trackingId;
        $request->preferred_language_code = strtolower($langAbbr) . '-' . strtoupper($langAbbr);
        $request->operations = [
            $this->getOperationsRequest($nonce)
        ];
        $request->products = [
            'PAYMENT_METHODS',
            'PPCP'
        ];
        $request->legal_consents = [
            [
                'type' => 'SHARE_DATA_CONSENT',
                'granted' => true
            ]
        ];
        $request->capabilities = [
            'PAY_UPON_INVOICE'
        ];

        $request->initPartnerConfigOverride();
        $request->partner_config_override = $this->getPartnerConfigOverride();

        $request->initBusinessEntity();
        $request->business_entity = $this->getBusinessEntity();

        return $request;
    }

    private function getPartnerConfigOverride(): ReferralDataPartnerConfigOverride
    {
        /** @var ReferralDataPartnerConfigOverride $result */
        $result = new ReferralDataPartnerConfigOverride();

        $result->partner_logo_url = EshopRegistry::getConfig()->getOutUrl(null, true) . 'img/setup_logo.png';
        $result->return_url = EshopRegistry::getConfig()->getCurrentShopUrl(true) .
            '?cl=oscpaypalonboarding&fnc=returnFromSignup' .
            '&stoken=' . $this->eshopSession->getSessionChallengeToken() .
            '&force_admin_sid=' . $this->eshopSession->getId();

        $result->return_url_description = 'return to ' . EshopRegistry::getConfig()->getSslShopUrl();
        $result->action_renewal_url = EshopRegistry::getConfig()->getCurrentShopUrl(true) .
            '?cl=oscpaypalconfig' .
            '&stoken=' . $this->eshopSession->getSessionChallengeToken() .
            '&force_admin_sid=' . $this->eshopSession->getId();

        $result->show_add_credit_card = true;

        return $result;
    }

    private function getBusinessEntity(): AccountBusinessEntity
    {
        /** @var AccountBusinessEntity $entity */
        $entity = new AccountBusinessEntity();

        /** @var BusinessTypeInfo $businessType */
        $businessType = new BusinessTypeInfo();
        $businessType->type = BusinessTypeInfo::TYPE_PRIVATE_CORPORATION;

        $shop = oxNew(EshopModelShop::class);
        $shop->load($this->context->getCurrentShopId());

        /** @var BusinessAddressDetail2 $address */
        $address = new BusinessAddressDetail2();
        $address->type = BusinessAddressDetail2::TYPE_WORK;
        $address->address_line_1 = $shop->getFieldData('oxstreet');
        $address->admin_area_1 = $shop->getFieldData('oxcity');
        $address->postal_code = $shop->getFieldData('oxzip');
        $address->country_code = $this->getCountryCodeByCountryName($shop->getFieldData('oxcountry'));

        $entity->addresses = [
            $address
        ];

        return $entity;
    }

    private function getOperationsRequest(string $nonce): ReferralDataOperation
    {
        /** @var ReferralDataOperation $operation */
        $operation = new ReferralDataOperation();
        $operation->operation = $operation::OPERATION_API_INTEGRATION;
        $operation->initApiIntegrationPreference()
            ->initRestApiIntegration();

        /** @var ReferralDataRestApiIntegration $details */
        $details = $operation->api_integration_preference->rest_api_integration;
        $details->integration_method = ReferralDataRestApiIntegration::INTEGRATION_METHOD_PAYPAL;
        $details->integration_type = ReferralDataRestApiIntegration::INTEGRATION_TYPE_FIRST_PARTY;

        /** @var ReferralDataRestApiIntegrationFirstPartyDetails $firstPartyDetails */
        $firstPartyDetails = $details->initFirstPartyDetails();
        $firstPartyDetails->features = [
            'PAYMENT',
            'REFUND'
        ];
        $firstPartyDetails->seller_nonce = $nonce;

        return $operation;
    }

    //TODO: improve, we have only country name in oxshops table and need to find out in which language
    private function getCountryCodeByCountryName(string $name): string
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->queryBuilderFactory->create();

        $queryBuilder->select('oxisoalpha2')
            ->from('oxcountry')
            ->where('oxtitle = :oxtitle')
            ->orWhere('oxtitle_1 = :oxtitle')
            ->orWhere('oxtitle_3 = :oxtitle');

        $code = $queryBuilder->setParameters(['oxtitle' => $name])
            ->setMaxResults(1)
            ->execute()
            ->fetch(PDO::FETCH_COLUMN);

        return (string) $code;
    }
}