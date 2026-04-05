<?php
/**
 * Copyright © Byte8 Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Byte8\Core\Framework\DataStorage;

/**
 * Interface OutputArrayInterface used to
 * output data to array
 */
interface OutputArrayInterface
{
    /**
     * @param array $data
     * @return array|string
     */
    public function execute(array $data);
}
