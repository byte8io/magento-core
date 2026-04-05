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
class IsMultidimensionalArray implements IsMultidimensionalArrayInterface
{
    /**
     * @inheritDoc
     */
    public function execute(array $array): bool
    {
        return count($array) !== count($array, COUNT_RECURSIVE);
    }
}
