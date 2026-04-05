<?php
/**
 * Copyright © Byte8 Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Byte8\Core\Framework;

/**
 * Interface IsMultidimensionalArrayInterface used to
 * determine whether a given array is multidimensional array
 */
interface IsMultidimensionalArrayInterface
{
    /**
     * @param array $array
     * @return bool
     */
    public function execute(array $array): bool;
}
