<?php
/**
 * Copyright © Byte8 Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Byte8\Core\Framework\MessageStorage;

/**
 * Interface OutputArrayInterface used to output
 * data to array.
 * @deprecated in favour of
 * @see \Byte8\Core\Framework\MessageCollectorInterface
 */
interface OutputArrayInterface
{
    /**
     * @param array $data
     * @return array
     */
    public function execute(array $data): array;
}
