<?php
/**
 * Copyright © Byte8 Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Byte8\Core\Model\Activation;

use Magento\Framework\App\CacheInterface;

/**
 * Caches the last verify envelope per (productId, key). Stateless across
 * products — the productId is part of the cache key, so a single Cache
 * instance serves every consumer.
 *
 * Stored as { envelope: array, fetched_at: int }. The Activation
 * orchestrator decides freshness from `fetched_at` + the envelope's
 * own `expiresAt` / `graceUntil`. The cache TTL covers the grace
 * window so a 7-day Byte8 Core outage doesn't evict the row.
 */
class Cache
{
    private const KEY_PREFIX = 'byte8_activation_';
    private const TAG_PREFIX = 'BYTE8_ACTIVATION_';

    /** Lifetime in cache storage (8 days — slightly longer than the 7d grace). */
    private const LIFETIME_SECONDS = 8 * 24 * 3600;

    public function __construct(
        private readonly CacheInterface $cache
    ) {
    }

    /**
     * @param array<string, mixed> $envelope
     */
    public function put(string $productId, string $key, array $envelope, int $fetchedAt): void
    {
        $payload = json_encode([
            'envelope' => $envelope,
            'fetched_at' => $fetchedAt,
        ]);
        if ($payload === false) {
            return;
        }
        $this->cache->save(
            $payload,
            $this->cacheKey($productId, $key),
            [$this->cacheTag($productId)],
            self::LIFETIME_SECONDS
        );
    }

    /**
     * @return array{envelope: array<string, mixed>, fetched_at: int}|null
     */
    public function get(string $productId, string $key): ?array
    {
        $raw = $this->cache->load($this->cacheKey($productId, $key));
        if (!is_string($raw) || $raw === '') {
            return null;
        }
        try {
            $decoded = json_decode($raw, true, 8, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }
        if (!is_array($decoded) || !isset($decoded['envelope'], $decoded['fetched_at'])) {
            return null;
        }
        return [
            'envelope' => (array) $decoded['envelope'],
            'fetched_at' => (int) $decoded['fetched_at'],
        ];
    }

    public function forget(string $productId, string $key): void
    {
        $this->cache->remove($this->cacheKey($productId, $key));
    }

    private function cacheKey(string $productId, string $key): string
    {
        return self::KEY_PREFIX . $productId . '_' . sha1($key);
    }

    private function cacheTag(string $productId): string
    {
        return self::TAG_PREFIX . strtoupper($productId);
    }
}
