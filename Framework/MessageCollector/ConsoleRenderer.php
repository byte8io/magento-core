<?php
/**
 * Copyright © Byte8 Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Byte8\Core\Framework\MessageCollector;

use Byte8\Core\Framework\MessageCollectorInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;

/**
 * Console renderer for CLI output
 */
class ConsoleRenderer implements RendererInterface
{
    public function __construct(
        private readonly OutputInterface $output
    ) {
    }

    /**
     * @inheritDoc
     */
    public function render(MessageCollectorInterface $collector, array $options = [])
    {
        $verbose = $options['verbose'] ?? false;

        // Render summary first
        $this->renderSummary($collector, $options);

        // Render details if verbose
        if ($verbose) {
            $this->renderDetails($collector, $options);
        }
    }

    /**
     * @inheritDoc
     */
    public function renderSummary(MessageCollectorInterface $collector, array $options = [])
    {
        $statistics = $collector->getStatistics();
        $messages = $collector->getMessages();

        $operationType = $options['operation_type'] ?? 'Export';
        $idField = $options['id_field'] ?? 'entity_id';

        $this->output->writeln('');
        $this->output->writeln(sprintf('<info>%s Summary:</info>', ucfirst($operationType)));

        $totalEntities = 0;
        $totalSuccess = 0;
        $totalErrors = 0;
        $totalProductsWritten = 0;

        // Cross-cutting "system" bucket (entity key 0) is rendered separately as a header section;
        // it has no business being counted as a processed entity.
        $systemMessages = $messages[0] ?? ($messages['0'] ?? []);
        if (!empty($systemMessages)) {
            $this->output->writeln('');
            $this->output->writeln('  <comment>System:</comment>');
            foreach ($this->buildSystemSummaryLines($systemMessages) as $line) {
                $this->output->writeln('    <info>✓</info> ' . $line);
            }
        }

        foreach ($statistics as $entity => $stats) {
            // Skip the system bucket we already rendered
            if ((string) $entity === '0') {
                continue;
            }

            $entityMessages = $messages[$entity] ?? [];
            $totalEntities++;
            if ($stats['success'] > 0) {
                $totalSuccess++;
            }
            $totalErrors += $stats['error'];

            $statusIcon = $stats['error'] > 0 ? '<error>✗</error>' : '<info>✓</info>';
            $headline = $this->buildEntitySummary($entity, $entityMessages, $idField);
            $rollupLines = $this->buildEntityRollup($entityMessages, $totalProductsWritten);

            $this->output->writeln('');
            $this->output->writeln(sprintf(
                '  %s %s: %s',
                $statusIcon,
                $entity,
                $headline ?: '(no details available)'
            ));
            foreach ($rollupLines as $rollup) {
                $this->output->writeln('           <fg=gray>→</> ' . $rollup);
            }
        }

        $this->output->writeln('');
        $totalLine = sprintf('Total: %d entities processed', $totalEntities);
        if ($totalProductsWritten > 0) {
            $totalLine .= sprintf(' · %d product(s) written', $totalProductsWritten);
        }
        $totalLine .= sprintf(' · %d successful · %d error(s)', $totalSuccess, $totalErrors);
        $this->output->writeln('<info>' . $totalLine . '</info>');
    }

    /**
     * @inheritDoc
     */
    public function renderDetails(MessageCollectorInterface $collector, array $options = [])
    {
        $messages = $collector->getMessages();

        $this->output->writeln('');
        $this->output->writeln('<comment>Detailed Messages:</comment>');

        foreach ($messages as $entity => $entityMessages) {
            $this->output->writeln('');
            $this->output->writeln(sprintf('<comment>%s:</comment>', $entity));

            foreach ($entityMessages as $msg) {
                $status = $msg['status'] ?? 'info';
                $message = $msg['message'] ?? '';
                $timestamp = $msg['timestamp'] ?? null;

                $icon = match($status) {
                    'error', 'critical' => '<error>✗</error>',
                    'warning' => '<comment>⚠</comment>',
                    default => '<info>✓</info>'
                };

                $line = sprintf('  %s %s', $icon, $message);
                if ($timestamp) {
                    $line .= sprintf(' <fg=gray>[%s]</>', date('H:i:s', $timestamp));
                }

                $this->output->writeln($line);
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function supports(string $outputType): bool
    {
        return in_array($outputType, ['cli', 'console', 'terminal']);
    }

    /**
     * Build the single-line headline shown after "✓ <entity_id>:".
     *
     * Priority:
     *   1. Anchor message with metadata.product_type → "<name> — <Type> (new|updated), N variation(s)"
     *   2. Legacy metadata.entity_type + idField → "<Type> <id>" (preserves non-product CLI summaries)
     *   3. Count of "Created/Updated new product:" messages → "N created/updated"
     *   4. First message text (last-resort fallback)
     */
    private function buildEntitySummary(int|string $entity, array $entityMessages, string $idField = 'entity_id'): string
    {
        // 1. Anchor on metadata.product_type — emitted by the final "X processed successfully" message
        foreach ($entityMessages as $msg) {
            $md = $msg['metadata'] ?? [];
            if (empty($md['product_type'])) {
                continue;
            }

            $type = ucfirst((string) $md['product_type']);
            $state = !empty($md['is_new']) ? 'new' : 'updated';
            $name = $this->findEntityName($entityMessages);

            $line = ($name !== null ? $name . ' — ' : '') . sprintf('%s (%s)', $type, $state);
            if (!empty($md['variation_count'])) {
                $line .= sprintf(', %d variation(s)', (int) $md['variation_count']);
            }
            return $line;
        }

        // 2. entity_type-anchored path — used by stock/attribute/order/customer CLIs.
        // Aggregate metadata across all messages in the bucket to build a rich headline.
        $entityType = null;
        $displayName = null;
        $action = null;
        $skipReason = null;
        $idValue = null;
        foreach ($entityMessages as $msg) {
            $md = $msg['metadata'] ?? [];
            if ($entityType === null && !empty($md['entity_type'])) {
                $entityType = (string) $md['entity_type'];
            }
            if ($displayName === null) {
                foreach (['sku', 'attribute_code', 'name'] as $nameField) {
                    if (!empty($md[$nameField])) {
                        $displayName = (string) $md[$nameField];
                        break;
                    }
                }
            }
            // action — later messages win (terminal action overrides early intermediates)
            if (!empty($md['action'])) {
                $action = (string) $md['action'];
            }
            if ($skipReason === null && !empty($md['skip_reason'])) {
                $skipReason = (string) $md['skip_reason'];
            }
            if ($idValue === null && !empty($md[$idField])) {
                $idValue = is_array($md[$idField]) ? implode(', ', $md[$idField]) : (string) $md[$idField];
            }
        }

        if ($entityType !== null) {
            $type = ucwords(str_replace('_', ' ', $entityType));
            // Skip the displayName prefix when it duplicates the entity key
            // (e.g. attribute messages bucket on attribute_code AND set attribute_code in metadata).
            $showName = $displayName !== null && (string) $displayName !== (string) $entity;
            $line = ($showName ? $displayName . ' — ' : '') . $type;
            if ($action !== null) {
                $stateStr = str_replace('_', ' ', $action);
                if ($action === 'skipped' && $skipReason !== null) {
                    $stateStr .= ': ' . str_replace('_', ' ', $skipReason);
                }
                $line .= ' (' . $stateStr . ')';
            } elseif (!$showName && $idValue !== null) {
                $line .= ' ' . $idValue;
            }
            return $line;
        }

        // 3. Count create/update messages
        $created = 0;
        $updated = 0;
        foreach ($entityMessages as $msg) {
            if (($msg['status'] ?? '') !== 'success') {
                continue;
            }
            $m = (string) ($msg['message'] ?? '');
            if (str_starts_with($m, 'Created new product:')) {
                $created++;
            } elseif (str_starts_with($m, 'Updated product:')) {
                $updated++;
            }
        }
        if ($created || $updated) {
            $countParts = [];
            if ($created) {
                $countParts[] = "$created created";
            }
            if ($updated) {
                $countParts[] = "$updated updated";
            }
            return implode(', ', $countParts) . ' product(s)';
        }

        // 4. Last resort
        return isset($entityMessages[0]['message']) ? (string) $entityMessages[0]['message'] : '';
    }

    /**
     * Render the cross-cutting "System:" bucket (entity key 0).
     * Groups all `metadata.indexer` messages into one line; other success messages pass through.
     * Notices are dropped (visible only in --verbose via renderDetails).
     */
    private function buildSystemSummaryLines(array $messages): array
    {
        $lines = [];
        $indexers = [];

        foreach ($messages as $msg) {
            if (($msg['status'] ?? '') === 'notice') {
                continue;
            }
            $md = $msg['metadata'] ?? [];

            if (!empty($md['indexer'])) {
                $indexers[$this->shortenIndexerName((string) $md['indexer'])] = (int) ($md['product_count'] ?? 0);
                continue;
            }

            $text = (string) ($msg['message'] ?? '');
            if ($text !== '') {
                $lines[] = $text;
            }
        }

        if (!empty($indexers)) {
            $parts = [];
            foreach ($indexers as $name => $count) {
                $parts[] = "$name($count)";
            }
            $lines[] = sprintf('%d indexer(s) refreshed: %s', count($indexers), implode(' · ', $parts));
        }

        return $lines;
    }

    /**
     * Build the indented "→" sub-lines under an entity headline.
     * Rolls up: create/update counts (also feeds $totalProductsWritten by reference),
     * URL rewrites, categories assigned, super attributes, MSI / catalog inventory stock.
     */
    private function buildEntityRollup(array $entityMessages, int &$totalProductsWritten): array
    {
        $created = 0;
        $updated = 0;
        $urlRewrites = 0;
        $categoriesAssigned = 0;
        $superAttributes = [];
        $msiTouched = false;

        foreach ($entityMessages as $msg) {
            if (($msg['status'] ?? '') !== 'success') {
                continue;
            }

            $m = (string) ($msg['message'] ?? '');
            $md = $msg['metadata'] ?? [];

            if (str_starts_with($m, 'Created new product:')) {
                $created++;
                $totalProductsWritten++;
            } elseif (str_starts_with($m, 'Updated product:')) {
                $updated++;
                $totalProductsWritten++;
            } elseif (str_starts_with($m, 'Generated ') && str_contains($m, 'URL rewrite')) {
                $urlRewrites += (int) ($md['total_count'] ?? 1);
            } elseif (preg_match('/^Assigned (\d+) new categor/', $m, $matches)) {
                $categoriesAssigned += (int) $matches[1];
            } elseif (!empty($md['added_attributes']) && is_array($md['added_attributes'])) {
                $superAttributes = array_merge($superAttributes, $md['added_attributes']);
            } elseif (str_contains($m, 'MSI stock') || str_contains($m, 'catalog inventory stock')) {
                $msiTouched = true;
            }
        }

        $lines = [];
        $headline = [];

        if ($created || $updated) {
            $cu = [];
            if ($created) {
                $cu[] = "$created created";
            }
            if ($updated) {
                $cu[] = "$updated updated";
            }
            $headline[] = implode('/', $cu) . ' product(s)';
        }
        if ($categoriesAssigned) {
            $headline[] = sprintf(
                '%d categor%s assigned',
                $categoriesAssigned,
                $categoriesAssigned === 1 ? 'y' : 'ies'
            );
        }
        if ($urlRewrites) {
            $headline[] = sprintf('%d URL rewrite%s', $urlRewrites, $urlRewrites === 1 ? '' : 's');
        }
        if ($msiTouched) {
            $headline[] = 'MSI stock written';
        }

        if (!empty($headline)) {
            $lines[] = implode(' · ', $headline);
        }
        if (!empty($superAttributes)) {
            $lines[] = 'super attributes: ' . implode(', ', array_unique($superAttributes));
        }

        return $lines;
    }

    /**
     * Best-effort SKU/name extraction for the entity headline.
     * Prefers metadata.sku from any create/update message (carried by the parent
     * product's "Created/Updated new product:" emission); falls back to parsing
     * the message text "Created new product: <NAME> (ID: ...)".
     */
    private function findEntityName(array $entityMessages): ?string
    {
        foreach ($entityMessages as $msg) {
            $md = $msg['metadata'] ?? [];
            if (!empty($md['sku'])) {
                return (string) $md['sku'];
            }
        }
        foreach ($entityMessages as $msg) {
            $text = (string) ($msg['message'] ?? '');
            if (preg_match('/^(?:Created new|Updated) product: (.+?)\s+\(ID:/', $text, $matches)) {
                return trim($matches[1]);
            }
        }
        return null;
    }

    /**
     * Compact display name for Magento indexer codes.
     */
    private function shortenIndexerName(string $indexer): string
    {
        return match ($indexer) {
            'catalog_product_price' => 'prices',
            'catalog_product_attribute' => 'eav',
            'catalog_product_category' => 'product→cat',
            'catalog_category_product' => 'cat→product',
            'catalogsearch_fulltext' => 'fulltext',
            'cataloginventory_stock' => 'stock',
            default => $indexer,
        };
    }
}
