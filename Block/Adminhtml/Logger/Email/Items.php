<?php
/**
 * Copyright © Byte8 Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Byte8\Core\Block\Adminhtml\Logger\Email;

use Byte8\Core\Framework\MessageCollectorInterface;
use Magento\Framework\View\Element\Template;

/**
 * @inheritDoc
 */
class Items extends Template
{
    public function __construct(
        private readonly MessageCollectorInterface $collector,
        Template\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * @return array<string, string> [title => htmlContent]
     */
    public function getItems(): array
    {
        $items = [];
        foreach ($this->getData('items') ?: [] as $item) {
            if (!isset($item['message'], $item['context']) || !is_array($item['context'])) {
                continue;
            }
            $this->collector->reset();
            $this->ingest($item['context']);
            $items[$item['message']] = $this->renderHtml($this->collector->getMessages());
        }
        return $items;
    }

    /**
     * Walk the (possibly nested) context array from a Monolog record and feed
     * each leaf message into the collector. Accepts both the legacy MessageStorage
     * shape (entity/status/message/metadata keys) and arbitrary nesting.
     */
    private function ingest(array $context): void
    {
        foreach ($context as $entry) {
            if (!is_array($entry)) {
                continue;
            }
            if (isset($entry['entity'], $entry['status'], $entry['message'])) {
                $this->collector->addMessage(
                    (string) $entry['entity'],
                    (string) $entry['message'],
                    (string) $entry['status'],
                    is_array($entry['metadata'] ?? null) ? $entry['metadata'] : []
                );
                continue;
            }
            $this->ingest($entry);
        }
    }

    /**
     * @param array<int|string, array<int, array<string, mixed>>> $messagesByEntity
     */
    private function renderHtml(array $messagesByEntity): string
    {
        $html = '';
        foreach ($messagesByEntity as $entity => $messages) {
            $html .= '<h3>#' . $this->escapeHtml((string) $entity) . '</h3><ul>';
            foreach ($messages as $msg) {
                $html .= sprintf(
                    '<li class="status-%s">%s</li>',
                    $this->escapeHtmlAttr((string) ($msg['status'] ?? '')),
                    $this->escapeHtml((string) ($msg['message'] ?? ''))
                );
            }
            $html .= '</ul>';
        }
        return $html;
    }
}
