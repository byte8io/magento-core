<?php
/**
 * Copyright © Byte8 Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Byte8\Core\Model\Activation;

use Magento\Framework\HTTP\Client\CurlFactory;
use Psr\Log\LoggerInterface;

/**
 * GraphQL client for Byte8 Core's verifyProductActivation mutation.
 *
 * Stateless — the consumer's Activation orchestrator passes everything per
 * call so the same Client serves every product without per-product DI.
 *
 * Returns the parsed envelope (the same shape the server returns) or null
 * on transient failure (network error, non-2xx, malformed JSON). Failures
 * are logged but never thrown — the gate must never break the consumer's
 * hot path (observer, validator, plugin).
 */
class Client
{
    private const MUTATION = <<<'GQL'
mutation Verify($input: VerifyProductActivationInput!) {
  verifyProductActivation(input: $input) {
    ok productId tier host features expiresAt graceUntil nonce signature error
  }
}
GQL;

    public function __construct(
        private readonly CurlFactory $curlFactory,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @return array<string, mixed>|null Parsed verify envelope, or null on transient failure.
     */
    public function verify(
        string $productId,
        string $key,
        string $host,
        string $endpoint,
        int $timeout,
        int $connectTimeout
    ): ?array {
        if ($endpoint === '') {
            return null;
        }

        $payload = json_encode([
            'query' => self::MUTATION,
            'variables' => [
                'input' => [
                    'productId' => $productId,
                    'key' => $key,
                    'host' => $host,
                    'nonce' => $this->generateNonce(),
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $curl = $this->curlFactory->create();
        $curl->setOption(CURLOPT_CONNECTTIMEOUT, max(1, $connectTimeout));
        $curl->setOption(CURLOPT_TIMEOUT, max(1, $timeout));
        $curl->addHeader('Content-Type', 'application/json');
        $curl->addHeader('Accept', 'application/json');

        try {
            $curl->post($endpoint, $payload);
        } catch (\Throwable $e) {
            $this->logger->warning(sprintf(
                'Byte8 activation verify failed for product "%s": %s',
                $productId,
                $e->getMessage()
            ));
            return null;
        }

        $status = $curl->getStatus();
        $body = $curl->getBody();
        if ($status < 200 || $status >= 300) {
            $this->logger->warning(sprintf(
                'Byte8 activation verify for product "%s" returned HTTP %d: %s',
                $productId,
                $status,
                substr($body, 0, 500)
            ));
            return null;
        }

        try {
            $decoded = json_decode($body, true, 16, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            $this->logger->warning(sprintf(
                'Byte8 activation verify for product "%s": malformed JSON response',
                $productId
            ));
            return null;
        }

        $envelope = $decoded['data']['verifyProductActivation'] ?? null;
        if (!is_array($envelope)) {
            return null;
        }
        return $envelope;
    }

    private function generateNonce(): string
    {
        try {
            return bin2hex(random_bytes(12));
        } catch (\Throwable) {
            return uniqid('', true);
        }
    }
}
