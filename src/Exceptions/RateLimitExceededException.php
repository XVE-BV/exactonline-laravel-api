<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Exceptions;

use Exception;

class RateLimitExceededException extends Exception
{
    protected int $retryAfterSeconds;

    protected string $limitType;

    public function __construct(string $message, int $retryAfterSeconds, string $limitType)
    {
        parent::__construct($message);
        $this->retryAfterSeconds = $retryAfterSeconds;
        $this->limitType = $limitType;
    }

    public static function dailyLimitExceeded(int $limit, int $resetInSeconds): self
    {
        $hours = round($resetInSeconds / 3600, 1);

        return new self(
            "Daily API rate limit of {$limit} requests exceeded. ".
            "Limit will reset in {$hours} hours.",
            $resetInSeconds,
            'daily'
        );
    }

    public static function minutelyLimitExceeded(int $limit, int $resetInSeconds): self
    {
        return new self(
            "Minutely API rate limit of {$limit} requests exceeded. ".
            "Please wait {$resetInSeconds} seconds before retrying.",
            $resetInSeconds,
            'minutely'
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

    public function isDaily(): bool
    {
        return $this->limitType === 'daily';
    }

    public function isMinutely(): bool
    {
        return $this->limitType === 'minutely';
    }
}
