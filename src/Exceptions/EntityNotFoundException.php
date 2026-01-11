<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Exceptions;

/**
 * Exception thrown when an entity is not found in Exact Online.
 *
 * This is a specialized API exception for 404 responses, providing
 * additional context about the entity type and ID that was not found.
 */
class EntityNotFoundException extends ApiException
{
    protected ?string $entityId = null;

    public function __construct(
        string $entity,
        string $id,
        ?\Throwable $previous = null
    ) {
        $this->entityId = $id;
        $message = "{$entity} with ID '{$id}' not found in Exact Online";

        parent::__construct($message, 404, $previous);

        $this->setEntity($entity);
        $this->addContext('entity_id', $id);
    }

    /**
     * Create exception for Account not found.
     */
    public static function account(string $id): self
    {
        return new self('Account', $id);
    }

    /**
     * Create exception for Contact not found.
     */
    public static function contact(string $id): self
    {
        return new self('Contact', $id);
    }

    /**
     * Create exception for Item not found.
     */
    public static function item(string $id): self
    {
        return new self('Item', $id);
    }

    /**
     * Create exception for SalesOrder not found.
     */
    public static function salesOrder(string $id): self
    {
        return new self('SalesOrder', $id);
    }

    /**
     * Create exception for SalesInvoice not found.
     */
    public static function salesInvoice(string $id): self
    {
        return new self('SalesInvoice', $id);
    }

    /**
     * Create exception for PurchaseOrder not found.
     */
    public static function purchaseOrder(string $id): self
    {
        return new self('PurchaseOrder', $id);
    }

    /**
     * Create exception for Quotation not found.
     */
    public static function quotation(string $id): self
    {
        return new self('Quotation', $id);
    }

    /**
     * Create exception for Project not found.
     */
    public static function project(string $id): self
    {
        return new self('Project', $id);
    }

    /**
     * Create exception for GLAccount not found.
     */
    public static function glAccount(string $id): self
    {
        return new self('GLAccount', $id);
    }

    /**
     * Create exception for Document not found.
     */
    public static function document(string $id): self
    {
        return new self('Document', $id);
    }

    /**
     * Create exception for generic entity not found.
     */
    public static function entity(string $entity, string $id): self
    {
        return new self($entity, $id);
    }

    /**
     * Get the entity ID that was not found.
     */
    public function getEntityId(): ?string
    {
        return $this->entityId;
    }

    /**
     * Get exception data for logging.
     */
    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'entity_id' => $this->entityId,
        ]);
    }
}
