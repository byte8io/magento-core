<?php
/**
 * Copyright © Byte8 Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Byte8\Core\Framework\MessageStorage;

/**
 * Interface OutputMessageManagerInterface used to output
 * data to message manager
 * @see \Magento\Framework\Message\ManagerInterface
 * @deprecated in favour of
 * @see \Byte8\Core\Framework\MessageCollectorInterface
 */
interface OutputMessageManagerInterface
{
    /**
     * @param array $data
     * @param string|null $lineBreak
     */
    public function execute(array $data, ?string $lineBreak = null): void;
}
