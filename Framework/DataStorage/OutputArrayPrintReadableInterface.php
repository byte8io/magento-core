<?php
/**
 * Copyright © Byte8 Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Byte8\Core\Framework\DataStorage;

/**
 * Interface OutputArrayPrintReadableInterface used to
 * output array elements in readable format.
 */
interface OutputArrayPrintReadableInterface
{
    /**
     * @param array $data
     * @return string
     */
    public function execute(array $data): string;
}
