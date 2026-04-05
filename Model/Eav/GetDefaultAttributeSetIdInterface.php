<?php
/**
 * Copyright © Byte8 Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Byte8\Core\Model\Eav;

/**
 * Interface GetDefaultAttributeSetIdInterface
 * used to obtain default attribute set ID.
 */
interface GetDefaultAttributeSetIdInterface
{
    /**
     * @param string $entityTypeCode
     * @return int
     */
    public function execute(string $entityTypeCode): int;
}
