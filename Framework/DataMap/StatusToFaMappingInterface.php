<?php
/**
 * Copyright © Byte8 Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Byte8\Core\Framework\DataMap;

use Byte8\Core\Model\Source\StatusInterface;

/**
 * Interface StatusToFaMappingInterface used to
 * map status to Fontawesome icon class.
 */
interface StatusToFaMappingInterface
{
    /**
     * @param string|null $status
     * @param string $fallBack
     * @return string
     */
    public function execute(?string $status = null, string $fallBack = StatusInterface::SUCCESS): string;
}
