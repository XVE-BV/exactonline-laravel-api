<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Events\Webhooks;

class ProjectDeleted extends BaseWebhookEvent
{
    public function getProjectId(): ?string
    {
        return $this->getEntityId();
    }
}
