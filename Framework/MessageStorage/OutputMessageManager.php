<?php
/**
 * Copyright © Byte8 Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Byte8\Core\Framework\MessageStorage;

use Magento\Framework\Message\ManagerInterface;
use Byte8\Core\Framework\MessageStorageInterface;
use Byte8\Core\Model\Source\StatusInterface;

/**
 * @inheritDoc
 * @deprecated in favour of
 * @see \Byte8\Core\Framework\MessageCollectorInterface
 */
class OutputMessageManager implements OutputMessageManagerInterface
{
    /**
     * @param ManagerInterface $messageManager
     * @param OutputHtmlInterface $outputHtml
     * @param StatusPredictionInterface $statusPrediction
     */
    public function __construct(
        private readonly ManagerInterface $messageManager,
        private readonly OutputHtmlInterface $outputHtml,
        private readonly StatusPredictionInterface $statusPrediction
    ) {
    }

    /**
     * @param array $data
     * @param string|null $lineBreak
     */
    public function execute(array $data, ?string $lineBreak = null): void
    {
        foreach ($data as $items) {
            if (null !== $lineBreak) {
                $item = $this->outputHtml->execute(
                    $items,
                    [
                        OutputHtmlInterface::LINE_BREAK => $lineBreak,
                    ]
                );
                $status = $this->statusPrediction->execute($items);
                $this->addMessage($item, $status);
                continue;
            }

            foreach ($items as $item) {
                if (!isset($item[MessageStorageInterface::MESSAGE])) {
                    continue;
                }
                $status = $item[MessageStorageInterface::STATUS] ?? $this->statusPrediction->execute($items);
                $this->addMessage($item[MessageStorageInterface::MESSAGE], $status);
            }
        }
    }

    /**
     * @param $message
     * @param string $status
     */
    private function addMessage($message, string $status): void
    {
        switch ($status) {
            case StatusInterface::CRITICAL:
            case StatusInterface::ERROR:
            case StatusInterface::FAILED:
                $this->messageManager->addErrorMessage($message);
                break;
            case StatusInterface::WARNING:
                $this->messageManager->addWarningMessage($message);
                break;
            case StatusInterface::NOTICE:
                $this->messageManager->addNoticeMessage($message);
                break;
            default:
                $this->messageManager->addSuccessMessage($message);
        }
    }
}
