<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Exceptions;

use Exception;

class RateLimitExceededException extends Exception
{
    protected int $retryAfterSeconds;

    protected string $limitType;

    protected int $resetAt;

    public function __construct(string $message, int $retryAfterSeconds, string $limitType, ?int $resetAt = null)
    {
        parent::__construct($message);
        $this->retryAfterSeconds = $retryAfterSeconds;
        $this->limitType = $limitType;
        $this->resetAt = $resetAt ?? (now()->timestamp + $retryAfterSeconds);
    }

    public static function dailyLimitExceeded(int $limit, int $resetInSeconds): self
    {
        $hours = round($resetInSeconds / 3600, 1);
        $resetAt = now()->timestamp + $resetInSeconds;

        return new self(
            "Daily API rate limit of {$limit} requests exceeded. ".
            "Limit will reset in {$hours} hours.",
            $resetInSeconds,
            'daily',
            $resetAt
        );
    }

    public static function minutelyLimitExceeded(int $limit, int $resetInSeconds): self
    {
        $resetAt = now()->timestamp + $resetInSeconds;

        return new self(
            "Minutely API rate limit of {$limit} requests exceeded. ".
            "Please wait {$resetInSeconds} seconds before retrying.",
            $resetInSeconds,
            'minutely',
            $resetAt
        );
    }

    public function getRetryAfterSeconds(): int
    {
        return $this->retryAfterSeconds;
    }

    public function getLimitType(): string
    {
        return $this->limitType;
    }

    public function getResetAt(): int
    {
        return $this->resetAt;
    }

    public function isDaily(): bool
    {
        return $this->limitType === 'daily';
    }

    public function isDailyLimit(): bool
    {
        return $this->isDaily();
    }

    public function isMinutely(): bool
    {
        return $this->limitType === 'minutely';
    }

    public function isMinutelyLimit(): bool
    {
        return $this->isMinutely();
    }
}
