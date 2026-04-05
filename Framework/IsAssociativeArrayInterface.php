<?php
/**
 * Copyright © Byte8 Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Byte8\Core\Framework;

/**
 * Interface IsAssociativeArrayInterface used to
 * determine whether a given array is associative array
 */
interface IsAssociativeArrayInterface
{
    /**
     * @param array $array
     * @return bool
     */
    public function execute(array $array): bool;
}
