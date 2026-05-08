<?php

declare(strict_types=1);

namespace XVE\ExactonlineLaravelApi\Events\Webhooks;

class ProjectUpdated extends BaseWebhookEvent
{
    public function getEventName(): string
    {
        return 'project.updated';
    }

    public function getEntityType(): string
    {
        return 'Project';
    }

    public function getActionType(): string
    {
        return 'Updated';
    }

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

    /** @return array<string, mixed> */
    public function getModifiedFields(): array
    {
        return $this->getData('ModifiedFields', []);
    }
}
