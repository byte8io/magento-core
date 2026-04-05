<?php
/**
 * Copyright © Byte8 Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Byte8\Core\Framework\DataMap;

use Byte8\Core\Model\Source\Status\IsActive;
use Byte8\Core\Model\Source\StatusInterface;

/**
 * @inheritDoc
 */
class StatusToLabelMapping implements StatusToLabelMappingInterface
{
    /**
     * @param StatusInterface $status
     * @param IsActive $isActiveStatus
     */
    public function __construct(
        private readonly StatusInterface $status,
        private readonly IsActive $isActiveStatus
    ) {
    }

    /**
     * @inheritDoc
     */
    public function execute($status)
    {
        if ($result = $this->status->getOptions()[$status] ?? null) {
            return $result;
        }

        if (is_numeric($status)
            && $result = $this->isActiveStatus->getOptions()[(int) $status] ?? null
        ) {
            return $result;
        }

        return __('Unknown Status Map');
    }
}
