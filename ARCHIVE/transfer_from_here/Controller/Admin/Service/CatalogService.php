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

namespace OxidProfessionalServices\PayPal\Controller\Admin\Service;

use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Exception\DatabaseErrorException;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Request;
use OxidProfessionalServices\PayPal\Api\Exception\ApiException;
use OxidProfessionalServices\PayPal\Api\Model\Catalog\Patch;
use OxidProfessionalServices\PayPal\Api\Model\Catalog\Product;
use OxidProfessionalServices\PayPal\Api\Model\Catalog\ProductRequestPOST;
use OxidProfessionalServices\PayPal\Api\Service\Catalog;
use OxidProfessionalServices\PayPal\Core\ServiceFactory;
use OxidProfessionalServices\PayPal\Repository\SubscriptionRepository;

class CatalogService
{
    /**
     * @var Catalog
     */
    public $catalogService;

    /**
     * @var Request
     */
    private $request;

    private $linkedObject;

    public function __construct($linkedObject)
    {
        $this->catalogService = Registry::get(ServiceFactory::class)->getCatalogService();
        $this->request = Registry::getRequest();
        $this->linkedObject = $linkedObject;
    }

    /**
     * @param $productId
     * @throws ApiException
     */
    public function updateProduct($productId)
    {
        $this->updateProductDescription($productId);
        $this->updateProductCategory($productId);
        $this->updateImageUrl($productId);
        $this->updateHomeUrl($productId);
    }

    /**
     * @param Request $request
     * @param Catalog $cs
     * @param $productId
     * @throws ApiException
     */
    private function updateProductDescription($productId)
    {
        if ($this->linkedObject->description !== $this->request->getRequestEscapedParameter('description')) {
            $patchRequest = new Patch();
            $patchRequest->op = Patch::OP_REPLACE;
            $patchRequest->value = $this->request->getRequestEscapedParameter('description');
            $patchRequest->path = '/description';
            $this->catalogService->updateProduct($productId, [$patchRequest]);
        }
    }

    /**
     * @param Request $request
     * @param Catalog $cs
     * @param $productId
     * @throws ApiException
     */
    private function updateProductCategory($productId)
    {
        if ($this->linkedObject->category !== $this->request->getRequestEscapedParameter('category')) {
            $patchRequest = new Patch();
            $patchRequest->op = Patch::OP_REPLACE;
            $patchRequest->value = $this->request->getRequestEscapedParameter('category');
            $patchRequest->path = '/category';
            $this->catalogService->updateProduct($productId, [$patchRequest]);
        }
    }

    /**
     * @param Request $request
     * @param Catalog $cs
     * @param $productId
     * @return Patch
     * @throws ApiException
     */
    private function updateImageUrl($productId)
    {
        if ($this->linkedObject->image_url !== $this->request->getRequestEscapedParameter('imageUrl')) {
            $patchRequest = new Patch();
            $patchRequest->op = Patch::OP_REPLACE;
            $patchRequest->value = $this->request->getRequestEscapedParameter('imageUrl');
            $patchRequest->path = '/image_url';
            $this->catalogService->updateProduct($productId, [$patchRequest]);
        }
    }

    /**
     * @param Request $request
     * @param Catalog $cs
     * @param $productId
     * @throws ApiException
     */
    private function updateHomeUrl($productId)
    {
        if ($this->linkedObject->home_url !== $this->request->getRequestEscapedParameter('homeUrl')) {
            $patchRequest = new Patch();
            $patchRequest->op = Patch::OP_REPLACE;
            $patchRequest->value = $this->request->getRequestEscapedParameter('homeUrl');
            $patchRequest->path = '/home_url';
            $this->catalogService->updateProduct($productId, [$patchRequest]);
        }
    }

    /**
     * @throws ApiException
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function createProduct()
    {
        $productRequest = [];
        $productRequest['name'] = utf8_encode($this->request->getRequestParameter('title'));
        $productRequest['description'] = utf8_encode($this->request->getRequestParameter('description'));
        $productRequest['type'] = $this->request->getRequestParameter('productType');
        $productRequest['category'] = $this->request->getRequestParameter('category');
        $productRequest['image_url'] = $this->request->getRequestParameter('imageUrl');
        $productRequest['home_url'] = $this->request->getRequestParameter('homeUrl');

        $productRequestPost = new ProductRequestPOST($productRequest);

        /** @var Product $response */
        $response = $this->catalogService->createProduct($productRequestPost);

        $repository = new SubscriptionRepository();

        $repository->saveLinkedProduct(
            $response,
            Registry::getRequest()->getRequestParameter('oxid')
        );
    }
}
