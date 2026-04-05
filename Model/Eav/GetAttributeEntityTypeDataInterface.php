<?php
/**
 * Copyright © Byte8 Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Byte8\Core\Model\Eav;

/**
 * Interface GetAttributeEntityTypeDataInterface
 * used to obtain Eav attribute entity type data.
 */
interface GetAttributeEntityTypeDataInterface
{
    /**
     * @param string $entityTypeCode
     * @param int|string|null $index
     * @return array|string|null
     */
    public function execute(string $entityTypeCode, int|string|null $index = null): array|string|null;
}
