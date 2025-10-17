<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Events\Webhooks;

use Illuminate\Contracts\Queue\ShouldQueue;

class ProjectCreated extends BaseWebhookEvent implements ShouldQueue
{
    public function getProjectId(): ?string
    {
        return $this->getEntityId();
    }

    public function getCode(): ?string
    {
        return $this->getData('Code');
    }

    public function getDescription(): ?string
    {
        return $this->getData('Description');
    }

    public function getAccount(): ?string
    {
        return $this->getData('Account');
    }
}
