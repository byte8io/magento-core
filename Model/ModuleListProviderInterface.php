<?php
/**
 * Copyright © Byte8 Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Byte8\Core\Model;

/**
 * Class ModuleListProviderInterface used
 * to provide a list of vendor modules
 */
interface ModuleListProviderInterface
{
    /**
     * @param string|null $moduleName
     * @param string|null $metadata
     * @return mixed|null
     */
    public function getList(?string $moduleName = null, ?string $metadata = null);
}
