<?php

/**
 * This file is part of OXID eSales PayPal module.
 *
 * OXID eSales PayPal module is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * OXID eSales PayPal module is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with OXID eSales PayPal module.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @link      http://www.oxid-esales.com
 * @copyright (C) OXID eSales AG 2003-2020
 */

namespace OxidProfessionalServices\PayPal\Controller\Admin;

use OxidEsales\Eshop\Application\Controller\Admin\AdminController;
use OxidEsales\Eshop\Application\Model\Article;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Exception\DatabaseErrorException;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\UtilsObject;
use OxidProfessionalServices\PayPal\Api\Exception\ApiException;
use OxidProfessionalServices\PayPal\Api\Model\Catalog\Product;
use OxidProfessionalServices\PayPal\Api\Model\Subscriptions\BillingCycle;
use OxidProfessionalServices\PayPal\Api\Model\Subscriptions\Frequency;
use OxidProfessionalServices\PayPal\Api\Model\Subscriptions\Plan;
use OxidProfessionalServices\PayPal\Controller\Admin\Service\CatalogService;
use OxidProfessionalServices\PayPal\Controller\Admin\Service\SubscriptionService;
use OxidProfessionalServices\PayPal\Core\Currency;
use OxidProfessionalServices\PayPal\Core\ServiceFactory;
use OxidProfessionalServices\PayPal\Model\Category;
use OxidProfessionalServices\PayPal\Repository\SubscriptionRepository;

/**
 * Controller for admin > PayPal/Configuration page
 */
class PayPalSubscribeController extends AdminController
{
    /**
     * The Product from PayPal's API
     * Caching the linked object to reduce calls to paypal api
     * @var Product
     */
    private $linkedObject;

    /**
     * The Linked data stored in OXID db
     * Caching the linked object to reduce calls to paypal api
     * @var array
     */
    private $linkedProduct;


    /**
     * The lined subscription plan called from PayPal API
     * @var Plan
     */
    private $subscriptionPlan;

    /**
     * @var SubscriptionRepository
     */
    private $repository;

    public function __construct()
    {
        parent::__construct();
        $this->_sThisTemplate = 'pspaypalsubscribe.tpl';
        $this->repository = new SubscriptionRepository();
    }

    /**
     * @return object
     */
    public function getEditObject()
    {
        return $this->repository->getEditObject(Registry::getRequest()->getRequestParameter('oxid'));
    }

    /**
     * @return array
     */
    public function getIntervalDefaults()
    {
        return [
            Frequency::INTERVAL_UNIT_DAY,
            Frequency::INTERVAL_UNIT_WEEK,
            Frequency::INTERVAL_UNIT_SEMI_MONTH,
            Frequency::INTERVAL_UNIT_MONTH,
            Frequency::INTERVAL_UNIT_YEAR
        ];
    }

    /**
     * @return array|string[]
     */
    public function getCurrencyCodes()
    {
        return Currency::getCurrencyCodes();
    }

    /**
     * @return array
     */
    public function getTenureTypeDefaults()
    {
        return [
            'REGULAR',
            'TRIAL'
        ];
    }

    /**
     * @return array
     */
    public function getTotalCycleDefaults()
    {
        $array = [];

        for ($i = 1; $i < 1000; $i++) {
            $array[] = $i;
        }

        return $array;
    }

    /**
     * @return bool
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @throws ApiException
     */
    public function hasSubscriptionPlan()
    {
        $this->setSubscriptionPlan();

        return !empty($this->subscriptionPlan);
    }

    public function getPayPalProductId()
    {
        return $this->linkedObject->id;
    }

    /**
     * @return bool|Plan
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @throws ApiException
     */
    public function setSubscriptionPlan($SelectSubscriptionPlanId = '')
    {
        if (!empty($this->subscriptionPlan)) {
            return $this->subscriptionPlan;
        }

        if (!$SelectSubscriptionPlanId) {
            $result = $this->repository->getSubscriptionIdPlanByProductId($this->linkedObject->id);
            $subscriptionPlanId = $result[0]['PAYPALSUBSCRIPTIONPLANID'];
        } else {
            $result = $this->repository->getSubscriptionIdPlanByProductIdSubscriptionPlanId(
                $this->linkedObject->id,
                $SelectSubscriptionPlanId
            );
            $subscriptionPlanId = $result['PAYPALSUBSCRIPTIONPLANID'];
        }

        if (empty($subscriptionPlanId)) {
            return false;
        }

        /** @var ServiceFactory $sf */
        $sf = Registry::get(ServiceFactory::class);
        $subscriptionPlan = $sf->getSubscriptionService()->showPlanDetails('string', $subscriptionPlanId, 1);

        if ($subscriptionPlan !== null) {
            $this->subscriptionPlan = $subscriptionPlan;
        }

        return $this->subscriptionPlan;
    }

    /**
     * @return bool
     * @throws ApiException
     */
    public function hasLinkedObject()
    {
        $this->setLinkedObject();
        return !empty($this->linkedObject);
    }

    /**
     * @return Product
     * @throws ApiException
     */
    public function getLinkedObject()
    {
        $this->setLinkedObject();
        return $this->linkedObject;
    }

    /**
     * @throws ApiException
     */
    public function setLinkedObject()
    {
        if (!empty($this->linkedObject)) {
            return;
        }

        $article = oxNew(Article::class);
        $oxid = Registry::getRequest()->getRequestParameter('oxid');
        $article->load($oxid);

        $this->linkedProduct = $this->repository->getLinkedProductByOxid($oxid);
        if ($this->linkedProduct) {
            if ($linkedObject = $this->getPayPalProductDetail($this->linkedProduct[0]['PAYPALPRODUCTID'])) {
                $this->linkedObject = $linkedObject;
            } else {
                // We have a linkedProduct, but its does not exists in PayPal-Catalogs, so we delete them
                //$this->$repository->deleteLinkedProduct($linkedProduct);
            }
        }
    }

    /**
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @throws ApiException
     */
    public function unlink()
    {
        $this->setLinkedObject();

        if (empty($this->linkedObject)) {
            return;
        }

        $this->repository->deleteLinkedProduct($this->linkedObject->id);
        $this->addTplParam('updatelist', 1);
    }

    /**
     * @param $id
     * @return Product
     * @throws ApiException
     */
    public function getPayPalProductDetail($id): ?Product
    {
        $linkedObject = null;
        try {
            $linkedObject = Registry::get(ServiceFactory::class)
                ->getCatalogService()
                ->showProductDetails($id);
        } catch (ApiException $exception) {
            // We have a linkedProduct, but its does not exists in PayPal-Catalogs
            Registry::getLogger()->error($exception);
        }
        return $linkedObject;
    }

    /**
     * @throws ApiException
     */
    public function getCatalogEntries()
    {
        $products = Registry::get(ServiceFactory::class)->getCatalogService()->listProducts();

        $filteredProducts = [];
        foreach ($products as $product) {
            $filteredProducts = $product;
        }

        return $filteredProducts;
    }

    /**
     * @return array
     */
    public function getCategories()
    {
        $category = new Category();
        $categories = $category->getCategories();

        $categoryArray = [];
        foreach ($categories as $type => $value) {
            $categoryArray[] = $value;
        }

        return $categoryArray;
    }

    /**
     * @return array
     */
    public function getTypes()
    {
        $category = new Category();
        $types = $category->getTypes();

        $typeArray = [];
        foreach ($types as $type => $value) {
            $typeArray[] = $value;
        }

        return $typeArray;
    }

    /**
     * @return mixed
     */
    public function getProductUrl()
    {
        return $this->getEditObject()->getBaseStdLink($this->_iEditLang);
    }

    /**
     * @return array
     */
    public function getDisplayImages(): array
    {
        $editObject = $this->getEditObject();

        $images = [];

        $picCount = Registry::getConfig()->getConfigParam('iPicCount');
        for ($i = 1; $i <= $picCount; $i++) {
            if (
                ($masterPic = $editObject->getMasterZoomPictureUrl($i)) &&
                ($viewPic = $editObject->getPictureUrl($i))
            ) {
                $images[] = [
                    'imageUrl' => $viewPic,
                    'masterUrl' => $masterPic
                ];
            }
        }

        return $images;
    }

    /**
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @throws ApiException
     */
    public function saveProduct()
    {
        $catalogService = new CatalogService($this->linkedObject);
        $productId = Registry::getRequest()->getRequestEscapedParameter('paypalProductId', "");

        try {
            if ($this->hasLinkedObject()) {
                $this->setLinkedObject();
                $catalogService->updateProduct($productId);
            } else {
                $catalogService->createProduct();
            }
        } catch (ApiException $e) {
            $this->addTplParam('error', $e->getErrorDescription());
        }
    }

    public function saveBillingPlans()
    {
        $subscriptionService = new SubscriptionService();
        $productId = Registry::getRequest()->getRequestEscapedParameter('paypalProductId', "");

        try {
            $this->setLinkedObject();
            /** @var Plan $subscription */
            $subscriptionService->saveNewSubscriptionPlan($productId, $this->getEditObjectId());
        } catch (DatabaseConnectionException $e) {
            $this->addTplParam('error', $e->getMessage());
        } catch (DatabaseErrorException $e) {
            $this->addTplParam('error', $e->getMessage());
        } catch (ApiException $e) {
            $this->addTplParam('error', $e->getErrorDescription());
        }
    }

    public function getSubscriptionPlans()
    {
        $this->setSubscriptionPlan();

        if (empty($this->subscriptionPlan)) {
            return [];
        }

        if ($linkedProducts = $this->repository->getLinkedProductByOxid($this->getEditObjectId())) {
            $sf = Registry::get(ServiceFactory::class);
            foreach ($linkedProducts as $linkedProduct) {
                $subscriptionPlan = $sf
                    ->getSubscriptionService()
                    ->showPlanDetails('string', $linkedProduct['PAYPALSUBSCRIPTIONPLANID'], 1);
                if ($subscriptionPlan->status == 'ACTIVE') {
                    $subscriptionPlans[] = $subscriptionPlan;
                }
            }
        }
        return $subscriptionPlans;
    }

    public function getSubscriptionPlansAreSubscripted()
    {
        $result = [];
        foreach ($this->getSubscriptionPlans() as $plan) {
            if ($this->repository->getSubscriptionsBySubscriptionPlanId($plan->id)) {
                $result[] = $plan->id;
            }
        }
        return $result;
    }

    /**
     */
    public function editBillingPlan()
    {
        $editBillingPlanId = Registry::getRequest()->getRequestEscapedParameter('editBillingPlanId', "");
        $this->addTplParam('editBillingPlanId', $editBillingPlanId);
    }

    /**
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function patch()
    {
        $editBillingPlanId = Registry::getRequest()->getRequestEscapedParameter('editBillingPlanId', "");

        $this->setLinkedObject();
        $this->setSubscriptionPlan($editBillingPlanId);

        $productId = Registry::getRequest()->getRequestEscapedParameter('paypalProductId', "");
        $subscriptionService = new SubscriptionService();
        $catalogService = new CatalogService($this->linkedObject);

        try {
            if ($this->hasSubscriptionPlan()) {
                $subscriptionService->deactivatePlan($this->subscriptionPlan);
                $this->repository->deleteLinkedPlan($editBillingPlanId);
            }
            $this->setLinkedObject();
            $subscriptionService->saveNewSubscriptionPlan($productId, $this->getEditObjectId());
        } catch (DatabaseConnectionException $e) {
            $this->addTplParam('error', $e->getMessage());
        } catch (DatabaseErrorException $e) {
            $this->addTplParam('error', $e->getMessage());
        } catch (ApiException $e) {
            $this->addTplParam('error', $e->getErrorDescription());
        }
    }

    /**
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function deactivate()
    {
        $deactivateBillingPlanId = Registry::getRequest()->getRequestEscapedParameter('deactivateBillingPlanId', "");

        $this->setLinkedObject();
        $this->setSubscriptionPlan($deactivateBillingPlanId);

        $subscriptionService = new SubscriptionService();
        $catalogService = new CatalogService($this->linkedObject);

        try {
            if ($this->hasSubscriptionPlan()) {
                $subscriptionService->deactivatePlan($this->subscriptionPlan);
                $this->repository->deleteLinkedPlan($deactivateBillingPlanId);
            }
        } catch (ApiException $e) {
            $this->addTplParam('error', $e->getErrorDescription());
        }
    }

    /**
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function activate()
    {
        $activateBillingPlanId = Registry::getRequest()->getRequestEscapedParameter('activateBillingPlanId', "");

        $this->setLinkedObject();
        $this->setSubscriptionPlan($activateBillingPlanId);

        $subscriptionService = new SubscriptionService();
        $catalogService = new CatalogService($this->linkedObject);

        try {
            if ($this->hasSubscriptionPlan()) {
                $subscriptionService->activatePlan($this->subscriptionPlan);
            }
        } catch (ApiException $e) {
            $this->addTplParam('error', $e->getErrorDescription());
        }
    }

    /**
     * @param $oxid
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    private function getLinkedProductByOxid($oxid): void
    {
        if (!empty($this->linkedProduct)) {
            return;
        }

        $this->linkedProduct = $this->repository->getLinkedProductByOxid($oxid);
    }
}
