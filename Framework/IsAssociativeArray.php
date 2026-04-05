<?php
/**
 * Copyright © Byte8 Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Byte8\Core\Framework;

/**
 * @inheritDoc
 */
class IsAssociativeArray implements IsAssociativeArrayInterface
{
    /**
     * @inheritDoc
     */
    public function execute(array $array): bool
    {
        return count(array_filter(array_keys($array), 'is_string')) !== 0;
    }
}
