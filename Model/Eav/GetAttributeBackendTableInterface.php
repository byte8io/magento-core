<?php
/**
 * Copyright © Byte8 Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Byte8\Core\Model\Eav;

use Magento\Framework\Exception\LocalizedException;

/**
 * Interface GetAttributeBackendTableInterface
 * used to obtain attribute's backend table.
 */
interface GetAttributeBackendTableInterface
{
    /**
     * @param int $attributeId
     * @param string $entityTypeCode
     * @return string
     * @throws LocalizedException
     */
    public function execute(int $attributeId, string $entityTypeCode): string;
}
