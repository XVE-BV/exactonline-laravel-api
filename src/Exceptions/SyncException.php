<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Exceptions;

/**
 * Exception for synchronization failures between local and Exact Online data.
 *
 * Thrown when sync operations fail, including data mapping errors,
 * conflict resolution failures, and batch sync issues.
 */
class SyncException extends ExactOnlineException
{
    protected ?string $syncDirection = null;

    protected ?string $localId = null;

    protected ?string $remoteId = null;

    protected array $failedRecords = [];

    protected int $successCount = 0;

    protected int $failureCount = 0;

    /**
     * Create exception for mapping failure.
     */
    public static function mappingFailed(
        string $entity,
        string $field,
        mixed $value,
        string $reason
    ): self {
        $exception = new self(
            "Failed to map {$entity} field '{$field}': {$reason}. Value: ".
            (is_scalar($value) ? (string) $value : gettype($value))
        );

        return $exception
            ->setEntity($entity)
            ->addContext('field', $field)
            ->addContext('value', $value);
    }

    /**
     * Create exception for push sync failure.
     */
    public static function pushFailed(
        string $entity,
        string $localId,
        string $reason
    ): self {
        $exception = new self(
            "Failed to push {$entity} (local ID: {$localId}) to Exact Online: {$reason}"
        );

        return $exception
            ->setEntity($entity)
            ->setLocalId($localId)
            ->setSyncDirection('push');
    }

    /**
     * Create exception for pull sync failure.
     */
    public static function pullFailed(
        string $entity,
        string $remoteId,
        string $reason
    ): self {
        $exception = new self(
            "Failed to pull {$entity} (Exact ID: {$remoteId}) from Exact Online: {$reason}"
        );

        return $exception
            ->setEntity($entity)
            ->setRemoteId($remoteId)
            ->setSyncDirection('pull');
    }

    /**
     * Create exception for conflict during sync.
     */
    public static function conflict(
        string $entity,
        string $localId,
        string $remoteId,
        string $reason
    ): self {
        $exception = new self(
            "Sync conflict for {$entity} (local: {$localId}, remote: {$remoteId}): {$reason}"
        );

        return $exception
            ->setEntity($entity)
            ->setLocalId($localId)
            ->setRemoteId($remoteId);
    }

    /**
     * Create exception for batch sync failure.
     */
    public static function batchFailed(
        string $entity,
        int $successCount,
        int $failureCount,
        array $failedRecords = []
    ): self {
        $exception = new self(
            "Batch sync for {$entity} completed with errors: ".
            "{$successCount} succeeded, {$failureCount} failed"
        );

        $exception->setEntity($entity);
        $exception->successCount = $successCount;
        $exception->failureCount = $failureCount;
        $exception->failedRecords = $failedRecords;

        return $exception;
    }

    /**
     * Create exception for missing required field.
     */
    public static function missingRequiredField(
        string $entity,
        string $field,
        string $direction = 'push'
    ): self {
        $exception = new self(
            "Cannot sync {$entity}: required field '{$field}' is missing"
        );

        return $exception
            ->setEntity($entity)
            ->setSyncDirection($direction)
            ->addContext('missing_field', $field);
    }

    /**
     * Create exception for invalid data format.
     */
    public static function invalidDataFormat(
        string $entity,
        string $field,
        string $expectedFormat,
        mixed $actualValue
    ): self {
        $exception = new self(
            "Invalid data format for {$entity}.{$field}: ".
            "expected {$expectedFormat}, got ".gettype($actualValue)
        );

        return $exception
            ->setEntity($entity)
            ->addContext('field', $field)
            ->addContext('expected_format', $expectedFormat);
    }

    /**
     * Create exception for entity already synced.
     */
    public static function alreadySynced(string $entity, string $id): self
    {
        return (new self(
            "{$entity} with ID '{$id}' has already been synced"
        ))->setEntity($entity);
    }

    /**
     * Create exception for sync lock timeout.
     */
    public static function lockTimeout(string $entity, string $connectionId): self
    {
        $exception = new self(
            "Timeout waiting for sync lock on {$entity} for connection '{$connectionId}'. ".
            'Another sync operation may be in progress.'
        );

        return $exception
            ->setEntity($entity)
            ->setConnectionId($connectionId);
    }

    /**
     * Set the sync direction.
     */
    public function setSyncDirection(string $direction): self
    {
        $this->syncDirection = $direction;

        return $this;
    }

    /**
     * Get the sync direction.
     */
    public function getSyncDirection(): ?string
    {
        return $this->syncDirection;
    }

    /**
     * Set the local record ID.
     */
    public function setLocalId(string $localId): self
    {
        $this->localId = $localId;

        return $this;
    }

    /**
     * Get the local record ID.
     */
    public function getLocalId(): ?string
    {
        return $this->localId;
    }

    /**
     * Set the remote (Exact Online) record ID.
     */
    public function setRemoteId(string $remoteId): self
    {
        $this->remoteId = $remoteId;

        return $this;
    }

    /**
     * Get the remote (Exact Online) record ID.
     */
    public function getRemoteId(): ?string
    {
        return $this->remoteId;
    }

    /**
     * Get failed records from batch sync.
     */
    public function getFailedRecords(): array
    {
        return $this->failedRecords;
    }

    /**
     * Get success count from batch sync.
     */
    public function getSuccessCount(): int
    {
        return $this->successCount;
    }

    /**
     * Get failure count from batch sync.
     */
    public function getFailureCount(): int
    {
        return $this->failureCount;
    }

    /**
     * Check if this is a push (local to remote) sync failure.
     */
    public function isPushFailure(): bool
    {
        return $this->syncDirection === 'push';
    }

    /**
     * Check if this is a pull (remote to local) sync failure.
     */
    public function isPullFailure(): bool
    {
        return $this->syncDirection === 'pull';
    }

    /**
     * Check if this is a batch sync failure.
     */
    public function isBatchFailure(): bool
    {
        return $this->failureCount > 0;
    }

    /**
     * Get exception data for logging.
     */
    public function toArray(): array
    {
        return array_merge(parent::toArray(), array_filter([
            'sync_direction' => $this->syncDirection,
            'local_id' => $this->localId,
            'remote_id' => $this->remoteId,
            'success_count' => $this->successCount ?: null,
            'failure_count' => $this->failureCount ?: null,
            'failed_records' => $this->failedRecords ?: null,
        ]));
    }
}
