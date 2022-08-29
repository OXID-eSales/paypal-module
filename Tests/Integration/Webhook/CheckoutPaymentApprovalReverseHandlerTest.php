<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Integration\Webhook;

use OxidSolutionCatalysts\PayPal\Core\Webhook\Handler\CheckoutPaymentApprovalReverseHandler;

final class CheckoutPaymentApprovalReverseHandlerTest extends PaymentCaptureDeniedHandlerTest
{
    public const WEBHOOK_EVENT = 'PAYMENT.CAPTURE.DENIED';

    public const HANDLER_CLASS = CheckoutPaymentApprovalReverseHandler::class;
}
