<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Traits;

use OxidEsales\Eshop\Application\Model\Article;
use OxidEsales\Eshop\Core\Registry;
use OxidSolutionCatalysts\PayPal\Core\ServiceFactory;
use OxidSolutionCatalysts\PayPal\Repository\SubscriptionRepository;

/**
 * Class ArticleDetailsController
 * @mixin \OxidEsales\Eshop\Application\Controller\ArticleDetailsController
 */
trait ArticleDetailsTrait
{
    protected function loadTemplateSubscriptionData(): void
    {
        $subscriptionRepository = new SubscriptionRepository();

        $articleId = Registry::getRequest()->getRequestEscapedParameter('anid');

        /** @var Article $article */
        $article = oxNew(Article::class);
        $article->load($articleId);

        $subscriptionPlans = [];

        if ($linkedProducts = $subscriptionRepository->getLinkedProductByOxid($articleId)) {
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

        $this->addTplParam('subscriptionPlans', $subscriptionPlans);
        $this->addTplParam('hasSubscriptionPlans', (count($subscriptionPlans) > 0));
    }

    public function checkLogin(): void
    {
        $user = $this->getSession()->getUser();

        if (!$user) {
            $url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
               . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            $url = explode('?', $url)[0];
            $this->addTplParam('currentUrl', $url);
            $this->addTplParam('isLoggedIn', false);
        } else {
            $this->addTplParam('isLoggedIn', true);
        }
    }

    public function render()
    {
        $return = parent::render();

        $this->checkLogin();
        $this->loadTemplateSubscriptionData();

        return $return;
    }
}
