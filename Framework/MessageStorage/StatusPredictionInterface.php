<?php
/**
 * Copyright © Byte8 Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Byte8\Core\Framework\MessageStorage;

use Byte8\Core\Model\Source\StatusInterface;

/**
 * Interface StatusPredictionInterface used to
 * predict output status.
 * @deprecated in favour of
 * @see \Byte8\Core\Framework\MessageCollectorInterface
 */
interface StatusPredictionInterface
{
    /**
     * @param array $data
     * @param string $fallback
     * @return string
     */
    public function execute(array $data, string $fallback = StatusInterface::SUCCESS): string;
}
