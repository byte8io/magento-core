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
class SearchMultidimensionalArray implements SearchMultidimensionalArrayInterface
{
    /**
     * @inheritDoc
     */
    public function execute(mixed $needle, array $haystack, string $columnName, $columnId = null): bool|int|string
    {
        return array_search(
            $needle,
            array_column(
                $haystack,
                $columnName,
                $columnId
            )
        );
    }
}
