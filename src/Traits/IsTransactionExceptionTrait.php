<?php

/**
 * Copyright © HardmanCommerce. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TddWizard\Fixtures\Traits;

trait IsTransactionExceptionTrait
{
    private static function isTransactionException(// phpcs:ignore Magento2.Functions.StaticFunction.StaticFunction
        ?\Throwable $exception,
    ): bool {
        if ($exception === null) {
            return false;
        }

        return (bool)preg_match(
            pattern: '{please retry transaction|DDL statements are not allowed in transactions}i',
            subject: $exception->getMessage(),
        );
    }
}
