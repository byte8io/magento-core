<?php
/**
 * Copyright © Byte8 Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Byte8\Core\Framework\DataMap;

use Magento\Framework\Phrase;

/**
 * Interface StatusToLabelMappingInterface used to
 * map status to its label.
 */
interface StatusToLabelMappingInterface
{
    /**
     * @param $status
     * @return Phrase|mixed|string
     */
    public function execute($status);
}
