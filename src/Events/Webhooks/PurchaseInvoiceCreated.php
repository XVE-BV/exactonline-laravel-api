<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Events\Webhooks;

class PurchaseInvoiceCreated extends BaseWebhookEvent
{
    public function getInvoiceId(): ?string
    {
        return $this->getEntityId();
    }

    public function getInvoiceNumber(): ?string
    {
        return $this->getData('InvoiceNumber');
    }

    public function getSupplierId(): ?string
    {
        return $this->getData('Supplier');
    }

    public function getAmount(): ?float
    {
        $value = $this->getData('Amount');

        return $value !== null ? (float) $value : null;
    }
}
