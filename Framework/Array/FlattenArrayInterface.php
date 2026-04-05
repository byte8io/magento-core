<?php
/**
 * Copyright © Byte8 Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Byte8\Core\Framework\Array;

/**
 * Interface FlattenArrayInterface used to expand
 * multidimensional array into flat structure
 */
interface FlattenArrayInterface
{
    /**
     * @param array $array
     * @param bool $shouldStripTags
     * @param int $maxLevel
     * @param string $path
     * @param string $separator
     * @return array
     */
    public function execute(
        array $array,
        bool $shouldStripTags = false,
        int $maxLevel = 0,
        string $path = '',
        string $separator = '/'
    ): array;
}
