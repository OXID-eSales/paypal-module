<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\PayPal\Core;

use OxidEsales\Eshop\Core\DisplayError;
use OxidEsales\Eshop\Core\Exception\StandardException;
use OxidEsales\Eshop\Core\Registry;
use OxidSolutionCatalysts\PayPal\Exception\PayPalException;
use OxidSolutionCatalysts\PayPal\Exception\Redirect;
use OxidSolutionCatalysts\PayPal\Exception\RedirectWithMessage;

/**
 * @mixin \OxidEsales\Eshop\Core\ShopControl
 */
class ShopControl extends ShopControl_parent
{
    /**
     * @param StandardException $exception
     */
    protected function _handleBaseException($exception) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if ($exception instanceof PayPalException) {
            $this->handleCustomPayPalException($exception);
        } else {
            parent::_handleBaseException($exception);
        }
    } // @codeCoverageIgnore

    /**
     * @param PayPalException $exception
     */
    public function handleCustomPayPalException(PayPalException $exception): void
    {
        if ($exception instanceof RedirectWithMessage) {
            $this->handlePayPalRedirectWithMessageException($exception);
        } elseif ($exception instanceof Redirect) {
            $this->handlePayPalRedirectException($exception, false);
        } else {
            parent::_handleBaseException($exception);
        }
    } // @codeCoverageIgnore

    /**
     * @param Redirect $redirectException
     * @param bool $blAddRedirectParam
     */
    protected function handlePayPalRedirectException(Redirect $redirectException, bool $blAddRedirectParam = true): void
    {
        Registry::getUtils()->redirect($redirectException->getDestination(), $blAddRedirectParam);
    }

    /**
     * @param RedirectWithMessage $redirectException
     */
    protected function handlePayPalRedirectWithMessageException(RedirectWithMessage $redirectException): void
    {
        $displayError = oxNew(DisplayError::class);
        $displayError->setMessage($redirectException->getMessageKey());
        $displayError->setFormatParameters($redirectException->getMessageParams());

        Registry::getUtilsView()->addErrorToDisplay($displayError);

        $this->handlePayPalRedirectException($redirectException);
    }
}
