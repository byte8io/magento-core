<?php
/**
 * Copyright © Byte8 Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Byte8\Core\Model\Activation;

/**
 * Outcome of an activation check. Returned by Activation::getStatus().
 *
 * STATE_ACTIVE   — Verified within the last 24h (or soft-gate window is open
 *                  and a non-empty key is set).
 * STATE_GRACE    — Last verify succeeded but is past the 24h freshness
 *                  window. The 7-day grace tolerates a Byte8 Core outage.
 * STATE_INACTIVE — Key is missing, server returned a denial, or grace has
 *                  expired. Consumer is expected to no-op gracefully.
 * STATE_UNKNOWN  — Never checked yet, transient lookup failure, or empty
 *                  key during soft-gate. Behavioural alias for INACTIVE
 *                  outside the soft-gate window.
 */
class Status
{
    public const STATE_ACTIVE = 'active';
    public const STATE_GRACE = 'grace';
    public const STATE_INACTIVE = 'inactive';
    public const STATE_UNKNOWN = 'unknown';

    public function __construct(
        private readonly string $state,
        private readonly ?int $lastCheckedAt = null,
        private readonly ?string $reason = null
    ) {
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function getLastCheckedAt(): ?int
    {
        return $this->lastCheckedAt;
    }

    /**
     * Server-supplied error code (e.g. key_not_recognised, key_revoked,
     * host_not_authorised) or a client-side reason (no_key, network_error,
     * grace_expired). Never user-facing copy; consumers map to UI strings.
     */
    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function isUsable(): bool
    {
        return $this->state === self::STATE_ACTIVE || $this->state === self::STATE_GRACE;
    }
}
