<?php

declare(strict_types=1);

namespace Smartframe\Cdn\Service\Purge\Fastly;

use Fastly\Api\PurgeApi;
use Fig\Http\Message\StatusCodeInterface;
use Smartframe\Cdn\Exception\PurgeByHostnameNotSupportedException;
use Smartframe\Cdn\Exception\WildcardUrlNotSupportedException;
use Smartframe\Cdn\Logger\ResponseLogger;
use Smartframe\Cdn\Service\Purge\PurgeInterface;

class FastlyPurge implements PurgeInterface
{
    public const CACHE_KEY_HEADER_KEY = 'Surrogate-Key';
    public const CACHE_KEY_SEPARATOR = ' ';

    private PurgeApi $fastlyClient;
    private ResponseLogger $responseLogger;
    private bool $fastlySoftPurge;

    public function __construct(
        PurgeApi $fastlyClient,
        ResponseLogger $responseLogger,
        bool $fastlySoftPurge = false
    ) {
        $this->fastlyClient = $fastlyClient;
        $this->responseLogger = $responseLogger;
        $this->fastlySoftPurge = $fastlySoftPurge;
    }

    public function url(string $cacheId, string $url): bool
    {
        if (false !== \strpos($url, '*')) {
            throw new WildcardUrlNotSupportedException();
        }

        $response = $this->fastlyClient->purgeSingleUrl(['service_id' => $cacheId, 'cached_url' => $url, 'fastly_soft_purge' => $this->fastlySoftPurge ? 1 : 0]);

        ($this->responseLogger)($response, [
            'cacheId' => $cacheId,
            'cached_url' => $url,
        ]);

        return StatusCodeInterface::STATUS_OK === $response->getStatus();
    }

    public function key(string $cacheId, string $keyId): bool
    {
        $response = $this->fastlyClient->purgeTag(['service_id' => $cacheId, 'surrogate_key' => $keyId, 'fastly_soft_purge' => $this->fastlySoftPurge ? 1 : 0]);

        ($this->responseLogger)($response, [
            'cacheId' => $cacheId,
            'keyId' => $keyId,
        ]);

        return StatusCodeInterface::STATUS_OK === $response->getStatus();
    }

    /**
     * @throws PurgeByHostnameNotSupportedException
     */
    public function hostname(string $cacheId, string $hostname): bool
    {
        throw new PurgeByHostnameNotSupportedException();
    }

    public function all(string $cacheId): bool
    {
        $response = $this->fastlyClient->purgeAll(['service_id' => $cacheId, 'fastly_soft_purge' => $this->fastlySoftPurge ? 1 : 0]);

        ($this->responseLogger)($response, [
            'cacheId' => $cacheId,
        ]);

        return StatusCodeInterface::STATUS_OK === $response->getStatus();
    }

    /**
     * @codeCoverageIgnore
     */
    public function isWildcardUrlSupported(): bool
    {
        return false;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getCacheKeyHeaderKey(): string
    {
        return self::CACHE_KEY_HEADER_KEY;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getCacheKeySeparator(): string
    {
        return self::CACHE_KEY_SEPARATOR;
    }
}
