<?php
/**
 * Copyright © Byte8 Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Byte8\Core\Model\Eav;

/**
 * Interface GetAttributeSetDataInterface used to
 * retrieve eav attribute set data in array format.
 */
interface GetAttributeSetDataInterface
{
    /**
     * @param string $entityTypeCode
     * @return array
     */
    public function execute(string $entityTypeCode): array;
}
