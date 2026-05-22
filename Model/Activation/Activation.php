<?php
/**
 * Copyright © Byte8 Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Byte8\Core\Model\Activation;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Activation gate. One instance per consuming product, wired via DI:
 *
 *   <type name="Byte8\YourModule\Model\Activation">
 *       <arguments>
 *           <argument name="productId" xsi:type="string">your_product</argument>
 *           <argument name="configPathPrefix" xsi:type="string">byte8_your_module/activation/</argument>
 *       </arguments>
 *   </type>
 *
 * Where `Byte8\YourModule\Model\Activation` is an empty subclass of this
 * one (PHP-side type-hint anchor). See module-core/README.md or one of
 * `module-vat-validator` / `module-pingbell` / `module-stock-radar` for a
 * worked example.
 *
 * Three windows control behaviour:
 *
 *   1. Soft-gate window (until SOFT_GATE_UNTIL) — any non-empty key
 *      counts as active. No server roundtrip. Lets a module ship to
 *      Packagist before the activate endpoint is reachable and lets
 *      admins enter a placeholder while testing.
 *
 *   2. Fresh window (24h since last successful verify) — cached
 *      envelope is reused. No server roundtrip.
 *
 *   3. Grace window (7d past freshness) — cached envelope is reused
 *      while the orchestrator attempts a background refresh. If the
 *      refresh succeeds, the cache is bumped. If it fails (server
 *      unreachable), the cached envelope continues to satisfy the gate
 *      until grace expires.
 *
 * Outside all three: gate denies. Consumer's hot path is expected to
 * short-circuit gracefully. No exceptions ever bubble out — the gate
 * must not break checkout / order placement / customer save / etc.
 */
class Activation
{
    /**
     * Soft-gate cutoff. Until this UTC instant, any non-empty key is
     * treated as valid with no server roundtrip. After this date,
     * verification is required and unverified keys flip every consumer
     * to no-op. Single source of truth across the whole free-product
     * fleet — bump here to extend.
     */
    private const SOFT_GATE_UNTIL = '2026-06-18T00:00:00Z';

    private const FRESH_TTL_SECONDS = 24 * 3600;
    private const GRACE_TTL_SECONDS = 7 * 24 * 3600;

    private const DEFAULT_ENDPOINT = 'https://api.byte8.io/graphql';
    private const DEFAULT_TIMEOUT = 5;
    private const DEFAULT_CONNECT_TIMEOUT = 2;

    public function __construct(
        private readonly string $productId,
        private readonly string $configPathPrefix,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly EncryptorInterface $encryptor,
        private readonly Client $client,
        private readonly Cache $cache,
        private readonly StoreManagerInterface $storeManager,
        private readonly DeploymentConfig $deploymentConfig,
        private readonly LoggerInterface $logger
    ) {
    }

    public function isActive(?int $storeId = null): bool
    {
        return $this->getStatus($storeId)->isUsable();
    }

    public function getStatus(?int $storeId = null): Status
    {
        $key = $this->getKey($storeId);
        if ($key === '') {
            return new Status(Status::STATE_INACTIVE, null, 'no_key');
        }

        // Soft-gate: any non-empty key counts as active before the cutoff.
        if ($this->isWithinSoftGate()) {
            return new Status(Status::STATE_ACTIVE, time(), 'soft_gate');
        }

        $cached = $this->cache->get($this->productId, $key);
        $now = time();

        if ($cached !== null) {
            $envelope = $cached['envelope'];
            $fetchedAt = $cached['fetched_at'];
            $envelopeOk = (bool) ($envelope['ok'] ?? false);
            $error = isset($envelope['error']) && is_string($envelope['error']) ? $envelope['error'] : null;

            // Server-side denial. Cache the denial briefly so we don't
            // hammer Core, but never report active.
            if (!$envelopeOk) {
                return new Status(Status::STATE_INACTIVE, $fetchedAt, $error);
            }

            if (($now - $fetchedAt) < self::FRESH_TTL_SECONDS) {
                return new Status(Status::STATE_ACTIVE, $fetchedAt);
            }
            // Past freshness — refresh, but if refresh fails fall back
            // to grace.
            $refreshed = $this->refresh($key, $storeId);
            if ($refreshed !== null) {
                return $this->statusFromEnvelope($refreshed, time());
            }
            if (($now - $fetchedAt) < (self::FRESH_TTL_SECONDS + self::GRACE_TTL_SECONDS)) {
                return new Status(Status::STATE_GRACE, $fetchedAt, 'server_unreachable');
            }
            return new Status(Status::STATE_INACTIVE, $fetchedAt, 'grace_expired');
        }

        // First-time verify.
        $envelope = $this->refresh($key, $storeId);
        if ($envelope === null) {
            return new Status(Status::STATE_UNKNOWN, null, 'server_unreachable');
        }
        return $this->statusFromEnvelope($envelope, time());
    }

    /**
     * Force a re-verify now. Used by CLI activate commands to confirm a
     * freshly-entered key before printing success.
     *
     * @return array<string, mixed>|null
     */
    public function refresh(string $key, ?int $storeId = null): ?array
    {
        $host = $this->resolveHost();
        $envelope = $this->client->verify(
            $this->productId,
            $key,
            $host,
            $this->getEndpoint($storeId),
            $this->getTimeout($storeId),
            $this->getConnectTimeout($storeId)
        );
        if ($envelope !== null) {
            $this->cache->put($this->productId, $key, $envelope, time());
        }
        return $envelope;
    }

    /**
     * Read the (encrypted) activation key from scope config and decrypt it.
     * Public because CLI activate commands need to write to the same path —
     * but they should use this to read back.
     */
    public function getKey(?int $storeId = null): string
    {
        $stored = (string) $this->scopeConfig->getValue(
            $this->configPathPrefix . 'key',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        if ($stored === '') {
            return '';
        }
        return trim($this->encryptor->decrypt($stored));
    }

    /**
     * @param array<string, mixed> $envelope
     */
    private function statusFromEnvelope(array $envelope, int $fetchedAt): Status
    {
        $ok = (bool) ($envelope['ok'] ?? false);
        $reason = isset($envelope['error']) && is_string($envelope['error']) ? $envelope['error'] : null;
        return new Status(
            $ok ? Status::STATE_ACTIVE : Status::STATE_INACTIVE,
            $fetchedAt,
            $reason
        );
    }

    private function isWithinSoftGate(): bool
    {
        try {
            $cutoff = new \DateTimeImmutable(self::SOFT_GATE_UNTIL);
        } catch (\Throwable) {
            return false;
        }
        return (new \DateTimeImmutable('now', new \DateTimeZone('UTC'))) < $cutoff;
    }

    private function resolveHost(): string
    {
        try {
            $base = $this->storeManager->getStore()->getBaseUrl();
        } catch (\Throwable) {
            $base = $this->deploymentConfig->get('system/default/web/secure/base_url')
                ?? $this->deploymentConfig->get('system/default/web/unsecure/base_url')
                ?? '';
        }
        $host = parse_url((string) $base, PHP_URL_HOST);
        return is_string($host) && $host !== '' ? $host : 'unknown';
    }

    private function getEndpoint(?int $storeId): string
    {
        $value = trim((string) $this->scopeConfig->getValue(
            $this->configPathPrefix . 'endpoint',
            ScopeInterface::SCOPE_STORE,
            $storeId
        ));
        return $value !== '' ? $value : self::DEFAULT_ENDPOINT;
    }

    private function getTimeout(?int $storeId): int
    {
        $value = (int) $this->scopeConfig->getValue(
            $this->configPathPrefix . 'timeout',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        return $value > 0 ? $value : self::DEFAULT_TIMEOUT;
    }

    private function getConnectTimeout(?int $storeId): int
    {
        $value = (int) $this->scopeConfig->getValue(
            $this->configPathPrefix . 'connect_timeout',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        return $value > 0 ? $value : self::DEFAULT_CONNECT_TIMEOUT;
    }

    /**
     * Returns the canonical scope-config key path (`...activation/key`).
     * Used by consumer-side activate CLI commands to write the encrypted
     * value via ConfigResource::saveConfig.
     */
    public function getKeyConfigPath(): string
    {
        return $this->configPathPrefix . 'key';
    }
}
