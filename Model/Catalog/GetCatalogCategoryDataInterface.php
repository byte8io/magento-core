<?php
/**
 * Copyright © Byte8 Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Byte8\Core\Model\Catalog;

/**
 * Interface GetCatalogCategoryDataInterface used to obtain
 * catalog category raw data in array format.
 */
interface GetCatalogCategoryDataInterface
{
    /**
     * @param int $entityId
     * @return array
     */
    public function execute(int $entityId): array;
}
