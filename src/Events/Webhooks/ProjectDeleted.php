<?php

declare(strict_types=1);

namespace XVE\Exactonline\Events\Webhooks;

class ProjectDeleted extends BaseWebhookEvent
{
    public function getEventName(): string
    {
        return 'project.deleted';
    }

    public function getEntityType(): string
    {
        return 'Project';
    }

    public function getActionType(): string
    {
        return 'Deleted';
    }

    public function getProjectId(): ?string
    {
        return $this->getEntityId();
    }
}
